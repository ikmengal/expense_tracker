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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('CashFlow App');
            $table->string('site_email')->default('admin@system.com');
            $table->string('site_logo')->nullable();
            $table->timestamps();
        });

        // Default row insert kar dein taake database khali na rahe
        DB::table('settings')->insert([
            'site_name' => 'CashFlow App',
            'site_email' => 'admin@system.com',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
