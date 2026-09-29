<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Support\Facades\DB;

    return new class extends Migration
    {
        public function up(): void
        {
            DB::statement(
                'ALTER TABLE inventories ADD CONSTRAINT quantity_non_negative CHECK (quantity >= 0)'
            );
            DB::statement(
                'ALTER TABLE inventories ADD CONSTRAINT reserved_quantity_non_negative CHECK (reserved_quantity >= 0)'
            );
        }

        public function down(): void
        {
            DB::statement('ALTER TABLE inventories DROP CONSTRAINT quantity_non_negative');
            DB::statement('ALTER TABLE inventories DROP CONSTRAINT reserved_quantity_non_negative');
        }
    };
