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

            @if ($njangiCycle->sessions->isEmpty() || ($settings && $settings->allow_mid_cycle_enrollment))
                <form action="{{ route('njangi-cycles.add-members', $njangiCycle) }}" method="POST" class="inline">
                    @csrf
                    <x-premium-button type="submit" variant="primary">Add Members</x-premium-button>
                </form>
            @endif

            @if ($njangiCycle->sessions->isEmpty())
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
    <x-premium-card>
        <div class="flex items-center justify-between border-b border-zinc-150 dark:border-zinc-800 pb-3 -mt-2">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-zinc-900 dark:text-white">
                Cycle Members
            </h3>
            
            @php
                $canEnroll = $njangiCycle->sessions->isEmpty() || ($settings && $settings->allow_mid_cycle_enrollment);
            @endphp
            
            @if ($canEnroll)
                <div x-data="{ addMemberOpen: false }">
                    <x-premium-button variant="secondary" @click="addMemberOpen = true" class="flex items-center gap-1.5 py-1 px-3 text-xs">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                        <span>Add Participant</span>
                    </x-premium-button>

                    <!-- Add Participant Modal -->
                    <div x-show="addMemberOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/50 backdrop-blur-sm">
                        <div @click.away="addMemberOpen = false" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl max-w-md w-full p-6 shadow-2xl animate-swift-up-premium text-left max-h-[90vh] overflow-y-auto">
                            <div class="flex items-center justify-between border-b border-zinc-150 dark:border-zinc-800 pb-3 mb-4">
                                <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Add Individual Participant</h4>
                                <button type="button" @click="addMemberOpen = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                            
                            @if ($availableMembers->isEmpty())
                                <p class="text-xs text-zinc-550 dark:text-zinc-400 py-4 text-center">
                                    No available eligible organization members to add.
                                </p>
                            @else
                                <!-- Available Members JSON Data Store -->
                                <script id="available-members-data" type="application/json">
                                    {!! json_encode($availableMembers->map(fn($m) => ['id' => $m->id, 'name' => $m->first_name . ' ' . $m->last_name, 'code' => $m->member_code])) !!}
                                </script>

                                <form action="{{ route('njangi-cycles.members.store', $njangiCycle) }}" method="POST" class="flex flex-col gap-4">
                                    @csrf
                                    
                                    <!-- Member Selection (Searchable Dropdown) -->
                                    <div 
                                        x-data="{
                                            open: false,
                                            search: '',
                                            selectedId: '',
                                            selectedName: '',
                                            members: JSON.parse(document.getElementById('available-members-data').textContent),
                                            get filteredMembers() {
                                                if (this.search.trim() === '') return this.members;
                                                return this.members.filter(m => 
                                                    m.name.toLowerCase().includes(this.search.toLowerCase()) || 
                                                    m.code.toLowerCase().includes(this.search.toLowerCase())
                                                );
                                            },
                                            selectMember(id, name, code) {
                                                this.selectedId = id;
                                                this.selectedName = name + ' (' + code + ')';
                                                this.open = false;
                                                this.search = '';
                                            }
                                        }"
                                        class="flex flex-col w-full relative"
                                        @click.outside="open = false"
                                    >
                                        <label class="block text-[10px] font-black uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            Select Member <span class="text-red-500">*</span>
                                        </label>

                                        <!-- Hidden input to hold the value for form submission -->
                                        <input type="hidden" name="member_id" :value="selectedId" required />

                                        <div class="relative w-full">
                                            <!-- Trigger Button -->
                                            <button
                                                type="button"
                                                @click="open = !open"
                                                class="w-full text-left bg-zinc-50 dark:bg-zinc-950 text-zinc-805 dark:text-white px-4 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-all cursor-pointer flex justify-between items-center"
                                            >
                                                <span x-text="selectedName || '-- Choose a Member --'" :class="!selectedName && 'text-zinc-400 dark:text-zinc-500'"></span>
                                                <svg class="h-3.5 w-3.5 text-zinc-450 transition-transform" :class="open && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <!-- Dropdown Panel -->
                                            <div
                                                x-show="open"
                                                x-cloak
                                                class="absolute left-0 z-50 w-full mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-lg max-h-60 overflow-hidden flex flex-col"
                                            >
                                                <!-- Search Input -->
                                                <div class="p-2 border-b border-zinc-100 dark:border-zinc-800">
                                                    <div class="relative flex items-center">
                                                        <span class="absolute left-2.5 text-zinc-400 pointer-events-none">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                                        </span>
                                                        <input
                                                            type="text"
                                                            x-model="search"
                                                            placeholder="Type name or code to search..."
                                                            class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-450 !pl-8 pr-3 py-1 text-xs font-semibold border border-zinc-200 dark:border-zinc-800 rounded-lg focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700"
                                                            style="padding-left: 2rem;"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Options List -->
                                                <div class="overflow-y-auto max-h-48 flex-1">
                                                    <template x-for="m in filteredMembers" :key="m.id">
                                                        <button
                                                            type="button"
                                                            @click="selectMember(m.id, m.name, m.code)"
                                                            class="w-full text-left px-4 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs font-semibold text-zinc-750 dark:text-zinc-350 cursor-pointer flex justify-between items-center transition-colors border-none bg-transparent"
                                                        >
                                                            <span x-text="m.name"></span>
                                                            <span class="font-mono text-[10px] text-zinc-450 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-950 px-1.5 py-0.5 rounded" x-text="m.code"></span>
                                                        </button>
                                                    </template>
                                                    <div x-show="filteredMembers.length === 0" class="px-4 py-3 text-center text-xs text-zinc-400 dark:text-zinc-650 font-semibold">
                                                        No members found
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1.5">Benefit Draw Order (Optional)</label>
                                        <input type="number" name="benefit_order" min="1" placeholder="e.g. 1, 2, 3..." class="w-full text-xs bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded px-3 py-2 text-zinc-800 dark:text-zinc-200" />
                                        <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">Leave empty to assign later.</p>
                                    </div>
                                    <div class="flex justify-end gap-2 border-t border-zinc-150 dark:border-zinc-800 pt-4 mt-2">
                                        <x-premium-button type="button" variant="secondary" @click="addMemberOpen = false">Cancel</x-premium-button>
                                        <x-premium-button type="submit" variant="primary">Add Member</x-premium-button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <form action="{{ route('njangi-cycles.members.bulk-update', $njangiCycle) }}" method="POST">
            @csrf
            @method('PUT')
            
            <x-premium-table :headers="['Member', 'Member ID', 'Benefit Order', 'Status', ['label' => 'Actions', 'align' => 'center']]" min-width="min-w-0">
                @forelse ($njangiCycle->cycleMembers as $index => $cycleMember)
                    <x-premium-table-row :is-even="$index % 2 === 1">
                        <td class="py-2.5 px-3 font-semibold text-zinc-900 dark:text-white">
                            {{ $cycleMember->member->first_name }} {{ $cycleMember->member->last_name }}
                        </td>
                        <td class="py-2.5 px-3 font-mono text-zinc-550 dark:text-zinc-400 select-text">
                            /{{ $cycleMember->member->member_code }}
                        </td>
                        <td class="py-2.5 px-3">
                            <input 
                                type="number" 
                                name="members[{{ $cycleMember->id }}][benefit_order]" 
                                value="{{ $cycleMember->benefit_order }}" 
                                min="1"
                                class="w-16 h-7 px-1.5 py-0.5 border border-zinc-200 dark:border-zinc-800 rounded text-center font-bold font-mono text-xs bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-zinc-200"
                                placeholder="-"
                            />
                        </td>
                        <td class="py-2.5 px-3" x-data="{ status: '{{ $cycleMember->status }}', open: false }">
                            <input type="hidden" name="members[{{ $cycleMember->id }}][status]" :value="status" />
                            <div class="relative inline-block w-28" @click.outside="open = false">
                                <button 
                                    type="button" 
                                    @click="open = !open"
                                    class="h-8 px-3 py-1 flex items-center justify-between gap-1.5 border border-zinc-200 dark:border-zinc-800 rounded-lg font-bold text-xs bg-zinc-50 dark:bg-zinc-950/50 text-zinc-800 dark:text-zinc-200 hover:border-zinc-350 dark:hover:border-zinc-700 transition-all cursor-pointer w-28"
                                >
                                    <span x-text="status.charAt(0).toUpperCase() + status.slice(1)"></span>
                                    <svg class="h-3 w-3 text-zinc-455 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                
                                <div 
                                    x-show="open"
                                    x-cloak
                                    class="absolute z-[100] left-0 w-28 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-xl dark:shadow-[0_8px_30px_rgba(0,0,0,0.5)] overflow-hidden flex flex-col"
                                    :class="parseInt('{{ $index }}') >= parseInt('{{ count($njangiCycle->cycleMembers) - 2 }}') ? 'bottom-full mb-2 mt-auto' : 'top-full mt-1'"
                                >
                                    <div class="flex flex-col py-1">
                                        <button type="button" @click="status = 'active'; open = false" class="w-full text-left px-3 py-1.5 text-xs font-semibold text-zinc-750 dark:text-zinc-350 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-none bg-transparent cursor-pointer">Active</button>
                                        <button type="button" @click="status = 'inactive'; open = false" class="w-full text-left px-3 py-1.5 text-xs font-semibold text-zinc-750 dark:text-zinc-350 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-none bg-transparent cursor-pointer">Inactive</button>
                                        <button type="button" @click="status = 'withdrawn'; open = false" class="w-full text-left px-3 py-1.5 text-xs font-semibold text-zinc-750 dark:text-zinc-350 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-none bg-transparent cursor-pointer">Withdrawn</button>
                                        <button type="button" @click="status = 'suspended'; open = false" class="w-full text-left px-3 py-1.5 text-xs font-semibold text-zinc-750 dark:text-zinc-350 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-none bg-transparent cursor-pointer">Suspended</button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-2.5 px-3 text-center">
                            @php
                                $canDelete = $njangiCycle->sessions->isEmpty() || ($settings && $settings->allow_mid_cycle_removal);
                            @endphp
                            @if ($canDelete)
                                <button 
                                    type="button" 
                                    onclick="
                                        var form = document.getElementById('delete-member-form');
                                        form.action = '{{ route('njangi-cycles.members.destroy', [$njangiCycle, $cycleMember]) }}';
                                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-member-removal' }));
                                    "
                                    class="text-zinc-400 hover:text-red-650 dark:hover:text-red-400 p-1 rounded transition-colors" 
                                    title="Remove Member"
                                >
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-700 cursor-not-allowed" title="Removal disabled in active cycles (check System Settings)">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </span>
                            @endif
                        </td>
                    </x-premium-table-row>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                            No members added to this cycle yet.
                        </td>
                    </tr>
                @endforelse
            </x-premium-table>

            @if ($njangiCycle->cycleMembers->isNotEmpty())
                <div class="flex justify-end mt-4 pt-4 border-t border-zinc-150 dark:border-zinc-800">
                    <x-premium-button type="submit" variant="primary">Save Changes</x-premium-button>
                </div>
            @endif
        </form>
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

<form id="delete-member-form" action="" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<x-modal name="confirm-member-removal" focusable>
    <div class="p-6 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-2">
            Confirm Member Removal
        </h3>
        <p class="text-xs text-zinc-550 dark:text-zinc-400 mb-6">
            Are you sure you want to remove this member from the cycle?
        </p>
        
        <div class="flex justify-end gap-2 border-t border-zinc-150 dark:border-zinc-800 pt-4">
            <x-premium-button type="button" variant="secondary" x-on:click="$dispatch('close')">
                Cancel
            </x-premium-button>
            <x-premium-button type="button" variant="primary" class="!bg-red-600 hover:!bg-red-700 !text-white border-none" onclick="document.getElementById('delete-member-form').submit();">
                Remove Member
            </x-premium-button>
        </div>
    </div>
</x-modal>
@endsection