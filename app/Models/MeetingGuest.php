<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingGuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'njangi_session_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'invited_by_member_id',
        'invited_by_name',
        'check_in_at',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(NjangiSession::class, 'njangi_session_id');
    }

    public function invitedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'invited_by_member_id');
    }
}
