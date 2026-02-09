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
        .annexure-header img.logo {
            height: 55px;
        }

        .annexure-header img.pattern {
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
        }

        /* TITLE */
        .annexure-title {
            margin-top: 40px;
            margin-bottom: 25px;
        }

        .annexure-title h4 {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* EMPLOYEE INFO */
        .emp-info p {
            margin-bottom: 4px;
            font-weight: 600;
        }

        /* TABLE */
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            color: #000000;
            margin-top: 25px;
            font-size: 12px;
        }

        .salary-table th,
        .salary-table td {
            border: 1px solid #000000;
            padding: 3px 4px;
        }

        .salary-table .fw-bold {
            font-weight: bold !important;
        }

        .section-row {
            background-color: #fff !important;
            font-weight: bold !important;
        }

        .total-row {
            background-color: #fff !important;
        }

        .net-pay-row {
            background-color: #fff !important;
        }

        .ctc-row {
            background-color: #fff !important;
        }

        /* FOOTER */
        .annexure-footer {
            position: absolute;
            bottom: 25px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 12px;
            color: #000;
        }

        .annexure-footer span {
            display: block;
            margin-top: 4px;
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
            
            /* Hide buttons and other elements that shouldn't print */
            .breadcrumb,
            .float-end,
            .btn-group,
            .page-header,
            .breadcrumb-nav,
            .float-end.ms-auto {
                display: none !important;
            }
            
            /* Ensure proper page breaks */
            .salary-table {
                page-break-inside: avoid;
            }
            
            .annexure-footer {
                position: fixed;
                bottom: 25px;
            }
        }

        /* Hide print elements on screen */
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
                        <!-- <button class="btn btn-white" onclick="printPayslip()"><i class="fa-solid fa-print fa-lg"></i> {{ __('Print') }}</button> -->
                    </div>
                </div>
            </x-slot>
        </x-breadcrumb>
        <!-- /Page Header -->

        <div class="annexure-page" id="payslipSection">

            {{-- HEADER --}}
            <div class="annexure-header">
                <img src="{{ asset('images/bingo.png') }}" class="logo" alt="Logo">
                <img src="{{ asset('images/latterhead.png') }}" class="pattern" alt="">
            </div>

            {{-- TITLE --}}
            <div class="annexure-title">
                <strong>Payslip of the month of {{ $payslip->payslip_date ?? format_date($payslip->created_at) }}</strong>
            </div>

            {{-- EMPLOYEE INFO --}}
            <div class="emp-info">
                <p>Employee Name: {{ $payslip->employee->user->fullname }}</p>
                <p>Designation: {{ $employee->designation->name ?? 'XX' }}</p>
                <p>Location: {{ $employee->location ?? 'Noida' }}</p>
                <p>Date of Joining: {{ format_date($employee->date_joined) }}</p>
                <!-- <p>Salary Month: {{ $payslip->payslip_date ?? format_date($payslip->created_at) }}</p> -->
            </div>

            {{-- SALARY TABLE --}}
            <table class="salary-table">
                <thead>
                    <tr>
                        <th width="40%">SECTION</th>
                        <th width="30%">AMOUNT</th>
                        <th width="30%">DETAILS</th>
                    </tr>
                </thead>
                <tbody>

                    @php
                        // Calculate totals
                        $baseSalary = $employee->salaryDetails->base_salary ?? 0;
                        $totalAllowances = $allowances->sum('amount') ?? 0;
                        $totalDeductions = $deductions->sum('amount') ?? 0;
                        $grossSalary = $baseSalary + $totalAllowances;
                        $hraAllowance = $allowances->where('name', 'HRA')->first()->amount ?? 0;
                        $otherAllowances = $totalAllowances - $hraAllowance;
                        
                        // Employer contributions
                        $employerPF = 0;
                        $employerESI = 0;
                        foreach($deductions as $deduction) {
                            if(stripos($deduction->name, 'employer') !== false || stripos($deduction->name, 'pf') !== false) {
                                $employerPF += $deduction->amount ?? 0;
                            }
                            if(stripos($deduction->name, 'employer') !== false || stripos($deduction->name, 'esi') !== false) {
                                $employerESI += $deduction->amount ?? 0;
                            }
                        }
                        
                        $totalEmployerContribution = $employerPF + $employerESI;
                        $ctc = $grossSalary + $totalEmployerContribution;
                    @endphp   

                    {{-- Monthly Salary Structure (Earnings) --}}
                    @if($baseSalary > 0)
                    <tr>
                        <td>Basic Salary</td>
                        <td>{{ $currency }} {{ number_format($baseSalary, 2) }}</td>
                        <td>50% of Gross Salary</td>
                    </tr>
                    @endif
                    
                    @foreach($allowances as $allowance)
                    <tr>
                        <td>{{ $allowance->name }}</td>
                        <td>{{ $currency }} {{ number_format($allowance->amount, 2) }}</td>
                        @if(strtolower($allowance->name) == 'hra')
                        <td>{{ number_format(($hraAllowance / $grossSalary) * 100) }}% of basic Salary</td>
                        @else
                        <td></td>
                        @endif
                    </tr>
                    @endforeach
                    <tr class="fw-bold total-row">
                        <td>Total Gross Salary</td>
                        <td>{{ $currency }} {{ number_format($grossSalary, 2) }}</td>
                        <td></td>
                    </tr>

                    {{-- Statutory Deductions --}}
                    <tr class="section-row">
                        <td colspan="3">Statutory Deductions</td>
                    </tr>
                    @php $hasDeductions = false; @endphp
                    @foreach($deductions as $deduction)
                        @if(stripos($deduction->name, 'employee') !== false || stripos($deduction->name, 'pf') !== false || stripos($deduction->name, 'esi') !== false)
                            @php $hasDeductions = true; @endphp
                            <tr>
                                <td>{{ $deduction->name }}</td>
                                <td>{{ $currency }} {{ number_format($deduction->amount, 2) }}</td>
                                <td></td>
                            </tr>
                        @endif
                    @endforeach
                    @if(!$hasDeductions)
                    <tr>
                        <td>Employee PF</td>
                        <td>{{ $currency }} 0.00</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Employee ESI</td>
                        <td>{{ $currency }} 0.00</td>
                        <td></td>
                    </tr>
                    @endif
                    <tr class="fw-bold total-row">
                        <td>Total Deductions</td>
                        <td>{{ $currency }} {{ number_format($totalDeductions, 2) }}</td>
                        <td></td>
                    </tr>

                    {{-- Net Salary (In-Hand) --}}
                    <tr class="section-row">
                        <td colspan="3">Net Salary (In-Hand)</td>
                    </tr>
                    <tr class="fw-bold net-pay-row">
                        <td>Net Pay</td>
                        <td>{{ $currency }} {{ number_format($payslip->net_pay, 2) }}</td>
                        <td></td>
                    </tr>

                    {{-- Employer Contributions (CTC) --}}
                    <tr class="section-row">
                        <td colspan="3">Employer Contributions (CTC)</td>
                    </tr>
                    @if($employerPF > 0)
                    <tr>
                        <td>Employer PF</td>
                        <td>{{ $currency }} {{ number_format($employerPF, 2) }}</td>
                        <td></td>
                    </tr>
                    @else
                    <tr>
                        <td>Employer PF</td>
                        <td>{{ $currency }} 0.00</td>
                        <td></td>
                    </tr>
                    @endif
                    @if($employerESI > 0)
                    <tr>
                        <td>Employer ESI</td>
                        <td>{{ $currency }} {{ number_format($employerESI, 2) }}</td>
                        <td></td>
                    </tr>
                    @else
                    <tr>
                        <td>Employer ESI</td>
                        <td>{{ $currency }} 0.00</td>
                        <td></td>
                    </tr>
                    @endif
                    <tr class="fw-bold total-row">
                        <td>Total Employer Contribution</td>
                        <td>{{ $currency }} {{ number_format($totalEmployerContribution, 2) }}</td>
                        <td></td>
                    </tr>

                    {{-- CTC Per Annum --}}
                    <tr class="fw-bold ctc-row">
                        <td>CTC Per Annum</td>
                        <td>{{ $currency }} {{ number_format($ctc * 12, 2) }}</td>
                        <td></td>
                    </tr>

                </tbody>
            </table>

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
    function printPayslip() {
        // Store original content
        const originalContent = document.body.innerHTML;
        const printContent = document.getElementById('payslipSection').innerHTML;
        
        // Set body to print content only
        document.body.innerHTML = printContent;
        
        // Trigger print
        window.print();
        
        // Restore original content
        document.body.innerHTML = originalContent;
        
        // Refresh the page to restore event listeners
        window.location.reload();
    }

    function generatePDF() {
        const element = document.getElementById('payslipSection');
        
        // Temporarily add print styles
        const printStyles = document.createElement('style');
        printStyles.innerHTML = `
            @media print {
                body * { visibility: hidden; }
                #payslipSection, #payslipSection * { visibility: visible; }
                #payslipSection { position: absolute; left: 0; top: 0; }
            }
        `;
        document.head.appendChild(printStyles);

        html2canvas(element, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
            onclone: function(clonedDoc) {
                clonedDoc.getElementById('payslipSection').style.padding = '50px 60px 140px 60px';
                clonedDoc.getElementById('payslipSection').style.backgroundColor = '#ffffff';
            }
        }).then(canvas => {
            // Remove temporary styles
            document.head.removeChild(printStyles);
            
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jspdf.jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
            
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 297; // A4 height in mm
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
            
            pdf.save('{{ $payslip->ps_id }}.pdf');
        }).catch(error => {
            console.error('Error generating PDF:', error);
            alert('Error generating PDF. Please try again.');
            document.head.removeChild(printStyles);
        });
    }
</script>
@endpush