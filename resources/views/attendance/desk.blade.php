@extends('layouts.app')

@section('content')
<div x-data="{ 
    sessionId: {{ $selectedSession ? $selectedSession->id : 'null' }},
    stats: {
        total_members: {{ $stats['total_members'] ?? 0 }},
        rsvps_attending: {{ $stats['rsvps_attending'] ?? 0 }},
        verified_present: {{ $stats['verified_present'] ?? 0 }},
        verified_absent: {{ $stats['verified_absent'] ?? 0 }},
        verified_excused: {{ $stats['verified_excused'] ?? 0 }},
        unverified: {{ $stats['unverified'] ?? 0 }}
    },
    members: {
        @foreach($members as $m)
            @php
                $att = $m->current_attendance;
            @endphp
            '{{ $m->id }}': {
                status: '{{ $att?->attendance_status ?? '' }}',
                officer_notes: '{{ addslashes($att?->officer_notes ?? '') }}',
                verifier_name: '{{ addslashes($att?->verifier?->name ?? '') }}',
                verified_at: '{{ $att?->verified_at ? $att->verified_at->format('M d, H:i') : '' }}',
                is_updating: false
            },
        @endforeach
    },
    noteModalOpen: false, 
    selectedMemberId: null, 
    selectedMemberName: '', 
    selectedStatus: 'excused', 
    officerNotes: '',
    viewNoteModalOpen: false,
    viewNoteTitle: 'Officer Note / Reason',
    viewNoteSubtitle: 'Official roll-call verification remark',
    viewNoteType: 'officer',
    viewNoteMemberId: null,
    viewNoteMemberName: '',
    viewNoteMemberCode: '',
    viewNoteStatus: '',
    viewNoteContent: '',
    viewNoteVerifier: '',
    viewNoteDate: '',
    presetReasons: [
        'Bereavement in family', 
        'Illness / Medical appointment', 
        'Official work conflict', 
        'Out of state travel', 
        'Prior approved absence'
    ],
    toast: { show: false, message: '', type: 'success' },
    showToast(message, type = 'success') {
        this.toast.message = message;
        this.toast.type = type;
        this.toast.show = true;
        setTimeout(() => { this.toast.show = false; }, 3200);
    },
    openViewNoteModal(memberId, name, code, status, note, verifier, date) {
        this.viewNoteTitle = 'Officer Note / Reason';
        this.viewNoteSubtitle = 'Official roll-call verification remark';
        this.viewNoteType = 'officer';
        this.viewNoteMemberId = memberId;
        this.viewNoteMemberName = name;
        this.viewNoteMemberCode = code;
        this.viewNoteStatus = status;
        this.viewNoteContent = note;
        this.viewNoteVerifier = verifier || 'Executive Officer';
        this.viewNoteDate = date || 'Recorded';
        this.viewNoteModalOpen = true;
    },
    openViewRsvpNoteModal(name, code, rsvpStatus, note, date) {
        this.viewNoteTitle = 'Member RSVP Note';
        this.viewNoteSubtitle = 'Advance note submitted by member';
        this.viewNoteType = 'rsvp';
        this.viewNoteMemberId = null;
        this.viewNoteMemberName = name;
        this.viewNoteMemberCode = code;
        this.viewNoteStatus = rsvpStatus;
        this.viewNoteContent = note;
        this.viewNoteVerifier = name;
        this.viewNoteDate = date || 'Submitted with RSVP';
        this.viewNoteModalOpen = true;
    },
    openNoteModal(memberId, name, status, currentNotes = '') {
        this.selectedMemberId = memberId;
        this.selectedMemberName = name;
        this.selectedStatus = status;
        this.officerNotes = currentNotes || '';
        this.noteModalOpen = true;
    },
    async markAttendance(memberId, status, notes = '') {
        if (!this.sessionId) return;
        const memberState = this.members[memberId];
        if (!memberState) return;

        const previousStatus = memberState.status;
        const previousNotes = memberState.officer_notes;
        memberState.is_updating = true;
        memberState.status = status;
        if (notes) memberState.officer_notes = notes;

        try {
            const res = await fetch('{{ route('attendance.mark') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    member_id: memberId,
                    status: status,
                    officer_notes: notes
                })
            });

            const data = await res.json();
            if (res.ok && data.success) {
                if (data.attendance) {
                    memberState.status = data.attendance.status;
                    memberState.officer_notes = data.attendance.officer_notes || '';
                    memberState.verifier_name = data.attendance.verifier_name || 'Officer';
                    memberState.verified_at = data.attendance.verified_at || 'Just now';
                }
                if (data.stats) {
                    this.stats.verified_present = data.stats.verified_present;
                    this.stats.verified_absent = data.stats.verified_absent;
                    this.stats.verified_excused = data.stats.verified_excused;
                    this.stats.unverified = data.stats.unverified;
                }
                this.showToast(data.message, 'success');
            } else {
                throw new Error(data.message || 'Failed to update attendance');
            }
        } catch (err) {
            memberState.status = previousStatus;
            memberState.officer_notes = previousNotes;
            this.showToast(err.message || 'Network error updating attendance', 'error');
        } finally {
            memberState.is_updating = false;
            if (this.noteModalOpen) this.noteModalOpen = false;
        }
    }
}" class="flex flex-col gap-6 w-full relative">

    <!-- Page Header & Session Switcher -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2.5">
                <span class="p-2 bg-brand-subtle rounded-xl text-brand-primary shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="m9 14 2 2 4-4"/></svg>
                </span>
                <h1 class="text-xl sm:text-2xl font-bold text-zinc-950 dark:text-white tracking-tight font-display">
                    Official Attendance Desk
                </h1>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Live executive roll-call verification for meetings and general assemblies. Updates in real-time.
            </p>
        </div>

        <!-- Session Selector & Bulk Roll-Call -->
        @if(isset($sessions) && $sessions->isNotEmpty())
            @php
                $sessionsData = $sessions->map(function($s) use ($selectedSession) {
                    return [
                        'id' => $s->id,
                        'title' => $s->title ?? 'Session #' . $s->session_number,
                        'date' => $s->session_date ? $s->session_date->format('M d, Y') : '',
                        'status' => ucfirst($s->status),
                        'selected' => $selectedSession && $selectedSession->id == $s->id,
                        'url' => route('attendance.desk', ['session_id' => $s->id]),
                    ];
                });
            @endphp
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <!-- Searchable Custom Session Dropdown -->
                <div 
                    x-data="{
                        dropdownOpen: false,
                        sessionQuery: '',
                        sessionList: {{ Js::from($sessionsData) }},
                        activeSession: {{ Js::from($selectedSession ? [
                            'id' => $selectedSession->id,
                            'title' => $selectedSession->title ?? 'Session #' . $selectedSession->session_number,
                            'date' => $selectedSession->session_date ? $selectedSession->session_date->format('M d, Y') : '',
                            'status' => ucfirst($selectedSession->status),
                        ] : null) }},
                        get filteredSessions() {
                            if (!this.sessionQuery.trim()) return this.sessionList;
                            const q = this.sessionQuery.toLowerCase();
                            return this.sessionList.filter(s => 
                                s.title.toLowerCase().includes(q) || 
                                s.date.toLowerCase().includes(q) || 
                                s.status.toLowerCase().includes(q)
                            );
                        },
                        switchSession(s) {
                            window.location.href = s.url;
                        }
                    }" 
                    class="relative w-full sm:w-[320px]"
                >
                    <!-- Trigger Button -->
                    <button 
                        type="button" 
                        @click="dropdownOpen = !dropdownOpen; if(dropdownOpen) $nextTick(() => $refs.sessionSearchInput?.focus())"
                        class="w-full flex items-center justify-between gap-3 bg-white dark:bg-zinc-900 border border-zinc-200/90 dark:border-zinc-800/90 hover:border-zinc-300 dark:hover:border-zinc-700 text-zinc-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-2xs select-none"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="p-1 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 rounded-md shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                            </span>
                            <div class="flex items-center gap-1.5 truncate">
                                <span class="font-bold truncate" x-text="activeSession ? activeSession.title + ' - ' + activeSession.date : 'Select Session'"></span>
                                <template x-if="activeSession">
                                    <span 
                                        class="text-[9px] font-bold px-1.5 py-0.2 rounded uppercase shrink-0"
                                        :class="activeSession.status === 'Open' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20' : (activeSession.status === 'Scheduled' ? 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500')"
                                        x-text="activeSession.status"
                                    ></span>
                                </template>
                            </div>
                        </div>

                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            width="14" 
                            height="14" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="2.5" 
                            stroke-linecap="round" 
                            stroke-linejoin="round" 
                            class="text-zinc-400 transition-transform duration-200 shrink-0"
                            :class="dropdownOpen ? 'rotate-180' : ''"
                        >
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>

                    <!-- Searchable Popover Dropdown Panel (Identical Width & Alignment) -->
                    <div 
                        x-show="dropdownOpen" 
                        x-cloak 
                        @click.outside="dropdownOpen = false" 
                        @keydown.escape.window="dropdownOpen = false"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                        class="absolute left-0 right-0 w-full top-full mt-1.5 z-40 bg-white dark:bg-zinc-900 border border-zinc-200/90 dark:border-zinc-800/90 rounded-2xl shadow-xl overflow-hidden p-2 flex flex-col gap-2"
                    >
                        <!-- Live Search Input -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </div>
                            <input 
                                x-ref="sessionSearchInput"
                                type="text" 
                                x-model="sessionQuery" 
                                placeholder="Search sessions..." 
                                style="padding-left: 2.25rem;"
                                class="w-full pl-9 pr-7 py-2 bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-brand-primary text-zinc-900 dark:text-white placeholder-zinc-400"
                            >
                            <button 
                                x-show="sessionQuery" 
                                @click="sessionQuery = ''; $refs.sessionSearchInput?.focus()" 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>

                        <!-- Scrollable Session Results List -->
                        <div class="max-h-60 overflow-y-auto space-y-1 pr-0.5">
                            <template x-for="s in filteredSessions" :key="s.id">
                                <button 
                                    type="button" 
                                    @click="switchSession(s)"
                                    class="w-full flex items-center justify-between gap-2.5 px-3 py-2 rounded-xl text-xs transition-colors cursor-pointer select-none text-left"
                                    :class="s.selected 
                                        ? 'bg-brand-primary/10 text-brand-primary font-bold' 
                                        : 'hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 font-semibold'"
                                >
                                    <div class="flex flex-col min-w-0">
                                        <span class="truncate" x-text="s.title"></span>
                                        <span class="text-[10px] text-zinc-400 font-normal" x-text="s.date"></span>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span 
                                            class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase"
                                            :class="s.status === 'Open' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20' : (s.status === 'Scheduled' ? 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500')"
                                            x-text="s.status"
                                        ></span>
                                        <template x-if="s.selected">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-brand-primary"><polyline points="20 6 9 17 4 12"/></svg>
                                        </template>
                                    </div>
                                </button>
                            </template>

                            <template x-if="filteredSessions.length === 0">
                                <div class="py-4 text-center text-xs text-zinc-400">
                                    No sessions match "<span x-text="sessionQuery" class="font-bold"></span>"
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                @if($selectedSession && $stats['rsvps_attending'] > 0)
                    <form method="POST" action="{{ route('attendance.bulk-mark-rsvps') }}" onsubmit="return confirm('Mark all {{ $stats['rsvps_attending'] }} member(s) who RSVP\'d \'Attending\' as officially Present?')">
                        @csrf
                        <input type="hidden" name="session_id" value="{{ $selectedSession->id }}">
                        <button 
                            type="submit" 
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-brand-primary hover:opacity-95 text-brand-primary-text text-xs font-bold rounded-xl transition-all cursor-pointer shadow-xs select-none"
                            title="Quickly verify all members who confirmed attendance in advance"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/></svg>
                            <span>Mark Attending RSVPs as Present</span>
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    @if(!$selectedSession)
        <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-600 dark:text-amber-400 mx-auto mb-2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><line x1="10" x2="14" y1="14" y2="18"/><line x1="14" x2="10" y1="14" y2="18"/></svg>
            <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">No Meeting Sessions Configured</h3>
            <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">Please create or generate yearly sessions under Njangi Cycles to track attendance.</p>
        </div>
    @else
        <!-- Summary Stats Grid (Live Updating) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- Total Active Members -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Active Roster</span>
                <div class="mt-1 text-2xl font-bold font-display text-zinc-900 dark:text-white" x-text="stats.total_members">{{ $stats['total_members'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Total registered</span>
            </div>

            <!-- Advance RSVP Attending -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">RSVP Attending</span>
                <div class="mt-1 text-2xl font-bold font-display text-blue-600 dark:text-blue-400" x-text="stats.rsvps_attending">{{ $stats['rsvps_attending'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Advance confirmations</span>
            </div>

            <!-- Verified Present -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Verified Present</span>
                <div class="mt-1 text-2xl font-bold font-display text-emerald-600 dark:text-emerald-400" x-text="stats.verified_present">{{ $stats['verified_present'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Official roll-call</span>
            </div>

            <!-- Verified Absent -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Verified Absent</span>
                <div class="mt-1 text-2xl font-bold font-display text-rose-600 dark:text-rose-400" x-text="stats.verified_absent">{{ $stats['verified_absent'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Unexcused</span>
            </div>

            <!-- Verified Excused -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Excused</span>
                <div class="mt-1 text-2xl font-bold font-display text-amber-600 dark:text-amber-400" x-text="stats.verified_excused">{{ $stats['verified_excused'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Official apology</span>
            </div>

            <!-- Pending Verification -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">Unverified</span>
                <div class="mt-1 text-2xl font-bold font-display text-zinc-600 dark:text-zinc-400" x-text="stats.unverified">{{ $stats['unverified'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Awaiting roll call</span>
            </div>
        </div>

        <!-- Filter Bar with Non-Colliding Search Box & Responsive Controls -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 sm:p-4 shadow-2xs">
            <form method="GET" action="{{ route('attendance.desk') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3.5">
                <input type="hidden" name="session_id" value="{{ $selectedSession->id }}">
                
                <!-- Fixed Search Input (Icon wrapped properly with 2.75rem padding) -->
                <div class="relative w-full lg:w-80" x-data="{ query: '{{ addslashes(request('search', '')) }}' }">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none flex items-center z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input 
                        type="text" 
                        name="search" 
                        x-model="query"
                        placeholder="Search by name or member ID..." 
                        style="padding-left: 2.75rem !important;"
                        class="w-full !pl-11 pr-8 py-2.5 bg-zinc-50/70 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-brand-primary text-zinc-900 dark:text-white placeholder-zinc-400 transition-colors"
                    >
                    <button 
                        x-show="query" 
                        x-cloak
                        @click="query = ''; $nextTick(() => $el.closest('form').submit())"
                        type="button" 
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer p-0.5 rounded-full"
                        title="Clear search"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <!-- Dropdown Filters Group (Responsive Wrapping & Styled Chevrons) -->
                <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                    <!-- RSVP Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-none min-w-[140px]">
                        <select 
                            name="rsvp_filter" 
                            onchange="this.form.submit()"
                            class="w-full appearance-none bg-zinc-50/70 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 pl-3.5 pr-8 py-2.5 rounded-xl text-xs font-semibold focus:outline-none focus:border-brand-primary text-zinc-800 dark:text-zinc-200 cursor-pointer transition-colors shadow-2xs"
                        >
                            <option value="">All RSVPs</option>
                            <option value="attending" {{ request('rsvp_filter') === 'attending' ? 'selected' : '' }}>RSVP: Attending</option>
                            <option value="not_attending" {{ request('rsvp_filter') === 'not_attending' ? 'selected' : '' }}>RSVP: Not Attending</option>
                            <option value="undecided" {{ request('rsvp_filter') === 'undecided' ? 'selected' : '' }}>RSVP: Undecided</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                    </div>

                    <!-- Verification Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-none min-w-[145px]">
                        <select 
                            name="attendance_filter" 
                            onchange="this.form.submit()"
                            class="w-full appearance-none bg-zinc-50/70 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 pl-3.5 pr-8 py-2.5 rounded-xl text-xs font-semibold focus:outline-none focus:border-brand-primary text-zinc-800 dark:text-zinc-200 cursor-pointer transition-colors shadow-2xs"
                        >
                            <option value="">All Verification</option>
                            <option value="present" {{ request('attendance_filter') === 'present' ? 'selected' : '' }}>Status: Present</option>
                            <option value="absent" {{ request('attendance_filter') === 'absent' ? 'selected' : '' }}>Status: Absent</option>
                            <option value="excused" {{ request('attendance_filter') === 'excused' ? 'selected' : '' }}>Status: Excused</option>
                            <option value="unverified" {{ request('attendance_filter') === 'unverified' ? 'selected' : '' }}>Status: Unverified</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                    </div>

                    @if(request('search') || request('rsvp_filter') || request('attendance_filter'))
                        <a 
                            href="{{ route('attendance.desk', ['session_id' => $selectedSession->id]) }}" 
                            class="inline-flex items-center gap-1 px-3 py-2.5 text-xs font-bold text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors shrink-0"
                            title="Reset all filters"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                            <span>Reset</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- MOBILE / TABLET VIEW: Touch-Friendly Quick Roll-Call Cards (< lg)   -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="block lg:hidden space-y-3">
            @forelse($members as $m)
                @php
                    $att = $m->current_attendance;
                    $rsvp = $att?->rsvp_status ?? 'undecided';
                @endphp
                <div 
                    class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl shadow-2xs flex flex-col gap-3 transition-all"
                    :class="{
                        'border-emerald-500/40 bg-emerald-50/20 dark:bg-emerald-950/10': members['{{ $m->id }}']?.status === 'present',
                        'border-rose-500/40 bg-rose-50/20 dark:bg-rose-950/10': members['{{ $m->id }}']?.status === 'absent',
                        'border-amber-500/40 bg-amber-50/20 dark:bg-amber-950/10': members['{{ $m->id }}']?.status === 'excused'
                    }"
                >
                    <!-- Member Header -->
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-bold text-xs text-zinc-700 dark:text-zinc-300 shrink-0">
                                {{ substr($m->first_name, 0, 1) }}{{ substr($m->last_name, 0, 1) }}
                            </div>
                            <div class="flex flex-col min-w-0">
                                <div class="font-bold text-sm text-zinc-900 dark:text-white truncate flex items-center gap-1.5 flex-wrap">
                                    <span>{{ $m->first_name }} {{ $m->last_name }}</span>
                                    @if($m->rank)
                                        <span class="text-[9px] font-black uppercase px-1.5 py-0.2 bg-amber-500/10 text-amber-700 dark:text-amber-400 rounded border border-amber-500/20 shrink-0">
                                            {{ $m->rank->name }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-zinc-400 font-mono">{{ $m->member_code }}</span>
                            </div>
                        </div>

                        <!-- Current Verification Badge & Note Icon (Live Alpine) -->
                        <div class="shrink-0 flex items-center gap-1.5">
                            <template x-if="members['{{ $m->id }}']?.status === 'present'">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Present
                                </span>
                            </template>
                            <template x-if="members['{{ $m->id }}']?.status === 'absent'">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-500/15 text-rose-700 dark:text-rose-400 border border-rose-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Absent
                                </span>
                            </template>
                            <template x-if="members['{{ $m->id }}']?.status === 'excused'">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Excused
                                </span>
                            </template>
                            <template x-if="!members['{{ $m->id }}']?.status">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                                    Pending
                                </span>
                            </template>

                            <!-- Subtle note icon button side-by-side -->
                            <template x-if="members['{{ $m->id }}']?.officer_notes">
                                <button 
                                    type="button" 
                                    @click="openViewNoteModal('{{ $m->id }}', '{{ addslashes($m->first_name . ' ' . $m->last_name) }}', '{{ $m->member_code }}', members['{{ $m->id }}']?.status, members['{{ $m->id }}']?.officer_notes, members['{{ $m->id }}']?.verifier_name, members['{{ $m->id }}']?.verified_at)"
                                    class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60 transition-all cursor-pointer active:scale-95 shadow-2xs"
                                    title="View apology note / reason"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Advance RSVP & Audit Sub-line -->
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-zinc-100 dark:border-zinc-800/60">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] text-zinc-400 font-medium">RSVP:</span>
                            @if($rsvp === 'attending')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50">
                                    Attending
                                </span>
                            @elseif($rsvp === 'not_attending')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50">
                                    Not Attending
                                </span>
                            @else
                                <span class="text-[10px] text-zinc-400 font-medium italic">No RSVP</span>
                            @endif

                            @if($att && $att->rsvp_notes)
                                <button 
                                    type="button" 
                                    @click="openViewRsvpNoteModal('{{ addslashes($m->first_name . ' ' . $m->last_name) }}', '{{ $m->member_code }}', 'RSVP: {{ ucfirst(str_replace('_', ' ', $rsvp)) }}', '{{ addslashes($att->rsvp_notes) }}', '{{ $att->updated_at ? $att->updated_at->format('M d, Y h:i A') : 'Recorded' }}')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200/80 dark:border-blue-800/60 transition-all cursor-pointer active:scale-95"
                                    title="View member RSVP note"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                </button>
                            @endif
                        </div>

                        <template x-if="members['{{ $m->id }}']?.verified_at">
                            <span class="text-[10px] text-zinc-400 font-mono" x-text="'Verified ' + members['{{ $m->id }}'].verified_at"></span>
                        </template>
                    </div>

                    <!-- Big Touch-Friendly Roll-Call Tap Targets -->
                    <div class="grid grid-cols-3 gap-2 pt-1">
                        <!-- Present -->
                        <button 
                            type="button" 
                            @click="markAttendance('{{ $m->id }}', 'present')" 
                            :disabled="members['{{ $m->id }}']?.is_updating"
                            :class="members['{{ $m->id }}']?.status === 'present' 
                                ? 'bg-emerald-600 text-white shadow-xs font-bold ring-2 ring-emerald-500/40' 
                                : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:hover:bg-emerald-900/50 dark:text-emerald-300 font-semibold'"
                            class="py-2.5 px-3 rounded-xl text-xs transition-all active:scale-[0.97] flex items-center justify-center gap-1.5 cursor-pointer select-none"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Present</span>
                        </button>

                        <!-- Absent -->
                        <button 
                            type="button" 
                            @click="markAttendance('{{ $m->id }}', 'absent')" 
                            :disabled="members['{{ $m->id }}']?.is_updating"
                            :class="members['{{ $m->id }}']?.status === 'absent' 
                                ? 'bg-rose-600 text-white shadow-xs font-bold ring-2 ring-rose-500/40' 
                                : 'bg-rose-50 hover:bg-rose-100 text-rose-800 dark:bg-rose-950/30 dark:hover:bg-rose-900/50 dark:text-rose-300 font-semibold'"
                            class="py-2.5 px-3 rounded-xl text-xs transition-all active:scale-[0.97] flex items-center justify-center gap-1.5 cursor-pointer select-none"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <span>Absent</span>
                        </button>

                        <!-- Excused -->
                        <button 
                            type="button" 
                            @click="openNoteModal('{{ $m->id }}', '{{ addslashes($m->name) }}', 'excused', members['{{ $m->id }}']?.officer_notes || '')" 
                            :disabled="members['{{ $m->id }}']?.is_updating"
                            :class="members['{{ $m->id }}']?.status === 'excused' 
                                ? 'bg-amber-600 text-white shadow-xs font-bold ring-2 ring-amber-500/40' 
                                : 'bg-amber-50 hover:bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:hover:bg-amber-900/50 dark:text-amber-300 font-semibold'"
                            class="py-2.5 px-3 rounded-xl text-xs transition-all active:scale-[0.97] flex items-center justify-center gap-1.5 cursor-pointer select-none"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            <span>Excused</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-zinc-400 text-xs bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl">
                    No members found matching the specified criteria.
                </div>
            @endforelse
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- DESKTOP VIEW: Full Spacious Table (>= lg)                           -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="hidden lg:block bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/20 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                            <th class="px-4 py-3.5">Member</th>
                            <th class="px-4 py-3.5">Advance RSVP</th>
                            <th class="px-4 py-3.5">Official Verification</th>
                            <th class="px-4 py-3.5 text-center">Quick Roll-Call Actions</th>
                            <th class="px-4 py-3.5">Verification Audit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800 text-xs">
                        @forelse($members as $m)
                            @php
                                $att = $m->current_attendance;
                                $rsvp = $att?->rsvp_status ?? 'undecided';
                            @endphp
                            <tr 
                                class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors"
                                :class="{
                                    'bg-emerald-50/20 dark:bg-emerald-950/10': members['{{ $m->id }}']?.status === 'present',
                                    'bg-rose-50/20 dark:bg-rose-950/10': members['{{ $m->id }}']?.status === 'absent',
                                    'bg-amber-50/20 dark:bg-amber-950/10': members['{{ $m->id }}']?.status === 'excused'
                                }"
                            >
                                <!-- Member Info -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-bold text-xs text-zinc-700 dark:text-zinc-300 shrink-0">
                                            {{ substr($m->first_name, 0, 1) }}{{ substr($m->last_name, 0, 1) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <div class="font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                                <span>{{ $m->first_name }} {{ $m->last_name }}</span>
                                                @if($m->rank)
                                                    <span class="text-[9px] font-black uppercase px-1.5 py-0.2 bg-amber-500/10 text-amber-700 dark:text-amber-400 rounded-md border border-amber-500/20">
                                                        {{ $m->rank->name }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-[10px] text-zinc-400 font-mono">{{ $m->member_code }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Advance RSVP -->
                                <td class="px-4 py-3.5">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if($rsvp === 'attending')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                                                Attending
                                            </span>
                                        @elseif($rsvp === 'not_attending')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="8" x2="22" y2="13"/><line x1="22" y1="8" x2="17" y2="13"/></svg>
                                                Not Attending
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                                                No RSVP
                                            </span>
                                        @endif

                                        @if($att && $att->rsvp_notes)
                                            <button 
                                                type="button" 
                                                @click="openViewRsvpNoteModal('{{ addslashes($m->first_name . ' ' . $m->last_name) }}', '{{ $m->member_code }}', 'RSVP: {{ ucfirst(str_replace('_', ' ', $rsvp)) }}', '{{ addslashes($att->rsvp_notes) }}', '{{ $att->updated_at ? $att->updated_at->format('M d, Y h:i A') : 'Recorded' }}')"
                                                class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200/80 dark:border-blue-800/60 transition-all cursor-pointer active:scale-95 shadow-2xs"
                                                title="View member RSVP note"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Official Status (Live Alpine) -->
                                <td class="px-4 py-3.5">
                                    <div class="inline-flex items-center gap-2">
                                        <template x-if="members['{{ $m->id }}']?.status === 'present'">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Present
                                            </span>
                                        </template>
                                        <template x-if="members['{{ $m->id }}']?.status === 'absent'">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                                Absent
                                            </span>
                                        </template>
                                        <template x-if="members['{{ $m->id }}']?.status === 'excused'">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                Excused
                                            </span>
                                        </template>
                                        <template x-if="!members['{{ $m->id }}']?.status">
                                            <span class="text-xs text-zinc-400 font-medium italic">Pending Verification</span>
                                        </template>

                                        <!-- Subtle Note Icon Side-by-Side (Single Line, No Row Stretching) -->
                                        <template x-if="members['{{ $m->id }}']?.officer_notes">
                                            <button 
                                                type="button" 
                                                @click="openViewNoteModal('{{ $m->id }}', '{{ addslashes($m->first_name . ' ' . $m->last_name) }}', '{{ $m->member_code }}', members['{{ $m->id }}']?.status, members['{{ $m->id }}']?.officer_notes, members['{{ $m->id }}']?.verifier_name, members['{{ $m->id }}']?.verified_at)"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 dark:hover:bg-amber-900/60 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60 transition-all cursor-pointer active:scale-95 shadow-2xs"
                                                title="View apology note / reason"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>

                                <!-- Quick Actions (AJAX Trigger) -->
                                <td class="px-4 py-3.5 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Present Button -->
                                        <button 
                                            type="button" 
                                            @click="markAttendance('{{ $m->id }}', 'present')" 
                                            :disabled="members['{{ $m->id }}']?.is_updating"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer select-none"
                                            :class="members['{{ $m->id }}']?.status === 'present' 
                                                ? 'bg-emerald-600 text-white shadow-xs' 
                                                : 'bg-zinc-100 hover:bg-emerald-100 hover:text-emerald-700 dark:bg-zinc-800 dark:hover:bg-emerald-950/60 dark:hover:text-emerald-300 text-zinc-600 dark:text-zinc-400'"
                                            title="Mark Present"
                                        >
                                            P
                                        </button>

                                        <!-- Absent Button -->
                                        <button 
                                            type="button" 
                                            @click="markAttendance('{{ $m->id }}', 'absent')" 
                                            :disabled="members['{{ $m->id }}']?.is_updating"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer select-none"
                                            :class="members['{{ $m->id }}']?.status === 'absent' 
                                                ? 'bg-rose-600 text-white shadow-xs' 
                                                : 'bg-zinc-100 hover:bg-rose-100 hover:text-rose-700 dark:bg-zinc-800 dark:hover:bg-rose-950/60 dark:hover:text-rose-300 text-zinc-600 dark:text-zinc-400'"
                                            title="Mark Absent"
                                        >
                                            A
                                        </button>

                                        <!-- Excused Button (With Modal) -->
                                        <button 
                                            type="button" 
                                            @click="openNoteModal('{{ $m->id }}', '{{ addslashes($m->name) }}', 'excused', members['{{ $m->id }}']?.officer_notes || '')" 
                                            :disabled="members['{{ $m->id }}']?.is_updating"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer select-none"
                                            :class="members['{{ $m->id }}']?.status === 'excused' 
                                                ? 'bg-amber-600 text-white shadow-xs' 
                                                : 'bg-zinc-100 hover:bg-amber-100 hover:text-amber-700 dark:bg-zinc-800 dark:hover:bg-amber-950/60 dark:hover:text-amber-300 text-zinc-600 dark:text-zinc-400'"
                                            title="Mark Excused with note"
                                        >
                                            E
                                        </button>
                                    </div>
                                </td>

                                <!-- Audit Info -->
                                <td class="px-4 py-3.5">
                                    <template x-if="members['{{ $m->id }}']?.verified_at">
                                        <div class="flex flex-col text-[11px]">
                                            <span class="font-semibold text-zinc-800 dark:text-zinc-200" x-text="members['{{ $m->id }}'].verifier_name || 'Officer'"></span>
                                            <span class="text-[10px] text-zinc-400 font-mono" x-text="members['{{ $m->id }}'].verified_at"></span>
                                        </div>
                                    </template>
                                    <template x-if="!members['{{ $m->id }}']?.verified_at">
                                        <span class="text-[11px] text-zinc-400">-</span>
                                    </template>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-zinc-400 text-xs">
                                    No members found matching the specified criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- View Note / Apology Modal (Unified max-w-md for Officer & RSVP notes) -->
    <div 
        x-show="viewNoteModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div 
            @click.outside="viewNoteModalOpen = false" 
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl max-w-md w-full p-6 relative animate-fadeIn"
        >
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2.5">
                    <span 
                        class="p-2 rounded-xl"
                        :class="viewNoteType === 'rsvp' ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    <div class="flex flex-col">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white" x-text="viewNoteTitle"></h3>
                        <p class="text-[11px] text-zinc-400 font-medium" x-text="viewNoteSubtitle"></p>
                    </div>
                </div>
                <button @click="viewNoteModalOpen = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="mt-4 flex flex-col gap-3.5">
                <!-- Member details strip -->
                <div class="flex items-center justify-between bg-zinc-50 dark:bg-zinc-950/40 p-2.5 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-zinc-900 dark:text-white" x-text="viewNoteMemberName"></span>
                        <span class="text-[10px] text-zinc-400 font-mono" x-text="viewNoteMemberCode"></span>
                    </div>
                    <span 
                        class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                        :class="viewNoteStatus.toLowerCase().includes('attend') ? 'bg-blue-500/10 text-blue-700 dark:text-blue-400' : (viewNoteStatus === 'present' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : (viewNoteStatus === 'absent' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400' : 'bg-amber-500/10 text-amber-700 dark:text-amber-400'))"
                        x-text="viewNoteStatus"
                    ></span>
                </div>

                <!-- Full note content -->
                <div 
                    class="rounded-xl p-3.5 text-xs leading-relaxed whitespace-pre-wrap break-words italic border"
                    :class="viewNoteType === 'rsvp' ? 'bg-blue-50/40 dark:bg-blue-950/20 border-blue-200/60 dark:border-blue-800/40 text-blue-900 dark:text-blue-200' : 'bg-amber-50/40 dark:bg-amber-950/20 border-amber-200/60 dark:border-amber-800/40 text-zinc-800 dark:text-zinc-200'"
                    x-text="'&ldquo;' + viewNoteContent + '&rdquo;'"
                ></div>

                <!-- Audit info -->
                <div class="flex items-center justify-between text-[11px] text-zinc-400 pt-1">
                    <span x-text="(viewNoteType === 'rsvp' ? 'Submitted by: ' : 'Recorded by: ') + viewNoteVerifier"></span>
                    <span class="font-mono" x-text="viewNoteDate"></span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 mt-2 border-t border-zinc-100 dark:border-zinc-800">
                <template x-if="viewNoteType === 'officer'">
                    <button 
                        type="button" 
                        @click="viewNoteModalOpen = false; openNoteModal(viewNoteMemberId, viewNoteMemberName, viewNoteStatus, viewNoteContent)" 
                        class="py-2 px-3 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer"
                    >
                        Edit Note
                    </button>
                </template>
                <button 
                    type="button" 
                    @click="viewNoteModalOpen = false" 
                    class="py-2 px-4 text-xs font-bold rounded-xl bg-brand-primary text-brand-primary-text hover:opacity-95 transition-colors cursor-pointer shadow-xs"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Standardized Excused / Officer Note Modal (max-w-lg) with Fast AJAX Submit -->
    <div 
        x-show="noteModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div 
            @click.outside="noteModalOpen = false" 
            class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 relative animate-fadeIn"
        >
            <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                <div class="flex flex-col">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white" x-text="'Mark ' + selectedStatus.toUpperCase() + ': ' + selectedMemberName"></h3>
                    <p class="text-xs text-zinc-500">Record apology reason or official roll-call notes.</p>
                </div>
                <button @click="noteModalOpen = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="mt-4 flex flex-col gap-4">
                <!-- Preset Reason Quick Chips -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Quick Preset Reasons:
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="preset in presetReasons" :key="preset">
                            <button 
                                type="button" 
                                @click="officerNotes = preset"
                                class="px-2.5 py-1 bg-zinc-100 hover:bg-zinc-200/80 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-[11px] font-semibold rounded-lg transition-colors cursor-pointer"
                                x-text="preset"
                            ></button>
                        </template>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                        Officer Notes / Apology Reason
                    </label>
                    <textarea 
                        x-model="officerNotes" 
                        rows="3" 
                        placeholder="e.g. Bereavement apology accepted by President, illness, official travel..." 
                        class="w-full px-3.5 py-2.5 text-xs font-semibold bg-zinc-50/70 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:border-brand-primary text-zinc-900 dark:text-white"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <button 
                        type="button" 
                        @click="noteModalOpen = false" 
                        class="py-2.5 px-4 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        @click="markAttendance(selectedMemberId, selectedStatus, officerNotes)"
                        class="py-2.5 px-4 text-xs font-bold rounded-xl bg-brand-primary hover:opacity-95 text-brand-primary-text transition-colors cursor-pointer shadow-xs"
                    >
                        Save Verification
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Live Toast Notification -->
    <div 
        x-show="toast.show" 
        x-cloak 
        x-transition:enter="transition ease-out duration-200" 
        x-transition:enter-start="opacity-0 translate-y-3" 
        x-transition:enter-end="opacity-100 translate-y-0" 
        x-transition:leave="transition ease-in duration-150" 
        x-transition:leave-start="opacity-100 translate-y-0" 
        x-transition:leave-end="opacity-0 translate-y-3"
        class="fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-xl text-xs font-bold select-none"
        :class="toast.type === 'success' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-950' : 'bg-rose-600 text-white'"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polyline points="20 6 9 17 4 12"/></svg>
        <span x-text="toast.message"></span>
    </div>
</div>
@endsection
