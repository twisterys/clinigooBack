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
        Schema::create('profession_profession_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profession_id')->constrained('professions')->cascadeOnDelete();
            $table->foreignId('profession_document_id')->constrained('profession_documents')->cascadeOnDelete();
            $table->boolean('required')->default(true);
            $table->timestamps();

            $table->unique(['profession_id', 'profession_document_id'], 'prof_doc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profession_profession_document');
    }
};
