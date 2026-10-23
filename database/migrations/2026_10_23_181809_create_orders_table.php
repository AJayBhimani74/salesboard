<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('region', 30);
            $table->string('category', 40);
            $table->string('status', 20)->default('completed');
            $table->unsignedInteger('amount');
            $table->dateTime('ordered_at');
            $table->timestamps();

            $table->index('ordered_at');
            $table->index(['region', 'ordered_at']);
            $table->index(['category', 'ordered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
