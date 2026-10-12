<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'submission_token' => (string) Str::uuid(),
            'sample_reference' => 'SAMPLE-'.$this->faker->unique()->numerify('#####'),
            'collection_date' => $this->faker->date(),
            'notes' => null,
            'input_snapshot' => ['marker_calls' => []],
            'result_code' => 'indeterminate',
            'result_explanation' => 'Synthetic software-test fixture; no scientific interpretation.',
            'rule_set_version' => hash('sha256', 'synthetic-empty-rule-set'),
            'evaluation_snapshot' => [
                'outcome' => 'indeterminate',
                'evaluated_rules' => [],
            ],
            'assessed_at' => now(),
        ];
    }
}
