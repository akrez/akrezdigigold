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
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrap_id')->constrained('scraps')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('external_id', 100)->nullable();
            $table->string('seller', 255)->nullable();
            $table->string('carat', 12)->nullable();
            $table->decimal('size', 10, 3)->nullable();
            $table->unsignedBigInteger('price')->nullable();
            $table->unsignedBigInteger('price_per_gram')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'external_id']);
            $table->index('price_per_gram');
            $table->index('carat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
