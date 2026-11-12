<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
        });

        DB::table('members')
            ->whereIn('email', DB::table('users')->select('email'))
            ->update([
                'user_id' => DB::raw('(select id from users where users.email = members.email)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
