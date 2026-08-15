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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrap_id')->constrained('scraps')->onDelete('cascade');
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            $table->string('external_id', 100);
            $table->string('title', 512);
            $table->string('image_url', 1024)->nullable();
            $table->string('product_url', 1024)->nullable();
            $table->integer('page_number');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'external_id']);
            $table->index('page_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
