<?php

namespace App\Models;

use App\Enums\AssessmentOutcome;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'submission_token',
    'sample_reference',
    'collection_date',
    'notes',
    'input_snapshot',
    'result_code',
    'result_explanation',
    'rule_set_version',
    'evaluation_snapshot',
    'assessed_at',
])]
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'input_snapshot' => 'array',
            'evaluation_snapshot' => 'array',
            'result_code' => AssessmentOutcome::class,
            'assessed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
