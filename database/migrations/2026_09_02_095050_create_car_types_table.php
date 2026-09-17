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
        Schema::create('car_types', function (Blueprint $table) {
            $table->id();
            $table->enum('type',['Economy' , 'Comfort' , 'XL']);
            $table->string('icon')->nullable();
            $table->float('base_fare')->nullable();
            $table->float('per_km')->nullable();
            $table->float('per_minute')->nullable();
            $table->float('min_fare')->nullable();
            $table->float('capacity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_types');
    }
};
