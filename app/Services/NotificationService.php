<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\Reservation;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Push an in-app notification to a single user. Email/SMS hooks would
     * be queued here in production; for now we persist the row so the
     * header dropdown can show real per-user activity immediately.
     */
    public function notify(
        User|int|null $user,
        string $channel,
        string $subject,
        ?string $message = null,
        ?Reservation $reservation = null,
        array $metadata = [],
    ): NotificationLog {
        $userId = $user instanceof User ? $user->id : $user;
        $recipient = ($user instanceof User ? $user->email : User::query()->find($userId)?->email) ?? 'system@local';

        return NotificationLog::query()->create([
            'user_id' => $userId,
            'reservation_id' => $reservation?->id,
            'notification_type' => 'in_app',
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
            'delivered_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Notify all admins/super-admins (used for new payment proofs, low stock).
     */
    public function notifyAdmins(string $subject, ?string $message = null, ?Reservation $reservation = null, array $metadata = []): void
    {
        $admins = User::query()->role([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])->get();

        foreach ($admins as $admin) {
            $this->notify($admin, 'admin', $subject, $message, $reservation, $metadata);
        }
    }

    /**
     * Notify staff at a specific location (location managers + staff).
     */
    public function notifyLocationStaff(int $locationId, string $subject, ?string $message = null, ?Reservation $reservation = null, array $metadata = []): void
    {
        $staffIds = StaffProfile::query()
            ->where('assigned_location_id', $locationId)
            ->pluck('user_id');

        $staff = User::query()
            ->whereIn('id', $staffIds)
            ->role([User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF])
            ->get();

        foreach ($staff as $member) {
            $this->notify($member, 'staff', $subject, $message, $reservation, $metadata);
        }
    }

    /**
     * Recent notifications for the bell dropdown.
     *
     * @return Collection<int, NotificationLog>
     */
    public function recent(User $user, int $limit = 8): Collection
    {
        return NotificationLog::query()
            ->where('user_id', $user->id)
            ->where('notification_type', 'in_app')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function unreadCount(User $user): int
    {
        return NotificationLog::query()
            ->where('user_id', $user->id)
            ->where('notification_type', 'in_app')
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(User $user, int $notificationId): void
    {
        NotificationLog::query()
            ->where('user_id', $user->id)
            ->where('id', $notificationId)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'status' => 'read']);
    }

    public function markAllRead(User $user): void
    {
        NotificationLog::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'status' => 'read']);
    }
}
