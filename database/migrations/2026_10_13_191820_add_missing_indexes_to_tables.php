<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::table('order_items', function (Blueprint $table) {
                $table->index('order_id');
                $table->index('product_id');
            });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->index('cart_id');
                $table->index('product_id');
            });
        }

        public function down(): void
        {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropIndex(['order_id']);
                $table->dropIndex(['product_id']);
            });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropIndex(['cart_id']);
                $table->dropIndex(['product_id']);
            });
        }
    };
