<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $this->notifications->recent($user, 12)->map(fn ($notification) => [
            'id' => $notification->id,
            'subject' => $notification->subject,
            'message' => $notification->message,
            'channel' => $notification->channel,
            'reservation_id' => $notification->reservation_id,
            'unread' => $notification->read_at === null,
            'created_at' => $notification->created_at?->diffForHumans(),
        ])->all();

        return response()->json([
            'unread' => $this->notifications->unreadCount($user),
            'items' => $items,
        ]);
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $perPage = 20;
        $items = \App\Models\NotificationLog::query()
            ->where('user_id', $user->id)
            ->where('notification_type', 'in_app')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return view('notifications.index', [
            'notifications' => $items,
            'unreadCount' => $this->notifications->unreadCount($user),
        ]);
    }

    public function read(Request $request, int $notification): RedirectResponse
    {
        $this->notifications->markRead($request->user(), $notification);

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->notifications->markAllRead($request->user());

        return back()->with('status', 'All notifications marked as read.');
    }
}
