<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\Organization;
use App\Models\MemberRank;
use App\Models\MemberRole;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::firstOrCreate([
            'name' => 'NFUH DMV',
        ]);

        $titleNames = [
            'HRH',
            'Nformi',
            'Tamfuh',
            'Tar Kifu',
            'Ngwang',
            'Ngwaye',
            'Gwei',
            'Lagham',
        ];

        foreach ($titleNames as $titleName) {
            MemberRank::firstOrCreate(['name' => $titleName]);
        }

        $roleNames = [
            'Secretary',
            'Treasurer',
            'Financial Secretary',
            'Loan Officer',
            'Lead Nformi',
        ];

        foreach ($roleNames as $roleName) {
            MemberRole::firstOrCreate(['name' => $roleName]);
        }

        $titles = MemberRank::pluck('id', 'name');
        $roles = MemberRole::pluck('id', 'name');

        $members = [
            [
                'first_name' => 'Julius',
                'last_name' => 'Nfor',
                'email' => 'julius@example.com',
                'phone' => '2401110001',
                'state_code' => 'MD',
                'join_date' => '2024-01-10',
                'title' => null,
                'roles' => [],
                'address' => '3505 Sunflower Pl, Bowie, MD',
                'next_of_kin_name' => 'Jovian Gangtar',
                'next_of_kin_phone' => '2405814324',
                'next_of_kin_email' => 'ajovian2013@gmail.com',
                'next_of_kin_address' => '3505 Sunflower Pl, Bowie, MD',
                'participates_in_njangi' => true,
                'participates_in_savings' => true,
                'participates_in_cultural' => false,
            ],
            [
                'first_name' => 'Emmanuel',
                'last_name' => 'Tambe',
                'email' => 'emmanuel@example.com',
                'phone' => '2401110002',
                'state_code' => 'MD',
                'join_date' => '2023-12-05',
                'title' => 'HRH',
                'roles' => ['Secretary'],
                'address' => '1401 Central Ave, Hyattsville, MD',
                'next_of_kin_name' => 'Martha Tambe',
                'next_of_kin_phone' => '2401111002',
                'next_of_kin_email' => 'martha.tambe@example.com',
                'next_of_kin_address' => '1401 Central Ave, Hyattsville, MD',
                'participates_in_njangi' => true,
                'participates_in_savings' => true,
                'participates_in_cultural' => true,
            ],
            [
                'first_name' => 'Brenda',
                'last_name' => 'Ngwa',
                'email' => 'brenda@example.com',
                'phone' => '7031110003',
                'state_code' => 'VA',
                'join_date' => '2024-02-20',
                'title' => 'Nformi',
                'roles' => ['Treasurer'],
                'address' => '8021 Richmond Hwy, Alexandria, VA',
                'next_of_kin_name' => 'Samuel Ngwa',
                'next_of_kin_phone' => '7031111003',
                'next_of_kin_email' => 'samuel.ngwa@example.com',
                'next_of_kin_address' => '8021 Richmond Hwy, Alexandria, VA',
                'participates_in_njangi' => true,
                'participates_in_savings' => true,
                'participates_in_cultural' => true,
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Fon',
                'email' => 'michael@example.com',
                'phone' => '2021110004',
                'state_code' => 'DC',
                'join_date' => '2024-03-01',
                'title' => null,
                'roles' => [],
                'address' => '1200 Rhode Island Ave NE, Washington, DC',
                'next_of_kin_name' => 'Grace Fon',
                'next_of_kin_phone' => '2021111004',
                'next_of_kin_email' => 'grace.fon@example.com',
                'next_of_kin_address' => '1200 Rhode Island Ave NE, Washington, DC',
                'participates_in_njangi' => false,
                'participates_in_savings' => true,
                'participates_in_cultural' => true,
            ],
            [
                'first_name' => 'Agnes',
                'last_name' => 'Tanyi',
                'email' => 'agnes@example.com',
                'phone' => '2401110005',
                'state_code' => 'MD',
                'join_date' => '2023-11-15',
                'title' => 'Ngwaye',
                'roles' => ['Financial Secretary'],
                'address' => '5510 Silver Hill Rd, District Heights, MD',
                'next_of_kin_name' => 'John Tanyi',
                'next_of_kin_phone' => '2401111005',
                'next_of_kin_email' => 'john.tanyi@example.com',
                'next_of_kin_address' => '5510 Silver Hill Rd, District Heights, MD',
                'participates_in_njangi' => true,
                'participates_in_savings' => true,
                'participates_in_cultural' => false,
            ],
            [
                'first_name' => 'Paul',
                'last_name' => 'Kifu',
                'email' => 'paul@example.com',
                'phone' => '7031110006',
                'state_code' => 'VA',
                'join_date' => '2024-01-25',
                'title' => 'Tar Kifu',
                'roles' => ['Loan Officer'],
                'address' => '2200 Wilson Blvd, Arlington, VA',
                'next_of_kin_name' => 'Rita Kifu',
                'next_of_kin_phone' => '7031111006',
                'next_of_kin_email' => 'rita.kifu@example.com',
                'next_of_kin_address' => '2200 Wilson Blvd, Arlington, VA',
                'participates_in_njangi' => false,
                'participates_in_savings' => true,
                'participates_in_cultural' => true,
            ],
            [
                'first_name' => 'Linda',
                'last_name' => 'Tamfuh',
                'email' => 'linda@example.com',
                'phone' => '2401110007',
                'state_code' => 'MD',
                'join_date' => '2024-02-10',
                'title' => 'Tamfuh',
                'roles' => [],
                'address' => '4301 Northview Dr, Bowie, MD',
                'next_of_kin_name' => 'James Tamfuh',
                'next_of_kin_phone' => '2401111007',
                'next_of_kin_email' => 'james.tamfuh@example.com',
                'next_of_kin_address' => '4301 Northview Dr, Bowie, MD',
                'participates_in_njangi' => true,
                'participates_in_savings' => false,
                'participates_in_cultural' => true,
            ],
            [
                'first_name' => 'George',
                'last_name' => 'Lagham',
                'email' => 'george@example.com',
                'phone' => '2021110008',
                'state_code' => 'DC',
                'join_date' => '2023-10-05',
                'title' => 'Lagham',
                'roles' => ['Lead Nformi'],
                'address' => '600 H St NW, Washington, DC',
                'next_of_kin_name' => 'Linda George',
                'next_of_kin_phone' => '2021111008',
                'next_of_kin_email' => 'linda.george@example.com',
                'next_of_kin_address' => '600 H St NW, Washington, DC',
                'participates_in_njangi' => false,
                'participates_in_savings' => false,
                'participates_in_cultural' => true,
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Ngwang',
                'email' => 'sarah@example.com',
                'phone' => '7031110009',
                'state_code' => 'VA',
                'join_date' => '2024-03-12',
                'title' => 'Ngwang',
                'roles' => [],
                'address' => '5100 Duke St, Alexandria, VA',
                'next_of_kin_name' => 'Peter Ngwang',
                'next_of_kin_phone' => '7031111009',
                'next_of_kin_email' => 'peter.ngwang@example.com',
                'next_of_kin_address' => '5100 Duke St, Alexandria, VA',
                'participates_in_njangi' => true,
                'participates_in_savings' => false,
                'participates_in_cultural' => true,
            ],
            [
                'first_name' => 'Peter',
                'last_name' => 'Gwei',
                'email' => 'peter@example.com',
                'phone' => '2401110010',
                'state_code' => 'MD',
                'join_date' => '2024-01-18',
                'title' => 'Gwei',
                'roles' => ['Secretary', 'Loan Officer'],
                'address' => '7700 Laurel Bowie Rd, Bowie, MD',
                'next_of_kin_name' => 'Nancy Gwei',
                'next_of_kin_phone' => '2401111010',
                'next_of_kin_email' => 'nancy.gwei@example.com',
                'next_of_kin_address' => '7700 Laurel Bowie Rd, Bowie, MD',
                'participates_in_njangi' => true,
                'participates_in_savings' => true,
                'participates_in_cultural' => false,
            ],
        ];

        foreach ($members as $data) {
            $member = Member::create([
                'organization_id' => $org->id,
                'member_code' => 'TEMP',
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'rank_id' => $data['title'] ? ($titles[$data['title']] ?? null) : null,
                'status' => 'active',
                'address' => $data['address'],
                'state_code' => $data['state_code'],
                'join_date' => $data['join_date'],
                'next_of_kin_name' => $data['next_of_kin_name'],
                'next_of_kin_phone' => $data['next_of_kin_phone'],
                'next_of_kin_email' => $data['next_of_kin_email'],
                'next_of_kin_address' => $data['next_of_kin_address'],
                'participates_in_njangi' => $data['participates_in_njangi'],
                'participates_in_savings' => $data['participates_in_savings'],
                'participates_in_cultural' => $data['participates_in_cultural'],
            ]);

            $member->update([
                'member_code' => $this->generateMemberCode($member->state_code, $member->join_date),
            ]);

            $roleIds = collect($data['roles'])
                ->map(fn ($roleName) => $roles[$roleName] ?? null)
                ->filter()
                ->values()
                ->toArray();

            $member->roles()->sync($roleIds);
        }
    }

    private function generateMemberCode(string $stateCode, string $joinDate): string
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