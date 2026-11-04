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
        Schema::table('care_requests', function (Blueprint $table) {
            $columnsToDrop = array_filter(
                ['duration_hours', 'perimeter', 'evaluation', 'gender', 'certified', 'free_mobility'],
                fn ($col) => Schema::hasColumn('care_requests', $col)
            );

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            $table->unique('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_requests', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->decimal('duration_hours', 5, 2)->default(1.00);
            $table->integer('perimeter')->default(0);
            $table->decimal('evaluation', 3, 2)->nullable();
            $table->string('gender')->default('all');
            $table->boolean('certified')->default(false);
            $table->boolean('free_mobility')->default(false);
        });
    }
};
