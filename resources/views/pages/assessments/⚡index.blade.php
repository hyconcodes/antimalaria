<?php

use App\Enums\AssessmentOutcome;
use App\Models\Assessment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Assessment history')] class extends Component {
    use WithPagination;

    public string $filterSearch = '';

    public string $filterResultCode = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public string $search = '';

    public string $resultCode = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function applyFilters(): void
    {
        $validated = $this->validate([
            'filterSearch' => ['nullable', 'string', 'max:100'],
            'filterResultCode' => [
                'nullable',
                Rule::in(array_map(
                    static fn (AssessmentOutcome $outcome): string => $outcome->value,
                    AssessmentOutcome::cases(),
                )),
            ],
            'filterDateFrom' => ['nullable', 'date'],
            'filterDateTo' => ['nullable', 'date', 'after_or_equal:filterDateFrom'],
        ], [
            'filterDateTo.after_or_equal' => 'The end date must be the same as or after the start date.',
        ]);

        $this->search = $validated['filterSearch'] ?? '';
        $this->resultCode = $validated['filterResultCode'] ?? '';
        $this->dateFrom = $validated['filterDateFrom'] ?? '';
        $this->dateTo = $validated['filterDateTo'] ?? '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(
            'filterSearch',
            'filterResultCode',
            'filterDateFrom',
            'filterDateTo',
            'search',
            'resultCode',
            'dateFrom',
            'dateTo',
        );
        $this->resetPage();
    }

    #[Computed]
    public function assessments(): LengthAwarePaginator
    {
        return auth()->user()->assessments()
            ->when($this->search !== '', fn ($query) => $query->where('sample_reference', 'like', '%'.$this->search.'%'))
            ->when($this->resultCode !== '', fn ($query) => $query->where('result_code', $this->resultCode))
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('assessed_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('assessed_at', '<=', $this->dateTo))
            ->orderByDesc('assessed_at')
            ->orderByDesc('id')
            ->paginate(10);
    }
}; ?>

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-2">
                <flux:heading size="xl">Assessment history</flux:heading>
                <flux:text>Review saved assessments and the rule-set snapshots used at the time.</flux:text>
            </div>
            <flux:button variant="primary" icon="plus" :href="route('assessments.create')" wire:navigate>New assessment</flux:button>
        </header>

        <form wire:submit="applyFilters" class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-2 xl:grid-cols-5">
            <flux:field>
                <flux:label>Sample reference</flux:label>
                <flux:input wire:model="filterSearch" maxlength="100" placeholder="Search this account's records" />
                <flux:error name="filterSearch" />
            </flux:field>

            <flux:field>
                <flux:label>Result</flux:label>
                <flux:select wire:model="filterResultCode">
                    <option value="">All results</option>
                    @foreach (AssessmentOutcome::cases() as $outcome)
                        <option value="{{ $outcome->value }}">{{ $outcome->label() }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="filterResultCode" />
            </flux:field>

            <flux:field>
                <flux:label>Assessed from</flux:label>
                <flux:input wire:model="filterDateFrom" type="date" />
                <flux:error name="filterDateFrom" />
            </flux:field>

            <flux:field>
                <flux:label>Assessed through</flux:label>
                <flux:input wire:model="filterDateTo" type="date" />
                <flux:error name="filterDateTo" />
            </flux:field>

            <div class="flex items-end gap-2">
                <flux:button type="submit" variant="primary" class="flex-1">Apply filters</flux:button>
                <flux:button type="button" variant="ghost" wire:click="resetFilters">Reset</flux:button>
            </div>
        </form>

        @if ($this->assessments->isEmpty())
            <section class="flex flex-col items-start gap-4 rounded-2xl border border-dashed border-zinc-300 bg-white p-8 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ $search !== '' || $resultCode !== '' || $dateFrom !== '' || $dateTo !== '' ? 'No assessments match these filters' : 'No assessments yet' }}</flux:heading>
                <flux:text>{{ $search !== '' || $resultCode !== '' || $dateFrom !== '' || $dateTo !== '' ? 'Try adjusting your filters or reset them to see all of your assessments.' : 'Create an assessment to record marker calls and see the approved rule catalogue’s current coverage.' }}</flux:text>
                @if ($search !== '' || $resultCode !== '' || $dateFrom !== '' || $dateTo !== '')
                    <flux:button variant="ghost" wire:click="resetFilters">Reset filters</flux:button>
                @else
                    <flux:button variant="primary" :href="route('assessments.create')" wire:navigate>Start an assessment</flux:button>
                @endif
            </section>
        @else
            <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th scope="col" class="px-5 py-4">Sample reference</th>
                            <th scope="col" class="px-5 py-4">Result</th>
                            <th scope="col" class="px-5 py-4">Assessed</th>
                            <th scope="col" class="px-5 py-4">Rule-set version</th>
                            <th scope="col" class="px-5 py-4"><span class="sr-only">Open</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        @foreach ($this->assessments as $assessment)
                            <tr wire:key="assessment-{{ $assessment->id }}">
                                <td class="whitespace-nowrap px-5 py-4 font-medium">{{ $assessment->sample_reference }}</td>
                                <td class="px-5 py-4">
                                    <flux:badge>{{ $assessment->result_code->label() }}</flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $assessment->assessed_at->format('Y-m-d H:i') }}</td>
                                <td class="max-w-40 truncate px-5 py-4 font-mono text-xs text-zinc-600 dark:text-zinc-300" title="{{ $assessment->rule_set_version }}">{{ Str::limit($assessment->rule_set_version, 16) }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <flux:button size="sm" variant="ghost" :href="route('assessments.show', $assessment->id)" wire:navigate>View result</flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>{{ $this->assessments->links() }}</div>
        @endif
</div>
