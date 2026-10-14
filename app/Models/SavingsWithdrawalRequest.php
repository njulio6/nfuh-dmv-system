<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsWithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'organization_id',
        'gross_amount',
        'fee_rate',
        'fee_amount',
        'net_amount',
        'payout_method',
        'payout_details',
        'notes',
        'status',
        'rejection_reason',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'disbursed_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'fee_rate' => 'float',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'disbursed_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
