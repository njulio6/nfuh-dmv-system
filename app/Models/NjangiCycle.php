<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NjangiCycle extends Model
{
protected $fillable = [
    'name',
    'year',
    'start_date',
    'end_date',
    'status',
    'notes',
];

    public function cycleMembers(): HasMany
    {
        return $this->hasMany(NjangiCycleMember::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(NjangiSession::class);
    }
}