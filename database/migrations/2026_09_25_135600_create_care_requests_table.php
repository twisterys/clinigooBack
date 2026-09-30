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
        Schema::disableForeignKeyConstraints();

        Schema::create('care_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('beneficiary_id')->constrained();
            $table->foreignId('service_id')->constrained();
            $table->foreignId('address_id')->nullable()->constrained();
            $table->boolean('urgent')->default(false);
            $table->date('date')->nullable();
            $table->string('time')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->decimal('duration_hours', 5, 2)->default(1.00);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->integer('perimeter')->default(0);
            $table->decimal('evaluation', 3, 2)->nullable();
            $table->string('gender')->default('all');
            $table->boolean('certified')->default(false);
            $table->boolean('free_mobility')->default(false);
            $table->string('status')->default('pending');
            $table->string('address_label')->nullable();
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('address_instructions')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_requests');
    }
};
