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
        Schema::create('assessment_rule_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_rule_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('url');
            $table->string('doi', 150)->nullable();
            $table->date('published_on')->nullable();
            $table->text('evidence_notes')->nullable();
            $table->date('reviewed_on')->nullable();
            $table->timestamps();

            $table->index('assessment_rule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_rule_sources');
    }
};
