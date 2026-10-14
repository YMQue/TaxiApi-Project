<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('commission_rate', 5, 2)->default(20);
            $table->decimal('driver_search_radius_km', 6, 2)->default(5);
            $table->unsignedSmallInteger('ride_request_timeout_sec')->default(30);
            $table->unsignedTinyInteger('max_driver_search_attempts')->default(2);
            $table->decimal('min_withdrawal_amount', 10, 2)->default(50);
            $table->decimal('cancellation_fee', 10, 2)->default(5);
            $table->timestamps();
        });

        // الصف الوحيد بالقيم الافتراضية
        DB::table('settings')->insert([
            'created_at' => now(),
            'updated_at' => now(),
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
