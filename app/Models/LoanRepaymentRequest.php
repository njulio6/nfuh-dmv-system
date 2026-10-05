<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepaymentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_request_id',
        'member_id',
        'organization_id',
        'amount',
        'status',
        'screenshot_path',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function loanRequest()
    {
        return $this->belongsTo(LoanRequest::class, 'loan_request_id');
    }

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
