<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the email column from members table.
     * Email is now exclusively owned by the users table (users.email).
     * Members are linked to users via the user_id foreign key.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Drop unique index if it exists, then drop the column
            if (collect(\DB::select("SHOW INDEX FROM members WHERE Column_name = 'email'"))->isNotEmpty()) {
                $table->dropUnique(['email']);
            }
            $table->dropColumn('email');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('email')->nullable()->after('last_name');
        });
    }
};
