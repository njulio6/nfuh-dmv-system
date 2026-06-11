<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class MembersImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $org = Organization::firstOrCreate([
            'name' => 'NFUH DMV',
        ]);

        foreach ($rows as $index => $row) {
            // Skip header row
            if ($index === 0) {
                continue;
            }

            $fullName = trim($row[0] ?? '');
            if ($fullName === '') {
                continue;
            }

            $parts = explode(' ', $fullName, 2);
            $firstName = $parts[0] ?? '';
            $lastName = isset($parts[1]) ? trim($parts[1]) : 'N/A';
            if ($lastName === '') {
                $lastName = 'N/A';
            }

            $email = isset($row[1]) ? trim($row[1]) : null;
            $phone = isset($row[2]) ? trim($row[2]) : 'N/A';

            $stateCode = 'MD';
            $joinDate = now()->toDateString();

            $generator = new \App\Services\Member\MemberCodeGenerator();

            $member = Member::create([
                'organization_id' => $org->id,
                'member_code' => 'TEMP',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'status' => 'active',
                'address' => 'N/A',
                'state_code' => $stateCode,
                'join_date' => $joinDate,
                'participates_in_njangi' => true,
                'participates_in_savings' => true,
            ]);

            $member->update([
                'member_code' => $generator->generate($stateCode, $joinDate),
            ]);
        }
    }
}