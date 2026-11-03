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
        Schema::table('evacuation_shelters', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('note');
            $table->string('activity_status', 16)->default('unknown')->after('is_active');
            $table->unsignedInteger('evacuees')->nullable()->after('activity_status');
            $table->text('status_note')->nullable()->after('evacuees');
            $table->string('status_source_name')->nullable()->after('status_note');
            $table->string('status_source_url', 2048)->nullable()->after('status_source_name');
            $table->timestamp('status_checked_at')->nullable()->after('status_source_url');
            $table->timestamp('status_changed_at')->nullable()->after('status_checked_at');

            $table->index(['local_government_id', 'is_active'], 'evacuation_shelters_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evacuation_shelters', function (Blueprint $table) {
            $table->dropIndex('evacuation_shelters_active_index');
            $table->dropColumn([
                'is_active',
                'activity_status',
                'evacuees',
                'status_note',
                'status_source_name',
                'status_source_url',
                'status_checked_at',
                'status_changed_at',
            ]);
        });
    }
};
