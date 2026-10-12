<?php

namespace App\Domain\ResistanceAssessment;

use App\Enums\AssessmentOutcome;

class AssessmentResult
{
    /**
     * @param  list<array<string, mixed>>  $evaluatedRules
     */
    public function __construct(
        public readonly AssessmentOutcome $outcome,
        public readonly string $explanation,
        public readonly string $ruleSetVersion,
        public readonly array $evaluatedRules,
    ) {}

    /**
     * @return array{
     *     outcome: string,
     *     explanation: string,
     *     rule_set_version: string,
     *     evaluated_rules: list<array<string, mixed>>
     * }
     */
    public function toSnapshot(): array
    {
        return [
            'outcome' => $this->outcome->value,
            'explanation' => $this->explanation,
            'rule_set_version' => $this->ruleSetVersion,
            'evaluated_rules' => $this->evaluatedRules,
        ];
    }
}
