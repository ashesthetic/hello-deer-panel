<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <title>Work Hour Report</title>
    <style>
        @media print {
            @page {
                margin: 0;
                size: A4;
            }
            body {
                margin: 0;
                padding: 20px;
            }
            /* Remove browser headers and footers */
            html, body {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            /* Hide browser UI elements */
            @page :first {
                margin-top: 0;
            }
            @page :left {
                margin-left: 0;
            }
            @page :right {
                margin-right: 0;
            }
            /* Ensure no browser-added content */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #7f8c8d;
        }
        .company-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .company-logo {
            width: 60px;
            height: 60px;
            margin-right: 15px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        .report-info {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .report-info h2 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 18px;
        }
        .report-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .employee-section {
            page-break-after: always;
            margin-bottom: 40px;
        }
        .employee-section:last-child {
            page-break-after: avoid;
        }
        .employee-header {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .employee-header h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
        }
        .employee-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-group {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .detail-group h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .detail-item {
            margin: 5px 0;
            font-size: 14px;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .work-summary {
            background-color: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .work-summary h4 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .summary-item {
            text-align: center;
            padding: 10px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .summary-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
        }
        @media print {
            .employee-section {
                page-break-after: always;
            }
            .employee-section:last-child {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('hello-deer-logo.png'))) }}" alt="Hello Deer!" class="company-logo">
            <div class="company-name">Hello Deer!</div>
        </div>
        <h1>Work Hour Report</h1>
        <p>Generated on {{ $generatedAt }}</p>
    </div>

    <div class="report-info">
        <h2>Report Details</h2>
        <p><strong>Pay Day:</strong> {{ $payDay }}</p>
        <p><strong>Work Period:</strong> {{ $periodStart }} to {{ $periodEnd }}</p>
        <p><strong>Total Employees:</strong> {{ count($employees) }}</p>
    </div>

    @php
        $totalHours = 0;
        $totalEarnings = 0;
    @endphp

    @foreach($employees as $index => $employee)
        @php
            $totalHours += $employee['total_hours'];
            $totalEarnings += $employee['total_earnings'];
        @endphp
        
        <div class="employee-section">
            <div class="employee-header">
                <h3>{{ $employee['name'] }}</h3>
                <p>{{ $employee['position'] }}</p>
            </div>

            <div class="employee-details">
                <div class="detail-group">
                    <h4>Personal Information</h4>
                    <div class="detail-item">
                        <span class="detail-label">Full Name:</span> {{ $employee['name'] }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Position:</span> {{ $employee['position'] }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">SIN Number:</span> {{ $employee['sin_number'] }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Address:</span><br>
                        {{ $employee['address'] }}
                        @if($employee['postal_code'])
                            <br>{{ $employee['postal_code'] }}
                        @endif
                        @if($employee['country'])
                            <br>{{ $employee['country'] }}
                        @endif
                    </div>
                </div>

                <div class="detail-group">
                    <h4>Pay Period Information</h4>
                    <div class="detail-item">
                        <span class="detail-label">Pay Day:</span> {{ $payDay }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Work Period:</span><br>
                        {{ $periodStart }} to {{ $periodEnd }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Hourly Rate:</span> ${{ number_format($employee['hourly_rate'], 2) }}
                    </div>
                </div>
            </div>

            <div class="work-summary">
                <h4>Work Summary</h4>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value">{{ number_format($employee['total_hours'], 2) }}</div>
                        <div class="summary-label">Total Hours</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">${{ number_format($employee['hourly_rate'], 2) }}</div>
                        <div class="summary-label">Hourly Rate</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">${{ number_format($employee['total_earnings'], 2) }}</div>
                        <div class="summary-label">Total Earnings</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="footer">
        <p>This report was generated automatically by The Deer Hub Convenience Inc. traded as Hello Deer!.</p>
        <p>For questions or concerns, please contact the administrator.</p>
    </div>
</body>
</html> 