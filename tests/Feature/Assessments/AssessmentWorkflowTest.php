<?php

use App\Actions\ResistanceAssessment\CreateAssessment;
use App\Enums\AssessmentOutcome;
use App\Enums\RuleStatus;
use App\Models\Assessment;
use App\Models\AssessmentRule;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('guests cannot open assessment pages', function () {
    $this->get(route('assessments.create'))->assertRedirect(route('login'));
    $this->get(route('assessments.index'))->assertRedirect(route('login'));
});

test('a submitted sample is saved with an indeterminate result when no rule is approved and enabled', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::assessments.create')
        ->set('sampleReference', 'STUDY-SYNTHETIC-001')
        ->set('markerCalls.pfcrt_k76', 'T')
        ->call('save')
        ->assertHasNoErrors();

    $assessment = Assessment::query()->where('sample_reference', 'STUDY-SYNTHETIC-001')->firstOrFail();

    $this->assertModelExists($assessment);
    $this->assertDatabaseCount('assessments', 1);
    expect($assessment->user_id)->toBe($user->id)
        ->and($assessment->result_code)->toBe(AssessmentOutcome::Indeterminate)
        ->and($assessment->input_snapshot['marker_calls']['pfcrt_k76'])->toBe('T')
        ->and($assessment->evaluation_snapshot['evaluated_rules'])->toBe([]);
});

test('pending rules never affect a saved assessment', function () {
    $user = User::factory()->create();
    $pendingRule = AssessmentRule::factory()->create([
        'stable_key' => 'synthetic-pending-rule',
        'status' => RuleStatus::PendingReview,
        'enabled' => true,
    ]);
    $pendingRule->sources()->create([
        'title' => 'Synthetic source fixture',
        'url' => 'https://example.org/synthetic',
        'evidence_notes' => 'Synthetic test fixture only.',
    ]);

    $this->actingAs($user);
    Livewire::test('pages::assessments.create')
        ->set('sampleReference', 'STUDY-SYNTHETIC-PENDING')
        ->set('markerCalls.pfcrt_k76', 'T')
        ->call('save')
        ->assertHasNoErrors();

    $assessment = Assessment::query()->where('sample_reference', 'STUDY-SYNTHETIC-PENDING')->firstOrFail();

    expect($assessment->result_code)->toBe(AssessmentOutcome::Indeterminate)
        ->and($assessment->evaluation_snapshot['evaluated_rules'])->toBe([]);
});

test('an invalid marker call is rejected without persisting an assessment', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::assessments.create')
        ->set('sampleReference', 'STUDY-SYNTHETIC-INVALID')
        ->set('markerCalls.pfcrt_k76', 'resistant')
        ->call('save')
        ->assertHasErrors(['markerCalls.pfcrt_k76']);

    $this->assertDatabaseCount('assessments', 0);
});

test('an active rule and its evidence are snapshotted with the assessment', function () {
    $user = User::factory()->create();
    $rule = AssessmentRule::factory()->create([
        'stable_key' => 'synthetic-active-rule',
        'marker_key' => 'pfcrt_k76',
        'expected_value' => 'T',
        'status' => RuleStatus::Approved,
        'enabled' => true,
    ]);
    $rule->sources()->create([
        'title' => 'Synthetic version-one source',
        'url' => 'https://example.org/version-one',
        'evidence_notes' => 'Synthetic test fixture only.',
    ]);

    $this->actingAs($user);
    Livewire::test('pages::assessments.create')
        ->set('sampleReference', 'STUDY-SYNTHETIC-SNAPSHOT')
        ->set('markerCalls.pfcrt_k76', 'T')
        ->call('save')
        ->assertHasNoErrors();

    $assessment = Assessment::query()->where('sample_reference', 'STUDY-SYNTHETIC-SNAPSHOT')->firstOrFail();
    $rule->update(['interpretation' => 'Changed after this assessment was recorded.']);
    $rule->sources()->firstOrFail()->update(['title' => 'Changed source title']);

    $this->get(route('assessments.show', $assessment->id))
        ->assertOk()
        ->assertSee('Synthetic marker-pattern finding for test coverage.')
        ->assertSee('Synthetic version-one source')
        ->assertDontSee('Changed after this assessment was recorded.')
        ->assertDontSee('Changed source title');

    expect($assessment->fresh()->result_code)->toBe(AssessmentOutcome::ResistanceAssociatedPattern)
        ->and($assessment->evaluation_snapshot['evaluated_rules'][0]['sources'][0]['url'])->toBe('https://example.org/version-one');
});

test('a user cannot view another account assessment', function () {
    $owner = User::factory()->create();
    $assessment = Assessment::factory()->for($owner)->create([
        'sample_reference' => 'PRIVATE-SYNTHETIC-SAMPLE',
    ]);
    $this->actingAs(User::factory()->create());

    $this->get(route('assessments.show', $assessment->id))->assertNotFound();
});

test('assessment history only shows the owner’s matching records', function () {
    $owner = User::factory()->create();
    Assessment::factory()->for($owner)->create(['sample_reference' => 'OWNER-SYNTHETIC-MATCH']);
    Assessment::factory()->for($owner)->create(['sample_reference' => 'OWNER-SYNTHETIC-OTHER']);
    Assessment::factory()->for(User::factory()->create())->create(['sample_reference' => 'PRIVATE-SYNTHETIC-RECORD']);

    $this->actingAs($owner);
    Livewire::test('pages::assessments.index')
        ->set('filterSearch', 'OWNER-SYNTHETIC-MATCH')
        ->call('applyFilters')
        ->assertHasNoErrors()
        ->assertSee('OWNER-SYNTHETIC-MATCH')
        ->assertDontSee('OWNER-SYNTHETIC-OTHER')
        ->assertDontSee('PRIVATE-SYNTHETIC-RECORD');
});

test('dashboard metrics and recent assessments are scoped to the signed-in user', function () {
    $owner = User::factory()->create();
    Assessment::factory()->for($owner)->create(['sample_reference' => 'OWNER-DASHBOARD-SYNTHETIC']);
    Assessment::factory()->for(User::factory()->create())->create(['sample_reference' => 'PRIVATE-DASHBOARD-SYNTHETIC']);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('OWNER-DASHBOARD-SYNTHETIC')
        ->assertDontSee('PRIVATE-DASHBOARD-SYNTHETIC');
});

test('assessment submission is idempotent for the same locked submission token', function () {
    $user = User::factory()->create();
    $token = (string) Str::uuid();
    $input = [
        'sample_reference' => 'STUDY-SYNTHETIC-IDEMPOTENT',
        'collection_date' => null,
        'notes' => null,
        'marker_calls' => [
            'pfcrt_k76' => null,
            'pfcrt_haplotype_72_76' => null,
            'pfmdr1_n86' => null,
        ],
    ];
    $creator = app(CreateAssessment::class);

    $first = $creator->handle($user, $input, $token);
    $second = $creator->handle($user, $input, $token);

    expect($first->id)->toBe($second->id);
    $this->assertDatabaseCount('assessments', 1);
});
