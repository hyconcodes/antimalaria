<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'actor_id',
    'action',
    'before_snapshot',
    'after_snapshot',
])]
class AssessmentRuleAudit extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before_snapshot' => 'array',
            'after_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<AssessmentRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AssessmentRule::class, 'assessment_rule_id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
