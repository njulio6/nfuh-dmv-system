<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanGuarantor extends Model
{
    protected $fillable = [
        'loan_request_id',
        'guarantor_member_id',
        'status',
        'terms_accepted',
        'terms_accepted_at',
        'terms_ip_address',
        'notes',
        'responded_at',
    ];

    protected $casts = [
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function loanRequest(): BelongsTo
    {
        return $this->belongsTo(LoanRequest::class, 'loan_request_id');
    }

    public function guarantorMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'guarantor_member_id');
    }
}
