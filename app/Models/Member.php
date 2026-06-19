<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'member_code',
        'first_name',
        'last_name',
        // email is NOT here — it lives exclusively on users.email
        'phone',
        'rank_id',
        'status',
        'address',
        'state_code',
        'join_date',
        'next_of_kin_name',
        'next_of_kin_phone',
        'next_of_kin_email',
        'next_of_kin_address',
        'participates_in_njangi',
        'participates_in_savings',
    ];

    protected $casts = [
        'join_date' => 'date',
        'participates_in_njangi' => 'boolean',
        'participates_in_savings' => 'boolean',
    ];

    protected $appends = [
        'name',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(MemberRank::class, 'rank_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(MemberRole::class, 'member_role_member');
    }

    public function njangiCycleMemberships(): HasMany
    {
        return $this->hasMany(NjangiCycleMember::class);
    }

    public function njangiPaymentSubmissions(): HasMany
    {
        return $this->hasMany(NjangiPaymentSubmission::class);
    }

    public function njangiContributionsMade(): HasMany
    {
        return $this->hasMany(NjangiContribution::class, 'contributor_member_id');
    }

    public function njangiContributionsReceived(): HasMany
    {
        return $this->hasMany(NjangiContribution::class, 'beneficiary_member_id');
    }

    public function savingsTransactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class);
    }

    public function getSavingsBalanceAttribute(): float
    {
        $deposits = $this->savingsTransactions()->whereIn('type', ['deposit', 'adjustment'])->where('status', 'approved')->sum('amount');
        $withdrawals = $this->savingsTransactions()->where('type', 'withdrawal')->where('status', 'approved')->sum('amount');
        return (float) max(0, $deposits - $withdrawals);
    }

    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function loanRequests(): HasMany
    {
        return $this->hasMany(LoanRequest::class);
    }

    public function guaranteeRequests(): HasMany
    {
        return $this->hasMany(LoanGuarantor::class, 'guarantor_member_id');
    }

    public function getOutstandingLoanBalanceAttribute(): float
    {
        $activeLoans = $this->loanRequests()->whereIn('status', ['active', 'defaulted'])->get();
        $balance = 0.0;
        foreach ($activeLoans as $loan) {
            $balance += $loan->remaining_balance;
        }
        return (float) $balance;
    }
}