<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('google_drive_file_id')->nullable()->after('url');
            // Stores the Google service account JSON credentials, encrypted at
            // rest via the model's `encrypted:array` cast. Never store this
            // in plain text or commit a credentials file to the repository.
            $table->text('google_service_account_credentials')->nullable()->after('google_drive_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['google_drive_file_id', 'google_service_account_credentials']);
        });
    }
};
