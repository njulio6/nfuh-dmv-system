@extends('layouts.app')

@section('content')
<div class="w-full">
    <!-- Macro KPIs Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Active Disbursements -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex items-center gap-5">
            <div class="p-4 bg-zinc-50 dark:bg-zinc-950 rounded-xl text-zinc-900 dark:text-zinc-50 border border-zinc-100 dark:border-zinc-850">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Active Disbursements</span>
                <span class="text-2xl font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($totalActiveAmount, 2) }}
                </span>
            </div>
        </div>

        <!-- Total Outstanding -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex items-center gap-5">
            <div class="p-4 bg-zinc-50 dark:bg-zinc-950 rounded-xl text-zinc-900 dark:text-zinc-50 border border-zinc-100 dark:border-zinc-850">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Total Outstanding Principal</span>
                <span class="text-2xl font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($totalRemainingBalance, 2) }}
                </span>
            </div>
        </div>

        <!-- Total Repayments Collected -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex items-center gap-5">
            <div class="p-4 bg-zinc-50 dark:bg-zinc-950 rounded-xl text-zinc-900 dark:text-zinc-50 border border-zinc-100 dark:border-zinc-850">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Total Repayments Collected</span>
                <span class="text-2xl font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($totalRepaymentsCollected, 2) }}
                </span>
            </div>
        </div>

        <!-- Defaulted Balance -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-6 shadow-3xs flex items-center gap-5">
            <div class="p-4 bg-zinc-50 dark:bg-zinc-950 rounded-xl text-zinc-900 dark:text-zinc-50 border border-zinc-100 dark:border-zinc-850">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Defaulted Balance</span>
                <span class="text-2xl font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                    ${{ number_format($totalDefaultedBalance, 2) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Section Divider / Title -->
    <div class="mb-6">
        <h2 class="text-xs font-black uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Loan Department Queues</h2>
    </div>

    <!-- Status Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        
        <!-- 1. Committee Review -->
        @php $pendingCount = $counts['pending_committee'] ?? 0; @endphp
        <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between h-52 transition-all duration-205 hover:shadow-md select-none">
            <div class="flex justify-between items-center">
                <div class="p-2.5 bg-purple-50 dark:bg-purple-950/20 rounded-xl text-purple-650 dark:text-purple-400 border border-purple-100/50 dark:border-purple-800/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div class="flex items-center gap-1.5">
                    @if($pendingCount > 0)
                        <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                    @endif
                    <span class="text-3xl font-black text-zinc-950 dark:text-white">{{ $pendingCount }}</span>
                </div>
            </div>
            <div class="my-1.5">
                <span class="text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100 block">Committee Review</span>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block mt-1 leading-normal">Awaiting credit decision and disbursement approval.</span>
            </div>
            <a 
                href="{{ route('loans.status-list', 'pending_committee') }}"
                class="w-full text-center py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-white dark:text-purple-300 text-[11px] font-bold rounded-xl transition-all shadow-3xs flex items-center justify-center gap-1.5 active:scale-[0.98] select-none cursor-pointer"
            >
                <span>Open Queue</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
 
        <!-- 2. Guarantor Signatures -->
        @php $guarantorCount = $counts['pending_guarantors'] ?? 0; @endphp
        <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between h-52 transition-all duration-205 hover:shadow-md select-none">
            <div class="flex justify-between items-center">
                <div class="p-2.5 bg-zinc-50 dark:bg-zinc-950 rounded-xl text-zinc-500 dark:text-zinc-400 border border-zinc-100 dark:border-zinc-850">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <span class="text-3xl font-black text-zinc-950 dark:text-white">{{ $guarantorCount }}</span>
            </div>
            <div class="my-1.5">
                <span class="text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100 block">Guarantor Signatures</span>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block mt-1 leading-normal">Pending guarantor approvals before review.</span>
            </div>
            <a 
                href="{{ route('loans.status-list', 'pending_guarantors') }}"
                class="w-full text-center py-2 bg-zinc-700 hover:bg-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700/80 text-white text-[11px] font-bold rounded-xl transition-all shadow-3xs flex items-center justify-center gap-1.5 active:scale-[0.98] select-none cursor-pointer"
            >
                <span>Open Queue</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
 
        <!-- Approved Requests (Pending Disbursement) -->
        @php $approvedCount = $counts['approved'] ?? 0; @endphp
        <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between h-52 transition-all duration-205 hover:shadow-md select-none">
            <div class="flex justify-between items-center">
                <div class="p-2.5 bg-blue-50 dark:bg-blue-950/20 rounded-xl text-blue-650 dark:text-blue-400 border border-blue-100/50 dark:border-blue-800/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="flex items-center gap-1.5">
                    @if($approvedCount > 0)
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    @endif
                    <span class="text-3xl font-black text-zinc-950 dark:text-white">{{ $approvedCount }}</span>
                </div>
            </div>
            <div class="my-1.5">
                <span class="text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100 block">Approved Requests</span>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block mt-1 leading-normal">Approved by committee, ready for fund release/disbursement.</span>
            </div>
            <a 
                href="{{ route('loans.status-list', 'approved') }}"
                class="w-full text-center py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-white dark:text-blue-300 text-[11px] font-bold rounded-xl transition-all shadow-3xs flex items-center justify-center gap-1.5 active:scale-[0.98] select-none cursor-pointer"
            >
                <span>Open Queue</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>

        <!-- 3. Active Loans -->
        @php $activeCount = $counts['active'] ?? 0; @endphp
        <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between h-52 transition-all duration-205 hover:shadow-md select-none">
            <div class="flex justify-between items-center">
                <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/20 rounded-xl text-emerald-650 dark:text-emerald-400 border border-emerald-100/50 dark:border-emerald-800/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <span class="text-3xl font-black text-zinc-950 dark:text-white">{{ $activeCount }}</span>
            </div>
            <div class="my-1.5">
                <span class="text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100 block">Active Disbursements</span>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block mt-1 leading-normal">Active loans currently in repayment stage.</span>
            </div>
            <a 
                href="{{ route('loans.status-list', 'active') }}"
                class="w-full text-center py-2 bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 text-white dark:text-emerald-300 text-[11px] font-bold rounded-xl transition-all shadow-3xs flex items-center justify-center gap-1.5 active:scale-[0.98] select-none cursor-pointer"
            >
                <span>Open Ledger</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
 
        <!-- 4. Defaulted Loans -->
        @php $defaultedCount = $counts['defaulted'] ?? 0; @endphp
        <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between h-52 transition-all duration-205 hover:shadow-md select-none">
            <div class="flex justify-between items-center">
                <div class="p-2.5 bg-red-50 dark:bg-red-950/20 rounded-xl text-red-655 dark:text-red-400 border border-red-100/50 dark:border-red-800/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <span class="text-3xl font-black text-zinc-950 dark:text-white">{{ $defaultedCount }}</span>
            </div>
            <div class="my-1.5">
                <span class="text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100 block">Defaulted Accounts</span>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block mt-1 leading-normal">Past due loans flags for follow up or tags.</span>
            </div>
            <a 
                href="{{ route('loans.status-list', 'defaulted') }}"
                class="w-full text-center py-2 bg-red-600 hover:bg-red-700 dark:bg-red-950/30 dark:hover:bg-red-950/50 text-white dark:text-red-400 text-[11px] font-bold rounded-xl transition-all shadow-3xs flex items-center justify-center gap-1.5 active:scale-[0.98] select-none cursor-pointer"
            >
                <span>Open List</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
 
        <!-- 5. Completed Loans -->
        @php $completedCount = $counts['completed'] ?? 0; @endphp
        <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between h-52 transition-all duration-205 hover:shadow-md select-none">
            <div class="flex justify-between items-center">
                <div class="p-2.5 bg-teal-50 dark:bg-teal-950/20 rounded-xl text-teal-600 dark:text-teal-400 border border-teal-100/50 dark:border-teal-850/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span class="text-3xl font-black text-zinc-950 dark:text-white">{{ $completedCount }}</span>
            </div>
            <div class="my-1.5">
                <span class="text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100 block">Completed Ledger</span>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block mt-1 leading-normal">Historically paid-off loan files.</span>
            </div>
            <a 
                href="{{ route('loans.status-list', 'completed') }}"
                class="w-full text-center py-2 bg-teal-600 hover:bg-teal-700 dark:bg-teal-900/30 dark:hover:bg-teal-900/50 text-white dark:text-teal-300 text-[11px] font-bold rounded-xl transition-all shadow-3xs flex items-center justify-center gap-1.5 active:scale-[0.98] select-none cursor-pointer"
            >
                <span>Open Archive</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
 
        <!-- 6. Rejected Requests -->
        @php $rejectedCount = $counts['rejected'] ?? 0; @endphp
        <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between h-52 transition-all duration-205 hover:shadow-md select-none">
            <div class="flex justify-between items-center">
                <div class="p-2.5 bg-amber-50 dark:bg-amber-950 rounded-xl text-amber-600 dark:text-amber-400 border border-amber-100/50 dark:border-amber-850/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </div>
                <span class="text-3xl font-black text-zinc-950 dark:text-white">{{ $rejectedCount }}</span>
            </div>
            <div class="my-1.5">
                <span class="text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100 block">Rejected Requests</span>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block mt-1 leading-normal">Declined by guarantors or loan committee.</span>
            </div>
            <a 
                href="{{ route('loans.status-list', 'rejected') }}"
                class="w-full text-center py-2 bg-amber-600 hover:bg-amber-700 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 text-white dark:text-amber-300 text-[11px] font-bold rounded-xl transition-all shadow-3xs flex items-center justify-center gap-1.5 active:scale-[0.98] select-none cursor-pointer"
            >
                <span>Open Archive</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
 
        <!-- 7. Repayments Transactions Log -->
        @php $repayCount = $counts['repayments'] ?? 0; @endphp
        <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between h-52 transition-all duration-205 hover:shadow-md select-none">
            <div class="flex justify-between items-center">
                <div class="p-2.5 bg-blue-50 dark:bg-blue-950 rounded-xl text-blue-600 dark:text-blue-400 border border-blue-100/50 dark:border-blue-800/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"/><line x1="12" y1="4" x2="12" y2="20"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
                </div>
                <span class="text-3xl font-black text-zinc-950 dark:text-white">{{ $repayCount }}</span>
            </div>
            <div class="my-1.5">
                <span class="text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100 block">Repayments Log</span>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block mt-1 leading-normal">Audit trail of all recorded loan repayments.</span>
            </div>
            <a 
                href="{{ route('loans.repayments-log') }}"
                class="w-full text-center py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-white dark:text-blue-300 text-[11px] font-bold rounded-xl transition-all shadow-3xs flex items-center justify-center gap-1.5 active:scale-[0.98] select-none cursor-pointer"
            >
                <span>Open Log</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
 
        <!-- 8. Repayment Requests -->
        @php $repayReqCount = \App\Models\LoanRepaymentRequest::where('status', 'pending')->count(); @endphp
        <div class="group bg-white dark:bg-zinc-900 border border-zinc-200/85 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between h-52 transition-all duration-205 hover:shadow-md select-none">
            <div class="flex justify-between items-center">
                <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/20 rounded-xl text-indigo-600 dark:text-indigo-400 border border-indigo-100/50 dark:border-indigo-800/20">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                </div>
                <div class="flex items-center gap-1.5">
                    @if($repayReqCount > 0)
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    @endif
                    <span class="text-3xl font-black text-zinc-950 dark:text-white">{{ $repayReqCount }}</span>
                </div>
            </div>
            <div class="my-1.5">
                <span class="text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100 block">Repayment Requests</span>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block mt-1 leading-normal">Repayments submitted by members pending review.</span>
            </div>
            <a 
                href="{{ route('loans.repayment-requests') }}"
                class="w-full text-center py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 text-white dark:text-indigo-300 text-[11px] font-bold rounded-xl transition-all shadow-3xs flex items-center justify-center gap-1.5 active:scale-[0.98] select-none cursor-pointer"
            >
                <span>Open Requests</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
 
    </div>
</div>
@endsection
