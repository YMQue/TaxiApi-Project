<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->foreignId('promo_code_id')
                ->nullable()
                ->after('car_type_id')
                ->constrained('promo_codes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_code_id');
        });
    }
};