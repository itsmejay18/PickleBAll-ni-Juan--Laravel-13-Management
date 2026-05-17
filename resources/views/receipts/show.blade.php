<x-app-layout>
    <x-slot name="header">Receipt - {{ $reservation->reservation_code }}</x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-xs text-uppercase text-secondary mb-0">Digital receipt</p>
                        <h5 class="mb-0">{{ $reservation->reservation_code }}</h5>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-gradient-{{ $reservation->payment_status === 'paid' ? 'success' : ($reservation->payment_status === 'refunded' ? 'info' : 'warning') }}">
                            {{ ucwords(str_replace('_', ' ', $reservation->payment_status)) }}
                        </span>
                        <p class="text-xs text-secondary mb-0 mt-1">{{ ucwords(str_replace('_', ' ', $reservation->status)) }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-xs text-uppercase text-secondary mb-1">Customer</p>
                            <h6 class="mb-0">
                                {{ trim(($profile->first_name ?? '').' '.($profile->last_name ?? '')) ?: $reservation->user?->email }}
                            </h6>
                            <p class="text-xs text-secondary mb-0">{{ $reservation->user?->email }}</p>
                            <p class="text-xs text-secondary mb-0">{{ $reservation->user?->mobile_number }}</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="text-xs text-uppercase text-secondary mb-1">Issued</p>
                            <h6 class="mb-0">{{ optional($reservation->confirmed_at)->format('Y-m-d H:i') ?: $reservation->created_at?->format('Y-m-d H:i') }}</h6>
                            <p class="text-xs text-secondary mb-0">{{ $reservation->reservation_type === 'walk_in' ? 'Walk-in' : 'Online' }}</p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-xs text-uppercase text-secondary mb-1">Court</p>
                            <h6 class="mb-0">{{ $reservation->court?->location?->name }} - Court {{ $reservation->court?->court_number }}</h6>
                            <p class="text-xs text-secondary mb-0">
                                {{ ucfirst($reservation->court?->court_type ?? '') }} | {{ ucfirst($reservation->court?->surface_type ?? '') }}
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="text-xs text-uppercase text-secondary mb-1">Schedule</p>
                            <h6 class="mb-0">{{ $reservation->reservation_date?->format('Y-m-d') }}</h6>
                            <p class="text-xs text-secondary mb-0">
                                {{ \Illuminate\Support\Str::of($reservation->start_time)->limit(5, '') }} - {{ \Illuminate\Support\Str::of($reservation->end_time)->limit(5, '') }}
                            </p>
                        </div>
                    </div>

                    <table class="table mb-4">
                        <thead>
                            <tr class="text-xs text-uppercase text-secondary">
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Court rental ({{ number_format(($reservation->grand_total - $reservation->equipment_total + $reservation->discount_amount - $reservation->tax_amount) / max($reservation->court_price_per_hour, 1), 2) }} hrs)</td>
                                <td class="text-end">1</td>
                                <td class="text-end">PHP {{ number_format($reservation->court_price_per_hour, 2) }}</td>
                                <td class="text-end">PHP {{ number_format($reservation->court_subtotal, 2) }}</td>
                            </tr>

                            @foreach ($reservation->equipment as $line)
                                <tr>
                                    <td>{{ $line->equipmentType?->name }}</td>
                                    <td class="text-end">{{ $line->quantity }}</td>
                                    <td class="text-end">PHP {{ number_format($line->price_per_unit, 2) }}</td>
                                    <td class="text-end">PHP {{ number_format($line->price_per_unit * $line->quantity, 2) }}</td>
                                </tr>
                            @endforeach

                            @if ($reservation->discount_amount > 0)
                                <tr>
                                    <td colspan="3" class="text-end text-secondary">Discount ({{ $reservation->discount_reason ?: 'Applied' }})</td>
                                    <td class="text-end text-secondary">- PHP {{ number_format($reservation->discount_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if ($reservation->tax_amount > 0)
                                <tr>
                                    <td colspan="3" class="text-end text-secondary">Tax ({{ number_format($reservation->tax_rate, 2) }}%)</td>
                                    <td class="text-end text-secondary">PHP {{ number_format($reservation->tax_amount, 2) }}</td>
                                </tr>
                            @endif

                            <tr class="text-dark">
                                <th colspan="3" class="text-end">Grand total</th>
                                <th class="text-end">PHP {{ number_format($reservation->grand_total, 2) }}</th>
                            </tr>
                        </tbody>
                    </table>

                    @foreach ($reservation->payments as $payment)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-xs text-uppercase text-secondary mb-1">{{ strtoupper($payment->payment_method) }}</p>
                                    <h6 class="mb-0">{{ $payment->payment_reference }}</h6>
                                    @if ($payment->gcash_reference_number)
                                        <p class="text-xs text-secondary mb-0">GCash ref: {{ $payment->gcash_reference_number }}</p>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <h6 class="mb-0 text-{{ $payment->status === 'verified' ? 'success' : ($payment->status === 'refunded' ? 'info' : 'warning') }}">
                                        PHP {{ number_format($payment->amount, 2) }}
                                    </h6>
                                    <p class="text-xs text-secondary mb-0">{{ ucfirst($payment->status) }} {{ $payment->verified_at?->format('Y-m-d H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" class="btn bg-gradient-info" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print / Save as PDF
                        </button>
                        <a href="{{ route('modules.show', 'receipts') }}" class="btn btn-outline-dark">
                            <i class="fas fa-arrow-left me-2"></i>Back to receipts
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
