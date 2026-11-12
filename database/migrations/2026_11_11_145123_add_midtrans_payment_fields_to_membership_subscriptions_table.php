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
        Schema::table('membership_subscriptions', function (Blueprint $table): void {
            $table->string('payment_order_id')->nullable()->unique();
            $table->string('payment_status')->default('pending');
            $table->text('payment_url')->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->timestamp('payment_paid_at')->nullable();
        });

        DB::table('membership_subscriptions')
            ->where('status', 'active')
            ->update([
                'payment_status' => 'paid',
                'payment_paid_at' => DB::raw('approved_at'),
            ]);

        DB::table('membership_subscriptions')
            ->where('status', 'rejected')
            ->update(['payment_status' => 'cancelled']);

        DB::table('membership_subscriptions')
            ->where('status', 'pending')
            ->whereNull('payment_order_id')
            ->update(['payment_status' => 'cancelled']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_subscriptions', function (Blueprint $table): void {
            $table->dropUnique(['payment_order_id']);
            $table->dropColumn([
                'payment_order_id',
                'payment_status',
                'payment_url',
                'payment_transaction_id',
                'payment_paid_at',
            ]);
        });
    }
};
