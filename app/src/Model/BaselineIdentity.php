<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * The single `prototype_baseline` row: the active source/model/rule/patient/
 * question/subset combination the running application is evaluating against
 * (REQ-CFG-01 / TEST-CFG-01). MODELBASE-0.2: `case_baseline_id` is replaced by
 * `patient_baseline_id` + `question_baseline_id`.
 */
final class BaselineIdentity
{
    public function __construct(
        public readonly string $prototypeBaselineId,
        public readonly string $modelBaselineId,
        public readonly string $requirementsCatalogueVersion,
        public readonly string $sourceRegisterVersion,
        public readonly string $domainBaselineId,
        public readonly string $ruleBaselineId,
        public readonly string $subsetBaselineId,
        public readonly string $patientBaselineId,
        public readonly string $questionBaselineId,
        public readonly string $catalogueEdition,
        public readonly string $diaglistSha256,
    ) {
    }
}
