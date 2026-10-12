<?php

namespace App\Actions\ResistanceAssessment;

use App\Domain\ResistanceAssessment\ResistanceAssessmentEngine;
use App\Enums\RuleStatus;
use App\Models\Assessment;
use App\Models\AssessmentRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreateAssessment
{
    public function __construct(
        private readonly ResistanceAssessmentEngine $engine,
    ) {}

    /**
     * @param array{
     *     sample_reference: string,
     *     collection_date: string|null,
     *     notes: string|null,
     *     marker_calls: array<string, string|null>
     * } $input
     */
    public function handle(User $user, array $input, string $submissionToken): Assessment
    {
        return DB::transaction(function () use ($user, $input, $submissionToken): Assessment {
            /** @var Collection<int, AssessmentRule> $approvedRules */
            $approvedRules = AssessmentRule::query()
                ->with('sources')
                ->where('status', RuleStatus::Approved->value)
                ->where('enabled', true)
                ->orderBy('stable_key')
                ->orderBy('revision')
                ->get();

            $rules = array_values(
                $approvedRules
                    ->map(fn (AssessmentRule $rule): array => $rule->toAssessmentSnapshot())
                    ->all(),
            );

            $result = $this->engine->evaluate($input['marker_calls'], $rules);
            $inputSnapshot = [
                'sample_reference' => $input['sample_reference'],
                'collection_date' => $input['collection_date'],
                'notes' => $input['notes'],
                'marker_calls' => $input['marker_calls'],
            ];

            return $user->assessments()->firstOrCreate(
                ['submission_token' => $submissionToken],
                [
                    'sample_reference' => $input['sample_reference'],
                    'collection_date' => $input['collection_date'],
                    'notes' => $input['notes'],
                    'input_snapshot' => $inputSnapshot,
                    'result_code' => $result->outcome,
                    'result_explanation' => $result->explanation,
                    'rule_set_version' => $result->ruleSetVersion,
                    'evaluation_snapshot' => $result->toSnapshot(),
                    'assessed_at' => now(),
                ],
            );
        });
    }
}
