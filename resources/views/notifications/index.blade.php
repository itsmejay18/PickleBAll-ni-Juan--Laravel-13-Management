<x-app-layout>
    <x-slot name="header">Notifications</x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Your notifications</h6>
                        <p class="text-sm mb-0">
                            {{ $unreadCount }} unread of {{ $notifications->total() }} total.
                        </p>
                    </div>
                    @if ($unreadCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-info mb-0">
                                <i class="fas fa-check-double me-2"></i>Mark all as read
                            </button>
                        </form>
                    @endif
                </div>
                <div class="card-body">
                    @forelse ($notifications as $notification)
                        @php
                            $isUnread = $notification->read_at === null;
                            $channelColor = match ($notification->channel) {
                                'admin' => 'dark',
                                'staff' => 'info',
                                default => 'primary',
                            };
                            $icon = match ($notification->channel) {
                                'admin' => 'user-shield',
                                'staff' => 'user-cog',
                                default => 'bell',
                            };
                        @endphp
                        <div class="border rounded p-3 mb-3 {{ $isUnread ? 'bg-light-info' : '' }}" style="{{ $isUnread ? 'background:rgba(94,114,228,0.06);' : '' }}">
                            <div class="d-flex">
                                <div class="avatar avatar-sm bg-gradient-{{ $channelColor }} me-3 my-auto flex-shrink-0">
                                    <i class="fas fa-{{ $icon }} text-white text-sm"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start">
                                        <h6 class="mb-1">{{ $notification->subject }}</h6>
                                        <div class="text-end">
                                            @if ($isUnread)
                                                <span class="badge badge-sm bg-gradient-info">New</span>
                                            @endif
                                            <p class="text-xs text-secondary mb-0">
                                                <i class="fa fa-clock me-1"></i>{{ $notification->created_at?->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                    @if ($notification->message)
                                        <p class="text-sm text-secondary mb-2">{{ $notification->message }}</p>
                                    @endif
                                    <div class="d-flex flex-wrap gap-2">
                                        @if ($notification->reservation_id)
                                            <a href="{{ route('receipts.show', $notification->reservation_id) }}" class="btn btn-sm bg-gradient-info mb-0">
                                                <i class="fas fa-receipt me-1"></i>View receipt
                                            </a>
                                        @endif
                                        @if ($isUnread)
                                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary mb-0">
                                                    <i class="fas fa-check me-1"></i>Mark read
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-secondary">
                            <i class="fas fa-bell-slash fa-2x mb-3 d-block"></i>
                            <p class="mb-0">No notifications yet. We'll let you know when something happens.</p>
                        </div>
                    @endforelse

                    @if ($notifications->hasPages())
                        <div class="mt-3">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
