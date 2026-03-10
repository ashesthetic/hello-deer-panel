<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        h2 { color: #b45309; }
        .summary { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 16px; margin: 20px 0; }
        .our-price { font-size: 20px; font-weight: bold; color: #1d4ed8; }
        h3.cheaper-heading { color: #dc2626; margin-top: 28px; }
        h3.expensive-heading { color: #2563eb; margin-top: 28px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f3f4f6; text-align: left; padding: 10px 12px; font-size: 13px; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .price-cheaper   { color: #dc2626; font-weight: bold; }
        .price-expensive { color: #2563eb; font-weight: bold; }
        .diff-cheaper    { color: #dc2626; font-size: 12px; }
        .diff-expensive  { color: #2563eb; font-size: 12px; }
        .footer { margin-top: 30px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <h2>⛽ GasBuddy Price Alert</h2>

    <div class="summary">
        <p>Our current <strong>Regular 87</strong> price: <span class="our-price">{{ number_format($ourPrice, 1) }}¢/L</span></p>
        @if($totalCheaper > 0)
        <p>⚠️ <strong>{{ $totalCheaper }} station{{ $totalCheaper > 1 ? 's are' : ' is' }}</strong> cheaper than us within 3 miles.</p>
        @endif
        @if($totalExpensive > 0)
        <p>📈 <strong>{{ $totalExpensive }} station{{ $totalExpensive > 1 ? 's are' : ' is' }}</strong> more expensive than us within 3 miles.</p>
        @endif
    </div>

    @if($totalCheaper > 0)
    <h3 class="cheaper-heading">⚠️ Cheaper Stations ({{ $totalCheaper }})</h3>
    <table>
        <thead>
            <tr>
                <th>Station</th>
                <th>Address</th>
                <th>Distance</th>
                <th>Regular Price</th>
                <th>Difference</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cheaperStations as $station)
            <tr>
                <td>{{ $station->name }}</td>
                <td>{{ $station->address_line1 }}</td>
                <td>{{ $station->distance }}</td>
                <td class="price-cheaper">{{ number_format($station->regular_gas, 1) }}¢/L</td>
                <td class="diff-cheaper">-{{ number_format($ourPrice - $station->regular_gas, 1) }}¢</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($totalExpensive > 0)
    <h3 class="expensive-heading">📈 More Expensive Stations ({{ $totalExpensive }})</h3>
    <table>
        <thead>
            <tr>
                <th>Station</th>
                <th>Address</th>
                <th>Distance</th>
                <th>Regular Price</th>
                <th>Difference</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expensiveStations as $station)
            <tr>
                <td>{{ $station->name }}</td>
                <td>{{ $station->address_line1 }}</td>
                <td>{{ $station->distance }}</td>
                <td class="price-expensive">{{ number_format($station->regular_gas, 1) }}¢/L</td>
                <td class="diff-expensive">+{{ number_format($station->regular_gas - $ourPrice, 1) }}¢</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p class="footer">
        This alert was triggered automatically at {{ now()->format('M j, Y g:i A') }} ({{ config('app.timezone', 'UTC') }}).<br>
        Check the <a href="{{ config('app.url') }}/settings/gasbuddy">dashboard</a> for the full price comparison.
    </p>
</body>
</html>
