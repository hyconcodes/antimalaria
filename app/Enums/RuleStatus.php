<?php

namespace App\Enums;

enum RuleStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending review',
            self::Approved => 'Approved',
            self::Retired => 'Retired',
        };
    }
}
