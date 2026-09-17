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
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->nullable()->cascadeOnDelete();
            $table->foreignId('car_type_id')->constrained('car_types')->cascadeOnDelete();

            $table->enum('status',['searching','accepted','arrived','in_progress','completed','cancelled']);

            $table->decimal('pickup_lat',10,7);
            $table->decimal('pickup_lng',10,7);
            $table->string('pickup_address')->nullable();

            $table->decimal('drop_lat',10,7);
            $table->decimal('drop_lng',10,7);
            $table->string('drop_address')->nullable();

            $table->decimal('distance_km',8,2)->nullable();
            $table->unsignedInteger('duration_min')->nullable();

            $table->decimal('estimated_fare', 10, 2)->nullable();
            $table->decimal('final_fare', 10, 2)->nullable();
            $table->string('payment_method')->nullable(); // cash, wallet, card

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->string('cancel_by')->nullable(); // rider, driver, admin     
                   
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
