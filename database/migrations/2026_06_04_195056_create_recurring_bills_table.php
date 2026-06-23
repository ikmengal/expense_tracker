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
        Schema::create('recurring_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "PTCL Internet", "Ghar ka Rent"
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('PKR');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->date('due_date'); // Agli tareeq jab bill bharna hai
            $table->string('status')->default('unpaid'); // unpaid ya paid
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_bills');
    }
};
