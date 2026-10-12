<?php

use App\Actions\ResistanceAssessment\CreateAssessment;
use App\Domain\ResistanceAssessment\MarkerCatalog;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New assessment')] class extends Component {
    public string $sampleReference = '';

    public string $collectionDate = '';

    public string $notes = '';

    public array $markerCalls = [];

    #[Locked]
    public string $submissionToken = '';

    public function mount(): void
    {
        $this->submissionToken = (string) Str::uuid();
        $this->collectionDate = now()->toDateString();

        foreach (MarkerCatalog::definitions() as $markerKey => $marker) {
            $this->markerCalls[$markerKey] = '';
        }
    }

    public function save(CreateAssessment $createAssessment): void
    {
        $markerRules = [];

        foreach (MarkerCatalog::definitions() as $markerKey => $marker) {
            $markerRules['markerCalls.'.$markerKey] = [
                'nullable',
                'string',
                Rule::in(['', ...array_keys($marker['calls'])]),
            ];
        }

        $validated = $this->validate([
            'sampleReference' => ['required', 'string', 'max:100'],
            'collectionDate' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'markerCalls' => ['required', 'array:'.implode(',', array_keys(MarkerCatalog::definitions()))],
            ...$markerRules,
        ], [
            'sampleReference.required' => 'Enter a pseudonymous sample reference.',
            'collectionDate.before_or_equal' => 'The collection date cannot be in the future.',
        ]);

        $markerCalls = array_map(
            static fn (?string $call): ?string => $call === '' ? null : $call,
            $validated['markerCalls'],
        );

        $assessment = $createAssessment->handle(
            auth()->user(),
            [
                'sample_reference' => $validated['sampleReference'],
                'collection_date' => $validated['collectionDate'] ?: null,
                'notes' => $validated['notes'] ?: null,
                'marker_calls' => $markerCalls,
            ],
            $this->submissionToken,
        );

        $this->redirect(route('assessments.show', $assessment->id), navigate: true);
    }
}; ?>

<div class="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-3">
            <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('assessments.index')" wire:navigate class="w-fit">
                Assessment history
            </flux:button>
            <div class="flex flex-col gap-2">
                <flux:heading size="xl">New marker assessment</flux:heading>
                <flux:text>Record available calls for this sample. Missing or uncertain calls are not treated as reference alleles.</flux:text>
            </div>
        </header>

        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.heading>Research and demonstration support only</flux:callout.heading>
            <flux:callout.text>
                This assessment does not diagnose malaria, establish clinical resistance or susceptibility, or recommend treatment. If no applicable approved rules are enabled, the result will be indeterminate.
            </flux:callout.text>
        </flux:callout>

        <form wire:submit="save" class="flex flex-col gap-8">
            <section class="grid gap-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <flux:field>
                        <flux:label>Sample reference</flux:label>
                        <flux:input wire:model="sampleReference" maxlength="100" required autocomplete="off" placeholder="e.g. STUDY-001" />
                        <flux:description>Use a pseudonymous laboratory or study identifier. Do not enter a patient name or other direct identifier.</flux:description>
                        <flux:error name="sampleReference" />
                    </flux:field>
                </div>

                <div class="flex flex-col gap-2">
                    <flux:field>
                        <flux:label>Collection date</flux:label>
                        <flux:input wire:model="collectionDate" type="date" />
                        <flux:description>Optional; future dates are not accepted.</flux:description>
                        <flux:error name="collectionDate" />
                    </flux:field>
                </div>

                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Notes</flux:label>
                        <flux:textarea wire:model="notes" rows="3" maxlength="2000" placeholder="Optional non-identifying provenance or laboratory notes" />
                        <flux:description>Do not include personally identifying patient information.</flux:description>
                        <flux:error name="notes" />
                    </flux:field>
                </div>
            </section>

            <section class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <flux:heading size="lg">Genetic-marker calls</flux:heading>
                    <flux:text>Only the displayed loci are currently supported. A value of “Other”, “Unknown”, “Mixed”, “Conflicting”, or “Not tested” cannot be silently interpreted as a known allele.</flux:text>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    @foreach (MarkerCatalog::definitions() as $markerKey => $marker)
                        <section wire:key="marker-{{ $markerKey }}" class="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex flex-col gap-1">
                                <flux:heading size="sm">{{ $marker['gene'] }} · {{ $marker['label'] }}</flux:heading>
                                <flux:text>{{ $marker['description'] }}</flux:text>
                            </div>
                            <flux:field>
                                <flux:label>{{ $marker['label'] }} call</flux:label>
                                <flux:select wire:model="markerCalls.{{ $markerKey }}">
                                    <option value="">Not provided</option>
                                    @foreach ($marker['calls'] as $call => $label)
                                        <option value="{{ $call }}">{{ $label }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="markerCalls.{{ $markerKey }}" />
                            </flux:field>
                        </section>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-col gap-4 border-t border-zinc-200 pt-6 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
                <flux:text class="max-w-2xl">The result records the exact rule-set snapshot used. Opening a saved result later will not recalculate it with newer rules.</flux:text>
                <flux:button type="submit" variant="primary" icon="beaker" wire:loading.attr="disabled" wire:target="save" class="shrink-0">
                    <span wire:loading.remove wire:target="save">Evaluate and save</span>
                    <span wire:loading wire:target="save">Evaluating…</span>
                </flux:button>
            </div>
        </form>
</div>
