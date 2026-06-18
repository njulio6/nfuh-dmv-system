<?php

namespace App\Support;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class MemberResolver
{
    /**
     * Resolve the Member record for a given authenticated User.
     *
     * Resolution is now purely by user_id — the explicit, reliable foreign key.
     * Email-based fallback has been removed because members.email column no longer exists.
     *
     * @return Member|null
     */
    public static function fromUser(User $user): ?Member
    {
        // If the `user_id` column hasn't been migrated yet, avoid running the query
        // which would raise a SQL error in older databases. Return null so callers
        // can handle the absence gracefully until migrations are applied.
        if (! Schema::hasColumn('members', 'user_id')) {
            return null;
        }

        return Member::where('user_id', $user->id)->first();
    }
}
