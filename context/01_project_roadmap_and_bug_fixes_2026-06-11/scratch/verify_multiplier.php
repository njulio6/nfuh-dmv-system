<?php
require 'c:/xampp/htdocs/nfuh-dmv-system/vendor/autoload.php';
$app = require_once 'c:/xampp/htdocs/nfuh-dmv-system/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\NjangiSession;
use App\Models\NjangiPaymentSubmission;
use App\Services\Njangi\ApproveNjangiPaymentSubmission;
use App\Models\NjangiCycle;
use App\Models\Organization;
use App\Models\Member;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSessionBeneficiary;
use App\Models\NjangiContribution;

echo "=====================================\n";
echo "VERIFYING MULTIPLIER WITH 4 BENEFICIARIES\n";
echo "=====================================\n";

try {
    $org = Organization::first() ?? Organization::create(['name' => 'Test Org']);
    
    // Create cycle
    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => '4 Beneficiaries Test Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);
    
    // Create contributor
    $contributor = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'CONT-001',
        'first_name' => 'Contributor',
        'last_name' => 'User',
        'phone' => '1111111111',
        'status' => 'active',
        'address' => '123 Contributor St',
        'state_code' => 'MD',
        'join_date' => '2026-01-01',
    ]);
    
    // Create 4 beneficiaries
    $beneficiaries = [];
    $cycleMembers = [];
    for ($i = 1; $i <= 4; $i++) {
        $ben = Member::create([
            'organization_id' => $org->id,
            'member_code' => "BEN-00$i",
            'first_name' => "Beneficiary",
            'last_name' => "Number $i",
            'phone' => "222222222$i",
            'status' => 'active',
            'address' => "Address $i",
            'state_code' => 'MD',
            'join_date' => '2026-01-01',
        ]);
        
        $cm = NjangiCycleMember::create([
            'njangi_cycle_id' => $cycle->id,
            'member_id' => $ben->id,
            'benefit_order' => $i,
        ]);
        
        $beneficiaries[] = $ben;
        $cycleMembers[] = $cm;
    }
    
    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => '2026-06-01',
        'title' => 'June Session',
        'status' => 'open',
    ]);
    
    // Assign the 4 members as beneficiaries to this session
    foreach ($cycleMembers as $index => $cm) {
        NjangiSessionBeneficiary::create([
            'organization_id' => $org->id,
            'njangi_session_id' => $session->id,
            'njangi_cycle_member_id' => $cm->id,
            'beneficiary_slot' => $index + 1,
        ]);
    }
    
    // Contributor uploads $400.00 Zelle payment
    $submission = NjangiPaymentSubmission::create([
        'organization_id' => $org->id,
        'member_id' => $contributor->id,
        'njangi_cycle_id' => $cycle->id,
        'njangi_session_id' => $session->id,
        'amount' => 400.00,
        'screenshot_path' => 'screenshots/test.png',
        'status' => 'pending',
    ]);
    
    echo "Created payment submission of $400.00.\n";
    echo "Approving submission...\n";
    
    $approveService = new ApproveNjangiPaymentSubmission();
    $approveService->execute($submission, 1);
    
    // Check contributions table for this submission
    $contributions = NjangiContribution::where('payment_submission_id', $submission->id)->get();
    echo "Number of contribution records created: " . $contributions->count() . "\n";
    $sum = 0;
    foreach ($contributions as $c) {
        $benName = $c->beneficiary->first_name . " " . $c->beneficiary->last_name;
        echo " - Contributor paid $benName: \${$c->amount}\n";
        $sum += $c->amount;
    }
    echo "Total amount written to ledger: \${$sum}\n";
    
} catch (\Throwable $e) {
    echo "Error running payment submission test: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
