<?php

namespace App\Imports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class MembersImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            // Skip header row
            if ($index === 0) {
                continue;
            }

            Member::create([
                'name' => $row[0] ?? null,
                'email' => $row[1] ?? null,
                'phone' => $row[2] ?? null,
                'status' => 'active',
            ]);
        }
    }
}