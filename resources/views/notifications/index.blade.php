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
                    <div class="d-flex flex-wrap gap-2 align-items-center mt-2 mt-sm-0">
                        <a href="{{ route('modules.show', 'receipts') }}" class="btn btn-outline-primary mb-0">
                            <i class="fas fa-history me-2"></i>Book History
                        </a>
                        @if ($unreadCount > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}" class="mb-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-info mb-0">
                                    <i class="fas fa-check-double me-2"></i>Mark all as read
                                </button>
                            </form>
                        @endif
                    </div>
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
                        @php
                            $isPaymentProof = isset($notification->metadata['payment_id']);
                            $cardClick = '';
                            if ($isPaymentProof) {
                                $cardClick = "onclick=\"const f = document.getElementById('feed-readForm-" . $notification->id . "'); if (f) f.requestSubmit ? f.requestSubmit() : f.submit();\" style=\"cursor:pointer;\"";
                            }
                        @endphp
                        <div class="border rounded p-3 mb-3 {{ $isUnread ? 'bg-light-info' : '' }}" {!! $cardClick !!} style="{{ $isUnread ? 'background:rgba(94,114,228,0.06);' : '' }}">
                            @if ($isPaymentProof)
                                <form id="feed-readForm-{{ $notification->id }}" method="POST" action="{{ route('notifications.read', $notification->id) }}" class="m-0 pbj-notification-form d-none" data-pbj-target="{{ route('modules.show', 'payments') }}">
                                    @csrf
                                </form>
                            @endif
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
                                    <div class="d-flex flex-wrap gap-2" onclick="event.stopPropagation()">
                                        @if ($notification->reservation_id)
                                            <a href="{{ route('receipts.show', $notification->reservation_id) }}" class="btn btn-sm bg-gradient-info mb-0">
                                                <i class="fas fa-receipt me-1"></i>View receipt
                                            </a>
                                        @endif
                                        @if ($isUnread)
                                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary mb-0">
                                                    <i class="fas fa-check me-1"></i>Mark read
                                                </button>
                                            </form>
                                        @endif
                                    </div>
 
                                    @if ($isUnread && isset($notification->metadata['payment_id']))
                                        @php
                                            $pId = $notification->metadata['payment_id'];
                                            $amt = $notification->metadata['amount'] ?? 0;
                                            $ref = $notification->metadata['reference_number'] ?? '';
                                        @endphp
                                        <div class="mt-3 p-3 rounded bg-light border" style="max-width: 500px;" onclick="event.stopPropagation()">
                                            <h6 class="text-xs font-weight-bold text-dark mb-2">Quick Review GCash Payment</h6>
                                            <div class="d-flex justify-content-between mb-2 text-xs text-secondary">
                                                <span>Amount to Verify: <strong>₱{{ number_format($amt, 2) }}</strong></span>
                                                <span>Reference: <strong>{{ $ref }}</strong></span>
                                            </div>
                                            
                                            <div class="d-flex gap-2 mb-2">
                                                <a href="{{ route('payments.proof.download', $pId) }}" target="_blank" class="btn btn-sm bg-gradient-dark mb-0 py-1 px-3">
                                                    <i class="fas fa-eye me-1"></i>View Screenshot Proof
                                                </a>
                                                
                                                <form method="POST" action="{{ route('payments.approve', $pId) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm bg-gradient-success mb-0 py-1 px-3" onclick="return confirm('Approve this GCash payment?')">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                     </button>
                                                </form>

                                                <button type="button" class="btn btn-sm bg-gradient-danger mb-0 py-1 px-3" onclick="toggleQuickReject('{{ $pId }}')">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            </div>

                                            <!-- Hidden Rejection Form -->
                                            <div id="quickRejectForm-{{ $pId }}" style="display: none;">
                                                <form method="POST" action="{{ route('payments.reject', $pId) }}">
                                                    @csrf
                                                    <div class="mb-2">
                                                        <label class="form-label text-xxs font-weight-bold">Rejection Reason</label>
                                                        <input type="text" name="rejection_reason" class="form-control form-control-sm" placeholder="Reason (e.g. blurred image)" required>
                                                    </div>
                                                    <div class="d-flex gap-2 justify-content-end">
                                                        <button type="button" class="btn btn-sm btn-link text-secondary mb-0" onclick="toggleQuickReject('{{ $pId }}')">Cancel</button>
                                                        <button type="submit" class="btn btn-sm bg-gradient-danger mb-0">Submit Reject</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
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
