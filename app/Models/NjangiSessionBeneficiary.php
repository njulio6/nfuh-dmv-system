<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NjangiSessionBeneficiary extends Model
{
    protected $fillable = [
        'njangi_session_id',
        'njangi_cycle_member_id',
        'beneficiary_slot',
        'benefit_order',
        'notes',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(NjangiSession::class, 'njangi_session_id');
    }

    public function cycleMember(): BelongsTo
    {
        return $this->belongsTo(NjangiCycleMember::class, 'njangi_cycle_member_id');
    }

    public function disbursement(): HasOne
    {
        return $this->hasOne(NjangiDisbursement::class);
    }
}