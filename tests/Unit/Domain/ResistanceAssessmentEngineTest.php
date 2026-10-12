<?php

use App\Domain\ResistanceAssessment\ResistanceAssessmentEngine;
use App\Enums\AssessmentOutcome;

function syntheticResistanceRule(string $key, string $marker, string $expectedValue): array
{
    return [
        'rule_key' => $key,
        'revision' => 1,
        'name' => 'Synthetic software-test rule',
        'description' => 'Synthetic fixture only.',
        'scientific_rationale' => 'Synthetic fixture; not scientific evidence.',
        'marker_key' => $marker,
        'expected_value' => $expectedValue,
        'interpretation' => 'Synthetic marker-pattern finding.',
        'evidence_classification' => 'candidate',
        'sources' => [],
    ];
}

test('a known call matching an approved rule produces a cautious marker-pattern outcome', function () {
    $result = (new ResistanceAssessmentEngine)->evaluate(
        ['pfcrt_k76' => 'T'],
        [syntheticResistanceRule('synthetic-pfcrt-call', 'pfcrt_k76', 'T')],
    );

    expect($result->outcome)->toBe(AssessmentOutcome::ResistanceAssociatedPattern)
        ->and($result->evaluatedRules[0]['match_status'])->toBe('matched')
        ->and($result->explanation)->toContain('not a clinical diagnosis');
});

test('known calls that do not match any applicable rule produce no qualifying marker', function () {
    $result = (new ResistanceAssessmentEngine)->evaluate(
        ['pfcrt_k76' => 'K'],
        [syntheticResistanceRule('synthetic-pfcrt-call', 'pfcrt_k76', 'T')],
    );

    expect($result->outcome)->toBe(AssessmentOutcome::NoQualifyingMarker)
        ->and($result->evaluatedRules[0]['match_status'])->toBe('not_matched')
        ->and($result->explanation)->toContain('does not prove chloroquine susceptibility');
});

test('no active rules produce an indeterminate result', function () {
    $result = (new ResistanceAssessmentEngine)->evaluate(
        ['pfcrt_k76' => 'K'],
        [],
    );

    expect($result->outcome)->toBe(AssessmentOutcome::Indeterminate)
        ->and($result->evaluatedRules)->toBe([]);
});

test('missing, uncertain, or unsupported calls remain indeterminate', function (?string $call) {
    $result = (new ResistanceAssessmentEngine)->evaluate(
        ['pfcrt_k76' => $call],
        [syntheticResistanceRule('synthetic-pfcrt-call', 'pfcrt_k76', 'T')],
    );

    expect($result->outcome)->toBe(AssessmentOutcome::Indeterminate)
        ->and($result->evaluatedRules[0]['match_status'])->toBe('insufficient_input');
})->with([
    'missing' => null,
    'unknown' => 'unknown',
    'mixed' => 'mixed',
    'conflicting' => 'conflicting',
    'not tested' => 'not_tested',
    'unsupported' => 'resistant',
]);

test('multiple rule matches are preserved in the evaluation snapshot', function () {
    $result = (new ResistanceAssessmentEngine)->evaluate(
        [
            'pfcrt_k76' => 'T',
            'pfmdr1_n86' => 'Y',
        ],
        [
            syntheticResistanceRule('synthetic-pfcrt-call', 'pfcrt_k76', 'T'),
            syntheticResistanceRule('synthetic-pfmdr1-call', 'pfmdr1_n86', 'Y'),
        ],
    );

    expect($result->outcome)->toBe(AssessmentOutcome::ResistanceAssociatedPattern)
        ->and($result->evaluatedRules)->toHaveCount(2)
        ->and($result->toSnapshot()['outcome'])->toBe('resistance_associated_pattern');
});

test('the same rule set has the same version regardless of input order', function () {
    $firstRule = syntheticResistanceRule('synthetic-rule-a', 'pfcrt_k76', 'T');
    $secondRule = syntheticResistanceRule('synthetic-rule-b', 'pfmdr1_n86', 'Y');
    $engine = new ResistanceAssessmentEngine;

    $first = $engine->evaluate(
        ['pfcrt_k76' => 'T', 'pfmdr1_n86' => 'Y'],
        [$firstRule, $secondRule],
    );
    $second = $engine->evaluate(
        ['pfcrt_k76' => 'T', 'pfmdr1_n86' => 'Y'],
        [$secondRule, $firstRule],
    );

    expect($first->ruleSetVersion)->toBe($second->ruleSetVersion)
        ->and($first->evaluatedRules)->toBe($second->evaluatedRules);
});
