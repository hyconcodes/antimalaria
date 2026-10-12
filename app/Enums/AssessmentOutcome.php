<?php

namespace App\Enums;

enum AssessmentOutcome: string
{
    case ResistanceAssociatedPattern = 'resistance_associated_pattern';
    case NoQualifyingMarker = 'no_qualifying_marker';
    case Indeterminate = 'indeterminate';

    public function label(): string
    {
        return match ($this) {
            self::ResistanceAssociatedPattern => 'Resistance-associated marker pattern identified',
            self::NoQualifyingMarker => 'No qualifying marker detected',
            self::Indeterminate => 'Indeterminate or insufficient information',
        };
    }
}
