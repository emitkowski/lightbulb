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
        Schema::table('raw_signals', function (Blueprint $table) {
            $table->uuid('ingestion_run_id')->nullable()->after('id');
            $table->foreign('ingestion_run_id')->references('id')->on('ingestion_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('raw_signals', function (Blueprint $table) {
            $table->dropForeign(['ingestion_run_id']);
            $table->dropColumn('ingestion_run_id');
        });
    }
};
