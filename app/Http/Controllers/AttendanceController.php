<?php

namespace App\Http\Controllers;

use App\Models\AttendanceAuditLog;
use App\Models\MeetingAttendance;
use App\Models\MeetingGuest;
use App\Models\Member;
use App\Models\NjangiSession;
use App\Models\Setting;
use App\Services\QrCodeService;
use App\Support\MemberResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    protected QrCodeService $qrService;

    public function __construct(QrCodeService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Authorize officer roll-call desk access.
     */
    private function authorizeDeskAccess(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $member = MemberResolver::fromUser($user);
        $canMark = $user->hasRole('admin')
            || ($user->can('attendance.mark') ?? false)
            || $user->hasRole('General Secretary')
            || ($member && $member->roles()->whereIn('name', ['Secretary', 'Admin'])->exists());

        if (!$canMark) {
            abort(403, 'Unauthorized action. You do not have permission to access the Officer Attendance Desk.');
        }
    }

    /**
     * Officer Attendance Desk (Live Roll-Call Interface)
     */
    public function desk(Request $request)
    {
        $this->authorizeDeskAccess();

        // Fetch all sessions ordered chronologically
        $sessions = NjangiSession::with('cycle')
            ->orderBy('session_date', 'asc')
            ->get();

        // Selected session (defaults to current open session, or next scheduled, or latest)
        $sessionId = $request->input('session_id');
        $selectedSession = null;
        if ($sessionId) {
            $selectedSession = $sessions->firstWhere('id', $sessionId);
        }

        if (!$selectedSession) {
            $selectedSession = $sessions->firstWhere('status', 'open')
                ?? $sessions->where('status', 'scheduled')->sortBy('session_date')->first()
                ?? $sessions->first();
        }

        $members = collect();
        $guests = collect();
        $stats = [
            'total_members' => 0,
            'verified_present' => 0,
            'qr_checkins' => 0,
            'officer_verified' => 0,
            'verified_absent' => 0,
            'verified_excused' => 0,
            'unverified' => 0,
            'total_guests' => 0,
        ];

        $qrSvg = null;
        $checkInUrl = null;
        $windowStatus = null;

        $appSettings = Setting::first();

        if ($selectedSession) {
            $selectedSession->ensureQrToken();
            $checkInUrl = route('attendance.checkin.show', ['token' => $selectedSession->qr_token]);
            $qrSvg = $this->qrService->generateSvg($checkInUrl, 320, 2);
            $windowStatus = $selectedSession->checkInWindowStatus();

            $query = Member::where('organization_id', $selectedSession->organization_id)
                ->where('status', 'active')
                ->with(['attendances' => function ($q) use ($selectedSession) {
                    $q->where('njangi_session_id', $selectedSession->id)->with('verifier');
                }, 'rank']);

            if ($appSettings?->restrict_checkin_to_cycle_members && $selectedSession->njangi_cycle_id) {
                $enrolledMemberIds = DB::table('njangi_cycle_members')
                    ->where('njangi_cycle_id', $selectedSession->njangi_cycle_id)
                    ->where('status', 'active')
                    ->pluck('member_id');
                $query->whereIn('id', $enrolledMemberIds);
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('member_code', 'like', "%{$search}%");
                });
            }

            $allOrgMembers = $query->orderBy('first_name')->get();

            // Populate attendance rows and calculate stats
            $members = $allOrgMembers->map(function ($m) use ($selectedSession) {
                $attendance = $m->attendances->first();
                $m->current_attendance = $attendance;
                return $m;
            });

            // Calculate overall session stats
            $allSessionAttendances = MeetingAttendance::where('njangi_session_id', $selectedSession->id)->get();
            $totalActiveMembers = ($appSettings?->restrict_checkin_to_cycle_members && $selectedSession->njangi_cycle_id)
                ? DB::table('njangi_cycle_members')->where('njangi_cycle_id', $selectedSession->njangi_cycle_id)->where('status', 'active')->count()
                : Member::where('organization_id', $selectedSession->organization_id)->where('status', 'active')->count();

            $stats['total_members'] = $totalActiveMembers;
            $stats['verified_present'] = $allSessionAttendances->where('attendance_status', 'present')->count();
            $stats['qr_checkins'] = $allSessionAttendances->where('attendance_status', 'present')->where('check_in_source', 'qr_self_check_in')->count();
            $stats['officer_verified'] = $allSessionAttendances->where('attendance_status', 'present')->where('check_in_source', '!=', 'qr_self_check_in')->count();
            $stats['verified_absent'] = $allSessionAttendances->where('attendance_status', 'absent')->count();
            $stats['verified_excused'] = $allSessionAttendances->where('attendance_status', 'excused')->count();
            $stats['unverified'] = max(0, $totalActiveMembers - ($stats['verified_present'] + $stats['verified_absent'] + $stats['verified_excused']));

            // Guests for this session
            $guests = MeetingGuest::where('njangi_session_id', $selectedSession->id)
                ->with('invitedByMember')
                ->orderBy('check_in_at', 'desc')
                ->get();

            $stats['total_guests'] = $guests->count();

            // Filter by Attendance status if selected
            if ($request->filled('attendance_filter')) {
                $attFilter = $request->attendance_filter;
                $members = $members->filter(function ($m) use ($attFilter) {
                    $status = $m->current_attendance?->attendance_status;
                    if ($attFilter === 'unverified') {
                        return is_null($status);
                    }
                    if ($attFilter === 'qr_checkin') {
                        return $status === 'present' && $m->current_attendance?->check_in_source === 'qr_self_check_in';
                    }
                    return $status === $attFilter;
                });
            }
        }

        $appSettings = Setting::first();

        return view('attendance.desk', compact(
            'sessions',
            'selectedSession',
            'members',
            'guests',
            'stats',
            'qrSvg',
            'checkInUrl',
            'windowStatus',
            'appSettings'
        ));
    }

    /**
     * Mark single member official attendance (Officer Roll-call or Manual Correction)
     */
    public function mark(Request $request)
    {
        $this->authorizeDeskAccess();

        $request->validate([
            'session_id' => 'required|exists:njangi_sessions,id',
            'member_id' => 'required|exists:members,id',
            'status' => 'required|in:present,absent,excused',
            'officer_notes' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:500',
        ]);

        $session = NjangiSession::findOrFail($request->session_id);
        $member = Member::findOrFail($request->member_id);

        $attendance = MeetingAttendance::firstOrNew([
            'njangi_session_id' => $session->id,
            'member_id' => $member->id,
        ]);

        $previousStatus = $attendance->exists ? $attendance->attendance_status : null;
        $isExisting = $attendance->exists && !empty($attendance->attendance_status);

        $attendance->organization_id = $session->organization_id;
        $attendance->attendance_status = $request->status;
        $attendance->verified_by = Auth::id();
        $attendance->verified_at = now();

        // Source classification
        if ($isExisting && $previousStatus !== $request->status) {
            $attendance->check_in_source = 'manual_correction';
        } elseif (!$attendance->check_in_source) {
            $attendance->check_in_source = 'officer_verification';
        }

        if ($request->status === 'present' && !$attendance->check_in_at) {
            $attendance->check_in_at = now();
        }

        if ($request->filled('officer_notes')) {
            $attendance->officer_notes = $request->officer_notes;
        }
        $attendance->save();

        // Record audit trail log if it's a correction or manual change
        AttendanceAuditLog::create([
            'organization_id' => $session->organization_id,
            'njangi_session_id' => $session->id,
            'member_id' => $member->id,
            'changed_by' => Auth::id(),
            'action' => $isExisting ? 'manual_correction' : 'officer_verification',
            'previous_status' => $previousStatus,
            'new_status' => $request->status,
            'source' => $attendance->check_in_source,
            'reason' => $request->reason ?? $request->officer_notes ?? ($isExisting ? 'Manual status correction by officer' : 'Desk roll-call verification'),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            $allSessionAttendances = MeetingAttendance::where('njangi_session_id', $session->id)->get();
            $totalActiveMembers = Member::where('organization_id', $session->organization_id)->where('status', 'active')->count();

            $verifiedPresent = $allSessionAttendances->where('attendance_status', 'present')->count();
            $qrCheckins = $allSessionAttendances->where('attendance_status', 'present')->where('check_in_source', 'qr_self_check_in')->count();
            $officerVerified = $allSessionAttendances->where('attendance_status', 'present')->where('check_in_source', '!=', 'qr_self_check_in')->count();
            $verifiedAbsent = $allSessionAttendances->where('attendance_status', 'absent')->count();
            $verifiedExcused = $allSessionAttendances->where('attendance_status', 'excused')->count();
            $unverified = max(0, $totalActiveMembers - ($verifiedPresent + $verifiedAbsent + $verifiedExcused));

            return response()->json([
                'success' => true,
                'message' => "Attendance for {$member->first_name} {$member->last_name} marked as " . ucfirst($request->status) . ".",
                'attendance' => [
                    'id' => $attendance->id,
                    'status' => $attendance->attendance_status,
                    'check_in_source' => $attendance->check_in_source,
                    'officer_notes' => $attendance->officer_notes,
                    'verified_at' => $attendance->verified_at ? ($session->check_in_timezone ? $attendance->verified_at->setTimezone($session->check_in_timezone)->format('M d, g:i A') : $attendance->verified_at->format('M d, g:i A')) : null,
                    'check_in_at' => $attendance->check_in_at ? ($session->check_in_timezone ? $attendance->check_in_at->setTimezone($session->check_in_timezone)->format('M d, g:i A') : $attendance->check_in_at->format('M d, g:i A')) : null,
                ],
                'stats' => [
                    'verified_present' => $verifiedPresent,
                    'qr_checkins' => $qrCheckins,
                    'officer_verified' => $officerVerified,
                    'verified_absent' => $verifiedAbsent,
                    'verified_excused' => $verifiedExcused,
                    'unverified' => $unverified,
                ]
            ]);
        }

        return redirect()->back()->with('success', "Attendance for {$member->name} marked as " . ucfirst($request->status) . ".");
    }

    /**
     * Update Session Check-in Window Configuration (Special Meetings / Hours)
     */
    public function updateCheckInWindow(Request $request, NjangiSession $session)
    {
        $this->authorizeDeskAccess();

        $request->validate([
            'check_in_opens_at' => 'required',
            'check_in_closes_at' => 'required',
            'check_in_timezone' => 'required|string',
        ]);

        $opensAt = Carbon::parse($request->check_in_opens_at)->format('H:i:s');
        $closesAt = Carbon::parse($request->check_in_closes_at)->format('H:i:s');

        $session->update([
            'check_in_opens_at' => $opensAt,
            'check_in_closes_at' => $closesAt,
            'check_in_timezone' => $request->check_in_timezone,
        ]);

        return redirect()->back()->with('success', "Check-in window updated: {$session->check_in_opens_at} to {$session->check_in_closes_at} ({$session->check_in_timezone}).");
    }

    /**
     * Show Public/Member Check-In Landing Page via Scanned QR Token
     */
    public function showCheckIn(string $token)
    {
        $session = NjangiSession::where('qr_token', $token)->first();
        if (!$session) {
            abort(404, 'Invalid check-in QR code or meeting session not found.');
        }

        $windowStatus = $session->checkInWindowStatus();

        // Authenticated member details (if logged in)
        $member = null;
        $existingAttendance = null;
        $isAlreadyCheckedIn = false;

        if (Auth::check()) {
            $member = MemberResolver::fromUser(Auth::user());
            if ($member) {
                $existingAttendance = MeetingAttendance::where('njangi_session_id', $session->id)
                    ->where('member_id', $member->id)
                    ->first();

                if ($existingAttendance && $existingAttendance->attendance_status === 'present') {
                    $isAlreadyCheckedIn = true;
                }
            }
        } else {
            // Remember check-in URL as intended destination after login
            session(['url.intended' => route('attendance.checkin.show', $token)]);
        }

        // Active members roster for the "Who invited you?" guest dropdown
        $activeMembers = Member::where('organization_id', $session->organization_id)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'member_code']);

        $appSettings = Setting::first();
        $isEnrolledInCycle = true;
        if ($appSettings?->restrict_checkin_to_cycle_members && $session->njangi_cycle_id && $member) {
            $isEnrolledInCycle = DB::table('njangi_cycle_members')
                ->where('njangi_cycle_id', $session->njangi_cycle_id)
                ->where('member_id', $member->id)
                ->where('status', 'active')
                ->exists();
        }

        return view('attendance.checkin', compact(
            'session',
            'windowStatus',
            'member',
            'existingAttendance',
            'isAlreadyCheckedIn',
            'isEnrolledInCycle',
            'activeMembers',
            'appSettings'
        ));
    }

    /**
     * Process Member QR Self-Check-In
     */
    public function submitMemberCheckIn(Request $request, string $token)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('info', 'Please sign in to check in to the meeting.');
        }

        $session = NjangiSession::where('qr_token', $token)->firstOrFail();
        $member = MemberResolver::fromUser($user);

        if (!$member) {
            abort(403, 'Your account is not linked to an active member profile. Please contact an officer.');
        }

        $appSettings = Setting::first();
        if ($appSettings?->restrict_checkin_to_cycle_members && $session->njangi_cycle_id) {
            $isEnrolled = DB::table('njangi_cycle_members')
                ->where('njangi_cycle_id', $session->njangi_cycle_id)
                ->where('member_id', $member->id)
                ->where('status', 'active')
                ->exists();

            if (!$isEnrolled) {
                return redirect()->back()->with('error', "Check-in is restricted to active participants of this cycle. You are not enrolled in this session's Njangi cycle.");
            }
        }

        // Enforce time window
        $windowStatus = $session->checkInWindowStatus();
        if (!$windowStatus['is_open']) {
            $cutoff = $windowStatus['closes_at']->format('g:i A T');
            $start = $windowStatus['opens_at']->format('g:i A T');
            if ($windowStatus['status'] === 'not_started') {
                return redirect()->back()->with('error', "Check-in has not opened yet. Check-in opens at {$start}.");
            }
            return redirect()->back()->with('error', "Check-in is currently closed. The check-in window closed at {$cutoff}. Please see the desk officer for manual entry.");
        }

        // Check for duplicate check-in
        $existing = MeetingAttendance::where('njangi_session_id', $session->id)
            ->where('member_id', $member->id)
            ->first();

        if ($existing && $existing->attendance_status === 'present') {
            $time = $existing->check_in_at ? $existing->check_in_at->format('g:i A') : $existing->updated_at->format('g:i A');
            return redirect()->back()->with('info', "You are already checked in as Present for this session (Checked in at {$time}).");
        }

        // Record check-in
        $attendance = MeetingAttendance::firstOrNew([
            'njangi_session_id' => $session->id,
            'member_id' => $member->id,
        ]);

        $attendance->organization_id = $session->organization_id;
        $attendance->attendance_status = 'present';
        $attendance->check_in_source = 'qr_self_check_in';
        $attendance->check_in_at = now();
        $attendance->save();

        $title = $member->traditional_title;
        $name = $member->name;

        return redirect()->back()->with('success', "Welcome, {$title} {$name}! Your attendance has been confirmed as Present.");
    }

    /**
     * Process Guest Check-In (No login required, isolated table)
     */
    public function submitGuestCheckIn(Request $request, string $token)
    {
        $session = NjangiSession::where('qr_token', $token)->firstOrFail();

        $request->validate([
            'guest_name' => 'required|string|min:2|max:100',
            'guest_phone' => 'nullable|string|max:30',
            'guest_email' => 'nullable|email|max:100',
            'invited_by_member_id' => 'nullable|exists:members,id',
            'invited_by_name' => 'nullable|string|max:100',
        ]);

        // Enforce time window
        $windowStatus = $session->checkInWindowStatus();
        if (!$windowStatus['is_open']) {
            $cutoff = $windowStatus['closes_at']->format('g:i A T');
            return redirect()->back()->with('error', "Check-in is closed for this session (Cutoff was {$cutoff}). Please see an officer.");
        }

        $guestName = trim($request->guest_name);

        // Duplicate prevention: check if guest name already checked in for this session
        $existingGuest = MeetingGuest::where('njangi_session_id', $session->id)
            ->whereRaw('LOWER(guest_name) = ?', [strtolower($guestName)])
            ->first();

        if ($existingGuest) {
            $time = $existingGuest->check_in_at ? $existingGuest->check_in_at->format('g:i A') : '';
            return redirect()->back()->with('info', "Guest '{$guestName}' is already registered for this meeting session (Checked in at {$time}).");
        }

        $invitedByName = $request->invited_by_name;
        if ($request->filled('invited_by_member_id')) {
            $invitingMember = Member::find($request->invited_by_member_id);
            if ($invitingMember) {
                $invitedByName = $invitingMember->name;
            }
        }

        MeetingGuest::create([
            'organization_id' => $session->organization_id,
            'njangi_session_id' => $session->id,
            'guest_name' => $guestName,
            'guest_phone' => $request->guest_phone,
            'guest_email' => $request->guest_email,
            'invited_by_member_id' => $request->invited_by_member_id,
            'invited_by_name' => $invitedByName,
            'check_in_at' => now(),
        ]);

        return redirect()->back()->with('guest_success', "Welcome to NFUH DMV, {$guestName}! Your guest check-in has been successfully registered.");
    }

    /**
     * Download QR Code as PNG image
     */
    public function viewQr(NjangiSession $session)
    {
        $this->authorizeDeskAccess();

        $session->ensureQrToken();
        $url = route('attendance.checkin.show', ['token' => $session->qr_token]);
        $png = $this->qrService->generatePng($url, 600, 4);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="session-' . $session->id . '-qr.png"',
        ]);
    }

    /**
     * Member Portal View: My Attendance & Meeting Records
     */
    public function myAttendance(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = MemberResolver::fromUser($user);
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        // Fetch upcoming and past sessions for member's organization
        $sessions = NjangiSession::where('organization_id', $member->organization_id)
            ->with(['cycle', 'attendances' => function ($q) use ($member) {
                $q->where('member_id', $member->id);
            }])
            ->orderBy('session_date', 'desc')
            ->paginate(12);

        // Calculate personal attendance rate
        $attendanceRate = $member->attendanceRate();

        $totalVerified = MeetingAttendance::where('member_id', $member->id)
            ->whereNotNull('attendance_status')
            ->count();

        $presentCount = MeetingAttendance::where('member_id', $member->id)
            ->where('attendance_status', 'present')
            ->count();

        $absentCount = MeetingAttendance::where('member_id', $member->id)
            ->where('attendance_status', 'absent')
            ->count();

        $excusedCount = MeetingAttendance::where('member_id', $member->id)
            ->where('attendance_status', 'excused')
            ->count();

        // Find next upcoming open or scheduled session
        $upcomingSession = NjangiSession::where('organization_id', $member->organization_id)
            ->whereIn('status', ['open', 'scheduled'])
            ->where('session_date', '>=', now()->toDateString())
            ->orderBy('session_date', 'asc')
            ->first();

        $upcomingAttendance = null;
        if ($upcomingSession) {
            $upcomingAttendance = MeetingAttendance::where('njangi_session_id', $upcomingSession->id)
                ->where('member_id', $member->id)
                ->first();
        }

        $appSettings = Setting::first();

        return view('attendance.member_history', compact(
            'member',
            'sessions',
            'attendanceRate',
            'totalVerified',
            'presentCount',
            'absentCount',
            'excusedCount',
            'upcomingSession',
            'upcomingAttendance',
            'appSettings'
        ));
    }
}
