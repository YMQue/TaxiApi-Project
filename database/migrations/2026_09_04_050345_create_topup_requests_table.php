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
        Schema::create('topup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('user_type', ['driver', 'rider']);
            $table->decimal('amount', 15, 2);
            $table->string('method')->nullable();
            $table->string('payment_ref')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();

            $table->foreignId('wallet_transaction_id')->nullable()
                ->constrained('transactions')->cascadeOnDelete();

            $table->timestamps();
            $table->index(['user_id', 'user_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topup_requests');
    }
};
