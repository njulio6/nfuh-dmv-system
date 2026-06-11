<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Member;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\NjangiSessionBeneficiary;
use App\Models\NjangiPaymentSubmission;
use App\Models\NjangiContribution;

class RealDataSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::first();

        // Third Cycle (2026) — Active
        $thirdCycle = NjangiCycle::create([
            'organization_id' => $org->id,
            'name'            => 'Third Cycle (2026)',
            'year'            => 2026,
            'start_date'      => '2026-01-01',
            'end_date'        => '2026-12-31',
            'status'          => 'active',
            'notes'           => 'Active 2026 Njangi rotational cycle.',
        ]);

        // Fourth Cycle (2026) — Active (second cycle visible in dropdown)
        $fourthCycle = NjangiCycle::create([
            'organization_id' => $org->id,
            'name'            => 'Fourth Cycle (2026)',
            'year'            => 2026,
            'start_date'      => '2026-06-01',
            'end_date'        => '2027-05-31',
            'status'          => 'active',
            'notes'           => 'Second active 2026 Njangi cycle.',
        ]);

        // ─── MEMBERS ─────────────────────────────────────────────────────────
        $members = Member::all()->keyBy(fn($m) => strtolower($m->first_name));

        $rakib   = $members->first(fn($m) => $m->email === 'krakib2002@gmail.com');
        $agnes   = $members->first(fn($m) => $m->email === 'agnes@example.com');
        $julius  = $members->first(fn($m) => $m->email === 'julius@example.com');
        $emmanuel = $members->first(fn($m) => $m->email === 'emmanuel@example.com');
        $brenda  = $members->first(fn($m) => $m->email === 'brenda@example.com');
        $paul    = $members->first(fn($m) => $m->email === 'paul@example.com');
        $linda   = $members->first(fn($m) => $m->email === 'linda@example.com');
        $george  = $members->first(fn($m) => $m->email === 'george@example.com');
        $sarah   = $members->first(fn($m) => $m->email === 'sarah@example.com');
        $peter   = $members->first(fn($m) => $m->email === 'peter@example.com');
        $michael = $members->first(fn($m) => $m->email === 'michael@example.com');

        // Enroll all members in Third Cycle — subscription_amount = $100
        // benefit_order follows the rotational schedule (1 = benefits first)
        $thirdCycleEnrollments = [
            ['member' => $julius,   'order' => 1],
            ['member' => $emmanuel, 'order' => 2],
            ['member' => $brenda,   'order' => 3],
            ['member' => $agnes,    'order' => 4],
            ['member' => $paul,     'order' => 5],
            ['member' => $linda,    'order' => 6],
            ['member' => $george,   'order' => 7],
            ['member' => $sarah,    'order' => 8],
            ['member' => $peter,    'order' => 9],
            ['member' => $michael,  'order' => 10],
            ['member' => $rakib,    'order' => 17], // Benefit position #17 (Awaiting Draw)
        ];

        $thirdCycleMemberRecords = [];
        foreach ($thirdCycleEnrollments as $cfg) {
            $member = $cfg['member'];
            if (!$member) continue;
            $thirdCycleMemberRecords[$member->id] = NjangiCycleMember::create([
                'njangi_cycle_id'     => $thirdCycle->id,
                'member_id'           => $member->id,
                'benefit_order'       => $cfg['order'],
                'subscription_amount' => 100.00,
                'is_active'           => true,
            ]);
        }

        // Enroll a subset in Fourth Cycle as well
        foreach ([$rakib, $agnes, $julius, $emmanuel] as $member) {
            if (!$member) continue;
            NjangiCycleMember::create([
                'njangi_cycle_id'     => $fourthCycle->id,
                'member_id'           => $member->id,
                'benefit_order'       => null,
                'subscription_amount' => 100.00,
                'is_active'           => true,
            ]);
        }

        // ─── SESSIONS (Third Cycle) ───────────────────────────────────────────
        $sessionData = [
            ['num' => 1,  'date' => '2026-01-01', 'title' => 'January Session',   'status' => 'closed'],
            ['num' => 2,  'date' => '2026-02-01', 'title' => 'February Session',  'status' => 'closed'],
            ['num' => 3,  'date' => '2026-03-01', 'title' => 'March Session',     'status' => 'closed'],
            ['num' => 4,  'date' => '2026-04-01', 'title' => 'April Session',     'status' => 'closed'],
            ['num' => 5,  'date' => '2026-05-01', 'title' => 'May Session',       'status' => 'closed'],
            ['num' => 6,  'date' => '2026-06-01', 'title' => 'June Session',      'status' => 'open'],
            ['num' => 7,  'date' => '2026-07-01', 'title' => 'July Session',      'status' => 'scheduled'],
            ['num' => 8,  'date' => '2026-08-01', 'title' => 'August Session',    'status' => 'scheduled'],
            ['num' => 9,  'date' => '2026-09-01', 'title' => 'September Session', 'status' => 'scheduled'],
            ['num' => 10, 'date' => '2026-10-01', 'title' => 'October Session',   'status' => 'scheduled'],
            ['num' => 11, 'date' => '2026-11-01', 'title' => 'November Session',  'status' => 'scheduled'],
            ['num' => 12, 'date' => '2026-12-01', 'title' => 'December Session',  'status' => 'scheduled'],
        ];

        $sessions = [];
        foreach ($sessionData as $s) {
            $sessions[$s['num']] = NjangiSession::create([
                'organization_id' => $org->id,
                'njangi_cycle_id' => $thirdCycle->id,
                'session_number'  => $s['num'],
                'session_date'    => $s['date'],
                'title'           => $s['title'],
                'status'          => $s['status'],
            ]);
        }

        // ─── BENEFICIARIES ───────────────────────────────────────────────────
        // June Session (session 6) — Agnes Tanyi + Rakib Hasan
        $juneSession = $sessions[6];
        $agnesCycleMember = $thirdCycleMemberRecords[$agnes->id] ?? null;
        $rakibCycleMember = $thirdCycleMemberRecords[$rakib->id] ?? null;

        if ($agnesCycleMember) {
            NjangiSessionBeneficiary::create([
                'organization_id'       => $org->id,
                'njangi_session_id'     => $juneSession->id,
                'njangi_cycle_member_id'=> $agnesCycleMember->id,
                'beneficiary_slot'      => 1,
            ]);
        }
        if ($rakibCycleMember) {
            NjangiSessionBeneficiary::create([
                'organization_id'       => $org->id,
                'njangi_session_id'     => $juneSession->id,
                'njangi_cycle_member_id'=> $rakibCycleMember->id,
                'beneficiary_slot'      => 2,
            ]);
        }

        // ─── PAYMENT SUBMISSIONS ─────────────────────────────────────────────
        // Note: reviewed_by is null since no users are seeded (admin is created via installer)
        // Submission 1: $400 — APPROVED
        $approvedSubmission = NjangiPaymentSubmission::create([
            'organization_id'   => $org->id,
            'member_id'         => $rakib->id,
            'njangi_cycle_id'   => $thirdCycle->id,
            'njangi_session_id' => $juneSession->id,
            'amount'            => 400.00,
            'is_attending'      => false,
            'screenshot_path'   => 'screenshots/placeholder.jpg',
            'status'            => 'approved',
            'reviewed_by'       => null,
            'submitted_at'      => '2026-06-11 08:00:00',
            'reviewed_at'       => '2026-06-11 09:00:00',
            'member_note'       => null,
            'review_note'       => null,
        ]);

        // Split $400 equally: $200 → Agnes, $200 → Rakib
        NjangiContribution::create([
            'organization_id'       => $org->id,
            'njangi_cycle_id'       => $thirdCycle->id,
            'njangi_session_id'     => $juneSession->id,
            'contributor_member_id' => $rakib->id,
            'beneficiary_member_id' => $agnes->id,
            'payment_submission_id' => $approvedSubmission->id,
            'amount'                => 200.00,
            'payment_date'          => '2026-06-11',
            'payment_method'        => 'zelle',
            'notes'                 => 'Auto-created from approved Njangi payment submission.',
        ]);

        NjangiContribution::create([
            'organization_id'       => $org->id,
            'njangi_cycle_id'       => $thirdCycle->id,
            'njangi_session_id'     => $juneSession->id,
            'contributor_member_id' => $rakib->id,
            'beneficiary_member_id' => $rakib->id,
            'payment_submission_id' => $approvedSubmission->id,
            'amount'                => 200.00,
            'payment_date'          => '2026-06-11',
            'payment_method'        => 'zelle',
            'notes'                 => 'Auto-created from approved Njangi payment submission.',
        ]);

        // Submission 2: $400 — REJECTED
        NjangiPaymentSubmission::create([
            'organization_id'   => $org->id,
            'member_id'         => $rakib->id,
            'njangi_cycle_id'   => $thirdCycle->id,
            'njangi_session_id' => $juneSession->id,
            'amount'            => 400.00,
            'is_attending'      => false,
            'screenshot_path'   => 'screenshots/placeholder.jpg',
            'status'            => 'rejected',
            'reviewed_by'       => null,
            'submitted_at'      => '2026-06-11 07:00:00',
            'reviewed_at'       => '2026-06-11 08:30:00',
            'member_note'       => null,
            'review_note'       => 'Duplicate submission.',
        ]);
    }
}
