<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_sub_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->default('slate');
            $table->timestamps();
        });

        Schema::table('loan_requests', function (Blueprint $table) {
            $table->foreignId('sub_status_id')
                ->nullable()
                ->after('status')
                ->constrained('loan_sub_statuses')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropForeign(['sub_status_id']);
            $table->dropColumn('sub_status_id');
        });

        Schema::dropIfExists('loan_sub_statuses');
    }
};
