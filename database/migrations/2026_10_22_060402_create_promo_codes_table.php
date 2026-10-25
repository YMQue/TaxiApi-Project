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
        Schema::create('promo_codes', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique();
        $table->enum('type', ['percentage', 'fixed']);
        $table->decimal('value', 10, 2);
        $table->decimal('min_ride_amount', 10, 2)->default(0);
        $table->decimal('max_discount', 10, 2)->nullable();
        $table->unsignedInteger('usage_limit')->nullable();        // null = غير محدود
        $table->unsignedInteger('used_count')->default(0);
        $table->unsignedInteger('user_limit_per_code')->default(1);
        $table->timestamp('valid_from')->nullable();
        $table->timestamp('valid_to')->nullable();
        $table->boolean('is_active')->default(true);
        $table->softDeletes();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
