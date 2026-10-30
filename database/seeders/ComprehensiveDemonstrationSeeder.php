<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\MemberRank;
use App\Models\MemberRole;
use App\Models\User;
use App\Models\Member;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\NjangiSessionBeneficiary;
use App\Models\NjangiContribution;
use App\Models\NjangiPaymentSubmission;
use App\Models\NjangiDisbursement;
use App\Models\SavingsTransaction;
use App\Models\SavingsDepositRequest;
use App\Models\SavingsWithdrawalRequest;
use App\Models\LoanSubStatus;
use App\Models\LoanRequest;
use App\Models\LoanGuarantor;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentRequest;
use App\Models\MeetingAttendance;
use App\Models\MeetingGuest;
use App\Models\AttendanceAuditLog;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ComprehensiveDemonstrationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset Spatie Cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Ensure Sample Proof Images Exist
        $this->ensureSampleProofImages();

        // 3. Organization Setup
        $org = Organization::firstOrCreate(
            ['name' => 'NFUH DMV'],
            [
                'email' => 'info@nfuhdmv.org',
                'phone' => '(240) 555-0199',
                'address' => '8401 Colesville Rd, Silver Spring, MD 20910',
            ]
        );

        // 4. Application Settings
        Setting::updateOrCreate(
            ['id' => 1],
            [
                'organization_id' => $org->id,
                'app_name' => 'NFUH DMV',
                'beneficiary_count' => 1,
                'savings_withdrawal_fee' => 2.00,
                'default_loan_interest' => 5.00,
                'min_savings_for_loan' => 200.00,
                'loan_guarantor_min' => 1,
                'loan_guarantor_max' => 3,
                'mid_cycle_payout_policy' => 'full',
                'session_schedule_frequency' => 'monthly',
                'session_schedule_day' => 'first_saturday',
                'session_schedule_start_time' => '17:00:00',
                'session_schedule_end_time' => '21:00:00',
                'session_schedule_timezone' => 'America/New_York',
                'restrict_checkin_to_cycle_members' => false,
                'theme_primary_color' => '#10b981',
                'theme_secondary_color' => '#d97706',
            ]
        );

        // 5. Seed Permissions and Spatie Roles
        (new RolesAndPermissionsSeeder())->run();

        // 6. Traditional Cultural Titles (MemberRank)
        $rankNames = [
            'HRH'      => 'His Royal Highness - Traditional Paramount Ruler',
            'Nformi'   => 'Warrior title and honorary elder custodian',
            'Tamfuh'   => 'Chief counselor of traditional customs',
            'Tar Kifu' => 'Custodian of cultural protocol and lineage',
            'Shey'     => 'Respected title of cultural heritage',
            'Ngwaye'   => 'Female noble title of community distinction',
            'Mfaw'     => 'Lineage clan chief',
            'Elder'    => 'Senior council elder',
        ];
        $ranks = [];
        foreach ($rankNames as $name => $desc) {
            $ranks[$name] = MemberRank::firstOrCreate(['name' => $name]);
        }

        // 7. Administrative/Governance Roles (MemberRole)
        $roleNames = [
            'Secretary',
            'Treasurer',
            'Financial Secretary',
            'Loan Officer',
            'Loan Committee',
            'Lead Nformi',
        ];
        $memberRoles = [];
        foreach ($roleNames as $rName) {
            $memberRoles[$rName] = MemberRole::firstOrCreate(['name' => $rName]);
        }

        // 8. Define 30 Distinct Realistic Members (Unified Password: Password123!)
        $membersData = [
            // Executive Officers (1 per role)
            [
                'first_name' => 'Julius', 'last_name' => 'Nfor', 'email' => 'admin@nfuhdmv.org',
                'phone' => '2405550001', 'state' => 'MD', 'title' => 'Elder',
                'spatie_role' => 'admin', 'member_role' => null, 'is_admin' => true,
            ],
            [
                'first_name' => 'Julius', 'last_name' => 'Talla', 'email' => 'treasurer@nfuhdmv.org',
                'phone' => '2405550002', 'state' => 'MD', 'title' => 'Nformi',
                'spatie_role' => 'Treasurer', 'member_role' => 'Treasurer',
            ],
            [
                'first_name' => 'Agnes', 'last_name' => 'Ndzi', 'email' => 'finsec@nfuhdmv.org',
                'phone' => '2405550003', 'state' => 'MD', 'title' => 'Ngwaye',
                'spatie_role' => 'Financial Secretary', 'member_role' => 'Financial Secretary',
            ],
            [
                'first_name' => 'Emmanuel', 'last_name' => 'Fon', 'email' => 'secretary@nfuhdmv.org',
                'phone' => '2405550004', 'state' => 'DC', 'title' => 'Tamfuh',
                'spatie_role' => 'General Secretary', 'member_role' => 'Secretary',
            ],
            [
                'first_name' => 'Brenda', 'last_name' => 'Bih', 'email' => 'committee1@nfuhdmv.org',
                'phone' => '2405550005', 'state' => 'VA', 'title' => 'Shey',
                'spatie_role' => 'Loan Committee', 'member_role' => 'Loan Committee',
            ],
            [
                'first_name' => 'Paul', 'last_name' => 'Ngwang', 'email' => 'committee2@nfuhdmv.org',
                'phone' => '2405550006', 'state' => 'VA', 'title' => 'Tar Kifu',
                'spatie_role' => 'Loan Committee', 'member_role' => 'Loan Committee',
            ],
            [
                'first_name' => 'George', 'last_name' => 'Chi', 'email' => 'loanofficer@nfuhdmv.org',
                'phone' => '2405550007', 'state' => 'MD', 'title' => 'Nformi',
                'spatie_role' => 'Loan Committee', 'member_role' => 'Loan Officer',
            ],
            [
                'first_name' => 'Peter', 'last_name' => 'Nkwenti', 'email' => 'lead.nformi@nfuhdmv.org',
                'phone' => '2405550008', 'state' => 'MD', 'title' => 'HRH',
                'spatie_role' => 'member', 'member_role' => 'Lead Nformi',
            ],
            // Traditional Dignitaries
            [
                'first_name' => 'Michael', 'last_name' => 'Kinyuy', 'email' => 'hrh.kinyuy@nfuhdmv.org',
                'phone' => '2405550009', 'state' => 'DC', 'title' => 'HRH',
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Victorine', 'last_name' => 'Beri', 'email' => 'victorine.beri@nfuhdmv.org',
                'phone' => '2405550010', 'state' => 'MD', 'title' => 'Mfaw',
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'David', 'last_name' => 'Wirngo', 'email' => 'david.wirngo@nfuhdmv.org',
                'phone' => '2405550011', 'state' => 'VA', 'title' => 'Shey',
                'spatie_role' => 'member', 'member_role' => null,
            ],
            // General Community Members
            [
                'first_name' => 'Sarah', 'last_name' => 'Fuh', 'email' => 'sarah.fuh@nfuhdmv.org',
                'phone' => '2405550012', 'state' => 'MD', 'title' => 'Ngwaye',
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Alice', 'last_name' => 'Nfor', 'email' => 'alice.nfor@nfuhdmv.org',
                'phone' => '2405550013', 'state' => 'MD', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Linda', 'last_name' => 'Tamfuh', 'email' => 'linda.tamfuh@nfuhdmv.org',
                'phone' => '2405550014', 'state' => 'DC', 'title' => 'Tamfuh',
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Mercy', 'last_name' => 'Lem', 'email' => 'mercy.lem@nfuhdmv.org',
                'phone' => '2405550015', 'state' => 'MD', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Collins', 'last_name' => 'Buma', 'email' => 'collins.buma@nfuhdmv.org',
                'phone' => '2405550016', 'state' => 'VA', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Roland', 'last_name' => 'Che', 'email' => 'roland.che@nfuhdmv.org',
                'phone' => '2405550017', 'state' => 'MD', 'title' => 'Tamfuh',
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Florence', 'last_name' => 'Lum', 'email' => 'florence.lum@nfuhdmv.org',
                'phone' => '2405550018', 'state' => 'DC', 'title' => 'Ngwaye',
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Patrick', 'last_name' => 'Kongnyuy', 'email' => 'patrick.k@nfuhdmv.org',
                'phone' => '2405550019', 'state' => 'VA', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Grace', 'last_name' => 'Mbah', 'email' => 'grace.mbah@nfuhdmv.org',
                'phone' => '2405550020', 'state' => 'MD', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Anthony', 'last_name' => 'Tabot', 'email' => 'anthony.tabot@nfuhdmv.org',
                'phone' => '2405550021', 'state' => 'MD', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Helen', 'last_name' => 'Mankaa', 'email' => 'helen.mankaa@nfuhdmv.org',
                'phone' => '2405550022', 'state' => 'VA', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Samuel', 'last_name' => 'Ndive', 'email' => 'samuel.ndive@nfuhdmv.org',
                'phone' => '2405550023', 'state' => 'DC', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Evelyn', 'last_name' => 'Bih', 'email' => 'evelyn.bih@nfuhdmv.org',
                'phone' => '2405550024', 'state' => 'MD', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Joseph', 'last_name' => 'Fai', 'email' => 'joseph.fai@nfuhdmv.org',
                'phone' => '2405550025', 'state' => 'VA', 'title' => 'Shey',
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Beatrice', 'last_name' => 'Nying', 'email' => 'beatrice.nying@nfuhdmv.org',
                'phone' => '2405550026', 'state' => 'MD', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Christopher', 'last_name' => 'Ndifor', 'email' => 'christopher.n@nfuhdmv.org',
                'phone' => '2405550027', 'state' => 'DC', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Daniel', 'last_name' => 'Mbiydzenyuy', 'email' => 'daniel.m@nfuhdmv.org',
                'phone' => '2405550028', 'state' => 'MD', 'title' => null,
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Bernard', 'last_name' => 'Ndum', 'email' => 'bernard.ndum@nfuhdmv.org',
                'phone' => '2405550029', 'state' => 'VA', 'title' => 'Tar Kifu',
                'spatie_role' => 'member', 'member_role' => null,
            ],
            [
                'first_name' => 'Sylvia', 'last_name' => 'Chenwi', 'email' => 'sylvia.chenwi@nfuhdmv.org',
                'phone' => '2405550030', 'state' => 'MD', 'title' => 'Ngwaye',
                'spatie_role' => 'member', 'member_role' => null,
            ],
        ];

        $users = [];
        $members = [];
        $passwordHash = Hash::make('Password123!');

        foreach ($membersData as $idx => $data) {
            $user = User::create([
                'name' => "{$data['first_name']} {$data['last_name']}",
                'email' => $data['email'],
                'password' => $passwordHash,
                'organization_id' => $org->id,
            ]);

            // Ensure Spatie role exists before assignment
            Role::firstOrCreate(['name' => $data['spatie_role'], 'guard_name' => 'web']);
            $user->assignRole($data['spatie_role']);

            // Create Member Record
            $seq = str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT);
            $memberCode = "{$data['state']}-2024-{$seq}";

            $member = Member::create([
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'member_code' => $memberCode,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'rank_id' => $data['title'] ? ($ranks[$data['title']]->id ?? null) : null,
                'status' => 'active',
                'address' => "Suite 100, {$data['state']} Corridor",
                'state_code' => $data['state'],
                'join_date' => Carbon::create(2024, 1, 15)->addDays($idx * 4)->toDateString(),
                'participates_in_njangi' => true,
                'participates_in_savings' => true,
                'next_of_kin_name' => "Kin of {$data['first_name']}",
                'next_of_kin_phone' => '2405559999',
                'next_of_kin_email' => "kin.{$data['first_name']}@example.com",
                'next_of_kin_address' => "{$data['state']} Resident",
            ]);

            // Assign Member Role if any
            if (!empty($data['member_role']) && isset($memberRoles[$data['member_role']])) {
                $member->roles()->sync([$memberRoles[$data['member_role']]->id]);
            }

            $users[$data['email']] = $user;
            $members[$data['email']] = $member;
        }

        $activeMembersList = array_values($members);
        $treasurerUser = $users['treasurer@nfuhdmv.org'];
        $finSecUser = $users['finsec@nfuhdmv.org'];
        $adminUser = $users['admin@nfuhdmv.org'];

        // 9. Loan Sub-Statuses
        $subStatuses = [
            'Under Initial Review'              => '#3b82f6', // blue
            'Awaiting Guarantor Signatures'     => '#f59e0b', // amber
            'Committee Deliberation'            => '#8b5cf6', // purple
            'Ready for Disbursement'            => '#10b981', // emerald
            'Repayment Active - Good Standing'  => '#06b6d4', // cyan
            'Overdue Notice Sent'               => '#ef4444', // red
        ];
        $seededSubStatuses = [];
        foreach ($subStatuses as $sName => $sColor) {
            $seededSubStatuses[$sName] = LoanSubStatus::firstOrCreate([
                'name' => $sName,
            ], ['color' => $sColor]);
        }

        // 10. Seed Njangi Cycles & Sessions
        $activeCycle = NjangiCycle::create([
            'organization_id' => $org->id,
            'name' => '2026 Gold Executive Cycle',
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'notes' => 'Primary active 2026 rotational savings and credit assembly.',
        ]);

        $upcomingCycle1 = NjangiCycle::create([
            'organization_id' => $org->id,
            'name' => '2027 Diamond Premier Cycle',
            'year' => 2027,
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 'upcoming',
            'notes' => 'Upcoming 2027 cycle open for advance registration.',
        ]);

        $upcomingCycle2 = NjangiCycle::create([
            'organization_id' => $org->id,
            'name' => '2027 Community Development Cycle',
            'year' => 2027,
            'start_date' => '2027-06-01',
            'end_date' => '2028-05-31',
            'status' => 'upcoming',
            'notes' => 'Mid-year community development group cycle.',
        ]);

        $completedCycle = NjangiCycle::create([
            'organization_id' => $org->id,
            'name' => '2025 Inaugural Annual Cycle',
            'year' => 2025,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'completed',
            'notes' => 'Completed 2025 inaugural cycle.',
        ]);

        // Enroll 24 members in 2026 Active Cycle with Benefit Orders
        $cycleMembers = [];
        for ($i = 0; $i < 24; $i++) {
            $m = $activeMembersList[$i];
            $cm = NjangiCycleMember::create([
                'njangi_cycle_id' => $activeCycle->id,
                'member_id' => $m->id,
                'benefit_order' => $i + 1,
                'subscription_amount' => 100.00,
                'is_active' => true,
            ]);
            $cycleMembers[$m->id] = $cm;
        }

        // Generate 12 Sessions for 2026 Active Cycle (First Saturday of each month)
        $sessionDates = [
            1  => '2026-01-03',
            2  => '2026-02-07',
            3  => '2026-03-07',
            4  => '2026-04-04',
            5  => '2026-05-02',
            6  => '2026-06-06',
            7  => '2026-07-04',
            8  => '2026-08-01',
            9  => '2026-09-05',
            10 => '2026-10-03',
            11 => '2026-11-07', // Upcoming active session (Saturday, Nov 7, 2026)
            12 => '2026-12-05',
        ];

        $sessions = [];
        foreach ($sessionDates as $num => $sDate) {
            $isPast = $num <= 10;
            $isCurrent = $num === 11;
            $status = $isPast ? 'closed' : ($isCurrent ? 'open' : 'scheduled');

            $monthName = Carbon::parse($sDate)->format('F');
            $session = NjangiSession::create([
                'organization_id' => $org->id,
                'njangi_cycle_id' => $activeCycle->id,
                'session_number' => $num,
                'session_date' => $sDate,
                'title' => "{$monthName} 2026 Assembly (Session #{$num})",
                'notes' => $isPast ? "Assembly conducted successfully on {$sDate}." : "Official monthly assembly session.",
                'status' => $status,
                'qr_token' => Str::random(32),
                'check_in_opens_at' => '17:00:00',
                'check_in_closes_at' => '21:00:00',
                'check_in_timezone' => 'America/New_York',
            ]);
            $sessions[$num] = $session;

            // Assign Beneficiary according to benefit order
            $beneficiaryMember = $activeMembersList[$num - 1] ?? $activeMembersList[0];
            $cm = $cycleMembers[$beneficiaryMember->id] ?? null;

            if ($cm) {
                $sessionBeneficiary = NjangiSessionBeneficiary::create([
                    'organization_id' => $org->id,
                    'njangi_session_id' => $session->id,
                    'njangi_cycle_member_id' => $cm->id,
                    'beneficiary_slot' => 1,
                    'benefit_order' => $num,
                    'notes' => "Official rotational beneficiary for Session #{$num}.",
                ]);

                // Record past disbursements
                if ($isPast) {
                    NjangiDisbursement::create([
                        'organization_id' => $org->id,
                        'njangi_session_id' => $session->id,
                        'njangi_session_beneficiary_id' => $sessionBeneficiary->id,
                        'njangi_cycle_member_id' => $cm->id,
                        'gross_amount' => 2400.00,
                        'loan_deduction' => 0.00,
                        'penalty_deduction' => 0.00,
                        'other_deduction' => 0.00,
                        'net_amount' => 2400.00,
                        'disbursement_date' => $sDate,
                        'status' => 'paid',
                        'payment_method' => 'bank_wire',
                        'reference_number' => 'DISB-' . Carbon::parse($sDate)->format('Ymd') . "-00{$num}",
                        'notes' => "Disbursed in full by Treasurer Julius Talla via direct ACH wire.",
                    ]);
                }
            }

            // Seed Past Contributions & Payment Submissions
            if ($isPast) {
                foreach (array_slice($activeMembersList, 0, 18) as $contributor) {
                    $sub = NjangiPaymentSubmission::create([
                        'organization_id' => $org->id,
                        'member_id' => $contributor->id,
                        'njangi_cycle_id' => $activeCycle->id,
                        'njangi_session_id' => $session->id,
                        'amount' => 100.00,
                        'is_attending' => true,
                        'screenshot_path' => 'screenshots/sample_receipt.png',
                        'status' => 'approved',
                        'reviewed_by' => $finSecUser->id,
                        'submitted_at' => Carbon::parse($sDate)->subHours(3),
                        'reviewed_at' => Carbon::parse($sDate)->subHour(),
                        'member_note' => 'Contribution for monthly assembly.',
                        'review_note' => 'Payment verified by Financial Secretary Agnes Ndzi.',
                    ]);

                    NjangiContribution::create([
                        'organization_id' => $org->id,
                        'njangi_cycle_id' => $activeCycle->id,
                        'njangi_session_id' => $session->id,
                        'contributor_member_id' => $contributor->id,
                        'beneficiary_member_id' => $beneficiaryMember->id,
                        'payment_submission_id' => $sub->id,
                        'amount' => 100.00,
                        'payment_date' => $sDate,
                        'payment_method' => 'zelle',
                        'reference_number' => 'NJ-CONT-' . Carbon::parse($sDate)->format('Ym') . "-{$contributor->id}",
                        'notes' => 'Rotational monthly pot credit.',
                    ]);
                }

                // Meeting Attendances for Past Session across ALL statuses (present, excused, absent)
                foreach ($activeMembersList as $idx => $attendingMember) {
                    $attStatus = match ($idx % 3) {
                        0 => 'present',
                        1 => 'excused',
                        2 => 'absent',
                    };
                    $isQr = ($attStatus === 'present' && $idx % 2 === 0);

                    MeetingAttendance::create([
                        'organization_id' => $org->id,
                        'njangi_session_id' => $session->id,
                        'member_id' => $attendingMember->id,
                        'attendance_status' => $attStatus,
                        'check_in_source' => $isQr ? 'qr_self_check_in' : 'officer_manual',
                        'check_in_at' => $attStatus === 'present' ? Carbon::parse($sDate)->setTime(17, 15 + ($idx % 45)) : null,
                        'verified_by' => $users['secretary@nfuhdmv.org']->id,
                        'verified_at' => Carbon::parse($sDate)->setTime(18, 00),
                        'officer_notes' => $attStatus === 'excused' ? 'Official excused notice received.' : null,
                    ]);
                }
            }
        }

        // Cancelled session demonstration in 2025 cycle (Demonstrating cancelled status)
        NjangiSession::create([
            'organization_id' => $org->id,
            'njangi_cycle_id' => $completedCycle->id,
            'session_number' => 13,
            'session_date' => '2025-12-20',
            'title' => 'Emergency Extraordinary Session (Cancelled)',
            'notes' => 'Session cancelled due to severe blizzard advisory and weather emergency.',
            'status' => 'cancelled',
            'qr_token' => Str::random(32),
            'check_in_opens_at' => '17:00:00',
            'check_in_closes_at' => '21:00:00',
            'check_in_timezone' => 'America/New_York',
        ]);

        // Njangi Payment Submissions for Upcoming Session #11 across ALL statuses (15 pending, 15 approved, 15 rejected)
        $novSession = $sessions[11];
        // 15 Pending Submissions
        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i];
            NjangiPaymentSubmission::create([
                'organization_id' => $org->id,
                'member_id' => $m->id,
                'njangi_cycle_id' => $activeCycle->id,
                'njangi_session_id' => $novSession->id,
                'amount' => 100.00,
                'is_attending' => true,
                'screenshot_path' => 'screenshots/sample_receipt.png',
                'status' => 'pending',
                'submitted_at' => now()->subHours($i + 1),
                'member_note' => "Advance payment for Nov 7 Assembly via Zelle reference #ZEL-{$i}00.",
            ]);
        }

        // 15 Approved Submissions for Upcoming Session #11
        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[($i + 5) % count($activeMembersList)];
            NjangiPaymentSubmission::create([
                'organization_id' => $org->id,
                'member_id' => $m->id,
                'njangi_cycle_id' => $activeCycle->id,
                'njangi_session_id' => $novSession->id,
                'amount' => 100.00,
                'is_attending' => true,
                'screenshot_path' => 'screenshots/sample_receipt.png',
                'status' => 'approved',
                'reviewed_by' => $finSecUser->id,
                'submitted_at' => now()->subDays(3),
                'reviewed_at' => now()->subDays(2),
                'member_note' => "Early advance payment for Nov 7 assembly.",
                'review_note' => "Payment verified by Financial Secretary.",
            ]);
        }

        // 15 Rejected Submissions (with audit notes)
        for ($i = 15; $i < 30; $i++) {
            $m = $activeMembersList[$i];
            NjangiPaymentSubmission::create([
                'organization_id' => $org->id,
                'member_id' => $m->id,
                'njangi_cycle_id' => $activeCycle->id,
                'njangi_session_id' => $novSession->id,
                'amount' => 50.00,
                'is_attending' => false,
                'screenshot_path' => 'screenshots/sample_receipt.png',
                'status' => 'rejected',
                'reviewed_by' => $finSecUser->id,
                'submitted_at' => now()->subDays(2),
                'reviewed_at' => now()->subDay(),
                'member_note' => 'Partial payment submission.',
                'review_note' => 'Full monthly subscription of $100.00 required. Please resubmit balance.',
            ]);
        }

        // 15 Guests in Meetings
        for ($g = 1; $g <= 15; $g++) {
            MeetingGuest::create([
                'organization_id' => $org->id,
                'njangi_session_id' => $sessions[min(10, $g)]->id,
                'guest_name' => "Community Guest #{$g} (Dignitary)",
                'guest_phone' => "240555800{$g}",
                'guest_email' => "guest.{$g}@example.com",
                'invited_by_member_id' => $activeMembersList[$g]->id,
                'invited_by_name' => $activeMembersList[$g]->name,
                'check_in_at' => Carbon::parse($sessionDates[min(10, $g)])->setTime(17, 30 + $g),
            ]);
        }

        // 15 Attendance Audit Logs
        for ($a = 1; $a <= 15; $a++) {
            AttendanceAuditLog::create([
                'organization_id' => $org->id,
                'njangi_session_id' => $sessions[min(10, $a)]->id,
                'member_id' => $activeMembersList[$a]->id,
                'changed_by' => $users['secretary@nfuhdmv.org']->id,
                'action' => 'verified_attendance',
                'previous_status' => null,
                'new_status' => 'present',
                'source' => 'officer_manual',
                'reason' => 'Officer roll call verification at meeting hall.',
            ]);
        }

        // 11. Seed Savings Module (ALL 6 TRANSACTION BADGES + DEPOSIT & WITHDRAWAL REQUEST STATUSES)
        // First establish starting base balances for all 30 members
        $memberBalances = [];
        foreach ($activeMembersList as $idx => $m) {
            $baseBal = 500.00 + ($idx * 100.00); // Between $500 and $3,500
            $memberBalances[$m->id] = $baseBal;

            // 15 Manual Deposits (type = deposit, is_manual = true)
            if ($idx < 15) {
                $depAmount = 200.00 + ($idx * 20.00);
                $prev = $memberBalances[$m->id];
                $new = $prev + $depAmount;
                SavingsTransaction::create([
                    'member_id' => $m->id,
                    'organization_id' => $org->id,
                    'amount' => $depAmount,
                    'previous_balance' => $prev,
                    'new_balance' => $new,
                    'type' => 'deposit',
                    'status' => 'approved',
                    'transaction_date' => '2026-02-15',
                    'notes' => "Manual cash/check deposit received by officer at meeting.",
                    'reviewed_by' => $treasurerUser->id,
                    'reviewed_at' => Carbon::create(2026, 2, 15, 12, 0),
                ]);
                $memberBalances[$m->id] = $new;
            }

            // 15 Portal Deposits (type = deposit, is_manual = false)
            if ($idx >= 15) {
                $depAmount = 250.00 + (($idx - 15) * 15.00);
                $prev = $memberBalances[$m->id];
                $new = $prev + $depAmount;
                SavingsTransaction::create([
                    'member_id' => $m->id,
                    'organization_id' => $org->id,
                    'amount' => $depAmount,
                    'previous_balance' => $prev,
                    'new_balance' => $new,
                    'type' => 'deposit',
                    'status' => 'approved',
                    'transaction_date' => '2026-03-20',
                    'notes' => "Approved deposit request #DEP-REQ-2026-{$idx} via member portal (Zelle).",
                    'reviewed_by' => $finSecUser->id,
                    'reviewed_at' => Carbon::create(2026, 3, 20, 15, 0),
                ]);
                $memberBalances[$m->id] = $new;
            }
        }

        // 15 Adjustments (Balance Overrides) with Detailed Justifications
        $adjustmentReasons = [
            'Annual interest distribution dividend credit per AGM resolution #2026-02.',
            'Bank statement reconciliation credit for reversed international wire transfer fee.',
            'Executive committee audit correction for uncredited cash deposit dated 2026-04-12.',
            'Promotional savings campaign matching bonus credited by union executive resolution.',
            'Special bereavement benevolent reserve fund balance allocation.',
            'End-of-quarter audit adjustment authorized by Board of Trustees.',
            'Reconciliation of duplicate deposit fee correction credited per ledger audit.',
            'Discrepancy adjustment per quarterly financial secretary review.',
            'Mid-year dividend bonus approved by general assembly.',
            'Reversed bank return charge credit authorized by treasurer.',
            'Benevolent fund special relief credit per council deliberation.',
            'Founding member long-service dividend allocation authorized by president.',
            'Audit reconciliation credit for unrecorded offline donation deposit.',
            'Capital reserve dividend distribution credit approved by executive council.',
            'Annual general meeting dividend distribution authorized by steering committee.',
        ];

        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i];
            $adjAmount = 75.00 + ($i * 10.00);
            $prev = $memberBalances[$m->id];
            $new = $prev + $adjAmount;

            SavingsTransaction::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'amount' => $adjAmount,
                'previous_balance' => $prev,
                'new_balance' => $new,
                'type' => 'adjustment',
                'status' => 'approved',
                'transaction_date' => Carbon::create(2026, 6, 30)->toDateString(),
                'notes' => $adjustmentReasons[$i],
                'reviewed_by' => $treasurerUser->id,
                'reviewed_at' => Carbon::create(2026, 6, 30, 18, 0),
            ]);
            $memberBalances[$m->id] = $new;
        }

        // 15 Manual Withdrawals (type = withdrawal, is_manual = true)
        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i + 5];
            $wAmount = 100.00 + ($i * 10.00);
            $prev = $memberBalances[$m->id];
            $new = max(50.00, $prev - $wAmount);

            SavingsTransaction::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'amount' => $wAmount,
                'previous_balance' => $prev,
                'new_balance' => $new,
                'type' => 'withdrawal',
                'status' => 'approved',
                'transaction_date' => Carbon::create(2026, 7, 15)->toDateString(),
                'notes' => "Manual withdrawal over-the-counter cash payout authorized by officer.",
                'reviewed_by' => $treasurerUser->id,
                'reviewed_at' => Carbon::create(2026, 7, 15, 14, 0),
            ]);
            $memberBalances[$m->id] = $new;
        }

        // 15 Disbursed Portal Withdrawals (type = withdrawal, is_manual = false)
        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i + 10];
            $gross = 200.00 + ($i * 15.00);
            $fee = round($gross * 0.02, 2);
            $net = $gross - $fee;

            $prev = $memberBalances[$m->id];
            $new = max(50.00, $prev - $gross);

            $notes = "Member withdrawal payout ($" . number_format($net, 2) . ") + 2.00% Admin Fee ($" . number_format($fee, 2) . ") via Bank Transfer (Ref: TRF-2026-{$i})";

            $tx = SavingsTransaction::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'amount' => $gross,
                'previous_balance' => $prev,
                'new_balance' => $new,
                'type' => 'withdrawal',
                'status' => 'approved',
                'transaction_date' => Carbon::create(2026, 8, 20)->toDateString(),
                'notes' => $notes,
                'reviewed_by' => $treasurerUser->id,
                'reviewed_at' => Carbon::create(2026, 8, 20, 16, 0),
            ]);
            $memberBalances[$m->id] = $new;

            SavingsWithdrawalRequest::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'gross_amount' => $gross,
                'fee_rate' => 2.00,
                'fee_amount' => $fee,
                'net_amount' => $net,
                'payout_method' => 'bank_transfer',
                'payout_details' => "Bank of America | Acc: ****{$m->phone} | Routing: ****0012",
                'notes' => 'Portal electronic withdrawal request.',
                'status' => 'disbursed',
                'reviewed_by' => $adminUser->id,
                'reviewed_at' => Carbon::create(2026, 8, 19, 10, 0),
                'disbursed_by_user_id' => $treasurerUser->id,
                'disbursed_date' => '2026-08-20',
                'disbursed_at' => Carbon::create(2026, 8, 20, 16, 0),
                'disbursement_method' => 'bank_transfer',
                'disbursement_destination' => 'Verified member ACH destination',
                'disbursement_reference' => "TRF-2026-{$i}",
                'disbursement_proof' => 'savings_disbursements/sample_disbursement.png',
                'disbursement_details' => 'ACH Transfer executed via Union Treasury portal.',
                'savings_transaction_id' => $tx->id,
            ]);
        }

        // 15 Pending Payout Withdrawals (status = pending in savings_transactions, approved in requests)
        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i + 8];
            $gross = 150.00 + ($i * 10.00);
            $fee = round($gross * 0.02, 2);
            $net = $gross - $fee;
            $prev = $memberBalances[$m->id];

            $wReq = SavingsWithdrawalRequest::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'gross_amount' => $gross,
                'fee_rate' => 2.00,
                'fee_amount' => $fee,
                'net_amount' => $net,
                'payout_method' => 'zelle',
                'payout_details' => "Zelle: {$m->phone}",
                'notes' => 'Approved by committee, queued in Treasurer disbursement queue.',
                'status' => 'approved',
                'reviewed_by' => $adminUser->id,
                'reviewed_at' => now()->subHours($i + 1),
                'admin_notes' => 'Approved for payout.',
            ]);

            $tx = SavingsTransaction::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'amount' => $gross,
                'previous_balance' => $prev,
                'new_balance' => max(0, $prev - $gross),
                'type' => 'withdrawal',
                'status' => 'pending',
                'transaction_date' => now()->toDateString(),
                'notes' => "Member withdrawal payout reserved pending disbursement by Treasurer.",
                'reviewed_by' => $adminUser->id,
                'reviewed_at' => now()->subHours($i + 1),
            ]);

            $wReq->update(['savings_transaction_id' => $tx->id]);
        }

        // 15 Rejected Savings Transactions (Direct reversals, dishonored deposit checks, rejected payout attempts)
        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i];
            $prev = $memberBalances[$m->id];
            $isDeposit = ($i % 2 === 0);

            SavingsTransaction::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'amount' => 125.00 + ($i * 15.00),
                'previous_balance' => $prev,
                'new_balance' => $prev,
                'type' => $isDeposit ? 'deposit' : 'withdrawal',
                'status' => 'rejected',
                'transaction_date' => Carbon::create(2026, 5, 5 + $i)->toDateString(),
                'notes' => $isDeposit
                    ? "Dishonored check deposit returned unpaid by drawer institution (RTN-2026-{$i})."
                    : "Withdrawal rejected due to invalid recipient routing information or account closure.",
                'reviewed_by' => $treasurerUser->id,
                'reviewed_at' => Carbon::create(2026, 5, 6 + $i, 14, 0),
            ]);
        }

        // 15 Pending Withdrawal Requests (awaiting initial review)
        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i];
            $gross = 120.00 + ($i * 10.00);
            $fee = round($gross * 0.02, 2);
            $net = $gross - $fee;

            SavingsWithdrawalRequest::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'gross_amount' => $gross,
                'fee_rate' => 2.00,
                'fee_amount' => $fee,
                'net_amount' => $net,
                'payout_method' => 'zelle',
                'payout_details' => "Zelle ID: {$m->phone}",
                'notes' => 'New withdrawal application submitted by member.',
                'status' => 'pending',
                'created_at' => now()->subHours($i + 2),
            ]);
        }

        // 15 Rejected Withdrawal Requests
        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i + 15];
            $gross = 1200.00;
            $fee = round($gross * 0.02, 2);
            $net = $gross - $fee;

            SavingsWithdrawalRequest::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'gross_amount' => $gross,
                'fee_rate' => 2.00,
                'fee_amount' => $fee,
                'net_amount' => $net,
                'payout_method' => 'zelle',
                'payout_details' => "Zelle: {$m->phone}",
                'notes' => 'Excessive withdrawal request.',
                'status' => 'rejected',
                'reviewed_by' => $adminUser->id,
                'reviewed_at' => now()->subDays(3),
                'rejection_reason' => 'Requested amount exceeds allowable unencumbered savings balance.',
            ]);
        }

        // Savings Deposit Requests Queue: 15 Pending, 15 Approved, 15 Rejected
        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i + 2];
            SavingsDepositRequest::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'amount' => 200.00 + ($i * 25.00),
                'status' => 'pending',
                'screenshot_path' => 'savings_proofs/sample_deposit.png',
                'notes' => 'Zelle deposit sent to union account, awaiting verification.',
                'submitted_at' => now()->subHours($i + 1),
            ]);
        }

        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i + 15];
            SavingsDepositRequest::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'amount' => 300.00 + ($i * 20.00),
                'status' => 'approved',
                'screenshot_path' => 'savings_proofs/sample_deposit.png',
                'notes' => 'Mobile check deposit.',
                'submitted_at' => now()->subDays(5),
                'reviewed_by' => $finSecUser->id,
                'reviewed_at' => now()->subDays(4),
                'review_note' => 'Funds cleared in bank account.',
            ]);
        }

        for ($i = 0; $i < 15; $i++) {
            $m = $activeMembersList[$i + 8];
            SavingsDepositRequest::create([
                'member_id' => $m->id,
                'organization_id' => $org->id,
                'amount' => 500.00,
                'status' => 'rejected',
                'screenshot_path' => 'savings_proofs/sample_deposit.png',
                'notes' => 'Bank counter deposit slip.',
                'submitted_at' => now()->subDays(6),
                'reviewed_by' => $finSecUser->id,
                'reviewed_at' => now()->subDays(5),
                'review_note' => 'Check image unreadable or check was returned by issuing institution.',
            ]);
        }

        // 12. Seed Loans Module (ALL 7 LOAN STATUSES WITH 15 RECORDS EACH = 105 LOANS ACROSS 30 MEMBERS!)
        $allLoanStatuses = [
            'pending_guarantors' => 15, // Awaiting guarantor signatures
            'pending_committee'  => 15, // Under committee review
            'approved'           => 15, // Approved, awaiting disbursement
            'active'             => 15, // Disbursed, in repayment
            'completed'          => 15, // Fully paid off (repaid)
            'defaulted'          => 15, // Past due date
            'rejected'           => 15, // Denied by committee
        ];

        $loanGlobalIdx = 1;
        $activeLoansList = [];

        foreach ($allLoanStatuses as $st => $count) {
            for ($k = 0; $k < $count; $k++) {
                $borrower = $activeMembersList[($loanGlobalIdx + $k) % count($activeMembersList)];
                $principal = 1000.00 + ($k * 200.00);
                $duration = 6;
                $interestRate = 5.0;

                $subStatusId = match ($st) {
                    'pending_guarantors' => $seededSubStatuses['Awaiting Guarantor Signatures']->id,
                    'pending_committee'  => $seededSubStatuses['Committee Deliberation']->id,
                    'approved'           => $seededSubStatuses['Ready for Disbursement']->id,
                    'active'             => $seededSubStatuses['Repayment Active - Good Standing']->id,
                    'completed'          => null,
                    'defaulted'          => $seededSubStatuses['Overdue Notice Sent']->id,
                    'rejected'           => null,
                };

                $isDisbursed = in_array($st, ['active', 'completed', 'defaulted']);

                $loan = LoanRequest::create([
                    'member_id' => $borrower->id,
                    'organization_id' => $org->id,
                    'amount' => $principal,
                    'duration_months' => $duration,
                    'status' => $st,
                    'sub_status_id' => $subStatusId,
                    'purpose' => "Community credit facility for {$borrower->name} (Facility #{$loanGlobalIdx}).",
                    'interest_rate' => $interestRate,
                    'interest_type' => 'percentage',
                    'repayment_frequency' => 'monthly',
                    'payout_method' => 'zelle',
                    'payout_details' => "Zelle: {$borrower->phone}",
                    'admin_notes' => "Underwritten according to NFUH DMV Credit Standards (Loan #{$loanGlobalIdx}).",
                    'disbursed_at' => $isDisbursed ? Carbon::create(2026, 3, 1, 10, 0) : null,
                    'disbursed_date' => $isDisbursed ? '2026-03-01' : null,
                    'disbursed_by_user_id' => $isDisbursed ? $treasurerUser->id : null,
                    'disbursement_method' => $isDisbursed ? 'zelle' : null,
                    'disbursement_reference' => $isDisbursed ? "LN-REF-2026-00{$loanGlobalIdx}" : null,
                    'disbursement_proof' => $isDisbursed ? 'loan_disbursements/sample_loan_voucher.png' : null,
                    'disbursement_details' => $isDisbursed ? 'Zelle disbursement processed from Operating Account.' : null,
                    'repayment_due_date' => $isDisbursed ? Carbon::create(2026, 9, 1)->toDateString() : null,
                    'rejection_reason' => $st === 'rejected' ? 'Debt obligations exceed maximum debt-to-savings ratio.' : null,
                ]);

                // Attach 2 Guarantors to each loan
                $guarantor1 = $activeMembersList[($loanGlobalIdx + $k + 1) % count($activeMembersList)];
                $guarantor2 = $activeMembersList[($loanGlobalIdx + $k + 2) % count($activeMembersList)];

                $g1Status = ($st === 'pending_guarantors' && $k % 2 === 0) ? 'pending' : 'accepted';
                $g2Status = ($st === 'pending_guarantors') ? 'pending' : (($st === 'rejected' && $k % 2 === 0) ? 'declined' : 'accepted');

                LoanGuarantor::create([
                    'loan_request_id' => $loan->id,
                    'guarantor_member_id' => $guarantor1->id,
                    'status' => $g1Status,
                    'terms_accepted' => ($g1Status === 'accepted'),
                    'terms_accepted_at' => ($g1Status === 'accepted') ? now()->subDays(20) : null,
                    'terms_ip_address' => '127.0.0.1',
                    'responded_at' => ($g1Status === 'accepted') ? now()->subDays(20) : null,
                    'notes' => 'Guarantor agreed to joint underwriting obligation.',
                ]);

                LoanGuarantor::create([
                    'loan_request_id' => $loan->id,
                    'guarantor_member_id' => $guarantor2->id,
                    'status' => $g2Status,
                    'terms_accepted' => ($g2Status === 'accepted'),
                    'terms_accepted_at' => ($g2Status === 'accepted') ? now()->subDays(19) : null,
                    'terms_ip_address' => '127.0.0.1',
                    'responded_at' => ($g2Status !== 'pending') ? now()->subDays(19) : null,
                    'notes' => ($g2Status === 'declined') ? 'Declined due to personal budget constraints.' : null,
                ]);

                // Attach Repayments for active and completed loans
                if ($st === 'active') {
                    $activeLoansList[] = $loan;
                    for ($r = 1; $r <= 3; $r++) {
                        LoanRepayment::create([
                            'loan_request_id' => $loan->id,
                            'amount' => 200.00,
                            'payment_date' => Carbon::create(2026, 3 + $r, 1)->toDateString(),
                            'payment_method' => 'zelle',
                            'reference_number' => "REP-{$loan->id}-0{$r}",
                            'notes' => "Monthly installment #{$r}.",
                        ]);
                    }
                } elseif ($st === 'completed') {
                    $totalRepay = $loan->total_repayable;
                    $installment = round($totalRepay / 4, 2);
                    for ($r = 1; $r <= 4; $r++) {
                        LoanRepayment::create([
                            'loan_request_id' => $loan->id,
                            'amount' => $r === 4 ? ($totalRepay - ($installment * 3)) : $installment,
                            'payment_date' => Carbon::create(2026, 3 + $r, 1)->toDateString(),
                            'payment_method' => 'bank_transfer',
                            'reference_number' => "REP-FULL-{$loan->id}-0{$r}",
                            'notes' => "Completed installment #{$r}.",
                        ]);
                    }
                }

                $loanGlobalIdx++;
            }
        }

        // Loan Repayment Requests across ALL statuses: 15 pending, 15 approved, 15 rejected
        for ($p = 0; $p < 15; $p++) {
            $targetLoan = $activeLoansList[$p % count($activeLoansList)];
            LoanRepaymentRequest::create([
                'loan_request_id' => $targetLoan->id,
                'member_id' => $targetLoan->member_id,
                'organization_id' => $org->id,
                'amount' => 250.00 + ($p * 15.00),
                'status' => 'pending',
                'screenshot_path' => 'repayment_proofs/sample_repayment.png',
                'payment_date' => now()->toDateString(),
                'payment_method' => 'zelle',
                'reference_number' => "ZEL-REP-PEND-{$p}",
                'notes' => 'Early principal repayment submission.',
                'submitted_at' => now()->subHours($p + 1),
            ]);
        }

        for ($p = 0; $p < 15; $p++) {
            $targetLoan = $activeLoansList[$p % count($activeLoansList)];
            LoanRepaymentRequest::create([
                'loan_request_id' => $targetLoan->id,
                'member_id' => $targetLoan->member_id,
                'organization_id' => $org->id,
                'amount' => 200.00,
                'status' => 'approved',
                'screenshot_path' => 'repayment_proofs/sample_repayment.png',
                'payment_date' => now()->subDays(15)->toDateString(),
                'payment_method' => 'zelle',
                'reference_number' => "ZEL-REP-APP-{$p}",
                'notes' => 'Scheduled monthly installment.',
                'submitted_at' => now()->subDays(16),
                'reviewed_by' => $treasurerUser->id,
                'reviewed_at' => now()->subDays(15),
                'review_note' => 'Payment reconciled in bank statement.',
            ]);
        }

        for ($p = 0; $p < 15; $p++) {
            $targetLoan = $activeLoansList[$p % count($activeLoansList)];
            LoanRepaymentRequest::create([
                'loan_request_id' => $targetLoan->id,
                'member_id' => $targetLoan->member_id,
                'organization_id' => $org->id,
                'amount' => 300.00,
                'status' => 'rejected',
                'screenshot_path' => 'repayment_proofs/sample_repayment.png',
                'payment_date' => now()->subDays(20)->toDateString(),
                'payment_method' => 'check',
                'reference_number' => "CHK-BAD-{$p}",
                'notes' => 'Payment slip unverified.',
                'submitted_at' => now()->subDays(21),
                'reviewed_by' => $treasurerUser->id,
                'reviewed_at' => now()->subDays(20),
                'review_note' => 'Payment reference was not found in bank operating account.',
            ]);
        }
    }

    /**
     * Ensure realistic receipt, voucher, and deposit proof images exist so "View Proof" displays real documents,
     * and purge any leftover legacy test uploads from storage.
     */
    private function ensureSampleProofImages(): void
    {
        // 1. Purge all existing legacy uploads in public storage so only clean samples remain
        $directories = [
            'screenshots',
            'savings_proofs',
            'savings_disbursements',
            'loan_disbursements',
            'repayment_proofs',
        ];

        foreach ($directories as $dir) {
            if (Storage::disk('public')->exists($dir)) {
                $allFiles = Storage::disk('public')->files($dir);
                foreach ($allFiles as $filePath) {
                    if (basename($filePath) !== '.gitignore') {
                        Storage::disk('public')->delete($filePath);
                    }
                }
            }

            // Also clean direct public/storage if it's a physical directory (not a symlink)
            $directDir = public_path('storage/' . $dir);
            if (is_dir($directDir) && !is_link(public_path('storage'))) {
                $files = glob($directDir . '/*');
                if ($files) {
                    foreach ($files as $f) {
                        if (is_file($f) && basename($f) !== '.gitignore') {
                            @unlink($f);
                        }
                    }
                }
            }
        }

        $docs = [
            'screenshots/sample_receipt.png' => [
                'title' => 'Njangi Session Contribution Proof',
                'docType' => 'Electronic Payment Receipt',
                'ref' => 'NJG-2026-OCT-84920',
                'amount' => '$200.00 USD',
                'status' => 'VERIFIED PAID',
                'method' => 'Zelle Transfer / Mobile Banking',
                'officer' => 'Financial Secretary Agnes Ndzi',
            ],
            'savings_proofs/sample_deposit.png' => [
                'title' => 'Savings Deposit Slip Confirmation',
                'docType' => 'Member Deposit Voucher',
                'ref' => 'SAV-DEP-2026-11048',
                'amount' => '$500.00 USD',
                'status' => 'CREDITED',
                'method' => 'Direct ACH Deposit / Mobile Check',
                'officer' => 'Treasurer Julius Talla',
            ],
            'savings_disbursements/sample_disbursement.png' => [
                'title' => 'Savings Withdrawal Payout Voucher',
                'docType' => 'Treasury Disbursement Order',
                'ref' => 'SAV-WTH-2026-03912',
                'amount' => '$350.00 USD',
                'status' => 'DISBURSED',
                'method' => 'Bank Wire / Cashier Disbursement',
                'officer' => 'Treasurer Julius Talla',
            ],
            'loan_disbursements/sample_loan_voucher.png' => [
                'title' => 'Approved Loan Disbursement Voucher',
                'docType' => 'Loan Payout & Transfer Voucher',
                'ref' => 'LN-DISB-2026-00421',
                'amount' => '$2,500.00 USD',
                'status' => 'DISBURSED',
                'method' => 'Bank Transfer / Wire Order #84912',
                'officer' => 'Treasurer Julius Talla',
            ],
            'repayment_proofs/sample_repayment.png' => [
                'title' => 'Loan Monthly Installment Receipt',
                'docType' => 'Repayment Proof Confirmation',
                'ref' => 'LN-RPY-2026-00954',
                'amount' => '$450.00 USD',
                'status' => 'REPAYMENT CONFIRMED',
                'method' => 'Online Bank Transfer / Ref #5892',
                'officer' => 'Treasurer Julius Talla',
            ],
        ];

        foreach ($docs as $relativePath => $info) {
            $pngBinary = $this->renderSampleDocumentPng(
                $info['title'],
                $info['docType'],
                $info['ref'],
                $info['amount'],
                $info['status'],
                $info['method'],
                $info['officer']
            );

            // 1. Put into public disk (storage/app/public)
            Storage::disk('public')->put($relativePath, $pngBinary);

            // 2. Also ensure direct public/storage copy exists for servers without symlinks
            $directPublicPath = public_path('storage/' . $relativePath);
            $dir = dirname($directPublicPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (!file_exists($directPublicPath) || filesize($directPublicPath) !== strlen($pngBinary)) {
                @file_put_contents($directPublicPath, $pngBinary);
            }
        }
    }

    private function renderSampleDocumentPng(string $title, string $docType, string $ref, string $amount, string $status, string $method, string $officer): string
    {
        if (extension_loaded('gd')) {
            $w = 640;
            $h = 800;
            $im = imagecreatetruecolor($w, $h);

            $white = imagecolorallocate($im, 255, 255, 255);
            $bgLight = imagecolorallocate($im, 248, 250, 252);
            $border = imagecolorallocate($im, 203, 213, 225);
            $headerBg = imagecolorallocate($im, 15, 23, 42);
            $headerAccent = imagecolorallocate($im, 16, 185, 129);
            $textDark = imagecolorallocate($im, 30, 41, 59);
            $textMuted = imagecolorallocate($im, 100, 116, 139);
            $greenBg = imagecolorallocate($im, 236, 253, 245);
            $greenBorder = imagecolorallocate($im, 52, 211, 153);
            $greenText = imagecolorallocate($im, 6, 95, 70);
            $cardBg = imagecolorallocate($im, 255, 255, 255);

            imagefilledrectangle($im, 0, 0, $w, $h, $bgLight);
            imagerectangle($im, 0, 0, $w - 1, $h - 1, $border);

            imagefilledrectangle($im, 20, 20, $w - 20, $h - 20, $cardBg);
            imagerectangle($im, 20, 20, $w - 20, $h - 20, $border);

            imagefilledrectangle($im, 20, 20, $w - 20, 120, $headerBg);
            imagefilledrectangle($im, 20, 116, $w - 20, 120, $headerAccent);

            imagestring($im, 5, 40, 40, "NFUH DMV COMMUNITY ASSOCIATION", $white);
            imagestring($im, 3, 40, 65, "FINANCIAL MANAGEMENT & TREASURY SERVICES", $headerAccent);
            imagestring($im, 2, 40, 88, "WASHINGTON DC  |  MARYLAND  |  VIRGINIA", $textMuted);

            imagestring($im, 4, 40, 145, strtoupper($docType), $textDark);
            imagestring($im, 2, 40, 168, "OFFICIAL DIGITAL PROOF & TRANSACTION RECORD", $textMuted);

            imageline($im, 40, 195, $w - 40, 195, $border);

            $fields = [
                ["REFERENCE NO:", $ref],
                ["DOCUMENT TITLE:", $title],
                ["DATE & TIME:", "2026-10-15 14:30:00 EST"],
                ["PAYMENT / TRANSFER METHOD:", $method],
                ["AUTHORIZED OFFICER:", $officer],
                ["ORGANIZATION UNIT:", "NFUH DMV Chapter #001"],
            ];

            $y = 215;
            foreach ($fields as $field) {
                imagestring($im, 2, 40, $y, $field[0], $textMuted);
                imagestring($im, 3, 40, $y + 16, $field[1], $textDark);
                $y += 48;
            }

            imageline($im, 40, $y + 10, $w - 40, $y + 10, $border);

            $amountBoxY = $y + 25;
            imagefilledrectangle($im, 40, $amountBoxY, $w - 40, $amountBoxY + 90, $bgLight);
            imagerectangle($im, 40, $amountBoxY, $w - 40, $amountBoxY + 90, $border);

            imagestring($im, 3, 60, $amountBoxY + 18, "TOTAL TRANSACTION AMOUNT:", $textMuted);
            imagestring($im, 5, 60, $amountBoxY + 45, $amount, $textDark);

            $stampX = $w - 240;
            $stampY = $amountBoxY + 20;
            imagefilledrectangle($im, $stampX, $stampY, $stampX + 180, $stampY + 50, $greenBg);
            imagerectangle($im, $stampX, $stampY, $stampX + 180, $stampY + 50, $greenBorder);
            imagestring($im, 4, $stampX + 25, $stampY + 16, $status, $greenText);

            $footerY = $h - 130;
            imageline($im, 40, $footerY, $w - 40, $footerY, $border);
            imagestring($im, 2, 40, $footerY + 15, "SECURITY VERIFICATION & SYSTEM AUDIT", $textDark);
            imagestring($im, 1, 40, $footerY + 35, "This document was generated and archived in the NFUH DMV Digital Records System.", $textMuted);
            imagestring($im, 1, 40, $footerY + 50, "Verification Hash: 8f4a-9c71-2b0e-61d5-e33a-91cb-7042", $textMuted);
            imagestring($im, 1, 40, $footerY + 65, "Status: Permanent Immutable Ledger Attachment - Verified by Officer", $headerAccent);

            ob_start();
            imagepng($im);
            $data = ob_get_clean();
            imagedestroy($im);
            return $data;
        }

        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    }
}
