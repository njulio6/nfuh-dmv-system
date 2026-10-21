<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'njangi_session_id',
        'member_id',
        'attendance_status',
        'check_in_source',
        'check_in_at',
        'verified_by',
        'verified_at',
        'officer_notes',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(NjangiSession::class, 'njangi_session_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('njangi_session_id', $sessionId);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('attendance_status');
    }

    public function isSelfCheckIn(): bool
    {
        return $this->check_in_source === 'qr_self_check_in';
    }

    public function isOfficialPresent(): bool
    {
        return $this->attendance_status === 'present';
    }

    public function isExcused(): bool
    {
        return $this->attendance_status === 'excused';
    }

    public function isAbsent(): bool
    {
        return $this->attendance_status === 'absent';
    }
}
