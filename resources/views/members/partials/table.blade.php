<x-premium-table :headers="[
    ['label' => 'SI', 'width' => 'w-12', 'align' => 'center'],
    ['label' => 'Member ID'],
    ['label' => 'Name'],
    ['label' => 'State'],
    ['label' => 'Join Date'],
    ['label' => 'Email'],
    ['label' => 'Phone'],
    ['label' => 'Title'],
    ['label' => 'Roles'],
    ['label' => 'Status', 'width' => 'w-24', 'align' => 'center'],
    ['label' => 'Actions', 'width' => 'w-28', 'align' => 'center']
]" class="min-w-[700px]">
    @forelse ($members as $index => $member)
        @php
            $serialIndex = $index + 1 + ($members->currentPage() - 1) * $members->perPage();
            $isEven = $index % 2 === 1;
        @endphp
        <x-premium-table-row :is-even="$isEven">
                    <!-- SI serial index cell -->
                    <td class="py-2.5 px-3 text-center font-bold text-zinc-500 dark:text-zinc-400 tabular-nums">
                        {{ $serialIndex }}
                    </td>
                    
                    <!-- Member ID Cell -->
                    <td class="py-2.5 px-3">
                        <span class="font-mono text-[11px] text-zinc-600 dark:text-zinc-300 font-semibold select-text">
                            /{{ $member->member_code }}
                        </span>
                    </td>
                    
                    <!-- Name Cell -->
                    <td class="py-2.5 px-3 text-zinc-900 dark:text-white font-bold text-sm select-text">
                        {{ $member->first_name }} {{ $member->last_name }}
                    </td>

                    <!-- State Cell -->
                    <td class="py-2.5 px-3 text-zinc-800 dark:text-zinc-250 font-semibold">
                        {{ $member->state_code ?? '-' }}
                    </td>

                    <!-- Join Date Cell -->
                    <td class="py-2.5 px-3 text-zinc-800 dark:text-zinc-250 font-semibold">
                        {{ $member->join_date ? $member->join_date->format('Y-m-d') : '-' }}
                    </td>

                    <!-- Email Cell -->
                    <td class="py-2.5 px-3 text-zinc-800 dark:text-zinc-250 font-semibold select-text">
                        {{ $member->email ?: '-' }}
                    </td>

                    <!-- Phone Cell -->
                    <td class="py-2.5 px-3 text-zinc-800 dark:text-zinc-250 font-semibold select-text">
                        {{ $member->phone }}
                    </td>

                    <!-- Title (Rank) Cell -->
                    <td class="py-2.5 px-3">
                        <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-950/30 text-blue-750 dark:text-blue-300 rounded-lg text-xs font-bold uppercase tracking-wide">
                            {{ $member->rank->name ?? 'Warrior' }}
                        </span>
                    </td>

                    <!-- Roles Cell -->
                    <td class="py-2.5 px-3">
                        <div class="flex flex-wrap gap-1">
                            @forelse ($member->roles as $role)
                                <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-zinc-400 dark:text-zinc-550">-</span>
                            @endforelse
                        </div>
                    </td>
                    
                    <!-- Status cell -->
                    <td class="py-2.5 px-3">
                        <div class="flex justify-center">
                            @if ($member->status === 'active')
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-750 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                    Active
                                </span>
                            @elseif ($member->status === 'suspended')
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-200/60 dark:border-red-800/40">
                                    Suspended
                                </span>
                            @else
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </td>
                    
                    <!-- Actions cell -->
                    <td class="py-2.5 px-3">
                        <div class="flex items-center justify-center gap-1.5">
                            <a 
                                href="{{ route('members.show', $member) }}" 
                                class="p-1.5 rounded-[10px] text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all hover:scale-105 active:scale-95 flex items-center justify-center"
                                title="View Member Profile"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </a>
                            <a 
                                href="{{ route('members.edit', $member) }}" 
                                class="p-1.5 rounded-[10px] text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all hover:scale-105 active:scale-95 flex items-center justify-center"
                                title="Edit Member Profile"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4z"></path></svg>
                            </a>
                            <button 
                                type="button"
                                @click="deleteMemberId = {{ $member->id }}; deleteMemberName = '{{ addslashes($member->first_name . ' ' . $member->last_name) }}'; showDeleteModal = true"
                                class="p-1.5 rounded-[10px] text-red-500 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-955/20 cursor-pointer transition-all hover:scale-105 active:scale-95 flex items-center justify-center"
                                title="Delete Member Profile"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </div>
                    </td>
        </x-premium-table-row>
    @empty
        <tr>
            <td colspan="11" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                <div class="flex flex-col items-center justify-center gap-2.5 select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-zinc-300 dark:text-zinc-700"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span class="text-xs font-semibold text-zinc-500">No registered members found matching filters.</span>
                </div>
            </td>
        </tr>
    @endforelse
</x-premium-table>