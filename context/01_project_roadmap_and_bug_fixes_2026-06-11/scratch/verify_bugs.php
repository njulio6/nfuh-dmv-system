<?php
require 'c:/xampp/htdocs/nfuh-dmv-system/vendor/autoload.php';
$app = require_once 'c:/xampp/htdocs/nfuh-dmv-system/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\NjangiSession;
use App\Services\NjangiSessionService;
use App\Models\NjangiPaymentSubmission;
use App\Services\Njangi\ApproveNjangiPaymentSubmission;
use App\Models\NjangiCycle;
use App\Models\Organization;
use App\Models\Member;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSessionBeneficiary;
use App\Models\NjangiContribution;

echo "=====================================\n";
echo "1. VERIFYING NJANGI SESSION SERVICE\n";
echo "=====================================\n";

try {
    // Let's find or create a mock session
    $cycle = NjangiCycle::first() ?? NjangiCycle::create([
        'organization_id' => Organization::first()->id ?? 1,
        'name' => 'Test Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);
    
    $session = NjangiSession::first() ?? NjangiSession::create([
        'organization_id' => $cycle->organization_id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => '2026-06-01',
        'title' => 'June Session',
        'status' => 'scheduled',
    ]);

    echo "Running openSession on Session ID: {$session->id}...\n";
    $service = new NjangiSessionService();
    $service->openSession($session);
} catch (\Throwable $e) {
    echo "SUCCESSFULLY DETECTED BUG (NjangiSessionService):\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "File/Line: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=====================================\n";
echo "2. VERIFYING PAYMENT SUBMISSION MULTIPLIER BUG\n";
echo "=====================================\n";

try {
    $org = Organization::first() ?? Organization::create(['name' => 'Test Org']);
    
    // Create cycle & members
    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => 'Multiplier Test Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);
    
    $member1 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'TEST-001',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '1234567890',
        'status' => 'active',
        'address' => '123 Test St',
        'state_code' => 'MD',
        'join_date' => '2026-01-01',
    ]);
    
    $member2 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'TEST-002',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'phone' => '0987654321',
        'status' => 'active',
        'address' => '456 Test St',
        'state_code' => 'MD',
        'join_date' => '2026-01-01',
    ]);
    
    $cycleMember1 = NjangiCycleMember::create([
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member1->id,
        'benefit_order' => 1,
    ]);
    
    $cycleMember2 = NjangiCycleMember::create([
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member2->id,
        'benefit_order' => 2,
    ]);
    
    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => '2026-06-01',
        'title' => 'June Session',
        'status' => 'open',
    ]);
    
    // Assign member2 as beneficiary
    NjangiSessionBeneficiary::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'njangi_cycle_member_id' => $cycleMember2->id,
        'beneficiary_slot' => 1,
    ]);
    
    // Create submission of $400 by John Doe
    $submission = NjangiPaymentSubmission::create([
        'organization_id' => $org->id,
        'member_id' => $member1->id,
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
        echo " - Contributor ID {$c->contributor_member_id} paid Beneficiary ID {$c->beneficiary_member_id}: \${$c->amount}\n";
        $sum += $c->amount;
    }
    echo "Total amount written to ledger: \${$sum}\n";
    
} catch (\Throwable $e) {
    echo "Error running payment submission test: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
