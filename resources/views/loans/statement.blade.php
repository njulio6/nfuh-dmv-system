<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Loan Statement - {{ $member->name }}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        /* Force page-white styling on screen and print, absolutely no dark mode overrides */
        body {
            background-color: #f4f4f5 !important; /* light zinc-100 on screen */
            color: #09090b !important; /* zinc-950 */
        }
        
        @media print {
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                max-width: 100% !important;
                width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                background-color: #ffffff !important;
            }
            
            /* Remove block card styles in print to let content break naturally across pages */
            .loan-record-block {
                border-top: none !important;
                border-left: none !important;
                border-right: none !important;
                border-bottom: 2px solid #000000 !important; /* simple line separator between loans */
                border-radius: 0 !important;
                background-color: transparent !important;
                padding: 0 !important;
                padding-bottom: 2rem !important;
                margin-bottom: 3rem !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            
            /* Print sections that should stay together on one page if possible */
            .print-avoid-break {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            /* High contrast text for print */
            .text-print-dark {
                color: #000000 !important;
            }
            .text-print-muted {
                color: #27272a !important; /* zinc-800 */
            }
            
            /* Table printing logic */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                page-break-inside: auto !important;
            }
            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            th, td {
                border: 1px solid #000000 !important;
                padding: 6px 10px !important;
                color: #000000 !important;
                background-color: transparent !important;
                font-size: 11px !important;
            }
            th {
                font-weight: 700 !important;
                text-transform: uppercase !important;
                background-color: #f3f4f6 !important; /* light gray header fill */
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .print-badge {
                border: 1px solid #000000 !important;
                background-color: transparent !important;
                color: #000000 !important;
                border-radius: 4px !important;
                padding: 1px 6px !important;
                font-weight: 700 !important;
                font-size: 10px !important;
                text-transform: uppercase !important;
            }
            .print-info-grid {
                border: 1px solid #000000 !important;
                border-radius: 6px !important;
                background-color: #ffffff !important;
                padding: 1.25rem !important;
            }
            .print-summary-box {
                border: 1px solid #000000 !important;
                border-radius: 6px !important;
                background-color: #ffffff !important;
                padding: 0.75rem !important;
            }
            
            @page {
                size: portrait;
                margin: 1.5cm;
            }
        }
    </style>
</head>
<body class="bg-zinc-100 text-zinc-900 font-sans min-h-screen py-8 px-4 md:px-8">

    <!-- Paper Sheet Container (Always white, regardless of application mode) -->
    <div class="print-container max-w-4xl mx-auto bg-white rounded-2xl border border-zinc-200 shadow-sm p-6 md:p-8 relative">
        
        <!-- Print Header Actions (Hidden in print) -->
        <div class="no-print flex justify-between items-center pb-6 border-b border-zinc-200 mb-6">
            @php
                $isAdmin = auth()->user() && auth()->user()->role === 'admin';
                $backRoute = $isAdmin ? route('loans.index') : route('member.loans.applications');
                $backLabel = $isAdmin ? 'Back to Loans' : 'Back to My Applications';
            @endphp
            <a 
                href="{{ $backRoute }}" 
                class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-500 hover:text-zinc-900 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                <span>{{ $backLabel }}</span>
            </a>
            
            <button 
                onclick="window.print()" 
                class="px-5 py-2 bg-zinc-900 hover:bg-zinc-800 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 shadow-xs transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                <span>Print Statement</span>
            </button>
        </div>

        <!-- Statement Logo/Title Grid -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b border-zinc-200 mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl border-2 border-zinc-900 text-zinc-900 flex items-center justify-center font-display font-black text-lg">
                    NF
                </div>
                <div>
                    <h1 class="font-display font-black text-xl text-zinc-900 tracking-tight leading-none text-print-dark">NFUH DMV System</h1>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 mt-1 block text-print-muted">Official Loan Statement Report</span>
                </div>
            </div>
            <div class="text-left sm:text-right text-xs">
                <p class="text-zinc-400 uppercase font-black tracking-wider text-[10px] text-print-muted">Statement Generated On</p>
                <p class="font-mono font-bold text-zinc-800 mt-0.5 text-print-dark">{{ date('F d, Y H:i:s') }}</p>
            </div>
        </div>

        <!-- Member details & summary info card -->
        <div class="print-info-grid grid grid-cols-2 gap-6 mb-8 bg-zinc-50/50 p-5 rounded-2xl border border-zinc-250">
            <!-- Left Info Column -->
            <div class="flex flex-col gap-3.5 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-zinc-900 no-print"></span>
                    <span class="text-[10px] text-zinc-400 uppercase font-bold tracking-wider text-print-muted">Member Details</span>
                </div>
                <div class="grid grid-cols-5 gap-y-2 text-zinc-800 text-print-dark">
                    <span class="col-span-2 text-zinc-400 font-semibold text-print-muted whitespace-nowrap">Name:</span>
                    <span class="col-span-3 font-bold">{{ $member->name }}</span>

                    <span class="col-span-2 text-zinc-400 font-semibold text-print-muted whitespace-nowrap">Code:</span>
                    <span class="col-span-3 font-mono font-black">{{ $member->member_code }}</span>

                    <span class="col-span-2 text-zinc-400 font-semibold text-print-muted whitespace-nowrap">Rank / Title:</span>
                    <span class="col-span-3 font-bold">{{ $member->rank->name ?? 'Warrior' }}</span>
                </div>
            </div>

            <!-- Right Info Column -->
            <div class="flex flex-col gap-3.5 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-zinc-900 no-print"></span>
                    <span class="text-[10px] text-zinc-400 uppercase font-bold tracking-wider text-print-muted">Financial Summary</span>
                </div>
                <div class="grid grid-cols-5 gap-y-2 text-zinc-800 text-print-dark">
                    <span class="col-span-2 text-zinc-400 font-semibold text-print-muted whitespace-nowrap">Outstanding Loan:</span>
                    <span class="col-span-3 font-extrabold text-zinc-900">${{ number_format(collect($loans)->sum('remaining_balance'), 2) }}</span>

                    <span class="col-span-2 text-zinc-400 font-semibold text-print-muted whitespace-nowrap">Join Date:</span>
                    <span class="col-span-3 font-bold">{{ $member->join_date ? $member->join_date->format('M d, Y') : '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Loan Records Section -->
        <div class="mb-8">
            <h3 class="font-display font-black text-sm text-zinc-900 uppercase tracking-wider mb-6 pb-2 border-b-2 border-zinc-900 text-print-dark">
                Loan Portfolio Statement
            </h3>
            
            @forelse($loans as $loan)
                <div class="loan-record-block mb-10 bg-zinc-50/30 p-6 rounded-2xl border border-zinc-200 flex flex-col gap-6">
                    
                    <!-- 1. Header & Financial summary (Kept together) -->
                    <div class="print-avoid-break flex flex-col gap-6">
                        <!-- Loan details subheader -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-4 border-b border-zinc-200 gap-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-black uppercase text-zinc-400 tracking-wider text-print-muted">Loan Request #{{ $loan->id }}</span>
                                <span class="text-lg font-black text-zinc-900 leading-none text-print-dark">${{ number_format($loan->amount, 2) }}</span>
                                <span class="text-[11px] text-zinc-500 mt-1 font-semibold text-print-muted">{{ $loan->duration_months }} Months Term &bull; Requested on {{ $loan->created_at->format('M d, Y') }}</span>
                            </div>
                                <!-- Clean text border outline badge -->
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="print-badge inline-block px-2.5 py-0.5 rounded border border-zinc-400 text-[9px] font-bold uppercase tracking-wider text-zinc-700">
                                        {{ str_replace('_', ' ', $loan->status) }}
                                    </span>
                                    @if($loan->subStatus)
                                        <span class="print-badge inline-block px-2.5 py-0.5 rounded border border-zinc-400 text-[9px] font-bold uppercase tracking-wider text-zinc-500">
                                            {{ $loan->subStatus->name }}
                                        </span>
                                    @endif
                                </div>
                        </div>

                        <!-- Financial breakdown numbers -->
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-xs">
                            <div class="print-summary-box bg-white p-3 rounded-xl border border-zinc-200">
                                <span class="text-zinc-400 block mb-0.5 text-print-muted">Principal Amount:</span>
                                <span class="font-extrabold text-zinc-900 text-print-dark">${{ number_format($loan->amount, 2) }}</span>
                            </div>
                            <div class="print-summary-box bg-white p-3 rounded-xl border border-zinc-200">
                                <span class="text-zinc-400 block mb-0.5 text-print-muted">Interest:</span>
                                <span class="font-extrabold text-zinc-900 text-print-dark">
                                    @if($loan->interest_rate > 0)
                                        {{ number_format($loan->interest_rate, 2) }}% <span class="text-[9px] font-normal">({{ $loan->interest_type === 'flat' ? 'Flat' : 'Duration' }})</span>
                                    @else
                                        No Interest
                                    @endif
                                </span>
                            </div>
                            <div class="print-summary-box bg-white p-3 rounded-xl border border-zinc-200">
                                <span class="text-zinc-400 block mb-0.5 text-print-muted">Total Repayable:</span>
                                <span class="font-extrabold text-zinc-900 text-print-dark">${{ number_format($loan->total_repayable, 2) }}</span>
                            </div>
                            <div class="print-summary-box bg-white p-3 rounded-xl border border-zinc-200">
                                <span class="text-zinc-400 block mb-0.5 text-print-muted">Paid Off:</span>
                                @php
                                    $repaidAmount = $loan->total_repayable - $loan->remaining_balance;
                                    $progressPercent = $loan->total_repayable > 0 ? (($repaidAmount / $loan->total_repayable) * 100) : 0;
                                @endphp
                                <span class="font-extrabold text-zinc-800 text-print-dark">${{ number_format($repaidAmount, 2) }}</span>
                                <span class="text-[9px] text-zinc-500 font-bold block mt-0.5">({{ number_format($progressPercent, 0) }}% Paid)</span>
                            </div>
                            <div class="print-summary-box bg-white p-3 rounded-xl border border-zinc-200">
                                <span class="text-zinc-400 block mb-0.5 text-print-muted">Outstanding:</span>
                                <span class="font-black text-zinc-900 text-print-dark">${{ number_format($loan->remaining_balance, 2) }}</span>
                            </div>
                        </div>
                    </div>



                    <!-- 3. Guarantors Table (Kept together) -->
                    <div class="print-avoid-break flex flex-col gap-2.5">
                        <h4 class="text-xs font-black uppercase text-zinc-800 tracking-wider flex items-center gap-1.5 text-print-dark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-zinc-550 no-print"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Designated Guarantors
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-zinc-100 text-[10px] font-black uppercase text-zinc-600 tracking-wider">
                                        <th class="py-2 px-3 border border-zinc-250">Guarantor Name</th>
                                        <th class="py-2 px-3 border border-zinc-250">Code</th>
                                        <th class="py-2 px-3 border border-zinc-250">Status</th>
                                        <th class="py-2 px-3 border border-zinc-250">Responded At</th>
                                        <th class="py-2 px-3 border border-zinc-250">Guarantor Comments</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 font-medium">
                                    @forelse($loan->guarantors as $g)
                                        <tr class="hover:bg-zinc-50/50 bg-white">
                                            <td class="py-2 px-3 border border-zinc-250 font-bold text-zinc-800 text-print-dark">{{ $g->guarantorMember->name }}</td>
                                            <td class="py-2 px-3 border border-zinc-250 font-mono text-[11px] text-zinc-500 text-print-muted">{{ $g->guarantorMember->member_code }}</td>
                                            <td class="py-2 px-3 border border-zinc-250 text-[10px] font-bold uppercase text-print-dark">
                                                {{ $g->status }}
                                            </td>
                                            <td class="py-2 px-3 border border-zinc-250 font-mono text-[11px] text-zinc-600 text-print-muted">
                                                {{ $g->responded_at ? $g->responded_at->format('Y-m-d H:i:s') : '-' }}
                                            </td>
                                            <td class="py-2 px-3 border border-zinc-250 italic text-zinc-555 text-print-muted">{{ $g->notes ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-4 text-center text-zinc-400 bg-white text-print-muted">No guarantors assigned.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. Repayments Log Table (Kept together) -->
                    <div class="print-avoid-break flex flex-col gap-2.5">
                        <h4 class="text-xs font-black uppercase text-zinc-800 tracking-wider flex items-center gap-1.5 text-print-dark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-zinc-550 no-print"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"/><line x1="12" y1="4" x2="12" y2="20"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
                            Repayments Log
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-zinc-100 text-[10px] font-black uppercase text-zinc-600 tracking-wider">
                                        <th class="py-2 px-3 border border-zinc-250">Payment Date</th>
                                        <th class="py-2 px-3 border border-zinc-250">Repaid Amount</th>
                                        <th class="py-2 px-3 border border-zinc-250">Method</th>
                                        <th class="py-2 px-3 border border-zinc-250">Reference Number</th>
                                        <th class="py-2 px-3 border border-zinc-250">Repayment Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 font-medium text-print-dark">
                                    @forelse($loan->repayments as $repay)
                                        <tr class="hover:bg-zinc-50/50 bg-white">
                                            <td class="py-2 px-3 border border-zinc-250 font-mono text-[11px] text-print-muted">{{ $repay->payment_date ? $repay->payment_date->format('Y-m-d') : '-' }}</td>
                                            <td class="py-2 px-3 border border-zinc-250 font-bold text-zinc-950 text-print-dark">${{ number_format($repay->amount, 2) }}</td>
                                            <td class="py-2 px-3 border border-zinc-250 capitalize text-zinc-700 text-print-muted">{{ str_replace('_', ' ', $repay->payment_method) }}</td>
                                            <td class="py-2 px-3 border border-zinc-250 font-mono text-[11px] text-zinc-500 text-print-muted">{{ $repay->reference_number ?: '-' }}</td>
                                            <td class="py-2 px-3 border border-zinc-250 text-zinc-500 text-print-muted">{{ $repay->notes ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-4 text-center text-zinc-400 bg-white text-print-muted">No repayments logged yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. Purpose and Decision Comments (Moved to bottom, shown only if present) -->
                    @if($loan->purpose || $loan->admin_notes)
                        <div class="print-avoid-break grid {{ ($loan->purpose && $loan->admin_notes) ? 'grid-cols-2' : 'grid-cols-1' }} gap-4 text-xs">
                            @if($loan->purpose)
                                <div class="flex flex-col gap-1">
                                    <span class="font-bold text-zinc-700 text-print-dark">Loan Purpose / Memo:</span>
                                    <div class="bg-white p-3 rounded-xl border border-zinc-200 italic text-zinc-655 text-print-muted">
                                        "{{ $loan->purpose }}"
                                    </div>
                                </div>
                            @endif
                            @if($loan->admin_notes)
                                <div class="flex flex-col gap-1">
                                    <span class="font-bold text-zinc-700 text-print-dark">Admin Decision Notes:</span>
                                    <div class="bg-white p-3 rounded-xl border border-zinc-200 italic text-zinc-655 text-print-muted">
                                        {{ $loan->admin_notes }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="py-12 text-center text-zinc-400 font-semibold text-xs border border-dashed border-zinc-200 rounded-2xl text-print-muted">
                    No loan requests or records found for this member.
                </div>
            @endforelse
        </div>

        <!-- Footer statement memo -->
        <div class="mt-12 text-center text-[10px] text-zinc-400 uppercase tracking-widest pt-4 border-t border-zinc-200 text-print-muted">
            End of Official Statement - NFUH DMV System
        </div>

    </div>

</body>
</html>
