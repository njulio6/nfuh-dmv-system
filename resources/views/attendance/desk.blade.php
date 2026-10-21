@extends('layouts.app')

@section('content')
<div x-data="{ 
    sessionId: {{ $selectedSession ? $selectedSession->id : 'null' }},
    activeTab: 'members',
    qrModalOpen: false,
    windowModalOpen: false,
    stats: {
        total_members: {{ $stats['total_members'] ?? 0 }},
        verified_present: {{ $stats['verified_present'] ?? 0 }},
        qr_checkins: {{ $stats['qr_checkins'] ?? 0 }},
        officer_verified: {{ $stats['officer_verified'] ?? 0 }},
        verified_absent: {{ $stats['verified_absent'] ?? 0 }},
        verified_excused: {{ $stats['verified_excused'] ?? 0 }},
        unverified: {{ $stats['unverified'] ?? 0 }},
        total_guests: {{ $stats['total_guests'] ?? 0 }}
    },
    members: {
        @foreach($members as $m)
            @php
                $att = $m->current_attendance;
            @endphp
            '{{ $m->id }}': {
                status: '{{ $att?->attendance_status ?? '' }}',
                source: '{{ $att?->check_in_source ?? '' }}',
                officer_notes: '{{ addslashes($att?->officer_notes ?? '') }}',
                verifier_name: '{{ addslashes($att?->verifier?->name ?? '') }}',
                verified_at: '{{ $att?->verified_at ? ($selectedSession?->check_in_timezone ? $att->verified_at->setTimezone($selectedSession->check_in_timezone)->format('M d, g:i A') : $att->verified_at->format('M d, g:i A')) : '' }}',
                check_in_at: '{{ $att?->check_in_at ? ($selectedSession?->check_in_timezone ? $att->check_in_at->setTimezone($selectedSession->check_in_timezone)->format('M d, g:i A') : $att->check_in_at->format('M d, g:i A')) : '' }}',
                is_updating: false
            },
        @endforeach
    },
    noteModalOpen: false, 
    selectedMemberId: null, 
    selectedMemberName: '', 
    selectedStatus: 'excused', 
    officerNotes: '',
    reasonNote: '',
    viewNoteModalOpen: false,
    viewNoteTitle: 'Officer Note / Reason',
    viewNoteSubtitle: 'Official roll-call remark & audit trail',
    viewNoteMemberId: null,
    viewNoteMemberName: '',
    viewNoteMemberCode: '',
    viewNoteStatus: '',
    viewNoteContent: '',
    viewNoteVerifier: '',
    viewNoteDate: '',
    copiedLink: false,
    presetReasons: [
        'Bereavement in family', 
        'Illness / Medical appointment', 
        'Official work conflict', 
        'Out of state travel', 
        'Arrived late after cutoff'
    ],
    toast: { show: false, message: '', type: 'success' },
    showToast(message, type = 'success') {
        this.toast.message = message;
        this.toast.type = type;
        this.toast.show = true;
        setTimeout(() => { this.toast.show = false; }, 3200);
    },
    copyCheckInUrl(url) {
        navigator.clipboard.writeText(url).then(() => {
            this.copiedLink = true;
            this.showToast('Check-in link copied to clipboard!', 'success');
            setTimeout(() => { this.copiedLink = false; }, 2500);
        });
    },
    openViewNoteModal(memberId, name, code, status, note, verifier, date) {
        this.viewNoteTitle = 'Officer Note / Reason';
        this.viewNoteSubtitle = 'Official roll-call verification remark';
        this.viewNoteMemberId = memberId;
        this.viewNoteMemberName = name;
        this.viewNoteMemberCode = code;
        this.viewNoteStatus = status;
        this.viewNoteContent = note;
        this.viewNoteVerifier = verifier || 'Executive Officer';
        this.viewNoteDate = date || 'Recorded';
        this.viewNoteModalOpen = true;
    },
    openNoteModal(memberId, name, status, currentNotes = '') {
        this.selectedMemberId = memberId;
        this.selectedMemberName = name;
        this.selectedStatus = status;
        this.officerNotes = currentNotes || '';
        this.reasonNote = currentNotes || '';
        this.noteModalOpen = true;
    },
    async markAttendance(memberId, status, notes = '', reason = '') {
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
                    officer_notes: notes,
                    reason: reason || notes
                })
            });

            const data = await res.json();
            if (res.ok && data.success) {
                if (data.attendance) {
                    memberState.status = data.attendance.status;
                    memberState.source = data.attendance.check_in_source || 'officer_verification';
                    memberState.officer_notes = data.attendance.officer_notes || '';
                    memberState.verifier_name = data.attendance.verifier_name || 'Officer';
                    memberState.verified_at = data.attendance.verified_at || 'Just now';
                    memberState.check_in_at = data.attendance.check_in_at || 'Just now';
                }
                if (data.stats) {
                    this.stats.verified_present = data.stats.verified_present;
                    this.stats.qr_checkins = data.stats.qr_checkins;
                    this.stats.officer_verified = data.stats.officer_verified;
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
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        }
    },
    init() {
        this.$nextTick(() => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    }
}" class="flex flex-col gap-6 w-full relative">

    <!-- Page Header & Session Controls -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2.5">
                <span class="p-2 bg-brand-subtle rounded-xl text-brand-primary shrink-0">
                    <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                </span>
                <h1 class="text-xl sm:text-2xl font-bold text-zinc-950 dark:text-white tracking-tight font-display">
                    Official Attendance Desk
                </h1>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Live executive roll-call, QR self check-in desk, and guest registration.
            </p>
        </div>

        <!-- Session Selector & Action Buttons -->
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
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full lg:w-auto">
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
                    class="relative w-full sm:w-[280px]"
                >
                    <!-- Trigger Button -->
                    <button 
                        type="button" 
                        @click="dropdownOpen = !dropdownOpen; if(dropdownOpen) $nextTick(() => $refs.sessionSearchInput?.focus())"
                        class="w-full h-11 flex items-center justify-between gap-3 bg-white dark:bg-zinc-900 border border-zinc-200/90 dark:border-zinc-800/90 hover:border-zinc-300 dark:hover:border-zinc-700 text-zinc-900 dark:text-white px-3.5 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-2xs select-none"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="p-1 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 rounded-md shrink-0">
                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            </span>
                            <div class="flex items-center gap-1.5 truncate">
                                <span class="font-bold truncate" x-text="activeSession ? activeSession.title + ' - ' + activeSession.date : 'Select Session'"></span>
                            </div>
                        </div>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-zinc-400 transition-transform duration-200 shrink-0" :class="dropdownOpen ? 'rotate-180' : ''"></i>
                    </button>

                    <!-- Dropdown Panel -->
                    <div 
                        x-show="dropdownOpen" 
                        x-cloak 
                        @click.outside="dropdownOpen = false" 
                        class="absolute left-0 right-0 w-full top-full mt-1.5 z-40 bg-white dark:bg-zinc-900 border border-zinc-200/90 dark:border-zinc-800/90 rounded-2xl shadow-xl overflow-hidden p-2 flex flex-col gap-2"
                    >
                        <input 
                            x-ref="sessionSearchInput"
                            type="text" 
                            x-model="sessionQuery" 
                            placeholder="Search sessions..." 
                            class="w-full px-3 py-2 text-xs rounded-lg bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 focus:outline-none focus:border-brand-primary"
                        >
                        <div class="max-h-56 overflow-y-auto space-y-1">
                            <template x-for="s in filteredSessions" :key="s.id">
                                <button 
                                    type="button" 
                                    @click="switchSession(s)"
                                    class="w-full flex items-center justify-between p-2 rounded-lg text-left text-xs hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                                    :class="s.selected ? 'bg-zinc-100 dark:bg-zinc-800 font-bold' : ''"
                                >
                                    <span x-text="s.title + ' (' + s.date + ')'"></span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded uppercase font-semibold" x-text="s.status"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                @if($selectedSession)
                    <!-- Action Buttons: 2-col equal grid on mobile, inline flex on desktop -->
                    <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                        <!-- QR Code Projection Button -->
                        <button 
                            type="button" 
                            @click="qrModalOpen = true"
                            class="h-11 flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 sm:px-4 bg-brand-primary hover:opacity-95 text-brand-primary-text text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer whitespace-nowrap active:scale-95"
                        >
                            <i data-lucide="qr-code" class="w-4 h-4 shrink-0"></i>
                            <span>Display QR</span>
                        </button>

                        <!-- Time Window Settings Button -->
                        <button 
                            type="button" 
                            @click="windowModalOpen = true"
                            class="h-11 flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 sm:px-4 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800 border border-zinc-200/90 dark:border-zinc-800/90 hover:border-zinc-300 dark:hover:border-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-bold rounded-xl shadow-2xs transition-all cursor-pointer whitespace-nowrap"
                            title="Configure Check-in Opening & Cutoff Hours"
                        >
                            <i data-lucide="clock" class="w-4 h-4 shrink-0"></i>
                            <span>Time Window</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if(!$selectedSession)
        <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6 text-center">
            <i data-lucide="alert-triangle" class="w-8 h-8 text-amber-600 dark:text-amber-400 mx-auto mb-2"></i>
            <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">No Meeting Sessions Configured</h3>
            <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">Please create or generate yearly sessions under Njangi Cycles to track attendance.</p>
        </div>
    @else
        <!-- Summary Stats Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
            <!-- Total Active Members -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Active Roster</span>
                <div class="mt-1 text-2xl font-bold font-display text-zinc-900 dark:text-white" x-text="stats.total_members">{{ $stats['total_members'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Members registered</span>
            </div>

            <!-- Verified Present Total -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Present</span>
                <div class="mt-1 text-2xl font-bold font-display text-emerald-600 dark:text-emerald-400" x-text="stats.verified_present">{{ $stats['verified_present'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Total in room</span>
            </div>

            <!-- QR Self Check-Ins -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">QR Self-Scans</span>
                <div class="mt-1 text-2xl font-bold font-display text-blue-600 dark:text-blue-400" x-text="stats.qr_checkins">{{ $stats['qr_checkins'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Direct phone scans</span>
            </div>

            <!-- Officer Desk Verified -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">Desk Verified</span>
                <div class="mt-1 text-2xl font-bold font-display text-purple-600 dark:text-purple-400" x-text="stats.officer_verified">{{ $stats['officer_verified'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Manual roll-call</span>
            </div>

            <!-- Excused -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Excused</span>
                <div class="mt-1 text-2xl font-bold font-display text-amber-600 dark:text-amber-400" x-text="stats.verified_excused">{{ $stats['verified_excused'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Audited apologies</span>
            </div>

            <!-- Absent -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-col justify-between shadow-2xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Absent</span>
                <div class="mt-1 text-2xl font-bold font-display text-rose-600 dark:text-rose-400" x-text="stats.verified_absent">{{ $stats['verified_absent'] }}</div>
                <span class="text-[10px] text-zinc-400 mt-0.5">Unexcused</span>
            </div>

            <!-- Guest Attendees (Spans full width on mobile/tablet to avoid dangling lone card) -->
            <div class="col-span-2 sm:col-span-3 lg:col-span-1 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 flex flex-row lg:flex-col items-center lg:items-start justify-between shadow-2xs cursor-pointer hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors"
                 @click="activeTab = 'guests'">
                <div class="flex flex-col">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400">Guests</span>
                    <div class="hidden lg:block mt-1 text-2xl font-bold font-display text-teal-600 dark:text-teal-400" x-text="stats.total_guests">{{ $stats['total_guests'] }}</div>
                    <span class="text-[10px] text-zinc-400 mt-0.5">Click to view &rarr;</span>
                </div>
                <div class="lg:hidden text-2xl font-bold font-display text-teal-600 dark:text-teal-400" x-text="stats.total_guests">{{ $stats['total_guests'] }}</div>
            </div>
        </div>

        <!-- Tab Selector: Member Roster vs. Guest Attendees -->
        <div class="grid grid-cols-2 sm:flex items-center gap-1.5 p-1 bg-zinc-100 dark:bg-zinc-800/80 rounded-2xl sm:bg-transparent sm:dark:bg-transparent sm:p-0 sm:border-b sm:border-zinc-200 sm:dark:border-zinc-800 sm:pb-2">
            <button 
                type="button" 
                @click="activeTab = 'members'"
                class="px-3 py-2.5 sm:px-4 sm:py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center sm:justify-start gap-1.5 sm:gap-2 cursor-pointer"
                :class="activeTab === 'members' ? 'bg-brand-primary text-brand-primary-text shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white font-semibold'"
            >
                <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
                <span class="sm:hidden">Members</span>
                <span class="hidden sm:inline">Member Roll-Call Roster</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeTab === 'members' ? 'bg-white/20 text-brand-primary-text' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'" x-text="stats.total_members"></span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'guests'"
                class="px-3 py-2.5 sm:px-4 sm:py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center sm:justify-start gap-1.5 sm:gap-2 cursor-pointer"
                :class="activeTab === 'guests' ? 'bg-brand-primary text-brand-primary-text shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white font-semibold'"
            >
                <i data-lucide="user-plus" class="w-4 h-4 shrink-0"></i>
                <span class="sm:hidden">Guests</span>
                <span class="hidden sm:inline">Guest Attendees</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeTab === 'guests' ? 'bg-white/20 text-brand-primary-text' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'" x-text="stats.total_guests"></span>
            </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- TAB 1: MEMBERS ROSTER                                               -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div x-show="activeTab === 'members'" class="space-y-4">
            <!-- Filter Bar -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-3.5 sm:p-4 shadow-2xs">
                <form method="GET" action="{{ route('attendance.desk') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3.5">
                    <input type="hidden" name="session_id" value="{{ $selectedSession->id }}">
                    
                    <!-- Search Input -->
                    <div class="relative w-full lg:w-80" x-data="{ query: '{{ addslashes(request('search', '')) }}' }">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none flex items-center z-10">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
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
                        >
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Filter Dropdowns -->
                    <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                        <!-- Verification Status Filter -->
                        <div class="relative flex-1 sm:flex-none min-w-[160px]">
                            <select 
                                name="attendance_filter" 
                                onchange="this.form.submit()"
                                class="w-full appearance-none bg-zinc-50/70 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 pl-3.5 pr-8 py-2.5 rounded-xl text-xs font-semibold focus:outline-none focus:border-brand-primary text-zinc-800 dark:text-zinc-200 cursor-pointer transition-colors shadow-2xs"
                            >
                                <option value="">All Verification Status</option>
                                <option value="present" {{ request('attendance_filter') === 'present' ? 'selected' : '' }}>Present (All)</option>
                                <option value="qr_checkin" {{ request('attendance_filter') === 'qr_checkin' ? 'selected' : '' }}>QR Self Check-In</option>
                                <option value="absent" {{ request('attendance_filter') === 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="excused" {{ request('attendance_filter') === 'excused' ? 'selected' : '' }}>Excused</option>
                                <option value="unverified" {{ request('attendance_filter') === 'unverified' ? 'selected' : '' }}>Unverified</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400">
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>

                        @if(request('search') || request('attendance_filter'))
                            <a 
                                href="{{ route('attendance.desk', ['session_id' => $selectedSession->id]) }}" 
                                class="inline-flex items-center gap-1 px-3 py-2.5 text-xs font-bold text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors shrink-0"
                            >
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                <span>Reset</span>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Mobile View: Cards (< lg) -->
            <div class="block lg:hidden space-y-3">
                @forelse($members as $m)
                    <div 
                        class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl shadow-2xs flex flex-col gap-3 transition-all"
                        :class="{
                            'border-emerald-500/40 bg-emerald-50/20 dark:bg-emerald-950/10': members['{{ $m->id }}']?.status === 'present',
                            'border-rose-500/40 bg-rose-50/20 dark:bg-rose-950/10': members['{{ $m->id }}']?.status === 'absent',
                            'border-amber-500/40 bg-amber-50/20 dark:bg-amber-950/10': members['{{ $m->id }}']?.status === 'excused'
                        }"
                    >
                        <div class="flex items-start justify-between gap-2.5">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-bold text-xs text-zinc-700 dark:text-zinc-300 shrink-0">
                                    {{ substr($m->first_name, 0, 1) }}{{ substr($m->last_name, 0, 1) }}
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <div class="font-bold text-sm text-zinc-900 dark:text-white truncate flex items-center gap-1.5 flex-wrap">
                                        <span>{{ $m->name }}</span>
                                        <span class="text-[9px] font-black uppercase px-1.5 py-0.2 bg-amber-500/10 text-amber-700 dark:text-amber-400 rounded border border-amber-500/20 shrink-0">
                                            {{ $m->traditional_title }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] text-zinc-400 font-mono">{{ $m->member_code }}</span>
                                </div>
                            </div>

                            <!-- Status Badge -->
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
                                        Unverified
                                    </span>
                                </template>

                                <template x-if="members['{{ $m->id }}']?.officer_notes">
                                    <button 
                                        type="button" 
                                        @click="openViewNoteModal('{{ $m->id }}', '{{ addslashes($m->name) }}', '{{ $m->member_code }}', members['{{ $m->id }}']?.status, members['{{ $m->id }}']?.officer_notes, members['{{ $m->id }}']?.verifier_name, members['{{ $m->id }}']?.verified_at)"
                                        class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60"
                                    >
                                        <i data-lucide="message-square" class="w-3 h-3"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Source Badge & Timestamp -->
                        <div class="flex items-center justify-between text-xs pt-2 border-t border-zinc-100 dark:border-zinc-800/60">
                            <template x-if="members['{{ $m->id }}']?.source === 'qr_self_check_in'">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-blue-600 dark:text-blue-400">
                                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="5" height="5" x="3" y="3" rx="1"/>
                                        <rect width="5" height="5" x="16" y="3" rx="1"/>
                                        <rect width="5" height="5" x="3" y="16" rx="1"/>
                                        <path d="M21 16h-3a2 2 0 0 0-2 2v3"/>
                                        <path d="M21 21v.01"/>
                                        <path d="M12 7v3a2 2 0 0 1-2 2H7"/>
                                        <path d="M3 12h.01"/>
                                        <path d="M12 3h.01"/>
                                        <path d="M12 16v.01"/>
                                        <path d="M16 12h1"/>
                                        <path d="M21 12v.01"/>
                                        <path d="M12 21v-1"/>
                                    </svg>
                                    <span>QR Self Check-In</span>
                                </span>
                            </template>
                            <template x-if="members['{{ $m->id }}']?.source === 'manual_correction'">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-purple-600 dark:text-purple-400">
                                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m18 5-3-3L6 11l-3 8 8-3 9-9z"/>
                                        <path d="m15 2 3 3"/>
                                    </svg>
                                    <span>Manual Correction</span>
                                </span>
                            </template>
                            <template x-if="members['{{ $m->id }}']?.source === 'officer_verification' && members['{{ $m->id }}']?.status">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-zinc-500">
                                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        <path d="m9 12 2 2 4-4"/>
                                    </svg>
                                    <span>Desk Roll-Call</span>
                                </span>
                            </template>
                            <template x-if="!members['{{ $m->id }}']?.source || !members['{{ $m->id }}']?.status">
                                <span class="text-[11px] text-zinc-400 italic">Pending check-in</span>
                            </template>

                            <template x-if="members['{{ $m->id }}']?.check_in_at || members['{{ $m->id }}']?.verified_at">
                                <span class="text-xs font-bold text-zinc-900 dark:text-white font-mono flex items-center gap-1.5">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-zinc-400"></i>
                                    <span x-text="members['{{ $m->id }}'].check_in_at || members['{{ $m->id }}'].verified_at"></span>
                                </span>
                            </template>
                        </div>

                        <!-- Roll-Call Buttons -->
                        <div class="grid grid-cols-3 gap-2 pt-1">
                            <button 
                                type="button" 
                                @click="markAttendance('{{ $m->id }}', 'present')" 
                                :disabled="members['{{ $m->id }}']?.is_updating"
                                :class="members['{{ $m->id }}']?.status === 'present' ? 'bg-emerald-600 text-white font-bold ring-2 ring-emerald-500/40' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300 font-semibold'"
                                class="py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all"
                            >
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Present</span>
                            </button>

                            <button 
                                type="button" 
                                @click="markAttendance('{{ $m->id }}', 'absent')" 
                                :disabled="members['{{ $m->id }}']?.is_updating"
                                :class="members['{{ $m->id }}']?.status === 'absent' ? 'bg-rose-600 text-white font-bold ring-2 ring-rose-500/40' : 'bg-rose-50 hover:bg-rose-100 text-rose-800 dark:bg-rose-950/30 dark:text-rose-300 font-semibold'"
                                class="py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all"
                            >
                                <i data-lucide="x" class="w-4 h-4"></i>
                                <span>Absent</span>
                            </button>

                            <button 
                                type="button" 
                                @click="openNoteModal('{{ $m->id }}', '{{ addslashes($m->name) }}', 'excused', members['{{ $m->id }}']?.officer_notes || '')" 
                                :disabled="members['{{ $m->id }}']?.is_updating"
                                :class="members['{{ $m->id }}']?.status === 'excused' ? 'bg-amber-600 text-white font-bold ring-2 ring-amber-500/40' : 'bg-amber-50 hover:bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:text-amber-300 font-semibold'"
                                class="py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all"
                            >
                                <i data-lucide="file-text" class="w-4 h-4"></i>
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

            <!-- Desktop View: Spacious Table (>= lg) -->
            <div class="hidden lg:block bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl overflow-hidden shadow-2xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/20 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                <th class="px-4 py-3.5">Member</th>
                                <th class="px-4 py-3.5">Check-In Source</th>
                                <th class="px-4 py-3.5">Official Verification</th>
                                <th class="px-4 py-3.5 text-center">Quick Roll-Call Actions</th>
                                <th class="px-4 py-3.5">Check-In Time & Audit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800 text-xs">
                            @forelse($members as $m)
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
                                                    <span>{{ $m->name }}</span>
                                                    <span class="text-[9px] font-black uppercase px-1.5 py-0.2 bg-amber-500/10 text-amber-700 dark:text-amber-400 rounded-md border border-amber-500/20">
                                                        {{ $m->traditional_title }}
                                                    </span>
                                                </div>
                                                <span class="text-[10px] text-zinc-400 font-mono">{{ $m->member_code }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Check-In Source -->
                                    <td class="px-4 py-3.5">
                                        <template x-if="members['{{ $m->id }}']?.source === 'qr_self_check_in'">
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50">
                                                <svg class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect width="5" height="5" x="3" y="3" rx="1"/>
                                                    <rect width="5" height="5" x="16" y="3" rx="1"/>
                                                    <rect width="5" height="5" x="3" y="16" rx="1"/>
                                                    <path d="M21 16h-3a2 2 0 0 0-2 2v3"/>
                                                    <path d="M21 21v.01"/>
                                                    <path d="M12 7v3a2 2 0 0 1-2 2H7"/>
                                                    <path d="M3 12h.01"/>
                                                    <path d="M12 3h.01"/>
                                                    <path d="M12 16v.01"/>
                                                    <path d="M16 12h1"/>
                                                    <path d="M21 12v.01"/>
                                                    <path d="M12 21v-1"/>
                                                </svg>
                                                <span>QR Self Check-In</span>
                                            </div>
                                        </template>
                                        <template x-if="members['{{ $m->id }}']?.source === 'manual_correction'">
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-50 dark:bg-purple-950/30 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-800/50">
                                                <svg class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="m18 5-3-3L6 11l-3 8 8-3 9-9z"/>
                                                    <path d="m15 2 3 3"/>
                                                </svg>
                                                <span>Manual Correction</span>
                                            </div>
                                        </template>
                                        <template x-if="members['{{ $m->id }}']?.source === 'officer_verification' && members['{{ $m->id }}']?.status">
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                                <svg class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                    <path d="m9 12 2 2 4-4"/>
                                                </svg>
                                                <span>Desk Roll-Call</span>
                                            </div>
                                        </template>
                                        <template x-if="!members['{{ $m->id }}']?.status">
                                            <span class="text-zinc-400 italic text-[11px]">Unverified</span>
                                        </template>
                                    </td>

                                    <!-- Official Status -->
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

                                            <!-- Apology / Reason Note Button -->
                                            <template x-if="members['{{ $m->id }}']?.officer_notes">
                                                <button 
                                                    type="button" 
                                                    @click="openViewNoteModal('{{ $m->id }}', '{{ addslashes($m->name) }}', '{{ $m->member_code }}', members['{{ $m->id }}']?.status, members['{{ $m->id }}']?.officer_notes, members['{{ $m->id }}']?.verifier_name, members['{{ $m->id }}']?.verified_at)"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60 transition-all cursor-pointer shadow-2xs"
                                                    title="View roll-call reason / note"
                                                >
                                                    <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Quick Actions -->
                                    <td class="px-4 py-3.5 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button 
                                                type="button" 
                                                @click="markAttendance('{{ $m->id }}', 'present')" 
                                                :disabled="members['{{ $m->id }}']?.is_updating"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                :class="members['{{ $m->id }}']?.status === 'present' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-zinc-100 hover:bg-emerald-100 hover:text-emerald-700 dark:bg-zinc-800 dark:hover:bg-emerald-950/60 dark:hover:text-emerald-300 text-zinc-600 dark:text-zinc-400'"
                                                title="Mark Present"
                                            >
                                                P
                                            </button>

                                            <button 
                                                type="button" 
                                                @click="markAttendance('{{ $m->id }}', 'absent')" 
                                                :disabled="members['{{ $m->id }}']?.is_updating"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                :class="members['{{ $m->id }}']?.status === 'absent' ? 'bg-rose-600 text-white shadow-xs' : 'bg-zinc-100 hover:bg-rose-100 hover:text-rose-700 dark:bg-zinc-800 dark:hover:bg-rose-950/60 dark:hover:text-rose-300 text-zinc-600 dark:text-zinc-400'"
                                                title="Mark Absent"
                                            >
                                                A
                                            </button>

                                            <button 
                                                type="button" 
                                                @click="openNoteModal('{{ $m->id }}', '{{ addslashes($m->name) }}', 'excused', members['{{ $m->id }}']?.officer_notes || '')" 
                                                :disabled="members['{{ $m->id }}']?.is_updating"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                :class="members['{{ $m->id }}']?.status === 'excused' ? 'bg-amber-600 text-white shadow-xs' : 'bg-zinc-100 hover:bg-amber-100 hover:text-amber-700 dark:bg-zinc-800 dark:hover:bg-amber-950/60 dark:hover:text-amber-300 text-zinc-600 dark:text-zinc-400'"
                                                title="Mark Excused with note"
                                            >
                                                E
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Check-In Time & Audit Info -->
                                    <td class="px-4 py-3.5">
                                        <template x-if="members['{{ $m->id }}']?.source === 'qr_self_check_in'">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-zinc-900 dark:text-white font-mono tracking-tight" x-text="members['{{ $m->id }}'].check_in_at || members['{{ $m->id }}'].verified_at"></span>
                                                <span class="text-[10px] font-semibold text-blue-600 dark:text-blue-400 flex items-center gap-1 mt-0.5">
                                                    <svg class="w-2.5 h-2.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <rect width="5" height="5" x="3" y="3" rx="1"/>
                                                        <rect width="5" height="5" x="16" y="3" rx="1"/>
                                                        <rect width="5" height="5" x="3" y="16" rx="1"/>
                                                        <path d="M21 16h-3a2 2 0 0 0-2 2v3"/>
                                                        <path d="M21 21v.01"/>
                                                        <path d="M12 7v3a2 2 0 0 1-2 2H7"/>
                                                        <path d="M3 12h.01"/>
                                                        <path d="M12 3h.01"/>
                                                        <path d="M12 16v.01"/>
                                                        <path d="M16 12h1"/>
                                                        <path d="M21 12v.01"/>
                                                        <path d="M12 21v-1"/>
                                                    </svg>
                                                    Self Check-In
                                                </span>
                                            </div>
                                        </template>
                                        <template x-if="members['{{ $m->id }}']?.source !== 'qr_self_check_in' && (members['{{ $m->id }}']?.verified_at || members['{{ $m->id }}']?.check_in_at)">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-zinc-900 dark:text-white font-mono tracking-tight" x-text="members['{{ $m->id }}'].check_in_at || members['{{ $m->id }}'].verified_at"></span>
                                                <span class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400 mt-0.5" x-text="'By ' + (members['{{ $m->id }}'].verifier_name || 'Officer Desk')"></span>
                                            </div>
                                        </template>
                                        <template x-if="!members['{{ $m->id }}']?.verified_at && !members['{{ $m->id }}']?.check_in_at">
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
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- TAB 2: GUEST ATTENDEES (Self-Contained Isolation)                   -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div x-show="activeTab === 'guests'" class="space-y-4">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl overflow-hidden shadow-2xs">
                <div class="p-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Registered Guest Attendees</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">Guests who self-registered via the session QR code.</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-teal-50 dark:bg-teal-950/40 text-teal-700 dark:text-teal-400 border border-teal-200 dark:border-teal-800/60">
                        {{ $guests->count() }} Guests Total
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/20 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                <th class="px-4 py-3.5">Guest Name</th>
                                <th class="px-4 py-3.5">Contact Details</th>
                                <th class="px-4 py-3.5">Invited By</th>
                                <th class="px-4 py-3.5">Registration Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800 text-xs">
                            @forelse($guests as $guest)
                                <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-4 py-3.5 font-bold text-zinc-900 dark:text-white">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-teal-100 dark:bg-teal-950/60 text-teal-700 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                                                {{ substr($guest->guest_name, 0, 1) }}
                                            </div>
                                            <span>{{ $guest->guest_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-zinc-600 dark:text-zinc-400">
                                        <div>{{ $guest->guest_phone ?: '—' }}</div>
                                        <div class="text-[11px] text-zinc-400">{{ $guest->guest_email }}</div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        @if($guest->invitedByMember)
                                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                                                {{ $guest->invitedByMember->name }}
                                            </span>
                                            <span class="text-[10px] text-zinc-400 font-mono">({{ $guest->invitedByMember->member_code }})</span>
                                        @else
                                            <span class="text-zinc-400 italic">{{ $guest->invited_by_name ?: 'General Invitation' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-xs font-bold text-zinc-900 dark:text-white">
                                        {{ $guest->check_in_at ? $guest->check_in_at->setTimezone($selectedSession->check_in_timezone)->format('M d, g:i A') : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-zinc-400 text-xs">
                                        No guests have registered for this session yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 1: MEETING QR CODE PROJECTION MODAL                           -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    @if($selectedSession)
        <div 
            x-show="qrModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm flex items-center justify-center p-4"
        >
            <div 
                @click.outside="qrModalOpen = false" 
                class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 text-center relative animate-fadeIn"
            >
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="text-left">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Live Meeting Roll-Call</span>
                        <h3 class="text-base font-extrabold text-zinc-900 dark:text-white">{{ $selectedSession->title }}</h3>
                    </div>
                    <button @click="qrModalOpen = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1.5 rounded-xl">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- High-Resolution QR Vector Code Container -->
                <div class="my-6 p-6 rounded-2xl bg-white border border-zinc-200 shadow-inner flex flex-col items-center justify-center max-w-[340px] mx-auto">
                    <div class="w-64 h-64 flex items-center justify-center">
                        {!! $qrSvg !!}
                    </div>
                    <p class="text-xs font-bold text-zinc-600 mt-3">Scan with Phone Camera to Check In</p>
                    <p class="text-[11px] text-zinc-400 mt-0.5">Supports both Member Roll-Call & Guest Registration</p>
                </div>

                <!-- Direct Link & Copy Action -->
                <div class="flex items-center gap-2 p-2 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700/80 mb-6">
                    <input 
                        type="text" 
                        readonly 
                        value="{{ $checkInUrl }}" 
                        class="w-full bg-transparent text-xs font-mono text-zinc-700 dark:text-zinc-300 px-2 outline-none select-all truncate"
                    >
                    <button 
                        type="button" 
                        @click="copyCheckInUrl('{{ $checkInUrl }}')" 
                        class="px-3 py-1.5 rounded-lg bg-brand-primary hover:opacity-95 text-brand-primary-text text-xs font-bold shrink-0 transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <i data-lucide="copy" class="w-3.5 h-3.5" x-show="!copiedLink"></i>
                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500" x-show="copiedLink" x-cloak></i>
                        <span x-text="copiedLink ? 'Copied!' : 'Copy Link'"></span>
                    </button>
                </div>

                <!-- Action Footer -->
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <a 
                        href="{{ route('attendance.session.qr', $selectedSession->id) }}" 
                        download="session-{{ $selectedSession->id }}-qr.png" 
                        class="py-2.5 px-4 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 flex items-center justify-center gap-2 transition-all"
                    >
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Download PNG</span>
                    </a>
                    <button 
                        type="button" 
                        @click="qrModalOpen = false" 
                        class="py-2.5 px-4 rounded-xl bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-xs font-bold hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all"
                    >
                        Close Desk View
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- MODAL 2: CHECK-IN TIME WINDOW CONFIGURATION                         -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div 
            x-show="windowModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div 
                @click.outside="windowModalOpen = false" 
                class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl shadow-2xl max-w-md w-full p-6 relative animate-fadeIn"
            >
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Check-In Window Settings</h3>
                            <p class="text-[11px] text-zinc-400">Configure opening and closing cutoff hours</p>
                        </div>
                    </div>
                    <button @click="windowModalOpen = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1.5 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form action="{{ route('attendance.session.window', $selectedSession->id) }}" method="POST" class="mt-4 space-y-4">
                    @csrf

                    <x-premium-timepicker 
                        label="Check-In Opens At"
                        name="check_in_opens_at"
                        value="{{ substr($selectedSession->check_in_opens_at ?: '17:00:00', 0, 5) }}"
                        helperText="Default: 5:00 PM (17:00)"
                        required
                    />

                    <x-premium-timepicker 
                        label="Check-In Closes At (Cutoff)"
                        name="check_in_closes_at"
                        value="{{ substr($selectedSession->check_in_closes_at ?: '21:00:00', 0, 5) }}"
                        helperText="Default: 9:00 PM (21:00). After this time, scans are rejected."
                        required
                    />

                    <!-- Timezone with existing x-dropdown component -->
                    <div class="flex flex-col gap-1.5" x-data="{
                        timezone: '{{ $selectedSession->check_in_timezone ?: 'America/New_York' }}',
                        timezoneLabels: {
                            'Asia/Dhaka': 'Asia/Dhaka (Bangladesh Time)',
                            'America/New_York': 'America/New_York (Eastern Time)',
                            'America/Chicago': 'America/Chicago (Central Time)',
                            'America/Los_Angeles': 'America/Los_Angeles (Pacific Time)',
                            'UTC': 'UTC'
                        }
                    }">
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Timezone
                        </label>
                        <input type="hidden" name="check_in_timezone" :value="timezone">

                        <x-dropdown align="left" width="w-full" contentClasses="p-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl flex flex-col gap-0.5">
                            <x-slot name="trigger">
                                <button 
                                    type="button" 
                                    class="w-full h-11 flex items-center justify-between bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/80 text-zinc-900 dark:text-white px-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold focus:outline-none focus:border-brand-primary transition-all cursor-pointer text-left select-none shadow-2xs"
                                >
                                    <span class="truncate font-bold" x-text="timezoneLabels[timezone] || 'Select Timezone'"></span>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0"></i>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <template x-for="(label, val) in timezoneLabels" :key="val">
                                    <button 
                                        type="button" 
                                        @click="timezone = val" 
                                        class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium rounded-lg transition-colors cursor-pointer text-left select-none"
                                        :class="timezone === val ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white font-bold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 hover:text-zinc-950 dark:hover:text-white'"
                                    >
                                        <span x-text="label"></span>
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-brand-primary" x-show="timezone === val"></i>
                                    </button>
                                </template>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <button 
                            type="button" 
                            @click="windowModalOpen = false" 
                            class="py-2 px-3 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="py-2 px-4 text-xs font-bold rounded-xl bg-brand-primary text-brand-primary-text hover:opacity-95 shadow-xs"
                        >
                            Save Window Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 3: OFFICER NOTE & AUDITED REASON MODAL                        -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
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
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white" x-text="'Roll-Call Verification: ' + selectedMemberName"></h3>
                    <p class="text-xs text-zinc-500">Record verification notes or reason for manual change.</p>
                </div>
                <button @click="noteModalOpen = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
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
                                @click="officerNotes = preset; reasonNote = preset"
                                class="px-2.5 py-1 bg-zinc-100 hover:bg-zinc-200/80 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-[11px] font-semibold rounded-lg transition-colors cursor-pointer"
                                x-text="preset"
                            ></button>
                        </template>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                        Officer Notes / Reason <span class="text-rose-500">*</span>
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
                        @click="markAttendance(selectedMemberId, selectedStatus, officerNotes, officerNotes)"
                        class="py-2.5 px-4 text-xs font-bold rounded-xl bg-brand-primary hover:opacity-95 text-brand-primary-text transition-colors cursor-pointer shadow-xs"
                    >
                        Save Verification
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- MODAL 4: VIEW NOTE MODAL                                            -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
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
                    <span class="p-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <i data-lucide="message-square" class="w-4 h-4"></i>
                    </span>
                    <div class="flex flex-col">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white" x-text="viewNoteTitle"></h3>
                        <p class="text-[11px] text-zinc-400 font-medium" x-text="viewNoteSubtitle"></p>
                    </div>
                </div>
                <button @click="viewNoteModalOpen = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1 rounded-lg">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="mt-4 flex flex-col gap-3.5">
                <div class="flex items-center justify-between bg-zinc-50 dark:bg-zinc-950/40 p-2.5 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-zinc-900 dark:text-white" x-text="viewNoteMemberName"></span>
                        <span class="text-[10px] text-zinc-400 font-mono" x-text="viewNoteMemberCode"></span>
                    </div>
                    <span 
                        class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                        :class="viewNoteStatus === 'present' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : (viewNoteStatus === 'absent' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400' : 'bg-amber-500/10 text-amber-700 dark:text-amber-400')"
                        x-text="viewNoteStatus"
                    ></span>
                </div>

                <div 
                    class="rounded-xl p-3.5 text-xs leading-relaxed whitespace-pre-wrap break-words italic border bg-amber-50/40 dark:bg-amber-950/20 border-amber-200/60 dark:border-amber-800/40 text-zinc-800 dark:text-zinc-200"
                    x-text="'&ldquo;' + viewNoteContent + '&rdquo;'"
                ></div>

                <div class="flex items-center justify-between text-[11px] text-zinc-400 pt-1">
                    <span x-text="'Recorded by: ' + viewNoteVerifier"></span>
                    <span class="font-mono" x-text="viewNoteDate"></span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 mt-2 border-t border-zinc-100 dark:border-zinc-800">
                <button 
                    type="button" 
                    @click="viewNoteModalOpen = false; openNoteModal(viewNoteMemberId, viewNoteMemberName, viewNoteStatus, viewNoteContent)" 
                    class="py-2 px-3 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors"
                >
                    Edit Note
                </button>
                <button 
                    type="button" 
                    @click="viewNoteModalOpen = false" 
                    class="py-2 px-4 text-xs font-bold rounded-xl bg-brand-primary text-brand-primary-text hover:opacity-95 shadow-xs"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Live Toast -->
    <div 
        x-show="toast.show" 
        x-cloak 
        class="fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-xl text-xs font-bold select-none"
        :class="toast.type === 'success' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-950' : 'bg-rose-600 text-white'"
    >
        <i data-lucide="check" class="w-4 h-4"></i>
        <span x-text="toast.message"></span>
    </div>

</div>
@endsection
