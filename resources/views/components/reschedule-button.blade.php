@props(['reservation', 'showLockStatus' => true])

@php
    $isLocked = $reservation->reschedule_locked ?? false;
    $canUnlock = auth()->user()?->hasAnyRole(['super_admin', 'admin', 'staff']);
    $isOwner = $reservation->user_id === auth()->id();
    $canReschedule = !$isLocked && $isOwner;
@endphp

<div class="reschedule-actions d-flex gap-2 align-items-center">
    {{-- Lock Status Badge --}}
    @if($showLockStatus && $isLocked)
        <div class="badge bg-danger" title="{{ $reservation->reschedule_locked_reason ?? 'Rescheduling is locked' }}">
            <i class="fas fa-lock me-1"></i>Locked
        </div>
    @endif

    {{-- Client Reschedule Button --}}
    @if($canReschedule)
        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" 
                data-bs-target="#rescheduleModal{{ $reservation->id }}">
            <i class="fas fa-calendar-alt me-1"></i>Reschedule
        </button>

        {{-- Reschedule Modal --}}
        <div class="modal fade" id="rescheduleModal{{ $reservation->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reschedule Reservation {{ $reservation->reservation_code }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('reservations.reschedule', $reservation) }}">
                        @csrf
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">New Date</label>
                                    <input type="date" name="reservation_date" class="form-control" 
                                           min="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                            </div>
                            <div class="alert alert-info small">
                                <strong>Current Schedule:</strong><br>
                                {{ $reservation->reservation_date->format('M d, Y') }} | 
                                {{ \Illuminate\Support\Str::of($reservation->start_time)->limit(5, '') }} - 
                                {{ \Illuminate\Support\Str::of($reservation->end_time)->limit(5, '') }}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Reschedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @elseif($isLocked && !$canReschedule)
        <span class="text-danger small">
            <i class="fas fa-lock me-1"></i>
            {{ $reservation->reschedule_locked_reason ?? 'Rescheduling is restricted. Admin opens it only when needed.' }}
        </span>
    @endif

    {{-- Admin Lock/Unlock Button --}}
    @if($canUnlock)
        @if($isLocked)
            <form method="POST" action="{{ route('reservations.reschedule.unlock', $reservation) }}" 
                  class="d-inline" onsubmit="return confirm('Unlock reschedule for this reservation?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-unlock me-1"></i>Unlock
                </button>
            </form>
        @else
            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" 
                    data-bs-target="#lockModal{{ $reservation->id }}">
                <i class="fas fa-lock me-1"></i>Lock
            </button>

            {{-- Lock Modal --}}
            <div class="modal fade" id="lockModal{{ $reservation->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Lock Reschedule - {{ $reservation->reservation_code }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('reservations.reschedule.lock', $reservation) }}">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Reason for locking</label>
                                    <textarea name="reason" class="form-control" rows="3" 
                                              placeholder="e.g., Rain closure 7:00 AM - 12:00 PM" required></textarea>
                                    <small class="text-muted">This reason will be shown to the customer when rescheduling is opened.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-warning">Lock Reschedule</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
