<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\MemberRole;

class Member extends Model
{
    protected $fillable = [
        'organization_id',
        'member_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'rank_id',
        'status',
        'address',
        'state_code',
        'join_date',
        'next_of_kin_name',
        'next_of_kin_phone',
        'next_of_kin_email',
        'next_of_kin_address',
        'participates_in_njangi',
        'participates_in_savings',
        'participates_in_cultural',
    ];

    protected $casts = [
        'join_date' => 'date',
        'participates_in_njangi' => 'boolean',
        'participates_in_savings' => 'boolean',
        'participates_in_cultural' => 'boolean',
    ];

    public function rank(): BelongsTo
    {
        return $this->belongsTo(MemberRank::class, 'rank_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(MemberRole::class, 'member_role_member');
    }
}