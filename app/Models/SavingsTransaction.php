<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsTransaction extends Model
{
    protected $fillable = [
        'member_id',
        'organization_id',
        'amount',
        'previous_balance',
        'new_balance',
        'type',
        'status',
        'screenshot_path',
        'transaction_date',
        'notes',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'previous_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'transaction_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function withdrawalRequest()
    {
        return $this->hasOne(SavingsWithdrawalRequest::class, 'savings_transaction_id');
    }

    public function getOfficerAttribute()
    {
        return $this->reviewer 
            ?? $this->withdrawalRequest?->disbursedBy 
            ?? $this->withdrawalRequest?->reviewer;
    }

    public function getOfficerNameAttribute(): string
    {
        $officer = $this->officer;
        if ($officer) {
            return $officer->name;
        }

        if (preg_match('/(?:recorded|entered) by\s+([^,\.\n\)]+)/i', $this->notes ?? '', $matches)) {
            return trim($matches[1]);
        }

        return 'Admin / Office';
    }

    public function getIsManualAttribute(): bool
    {
        if ($this->type === 'adjustment') {
            return true;
        }

        if ($this->type === 'withdrawal') {
            $notes = strtolower($this->notes ?? '');
            $req = $this->withdrawalRequest;
            if ($req) {
                $reqNotes = strtolower(($req->notes ?? '') . ' ' . ($req->payout_details ?? ''));
                if (str_contains($reqNotes, 'manual') || $req->payout_method === 'cash') {
                    return true;
                }
            }
            if (str_contains($notes, 'manual')) {
                return true;
            }
            return false;
        }

        // For deposit: direct admin posts don't start with "Approved deposit request"
        if ($this->type === 'deposit') {
            if (str_starts_with($this->notes ?? '', 'Approved deposit request')) {
                return false;
            }
            return true;
        }

        return false;
    }

    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdrawal');
    }

    public function scopeAdjustments($query)
    {
        return $query->where('type', 'adjustment');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
