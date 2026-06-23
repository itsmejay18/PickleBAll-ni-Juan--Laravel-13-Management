<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Report {{ $from->toDateString() }} to {{ $to->toDateString() }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #222; margin: 24px; font-size: 12px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #222; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 20px; margin: 0 0 4px; }
        .header p { margin: 2px 0; color: #555; }
        .meta { text-align: right; font-size: 11px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f2f2f2; text-transform: uppercase; font-size: 10px; letter-spacing: 0.03em; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: bold; border-top: 2px solid #222; }
        .badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; background: #eee; }
        .actions { margin-bottom: 16px; }
        .btn { display: inline-block; padding: 8px 16px; background: #5e72e4; color: #fff; border: 0; border-radius: 6px; text-decoration: none; font-size: 12px; cursor: pointer; }
        .empty { text-align: center; padding: 32px; color: #888; }
        @media print {
            .actions { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="header">
        <div>
            <h1>Sales Report</h1>
            <p>Pickle Ballan ni Juan</p>
            <p>Period: {{ \Illuminate\Support\Carbon::parse($from)->format('M d, Y') }} &ndash; {{ \Illuminate\Support\Carbon::parse($to)->format('M d, Y') }}</p>
        </div>
        <div class="meta">
            <p>Generated: {{ $generatedAt->format('M d, Y g:i A') }}</p>
            <p>Records: {{ count($rows) }}</p>
        </div>
    </div>

    @if (count($rows) > 0)
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Customer</th>
                    <th>Location &amp; Court</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Ref</th>
                    <th>Status</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['reservation_code'] }}</td>
                        <td>{{ $row['customer'] }}</td>
                        <td>{{ $row['location'] }} &mdash; Court {{ $row['court_number'] }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row['reservation_date'])->format('M d, Y') }}</td>
                        <td>{{ substr($row['start_time'], 0, 5) }}&ndash;{{ substr($row['end_time'], 0, 5) }}</td>
                        <td>{{ $row['reference'] }}</td>
                        <td><span class="badge">{{ ucfirst($row['payment_status'] ?: $row['reservation_status']) }}</span></td>
                        <td class="num">PHP {{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="num">Total</td>
                    <td class="num">PHP {{ number_format($total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="empty">No sales found for the selected range.</div>
    @endif

    <script>
        // Auto-open the print dialog so "Save as PDF" is one step.
        window.addEventListener('load', function () { window.print(); });
    </script>
</body>
</html>
