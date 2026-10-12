<?php

use App\Enums\RuleStatus;
use App\Models\AssessmentRule;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();

        return view('dashboard', [
            'totalAssessments' => $user->assessments()->count(),
            'recentAssessments' => $user->assessments()
                ->orderByDesc('assessed_at')
                ->orderByDesc('id')
                ->limit(6)
                ->get(),
            'outcomeCounts' => $user->assessments()
                ->select('result_code')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('result_code')
                ->pluck('total', 'result_code'),
            'activeRuleCount' => AssessmentRule::query()
                ->where('status', RuleStatus::Approved->value)
                ->where('enabled', true)
                ->count(),
        ]);
    })->name('dashboard');

    Route::livewire('assessments/create', 'pages::assessments.create')->name('assessments.create');
    Route::livewire('assessments', 'pages::assessments.index')->name('assessments.index');
    Route::livewire('assessments/{assessmentId}', 'pages::assessments.show')->name('assessments.show');
    Route::livewire('admin/rules', 'pages::admin.rules.index')
        ->middleware('can:viewAny,'.AssessmentRule::class)
        ->name('admin.rules.index');
});

require __DIR__.'/settings.php';
