<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NjangiPaymentSubmission extends Model
{
    protected $fillable = [
        'organization_id',
        'member_id',
        'njangi_cycle_id',
        'njangi_session_id',
        'amount',
        'is_attending',
        'screenshot_path',
        'status',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
        'member_note',
        'review_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_attending' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(NjangiCycle::class, 'njangi_cycle_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(NjangiSession::class, 'njangi_session_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}