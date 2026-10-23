<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin-bottom: 0; }
        h2 { font-size: 14px; margin-top: 22px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #ddd; }
        td.num, th.num { text-align: right; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h1>Sales report</h1>
    <p class="muted">
        {{ $filters['from'] }} to {{ $filters['to'] }}
        @if ($filters['region']) &middot; {{ $filters['region'] }} @endif
        @if ($filters['category']) &middot; {{ $filters['category'] }} @endif
    </p>

    <table>
        <tr><th>Revenue</th><th>Orders</th><th>Average order</th><th>Cancelled</th></tr>
        <tr>
            <td>Rs {{ number_format($report['revenue'] / 100, 2) }}</td>
            <td>{{ number_format($report['orders']) }}</td>
            <td>Rs {{ number_format($report['average_order'] / 100, 2) }}</td>
            <td>{{ $report['cancelled_rate'] }}%</td>
        </tr>
    </table>

    <h2>Revenue by month</h2>
    <table>
        @foreach ($report['monthly'] as $month => $revenue)
            <tr><td>{{ $month }}</td><td class="num">Rs {{ number_format($revenue / 100, 2) }}</td></tr>
        @endforeach
    </table>

    @foreach (['by_category' => 'Revenue by category', 'by_region' => 'Revenue by region'] as $key => $title)
        <h2>{{ $title }}</h2>
        <table>
            @foreach ($report[$key] as $label => $revenue)
                <tr><td>{{ $label }}</td><td class="num">Rs {{ number_format($revenue / 100, 2) }}</td></tr>
            @endforeach
        </table>
    @endforeach
</body>
</html>
