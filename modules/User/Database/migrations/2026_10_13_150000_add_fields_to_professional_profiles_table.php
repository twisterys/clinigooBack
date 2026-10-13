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
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->foreignId('profession_id')->nullable()->after('user_id')->constrained('professions')->nullOnDelete();
            $table->string('status')->default('draft')->after('id_number');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->text('bio')->nullable()->after('rejection_reason');
            $table->string('avatar')->nullable()->after('bio');
            $table->string('phone')->nullable()->after('avatar');
            $table->boolean('is_available')->default(false)->after('phone');
            $table->timestamp('submitted_at')->nullable()->after('is_available');
            $table->timestamp('validated_at')->nullable()->after('submitted_at');
            $table->foreignId('validated_by')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
            $table->json('pending_data')->nullable()->after('validated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->dropForeign(['profession_id']);
            $table->dropForeign(['validated_by']);
            $table->dropColumn([
                'profession_id',
                'status',
                'rejection_reason',
                'bio',
                'avatar',
                'phone',
                'is_available',
                'submitted_at',
                'validated_at',
                'validated_by',
                'pending_data',
            ]);
        });
    }
};
