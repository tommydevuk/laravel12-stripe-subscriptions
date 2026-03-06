<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_id')->unique()->index();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('amount');
            $table->string('currency', 3);
            $table->string('type'); // recurring, one_time
            $table->string('interval')->nullable(); // month, year, etc.
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
