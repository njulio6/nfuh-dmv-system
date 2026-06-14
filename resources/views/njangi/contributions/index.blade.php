@extends('layouts.app')

@section('content')

<x-premium-header 
    title="Njangi Ledger & Contributions" 
    subtitle="{{ isset($activeCycle) ? 'Viewing ledger for: ' . $activeCycle->name : 'Viewing active cycle ledger & contributions' }}"
    back-url="{{ isset($activeCycle) ? route('njangi-cycles.show', $activeCycle) : route('dashboard') }}"
    back-title="{{ isset($activeCycle) ? 'Back to Cycle' : 'Back to Dashboard' }}"
/>

<!-- Ledger Summary Cards (Monochrome) -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 -mt-3 mb-6">
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 hover:shadow-md transition-all duration-200">
        <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Total Ledger Records</span>
        <div class="text-xl md:text-2xl font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight">
            {{ $totalContributions }}
        </div>
        <p class="text-[9px] text-zinc-400 dark:text-zinc-500 mt-2 font-semibold">Audited rotational deposits</p>
    </div>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-4 hover:shadow-md transition-all duration-200">
        <span class="text-[9px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Total Contributions Volume</span>
        <div class="text-xl md:text-2xl font-display font-black text-zinc-800 dark:text-white leading-none tracking-tight">
            ${{ number_format($totalAmount, 2) }}
        </div>
        <p class="text-[9px] text-zinc-400 dark:text-zinc-500 mt-2 font-semibold">Net cleared volume today</p>
    </div>
</div>

<div class="grid grid-cols-1 gap-6">
    <!-- Refund Summary Table -->
    <x-premium-card title="Refund Summary by Beneficiary">
        <x-premium-table :headers="['Beneficiary', 'Expected Refund', 'Received', 'Remaining', 'Status']">
            @forelse ($memberBalances as $index => $balance)
                <x-premium-table-row :is-even="$index % 2 === 1">
                    <td class="py-2.5 px-3 font-semibold text-zinc-900 dark:text-white">{{ $balance['beneficiary'] }}</td>
                    <td class="py-2.5 px-3 font-bold text-zinc-800 dark:text-zinc-200">${{ number_format($balance['expected'], 2) }}</td>
                    <td class="py-2.5 px-3 text-zinc-600 dark:text-zinc-400">${{ number_format($balance['received'], 2) }}</td>
                    <td class="py-2.5 px-3 font-bold text-zinc-900 dark:text-white">${{ number_format($balance['remaining'], 2) }}</td>
                    <td class="py-2.5 px-3">
                        <div class="flex">
                            @if ($balance['remaining'] <= 0)
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-750 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                    {{ $balance['status'] }}
                                </span>
                            @else
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                    {{ $balance['status'] }}
                                </span>
                            @endif
                        </div>
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-zinc-400 dark:text-zinc-600 py-16">No refund summary available.</td>
                </tr>
            @endforelse
        </x-premium-table>
    </x-premium-card>

    <!-- Detailed Contribution Records -->
    <x-premium-card title="Contribution Records">
        <x-premium-table :headers="['ID', 'Contributor', 'Beneficiary', 'Cycle', 'Session', 'Amount', 'Payment Ref', 'Date']">
            @forelse ($contributions as $index => $contribution)
                <x-premium-table-row :is-even="$index % 2 === 1">
                    <td class="py-2.5 px-3 font-semibold text-zinc-500">{{ $contribution->id }}</td>
                    <td class="py-2.5 px-3 font-bold text-zinc-900 dark:text-white">
                        {{ $contribution->contributor->first_name }} {{ $contribution->contributor->last_name }}
                    </td>
                    <td class="py-2.5 px-3 font-semibold text-zinc-800 dark:text-zinc-250">
                        {{ $contribution->beneficiary->first_name }} {{ $contribution->beneficiary->last_name }}
                    </td>
                    <td class="py-2.5 px-3 text-zinc-700 dark:text-zinc-300">{{ $contribution->cycle->name ?? 'N/A' }}</td>
                    <td class="py-2.5 px-3 text-zinc-700 dark:text-zinc-300">Session #{{ $contribution->session->session_number ?? $contribution->njangi_session_id }}</td>
                    <td class="py-2.5 px-3 font-bold text-zinc-800 dark:text-zinc-200">
                        ${{ number_format($contribution->amount, 2) }}
                    </td>
                    <td class="py-2.5 px-3">
                        <span class="font-mono text-xs px-2 py-0.5 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 rounded-md border border-zinc-200/10 dark:border-white/5">
                            #{{ $contribution->payment_submission_id }}
                        </span>
                    </td>
                    <td class="py-2.5 px-3 text-zinc-500 dark:text-zinc-450">{{ $contribution->created_at }}</td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-zinc-400 dark:text-zinc-600 py-16">No contributions found.</td>
                </tr>
            @endforelse
        </x-premium-table>

        @if ($contributions->hasPages())
            <div class="mt-4">
                {{ $contributions->links() }}
            </div>
        @endif
    </x-premium-card>
</div>

@endsection