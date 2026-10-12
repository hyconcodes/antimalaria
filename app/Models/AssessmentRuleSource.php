<?php

namespace App\Models;

use Database\Factories\AssessmentRuleSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $published_on
 * @property Carbon|null $reviewed_on
 */
#[Fillable([
    'title',
    'url',
    'doi',
    'published_on',
    'evidence_notes',
    'reviewed_on',
])]
class AssessmentRuleSource extends Model
{
    /** @use HasFactory<AssessmentRuleSourceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_on' => 'date',
            'reviewed_on' => 'date',
        ];
    }

    /** @return BelongsTo<AssessmentRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AssessmentRule::class, 'assessment_rule_id');
    }
}
