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
        Schema::table('settings', function (Blueprint $table) {
            $table->text('site_address')->nullable()->after('site_logo');
            $table->string('site_url')->nullable()->after('site_address');
            $table->string('site_favicon')->nullable()->after('site_url');
            $table->string('site_banner')->nullable()->after('site_favicon');
            $table->longText('site_about')->nullable()->after('site_banner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('site_address');
            $table->dropColumn('site_url');
            $table->dropColumn('site_favicon');
            $table->dropColumn('site_banner');
            $table->dropColumn('site_about');
        });
    }
};
