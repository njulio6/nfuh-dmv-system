@extends('layouts.app')

@section('content')
<div 
    x-data="{ 
        showGuarantorsModal: false,
        activeGuarantors: [],
        showResponseModal: false,
        responseActionUrl: '',
        responseType: 'approve',
        responseNotes: '',
        responseRequest: null,
        openResponseModal(actionUrl, type, req) {
            this.responseActionUrl = actionUrl;
            this.responseType = type;
            this.responseRequest = req;
            this.responseNotes = '';
            this.showResponseModal = true;
        },
        getMemberName(req) {
            if (!req) return '';
            const request = req.loan_request || req.loanRequest;
            if (!request || !request.member) return '';
            const m = request.member;
            return m.name || ((m.first_name || '') + ' ' + (m.last_name || '')).trim();
        }
    }" 
    class="w-full flex flex-col gap-6"
>
    <!-- Member Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2">
        <!-- Outstanding Loan Balance -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">My Outstanding Balance</span>
            <div class="text-2xl font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                ${{ number_format($member->outstanding_loan_balance, 2) }}
            </div>
            <p class="text-[10px] text-zinc-555 mt-2 font-semibold">Total active loans principal remaining</p>
        </div>

        <!-- My Savings Balance -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-3xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">My Savings Balance</span>
            <div class="text-2xl font-black text-zinc-950 dark:text-white leading-none tracking-tight">
                ${{ number_format($member->savings_balance, 2) }}
            </div>
            <p class="text-[10px] mt-2 font-bold uppercase">
                @if($member->savings_balance >= $minSavings)
                    <span class="text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Eligible for Loans
                    </span>
                @else
                    <span class="text-amber-600 dark:text-amber-500 inline-flex items-center gap-1" title="Under ${{ number_format($minSavings, 0) }} Settings Threshold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Under Limit (Min: ${{ number_format($minSavings, 0) }})
                    </span>
                @endif
            </p>
        </div>
    </div>

    <!-- Active Grid: Guarantor Requests -->
    <div class="grid grid-cols-1 gap-6">
        <div class="w-full">
            <x-premium-card title="Incoming Guarantee Requests">
                <div class="flex flex-col gap-4 py-2">
                    @forelse($guarantorRequests as $req)
                        @php
                            $borrowerInitials = collect(explode(' ', $req->loanRequest->member->name))
                                ->map(fn($n) => mb_substr($n, 0, 1))
                                ->take(2)
                                ->join('');
                        @endphp
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between p-5 bg-zinc-50 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-zinc-800/60 rounded-2xl gap-5 hover:border-zinc-300 dark:hover:border-zinc-700 transition-all duration-200">
                            <div class="flex items-start gap-4 min-w-0">
                                <!-- Initials Avatar -->
                                <div class="w-10 h-10 rounded-xl bg-zinc-950 dark:bg-zinc-800 text-white dark:text-zinc-200 flex items-center justify-center font-bold text-xs shrink-0 border border-zinc-800/20 dark:border-zinc-700/30 shadow-3xs select-none">
                                    {{ $borrowerInitials }}
                                </div>
                                <div class="min-w-0 flex flex-col gap-1.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-zinc-900 dark:text-white text-sm leading-none">{{ $req->loanRequest->member->name }}</span>
                                        <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider bg-zinc-200/50 dark:bg-zinc-800/80 px-2 py-0.5 rounded-[6px]">Request #{{ $req->id }}</span>
                                        <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-550 font-mono leading-none">{{ $req->created_at->format('M d, Y') }}</span>
                                    </div>
                                    <p class="text-xs text-zinc-650 dark:text-zinc-400 leading-relaxed">
                                        Is requesting a loan of <strong class="text-zinc-900 dark:text-white font-extrabold text-[13px]">${{ number_format($req->loanRequest->amount, 2) }}</strong> and selected you as a guarantor.
                                    </p>
                                    @if($req->loanRequest->purpose)
                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 italic bg-white dark:bg-zinc-950 border border-zinc-200/40 dark:border-zinc-800/30 px-3 py-2 rounded-xl inline-block w-fit max-w-full truncate" title="{{ $req->loanRequest->purpose }}">
                                            "{{ $req->loanRequest->purpose }}"
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Quick Action Buttons -->
                            <div class="flex items-center gap-2.5 shrink-0 w-full lg:w-auto">
                                <button 
                                    type="button" 
                                    @click="openResponseModal('{{ route('member.loans.guarantee.approve', $req->id) }}', 'approve', {{ json_encode($req->load('loanRequest.member')) }})"
                                    class="w-full lg:w-auto px-5 py-2.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer text-center active:scale-[0.97]"
                                >
                                    Approve Request
                                </button>

                                <button 
                                    type="button" 
                                    @click="openResponseModal('{{ route('member.loans.guarantee.decline', $req->id) }}', 'decline', {{ json_encode($req->load('loanRequest.member')) }})"
                                    class="w-full lg:w-auto px-5 py-2.5 border border-red-200 dark:border-red-955/60 bg-red-50 hover:bg-red-100 dark:bg-red-950/10 text-red-655 dark:text-red-400 text-xs font-bold rounded-xl transition-all cursor-pointer text-center active:scale-[0.97]"
                                >
                                    Decline
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-zinc-400 dark:text-zinc-650 py-12 text-xs font-semibold">
                            No pending guarantee invitations.
                        </div>
                    @endforelse
                </div>
            </x-premium-card>
        </div>
    </div>


    <!-- VIEW GUARANTORS MODAL -->
    <div 
        x-show="showGuarantorsModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <!-- Overlay Backing -->
        <div @click="showGuarantorsModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Content Container -->
        <div 
            x-show="showGuarantorsModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl w-full max-w-md relative z-50 p-6 flex flex-col gap-4"
        >
            <div class="flex justify-between items-center pb-4 border-b border-zinc-100 dark:border-zinc-800/80 mb-1">
                <h3 class="text-sm font-black text-zinc-950 dark:text-white uppercase tracking-wider">Guarantor Status</h3>
                <button @click="showGuarantorsModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-250 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- List of guarantors -->
            <div class="flex flex-col gap-4 overflow-y-auto max-h-[60vh] pr-1">
                <template x-for="(g, index) in activeGuarantors" :key="index">
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-905 border border-zinc-200 dark:border-zinc-850 rounded-xl flex flex-col gap-2.5">
                        <div class="flex justify-between items-start">
                            <div class="flex flex-col min-w-0">
                                <span class="font-bold text-zinc-900 dark:text-zinc-100 text-sm truncate" x-text="g.name"></span>
                                <span class="font-mono text-[10px] text-zinc-400 dark:text-zinc-500 font-bold mt-0.5" x-text="g.code"></span>
                            </div>
                            
                            <!-- Status Badge -->
                            <template x-if="g.status === 'approved'">
                                <span class="inline-flex items-center text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">Approved</span>
                            </template>
                            <template x-if="g.status === 'declined'">
                                <span class="inline-flex items-center text-[10px] font-black uppercase tracking-wider text-red-600 dark:text-red-400 bg-red-500/10 px-2 py-0.5 rounded-full border border-red-500/20">Declined</span>
                            </template>
                            <template x-if="g.status === 'pending'">
                                <span class="inline-flex items-center text-[10px] font-black uppercase tracking-wider text-zinc-550 dark:text-zinc-400 bg-zinc-500/10 dark:bg-zinc-800/80 px-2 py-0.5 rounded-full border border-zinc-200/60 dark:border-zinc-700/60">Pending</span>
                            </template>
                        </div>

                        <!-- Response Date / Notes -->
                        <div class="text-[11px] text-zinc-550 dark:text-zinc-400 border-t border-zinc-200/30 dark:border-zinc-800/20 pt-2 flex flex-col gap-1">
                            <div class="flex justify-between" x-show="g.responded_at">
                                <span>Responded At:</span>
                                <span class="font-mono font-bold" x-text="g.responded_at"></span>
                            </div>
                            <div class="flex flex-col gap-1 mt-1.5" x-show="g.notes">
                                <span class="font-bold">Comments/Notes:</span>
                                <p class="text-xs bg-white dark:bg-zinc-900 p-2.5 rounded-lg border border-zinc-200/40 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 italic" x-text="g.notes"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="pt-2">
                <x-premium-button type="button" variant="secondary" class="w-full py-2.5" @click="showGuarantorsModal = false">
                    Close
                </x-premium-button>
            </div>
        </div>
    </div>

    <!-- GUARANTOR RESPONSE MODAL -->
    <div 
        x-show="showResponseModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <!-- Overlay Backing -->
        <div @click="showResponseModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Content Container -->
        <div 
            x-show="showResponseModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl w-full max-w-md relative z-50 p-6 flex flex-col gap-4"
        >
            <div class="flex justify-between items-center pb-4 border-b border-zinc-100 dark:border-zinc-800/80 mb-1">
                <h3 class="text-sm font-black text-zinc-950 dark:text-white uppercase tracking-wider" x-text="responseType === 'approve' ? 'Approve Loan Guarantee' : 'Decline Loan Guarantee'"></h3>
                <button @click="showResponseModal = false" class="text-zinc-400 hover:text-zinc-650 dark:hover:text-zinc-250 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form method="POST" :action="responseActionUrl" class="flex flex-col gap-4">
                @csrf
                
                <div class="text-xs leading-relaxed text-zinc-650 dark:text-zinc-400">
                    <template x-if="responseType === 'approve'">
                        <p>
                            Are you sure you want to act as a guarantor for <strong class="text-zinc-900 dark:text-white font-bold" x-text="getMemberName(responseRequest)"></strong>'s request for a loan of <strong class="text-zinc-900 dark:text-white font-extrabold text-sm" x-text="'$' + Number(responseRequest?.loan_request?.amount || responseRequest?.loanRequest?.amount).toLocaleString('en-US', {minimumFractionDigits: 2})"></strong>?
                        </p>
                    </template>
                    <template x-if="responseType === 'decline'">
                        <p class="text-red-655 dark:text-red-400 font-medium">
                            Are you sure you want to decline to act as a guarantor for <strong class="text-zinc-900 dark:text-white font-bold" x-text="getMemberName(responseRequest)"></strong>'s request for a loan of <strong class="text-zinc-900 dark:text-white font-extrabold text-sm" x-text="'$' + Number(responseRequest?.loan_request?.amount || responseRequest?.loanRequest?.amount).toLocaleString('en-US', {minimumFractionDigits: 2})"></strong>?
                        </p>
                    </template>
                </div>

                <div>
                    <label for="response_notes" class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 block">
                        Comments / Notes <span class="text-zinc-450 dark:text-zinc-500 font-medium">(Optional)</span>
                    </label>
                    <textarea 
                        id="response_notes"
                        name="notes"
                        x-model="responseNotes"
                        placeholder="Add optional comments or reasons here..."
                        class="w-full h-24 bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-455 dark:placeholder-zinc-600 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs p-3 focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-750 transition-all outline-none"
                    ></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button 
                        type="button" 
                        @click="showResponseModal = false" 
                        class="flex-1 py-2.5 border border-zinc-200/80 dark:border-zinc-800/85 text-zinc-700 dark:text-zinc-255 text-xs font-bold rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer text-center"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 py-2.5 text-xs font-bold rounded-xl transition-all cursor-pointer text-center shadow-xs active:scale-95 text-white dark:text-zinc-950"
                        :class="responseType === 'approve' ? 'bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100' : 'bg-red-600 hover:bg-red-700'"
                        x-text="responseType === 'approve' ? 'Confirm Approval' : 'Confirm Decline'"
                    >
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
