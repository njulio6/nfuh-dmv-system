<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('savings_deposit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('screenshot_path');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        // Copy existing requests (where submitted_at is not null)
        $existingRequests = DB::table('savings_transactions')
            ->whereNotNull('submitted_at')
            ->get();

        foreach ($existingRequests as $req) {
            DB::table('savings_deposit_requests')->insert([
                'member_id' => $req->member_id,
                'organization_id' => $req->organization_id,
                'amount' => $req->amount,
                'status' => $req->status,
                'screenshot_path' => $req->screenshot_path ?? 'savings_proofs/placeholder.png',
                'notes' => $req->notes,
                'submitted_at' => $req->submitted_at ?? $req->created_at ?? now(),
                'reviewed_by' => $req->reviewed_by,
                'reviewed_at' => $req->reviewed_at,
                'review_note' => $req->review_note,
                'created_at' => $req->created_at,
                'updated_at' => $req->updated_at,
            ]);
        }

        // Delete pending and rejected requests from transactions ledger since they shouldn't occupy ledger records
        DB::table('savings_transactions')
            ->whereNotNull('submitted_at')
            ->whereIn('status', ['pending', 'rejected'])
            ->delete();

        // Drop requests columns from transactions table
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'screenshot_path',
                'submitted_at',
                'reviewed_by',
                'reviewed_at',
                'review_note'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->string('screenshot_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });

        Schema::dropIfExists('savings_deposit_requests');
    }
};
