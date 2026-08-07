<?php

declare(strict_types=1);

namespace Icd10Prototype\Http;

use Icd10Prototype\Model\CaseFacts;
use Icd10Prototype\Repository\CaseRepository;
use Icd10Prototype\Repository\CatalogueRepository;

/**
 * Learner-visible case retrieval (REQ-INT-01 steps 1-2). CASE-004 is excluded
 * here because intended_use = verification_only (TEST-E2E-02); the
 * evaluation endpoint remains reachable for it regardless.
 */
final class CaseController
{
    public function __construct(
        private readonly CaseRepository $cases,
        private readonly CatalogueRepository $catalogue,
    ) {
    }

    public function list(): ApiResult
    {
        $cases = array_map($this->summarize(...), $this->cases->listLearnerVisible());

        return new ApiResult(200, ['cases' => $cases]);
    }

    public function show(string $caseId): ApiResult
    {
        $case = $this->cases->findById($caseId);
        if ($case === null || !$case->isLearnerVisible()) {
            return new ApiResult(404, ['error' => 'case_not_found']);
        }

        $codes = $this->catalogue->findByCodes(array_keys($case->responseDomain));
        $supportedCodes = array_map(
            static fn ($record) => [
                'code' => $record->code,
                'designation' => $record->designation,
                'short_designation' => $record->shortDesignation,
            ],
            $codes,
        );

        return new ApiResult(200, [
            ...$this->summarize($case),
            'supported_codes' => $supportedCodes,
        ]);
    }

    /** @return array<string, mixed> */
    private function summarize(CaseFacts $case): array
    {
        return [
            'case_id' => $case->caseId,
            'short_description' => $case->shortDescription,
            'encounter_setting' => $case->encounterSetting,
            'diagnosis_role' => $case->diagnosisRole,
            'inpatient_lkf_scored' => $case->inpatientLkfScored,
            'fev1_stable_pct_predicted' => $case->fev1StablePctPredicted,
        ];
    }
}
