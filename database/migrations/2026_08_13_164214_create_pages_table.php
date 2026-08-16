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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrap_id')->constrained('scraps')->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('number');
            $table->timestamps();

            $table->unique(['number', 'scrap_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
