@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6 w-full relative">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2.5">
                <span class="p-2 bg-brand-subtle rounded-xl text-brand-primary shrink-0">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                </span>
                <h1 class="text-2xl font-bold text-zinc-950 dark:text-white tracking-tight font-display">
                    My Meeting Attendance & Check-In
                </h1>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                View your official verified meeting attendance history and access live session check-in.
            </p>
        </div>

        @if($upcomingSession)
            <a 
                href="{{ route('attendance.checkin.show', $upcomingSession->ensureQrToken()) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary hover:bg-brand-primary text-brand-primary-text text-xs font-bold rounded-xl transition-all cursor-pointer shadow-xs select-none self-start sm:self-auto"
            >
                <i data-lucide="qr-code" class="w-4 h-4"></i>
                <span>Open Meeting Check-In</span>
            </a>
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

        <!-- Upcoming Session Card -->
        <div class="md:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 flex flex-col justify-between gap-4 shadow-xs">
            @if($upcomingSession)
                @php
                    $isWindowOpen = $upcomingSession->isCheckInOpen();
                    $windowInfo = $upcomingSession->checkInWindowStatus();
                @endphp
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-primary">Next Meeting & Assembly</span>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white font-display">
                            {{ $upcomingSession->title ?? 'Session #' . $upcomingSession->session_number }}
                        </h3>
                        <p class="text-xs text-zinc-500 flex items-center gap-1.5 mt-0.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-zinc-400"></i>
                            <span>{{ $upcomingSession->session_date ? $upcomingSession->session_date->format('l, F d, Y') : 'Date to be confirmed' }}</span>
                        </p>
                    </div>

                    <!-- Live Check-in Status Pill -->
                    <div>
                        @if($upcomingAttendance && $upcomingAttendance->attendance_status === 'present')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-500"></i>
                                Checked In: Present
                            </span>
                        @elseif($isWindowOpen)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20">
                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                Check-In Open Now
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-500">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                Opens at {{ $windowInfo['opens_at']->format('g:i A T') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Check-in window: <strong>{{ $windowInfo['opens_at']->format('g:i A') }} to {{ $windowInfo['closes_at']->format('g:i A T') }}</strong> on meeting day.
                    </p>
                    <a 
                        href="{{ route('attendance.checkin.show', $upcomingSession->ensureQrToken()) }}" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0 flex items-center gap-2"
                    >
                        <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                        <span>{{ $upcomingAttendance && $upcomingAttendance->attendance_status === 'present' ? 'View Check-In Pass' : 'Check In to Meeting' }}</span>
                    </a>
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
                        <th class="px-5 py-3.5">Check-In Source</th>
                        <th class="px-5 py-3.5">Official Verification</th>
                        <th class="px-5 py-3.5">Officer Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800 text-xs">
                    @forelse($sessions as $session)
                        @php
                            $att = $session->attendances->first();
                            $status = $att?->attendance_status;
                            $source = $att?->check_in_source;
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
                                @if($source === 'qr_self_check_in')
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
                                        QR Self Check-In
                                    </span>
                                @elseif($source === 'manual_correction')
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-purple-600 dark:text-purple-400">
                                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m18 5-3-3L6 11l-3 8 8-3 9-9z"/>
                                            <path d="m15 2 3 3"/>
                                        </svg>
                                        Manual Correction
                                    </span>
                                @elseif($status)
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-zinc-600 dark:text-zinc-400">
                                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                            <path d="m9 12 2 2 4-4"/>
                                        </svg>
                                        Desk Roll-Call
                                    </span>
                                @else
                                    <span class="text-[11px] text-zinc-400 italic">—</span>
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

</div>
@endsection
