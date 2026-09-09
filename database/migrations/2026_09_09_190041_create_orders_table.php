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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('order_number', 50)
                ->unique();
            $table->string('status', 50);
            $table->string('payment_status', 50);

            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->string('shipping_name', 255);
            $table->string('shipping_address', 500);
            $table->string('shipping_city', 100);
            $table->string('shipping_country', 100);
            $table->string('shipping_postal_code', 20)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index('user_id');
            $table->index('status');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
