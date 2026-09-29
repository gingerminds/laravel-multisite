<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('site_front_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->timestamps();

            $table->unique(['site_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_front_urls');
    }
};
