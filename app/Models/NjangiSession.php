<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NjangiSession extends Model
{
    protected $fillable = [
        'njangi_cycle_id',
        'session_number',
        'session_date',
        'title',
        'notes',
        'status',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(NjangiCycle::class, 'njangi_cycle_id');
    }

    public function beneficiaries(): HasMany
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