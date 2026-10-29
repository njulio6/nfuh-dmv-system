<?php

namespace App\Support;

use App\Models\User;

class SegregationGuard
{
    /**
     * Determine whether the given user's linked member profile matches the target member ID.
     * Used across financial approval and disbursement endpoints to enforce Segregation of Duties.
     *
     * @param User $user
     * @param int|string|null $targetMemberId
     * @return bool
     */
    public static function isSelf(User $user, $targetMemberId): bool
    {
        if (empty($targetMemberId)) {
            return false;
        }

        $targetMember = \App\Models\Member::find($targetMemberId);
        if (!$targetMember) {
            return false;
        }

        if (!empty($targetMember->user_id)) {
            return (int) $targetMember->user_id === (int) $user->id;
        }

        if (!empty($targetMember->email) && !empty($user->email)) {
            return strtolower(trim($targetMember->email)) === strtolower(trim($user->email));
        }

        return false;
    }
}
