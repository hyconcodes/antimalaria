<?php

namespace App\Domain\ResistanceAssessment;

use App\Enums\AssessmentOutcome;

class ResistanceAssessmentEngine
{
    /**
     * @param  array<string, string|null>  $markerCalls
     * @param  list<array<string, mixed>>  $approvedRules
     */
    public function evaluate(array $markerCalls, array $approvedRules): AssessmentResult
    {
        usort($approvedRules, static fn (array $left, array $right): int => [
            $left['rule_key'],
            $left['revision'],
        ] <=> [
            $right['rule_key'],
            $right['revision'],
        ]);

        $ruleSetVersion = hash('sha256', json_encode($approvedRules, JSON_THROW_ON_ERROR));
        $hasIncompleteEvaluation = false;
        $evaluatedRules = [];

        foreach ($approvedRules as $rule) {
            $markerKey = $rule['marker_key'];
            $call = $markerCalls[$markerKey] ?? null;
            $isKnownCall = MarkerCatalog::isKnownCall($markerKey, is_string($call) ? $call : null);

            if ($isKnownCall && $call === $rule['expected_value']) {
                $matchStatus = 'matched';
            } elseif (! $isKnownCall) {
                $matchStatus = 'insufficient_input';
                $hasIncompleteEvaluation = true;
            } else {
                $matchStatus = 'not_matched';
            }

            $evaluatedRules[] = [
                ...$rule,
                'match_status' => $matchStatus,
            ];
        }

        $matchedRules = array_filter(
            $evaluatedRules,
            static fn (array $rule): bool => $rule['match_status'] === 'matched',
        );

        if ($matchedRules !== []) {
            return new AssessmentResult(
                AssessmentOutcome::ResistanceAssociatedPattern,
                'At least one approved, enabled marker rule matched. This is a marker-level research finding, not a clinical diagnosis, a determination of treatment resistance, or a treatment recommendation.',
                $ruleSetVersion,
                $evaluatedRules,
            );
        }

        if ($approvedRules === []) {
            return new AssessmentResult(
                AssessmentOutcome::Indeterminate,
                'No approved, enabled rule is available to evaluate these marker calls. A scientific reviewer must establish and approve an applicable rule before this system can report a marker assessment.',
                $ruleSetVersion,
                $evaluatedRules,
            );
        }

        if ($hasIncompleteEvaluation) {
            return new AssessmentResult(
                AssessmentOutcome::Indeterminate,
                'One or more approved rules could not be evaluated because a required marker call is missing, unknown, mixed, conflicting, not tested, or outside the supported call values.',
                $ruleSetVersion,
                $evaluatedRules,
            );
        }

        return new AssessmentResult(
            AssessmentOutcome::NoQualifyingMarker,
            'No qualifying marker was detected by the approved rules evaluated. This does not prove chloroquine susceptibility or exclude resistance mechanisms not assessed by this application.',
            $ruleSetVersion,
            $evaluatedRules,
        );
    }
}
