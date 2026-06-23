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
        Schema::table('supports', function (Blueprint $table) {
            $table->bigInteger('user_id')->after('id');
            $table->enum('status', ['Open', 'Resolved'])->default('Open')->after('message');
            $table->text('admin_reply')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supports', function (Blueprint $table) {
            // $table->dropColumn('user_id');
            $table->dropColumn('status');
            $table->dropColumn('admin_reply');
        });
    }
};
