<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('professional_documents', 'reupload_allowed')) {
            Schema::table('professional_documents', function (Blueprint $table) {
                $table->boolean('reupload_allowed')->default(false)->after('rejection_reason');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('professional_documents', 'reupload_allowed')) {
            Schema::table('professional_documents', function (Blueprint $table) {
                $table->dropColumn('reupload_allowed');
            });
        }
    }
};
