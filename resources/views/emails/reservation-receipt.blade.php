@php
    $hours = max(0, (strtotime($reservation->end_time) - strtotime($reservation->start_time)) / 3600);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $reservation->reservation_code }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background:#f4f6f8; padding:24px;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
        <tr>
            <td style="padding:24px 28px;background:linear-gradient(135deg,#5e72e4,#825ee4);color:#fff;">
                <p style="margin:0;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;opacity:0.85;">Pickle Ballan ni Juan</p>
                <h1 style="margin:6px 0 0;font-size:22px;font-weight:700;">Reservation receipt</h1>
                <p style="margin:6px 0 0;font-size:14px;opacity:0.9;">Code: <strong>{{ $reservation->reservation_code }}</strong></p>
            </td>
        </tr>
        <tr>
            <td style="padding:24px 28px;color:#172033;font-size:14px;line-height:1.55;">
                <p style="margin:0 0 12px 0;">Hi {{ trim(($profile->first_name ?? '').' '.($profile->last_name ?? '')) ?: $reservation->user?->email }}, your booking is confirmed.</p>

                <table cellpadding="0" cellspacing="0" width="100%" style="margin:12px 0;">
                    <tr>
                        <td style="padding:6px 0;color:#475569;width:140px;">Branch</td>
                        <td style="padding:6px 0;font-weight:600;">{{ $reservation->location?->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#475569;">Court</td>
                        <td style="padding:6px 0;font-weight:600;">Court {{ $reservation->court?->court_number }} ({{ ucfirst($reservation->court?->court_type ?? '') }})</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#475569;">Date</td>
                        <td style="padding:6px 0;font-weight:600;">{{ $reservation->reservation_date?->format('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#475569;">Time</td>
                        <td style="padding:6px 0;font-weight:600;">{{ \Illuminate\Support\Str::of($reservation->start_time)->limit(5, '') }} - {{ \Illuminate\Support\Str::of($reservation->end_time)->limit(5, '') }}</td>
                    </tr>
                </table>

                <table cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse;margin:16px 0;font-size:13px;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th align="left" style="padding:8px;border-bottom:1px solid #e2e8f0;">Item</th>
                            <th align="right" style="padding:8px;border-bottom:1px solid #e2e8f0;">Qty</th>
                            <th align="right" style="padding:8px;border-bottom:1px solid #e2e8f0;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding:8px;border-bottom:1px solid #f1f5f9;">Court rental ({{ number_format($hours, 2) }} hr)</td>
                            <td align="right" style="padding:8px;border-bottom:1px solid #f1f5f9;">1</td>
                            <td align="right" style="padding:8px;border-bottom:1px solid #f1f5f9;">PHP {{ number_format($reservation->court_subtotal, 2) }}</td>
                        </tr>
                        @foreach ($reservation->equipment as $line)
                            <tr>
                                <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $line->equipmentType?->name }}</td>
                                <td align="right" style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $line->quantity }}</td>
                                <td align="right" style="padding:8px;border-bottom:1px solid #f1f5f9;">PHP {{ number_format($line->price_per_unit * $line->quantity, 2) }}</td>
                            </tr>
                        @endforeach
                        @if ($reservation->discount_amount > 0)
                            <tr>
                                <td colspan="2" align="right" style="padding:8px;border-bottom:1px solid #f1f5f9;color:#475569;">Discount</td>
                                <td align="right" style="padding:8px;border-bottom:1px solid #f1f5f9;color:#475569;">- PHP {{ number_format($reservation->discount_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if ($reservation->tax_amount > 0)
                            <tr>
                                <td colspan="2" align="right" style="padding:8px;border-bottom:1px solid #f1f5f9;color:#475569;">Tax ({{ number_format($reservation->tax_rate, 2) }}%)</td>
                                <td align="right" style="padding:8px;border-bottom:1px solid #f1f5f9;color:#475569;">PHP {{ number_format($reservation->tax_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="2" align="right" style="padding:10px 8px;font-weight:700;">Grand total</td>
                            <td align="right" style="padding:10px 8px;font-weight:700;">PHP {{ number_format($reservation->grand_total, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                @foreach ($reservation->payments as $payment)
                    <p style="margin:8px 0;font-size:13px;">
                        <strong>{{ strtoupper($payment->payment_method) }}</strong> &middot; {{ $payment->payment_reference }}
                        @if ($payment->gcash_reference_number)
                            &middot; GCash ref {{ $payment->gcash_reference_number }}
                        @endif
                        &middot; PHP {{ number_format($payment->amount, 2) }}
                        ({{ ucfirst($payment->status) }})
                    </p>
                @endforeach

                <p style="margin:24px 0 0;font-size:12px;color:#64748b;">Show this email or your in-app receipt at the front desk on game day.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:16px 28px;background:#f8fafc;color:#64748b;font-size:12px;">
                Pickle Ballan ni Juan &middot; {{ config('app.name') }}
            </td>
        </tr>
    </table>
</body>
</html>
