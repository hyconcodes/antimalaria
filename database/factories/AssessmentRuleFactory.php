<?php

namespace Database\Factories;

use App\Enums\RuleStatus;
use App\Models\AssessmentRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentRule>
 */
class AssessmentRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stable_key' => 'synthetic-rule-'.$this->faker->unique()->numerify('####'),
            'revision' => 1,
            'marker_key' => 'pfcrt_k76',
            'expected_value' => 'T',
            'name' => 'Synthetic software-test rule',
            'description' => 'Synthetic fixture used only for automated software tests.',
            'scientific_rationale' => 'Not a scientific claim; test-only data.',
            'interpretation' => 'Synthetic marker-pattern finding for test coverage.',
            'evidence_classification' => 'candidate',
            'status' => RuleStatus::Draft,
            'enabled' => false,
            'created_by' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }
}
