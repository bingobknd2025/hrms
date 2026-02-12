@extends('layouts.app')

@section('page-content')

    <style>
        /* Base styles */
        body {
            background: #f5f5f5;
        }

        .content.container-fluid {
            background: transparent;
        }

        .annexure-page {
            background: #ffffff;
            min-height: 1120px;
            padding: 50px 60px 140px 60px;
            position: relative;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0 auto;
            max-width: 800px;
        }

        /* HEADER */
        .annexure-header {
            margin-bottom: 30px;
        }

        .annexure-header img.logo {
            height: 85px;
        }

        .annexure-header .company-details {
            float: center;
            text-align: center;
            font-size: 12px;
            line-height: 1.4;
        }

        .annexure-header .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .annexure-header img.pattern {
            position: absolute;
            top: 0;
            right: 0;
            width: 350px;
            z-index: 0;
        }

        /* TITLE */
        .annexure-title {
            margin-top: 30px;
            margin-bottom: 25px;
            text-align: center;
        }

        .annexure-title h4 {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .salary-month {
            font-size: 14px;
            margin-top: 5px;
        }

        /* EMPLOYEE INFO */
        .emp-info {
            margin-bottom: 15px;
            padding: 8px;
            font-size: 13px;
        }

        .emp-info-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 5px;
        }

        .emp-info-label {
            font-weight: 600;
            width: 140px;
        }

        .emp-info-value {
            font-weight: normal;
        }

        /* TABLE */
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            color: #000000;
            margin-top: 15px;
            font-size: 12px;
            table-layout: fixed;
        }

        .salary-table th {
            border: 1px solid #000000;
            padding: 8px 6px;
            background-color: #f0f0f0 !important;
            font-weight: bold;
            text-align: center;
        }

        .salary-table td {
            border: 1px solid #000000;
            padding: 4px;
            vertical-align: top;
        }

        .salary-table .fw-bold {
            font-weight: bold !important;
        }

        .salary-table .section-header {
            background-color: #e6e6e6 !important;
            font-weight: bold;
            text-align: left;
        }

        .salary-table .total-row {
            background-color: #f9f9f9 !important;
            font-weight: bold;
        }

        .salary-table .net-pay-row {
            background-color: #f0f0f0 !important;
            font-weight: bold;
            font-size: 13px;
        }

        .salary-table .amount-col, 
        .salary-table .amount-right {
            text-align: right;
        }

        .salary-table .text-muted {
            color: #666;
            font-style: italic;
        }

        /* FOOTER */
        .annexure-footer {
            position: absolute;
            bottom: 25px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: #000;
            padding: 0 60px;
        }

        .annexure-footer span {
            display: block;
            margin-top: 4px;
        }

        .system-generated {
            margin-top: 20px;
            font-style: bold;
            padding-top: 10px;
        }

        /* Print-specific styles */
        @media print {
            body * {
                visibility: hidden;
                background: transparent !important;
            }
            
            .annexure-page, .annexure-page * {
                visibility: visible;
                background: white !important;
                color: black !important;
            }
            
            .annexure-page {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 50px 60px 140px 60px;
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }
            
            .salary-table {
                border: 1px solid #000 !important;
            }
            
            .salary-table th,
            .salary-table td {
                border: 1px solid #000 !important;
                background: white !important;
                color: black !important;
            }
            
            .salary-table th {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .salary-table .section-header {
                background-color: #e6e6e6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .breadcrumb,
            .float-end,
            .btn-group,
            .page-header,
            .breadcrumb-nav,
            .float-end.ms-auto,
            .no-print {
                display: none !important;
            }
            
            .salary-table {
                page-break-inside: avoid;
            }
            
            .annexure-footer {
                position: fixed;
                bottom: 25px;
            }
        }

        .print-only {
            display: none;
        }

        @media print {
            .print-only {
                display: block;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
    </style>

    <div class="content container-fluid no-print">

        <!-- Page Header -->
        <x-breadcrumb class="col">
            <x-slot name="title">{{ __('Payslip') }}</x-slot>
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                </li>
                <li class="breadcrumb-item active">
                    {{ __('Preview Payslip') }}
                </li>
            </ul>
            <x-slot name="right">
                <div class="col-auto float-end ms-auto">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-white" onclick='window.location.href="{{ route('payslips.index') }}"'>{{ __('Go Back') }}</button>
                        <button class="btn btn-white" onclick="generatePDF()">{{ __('PDF') }}</button>
                    </div>
                </div>
            </x-slot>
        </x-breadcrumb>
        <!-- /Page Header -->

        <div class="annexure-page" id="payslipSection">

            {{-- HEADER --}}
            <div class="annexure-header clearfix">
                <img src="{{ asset('images/bingo.png') }}" class="logo" alt="Bingo Manufacturing & Marketing Pvt. Ltd.">
                <div class="company-details">
                    <div class="company-name">Bingo Manufacturing & Marketing Pvt. Ltd.</div>
                    <div>H 102, Sector 63, Gautam Buddha Nagar, Noida 201301</div>
                    <div style="font-weight: bold;"> Salary slip for the month of {{ $payslip->payslip_date ?? \Carbon\Carbon::parse($payslip->created_at)->format('F/Y') }}</div>
                </div>
                <img src="{{ asset('images/latterhead.png') }}" class="pattern" alt="">
            </div>

            {{-- EMPLOYEE INFO --}}
            <div class="emp-info">
                <div class="emp-info-row">
                    <span class="emp-info-label">Employee Name :</span>
                    <span class="emp-info-value">{{ $payslip->employee->user->fullname }}</span>
                </div>
                <div class="emp-info-row">
                    <span class="emp-info-label">Designation :</span>
                    <span class="emp-info-value">{{ $employee->designation->name ?? 'XX' }}</span>
                </div>
                <!-- <div class="emp-info-row">
                    <span class="emp-info-label">Emp ID :</span>
                    <span class="emp-info-value">{{ $employee->emp_code ?? $employee->id ?? '45' }}</span>
                </div> -->
                <div class="emp-info-row">
                    <span class="emp-info-label">Date of Joining :</span>
                    <span class="emp-info-value">{{ \Carbon\Carbon::parse($employee->date_joined)->format('jS F Y') }}</span>
                </div>
                <div class="emp-info-row">
                    <span class="emp-info-label">Location :</span>
                    <span class="emp-info-value">{{ $employee->location ?? 'Noida' }}</span>
                </div>
                @if(!empty($employee->pan_number))
                <div class="emp-info-row">
                    <span class="emp-info-label">PAN Number :</span>
                    <span class="emp-info-value">{{ $employee->pan_number }}</span>
                </div>
                @endif
                @if(!empty($employee->bank_account_no))
                <div class="emp-info-row">
                    <span class="emp-info-label">Bank Account :</span>
                    <span class="emp-info-value">{{ $employee->bank_account_no }}</span>
                </div>
                @endif
            </div>

             @php
                // Calculate totals
                $baseSalary = $employee->salaryDetails->base_salary ?? 0;
                $totalAllowances = $allowances->sum('amount') ?? 0;
                $totalDeductions = $deductions->where('type', 'deduction')->sum('amount') ?? 0;
                $grossSalary = $baseSalary + $totalAllowances;
                
                // Identify specific allowances
                $hraAllowance = $allowances->where('name', 'HRA')->first()->amount ?? 0;
                $otherAllowances = $totalAllowances - $hraAllowance;
                
                // Identify employee deductions (for the table)
                $employeePF = $deductions->where('name', 'Employee PF')->first()->amount ?? 0;
                $employeeESI = $deductions->where('name', 'Employee ESI')->first()->amount ?? 0;
                $tdsDeduction = $deductions->where('name', 'TDS')->first()->amount ?? 0;
                $leaveDeduction = $deductions->where('name', 'Leave Deductions')->first()->amount ?? 0;
                $otherDeductions = $deductions->whereNotIn('name', ['Employee PF', 'Employee ESI', 'TDS', 'Leave Deductions'])->where('type', 'deduction')->sum('amount') ?? 0;
                
                // Employer contributions
                $employerPF = $deductions->where('name', 'Employer PF')->first()->amount ?? 0;
                $employerESI = $deductions->where('name', 'Employer ESI')->first()->amount ?? 0;
                $totalEmployerContribution = $employerPF + $employerESI;
                
                // CTC Calculation
                $ctcMonthly = $grossSalary + $totalEmployerContribution;
                $ctcAnnual = $ctcMonthly * 12;
                
                // Net Pay (should come from payslip)
                $netPay = $payslip->net_pay ?? ($grossSalary - $totalDeductions);
            @endphp   

            {{-- SALARY TABLE - MATCHING THE DESIGN --}}
            <table class="salary-table">
                <thead>
                    <tr>
                        <th width="40%">EMOLUMENTS</th>
                        <th width="20%" class="amount-col">AMOUNT (Rs.)</th>
                        <th width="40%">DEDUCTIONS</th>
                        <th width="20%" class="amount-col">AMOUNT (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Main row with Earnings and Deductions --}}
                    <tr>
                        {{-- Earnings Column --}}
                        <td style="vertical-align: top; padding: 0;">
                            <table style="width: 100%; border-collapse: collapse; border: none;">
                                @if($baseSalary > 0)
                                <tr>
                                    <td style="border: none; padding: 4px;">Basic Salary</td>
                                </tr>
                                @endif
                                
                                @if($hraAllowance > 0)
                                <tr>
                                    <td style="border: none; padding: 4px;">HRA</td>
                                </tr>
                                @endif
                                
                                @foreach($allowances as $allowance)
                                    @if(strtolower($allowance->name) != 'hra' && strtolower($allowance->name) != 'basic salary')
                                    <tr>
                                        <td style="border: none; padding: 4px;">{{ $allowance->name }}</td>
                                    </tr>
                                    @endif
                                @endforeach
                                
                                @if($otherAllowances > 0 && $allowances->whereNotIn('name', ['HRA', 'Basic Salary'])->count() == 0)
                                <tr>
                                    <td style="border: none; padding: 4px;">Other Allowances</td>
                                </tr>
                                @endif
                                
                                @php
                                    $earningsCount = ($baseSalary > 0 ? 1 : 0) + ($hraAllowance > 0 ? 1 : 0) + $allowances->whereNotIn('name', ['HRA', 'Basic Salary'])->count();
                                    $deductionsCount = ($employeePF > 0 ? 1 : 0) + ($employeeESI > 0 ? 1 : 0) + ($tdsDeduction > 0 ? 1 : 0) + ($leaveDeduction > 0 ? 1 : 0) + ($otherDeductions > 0 ? 1 : 0);
                                    $maxRows = max($earningsCount, $deductionsCount, 3);
                                @endphp
                                
                                @for($i = $earningsCount; $i < $maxRows; $i++)
                                <tr>
                                    <td style="border: none; padding: 4px;">&nbsp;</td>
                                </tr>
                                @endfor
                            </table>
                        </td>
                        
                        {{-- Earnings Amount Column --}}
                        <td style="vertical-align: top; padding: 4px; text-align: right;">
                            <table style="width: 100%; border-collapse: collapse; border: none;">
                                @if($baseSalary > 0)
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">{{ number_format($baseSalary, 2) }}</td>
                                </tr>
                                @endif
                                
                                @if($hraAllowance > 0)
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">{{ number_format($hraAllowance, 2) }}</td>
                                </tr>
                                @endif
                                
                                @foreach($allowances as $allowance)
                                    @if(strtolower($allowance->name) != 'hra' && strtolower($allowance->name) != 'basic salary')
                                    <tr>
                                        <td style="border: none; padding: 4px; text-align: right;">{{ number_format($allowance->amount, 2) }}</td>
                                    </tr>
                                    @endif
                                @endforeach
                                
                                @if($otherAllowances > 0 && $allowances->whereNotIn('name', ['HRA', 'Basic Salary'])->count() == 0)
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">{{ number_format($otherAllowances, 2) }}</td>
                                </tr>
                                @endif
                                
                                @for($i = $earningsCount; $i < $maxRows; $i++)
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">&nbsp;</td>
                                </tr>
                                @endfor
                                
                                {{-- Gross Salary Total --}}
                                <!-- <tr class="fw-bold">
                                    <td style="border: none; padding: 4px; text-align: right; border-top: 1px solid #000;">{{ number_format($grossSalary, 2) }}</td>
                                </tr> -->
                            </table>
                        </td>
                        
                        {{-- Deductions Column --}}
                        <td style="vertical-align: top; padding: 0;">
                            <table style="width: 100%; border-collapse: collapse; border: none;">
                                @if($tdsDeduction > 0)
                                <tr>
                                    <td style="border: none; padding: 4px;">TDS</td>
                                </tr>
                                @else
                                <tr>
                                    <td style="border: none; padding: 4px;">TDS</td>
                                </tr>
                                @endif
                                
                                @if($employeePF > 0)
                                <tr>
                                    <td style="border: none; padding: 4px;">Provident Fund</td>
                                </tr>
                                @else
                                <tr>
                                    <td style="border: none; padding: 4px;">Provident Fund</td>
                                </tr>
                                @endif
                                
                                @if($employeeESI > 0)
                                <tr>
                                    <td style="border: none; padding: 4px;">Employees' State Insurance</td>
                                </tr>
                                @else
                                <tr>
                                    <td style="border: none; padding: 4px;">Employees' State Insurance</td>
                                </tr>
                                @endif
                                
                                @if($leaveDeduction > 0)
                                <tr>
                                    <td style="border: none; padding: 4px;">Leave Deductions</td>
                                </tr>
                                @else
                                <tr>
                                    <td style="border: none; padding: 4px;">Leave Deductions</td>
                                </tr>
                                @endif
                                
                                @if($otherDeductions > 0)
                                <tr>
                                    <td style="border: none; padding: 4px;">Others</td>
                                </tr>
                                @else
                                <tr>
                                    <td style="border: none; padding: 4px;">Others</td>
                                </tr>
                                @endif
                                
                                {{-- Total Deductions Row --}}
                                <tr class="fw-bold">
                                    <td style="border: none; padding: 3px; border-top: 1px solid #000">Total Deductions</td>
                                </tr>
                            </table>
                        </td>
                        
                        {{-- Deductions Amount Column --}}
                        <td style="vertical-align: top; padding: 0px; text-align: right;">
                            <table style="width: 100%; border-collapse: collapse; border: none;">
                                @if($tdsDeduction > 0)
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">{{ number_format($tdsDeduction, 2) }}</td>
                                </tr>
                                @else
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">-</td>
                                </tr>
                                @endif
                                
                                @if($employeePF > 0)
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">{{ number_format($employeePF, 2) }}</td>
                                </tr>
                                @else
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">NA</td>
                                </tr>
                                @endif
                                
                                @if($employeeESI > 0)
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">{{ number_format($employeeESI, 2) }}</td>
                                </tr>
                                @else
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">NA</td>
                                </tr>
                                @endif
                                
                                @if($leaveDeduction > 0)
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">{{ number_format($leaveDeduction, 2) }}</td>
                                </tr>
                                @else
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">-</td>
                                </tr>
                                @endif
                                
                                @if($otherDeductions > 0)
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">{{ number_format($otherDeductions, 2) }}</td>
                                </tr>
                                @else
                                <tr>
                                    <td style="border: none; padding: 4px; text-align: right;">-</td>
                                </tr>
                                @endif
                                
                                {{-- Total Deductions Amount --}}
                                <tr class="fw-bold">
                                    <td style="border: none; padding: 3px; text-align: right; border-top: 1px solid #000">{{ number_format($totalDeductions, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    {{-- Gross Salary and Net Pay Row --}}
                    <tr class="fw-bold">
                        <td>Gross Salary</td>
                        <td style="text-align: right;">{{ number_format($grossSalary, 2) }}</td>
                        <td>Net Pay</td>
                        <td style="text-align: right;">{{ number_format($netPay, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- Employer Contributions & CTC Section --}}
            @if($totalEmployerContribution > 0)
            <table class="salary-table" style="margin-top: 20px;">
                <tbody>
                    <tr class="section-header">
                        <td colspan="4">Employer Contributions (CTC Components)</td>
                    </tr>
                    @if($employerPF > 0)
                    <tr>
                        <td width="40%">Employer PF</td>
                        <td width="20%" style="text-align: right;">{{ number_format($employerPF, 2) }}</td>
                        <td width="40%">Employer ESI</td>
                        <td width="20%" style="text-align: right;">{{ number_format($employerESI, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="fw-bold">
                        <td>Total Employer Contribution (Monthly)</td>
                        <td style="text-align: right;">{{ number_format($totalEmployerContribution, 2) }}</td>
                        <td>CTC Per Annum</td>
                        <td style="text-align: right;">{{ number_format($ctcAnnual, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            {{-- System Generated Footer --}}
            <div class="system-generated">
                <div style="text-align: center; font-size: 11px;">
                    <span>This is System Generated slip, Hence Signature is not required.</span>
                    <span style="display: block; margin-top: 5px;">Generated on: {{ date('d-m-Y H:i:s') }}</span>
                </div>
            </div>

            {{-- Print footer --}}
            <div class="annexure-footer print-only">
                <span>This is a system generated payslip. No signature required.</span>
                <span>Generated on: {{ date('d-m-Y H:i:s') }}</span>
            </div>
        </div>
    </div>
@endsection

@push('page-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    function generatePDF() {
        const element = document.getElementById('payslipSection');
        
        const printStyles = document.createElement('style');
        printStyles.innerHTML = `
            @media print {
                body * { visibility: hidden; }
                #payslipSection, #payslipSection * { visibility: visible; }
                #payslipSection { 
                    position: absolute; 
                    left: 0; 
                    top: 0;
                    background: white !important;
                }
                .salary-table th {
                    background-color: #f0f0f0 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        `;
        document.head.appendChild(printStyles);

        html2canvas(element, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
            allowTaint: true,
            windowWidth: 800,
            windowHeight: 1120
        }).then(canvas => {
            document.head.removeChild(printStyles);
            
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jspdf.jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
            
            const imgWidth = 210;
            const pageHeight = 297;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            let heightLeft = imgHeight;
            let position = 0;
            
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            
            pdf.save('Payslip_{{ $employee->emp_code ?? $employee->id }}_{{ $payslip->payslip_date ?? date('F_Y') }}.pdf');
        }).catch(error => {
            console.error('Error generating PDF:', error);
            alert('Error generating PDF. Please try again.');
            document.head.removeChild(printStyles);
        });
    }
</script>
@endpush