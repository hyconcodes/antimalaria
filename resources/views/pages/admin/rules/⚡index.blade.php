<?php

use App\Domain\ResistanceAssessment\MarkerCatalog;
use App\Enums\RuleStatus;
use App\Models\AssessmentRule;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Scientific rule catalogue')] class extends Component {
    public ?int $editingId = null;

    public string $stableKey = '';

    public string $name = '';

    public string $markerKey = 'pfcrt_k76';

    public string $expectedValue = '';

    public string $description = '';

    public string $scientificRationale = '';

    public string $interpretation = '';

    public string $evidenceClassification = 'candidate';

    public string $sourceTitle = '';

    public string $sourceUrl = '';

    public string $sourceDoi = '';

    public string $sourcePublishedOn = '';

    public string $sourceEvidenceNotes = '';

    public string $sourceReviewedOn = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', AssessmentRule::class);
    }

    public function saveDraft(): void
    {
        $rule = $this->editingId === null
            ? null
            : AssessmentRule::query()->with('sources')->findOrFail($this->editingId);

        if ($rule === null) {
            Gate::authorize('create', AssessmentRule::class);
        } else {
            Gate::authorize('update', $rule);
        }

        $validated = $this->validate([
            'stableKey' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9._-]*$/'],
            'name' => ['required', 'string', 'max:160'],
            'markerKey' => ['required', Rule::in(array_keys(MarkerCatalog::definitions()))],
            'expectedValue' => ['required', Rule::in(array_keys(MarkerCatalog::ruleValues($this->markerKey)))],
            'description' => ['required', 'string', 'max:2000'],
            'scientificRationale' => ['required', 'string', 'max:4000'],
            'interpretation' => ['required', 'string', 'max:2000'],
            'evidenceClassification' => ['required', Rule::in(['validated', 'candidate', 'other'])],
            'sourceTitle' => ['required', 'string', 'max:255'],
            'sourceUrl' => ['required', 'url', 'starts_with:https://', 'max:2048'],
            'sourceDoi' => ['nullable', 'string', 'max:150'],
            'sourcePublishedOn' => ['nullable', 'date', 'before_or_equal:today'],
            'sourceEvidenceNotes' => ['nullable', 'string', 'max:2000'],
            'sourceReviewedOn' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        DB::transaction(function () use ($rule, $validated): void {
            if ($rule === null) {
                $revision = (int) AssessmentRule::query()
                    ->where('stable_key', $validated['stableKey'])
                    ->max('revision') + 1;

                $rule = AssessmentRule::query()->create([
                    'stable_key' => $validated['stableKey'],
                    'revision' => $revision,
                    'marker_key' => $validated['markerKey'],
                    'expected_value' => $validated['expectedValue'],
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'scientific_rationale' => $validated['scientificRationale'],
                    'interpretation' => $validated['interpretation'],
                    'evidence_classification' => $validated['evidenceClassification'],
                    'status' => RuleStatus::Draft,
                    'enabled' => false,
                    'created_by' => auth()->id(),
                ]);

                $action = 'draft_created';
                $before = null;
            } else {
                $before = $this->ruleSnapshot($rule);

                $rule->update([
                    'stable_key' => $validated['stableKey'],
                    'marker_key' => $validated['markerKey'],
                    'expected_value' => $validated['expectedValue'],
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'scientific_rationale' => $validated['scientificRationale'],
                    'interpretation' => $validated['interpretation'],
                    'evidence_classification' => $validated['evidenceClassification'],
                ]);

                $action = 'draft_updated';
            }

            $source = $rule->sources()->first() ?? $rule->sources()->make();
            $source->fill([
                'title' => $validated['sourceTitle'],
                'url' => $validated['sourceUrl'],
                'doi' => $validated['sourceDoi'] ?: null,
                'published_on' => $validated['sourcePublishedOn'] ?: null,
                'evidence_notes' => $validated['sourceEvidenceNotes'] ?: null,
                'reviewed_on' => $validated['sourceReviewedOn'] ?: null,
            ]);
            $source->save();

            $this->writeAudit($rule->fresh('sources'), $action, $before);
        });

        $this->resetEditor();
        Flux::toast(variant: 'success', text: 'Draft rule saved. It does not affect assessments until approved and enabled.');
    }

    public function editRule(int $ruleId): void
    {
        $rule = AssessmentRule::query()->with('sources')->findOrFail($ruleId);
        Gate::authorize('update', $rule);

        $source = $rule->sources->first();
        $this->editingId = $rule->id;
        $this->stableKey = $rule->stable_key;
        $this->name = $rule->name;
        $this->markerKey = $rule->marker_key;
        $this->expectedValue = $rule->expected_value;
        $this->description = $rule->description;
        $this->scientificRationale = $rule->scientific_rationale;
        $this->interpretation = $rule->interpretation;
        $this->evidenceClassification = $rule->evidence_classification;
        $this->sourceTitle = $source?->title ?? '';
        $this->sourceUrl = $source?->url ?? '';
        $this->sourceDoi = $source?->doi ?? '';
        $this->sourcePublishedOn = $source?->published_on?->toDateString() ?? '';
        $this->sourceEvidenceNotes = $source?->evidence_notes ?? '';
        $this->sourceReviewedOn = $source?->reviewed_on?->toDateString() ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetEditor();
    }

    public function submitForReview(int $ruleId): void
    {
        $rule = AssessmentRule::query()->with('sources')->findOrFail($ruleId);
        Gate::authorize('submit', $rule);

        if ($rule->sources->isEmpty()) {
            throw ValidationException::withMessages([
                'sourceTitle' => 'Add at least one evidence source before submitting this rule for review.',
            ]);
        }

        $this->recordTransition($rule, RuleStatus::PendingReview, 'submitted_for_review');
    }

    public function approveRule(int $ruleId): void
    {
        $rule = AssessmentRule::query()->with('sources')->findOrFail($ruleId);
        Gate::authorize('approve', $rule);

        if ($rule->sources->isEmpty()) {
            throw ValidationException::withMessages([
                'sourceTitle' => 'A rule needs at least one evidence source before it can be approved.',
            ]);
        }

        $this->recordTransition($rule, RuleStatus::Approved, 'approved', [
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    public function activateRule(int $ruleId): void
    {
        $rule = AssessmentRule::query()->with('sources')->findOrFail($ruleId);
        Gate::authorize('activate', $rule);

        DB::transaction(function () use ($rule): void {
            $previousRules = AssessmentRule::query()
                ->with('sources')
                ->where('stable_key', $rule->stable_key)
                ->where('enabled', true)
                ->where('id', '!=', $rule->id)
                ->lockForUpdate()
                ->get();

            foreach ($previousRules as $previousRule) {
                $before = $this->ruleSnapshot($previousRule);
                $previousRule->update([
                    'status' => RuleStatus::Retired,
                    'enabled' => false,
                ]);
                $this->writeAudit($previousRule->fresh('sources'), 'superseded_by_new_revision', $before);
            }

            $before = $this->ruleSnapshot($rule);
            $rule->update(['enabled' => true]);
            $this->writeAudit($rule->fresh('sources'), 'enabled', $before);
        });

        Flux::toast(variant: 'success', text: 'Approved rule enabled for future assessments.');
    }

    public function retireRule(int $ruleId): void
    {
        $rule = AssessmentRule::query()->with('sources')->findOrFail($ruleId);
        Gate::authorize('retire', $rule);

        $this->recordTransition($rule, RuleStatus::Retired, 'retired', ['enabled' => false]);
    }

    #[Computed]
    public function catalogRules(): EloquentCollection
    {
        Gate::authorize('viewAny', AssessmentRule::class);

        return AssessmentRule::query()
            ->with(['sources', 'creator', 'reviewer', 'audits.actor'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function recordTransition(
        AssessmentRule $rule,
        RuleStatus $status,
        string $action,
        array $attributes = [],
    ): void {
        DB::transaction(function () use ($rule, $status, $action, $attributes): void {
            $before = $this->ruleSnapshot($rule);
            $rule->update([
                ...$attributes,
                'status' => $status,
                'enabled' => false,
            ]);
            $this->writeAudit($rule->fresh('sources'), $action, $before);
        });

        Flux::toast(variant: 'success', text: 'Rule status updated.');
    }

    /**
     * @param array<string, mixed>|null $before
     */
    private function writeAudit(AssessmentRule $rule, string $action, ?array $before): void
    {
        $rule->audits()->create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'before_snapshot' => $before,
            'after_snapshot' => $this->ruleSnapshot($rule),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function ruleSnapshot(AssessmentRule $rule): array
    {
        return [
            'stable_key' => $rule->stable_key,
            'revision' => $rule->revision,
            'marker_key' => $rule->marker_key,
            'expected_value' => $rule->expected_value,
            'name' => $rule->name,
            'description' => $rule->description,
            'scientific_rationale' => $rule->scientific_rationale,
            'interpretation' => $rule->interpretation,
            'evidence_classification' => $rule->evidence_classification,
            'status' => $rule->status->value,
            'enabled' => $rule->enabled,
            'reviewed_by' => $rule->reviewed_by,
            'reviewed_at' => $rule->reviewed_at?->toIso8601String(),
            'sources' => $rule->sources
                ->sortBy('id')
                ->map(fn ($source): array => [
                    'title' => $source->title,
                    'url' => $source->url,
                    'doi' => $source->doi,
                    'published_on' => $source->published_on?->toDateString(),
                    'evidence_notes' => $source->evidence_notes,
                    'reviewed_on' => $source->reviewed_on?->toDateString(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function resetEditor(): void
    {
        $this->reset(
            'editingId',
            'stableKey',
            'name',
            'expectedValue',
            'description',
            'scientificRationale',
            'interpretation',
            'sourceTitle',
            'sourceUrl',
            'sourceDoi',
            'sourcePublishedOn',
            'sourceEvidenceNotes',
            'sourceReviewedOn',
        );
        $this->markerKey = array_key_first(MarkerCatalog::definitions());
        $this->evidenceClassification = 'candidate';
        $this->resetValidation();
    }
}; ?>

<div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-3">
            <flux:heading size="xl">Scientific rule catalogue</flux:heading>
            <flux:text>Rule changes are versioned, source-linked, and audited. Only approved and enabled rules affect new assessments.</flux:text>
        </header>

        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.heading>Approval is an application control, not independent scientific validation</flux:callout.heading>
            <flux:callout.text>
                This installation uses a deployment-configured administrator allow-list. A single administrator can submit and approve a rule; an independent second-person review is not enforced. Confirm evidence and intended use before enabling any rule.
            </flux:callout.text>
        </flux:callout>

        <section class="grid gap-8 xl:grid-cols-[minmax(20rem,1fr)_minmax(0,1.5fr)]">
            <form wire:submit="saveDraft" class="flex flex-col gap-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-2">
                    <flux:heading size="lg">{{ $editingId === null ? 'Create draft rule' : 'Edit draft rule' }}</flux:heading>
                    <flux:text>Rules match one supported marker call exactly. Executable expressions and arbitrary rule code are not accepted.</flux:text>
                </div>

                <flux:field>
                    <flux:label>Stable rule key</flux:label>
                    <flux:input wire:model="stableKey" maxlength="100" placeholder="e.g. pfcrt-k76t-marker-pattern" />
                    <flux:description>Use a stable identifier; a later revision receives a new revision number.</flux:description>
                    <flux:error name="stableKey" />
                </flux:field>

                <flux:field>
                    <flux:label>Rule name</flux:label>
                    <flux:input wire:model="name" maxlength="160" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Marker</flux:label>
                    <flux:select wire:model="markerKey">
                        @foreach (MarkerCatalog::markerLabels() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="markerKey" />
                </flux:field>

                <flux:field>
                    <flux:label>Exact call to match</flux:label>
                    <flux:select wire:model="expectedValue">
                        <option value="">Choose a known call</option>
                        @foreach (MarkerCatalog::ruleValues($markerKey) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="expectedValue" />
                </flux:field>

                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="2" maxlength="2000" />
                    <flux:error name="description" />
                </flux:field>

                <flux:field>
                    <flux:label>Scientific rationale</flux:label>
                    <flux:textarea wire:model="scientificRationale" rows="3" maxlength="4000" />
                    <flux:error name="scientificRationale" />
                </flux:field>

                <flux:field>
                    <flux:label>User-facing interpretation</flux:label>
                    <flux:textarea wire:model="interpretation" rows="3" maxlength="2000" />
                    <flux:description>Describe a marker-level finding only; do not state a diagnosis or treatment recommendation.</flux:description>
                    <flux:error name="interpretation" />
                </flux:field>

                <flux:field>
                    <flux:label>Evidence classification</flux:label>
                    <flux:select wire:model="evidenceClassification">
                        <option value="validated">Validated</option>
                        <option value="candidate">Candidate</option>
                        <option value="other">Other / locally reviewed</option>
                    </flux:select>
                    <flux:error name="evidenceClassification" />
                </flux:field>

                <flux:separator />

                <div class="flex flex-col gap-4">
                    <flux:heading size="sm">Evidence source</flux:heading>
                    <flux:field>
                        <flux:label>Source title</flux:label>
                        <flux:input wire:model="sourceTitle" maxlength="255" />
                        <flux:error name="sourceTitle" />
                    </flux:field>
                    <flux:field>
                        <flux:label>HTTPS URL</flux:label>
                        <flux:input wire:model="sourceUrl" type="url" maxlength="2048" placeholder="https://" />
                        <flux:error name="sourceUrl" />
                    </flux:field>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>DOI (optional)</flux:label>
                            <flux:input wire:model="sourceDoi" maxlength="150" />
                            <flux:error name="sourceDoi" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Publication date (optional)</flux:label>
                            <flux:input wire:model="sourcePublishedOn" type="date" />
                            <flux:error name="sourcePublishedOn" />
                        </flux:field>
                    </div>
                    <flux:field>
                        <flux:label>Evidence notes</flux:label>
                        <flux:textarea wire:model="sourceEvidenceNotes" rows="2" maxlength="2000" />
                        <flux:error name="sourceEvidenceNotes" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Reviewed on (optional)</flux:label>
                        <flux:input wire:model="sourceReviewedOn" type="date" />
                        <flux:error name="sourceReviewedOn" />
                    </flux:field>
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveDraft">
                        <span wire:loading.remove wire:target="saveDraft">Save draft</span>
                        <span wire:loading wire:target="saveDraft">Saving…</span>
                    </flux:button>
                    @if ($editingId !== null)
                        <flux:button type="button" variant="ghost" wire:click="cancelEdit">Cancel edit</flux:button>
                    @endif
                </div>
            </form>

            <div class="flex flex-col gap-5">
                @forelse ($this->catalogRules as $rule)
                    <article wire:key="rule-{{ $rule->id }}" class="flex flex-col gap-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex flex-col gap-1">
                                <flux:heading size="sm">{{ $rule->name }}</flux:heading>
                                <flux:text>{{ $rule->stable_key }} · revision {{ $rule->revision }}</flux:text>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <flux:badge>{{ $rule->status->label() }}</flux:badge>
                                @if ($rule->enabled)
                                    <flux:badge color="green">Enabled</flux:badge>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-3 text-sm sm:grid-cols-2">
                            <p><span class="text-zinc-500 dark:text-zinc-400">Call:</span> {{ MarkerCatalog::markerLabels()[$rule->marker_key] ?? $rule->marker_key }} = {{ $rule->expected_value }}</p>
                            <p><span class="text-zinc-500 dark:text-zinc-400">Evidence:</span> {{ ucfirst($rule->evidence_classification) }}</p>
                        </div>
                        <p class="text-sm leading-6">{{ $rule->interpretation }}</p>

                        <div class="flex flex-wrap gap-2">
                            @if (in_array($rule->status, [RuleStatus::Draft, RuleStatus::PendingReview], true))
                                <flux:button size="sm" variant="ghost" wire:click="editRule({{ $rule->id }})">Edit draft</flux:button>
                            @endif
                            @if ($rule->status === RuleStatus::Draft)
                                <flux:button size="sm" variant="ghost" wire:click="submitForReview({{ $rule->id }})">Submit for review</flux:button>
                            @endif
                            @if ($rule->status === RuleStatus::PendingReview)
                                <flux:button size="sm" variant="ghost" wire:click="approveRule({{ $rule->id }})">Approve</flux:button>
                            @endif
                            @if ($rule->status === RuleStatus::Approved && ! $rule->enabled)
                                <flux:button size="sm" variant="primary" wire:click="activateRule({{ $rule->id }})">Enable for new assessments</flux:button>
                            @endif
                            @if ($rule->status === RuleStatus::Approved)
                                <flux:button size="sm" variant="danger" wire:click="retireRule({{ $rule->id }})">Retire</flux:button>
                            @endif
                        </div>

                        @if ($rule->sources->isNotEmpty())
                            <div class="flex flex-col gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                <flux:heading size="sm">Sources</flux:heading>
                                @foreach ($rule->sources as $source)
                                    <a wire:key="rule-{{ $rule->id }}-source-{{ $source->id }}" class="break-words text-sm text-indigo-700 underline dark:text-indigo-300" href="{{ $source->url }}" target="_blank" rel="noopener noreferrer">{{ $source->title }}</a>
                                @endforeach
                            </div>
                        @endif

                        <details class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <summary class="cursor-pointer text-sm font-medium">Audit history ({{ $rule->audits->count() }})</summary>
                            <ol class="mt-3 flex flex-col gap-3">
                                @forelse ($rule->audits->sortByDesc('created_at') as $audit)
                                    <li wire:key="audit-{{ $audit->id }}" class="text-sm">
                                        <span class="font-medium">{{ str_replace('_', ' ', ucfirst($audit->action)) }}</span>
                                        <span class="text-zinc-500 dark:text-zinc-400"> · {{ $audit->actor?->email ?? 'Former account' }} · {{ $audit->created_at->format('Y-m-d H:i') }}</span>
                                        <details class="mt-2">
                                            <summary class="cursor-pointer text-xs text-indigo-700 underline dark:text-indigo-300">View recorded change</summary>
                                            <div class="mt-2 grid gap-3 lg:grid-cols-2">
                                                <div>
                                                    <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Before</p>
                                                    <pre class="mt-1 max-h-56 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-zinc-100 p-3 text-xs dark:bg-zinc-800">{{ json_encode($audit->before_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">After</p>
                                                    <pre class="mt-1 max-h-56 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-zinc-100 p-3 text-xs dark:bg-zinc-800">{{ json_encode($audit->after_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </div>
                                            </div>
                                        </details>
                                    </li>
                                @empty
                                    <li class="text-sm text-zinc-500 dark:text-zinc-400">No rule changes recorded yet.</li>
                                @endforelse
                            </ol>
                        </details>
                    </article>
                @empty
                    <section class="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:heading size="sm">No rules in the catalogue</flux:heading>
                        <flux:text class="mt-2">Create and document a draft only after consulting appropriate scientific evidence. No rule is pre-approved by this application.</flux:text>
                    </section>
                @endforelse
            </div>
        </section>
</div>
