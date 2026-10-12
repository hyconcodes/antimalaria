<?php

namespace App\Domain\ResistanceAssessment;

class MarkerCatalog
{
    /**
     * @var array<string, array{
     *     gene: string,
     *     label: string,
     *     description: string,
     *     values: array<string, string>,
     *     calls: array<string, string>
     * }>
     */
    private const MARKERS = [
        'pfcrt_k76' => [
            'gene' => 'PfCRT',
            'label' => 'PfCRT codon 76',
            'description' => 'Record the observed amino-acid call at codon 76.',
            'values' => ['K' => 'K (lysine)', 'T' => 'T (threonine)'],
            'calls' => [
                'K' => 'K (lysine)',
                'T' => 'T (threonine)',
                'other' => 'Other call',
                'mixed' => 'Mixed call',
                'unknown' => 'Unknown or uncallable',
                'not_tested' => 'Not tested',
                'conflicting' => 'Conflicting result',
            ],
        ],
        'pfcrt_haplotype_72_76' => [
            'gene' => 'PfCRT',
            'label' => 'PfCRT codons 72–76 haplotype',
            'description' => 'Record a resolved 72–76 haplotype. CVIET and SVMNT are listed in the WHO compendium.',
            'values' => ['CVIET' => 'CVIET', 'SVMNT' => 'SVMNT'],
            'calls' => [
                'CVIET' => 'CVIET',
                'SVMNT' => 'SVMNT',
                'other' => 'Other haplotype',
                'mixed' => 'Mixed haplotype call',
                'unknown' => 'Unknown or uncallable',
                'not_tested' => 'Not tested',
                'conflicting' => 'Conflicting result',
            ],
        ],
        'pfmdr1_n86' => [
            'gene' => 'PfMDR1',
            'label' => 'PfMDR1 codon 86',
            'description' => 'Record the observed amino-acid call at codon 86.',
            'values' => ['N' => 'N (asparagine)', 'Y' => 'Y (tyrosine)'],
            'calls' => [
                'N' => 'N (asparagine)',
                'Y' => 'Y (tyrosine)',
                'other' => 'Other call',
                'mixed' => 'Mixed call',
                'unknown' => 'Unknown or uncallable',
                'not_tested' => 'Not tested',
                'conflicting' => 'Conflicting result',
            ],
        ],
    ];

    /**
     * @return array<string, array{
     *     gene: string,
     *     label: string,
     *     description: string,
     *     values: array<string, string>,
     *     calls: array<string, string>
     * }>
     */
    public static function definitions(): array
    {
        return self::MARKERS;
    }

    /**
     * @return array<string, string>
     */
    public static function markerLabels(): array
    {
        $labels = [];

        foreach (self::MARKERS as $key => $marker) {
            $labels[$key] = $marker['gene'].' — '.$marker['label'];
        }

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    public static function ruleValues(string $markerKey): array
    {
        return self::MARKERS[$markerKey]['values'] ?? [];
    }

    public static function isValidCall(string $markerKey, ?string $call): bool
    {
        return $call === null || isset(self::MARKERS[$markerKey]['calls'][$call]);
    }

    public static function isKnownCall(string $markerKey, ?string $call): bool
    {
        return $call !== null && isset(self::MARKERS[$markerKey]['values'][$call]);
    }
}
