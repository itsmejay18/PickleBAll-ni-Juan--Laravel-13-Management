<x-app-layout>
    <x-slot name="header">Receipt - {{ $reservation->reservation_code }}</x-slot>

    <style>
        .receipt-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
            position: relative;
        }

        .receipt-header-gradient {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            padding: 1.75rem 2rem;
            color: #ffffff;
        }

        .receipt-body {
            padding: 2.25rem 2rem;
        }

        /* Invoice styling */
        .invoice-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 0.25rem;
        }

        .invoice-code {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .invoice-badge-status {
            padding: 0.45rem 1.25rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .section-divider {
            height: 1px;
            background: radial-gradient(circle, rgba(0,0,0,0.08) 0%, rgba(0,0,0,0) 100%);
            margin: 1.75rem 0;
        }

        .meta-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.4rem;
        }

        .meta-value {
            font-size: 0.92rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.15rem;
        }

        .meta-sub {
            font-size: 0.78rem;
            color: #64748b;
            margin-bottom: 0;
        }

        /* Invoice Table */
        .receipt-table {
            width: 100%;
            margin-top: 1rem;
            border-collapse: collapse;
        }

        .receipt-table th {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            padding: 0.75rem 0;
            border-bottom: 2px solid #f1f5f9;
        }

        .receipt-table td {
            padding: 1rem 0;
            font-size: 0.88rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .receipt-table tr.total-row td, .receipt-table tr.total-row th {
            border-top: 2px solid #1e293b;
            border-bottom: none;
            padding-top: 1.25rem;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
        }

        /* Payment proof sidebar */
        .proof-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.75rem;
            margin-bottom: 1.25rem;
        }

        .proof-status-banner {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            display: inline-block;
        }

        .screenshot-frame {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px;
            background: #f8fafc;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .screenshot-frame:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #0ea5e9;
        }

        .screenshot-frame img {
            border-radius: 8px;
            width: 100%;
            display: block;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .screenshot-frame:hover img {
            transform: scale(1.02);
        }

        /* Action panel */
        .admin-action-panel {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 16px;
            padding: 1.25rem;
            margin-top: 1rem;
        }

        .action-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #78350f;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.75rem;
        }

        /* Status colors */
        .bg-light-success { background: #dcfce7 !important; }
        .text-success { color: #166534 !important; }
        .bg-light-info { background: #e0f2fe !important; }
        .text-info { color: #0369a1 !important; }
        .bg-light-danger { background: #fee2e2 !important; }
        .text-danger { color: #991b1b !important; }
        .bg-light-warning { background: #fef3c7 !important; }
        .text-warning { color: #92400e !important; }

        /* Responsive changes */
        @media(max-width: 991px) {
            .proof-sidebar-title {
                margin-top: 1.5rem;
            }
        }
    </style>

    <div class="row">
        <!-- LEFT COLUMN: Receipt Breakdown -->
        <div class="col-lg-7 col-md-12">
            <div class="card receipt-card mb-4">
                <div class="receipt-header-gradient d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <p class="invoice-title">Digital Receipt</p>
                        <h5 class="invoice-code">{{ $reservation->reservation_code }}</h5>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-white text-{{ $reservation->payment_status === 'paid' ? 'success' : ($reservation->payment_status === 'refunded' ? 'info' : 'warning') }} invoice-badge-status">
                            {{ ucwords(str_replace('_', ' ', $reservation->payment_status)) }}
                        </span>
                        <p class="text-xs text-white opacity-8 mb-0 mt-2 font-weight-bold">
                            {{ ucwords(str_replace('_', ' ', $reservation->status)) }}
                        </p>
                    </div>
                </div>
                
                <div class="receipt-body">
                    <!-- Customer & Issued Meta -->
                    <div class="row">
                        <div class="col-sm-6 mb-3 mb-sm-0">
                            <p class="meta-label">Customer</p>
                            <h6 class="meta-value">{{ trim(($profile->first_name ?? '').' '.($profile->last_name ?? '')) ?: $reservation->user?->email }}</h6>
                            <p class="meta-sub"><i class="far fa-envelope me-1"></i>{{ $reservation->user?->email }}</p>
                            @if($reservation->user?->mobile_number)
                                <p class="meta-sub"><i class="fas fa-phone-alt me-1 mt-1"></i>{{ $reservation->user?->mobile_number }}</p>
                            @endif
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <p class="meta-label">Issued</p>
                            <h6 class="meta-value">{{ optional($reservation->confirmed_at)->format('F j, Y g:i A') ?: $reservation->created_at?->format('F j, Y g:i A') }}</h6>
                            <p class="meta-sub"><i class="fas fa-globe me-1"></i>{{ $reservation->reservation_type === 'walk_in' ? 'Walk-in Booking' : 'Online Booking' }}</p>
                        </div>
                    </div>

                    <div class="section-divider"></div>

                    <!-- Court & Schedule Meta -->
                    <div class="row">
                        <div class="col-sm-6 mb-3 mb-sm-0">
                            <p class="meta-label">Location / Court</p>
                            <h6 class="meta-value">{{ $reservation->court?->location?->name }}</h6>
                            <p class="meta-sub">Court {{ $reservation->court?->court_number }} ({{ ucfirst($reservation->court?->court_type) }} | {{ ucfirst($reservation->court?->surface_type) }})</p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <p class="meta-label">Schedule</p>
                            <h6 class="meta-value">{{ $reservation->reservation_date?->format('F j, Y') }}</h6>
                            <p class="meta-sub"><i class="far fa-clock me-1"></i>{{ \Illuminate\Support\Str::of($reservation->start_time)->limit(5, '') }} - {{ \Illuminate\Support\Str::of($reservation->end_time)->limit(5, '') }}</p>
                        </div>
                    </div>

                    <div class="section-divider"></div>

                    <!-- Itemized Breakdown Table -->
                    <div class="table-responsive">
                        <table class="receipt-table">
                            <thead>
                                <tr>
                                    <th class="text-start">Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-start font-weight-bold text-dark">Court rental ({{ number_format(($reservation->grand_total - $reservation->equipment_total + $reservation->discount_amount - $reservation->tax_amount) / max($reservation->court_price_per_hour, 1), 2) }} hrs)</td>
                                    <td class="text-end">1</td>
                                    <td class="text-end">₱{{ number_format($reservation->court_price_per_hour, 2) }}</td>
                                    <td class="text-end">₱{{ number_format($reservation->court_subtotal, 2) }}</td>
                                </tr>

                                @foreach ($reservation->equipment as $line)
                                    <tr>
                                        <td class="text-start">{{ $line->equipmentType?->name }}</td>
                                        <td class="text-end">{{ $line->quantity }}</td>
                                        <td class="text-end">₱{{ number_format($line->price_per_unit, 2) }}</td>
                                        <td class="text-end">₱{{ number_format($line->price_per_unit * $line->quantity, 2) }}</td>
                                    </tr>
                                @endforeach

                                @if ($reservation->discount_amount > 0)
                                    <tr class="text-secondary">
                                        <td colspan="3" class="text-end text-sm">Discount ({{ $reservation->discount_reason ?: 'Applied' }})</td>
                                        <td class="text-end text-sm">- ₱{{ number_format($reservation->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ($reservation->tax_amount > 0)
                                    <tr class="text-secondary">
                                        <td colspan="3" class="text-end text-sm">Tax ({{ number_format($reservation->tax_rate, 2) }}%)</td>
                                        <td class="text-end text-sm">₱{{ number_format($reservation->tax_amount, 2) }}</td>
                                    </tr>
                                @endif

                                <tr class="total-row">
                                    <th colspan="3" class="text-end text-dark font-weight-bolder">Grand Total</th>
                                    <th class="text-end text-dark font-weight-bolder">₱{{ number_format($reservation->grand_total, 2) }}</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Actions bottom print bar -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top d-print-none">
                        <button type="button" class="btn bg-gradient-info mb-0 d-flex align-items-center gap-2" onclick="window.print()">
                            <i class="fas fa-print"></i> Print / Save as PDF
                        </button>
                        <a href="{{ route('modules.show', 'receipts') }}" class="btn btn-outline-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-arrow-left"></i> Back to receipts
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Payment Proofs & Actions -->
        <div class="col-lg-5 col-md-12">
            <h6 class="text-uppercase text-secondary text-xs font-weight-bold mb-3 proof-sidebar-title d-print-none">GCash Payment & Proof Details</h6>
            
            @forelse ($reservation->payments as $payment)
                <div class="proof-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="proof-status-banner bg-light-{{ $payment->status === 'verified' ? 'success' : ($payment->status === 'refunded' ? 'info' : ($payment->status === 'rejected' ? 'danger' : 'warning')) }} text-{{ $payment->status === 'verified' ? 'success' : ($payment->status === 'refunded' ? 'info' : ($payment->status === 'rejected' ? 'danger' : 'warning')) }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                            <h6 class="mb-0 mt-2 text-sm">{{ $payment->payment_reference }}</h6>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-0 font-weight-bolder text-dark">₱{{ number_format($payment->amount, 2) }}</h5>
                        </div>
                    </div>

                    <div class="mb-3 text-sm">
                        @if ($payment->gcash_reference_number)
                            <div class="d-flex justify-content-between py-2 border-bottom border-dashed text-xs text-secondary">
                                <span>Reference Number:</span>
                                <span class="font-weight-bold text-dark">{{ $payment->gcash_reference_number }}</span>
                            </div>
                        @endif
                        @if ($payment->gcash_sender_number)
                            <div class="d-flex justify-content-between py-2 border-bottom border-dashed text-xs text-secondary">
                                <span>Sender Number:</span>
                                <span class="font-weight-bold text-dark">{{ $payment->gcash_sender_number }}</span>
                            </div>
                        @endif
                        @if ($payment->verified_at)
                            <div class="d-flex justify-content-between py-2 text-xs text-secondary">
                                <span>Verified At:</span>
                                <span class="font-weight-bold text-dark">{{ \Carbon\Carbon::parse($payment->verified_at)->format('Y-m-d H:i') }}</span>
                            </div>
                        @endif
                        @if ($payment->rejection_reason)
                            <div class="alert alert-danger text-white text-xxs p-2 mt-2 mb-0 border-radius-sm">
                                <strong>Rejected Reason:</strong> {{ $payment->rejection_reason }}
                            </div>
                        @endif
                    </div>

                    @if ($payment->gcash_screenshot_path)
                        <div class="mt-3">
                            <label class="meta-label">GCash Screenshot Proof</label>
                            <div class="screenshot-frame">
                                <a href="{{ route('payments.proof.download', $payment->id) }}" target="_blank" title="Click to view full size">
                                    <img src="{{ route('payments.proof.download', $payment->id) }}" alt="GCash Screenshot" class="img-fluid">
                                </a>
                                <div class="text-center text-xxs text-secondary mt-2">
                                    <i class="fas fa-search-plus me-1 text-xs"></i> Click to open full-size image
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($payment->status === 'pending' && auth()->user()->hasAnyRole(['super_admin', 'admin', 'staff', 'location_manager']))
                        <div class="admin-action-panel d-print-none">
                            <div class="action-title">
                                <i class="fas fa-user-shield"></i>
                                Staff Actions Panel
                            </div>
                            
                            <div class="d-flex gap-2" id="verificationBtnArea-{{ $payment->id }}">
                                <form method="POST" action="{{ route('payments.approve', $payment->id) }}" class="d-inline mb-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm bg-gradient-success mb-0 d-flex align-items-center gap-1" onclick="return confirm('Approve this GCash payment and confirm booking?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                
                                <button type="button" class="btn btn-sm bg-gradient-danger mb-0 d-flex align-items-center gap-1" onclick="document.getElementById('receiptRejectForm-{{ $payment->id }}').style.display = 'block'; document.getElementById('verificationBtnArea-{{ $payment->id }}').style.display = 'none';">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>

                            <!-- Hidden Rejection Form -->
                            <div id="receiptRejectForm-{{ $payment->id }}" style="display: none;" class="mt-3">
                                <form method="POST" action="{{ route('payments.reject', $payment->id) }}" class="m-0">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label text-xxs font-weight-bold text-danger">Rejection Reason *</label>
                                        <input type="text" name="rejection_reason" class="form-control" style="font-size: 13px;" placeholder="e.g. Reference number doesn't match / Invalid screenshot" required>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" class="btn btn-sm btn-link text-secondary mb-0" onclick="document.getElementById('receiptRejectForm-{{ $payment->id }}').style.display = 'none'; document.getElementById('verificationBtnArea-{{ $payment->id }}').style.display = 'flex';">Cancel</button>
                                        <button type="submit" class="btn btn-sm bg-gradient-danger mb-0">Confirm Reject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="proof-card text-center py-4 text-secondary">
                    <i class="fas fa-receipt fa-2x mb-2 d-block"></i>
                    No payment information recorded.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
