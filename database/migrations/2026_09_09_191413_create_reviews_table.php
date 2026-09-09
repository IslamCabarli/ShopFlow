<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('product_id')
                    ->constrained('products')
                    ->restrictOnDelete();

                $table->index('product_id');

                $table->unsignedTinyInteger('rating');

                $table->text('comment')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['user_id', 'product_id']);
            });

            DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_rating_between_check CHECK (rating BETWEEN 1 AND 5)');
        }

        public function down(): void
        {
            Schema::dropIfExists('reviews');
        }
    };
