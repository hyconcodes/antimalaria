@php
    use App\Enums\AssessmentOutcome;
@endphp

<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-2">
                <flux:heading size="xl">Research dashboard</flux:heading>
                <flux:text>Review your saved marker assessments and the current approved rule catalogue.</flux:text>
            </div>
            <flux:button variant="primary" :href="route('assessments.create')" wire:navigate>Start an assessment</flux:button>
        </header>

        <section class="grid gap-4 sm:grid-cols-2">
            <article class="flex flex-col gap-2 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>Your saved assessments</flux:text>
                <flux:heading size="xl">{{ $totalAssessments }}</flux:heading>
                <flux:text>Only records associated with your account are included.</flux:text>
            </article>
            <article class="flex flex-col gap-2 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>Approved, enabled rules</flux:text>
                <flux:heading size="xl">{{ $activeRuleCount }}</flux:heading>
                <flux:text>{{ $activeRuleCount === 0 ? 'No rules are active; new assessments will be indeterminate.' : 'Applicable enabled rules are used for future assessments only.' }}</flux:text>
            </article>
        </section>

        <section class="flex flex-col gap-4">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">Recent assessments</flux:heading>
                @if ($totalAssessments > 0)
                    <flux:button variant="ghost" :href="route('assessments.index')" wire:navigate>View history</flux:button>
                @endif
            </div>

            @if ($recentAssessments->isEmpty())
                <div class="flex flex-col items-start gap-4 rounded-2xl border border-dashed border-zinc-300 bg-white p-8 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="sm">No assessments yet</flux:heading>
                    <flux:text>Your assessment history will appear here after you submit a pseudonymous sample reference and available marker calls.</flux:text>
                    <flux:button variant="primary" :href="route('assessments.create')" wire:navigate>Create first assessment</flux:button>
                </div>
            @else
                <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            <tr>
                                <th scope="col" class="px-5 py-4">Sample reference</th>
                                <th scope="col" class="px-5 py-4">Application result</th>
                                <th scope="col" class="px-5 py-4">Assessed</th>
                                <th scope="col" class="px-5 py-4"><span class="sr-only">Open result</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                            @foreach ($recentAssessments as $assessment)
                                <tr wire:key="recent-assessment-{{ $assessment->id }}">
                                    <td class="whitespace-nowrap px-5 py-4 font-medium">{{ $assessment->sample_reference }}</td>
                                    <td class="px-5 py-4"><flux:badge>{{ $assessment->result_code->label() }}</flux:badge></td>
                                    <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $assessment->assessed_at->format('Y-m-d H:i') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <flux:button size="sm" variant="ghost" :href="route('assessments.show', $assessment->id)" wire:navigate>View result</flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Results are marker-level research findings</flux:heading>
            <flux:text>
                “No qualifying marker detected” does not prove susceptibility. Incomplete or unsupported calls may result in an indeterminate assessment. This application does not diagnose malaria, recommend treatment, or replace expert interpretation.
            </flux:text>
            <div class="flex flex-wrap gap-3">
                @foreach (AssessmentOutcome::cases() as $outcome)
                    <div class="flex items-center gap-2">
                        <flux:badge>{{ $outcome->label() }}</flux:badge>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $outcomeCounts[$outcome->value] ?? 0 }} records</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts::app>
