@extends('layouts.app')

@section('content')
<div x-data="{ rsvpModalOpen: false }" class="flex flex-col gap-6 w-full relative">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2.5">
                <span class="p-2 bg-brand-subtle rounded-xl text-brand-primary shrink-0">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                </span>
                <h1 class="text-2xl font-bold text-zinc-950 dark:text-white tracking-tight font-display">
                    My Meeting Attendance & RSVP
                </h1>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Manage your advance session RSVPs and view your official verified meeting participation record.
            </p>
        </div>

        @if($upcomingSession)
            <button 
                @click="rsvpModalOpen = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary hover:bg-brand-primary text-brand-primary-text text-xs font-bold rounded-xl transition-all cursor-pointer shadow-xs select-none self-start sm:self-auto"
            >
                <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                <span>RSVP for {{ $upcomingSession->title ?? 'Upcoming Session' }}</span>
            </button>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-4 flex items-center gap-3 animate-fadeIn">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
            <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-300">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800 rounded-2xl p-4 flex items-center gap-3 animate-fadeIn">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0"></i>
            <p class="text-xs font-semibold text-rose-800 dark:text-rose-300">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Attendance Performance & Upcoming Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Attendance Rate Card -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between gap-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Attendance Score</span>
                <span class="p-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl">
                    <i data-lucide="award" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                @if($totalVerified === 0)
                    <span class="text-3xl font-black font-display text-zinc-400">0%</span>
                    <span class="text-xs text-zinc-400 font-medium">No verified sessions yet</span>
                @else
                    <span class="text-3xl font-black font-display text-zinc-900 dark:text-white">{{ $attendanceRate }}%</span>
                    <span class="text-xs text-zinc-400 font-medium">Verified presence</span>
                @endif
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $totalVerified === 0 ? 0 : min(100, $attendanceRate) }}%"></div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-zinc-500 dark:text-zinc-400">
                <span>{{ $presentCount }} Present</span>
                <span>{{ $excusedCount }} Excused</span>
                <span>{{ $absentCount }} Absent</span>
            </div>
        </div>

        <!-- Upcoming Session & RSVP Status -->
        <div class="md:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between gap-4 shadow-xs">
            @if($upcomingSession)
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-primary">Next Assembly & Meeting</span>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white font-display">
                            {{ $upcomingSession->title ?? 'Session #' . $upcomingSession->session_number }}
                        </h3>
                        <p class="text-xs text-zinc-500 flex items-center gap-1.5 mt-0.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-zinc-400"></i>
                            <span>{{ $upcomingSession->session_date ? $upcomingSession->session_date->format('l, F d, Y') : 'Date to be confirmed' }}</span>
                        </p>
                    </div>

                    <!-- Current RSVP Pill -->
                    <div>
                        @if($upcomingAttendance && $upcomingAttendance->rsvp_status === 'attending')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20">
                                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                                You RSVP'd: Attending
                            </span>
                        @elseif($upcomingAttendance && $upcomingAttendance->rsvp_status === 'not_attending')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                                <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                                You RSVP'd: Not Attending
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                RSVP Pending
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        @if($upcomingAttendance && $upcomingAttendance->rsvp_at)
                            Marked on {{ $upcomingAttendance->rsvp_at->format('M d, Y') }}. You may update your attendance response at any time before roll-call.
                        @else
                            Please let the executive officers know if you will be present to help arrange seating and refreshments.
                        @endif
                    </p>
                    <button 
                        @click="rsvpModalOpen = true" 
                        class="px-4 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-white rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0"
                    >
                        {{ $upcomingAttendance && $upcomingAttendance->rsvp_status !== 'undecided' ? 'Change RSVP' : 'Submit RSVP Now' }}
                    </button>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-6 text-center">
                    <i data-lucide="calendar" class="w-8 h-8 text-zinc-400 mb-2"></i>
                    <h4 class="text-sm font-bold text-zinc-700 dark:text-zinc-300">No Upcoming Meeting Scheduled</h4>
                    <p class="text-xs text-zinc-400 mt-1">All scheduled sessions for the current cycle are closed or pending announcement.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Attendance History Table -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4 text-zinc-400"></i>
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Meeting Attendance History</h3>
            </div>
            <span class="text-xs text-zinc-400">{{ $totalVerified }} verified sessions</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/20 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                        <th class="px-5 py-3.5">Meeting Session</th>
                        <th class="px-5 py-3.5">Date</th>
                        <th class="px-5 py-3.5">My Advance RSVP</th>
                        <th class="px-5 py-3.5">Official Verification</th>
                        <th class="px-5 py-3.5">Officer Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800 text-xs">
                    @forelse($sessions as $session)
                        @php
                            $att = $session->attendances->first();
                            $status = $att?->attendance_status;
                            $rsvp = $att?->rsvp_status ?? 'undecided';
                        @endphp
                        <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-zinc-900 dark:text-white">
                                {{ $session->title ?? 'Session #' . $session->session_number }}
                                @if($session->cycle)
                                    <span class="text-[10px] text-zinc-400 font-normal block">{{ $session->cycle->name }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-zinc-600 dark:text-zinc-300 font-mono text-[11px]">
                                {{ $session->session_date ? $session->session_date->format('M d, Y') : '-' }}
                            </td>
                            <td class="px-5 py-3.5">
                                @if($rsvp === 'attending')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 dark:text-blue-400">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        Attending
                                    </span>
                                @elseif($rsvp === 'not_attending')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 dark:text-rose-400">
                                        <i data-lucide="x" class="w-3 h-3"></i>
                                        Not Attending
                                    </span>
                                @else
                                    <span class="text-[11px] text-zinc-400 italic">No RSVP</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if($status === 'present')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Present
                                    </span>
                                @elseif($status === 'absent')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Absent
                                    </span>
                                @elseif($status === 'excused')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Excused
                                    </span>
                                @else
                                    <span class="text-[11px] text-zinc-400 italic">Unrecorded</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-zinc-500 text-[11px]">
                                {{ $att?->officer_notes ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-zinc-400 text-xs">
                                No meeting sessions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sessions->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>

    <!-- Standardized RSVP Modal (max-w-lg) -->
    @if($upcomingSession)
        <div 
            x-show="rsvpModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div 
                @click.outside="rsvpModalOpen = false" 
                class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 relative animate-fadeIn"
            >
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex flex-col">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                            RSVP: {{ $upcomingSession->title ?? 'Upcoming Session' }}
                        </h3>
                        <p class="text-xs text-zinc-500">
                            {{ $upcomingSession->session_date ? $upcomingSession->session_date->format('l, F d, Y') : '' }}
                        </p>
                    </div>
                    <button @click="rsvpModalOpen = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1 rounded-lg">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('member.attendance.rsvp') }}" class="mt-4 flex flex-col gap-4">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $upcomingSession->id }}">

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                            Will you attend this meeting?
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2 p-3 border rounded-xl cursor-pointer transition-colors border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 has-checked:border-brand-primary has-checked:bg-brand-subtle">
                                <input 
                                    type="radio" 
                                    name="rsvp_status" 
                                    value="attending" 
                                    class="text-brand-primary focus:ring-brand-primary"
                                    {{ $upcomingAttendance && $upcomingAttendance->rsvp_status === 'attending' ? 'checked' : '' }}
                                    required
                                >
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-zinc-900 dark:text-white">Yes, I will attend</span>
                                    <span class="text-[10px] text-zinc-500">Plan to be present</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-3 border rounded-xl cursor-pointer transition-colors border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 has-checked:border-rose-500 has-checked:bg-rose-500/10">
                                <input 
                                    type="radio" 
                                    name="rsvp_status" 
                                    value="not_attending" 
                                    class="text-rose-600 focus:ring-rose-500"
                                    {{ $upcomingAttendance && $upcomingAttendance->rsvp_status === 'not_attending' ? 'checked' : '' }}
                                    required
                                >
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-zinc-900 dark:text-white">No, I will be absent</span>
                                    <span class="text-[10px] text-zinc-500">Cannot make it</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                            Optional Note / Apology
                        </label>
                        <textarea 
                            name="rsvp_notes" 
                            rows="2" 
                            placeholder="e.g. Will arrive 15 minutes late, or traveling out of town..." 
                            class="w-full px-3.5 py-2.5 text-xs font-semibold bg-zinc-50/50 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:border-brand-primary"
                        >{{ $upcomingAttendance->rsvp_notes ?? '' }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        <button 
                            type="button" 
                            @click="rsvpModalOpen = false" 
                            class="py-2.5 px-4 text-xs font-bold rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="py-2.5 px-4 text-xs font-bold rounded-xl bg-brand-primary hover:bg-brand-primary text-brand-primary-text transition-colors cursor-pointer shadow-xs"
                        >
                            Save Response
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
