@extends('layouts.app')

@section('content')

<x-premium-header 
    title="Njangi Payment Submissions" 
    subtitle="{{ isset($activeCycle) ? 'Auditing cycle: ' . $activeCycle->name : 'Auditing active cycle submissions' }}"
    back-url="{{ isset($activeCycle) ? route('njangi-cycles.show', $activeCycle) : route('dashboard') }}"
    back-title="{{ isset($activeCycle) ? 'Back to Cycle' : 'Back to Dashboard' }}"
/>

<div class="-mt-3">
    <x-premium-card>
        <x-premium-table :headers="[
            'ID',
            'Member',
            'Cycle',
            'Session',
            'Amount',
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
                    <td colspan="8" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
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
</div>

@endsection