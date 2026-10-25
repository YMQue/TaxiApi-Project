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
        Schema::create('wallet_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
    $table->enum('type', ['credit', 'debit']);
    $table->string('category')->nullable(); // topup, ride_payment, ride_earning, commission, withdrawal
    $table->decimal('amount', 12, 2);
    $table->decimal('balance_after', 12, 2);
    $table->string('description')->nullable();

    $table->nullableMorphs('reference');

    $table->timestamps();

    $table->index(['wallet_id', 'created_at']);
    $table->index(['wallet_id', 'category', 'created_at']); // for the commission report
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
