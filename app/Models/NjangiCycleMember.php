<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NjangiCycleMember extends Model
{
    protected $fillable = [
        'njangi_cycle_id',
        'member_id',
        'benefit_order',
        'monthly_contribution_amount',
        'is_active',
        'joined_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'date',
        'monthly_contribution_amount' => 'decimal:2',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(NjangiCycle::class, 'njangi_cycle_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sessionBeneficiaries(): HasMany
    {
        return $this->hasMany(NjangiSessionBeneficiary::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(NjangiContribution::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(NjangiDisbursement::class);
    }
}