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
        Schema::create('car_type_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_type_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();            // e.g. "Morning rush"
            $table->time('start_time');
            $table->time('end_time');                      // may be earlier than start_time (e.g. 22:00 → 02:00)
            $table->decimal('base_fare', 8, 2)->nullable();
            $table->decimal('per_km', 8, 2)->nullable();
            $table->decimal('per_minute', 8, 2)->nullable();
            $table->decimal('min_fare', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_type_pricing_rules');
    }
};
