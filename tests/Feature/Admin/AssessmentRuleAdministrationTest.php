<?php

use App\Enums\RuleStatus;
use App\Models\AssessmentRule;
use App\Models\User;
use Livewire\Livewire;

test('non-administrators cannot access rule administration', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.rules.index'))
        ->assertForbidden();
});

test('an allow-listed administrator can create, submit, approve, and enable a versioned rule', function () {
    $admin = User::factory()->create();
    config(['resistance-assessment.admin_emails' => [$admin->email]]);
    $this->actingAs($admin);

    $component = Livewire::test('pages::admin.rules.index')
        ->set('stableKey', 'synthetic-review-test')
        ->set('name', 'Synthetic software-test rule')
        ->set('markerKey', 'pfcrt_k76')
        ->set('expectedValue', 'T')
        ->set('description', 'Synthetic test fixture only.')
        ->set('scientificRationale', 'Synthetic test fixture; not scientific evidence.')
        ->set('interpretation', 'Synthetic marker-pattern finding.')
        ->set('evidenceClassification', 'candidate')
        ->set('sourceTitle', 'Synthetic source fixture')
        ->set('sourceUrl', 'https://example.org/synthetic')
        ->set('sourceEvidenceNotes', 'Synthetic test fixture only.')
        ->call('saveDraft')
        ->assertHasNoErrors();

    $rule = AssessmentRule::query()->where('stable_key', 'synthetic-review-test')->firstOrFail();
    expect($rule->status)->toBe(RuleStatus::Draft)
        ->and($rule->enabled)->toBeFalse();

    $component->call('submitForReview', $rule->id)->assertHasNoErrors();
    expect($rule->fresh()->status)->toBe(RuleStatus::PendingReview)
        ->and($rule->fresh()->enabled)->toBeFalse();

    $component->call('approveRule', $rule->id)->assertHasNoErrors();
    expect($rule->fresh()->status)->toBe(RuleStatus::Approved)
        ->and($rule->fresh()->reviewed_by)->toBe($admin->id)
        ->and($rule->fresh()->enabled)->toBeFalse();

    $component->call('activateRule', $rule->id)->assertHasNoErrors();
    expect($rule->fresh()->status)->toBe(RuleStatus::Approved)
        ->and($rule->fresh()->enabled)->toBeTrue();

    $this->assertDatabaseCount('assessment_rule_audits', 4);
    $this->assertDatabaseHas('assessment_rule_audits', [
        'assessment_rule_id' => $rule->id,
        'actor_id' => $admin->id,
        'action' => 'approved',
    ]);
});

test('enabling a new revision retires the previous active revision', function () {
    $admin = User::factory()->create();
    config(['resistance-assessment.admin_emails' => [$admin->email]]);
    $this->actingAs($admin);

    $previous = AssessmentRule::factory()->create([
        'stable_key' => 'synthetic-versioned-rule',
        'revision' => 1,
        'status' => RuleStatus::Approved,
        'enabled' => true,
    ]);
    $previous->sources()->create([
        'title' => 'Synthetic old source',
        'url' => 'https://example.org/old',
        'evidence_notes' => 'Synthetic test fixture only.',
    ]);

    $component = Livewire::test('pages::admin.rules.index')
        ->set('stableKey', 'synthetic-versioned-rule')
        ->set('name', 'Synthetic revision two')
        ->set('markerKey', 'pfcrt_k76')
        ->set('expectedValue', 'K')
        ->set('description', 'Synthetic test fixture only.')
        ->set('scientificRationale', 'Synthetic test fixture; not scientific evidence.')
        ->set('interpretation', 'Synthetic revised marker finding.')
        ->set('evidenceClassification', 'candidate')
        ->set('sourceTitle', 'Synthetic new source')
        ->set('sourceUrl', 'https://example.org/new')
        ->set('sourceEvidenceNotes', 'Synthetic test fixture only.')
        ->call('saveDraft')
        ->assertHasNoErrors();

    $revision = AssessmentRule::query()
        ->where('stable_key', 'synthetic-versioned-rule')
        ->where('revision', 2)
        ->firstOrFail();

    $component->call('submitForReview', $revision->id)
        ->call('approveRule', $revision->id)
        ->call('activateRule', $revision->id)
        ->assertHasNoErrors();

    expect($previous->fresh()->status)->toBe(RuleStatus::Retired)
        ->and($previous->fresh()->enabled)->toBeFalse()
        ->and($revision->fresh()->status)->toBe(RuleStatus::Approved)
        ->and($revision->fresh()->enabled)->toBeTrue();

    $this->assertDatabaseHas('assessment_rule_audits', [
        'assessment_rule_id' => $previous->id,
        'action' => 'superseded_by_new_revision',
    ]);
});
