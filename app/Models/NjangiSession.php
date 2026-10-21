<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NjangiSession extends Model
{
    protected $fillable = [
        'organization_id',
        'njangi_cycle_id',
        'session_number',
        'session_date',
        'title',
        'notes',
        'status',
        'qr_token',
        'check_in_opens_at',
        'check_in_closes_at',
        'check_in_timezone',
    ];

    protected $casts = [
        'session_number' => 'integer',
        'session_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (NjangiSession $session) {
            if (empty($session->qr_token)) {
                $session->qr_token = Str::random(32);
            }
            if (empty($session->check_in_opens_at)) {
                $session->check_in_opens_at = '17:00:00';
            }
            if (empty($session->check_in_closes_at)) {
                $session->check_in_closes_at = '21:00:00';
            }
            if (empty($session->check_in_timezone)) {
                $session->check_in_timezone = 'America/New_York';
            }
        });
    }

    public function ensureQrToken(): string
    {
        if (empty($this->qr_token)) {
            $this->update(['qr_token' => Str::random(32)]);
        }
        return $this->qr_token;
    }

    public function getCheckInUrlAttribute(): string
    {
        return route('attendance.checkin.show', ['token' => $this->ensureQrToken()]);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(NjangiCycle::class, 'njangi_cycle_id');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(NjangiSessionBeneficiary::class);
    }

    public function paymentSubmissions(): HasMany
    {
        return $this->hasMany(NjangiPaymentSubmission::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(NjangiContribution::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(NjangiDisbursement::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(MeetingGuest::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AttendanceAuditLog::class);
    }

    /**
     * Check if check-in is currently open based on session date and configured hours.
     */
    public function checkInWindowStatus(?Carbon $now = null): array
    {
        $timezone = $this->check_in_timezone ?: 'America/New_York';
        $currentTime = $now ? $now->copy()->setTimezone($timezone) : Carbon::now($timezone);

        $opensAtTime = $this->check_in_opens_at ?: '17:00:00';
        $closesAtTime = $this->check_in_closes_at ?: '21:00:00';

        // Use session date if it falls on the current day; otherwise evaluate against current day
        // so that active meetings managed at the desk operate within today's configured hours.
        $baseDate = ($this->session_date && $this->session_date->format('Y-m-d') === $currentTime->format('Y-m-d'))
            ? $this->session_date->format('Y-m-d')
            : $currentTime->format('Y-m-d');

        $windowStart = Carbon::parse("{$baseDate} {$opensAtTime}", $timezone);
        $windowEnd = Carbon::parse("{$baseDate} {$closesAtTime}", $timezone);

        if ($windowEnd->lessThanOrEqualTo($windowStart)) {
            $windowEnd->addDay();
        }

        if ($currentTime->lessThan($windowStart)) {
            return [
                'status' => 'not_started',
                'is_open' => false,
                'opens_at' => $windowStart,
                'closes_at' => $windowEnd,
                'current_time' => $currentTime,
                'timezone' => $timezone,
            ];
        }

        if ($currentTime->greaterThan($windowEnd)) {
            return [
                'status' => 'closed',
                'is_open' => false,
                'opens_at' => $windowStart,
                'closes_at' => $windowEnd,
                'current_time' => $currentTime,
                'timezone' => $timezone,
            ];
        }

        return [
            'status' => 'open',
            'is_open' => true,
            'opens_at' => $windowStart,
            'closes_at' => $windowEnd,
            'current_time' => $currentTime,
            'timezone' => $timezone,
        ];
    }

    public function isCheckInOpen(?Carbon $now = null): bool
    {
        return $this->checkInWindowStatus($now)['is_open'];
    }

    public function attendanceStats(): array
    {
        $attendances = $this->attendances;
        return [
            'total_present' => $attendances->where('attendance_status', 'present')->count(),
            'total_qr_checkins' => $attendances->where('check_in_source', 'qr_self_check_in')->where('attendance_status', 'present')->count(),
            'total_officer_verified' => $attendances->where('check_in_source', '!=', 'qr_self_check_in')->where('attendance_status', 'present')->count(),
            'total_absent' => $attendances->where('attendance_status', 'absent')->count(),
            'total_excused' => $attendances->where('attendance_status', 'excused')->count(),
            'total_verified' => $attendances->whereNotNull('attendance_status')->count(),
            'total_guests' => $this->guests()->count(),
        ];
    }
}