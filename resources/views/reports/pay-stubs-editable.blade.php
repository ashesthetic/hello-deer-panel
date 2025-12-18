<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <title>Pay Stubs Report - Editable</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: white;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .header-right {
            text-align: right;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        .company-info {
            display: flex;
            align-items: center;
            margin-right: 20px;
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
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            margin-bottom: 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        .form-input {
            width: 100%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: right;
            font-size: 14px;
        }
        .form-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
        
        /* Print styles - hide borders and make inputs look like plain text */
        @media print {
            @page {
                margin: 0.5in;
                size: A4;
            }
            body {
                font-size: 10px !important;
                line-height: 1.2 !important;
                margin: 0 !important;
                padding: 0 !important;
                background-color: white !important;
                color: black !important;
            }
            .form-container {
                background-color: white !important;
            }
            .header {
                margin-bottom: 10px !important;
                padding: 10px !important;
                box-shadow: none !important;
            }
            .header h1 {
                font-size: 16px !important;
            }
            .header p {
                font-size: 12px !important;
            }
            .company-logo {
                width: 40px !important;
                height: 40px !important;
            }
            .company-name {
                font-size: 14px !important;
            }
            .employee-section {
                margin-bottom: 15px !important;
                padding: 10px !important;
                page-break-inside: avoid !important;
                box-shadow: none !important;
            }
            .employee-header {
                padding: 8px !important;
                margin-bottom: 10px !important;
            }
            .employee-header h3 {
                font-size: 14px !important;
            }
            .employee-details {
                margin-bottom: 10px !important;
                gap: 10px !important;
            }
            .detail-group {
                padding: 8px !important;
            }
            .detail-group h4 {
                font-size: 12px !important;
            }
            .detail-item {
                font-size: 10px !important;
            }
            .pay-stub-section {
                margin-bottom: 15px !important;
            }
            .pay-stub-section h4 {
                font-size: 12px !important;
                margin-bottom: 8px !important;
            }
            .pay-table {
                font-size: 9px !important;
            }
            .pay-table th,
            .pay-table td {
                padding: 4px 6px !important;
            }
            .net-pay-section {
                padding: 10px !important;
                margin-bottom: 15px !important;
            }
            .net-pay-amount {
                font-size: 16px !important;
            }
            .form-input {
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                appearance: none !important;
                font-size: 9px !important;
            }
            .form-input:focus {
                border: none !important;
                box-shadow: none !important;
                outline: none !important;
            }
            .action-buttons {
                display: none !important;
            }
            .report-info {
                display: none !important;
            }

            /* Hide header title and date in print */
            .header-right {
                display: none !important;
            }
            /* Remove shadows for clean print */
            * {
                box-shadow: none !important;
            }
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
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }
        .action-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .action-btn:hover {
            background-color: #2980b9;
        }
        .action-btn.secondary {
            background-color: #95a5a6;
        }
        .action-btn.secondary:hover {
            background-color: #7f8c8d;
        }
        .action-btn.success {
            background-color: #27ae60;
        }
        .action-btn.success:hover {
            background-color: #229954;
        }
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .auto-calculate {
            background-color: #e8f4fd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="action-buttons">
        <button class="action-btn success" onclick="generatePDF()">Generate PDF</button>
        <button class="action-btn" onclick="saveChanges()">Save Changes</button>
        <button class="action-btn secondary" onclick="window.history.back()">Back</button>
    </div>

    <div class="form-container">
            <div class="header">
        <div class="header-left">
            <div class="company-info">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('hello-deer-logo.png'))) }}" alt="Hello Deer!" class="company-logo">
                <div class="company-name">Hello Deer!</div>
            </div>
        </div>
        <div class="header-right">
            <h1>Pay Stub</h1>
            <p>{{ $generatedAt }}</p>
        </div>
    </div>

        <div class="report-info">
            <h2>Report Details</h2>
            <p><strong>Pay Day:</strong> {{ $payDay }}</p>
            <p><strong>Work Period:</strong> {{ $periodStart }} to {{ $periodEnd }}</p>
            <p><strong>Total Employees:</strong> {{ count($payStubs) }}</p>
        </div>

        <form id="payStubsForm">
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
                        <div class="auto-calculate">
            
                        </div>
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
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][regular][hours]" value="{{ $payStub['earnings']['regular']['hours'] }}" onchange="calculateRow({{ $index }}, 'regular')"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][regular][rate]" value="{{ $payStub['earnings']['regular']['rate'] }}" onchange="calculateRow({{ $index }}, 'regular')"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][regular][current_amount]" value="{{ $payStub['earnings']['regular']['current_amount'] }}" onchange="updateYTD({{ $index }}, 'regular'); calculateTotal({{ $index }})"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][regular][ytd_amount]" value="{{ $payStub['earnings']['regular']['ytd_amount'] }}" onchange="updateYTDTotal({{ $index }})"></td>
                                </tr>
                                <tr>
                                    <td>Stat Holiday Paid</td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][stat_holiday_paid][hours]" value="{{ $payStub['earnings']['stat_holiday_paid']['hours'] }}" onchange="calculateRow({{ $index }}, 'stat_holiday_paid')"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][stat_holiday_paid][rate]" value="{{ $payStub['earnings']['stat_holiday_paid']['rate'] }}" onchange="calculateRow({{ $index }}, 'stat_holiday_paid')"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][stat_holiday_paid][current_amount]" value="{{ $payStub['earnings']['stat_holiday_paid']['current_amount'] }}" onchange="updateYTD({{ $index }}, 'stat_holiday_paid'); calculateTotal({{ $index }})"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][stat_holiday_paid][ytd_amount]" value="{{ $payStub['earnings']['stat_holiday_paid']['ytd_amount'] }}" onchange="updateYTDTotal({{ $index }})"></td>
                                </tr>
                                <tr>
                                    <td>Overtime</td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][overtime][hours]" value="{{ $payStub['earnings']['overtime']['hours'] }}" onchange="calculateRow({{ $index }}, 'overtime')"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][overtime][rate]" value="{{ $payStub['earnings']['overtime']['rate'] }}" onchange="calculateRow({{ $index }}, 'overtime')"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][overtime][current_amount]" value="{{ $payStub['earnings']['overtime']['current_amount'] }}" onchange="updateYTD({{ $index }}, 'overtime'); calculateTotal({{ $index }})"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][overtime][ytd_amount]" value="{{ $payStub['earnings']['overtime']['ytd_amount'] }}" onchange="updateYTDTotal({{ $index }})"></td>
                                </tr>
                                <tr>
                                    <td>VAC Paid</td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][vac_paid][hours]" value="{{ $payStub['earnings']['vac_paid']['hours'] }}" onchange="calculateRow({{ $index }}, 'vac_paid')"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][vac_paid][rate]" value="{{ $payStub['earnings']['vac_paid']['rate'] }}" onchange="calculateRow({{ $index }}, 'vac_paid')"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][vac_paid][current_amount]" value="{{ $payStub['earnings']['vac_paid']['current_amount'] }}" onchange="updateYTD({{ $index }}, 'vac_paid'); calculateTotal({{ $index }})"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][earnings][vac_paid][ytd_amount]" value="{{ $payStub['earnings']['vac_paid']['ytd_amount'] }}" onchange="updateYTDTotal({{ $index }})"></td>
                                </tr>
                                <tr class="total-row">
                                    <td><strong>Total</strong></td>
                                    <td><strong id="total-hours-{{ $index }}">{{ number_format($payStub['earnings']['total']['hours'], 2) }}</strong></td>
                                    <td><strong>${{ number_format($payStub['earnings']['total']['rate'], 2) }}</strong></td>
                                    <td><strong id="total-earnings-{{ $index }}">${{ number_format($payStub['earnings']['total']['current_amount'], 2) }}</strong></td>
                                    <td><strong id="total-ytd-{{ $index }}">${{ number_format($payStub['earnings']['total']['ytd_amount'], 2) }}</strong></td>
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
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][deductions][cpp_employee][current_amount]" value="{{ $payStub['deductions']['cpp_employee']['current_amount'] }}" onchange="calculateDeductions({{ $index }})"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][deductions][cpp_employee][ytd_amount]" value="{{ $payStub['deductions']['cpp_employee']['ytd_amount'] }}" onchange="calculateDeductions({{ $index }})"></td>
                                </tr>
                                <tr>
                                    <td>EI - Employee</td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][deductions][ei_employee][current_amount]" value="{{ $payStub['deductions']['ei_employee']['current_amount'] }}" onchange="calculateDeductions({{ $index }})"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][deductions][ei_employee][ytd_amount]" value="{{ $payStub['deductions']['ei_employee']['ytd_amount'] }}" onchange="calculateDeductions({{ $index }})"></td>
                                </tr>
                                <tr>
                                    <td>Federal Income Tax</td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][deductions][federal_income_tax][current_amount]" value="{{ $payStub['deductions']['federal_income_tax']['current_amount'] }}" onchange="calculateDeductions({{ $index }})"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][deductions][federal_income_tax][ytd_amount]" value="{{ $payStub['deductions']['federal_income_tax']['ytd_amount'] }}" onchange="calculateDeductions({{ $index }})"></td>
                                </tr>
                                <tr class="total-row">
                                    <td><strong>Total</strong></td>
                                    <td><strong id="total-deductions-{{ $index }}">${{ number_format($payStub['deductions']['total']['current_amount'], 2) }}</strong></td>
                                    <td><strong id="total-deductions-ytd-{{ $index }}">${{ number_format($payStub['deductions']['total']['ytd_amount'], 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Net Pay Section -->
                    <div class="net-pay-section">
                        <h4>Net Pay</h4>
                        <div class="net-pay-amount" id="net-pay-{{ $index }}">${{ number_format($payStub['net_pay'], 2) }}</div>
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
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][vacation_summary][vac_earned]" value="{{ $payStub['vacation_summary']['vac_earned'] }}"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][vacation_summary][vac_earned_ytd]" value="{{ $payStub['vacation_summary']['vac_earned_ytd'] ?? 0 }}"></td>
                                </tr>
                                <tr>
                                    <td>VAC Paid</td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][vacation_summary][vac_paid]" value="{{ $payStub['vacation_summary']['vac_paid'] }}"></td>
                                    <td><input type="number" step="0.01" class="form-input" name="employees[{{ $index }}][vacation_summary][vac_paid_ytd]" value="{{ $payStub['vacation_summary']['vac_paid_ytd'] ?? 0 }}"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </form>
    </div>

    <div class="footer">
        <p>This report was generated automatically by The Deer Hub Convenience Inc. traded as Hello Deer!.</p>
        <p>For questions or concerns, please contact the administrator.</p>
    </div>

    <script>
        function calculateRow(employeeIndex, rowType) {
            const hoursInput = document.querySelector(`input[name="employees[${employeeIndex}][earnings][${rowType}][hours]"]`);
            const rateInput = document.querySelector(`input[name="employees[${employeeIndex}][earnings][${rowType}][rate]"]`);
            const amountInput = document.querySelector(`input[name="employees[${employeeIndex}][earnings][${rowType}][current_amount]"]`);
            
            const hours = parseFloat(hoursInput.value) || 0;
            const rate = parseFloat(rateInput.value) || 0;
            const amount = hours * rate;
            
            amountInput.value = amount.toFixed(2);
            
            // Don't automatically update YTD - let user edit it manually
            // YTD should be the cumulative total for the year
            
            calculateTotal(employeeIndex);
        }

        function calculateTotal(employeeIndex) {
            console.log(`calculateTotal called for employee ${employeeIndex}`);
            
            const earningsTypes = ['regular', 'stat_holiday_paid', 'overtime', 'vac_paid'];
            let totalHours = 0;
            let totalEarnings = 0;
            let totalYTD = 0;
            
            earningsTypes.forEach(type => {
                const hoursInput = document.querySelector(`input[name="employees[${employeeIndex}][earnings][${type}][hours]"]`);
                const amountInput = document.querySelector(`input[name="employees[${employeeIndex}][earnings][${type}][current_amount]"]`);
                const ytdInput = document.querySelector(`input[name="employees[${employeeIndex}][earnings][${type}][ytd_amount]"]`);
                
                const hours = parseFloat(hoursInput.value) || 0;
                const amount = parseFloat(amountInput.value) || 0;
                const ytd = parseFloat(ytdInput.value) || 0;
                
                console.log(`${type}: hours=${hours}, amount=${amount}, ytd=${ytd}`);
                
                totalHours += hours;
                totalEarnings += amount;
                totalYTD += ytd;
            });
            
            console.log(`Totals: hours=${totalHours}, earnings=${totalEarnings}, ytd=${totalYTD}`);
            
            document.getElementById(`total-hours-${employeeIndex}`).textContent = totalHours.toFixed(2);
            document.getElementById(`total-earnings-${employeeIndex}`).textContent = `$${totalEarnings.toFixed(2)}`;
            document.getElementById(`total-ytd-${employeeIndex}`).textContent = `$${totalYTD.toFixed(2)}`;
            
            calculateNetPay(employeeIndex);
        }

        function calculateDeductions(employeeIndex) {
            const deductionTypes = ['cpp_employee', 'ei_employee', 'federal_income_tax'];
            let totalDeductions = 0;
            let totalDeductionsYTD = 0;
            
            deductionTypes.forEach(type => {
                const currentInput = document.querySelector(`input[name="employees[${employeeIndex}][deductions][${type}][current_amount]"]`);
                const ytdInput = document.querySelector(`input[name="employees[${employeeIndex}][deductions][${type}][ytd_amount]"]`);
                
                totalDeductions += parseFloat(currentInput.value) || 0;
                totalDeductionsYTD += parseFloat(ytdInput.value) || 0;
            });
            
            document.getElementById(`total-deductions-${employeeIndex}`).textContent = `$${totalDeductions.toFixed(2)}`;
            document.getElementById(`total-deductions-ytd-${employeeIndex}`).textContent = `$${totalDeductionsYTD.toFixed(2)}`;
            
            calculateNetPay(employeeIndex);
        }

        function calculateNetPay(employeeIndex) {
            const totalEarnings = parseFloat(document.getElementById(`total-earnings-${employeeIndex}`).textContent.replace('$', '')) || 0;
            const totalDeductions = parseFloat(document.getElementById(`total-deductions-${employeeIndex}`).textContent.replace('$', '')) || 0;
            const netPay = totalEarnings - totalDeductions;
            
            document.getElementById(`net-pay-${employeeIndex}`).textContent = `$${netPay.toFixed(2)}`;
        }

        function updateYTD(employeeIndex, rowType) {
            console.log(`updateYTD called for employee ${employeeIndex}, rowType ${rowType}`);
            
            const amountInput = document.querySelector(`input[name="employees[${employeeIndex}][earnings][${rowType}][current_amount]"]`);
            const ytdInput = document.querySelector(`input[name="employees[${employeeIndex}][earnings][${rowType}][ytd_amount]"]`);
            
            if (!amountInput || !ytdInput) {
                console.error(`Could not find inputs for employee ${employeeIndex}, rowType ${rowType}`);
                return;
            }
            
            const amount = parseFloat(amountInput.value) || 0;
            console.log(`Current amount updated to: ${amount}`);
            
            calculateTotal(employeeIndex);
        }

        function updateYTDTotal(employeeIndex) {
            console.log(`updateYTDTotal called for employee ${employeeIndex}`);
            
            const earningsTypes = ['regular', 'stat_holiday_paid', 'overtime', 'vac_paid'];
            let totalYTD = 0;
            
            earningsTypes.forEach(type => {
                const ytdInput = document.querySelector(`input[name="employees[${employeeIndex}][earnings][${type}][ytd_amount]"]`);
                const ytd = parseFloat(ytdInput.value) || 0;
                totalYTD += ytd;
            });
            
            console.log(`Total YTD calculated: ${totalYTD}`);
            document.getElementById(`total-ytd-${employeeIndex}`).textContent = `$${totalYTD.toFixed(2)}`;
        }

        function saveChanges() {
            const formData = new FormData(document.getElementById('payStubsForm'));
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // Here you would typically send the data to the server
            console.log('Saving changes:', data);
            alert('Changes saved successfully!');
        }

        function generatePDF() {
            // Here you would typically send the form data to generate a PDF
            console.log('Generating PDF...');
            window.print();
        }
    </script>
</body>
</html> 