<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('road_regulations', function (Blueprint $table) {
            $table->string('status', 16)->default('closed')->after('regulation_type');
            $table->timestamp('recovered_at')->nullable()->after('status');
            $table->timestamp('last_verified_at')->nullable()->after('recovered_at');
            $table->string('recovery_source_url', 2048)->nullable()->after('last_verified_at');
            $table->text('recovery_evidence')->nullable()->after('recovery_source_url');

            $table->index(['disaster_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('road_regulations', function (Blueprint $table) {
            $table->dropIndex(['disaster_id', 'status']);
            $table->dropColumn([
                'status',
                'recovered_at',
                'last_verified_at',
                'recovery_source_url',
                'recovery_evidence',
            ]);
        });
    }
};
