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
            $table->string('seller', 255);
            $table->string('carat', 12);
            $table->decimal('size', 10, 3);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('price_per_gram');
            $table->timestamps();

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
