<?php

namespace App\Services\Member;

use App\Models\Member;

class MemberCodeGenerator
{
    public function generate(string $stateCode, string $joinDate): string
    {
        $year = date('Y', strtotime($joinDate));
        $prefix = "{$stateCode}-{$year}-";

        $lastMember = Member::where('member_code', 'like', $prefix . '%')
            ->orderByDesc('member_code')
            ->first();

        $nextSequence = 1;

        if ($lastMember) {
            $parts = explode('-', $lastMember->member_code);
            $lastSequence = (int) ($parts[2] ?? 0);
            $nextSequence = $lastSequence + 1;
        }

        return $prefix . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }
}
