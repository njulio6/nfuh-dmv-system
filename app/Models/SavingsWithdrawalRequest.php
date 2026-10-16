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

    /**
     * Mask sensitive bank routing and account information for non-financial or general display.
     */
    public function getMaskedPayoutDetailsAttribute(): string
    {
        $details = $this->payout_details ?? '';
        if ($this->payout_method === 'bank_transfer') {
            // Mask account number: replace all but last 4 digits
            $details = preg_replace_callback('/(Account:\s*)([^\s|]+)/i', function($matches) {
                $raw = trim($matches[2]);
                $len = strlen($raw);
                if ($len > 4) {
                    return $matches[1] . str_repeat('•', max(4, $len - 4)) . substr($raw, -4);
                }
                return $matches[1] . '••••' . substr($raw, -2);
            }, $details);

            // Mask routing number: hide first 5 digits
            $details = preg_replace_callback('/(Routing:\s*)([0-9]{5})([0-9]{4})/i', function($matches) {
                return $matches[1] . '•••••' . $matches[3];
            }, $details);
        }

        return $details;
    }
}
