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
        'sub_status_id',
        'purpose',
        'disbursed_at',
        'repayment_due_date',
        'admin_notes',
        'interest_rate',
        'interest_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'duration_months' => 'integer',
        'disbursed_at' => 'datetime',
        'repayment_due_date' => 'date',
        'interest_rate' => 'float',
        'interest_type' => 'string',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function subStatus(): BelongsTo
    {
        return $this->belongsTo(LoanSubStatus::class, 'sub_status_id');
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(LoanGuarantor::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function getTotalRepayableAttribute(): float
    {
        $interest = (float) $this->amount * ($this->interest_rate / 100);
        
        if ($this->interest_type === 'duration_based') {
            $interest = $interest * ($this->duration_months / 12);
        }
        
        return (float) ($this->amount + $interest);
    }

    public function getRemainingBalanceAttribute(): float
    {
        $paid = (float) $this->repayments()->sum('amount');
        return (float) max(0, $this->total_repayable - $paid);
    }
}
