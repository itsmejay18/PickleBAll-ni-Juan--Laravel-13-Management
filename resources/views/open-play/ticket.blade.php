<x-app-layout>
    <x-slot name="header">Open Play Ticket</x-slot>

    @php
        $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    @endphp

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card overflow-hidden">
                    <div class="card-header bg-gradient-success text-center text-white py-4">
                        <i class="fas fa-ticket-alt fa-2x mb-2"></i>
                        <h4 class="text-white mb-0">Open Play Ticket</h4>
                        <p class="text-white opacity-8 text-sm mb-0">Pickle Ballan ni Juan</p>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <span class="badge bg-gradient-dark px-4 py-2" style="font-size: 1rem;">SLOT #{{ $registration->slot_number }}</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <p class="text-xs text-secondary mb-0">Event Date</p>
                                <h6 class="mb-0">{{ optional($event)->event_date?->format('l, M d, Y') ?? '—' }}</h6>
                            </div>
                            <div class="col-6">
                                <p class="text-xs text-secondary mb-0">Event Time</p>
                                <h6 class="mb-0">
                                    {{ $event ? \Carbon\Carbon::parse($event->start_time)->format('g:i A') : '—' }}
                                    – {{ $event ? \Carbon\Carbon::parse($event->end_time)->format('g:i A') : '' }}
                                </h6>
                            </div>
                            <div class="col-6">
                                <p class="text-xs text-secondary mb-0">Entrance Fee</p>
                                <h6 class="mb-0 text-success">PHP {{ number_format((float) $registration->amount_paid, 2) }}</h6>
                            </div>
                            <div class="col-6">
                                <p class="text-xs text-secondary mb-0">Registration Status</p>
                                <h6 class="mb-0">
                                    <span class="badge bg-gradient-{{ $registration->status === 'checked_in' ? 'success' : 'warning' }}">
                                        {{ ucfirst(str_replace('_',' ', $registration->status)) }}
                                    </span>
                                </h6>
                            </div>
                        </div>

                        <hr class="horizontal dark my-4">

                        <div class="text-center">
                            <p class="text-xs text-secondary mb-1">Present this ticket at the venue for check-in.</p>
                            <a href="{{ route('open-play.index') }}" class="btn bg-gradient-dark mb-0"><i class="fas fa-arrow-left me-1"></i>Back to Open Play</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
