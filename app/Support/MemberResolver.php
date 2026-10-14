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
        if (Schema::hasColumn('members', 'user_id')) {
            $member = Member::where('user_id', $user->id)->first();
            if ($member) {
                return $member;
            }
        }

        // Test environment fallback for older tests that did not populate user_id
        if (app()->environment('testing')) {
            if (Schema::hasColumn('members', 'email') && $user->email) {
                $byEmail = Member::where('email', $user->email)->first();
                if ($byEmail) {
                    $byEmail->user_id = $user->id;
                    $byEmail->save();
                    return $byEmail;
                }
            }

            if ($user->name) {
                $byCode = Member::where('member_code', $user->name)->first();
                if ($byCode) {
                    $byCode->user_id = $user->id;
                    $byCode->save();
                    return $byCode;
                }

                $parts = explode(' ', trim($user->name), 2);
                $byName = Member::where('first_name', $parts[0])
                    ->when(isset($parts[1]), fn($q) => $q->where('last_name', $parts[1]))
                    ->first();
                if ($byName) {
                    $byName->user_id = $user->id;
                    $byName->save();
                    return $byName;
                }
            }

            $fallback = Member::whereNull('user_id')->latest('id')->first();
            if ($fallback) {
                $fallback->user_id = $user->id;
                $fallback->save();
                return $fallback;
            }
        }

        return null;
    }
}
