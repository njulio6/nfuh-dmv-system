<div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
    <div class="flex items-center justify-between pb-3.5 border-b border-zinc-100 dark:border-zinc-800">
        <div class="flex items-center gap-2">
            <div class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                <i data-lucide="banknote" class="w-4 h-4"></i>
            </div>
            <h3 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider">Active Loan Progress</h3>
        </div>
        <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50">
            {{ ucfirst($loan->status) }}
        </span>
    </div>

    <div class="flex flex-col gap-3 pt-3.5">
        <div class="flex justify-between items-end border-b border-zinc-100 dark:border-zinc-800/80 pb-2.5">
            <div class="flex flex-col">
                <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider">Remaining Balance</span>
                <span class="text-2xl font-black text-zinc-950 dark:text-white mt-1 leading-none tracking-tight font-display">
                    ${{ number_format($loan->remaining_balance, 2) }}
                </span>
            </div>
            @if($loan->interest_rate > 0)
                <div class="text-right">
                    <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider block">Total Repayable</span>
                    <span class="text-sm font-bold text-zinc-900 dark:text-white mt-0.5 block font-mono">
                        ${{ number_format($loan->total_repayable, 2) }}
                    </span>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-3 text-xs">
            <div>
                <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider block">Principal</span>
                <span class="font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 block font-mono">${{ number_format($loan->amount, 2) }}</span>
            </div>
            <div>
                <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider block">Interest Rate</span>
                <span class="font-semibold text-zinc-800 dark:text-zinc-200 mt-0.5 block">
                    @if($loan->interest_rate > 0)
                        {{ number_format($loan->interest_rate, 2) }}% <span class="text-[9px] text-zinc-400">({{ $loan->interest_type === 'flat' ? 'Flat' : 'Duration' }})</span>
                    @else
                        <span class="text-zinc-400">0.00%</span>
                    @endif
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 text-xs pt-2.5 border-t border-zinc-100 dark:border-zinc-800">
            <div>
                <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider block">Duration</span>
                <span class="font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 block">{{ $loan->duration_months }} Months</span>
            </div>
            <div>
                <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider block">Due Date</span>
                <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 block">
                    {{ $loan->repayment_due_date ? $loan->repayment_due_date->format('M d, Y') : '-' }}
                </span>
            </div>
        </div>

        @if($loan->guarantors && $loan->guarantors->isNotEmpty())
            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <span class="text-[10px] text-zinc-400 uppercase font-black tracking-wider block mb-1">Guarantors</span>
                <div class="flex flex-wrap gap-1">
                    @foreach($loan->guarantors as $g)
                        @if($g->guarantorMember)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                {{ $g->guarantorMember->name }}
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <div class="pt-2.5 border-t border-zinc-100 dark:border-zinc-800">
            <a href="{{ route('member.loans.statement', $loan->id) }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-primary hover:underline">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                <span>View & Print Statement</span>
            </a>
        </div>
    </div>
</div>
