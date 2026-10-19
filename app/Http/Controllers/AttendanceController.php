<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MeetingAttendance;
use App\Models\NjangiSession;
use App\Models\Setting;
use App\Support\MemberResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
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

        // Fetch all sessions ordered by date
        $sessions = NjangiSession::with('cycle')
            ->orderBy('session_date', 'desc')
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
        $stats = [
            'total_members' => 0,
            'rsvps_attending' => 0,
            'rsvps_not_attending' => 0,
            'verified_present' => 0,
            'verified_absent' => 0,
            'verified_excused' => 0,
            'unverified' => 0,
        ];

        if ($selectedSession) {
            $query = Member::where('organization_id', $selectedSession->organization_id)
                ->where('status', 'active')
                ->with(['attendances' => function ($q) use ($selectedSession) {
                    $q->where('njangi_session_id', $selectedSession->id);
                }, 'rank']);

            // Filters
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

            // Calculate overall session stats across all active members
            $allSessionAttendances = MeetingAttendance::where('njangi_session_id', $selectedSession->id)->get();
            $totalActiveMembers = Member::where('organization_id', $selectedSession->organization_id)->where('status', 'active')->count();

            $stats['total_members'] = $totalActiveMembers;
            $stats['rsvps_attending'] = $allSessionAttendances->where('rsvp_status', 'attending')->count();
            $stats['rsvps_not_attending'] = $allSessionAttendances->where('rsvp_status', 'not_attending')->count();
            $stats['verified_present'] = $allSessionAttendances->where('attendance_status', 'present')->count();
            $stats['verified_absent'] = $allSessionAttendances->where('attendance_status', 'absent')->count();
            $stats['verified_excused'] = $allSessionAttendances->where('attendance_status', 'excused')->count();
            $stats['unverified'] = max(0, $totalActiveMembers - ($stats['verified_present'] + $stats['verified_absent'] + $stats['verified_excused']));

            // Filter by RSVP or Attendance status if selected
            if ($request->filled('rsvp_filter')) {
                $rsvpFilter = $request->rsvp_filter;
                $members = $members->filter(function ($m) use ($rsvpFilter) {
                    $status = $m->current_attendance?->rsvp_status ?? 'undecided';
                    return $status === $rsvpFilter;
                });
            }

            if ($request->filled('attendance_filter')) {
                $attFilter = $request->attendance_filter;
                $members = $members->filter(function ($m) use ($attFilter) {
                    $status = $m->current_attendance?->attendance_status;
                    if ($attFilter === 'unverified') {
                        return is_null($status);
                    }
                    return $status === $attFilter;
                });
            }
        }

        $appSettings = Setting::first();

        return view('attendance.desk', compact('sessions', 'selectedSession', 'members', 'stats', 'appSettings'));
    }

    /**
     * Mark single member official attendance
     */
    public function mark(Request $request)
    {
        $this->authorizeDeskAccess();

        $request->validate([
            'session_id' => 'required|exists:njangi_sessions,id',
            'member_id' => 'required|exists:members,id',
            'status' => 'required|in:present,absent,excused',
            'officer_notes' => 'nullable|string|max:500',
        ]);

        $session = NjangiSession::findOrFail($request->session_id);
        $member = Member::findOrFail($request->member_id);

        $attendance = MeetingAttendance::firstOrNew([
            'njangi_session_id' => $session->id,
            'member_id' => $member->id,
        ]);

        $attendance->organization_id = $session->organization_id;
        $attendance->attendance_status = $request->status;
        $attendance->verified_by = Auth::id();
        $attendance->verified_at = now();
        if ($request->filled('officer_notes')) {
            $attendance->officer_notes = $request->officer_notes;
        }
        $attendance->save();

        if ($request->expectsJson() || $request->ajax()) {
            $allSessionAttendances = MeetingAttendance::where('njangi_session_id', $session->id)->get();
            $totalActiveMembers = Member::where('organization_id', $session->organization_id)->where('status', 'active')->count();

            $verifiedPresent = $allSessionAttendances->where('attendance_status', 'present')->count();
            $verifiedAbsent = $allSessionAttendances->where('attendance_status', 'absent')->count();
            $verifiedExcused = $allSessionAttendances->where('attendance_status', 'excused')->count();
            $unverified = max(0, $totalActiveMembers - ($verifiedPresent + $verifiedAbsent + $verifiedExcused));

            return response()->json([
                'success' => true,
                'message' => "Attendance for {$member->first_name} {$member->last_name} marked as " . ucfirst($request->status) . ".",
                'attendance' => [
                    'id' => $attendance->id,
                    'status' => $attendance->attendance_status,
                    'officer_notes' => $attendance->officer_notes,
                    'verifier_name' => Auth::user()->name,
                    'verified_at' => $attendance->verified_at ? $attendance->verified_at->format('M d, H:i') : null,
                ],
                'stats' => [
                    'verified_present' => $verifiedPresent,
                    'verified_absent' => $verifiedAbsent,
                    'verified_excused' => $verifiedExcused,
                    'unverified' => $unverified,
                ]
            ]);
        }

        return redirect()->back()->with('success', "Attendance for {$member->name} marked as " . ucfirst($request->status) . ".");
    }

    /**
     * Bulk Action: Mark All Attending RSVPs as Present
     */
    public function bulkMarkRsvps(Request $request)
    {
        $this->authorizeDeskAccess();

        $request->validate([
            'session_id' => 'required|exists:njangi_sessions,id',
        ]);

        $session = NjangiSession::findOrFail($request->session_id);

        $attendances = MeetingAttendance::where('njangi_session_id', $session->id)
            ->where('rsvp_status', 'attending')
            ->whereNull('attendance_status')
            ->get();

        $count = 0;
        foreach ($attendances as $att) {
            $att->attendance_status = 'present';
            $att->verified_by = Auth::id();
            $att->verified_at = now();
            $att->save();
            $count++;
        }

        return redirect()->back()->with('success', "Successfully marked {$count} member(s) with 'Attending' RSVPs as Present.");
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

    /**
     * Submit or update Member RSVP for an upcoming meeting
     */
    public function submitRsvp(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:njangi_sessions,id',
            'rsvp_status' => 'required|in:attending,not_attending',
            'rsvp_notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $member = MemberResolver::fromUser($user);
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        $session = NjangiSession::findOrFail($request->session_id);

        if (in_array($session->status, ['closed', 'cancelled'])) {
            return redirect()->back()->with('error', 'Cannot submit an RSVP for a closed or cancelled meeting session.');
        }

        $attendance = MeetingAttendance::firstOrNew([
            'njangi_session_id' => $session->id,
            'member_id' => $member->id,
        ]);

        $attendance->organization_id = $session->organization_id;
        $attendance->rsvp_status = $request->rsvp_status;
        $attendance->rsvp_notes = $request->rsvp_notes;
        $attendance->rsvp_at = now();
        $attendance->save();

        $statusText = $request->rsvp_status === 'attending' ? 'plan to attend' : 'will be absent';
        $dateStr = $session->session_date ? " ({$session->session_date->format('M d, Y')})" : '';
        return redirect()->back()->with('success', "Your RSVP has been saved: You {$statusText} for {$session->title}{$dateStr}.");
    }
}
