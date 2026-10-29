@extends('layouts.app')

@section('content')
<!-- Alpine context for Roles & Permissions dashboard -->
<div 
    x-data="{
        activeTab: (['users', 'matrix', 'governance'].includes(new URLSearchParams(window.location.search).get('tab')))
            ? new URLSearchParams(window.location.search).get('tab')
            : '{{ session('compliance_warning') ? 'users' : (in_array(request('tab'), ['users', 'matrix', 'governance']) ? request('tab') : 'users') }}',
        activeMatrixRole: new URLSearchParams(window.location.search).get('role') || '{{ request('role', $roles->first()?->name ?? 'admin') }}',
        showFilterModal: false,
        editModalOpen: false,
        selectedUserId: null,
        selectedUserName: '',
        selectedRoles: [],
        conflictWarning: '',
        
        // Incompatible role pairs for dual-control compliance
        conflictPairs: [
            { roles: ['Treasurer', 'Loan Committee'], message: 'Advisory Warning: Combining Treasurer (disburser) with Loan Committee (approver) bypasses dual-control over loan funds.' },
            { roles: ['Treasurer', 'Financial Secretary'], message: 'Advisory Warning: Combining Treasurer (cash custodian) with Financial Secretary (bookkeeper) reduces segregation of duties.' }
        ],

        roleCounts: {
            @foreach($roles as $r)
                '{{ $r->name }}': {{ $r->name === 'member' ? $r->permissions->whereIn('name', \App\Models\User::$defaultMemberPermissions)->count() : $r->permissions->count() }},
            @endforeach
        },

        openEditModal(userId, name, currentRoles) {
            this.selectedUserId = userId;
            this.selectedUserName = name;
            this.selectedRoles = Array.isArray(currentRoles) ? [...currentRoles] : [];
            this.checkConflicts();
            this.editModalOpen = true;
        },

        checkConflicts() {
            this.conflictWarning = '';
            for (const pair of this.conflictPairs) {
                const hasBoth = pair.roles.every(r => this.selectedRoles.includes(r));
                if (hasBoth) {
                    this.conflictWarning = pair.message;
                    break;
                }
            }
        },

        init() {
            this.$watch('activeTab', (val) => {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', val);
                window.history.replaceState({}, '', url);
            });
            this.$watch('activeMatrixRole', (val) => {
                const url = new URL(window.location.href);
                if (this.activeTab === 'matrix') {
                    url.searchParams.set('role', val);
                    window.history.replaceState({}, '', url);
                }
            });
            window.addEventListener('popstate', () => {
                const tab = new URLSearchParams(window.location.search).get('tab');
                if (['users', 'matrix', 'governance'].includes(tab)) {
                    this.activeTab = tab;
                }
                const role = new URLSearchParams(window.location.search).get('role');
                if (role && this.roleCounts[role] !== undefined) {
                    this.activeMatrixRole = role;
                }
            });
        }
    }"
    class="w-full"
