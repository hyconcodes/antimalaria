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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('submission_token');
            $table->string('sample_reference', 100);
            $table->date('collection_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('input_snapshot');
            $table->string('result_code', 64);
            $table->text('result_explanation');
            $table->string('rule_set_version', 64);
            $table->json('evaluation_snapshot');
            $table->timestamp('assessed_at');
            $table->timestamps();

            $table->unique(['user_id', 'submission_token']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'result_code']);
            $table->index(['user_id', 'sample_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
