<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('google_drive_file_id')->nullable()->after('url');
            // Encrypted at rest via the model's `encrypted:array` cast.
            $table->text('google_service_account_credentials')->nullable()->after('google_drive_file_id');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['google_drive_file_id', 'google_service_account_credentials']);
        });
    }
};
