<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanRequest extends Model
{
    protected $fillable = [
        'member_id',
        'organization_id',
        'amount',
        'duration_months',
        'status',
        'purpose',
        'disbursed_at',
        'repayment_due_date',
        'admin_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'duration_months' => 'integer',
        'disbursed_at' => 'datetime',
        'repayment_due_date' => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(LoanGuarantor::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function getRemainingBalanceAttribute(): float
    {
        $paid = (float) $this->repayments()->sum('amount');
        return (float) max(0, $this->amount - $paid);
    }
}
