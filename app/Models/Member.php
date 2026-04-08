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
    'next_of_kin_name',
    'next_of_kin_phone',
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