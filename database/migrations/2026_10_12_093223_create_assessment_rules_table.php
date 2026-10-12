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
        Schema::create('assessment_rules', function (Blueprint $table) {
            $table->id();
            $table->string('stable_key', 100);
            $table->unsignedInteger('revision')->default(1);
            $table->string('marker_key', 100);
            $table->string('expected_value', 100);
            $table->string('name', 160);
            $table->text('description');
            $table->text('scientific_rationale');
            $table->text('interpretation');
            $table->string('evidence_classification', 40);
            $table->string('status', 32)->default('draft');
            $table->boolean('enabled')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['stable_key', 'revision']);
            $table->index(['status', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_rules');
    }
};
