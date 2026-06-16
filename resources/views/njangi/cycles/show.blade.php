@extends('layouts.app')

@section('content')
<x-premium-header 
    title="Njangi Cycle" 
    subtitle="Details for {{ $njangiCycle->name }}" 
    back-url="{{ route('njangi-cycles.index') }}" 
/>

<div class="-mt-3">
    <x-premium-card>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-150 dark:border-zinc-800 pb-4 mb-5">
            <div>
                <h2 class="text-lg font-display font-black text-zinc-800 dark:text-white leading-tight mb-1">
                    {{ $njangiCycle->name }}
                </h2>
                <p class="text-xs text-zinc-550 dark:text-zinc-400">
                    Rotational Cycle Year: <strong class="font-mono text-zinc-700 dark:text-zinc-300">{{ $njangiCycle->year }}</strong>
                </p>
            </div>
            
            <div class="flex items-center">
                @if ($njangiCycle->status === 'active')
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-750 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                        Active
                    </span>
                @elseif ($njangiCycle->status === 'draft')
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                        Draft
                    </span>
                @elseif ($njangiCycle->status === 'closed')
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-blue-50 text-blue-750 dark:bg-blue-950/30 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/40">
                        Closed
                    </span>
                @else
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                        {{ $njangiCycle->status }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Cycle Details Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 text-xs">
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-2">
                <span class="text-[9px] font-black uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Organization</span>
                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $njangiCycle->organization->name ?? 'N/A' }}</span>
            </div>

            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-2">
                <span class="text-[9px] font-black uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Start Date</span>
                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $njangiCycle->start_date?->format('Y-m-d') ?? 'N/A' }}</span>
            </div>

            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-2">
                <span class="text-[9px] font-black uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">End Date</span>
                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $njangiCycle->end_date?->format('Y-m-d') ?? 'N/A' }}</span>
            </div>

            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-2 md:col-span-3">
                <span class="text-[9px] font-black uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-1">Cycle Notes</span>
                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $njangiCycle->notes ?: 'N/A' }}</span>
            </div>
        </div>

        <!-- Quick Operations Menu -->
        <div class="mt-8 flex flex-wrap gap-2.5 border-t border-zinc-100 dark:border-zinc-800 pt-5">
            <x-premium-button variant="primary" href="{{ route('njangi-submissions.index', ['cycle_id' => $njangiCycle->id]) }}">
                Payment Submissions
            </x-premium-button>
            <x-premium-button variant="secondary" href="{{ route('njangi-contributions.index', ['cycle_id' => $njangiCycle->id]) }}">
                Contributions & Ledger
            </x-premium-button>

            @if ($njangiCycle->sessions->isEmpty())
                <form action="{{ route('njangi-cycles.add-members', $njangiCycle) }}" method="POST" class="inline">
                    @csrf
                    <x-premium-button type="submit" variant="primary">Add Members</x-premium-button>
                </form>

                <form action="{{ route('njangi-cycles.assign-benefit-order', $njangiCycle) }}" method="POST" class="inline">
                    @csrf
                    <x-premium-button type="submit" variant="secondary">Assign Benefit Order</x-premium-button>
                </form>

                <x-premium-button variant="secondary" href="{{ route('njangi-cycles.edit', $njangiCycle) }}">Edit Cycle</x-premium-button>

                <form action="{{ route('njangi-cycles.destroy', $njangiCycle) }}" method="POST" onsubmit="return confirm('Delete this cycle?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <x-premium-button type="submit" variant="secondary" class="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/20 border-red-105">Delete Cycle</x-premium-button>
                </form>
            @endif

            <form action="{{ route('njangi-cycles.generate-sessions', $njangiCycle) }}" method="POST" class="inline">
                @csrf
                <x-premium-button type="submit" variant="primary">Generate Sessions</x-premium-button>
            </form>
        </div>
    </x-premium-card>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <!-- Cycle Members list -->
    <x-premium-card title="Cycle Members">
        <x-premium-table :headers="['Member', 'Member ID', 'Benefit Order']" min-width="min-w-0">
            @forelse ($njangiCycle->cycleMembers as $index => $cycleMember)
                <x-premium-table-row :is-even="$index % 2 === 1">
                    <td class="py-2.5 px-3 font-semibold text-zinc-900 dark:text-white">
                        {{ $cycleMember->member->first_name }} {{ $cycleMember->member->last_name }}
                    </td>
                    <td class="py-2.5 px-3 font-mono text-zinc-550 dark:text-zinc-400 select-text">
                        /{{ $cycleMember->member->member_code }}
                    </td>
                    <td class="py-2.5 px-3">
                        <span class="inline-block px-2.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded-md font-bold text-zinc-800 dark:text-zinc-200">
                            {{ $cycleMember->benefit_order ?? '-' }}
                        </span>
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                        No members added to this cycle yet.
                    </td>
                </tr>
            @endforelse
        </x-premium-table>
    </x-premium-card>

    <!-- Sessions list -->
    <x-premium-card title="Sessions">
        <x-premium-table :headers="['#', 'Date', 'Title', 'Status', ['label' => 'Action', 'align' => 'center']]" min-width="min-w-[600px]">
            @forelse ($njangiCycle->sessions as $index => $session)
                <x-premium-table-row :is-even="$index % 2 === 1">
                    <td class="py-2.5 px-3 font-semibold text-zinc-700 dark:text-zinc-300">{{ $session->session_number }}</td>
                    <td class="py-2.5 px-3">{{ $session->session_date->format('Y-m-d') }}</td>
                    <td class="py-2.5 px-3 font-semibold text-zinc-800 dark:text-zinc-200">{{ $session->title ?: '-' }}</td>
                    <td class="py-2.5 px-3">
                        @if ($session->status === 'active' || $session->status === 'open')
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-750 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                {{ $session->status }}
                            </span>
                        @else
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                {{ $session->status }}
                            </span>
                        @endif
                    </td>
                    <td class="py-2.5 px-3 text-center">
                        <a 
                            href="{{ route('njangi-sessions.beneficiaries.edit', $session) }}" 
                            class="inline-flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-white font-bold transition-colors"
                        >
                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                            <span>Beneficiaries</span>
                        </a>
                    </td>
                </x-premium-table-row>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                        No sessions generated yet.
                    </td>
                </tr>
            @endforelse
        </x-premium-table>
    </x-premium-card>
</div>
@endsection