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

            // PDF Data Columns
            $table->string('category_name')->nullable();
            $table->string('item_code')->unique(); // Our anchor for the PDF sync
            $table->text('item_name')->nullable(); // Using text in case of long descriptions
            $table->string('colors_available')->nullable();
            $table->text('image_link')->nullable();
            $table->text('detail_link')->nullable();

            // Pricing (Extracted as numbers for calculation/filtering later)
            $table->decimal('sample_price', 10, 2)->nullable();
            $table->decimal('bulk_price', 10, 2)->nullable();

            $table->string('comments')->nullable();

            // Sync & Management Rules
            $table->boolean('is_active')->default(true); // For hiding missing products instead of deleting

            $table->timestamps();
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