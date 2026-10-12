<?php

use App\Domain\ResistanceAssessment\MarkerCatalog;
use App\Models\Assessment;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Assessment result')] class extends Component {
    #[Locked]
    public Assessment $assessment;

    public function mount(string $assessmentId): void
    {
        $assessment = auth()->user()->assessments()->findOrFail($assessmentId);

        $this->authorize('view', $assessment);
        $this->assessment = $assessment;
    }
}; ?>

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-2">
                <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('assessments.index')" wire:navigate class="w-fit">
                    Assessment history
                </flux:button>
                <flux:heading size="xl">Assessment result</flux:heading>
                <flux:text>Assessment #{{ $assessment->id }} · {{ $assessment->assessed_at->format('Y-m-d H:i') }}</flux:text>
            </div>
            <flux:button variant="primary" :href="route('assessments.create')" wire:navigate>New assessment</flux:button>
        </header>

        <section class="grid gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
            <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-2">
                    <flux:text>Application outcome</flux:text>
                    <flux:heading size="lg">{{ $assessment->result_code->label() }}</flux:heading>
                </div>
                <flux:text>{{ $assessment->result_explanation }}</flux:text>
                <p class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm leading-6 text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                    Research and demonstration support only. This result is not a clinical diagnosis, does not establish treatment response, and must not be used to select treatment.
                </p>
            </div>

            <dl class="grid content-start gap-4 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-1">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Sample reference</dt>
                    <dd class="font-medium">{{ $assessment->sample_reference }}</dd>
                </div>
                <div class="flex flex-col gap-1">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Collection date</dt>
                    <dd>{{ $assessment->collection_date?->format('Y-m-d') ?? 'Not provided' }}</dd>
                </div>
                <div class="flex flex-col gap-1">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Rule-set version</dt>
                    <dd class="break-all font-mono text-xs">{{ $assessment->rule_set_version }}</dd>
                </div>
            </dl>
        </section>

        <section class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <flux:heading size="lg">Submitted marker calls</flux:heading>
                <flux:text>Unknown, missing, mixed, conflicting, or untested values are displayed explicitly and are never converted to reference alleles.</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach (MarkerCatalog::definitions() as $markerKey => $marker)
                    @php($call = data_get($assessment->input_snapshot, 'marker_calls.'.$markerKey))
                    <article wire:key="marker-{{ $markerKey }}" class="flex flex-col gap-2 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text>{{ $marker['gene'] }} · {{ $marker['label'] }}</flux:text>
                        <flux:heading size="sm">{{ $call === null || $call === '' ? 'Missing — not interpreted as a reference call' : ($marker['calls'][$call] ?? 'Unsupported value') }}</flux:heading>
                    </article>
                @endforeach
            </div>
        </section>

        @if (filled($assessment->notes))
            <section class="flex flex-col gap-2 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm">Notes</flux:heading>
                <p class="whitespace-pre-wrap text-sm leading-6">{{ $assessment->notes }}</p>
            </section>
        @endif

        <section class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <flux:heading size="lg">Rule evaluation snapshot</flux:heading>
                <flux:text>Historical results preserve rule definitions and references as recorded at assessment time; this page does not re-evaluate old input.</flux:text>
            </div>

            @if (empty($assessment->evaluation_snapshot['evaluated_rules']))
                <div class="rounded-2xl border border-dashed border-zinc-300 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text>No approved, enabled rules were available for this assessment.</flux:text>
                </div>
            @else
                <div class="grid gap-5">
                    @foreach ($assessment->evaluation_snapshot['evaluated_rules'] as $rule)
                        <article wire:key="rule-{{ $rule['rule_key'] }}-{{ $rule['revision'] }}" class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="flex flex-col gap-1">
                                    <flux:heading size="sm">{{ $rule['name'] }}</flux:heading>
                                    <flux:text>{{ $rule['rule_key'] }} · revision {{ $rule['revision'] }}</flux:text>
                                </div>
                                <flux:badge>{{ str_replace('_', ' ', ucfirst($rule['match_status'])) }}</flux:badge>
                            </div>

                            <p class="text-sm leading-6">{{ $rule['interpretation'] }}</p>

                            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Scientific rationale</dt>
                                    <dd class="mt-1 leading-6">{{ $rule['scientific_rationale'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">Evidence classification</dt>
                                    <dd class="mt-1">{{ ucfirst($rule['evidence_classification']) }}</dd>
                                </div>
                            </dl>

                            @if (count($rule['sources']) > 0)
                                <div class="flex flex-col gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                    <flux:heading size="sm">Evidence sources</flux:heading>
                                    @foreach ($rule['sources'] as $source)
                                        <div wire:key="source-{{ $loop->parent->index }}-{{ $loop->index }}" class="flex flex-col gap-1 text-sm">
                                            <a class="break-words font-medium text-indigo-700 underline dark:text-indigo-300" href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">
                                                {{ $source['title'] }}
                                            </a>
                                            @if (filled($source['doi']))
                                                <span class="text-zinc-600 dark:text-zinc-300">DOI: {{ $source['doi'] }}</span>
                                            @endif
                                            @if (filled($source['evidence_notes']))
                                                <span class="text-zinc-600 dark:text-zinc-300">{{ $source['evidence_notes'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
</div>
