<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function royalAuthority(): HasOne
    {
        return $this->hasOne(RoyalAuthority::class);
    }

    // 🔥 NJANGI RELATIONSHIPS

    public function njangiCycles(): HasMany
    {
        return $this->hasMany(NjangiCycle::class);
    }

    public function njangiSessions(): HasMany
    {
        return $this->hasMany(NjangiSession::class);
    }

    public function njangiPaymentSubmissions(): HasMany
    {
        return $this->hasMany(NjangiPaymentSubmission::class);
    }

    public function njangiContributions(): HasMany
    {
        return $this->hasMany(NjangiContribution::class);
    }

    public function njangiDisbursements(): HasMany
    {
        return $this->hasMany(NjangiDisbursement::class);
    }
}