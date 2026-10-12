<?php

namespace App\Models;

use App\Enums\RuleStatus;
use Database\Factories\AssessmentRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property RuleStatus $status
 */
#[Fillable([
    'stable_key',
    'revision',
    'marker_key',
    'expected_value',
    'name',
    'description',
    'scientific_rationale',
    'interpretation',
    'evidence_classification',
    'status',
    'enabled',
    'created_by',
    'reviewed_by',
    'reviewed_at',
])]
class AssessmentRule extends Model
{
    /** @use HasFactory<AssessmentRuleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RuleStatus::class,
            'enabled' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return HasMany<AssessmentRuleSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(AssessmentRuleSource::class);
    }

    /** @return HasMany<AssessmentRuleAudit, $this> */
    public function audits(): HasMany
    {
        return $this->hasMany(AssessmentRuleAudit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAssessmentSnapshot(): array
    {
        return [
            'rule_key' => $this->stable_key,
            'revision' => $this->revision,
            'name' => $this->name,
            'description' => $this->description,
            'scientific_rationale' => $this->scientific_rationale,
            'marker_key' => $this->marker_key,
            'expected_value' => $this->expected_value,
            'interpretation' => $this->interpretation,
            'evidence_classification' => $this->evidence_classification,
            'sources' => $this->sources
                ->sortBy('id')
                ->map(fn (AssessmentRuleSource $source): array => [
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
}
