<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NjangiDisbursement extends Model
{
    protected $fillable = [
        'organization_id',
        'njangi_session_id',
        'njangi_session_beneficiary_id',
        'njangi_cycle_member_id',
        'gross_amount',
        'loan_deduction',
        'penalty_deduction',
        'other_deduction',
        'net_amount',
        'disbursement_date',
        'status',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'penalty_deduction' => 'decimal:2',
        'other_deduction' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'disbursement_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(NjangiSession::class, 'njangi_session_id');
    }

    public function sessionBeneficiary(): BelongsTo
    {
        return $this->belongsTo(NjangiSessionBeneficiary::class, 'njangi_session_beneficiary_id');
    }

    public function cycleMember(): BelongsTo
    {
        return $this->belongsTo(NjangiCycleMember::class, 'njangi_cycle_member_id');
    }
}