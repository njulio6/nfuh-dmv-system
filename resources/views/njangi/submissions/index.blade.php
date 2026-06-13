@extends('layouts.app')

@section('content')

<x-premium-header 
    title="Njangi Payment Submissions" 
    subtitle="{{ isset($activeCycle) ? 'Auditing cycle: ' . $activeCycle->name : 'Auditing active cycle submissions' }}"
    back-url="{{ isset($activeCycle) ? route('njangi-cycles.show', $activeCycle) : route('dashboard') }}"
    back-title="{{ isset($activeCycle) ? 'Back to Cycle' : 'Back to Dashboard' }}"
/>

<div class="-mt-3" x-data="{ receiptModalOpen: false, receiptUrl: '' }">
    <x-premium-card>
        <x-premium-table :headers="[
            'ID',
            'Member',
            'Cycle',
            'Session',
            'Amount',
            'Receipt',
            'Status',
            'Submitted At',
            ['label' => 'Action', 'width' => 'w-32', 'align' => 'center']
        ]">
            @forelse ($submissions as $index => $submission)
                <x-premium-table-row :is-even="$index % 2 === 1">
                    <td class="py-2.5 px-3 font-semibold text-zinc-500">{{ $submission->id }}</td>
                    <td class="py-2.5 px-3 font-bold text-zinc-900 dark:text-white">
                        {{ optional($submission->member)->first_name }}
                        {{ optional($submission->member)->last_name }}
                    </td>
                    <td class="py-2.5 px-3 text-zinc-700 dark:text-zinc-300">{{ optional($submission->cycle)->name ?? $submission->njangi_cycle_id }}</td>
                    <td class="py-2.5 px-3 text-zinc-700 dark:text-zinc-300">Session #{{ optional($submission->session)->session_number ?? $submission->njangi_session_id }}</td>
                    <td class="py-2.5 px-3 font-bold text-zinc-800 dark:text-zinc-200">
                        ${{ number_format($submission->amount, 2) }}
                    </td>
                    <td class="py-2.5 px-3">
                        @if ($submission->screenshot_path)
                            <button 
                                @click="receiptUrl = '{{ asset('storage/' . $submission->screenshot_path) }}'; receiptModalOpen = true"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-900 hover:text-zinc-600 dark:text-white dark:hover:text-zinc-300 underline cursor-pointer select-none focus:outline-none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>View Receipt</span>
                            </button>
                        @else
                            <span class="text-zinc-400 dark:text-zinc-650 text-xs">No file</span>
                        @endif
                    </td>
                    <td class="py-2.5 px-3">
                        <div class="flex">
                            @if ($submission->status === 'approved')
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-750 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                    Approved
                                </span>
                            @elseif ($submission->status === 'pending')
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                    Pending
                                </span>
                            @else
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                                    {{ $submission->status }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="py-2.5 px-3 text-zinc-500 dark:text-zinc-450">{{ $submission->submitted_at }}</td>
                    <td class="py-2.5 px-3 text-center">
                        @if ($submission->status === 'pending')
                            <div class="flex gap-2 justify-center">
                                <form method="POST" action="{{ route('njangi-submissions.approve', $submission) }}">
                                    @csrf
                                    <x-premium-button type="submit" variant="primary" class="text-[10px] py-1 px-2.5">
                                        Approve
                                    </x-premium-button>
                                </form>

                                <form method="POST" action="{{ route('njangi-submissions.reject', $submission) }}">
                                    @csrf
                                    <x-premium-button type="submit" variant="danger" class="text-[10px] py-1 px-2.5">
                                        Reject
                                    </x-premium-button>
                                </form>
                            </div>
                        @else
                            <span class="text-zinc-400 dark:text-zinc-600 text-xs font-semibold select-none">Audited</span>
                        @endif
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                        No payment submissions found.
                    </td>
                </tr>
            @endforelse
        </x-premium-table>

        @if ($submissions->hasPages())
            <div class="mt-4">
                {{ $submissions->links() }}
            </div>
        @endif
    </x-premium-card>

    <!-- Receipt Modal -->
    <div 
        x-show="receiptModalOpen" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Backdrop -->
        <div 
            x-show="receiptModalOpen"
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="receiptModalOpen = false"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
        ></div>

        <!-- Modal Content -->
        <div 
            x-show="receiptModalOpen"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative max-w-2xl w-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl p-4 flex flex-col gap-4 max-h-[90vh]"
        >
            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2">
                <h3 class="font-bold text-zinc-900 dark:text-white text-sm">Receipt Image Preview</h3>
                <button 
                    @click="receiptModalOpen = false"
                    class="p-1 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-500 dark:text-zinc-400 cursor-pointer flex items-center justify-center"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="flex-grow overflow-auto flex items-center justify-center bg-zinc-50 dark:bg-zinc-950 rounded-xl border border-zinc-100 dark:border-zinc-800 p-2 min-h-[300px]">
                <img :src="receiptUrl" alt="Receipt Upload" class="max-w-full max-h-[60vh] object-contain rounded-lg shadow-sm">
            </div>
        </div>
    </div>
</div>

@endsection