>
    <!-- Main Form tracking filters, search, page & size -->
    <form id="roles-filter-form" method="GET" action="{{ route('admin.roles.index') }}" x-ref="form" class="hidden">
        <input type="hidden" name="tab" :value="activeTab">
        <input type="hidden" name="search" value="{{ request('search') }}" x-ref="searchInput">
        <input type="hidden" name="role_filter" value="{{ request('role_filter') }}" x-ref="roleInput">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}" x-ref="perPageInput">
        <input type="hidden" name="page" value="{{ $users->currentPage() }}" x-ref="pageInput">
    </form>

    <!-- Page Header & Tab Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div class="flex flex-col gap-0.5">
            <div class="flex items-center gap-2.5">
                <span class="p-2 bg-brand-subtle rounded-[10px] text-brand-primary shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                </span>
                <h1 class="text-xl font-bold text-zinc-950 dark:text-white tracking-tight font-display">
                    Roles & Segregation of Duties
                </h1>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Configure role-based access control (RBAC), fine-grained permissions, and financial governance checks.
            </p>
        </div>

        <!-- Navigation Tabs & Seed Sync Action -->
        <div class="flex flex-wrap items-center gap-2.5 self-start sm:self-auto">
            <div class="flex items-center gap-1 p-1 bg-zinc-100 dark:bg-zinc-800/60 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800 shadow-2xs">
                <button 
                    type="button"
                    @click="activeTab = 'users'" 
                    :class="activeTab === 'users' 
                        ? 'bg-brand-primary text-brand-primary-text shadow-xs font-bold' 
                        : 'text-zinc-700 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white hover:bg-white/40 dark:hover:bg-zinc-800/40 font-bold'"
                    class="px-4 py-2 rounded-[8px] text-[13px] font-bold transition-all cursor-pointer select-none"
                >
                    User Role Assignments
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'matrix'" 
                    :class="activeTab === 'matrix' 
                        ? 'bg-brand-primary text-brand-primary-text shadow-xs font-bold' 
                        : 'text-zinc-700 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white hover:bg-white/40 dark:hover:bg-zinc-800/40 font-bold'"
                    class="px-4 py-2 rounded-[8px] text-[13px] font-bold transition-all cursor-pointer select-none"
                >
                    Permissions Matrix
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'governance'" 
                    :class="activeTab === 'governance' 
                        ? 'bg-brand-primary text-brand-primary-text shadow-xs font-bold' 
                        : 'text-zinc-700 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white hover:bg-white/40 dark:hover:bg-zinc-800/40 font-bold'"
                    class="px-4 py-2 rounded-[8px] text-[13px] font-bold transition-all cursor-pointer select-none"
                >
                    Governance Rules
                </button>
            </div>

            <!-- Safe Seed / Sync Defaults Button -->
            <form method="POST" action="{{ route('admin.roles.sync-defaults') }}">
                @csrf
                <button 
                    type="submit"
                    class="px-3.5 py-2 rounded-[10px] text-xs font-bold border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-750 text-zinc-800 dark:text-zinc-200 flex items-center gap-2 shadow-2xs transition-all cursor-pointer active:scale-95 select-none"
                    title="Safely seed missing default roles & permissions without affecting custom changes"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-brand-primary"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
                    <span>Seed / Sync Roles</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Advisory Compliance Warning Banner (Override Confirmation) -->
    @if(session('compliance_warning'))
        <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-300 dark:border-amber-700 rounded-[10px] p-4 mb-6 flex flex-col gap-3 shadow-xs">
            <div class="flex items-start gap-3">
                <span class="p-1.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-lg shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <div class="flex flex-col gap-0.5">
                    <h3 class="text-xs font-bold text-amber-900 dark:text-amber-200">
                        Segregation of Duties Advisory: {{ session('target_user_name') }}
                    </h3>
                    <p class="text-xs text-amber-800 dark:text-amber-300 leading-relaxed">
                        {{ session('warning_message') }}
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.users.assign-roles', session('target_user_id')) }}" class="flex items-center justify-end gap-2 pt-2 border-t border-amber-200 dark:border-amber-900">
                @csrf
                @method('PUT')
                @foreach(session('proposed_roles', []) as $rName)
                    <input type="hidden" name="roles[]" value="{{ $rName }}">
                @endforeach
                <input type="hidden" name="confirm_override" value="1">

                <a href="{{ route('admin.roles.index', ['tab' => 'users']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-[8px] border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-3.5 py-1.5 text-xs font-bold rounded-[8px] bg-amber-600 hover:bg-amber-700 text-white transition-colors shadow-xs">
                    Confirm & Proceed with Warning
                </button>
            </form>
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 1: User Role Assignments (Standard 3-Sibling Block Layout)          -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div x-show="activeTab === 'users'" x-cloak>
        
        <!-- Sibling Block 1: Top Control Bar -->
        <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs mb-6">
            
            <!-- Left: Search Box -->
            <div class="flex items-center gap-3 flex-1 min-w-[240px] w-full sm:max-w-xs md:max-w-sm">
                <div class="relative w-full">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input
                        type="text"
                        placeholder="Search users or member codes..."
                        value="{{ request('search') }}"
                        @keydown.enter.prevent="$refs.searchInput.value = $el.value; $refs.pageInput.value = 1; $refs.form.submit()"
                        @blur="$refs.searchInput.value = $el.value"
                        class="w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 !pl-9 pr-4 py-2 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-colors"
                        style="padding-left: 2.25rem;"
                    />
                </div>
            </div>

            <!-- Right: Action Buttons Group -->
            <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto xl:justify-end">
                <!-- Reload / Clear Button -->
                <a 
                    href="{{ route('admin.roles.index', ['tab' => 'users']) }}" 
                    class="p-2.5 border border-zinc-200 dark:border-zinc-800 rounded-[10px] text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer transition-all active:scale-[0.96] select-none"
                    title="Reload & Clear Filters"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                </a>

                <!-- Filter Popover Button -->
                <button 
                    type="button"
                    @click="showFilterModal = true"
                    class="p-2.5 border rounded-[10px] cursor-pointer transition-all active:scale-[0.96] relative select-none {{ request('role_filter') ? 'bg-purple-500/10 text-purple-600 border-purple-500/20 dark:text-purple-400' : 'text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-950/40 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                    title="Filter by Role"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                    @if(request('role_filter'))
                        <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                    @endif
                </button>
            </div>
        </div>

        <!-- Sibling Block 2: Data Table Card -->
        <div class="bg-white dark:bg-zinc-900 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-2xs overflow-hidden relative mb-6">
            <x-premium-table :headers="[
                'User / Member',
                'Assigned Roles',
                'Status',
                ['label' => 'Actions', 'align' => 'right']
            ]">
                @forelse($users as $u)
                    @php
                        $roleNames = $u->roles->pluck('name')->toArray();
                        $hasConflict = false;
                        foreach($conflictRules as $cr) {
                            if (count(array_intersect($cr['roles'], $roleNames)) === count($cr['roles'])) {
                                $hasConflict = true;
                                break;
                            }
                        }
                    @endphp
                    <x-premium-table-row :is-even="$loop->index % 2 === 1">
                        <!-- User / Member -->
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-bold text-xs text-zinc-700 dark:text-zinc-300 shrink-0">
                                    {{ substr($u->name, 0, 1) }}
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <div class="font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                        <span>{{ $u->name }}</span>
                                        @if($u->member && $u->member->rank)
                                            <span class="text-[9px] font-black uppercase px-1.5 py-0.5 bg-amber-500/10 text-amber-700 dark:text-amber-400 rounded border border-amber-500/20">
                                                {{ $u->member->rank->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[11px] text-zinc-400">
                                        <span>{{ $u->email }}</span>
                                        @if($u->member)
                                            <span>&bull;</span>
                                            <span class="font-mono text-zinc-500">{{ $u->member->member_code }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Assigned Roles -->
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @forelse($u->roles as $r)
                                    <span class="inline-block px-2.5 py-0.5 rounded-full font-bold uppercase text-[10px] tracking-wider
                                        {{ $r->name === 'admin' ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400 border border-purple-200/60 dark:border-purple-800/40' : '' }}
                                        {{ $r->name === 'Treasurer' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40' : '' }}
                                        {{ $r->name === 'Financial Secretary' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/40' : '' }}
                                        {{ $r->name === 'Loan Committee' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/40' : '' }}
                                        {{ $r->name === 'General Secretary' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/40' : '' }}
                                        {{ $r->name === 'member' ? 'bg-zinc-100 text-zinc-650 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200/60 dark:border-zinc-700/60' : '' }}"
                                    >
                                        {{ ucfirst($r->name) }}
                                    </span>
                                @empty
                                    <span class="text-zinc-400 italic text-[11px]">No roles assigned</span>
                                @endforelse

                                @if($hasConflict)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/40" title="Contains overlapping segregation of duties positions">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        Dual Role Warning
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="py-3 px-3">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Active
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-3 text-right">
                            <button 
                                type="button"
                                @click="openEditModal('{{ $u->id }}', '{{ addslashes($u->name) }}', {{ json_encode($roleNames) }})"
                                class="px-3 py-1.5 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 rounded-[10px] text-xs font-bold transition-all cursor-pointer active:scale-95 shadow-2xs"
                            >
                                Edit Roles
                            </button>
                        </td>
                    </x-premium-table-row>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                            No users found matching the specified parameters.
                        </td>
                    </tr>
                @endforelse
            </x-premium-table>
        </div>

        <!-- Sibling Block 3: Pagination Footer Card -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-zinc-900/40 p-4 md:p-5 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs mb-6">
            <!-- Left: Rows per Page buttons -->
            <div class="flex items-center gap-3">
                <span class="text-[11px] font-black uppercase text-zinc-500 dark:text-zinc-400">
                    Rows Per Page
                </span>
                <div class="flex items-center gap-1.5">
                    @foreach([5, 10, 20, 30, 50] as $size)
                        @php
                            $isActive = request('per_page', 10) == $size;
                        @endphp
                        <button
                            type="button"
                            @click="$refs.perPageInput.value = {{ $size }}; $refs.pageInput.value = 1; $refs.form.submit()"
                            class="w-7 h-7 flex items-center justify-center text-xs font-bold rounded-[10px] border transition-all cursor-pointer
                                {{ $isActive 
                                    ? 'bg-zinc-950 border-zinc-950 text-white dark:bg-zinc-50 dark:border-zinc-50 dark:text-zinc-950 shadow-xs' 
                                    : 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400' 
                                }}"
                        >
                            {{ $size }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Right: Pagination links -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <!-- Go to first page button -->
                <button
                    type="button"
                    @click="$refs.pageInput.value = 1; $refs.form.submit()"
                    @if($users->onFirstPage()) disabled @endif
                    class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                >
                    First
                </button>

                <!-- Previous button -->
                <button
                    type="button"
                    @if(!$users->onFirstPage())
                        @click="$refs.pageInput.value = {{ $users->currentPage() - 1 }}; $refs.form.submit()"
                    @endif
                    @if($users->onFirstPage()) disabled @endif
                    class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                    title="Previous Page"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>

                <!-- Page numbers window loop -->
                @php
                    $startPage = max(1, $users->currentPage() - 2);
                    $endPage = min($users->lastPage(), $users->currentPage() + 2);
                @endphp
                @for ($page = $startPage; $page <= $endPage; $page++)
                    <button
                        type="button"
                        @click="$refs.pageInput.value = {{ $page }}; $refs.form.submit()"
                        class="w-7 h-7 flex items-center justify-center text-xs font-bold rounded-[10px] border transition-all cursor-pointer select-none
                            {{ $page == $users->currentPage()
                                ? 'bg-zinc-950 border-zinc-950 text-white dark:bg-zinc-50 dark:border-zinc-50 dark:text-zinc-950 shadow-xs' 
                                : 'bg-white dark:bg-zinc-950 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400' 
                            }}"
                    >
                        {{ $page }}
                    </button>
                @endfor

                <!-- Next button -->
                <button
                    type="button"
                    @if($users->hasMorePages())
                        @click="$refs.pageInput.value = {{ $users->currentPage() + 1 }}; $refs.form.submit()"
                    @endif
                    @if(!$users->hasMorePages()) disabled @endif
                    class="p-1.5 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 rounded-[10px] flex items-center justify-center transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                    title="Next Page"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>

                <!-- Last page button -->
                <button
                    type="button"
                    @click="$refs.pageInput.value = {{ $users->lastPage() }}; $refs.form.submit()"
                    @if($users->currentPage() == $users->lastPage()) disabled @endif
                    class="px-2 py-1.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[11px] font-bold rounded-[10px] shadow-xs transition-all disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed active:scale-[0.97]"
                >
                    Last
                </button>
            </div>
        </div>

        <!-- Filter Popover Modal -->
        <div 
            x-show="showFilterModal" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <!-- Modal Backdrop -->
            <div
                @click="showFilterModal = false"
                class="absolute inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-xs transition-opacity duration-300"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>
            
            <!-- Modal Container -->
            <div 
                class="relative w-full max-w-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl p-6 text-left z-10 transition-transform duration-300"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            >
                <h3 class="text-base font-bold text-zinc-900 dark:text-white mb-4">
                    Filter by Role
                </h3>

                <div class="space-y-4" x-data="{ roleVal: '{{ request('role_filter') }}' }">
                    <div>
                        <label class="block text-[11px] font-black uppercase text-zinc-500 dark:text-zinc-400 mb-1.5">
                            Assigned Role
                        </label>
                        <div class="relative flex items-center">
                            <select 
                                x-model="roleVal"
                                class="appearance-none w-full bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-white pl-3 pr-8 py-2 rounded-[10px] border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 cursor-pointer"
                            >
                                <option value="">All Roles</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->name }}">{{ ucfirst($r->name) }}</option>
                                @endforeach
                            </select>
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 dark:text-zinc-500 absolute right-3 pointer-events-none"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>

                    <div class="flex gap-2.5 mt-6">
                        <button
                            type="button"
                            @click="roleVal = ''; $refs.roleInput.value = ''; $refs.pageInput.value = 1; $refs.form.submit()"
                            class="flex-1 py-2 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-bold rounded-[10px] hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer select-none"
                        >
                            Reset
                        </button>
                        <button
                            type="button"
                            @click="$refs.roleInput.value = roleVal; $refs.pageInput.value = 1; $refs.form.submit()"
                            class="flex-1 py-2 bg-zinc-950 dark:bg-zinc-50 hover:bg-zinc-900 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-bold rounded-[10px] transition-all cursor-pointer shadow-xs active:scale-95 select-none"
                        >
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 2: Permissions Matrix (Simple, Clean, Easy to Use)                 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div x-show="activeTab === 'matrix'" x-cloak>
        @if($allPermissions->count() === 0)
            <!-- Prominent Unseeded State Banner -->
            <div class="bg-white dark:bg-zinc-900 border border-amber-300/80 dark:border-amber-700/80 rounded-2xl p-6 sm:p-10 text-center flex flex-col items-center justify-center gap-4 mb-6 shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div class="max-w-lg">
                    <h3 class="text-base font-bold text-zinc-950 dark:text-white font-display">System Permissions Not Initialized Yet</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1.5 leading-relaxed">
                        This database currently has 0 registered permissions. Click the button below to safely seed the standard permission matrix and initialize all 6 executive roles without affecting existing users.
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.roles.sync-defaults') }}">
                    @csrf
                    <button 
                        type="submit" 
                        class="px-5 py-2.5 bg-brand-primary text-brand-primary-text rounded-xl text-xs font-bold shadow-sm hover:opacity-95 transition-all flex items-center gap-2 cursor-pointer active:scale-95"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
                        <span>Seed Default Roles & Permissions Now</span>
                    </button>
                </form>
            </div>
        @endif

        <!-- Role Selector Top Bar -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3 bg-white dark:bg-zinc-900/40 p-3.5 md:p-4 rounded-[10px] border border-zinc-200/60 dark:border-zinc-800/60 shadow-xs mb-6">
            <div class="flex items-center gap-2.5 shrink-0">
                <span class="p-1.5 bg-brand-subtle text-brand-primary rounded-[8px]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                </span>
                <span class="text-[13.5px] font-bold text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                    Configure Role:
                </span>
            </div>

            <!-- Segmented Role Switcher Pills (Direct 1-Click Access) -->
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach($roles as $r)
                    <button 
                        type="button" 
                        @click="activeMatrixRole = '{{ $r->name }}'" 
                        :class="activeMatrixRole === '{{ $r->name }}' 
                            ? 'bg-brand-primary text-brand-primary-text font-bold shadow-xs' 
                            : 'bg-zinc-100 hover:bg-zinc-200/70 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-bold'"
                        class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-[8px] text-[13px] font-bold transition-all cursor-pointer select-none"
                    >
                        <span class="font-bold">{{ ucfirst($r->name) }}</span>
                        <span 
                            :class="activeMatrixRole === '{{ $r->name }}' ? 'bg-white/20 text-current' : 'bg-zinc-200/80 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'"
                            class="px-1.5 py-0.2 rounded-full text-[11px] font-mono font-bold"
                        >
                            {{ $r->name === 'member' ? $r->permissions->whereIn('name', \App\Models\User::$defaultMemberPermissions)->count() : $r->permissions->count() }}
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="text-[13px] text-zinc-500 dark:text-zinc-400 font-medium shrink-0 hidden sm:block">
                <span class="font-bold text-zinc-900 dark:text-white" x-text="roleCounts[activeMatrixRole] || 0"></span> of 
                <span x-text="activeMatrixRole === 'member' ? '{{ count(\App\Models\User::$defaultMemberPermissions) }}' : '{{ $allPermissions->count() }}'"></span> active
            </div>
        </div>

        <!-- Permissions Form Per Role -->
        @foreach($roles as $role)
            @php
                $rolePermissions = $role->permissions->pluck('name')->toArray();
                $isMemberRole = ($role->name === 'member');

                // For 'member' role, hide unrealistic administrative & officer permissions.
                // Only realistic self-service permissions are shown.
                $displayModules = $permissionsByModule->map(function ($perms) use ($isMemberRole) {
                    if ($isMemberRole) {
                        return $perms->filter(fn($p) => in_array($p->name, \App\Models\User::$defaultMemberPermissions, true));
                    }
                    return $perms;
                })->filter(fn($perms) => $perms->count() > 0);
            @endphp
            <div x-show="activeMatrixRole === '{{ $role->name }}'">
                @if($isMemberRole)
                    <!-- Friendly Explanatory Notice for Member Restrictions -->
                    <div class="bg-blue-50/70 dark:bg-blue-950/20 border border-blue-200/60 dark:border-blue-900/40 rounded-[10px] p-4 mb-5 flex items-start gap-3 shadow-2xs">
                        <span class="p-1.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-[8px] shrink-0 mt-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        </span>
                        <div class="flex flex-col gap-1 text-xs text-blue-950 dark:text-blue-200">
                            <span class="font-bold text-[13px]">Member Role Access & Restriction Policy</span>
                            <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                By default, all general members have full self-service portal access to savings, loans, payments, and attendance without needing any role assigned.
                                Assign this <strong>Member</strong> role to a user only when you want to enforce specific restrictions on that user (e.g. unchecking loan applications or withdrawal requests).
                                Unrealistic administrative and officer permissions (loan approvals, fund disbursements, cycle management, system tools) are hidden below.
                            </p>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.roles.update-permissions', $role->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- Clean 3-Column Responsive Grid of Module Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
                        @foreach($displayModules as $moduleName => $perms)
                            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800/60 rounded-[10px] p-4 flex flex-col justify-between shadow-2xs">
                                <div>
                                    <!-- Module Card Header -->
                                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-zinc-100 dark:border-zinc-800/60">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[13px] font-bold uppercase tracking-wider text-brand-primary">
                                                {{ $moduleName }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button 
                                                type="button" 
                                                onclick="document.querySelectorAll('.perm-{{ Str::slug($moduleName) }}-{{ $role->id }}').forEach(cb => cb.checked = true)"
                                                class="text-[11px] font-semibold text-zinc-400 hover:text-brand-primary cursor-pointer"
                                            >
                                                All
                                            </button>
                                            <span class="text-zinc-300 dark:text-zinc-700 text-[11px]">&bull;</span>
                                            <button 
                                                type="button" 
                                                onclick="document.querySelectorAll('.perm-{{ Str::slug($moduleName) }}-{{ $role->id }}').forEach(cb => cb.checked = false)"
                                                class="text-[11px] font-semibold text-zinc-400 hover:text-brand-primary cursor-pointer"
                                            >
                                                None
                                            </button>
                                            <span class="text-zinc-300 dark:text-zinc-700 text-[11px]">&bull;</span>
                                            <span class="text-xs font-mono font-bold text-zinc-400">{{ $perms->count() }}</span>
                                        </div>
                                    </div>

                                    <!-- Clean Checkbox List with Human Readable Friendly Names -->
                                    <div class="flex flex-col gap-2.5">
                                        @foreach($perms as $perm)
                                            @php
                                                $pMeta = $permissionMeta[$perm->name] ?? null;
                                                $pLabel = $pMeta['label'] ?? ucwords(str_replace(['.', '_'], ' ', $perm->name));
                                                $isChecked = in_array($perm->name, $rolePermissions);
                                            @endphp
                                            <label class="flex items-center gap-3 cursor-pointer group select-none py-0.5">
                                                <input 
                                                    type="checkbox" 
                                                    name="permissions[]" 
                                                    value="{{ $perm->name }}"
                                                    style="accent-color: var(--brand-primary);"
                                                    class="perm-{{ Str::slug($moduleName) }}-{{ $role->id }} rounded text-brand-primary focus:ring-brand-primary border-zinc-300 dark:border-zinc-700 w-[18px] h-[18px] cursor-pointer"
                                                    {{ $isChecked ? 'checked' : '' }}
                                                >
                                                <span class="text-zinc-800 dark:text-zinc-200 text-[13.5px] font-medium group-hover:text-zinc-950 dark:group-hover:text-white transition-colors">
                                                    {{ $pLabel }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Bottom Right Save Button -->
                    <div class="flex items-center justify-end mb-6">
                        <button 
                            type="submit" 
                            class="px-5 py-2.5 bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-[13px] font-bold rounded-[10px] shadow-sm transition-all hover:scale-[1.01] active:scale-[0.98] cursor-pointer select-none"
                        >
                            Save {{ ucfirst($role->name) }} Permissions
                        </button>
                    </div>
                </form>
            </div>
        @endforeach

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 3: Governance Rules (Clean, Standard System Card Design)           -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div x-show="activeTab === 'governance'" x-cloak class="flex flex-col gap-4">
        @foreach($conflictRules as $rule)
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800/60 rounded-[10px] p-5 shadow-xs flex flex-col gap-3">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-[10px] shrink-0 mt-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white">
                            {{ $rule['title'] }}
                        </h3>
                        <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                            <span class="text-[11px] font-bold text-zinc-400">Incompatible Roles:</span>
                            @foreach($rule['roles'] as $rName)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200/60 dark:border-zinc-700">
                                    {{ $rName }}
                                </span>
                                @if(!$loop->last)
                                    <span class="text-zinc-300 dark:text-zinc-600 text-xs">+</span>
                                @endif
                            @endforeach
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 leading-relaxed">
                            {{ $rule['description'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- Modal: Assign Roles to User (Clean, Standard System Modal)              -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div 
        x-show="editModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- Modal Backdrop -->
        <div 
            @click="editModalOpen = false"
            class="absolute inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-xs transition-opacity duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <!-- Modal Content Container -->
        <div 
            class="relative w-full max-w-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl p-6 text-left z-10 transition-transform duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        >
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex flex-col">
                    <h3 class="text-base font-bold text-zinc-950 dark:text-white">
                        Assign Roles: <span class="text-brand-primary" x-text="selectedUserName"></span>
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Select the organizational roles and privileges for this member.</p>
                </div>
                <button @click="editModalOpen = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1.5 rounded-lg transition-colors cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <!-- Dynamic Conflict Warning (Client-side instantaneous check) -->
            <template x-if="conflictWarning">
                <div class="mt-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-300 dark:border-amber-700 rounded-[10px] p-3 flex items-start gap-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <p class="text-xs font-semibold text-amber-800 dark:text-amber-300 leading-snug" x-text="conflictWarning"></p>
                </div>
            </template>

            <form :action="'{{ url('/admin/users') }}/' + selectedUserId + '/roles'" method="POST" class="mt-4 flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-2">
                    <label class="text-[11px] font-black uppercase text-zinc-500 dark:text-zinc-400">
                        Available Roles
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto pr-1">
                        @foreach($roles as $r)
                            <label 
                                class="flex items-start gap-3 p-3 border rounded-[10px] cursor-pointer transition-all border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 select-none shadow-2xs"
                                :class="selectedRoles.includes('{{ $r->name }}') ? 'border-brand-primary bg-brand-primary/5 dark:bg-brand-primary/10' : ''"
                            >
                                <input 
                                    type="checkbox" 
                                    name="roles[]" 
                                    value="{{ $r->name }}"
                                    x-model="selectedRoles"
                                    @change="checkConflicts()"
                                    style="accent-color: var(--brand-primary);"
                                    class="mt-0.5 rounded text-brand-primary focus:ring-brand-primary border-zinc-300 dark:border-zinc-700 w-4 h-4 cursor-pointer"
                                >
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[13.5px] font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                        {{ ucfirst($r->name) }}
                                        @if($r->name === 'member')
                                            <span class="text-[9.5px] font-bold px-1.5 py-0.5 rounded-full bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/40">Custom Restrictions</span>
                                        @endif
                                    </span>
                                    <span class="text-[11px] text-zinc-400 font-mono mt-0.5">
                                        @if($r->name === 'member')
                                            {{ $r->permissions->whereIn('name', \App\Models\User::$defaultMemberPermissions)->count() }} rules (for restricting member)
                                        @else
                                            {{ $r->permissions->count() }} permissions
                                        @endif
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Hidden override flag if acknowledged -->
                <template x-if="conflictWarning">
                    <label class="flex items-center gap-2 mt-1 cursor-pointer select-none p-2 bg-amber-50/50 dark:bg-amber-950/10 rounded-[10px] border border-amber-200 dark:border-amber-900">
                        <input type="checkbox" name="confirm_override" value="1" required class="rounded text-amber-600 focus:ring-amber-500 w-4 h-4">
                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            I understand the Segregation of Duties warning and authorize this assignment.
                        </span>
                    </label>
                </template>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <button 
                        type="button" 
                        @click="editModalOpen = false" 
                        class="py-2 px-4 text-xs font-bold rounded-[10px] border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="py-2 px-4 text-xs font-bold rounded-[10px] bg-zinc-950 hover:bg-zinc-900 dark:bg-zinc-50 dark:hover:bg-zinc-100 text-white dark:text-zinc-950 transition-all cursor-pointer shadow-xs active:scale-95 select-none"
                    >
                        Save Role Assignments
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
