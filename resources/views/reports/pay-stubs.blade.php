<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <title>Pay Stubs Report</title>
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
            .no-print {
                display: none !important;
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

        .employee-section {
            page-break-after: always;
            margin-bottom: 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background-color: #fff;
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
        .pay-stub-section {
            margin-bottom: 25px;
        }
        .pay-stub-section h4 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 16px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        .pay-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .pay-table th,
        .pay-table td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: right;
        }
        .pay-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        .pay-table th:first-child,
        .pay-table td:first-child {
            text-align: left;
        }
        .total-row {
            background-color: #e8f4fd;
            font-weight: bold;
        }
        .net-pay-section {
            background-color: #d4edda;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }
        .net-pay-section h4 {
            margin: 0 0 10px 0;
            color: #155724;
            font-size: 18px;
        }
        .net-pay-amount {
            font-size: 24px;
            font-weight: bold;
            color: #155724;
        }
        .vacation-summary {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .vacation-summary h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 16px;
        }
        .vacation-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .vacation-item {
            text-align: center;
            padding: 10px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .vacation-value {
            font-size: 18px;
            font-weight: bold;
            color: #856404;
            margin-bottom: 5px;
        }
        .vacation-label {
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
        .export-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .export-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 14px;
        }
        .export-btn:hover {
            background-color: #2980b9;
        }
        .export-btn.secondary {
            background-color: #95a5a6;
        }
        .export-btn.secondary:hover {
            background-color: #7f8c8d;
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
    <div class="export-buttons no-print">
        <button class="export-btn" onclick="window.print()">Export to PDF</button>
        <button class="export-btn secondary" onclick="window.history.back()">Back</button>
    </div>

    <div class="header">
        <div class="company-info">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('hello-deer-logo.png'))) }}" alt="Hello Deer!" class="company-logo">
            <div class="company-name">Hello Deer!</div>
        </div>
        <h1>Pay Stubs Report</h1>
        <p>Generated on {{ $generatedAt }}</p>
    </div>



    @foreach($payStubs as $index => $payStub)
        <div class="employee-section">
            <div class="employee-header">
                <h3>{{ $payStub['name'] }}</h3>
                <p>{{ $payStub['position'] }}</p>
            </div>

            <div class="employee-details">
                <div class="detail-group">
                    <h4>Personal Information</h4>
                    <div class="detail-item">
                        <span class="detail-label">Full Name:</span> {{ $payStub['name'] }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Position:</span> {{ $payStub['position'] }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">SIN Number:</span> {{ $payStub['sin_number'] }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Address:</span><br>
                        {{ $payStub['address'] }}
                        @if($payStub['postal_code'])
                            <br>{{ $payStub['postal_code'] }}
                        @endif
                        @if($payStub['country'])
                            <br>{{ $payStub['country'] }}
                        @endif
                    </div>
                </div>

                <div class="detail-group">
                    <h4>Pay Period Information</h4>
                    <div class="detail-item">
                        <span class="detail-label">Pay Day:</span> {{ $payStub['pay_day'] }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Work Period:</span><br>
                        {{ $payStub['period_start'] }} to {{ $payStub['period_end'] }}
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Hourly Rate:</span> ${{ number_format($payStub['hourly_rate'], 2) }}
                    </div>
                </div>
            </div>

            <!-- Earnings Section -->
            <div class="pay-stub-section">
                <h4>Earnings</h4>
                <table class="pay-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Hours</th>
                            <th>Rate</th>
                            <th>Current Amount</th>
                            <th>YTD Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Regular</td>
                            <td>{{ number_format($payStub['earnings']['regular']['hours'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['regular']['rate'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['regular']['current_amount'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['regular']['ytd_amount'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Stat Holiday Paid</td>
                            <td>{{ number_format($payStub['earnings']['stat_holiday_paid']['hours'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['stat_holiday_paid']['rate'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['stat_holiday_paid']['current_amount'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['stat_holiday_paid']['ytd_amount'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Overtime</td>
                            <td>{{ number_format($payStub['earnings']['overtime']['hours'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['overtime']['rate'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['overtime']['current_amount'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['overtime']['ytd_amount'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>VAC Paid</td>
                            <td>{{ number_format($payStub['earnings']['vac_paid']['hours'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['vac_paid']['rate'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['vac_paid']['current_amount'], 2) }}</td>
                            <td>${{ number_format($payStub['earnings']['vac_paid']['ytd_amount'], 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Total</strong></td>
                            <td><strong>{{ number_format($payStub['earnings']['total']['hours'], 2) }}</strong></td>
                            <td><strong>${{ number_format($payStub['earnings']['total']['rate'], 2) }}</strong></td>
                            <td><strong>${{ number_format($payStub['earnings']['total']['current_amount'], 2) }}</strong></td>
                            <td><strong>${{ number_format($payStub['earnings']['total']['ytd_amount'], 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Deductions Section -->
            <div class="pay-stub-section">
                <h4>Statutory Deductions</h4>
                <table class="pay-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Current Amount</th>
                            <th>YTD Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>CPP - Employee</td>
                            <td>${{ number_format($payStub['deductions']['cpp_employee']['current_amount'], 2) }}</td>
                            <td>${{ number_format($payStub['deductions']['cpp_employee']['ytd_amount'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>EI - Employee</td>
                            <td>${{ number_format($payStub['deductions']['ei_employee']['current_amount'], 2) }}</td>
                            <td>${{ number_format($payStub['deductions']['ei_employee']['ytd_amount'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Federal Income Tax</td>
                            <td>${{ number_format($payStub['deductions']['federal_income_tax']['current_amount'], 2) }}</td>
                            <td>${{ number_format($payStub['deductions']['federal_income_tax']['ytd_amount'], 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Total</strong></td>
                            <td><strong>${{ number_format($payStub['deductions']['total']['current_amount'], 2) }}</strong></td>
                            <td><strong>${{ number_format($payStub['deductions']['total']['ytd_amount'], 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Net Pay Section -->
            <div class="net-pay-section">
                <h4>Net Pay</h4>
                <div class="net-pay-amount">${{ number_format($payStub['net_pay'], 2) }}</div>
            </div>

            <!-- Vacation Summary Section -->
            <div class="pay-stub-section">
                <h4>Vacation Summary</h4>
                <table class="pay-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Current Amount</th>
                            <th>YTD Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>VAC Earned</td>
                            <td>${{ number_format($payStub['vacation_summary']['vac_earned'], 2) }}</td>
                            <td>${{ number_format($payStub['vacation_summary']['vac_earned_ytd'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>VAC Paid</td>
                            <td>${{ number_format($payStub['vacation_summary']['vac_paid'], 2) }}</td>
                            <td>${{ number_format($payStub['vacation_summary']['vac_paid_ytd'] ?? 0, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="footer">
        <p>This report was generated automatically by The Deer Hub Convenience Inc. traded as Hello Deer!.</p>
        <p>For questions or concerns, please contact the administrator.</p>
    </div>
</body>
</html> 