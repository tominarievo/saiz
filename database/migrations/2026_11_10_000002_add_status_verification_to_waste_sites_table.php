<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_sites', function (Blueprint $table) {
            $table->timestamp('last_verified_at')->nullable()->after('status');
            $table->timestamp('closed_at')->nullable()->after('last_verified_at');
            $table->string('status_source_url', 2048)->nullable()->after('closed_at');
            $table->text('status_evidence')->nullable()->after('status_source_url');
        });
    }

    public function down(): void
    {
        Schema::table('waste_sites', function (Blueprint $table) {
            $table->dropColumn([
                'last_verified_at',
                'closed_at',
                'status_source_url',
                'status_evidence',
            ]);
        });
    }
};
