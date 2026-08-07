<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Support;

use Icd10Prototype\Model\CaseFacts;
use Icd10Prototype\Model\CatalogueRecord;

/**
 * Test-only fixtures. Some vectors (e.g. a COPD relation with FEV1 removed,
 * or a hospital-outpatient status relation with the LKF flag removed) are
 * pure technical fixtures used to isolate a branch (TESTBASE-0.1 Section 2);
 * they are not additional clinical reference cases.
 */
final class Fixtures
{
    /** @param array<string, bool> $responseDomain */
    public static function copdCase(
        string $caseId,
        string $baseCode,
        ?float $fev1,
        array $responseDomain,
        string $encounterSetting = 'inpatient',
        string $diagnosisRole = 'main',
        string $intendedUse = 'learner_visible',
    ): CaseFacts {
        return new CaseFacts(
            $caseId,
            'test fixture: ' . $caseId,
            $encounterSetting,
            $diagnosisRole,
            null,
            $baseCode,
            $fev1,
            $responseDomain,
            $intendedUse,
        );
    }

    /** @param array<string, bool> $responseDomain */
    public static function statusCase(
        string $caseId,
        string $encounterSetting,
        ?bool $inpatientLkfScored,
        array $responseDomain,
        string $diagnosisRole = 'main',
        string $intendedUse = 'learner_visible',
    ): CaseFacts {
        return new CaseFacts(
            $caseId,
            'test fixture: ' . $caseId,
            $encounterSetting,
            $diagnosisRole,
            $inpatientLkfScored,
            null,
            null,
            $responseDomain,
            $intendedUse,
        );
    }

    public static function record(string $code, ?string $marker = null): CatalogueRecord
    {
        return new CatalogueRecord($code, $marker, $code . ' designation', $code . ' short');
    }
}
