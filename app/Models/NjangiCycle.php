<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NjangiCycle extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'year',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function cycleMembers(): HasMany
    {
        return $this->hasMany(NjangiCycleMember::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(NjangiSession::class);
    }

    public function paymentSubmissions(): HasMany
    {
        return $this->hasMany(NjangiPaymentSubmission::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(NjangiContribution::class);
    }
}