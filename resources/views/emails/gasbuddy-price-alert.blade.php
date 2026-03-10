<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        h2 { color: #dc2626; }
        .summary { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin: 20px 0; }
        .our-price { font-size: 20px; font-weight: bold; color: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #f3f4f6; text-align: left; padding: 10px 12px; font-size: 13px; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .cheaper { color: #dc2626; font-weight: bold; }
        .diff { color: #6b7280; font-size: 12px; }
        .footer { margin-top: 30px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <h2>⚠️ Price Alert — {{ $totalCheaper }} stations are cheaper than us</h2>

    <div class="summary">
        <p>Our current <strong>Regular 87</strong> price: <span class="our-price">{{ number_format($ourPrice, 1) }}¢/L</span></p>
        <p>{{ $totalCheaper }} station{{ $totalCheaper > 1 ? 's have' : ' has' }} a lower regular gas price right now.</p>
    </div>

    <h3>Cheaper Stations</h3>
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
                <td class="cheaper">{{ number_format($station->regular_gas, 1) }}¢/L</td>
                <td class="diff">-{{ number_format($ourPrice - $station->regular_gas, 1) }}¢</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">
        This alert was triggered automatically at {{ now()->format('M j, Y g:i A') }} ({{ config('app.timezone', 'UTC') }}).<br>
        Check the <a href="{{ config('app.url') }}/dashboard">dashboard</a> for the full price comparison.
    </p>
</body>
</html>
