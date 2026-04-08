<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoyalAuthority extends Model
{
    protected $fillable = [
        'organization_id',
        'title',
        'name',
        'email',
        'phone',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}