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
     * Notify all admins globally and location-scoped staff/managers.
     */
    public function notifyStaffAndAdmins(
        string $subject,
        ?string $message = null,
        ?Reservation $reservation = null,
        array $metadata = []
    ): void {
        // Query existing roles in DB to prevent exceptions in unseeded tests
        $adminRoleNames = [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN];
        $existingAdminRoles = DB::table('roles')
            ->whereIn('name', $adminRoleNames)
            ->pluck('name')
            ->toArray();

        $admins = collect();
        if (! empty($existingAdminRoles)) {
            $admins = User::query()
                ->role($existingAdminRoles)
                ->get();
        }

        // 2. Get location staff if location_id is available
        $staff = collect();
        $staffRoleNames = [User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF];
        $existingStaffRoles = DB::table('roles')
            ->whereIn('name', $staffRoleNames)
            ->pluck('name')
            ->toArray();

        if ($reservation && $reservation->location_id && ! empty($existingStaffRoles)) {
            $staffIds = StaffProfile::query()
                ->where('assigned_location_id', $reservation->location_id)
                ->pluck('user_id');

            $staff = User::query()
                ->whereIn('id', $staffIds)
                ->role($existingStaffRoles)
                ->get();
        }

        // Merge and unique by ID
        $recipients = $admins->concat($staff)->unique('id');

        foreach ($recipients as $recipient) {
            $isAdmin = false;
            if (! empty($existingAdminRoles)) {
                $isAdmin = $recipient->hasAnyRole($existingAdminRoles);
            }
            $channel = $isAdmin ? 'admin' : 'staff';
            $this->notify($recipient, $channel, $subject, $message, $reservation, $metadata);
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

    /**
     * Recent unread notifications for the bell dropdown.
     *
     * @return Collection<int, NotificationLog>
     */
    public function unreadRecent(User $user, int $limit = 8): Collection
    {
        return NotificationLog::query()
            ->where('user_id', $user->id)
            ->where('notification_type', 'in_app')
            ->whereNull('read_at')
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
