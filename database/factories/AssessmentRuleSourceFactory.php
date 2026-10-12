<?php

namespace Database\Factories;

use App\Models\AssessmentRuleSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentRuleSource>
 */
class AssessmentRuleSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Synthetic software-test source',
            'url' => 'https://example.org/synthetic-test-source',
            'doi' => null,
            'published_on' => null,
            'evidence_notes' => 'Synthetic fixture; not scientific evidence.',
            'reviewed_on' => null,
        ];
    }
}
