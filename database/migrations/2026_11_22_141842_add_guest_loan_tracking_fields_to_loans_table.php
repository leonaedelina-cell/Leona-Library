<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->string('member_number')->nullable()->change();
            $table->string('borrower_type')->default('member');
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('return_by')->nullable();
            $table->timestamp('returned_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('loans')
            ->whereNull('member_number')
            ->update(['member_number' => 'GUEST']);

        Schema::table('loans', function (Blueprint $table): void {
            $table->dropColumn(['borrower_type', 'checked_out_at', 'return_by', 'returned_at']);
            $table->string('member_number')->nullable(false)->change();
        });
    }
};
