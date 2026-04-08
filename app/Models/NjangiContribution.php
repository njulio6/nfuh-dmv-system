<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NjangiContribution extends Model
{
    protected $fillable = [
        'njangi_session_id',
        'njangi_cycle_member_id',
        'amount_due',
        'amount_paid',
        'penalty_amount',
        'other_adjustment',
        'payment_date',
        'payment_status',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'other_adjustment' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(NjangiSession::class, 'njangi_session_id');
    }

    public function cycleMember(): BelongsTo
    {
        return $this->belongsTo(NjangiCycleMember::class, 'njangi_cycle_member_id');
    }
}