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
        Schema::table('volunteer_centers', function (Blueprint $table) {
            $table->string('activity_status', 16)->default('unknown')->after('latest_recruitment_url');
            $table->text('status_note')->nullable()->after('activity_status');
            $table->string('status_source_name')->nullable()->after('status_note');
            $table->string('status_source_url', 2048)->nullable()->after('status_source_name');
            $table->timestamp('status_checked_at')->nullable()->after('status_source_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('volunteer_centers', function (Blueprint $table) {
            $table->dropColumn([
                'activity_status',
                'status_note',
                'status_source_name',
                'status_source_url',
                'status_checked_at',
            ]);
        });
    }
};
