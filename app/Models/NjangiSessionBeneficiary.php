<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NjangiSessionBeneficiary extends Model
{
    protected $fillable = [
        'organization_id',
        'njangi_session_id',
        'njangi_cycle_member_id',
        'beneficiary_slot',
        'benefit_order',
        'notes',
    ];

    protected $casts = [
        'beneficiary_slot' => 'integer',
        'benefit_order' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(NjangiSession::class, 'njangi_session_id');
    }

    public function cycleMember(): BelongsTo
    {
        return $this->belongsTo(NjangiCycleMember::class, 'njangi_cycle_member_id');
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(NjangiDisbursement::class);
    }
}