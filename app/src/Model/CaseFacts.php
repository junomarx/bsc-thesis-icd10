<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * One CASEBASE-0.1 synthetic case: explicit rule-relevant facts only.
 *
 * RULE-* predicates may consume only the facts represented here (REQ-MOD-01).
 * $responseDomain maps every code in the case's closed response domain to its
 * `is_acceptable` membership (MODELBASE-0.1 case_code_domain).
 */
final class CaseFacts
{
    /** @param array<string, bool> $responseDomain code => is_acceptable */
    public function __construct(
        public readonly string $caseId,
        public readonly string $shortDescription,
        public readonly string $encounterSetting,
        public readonly string $diagnosisRole,
        public readonly ?bool $inpatientLkfScored,
        public readonly ?string $copdBaseCode,
        public readonly ?float $fev1StablePctPredicted,
        public readonly array $responseDomain,
        public readonly string $intendedUse,
    ) {
    }

    public function hasDefinedRelation(string $code): bool
    {
        return array_key_exists($code, $this->responseDomain);
    }

    public function isAcceptable(string $code): bool
    {
        return $this->responseDomain[$code] ?? false;
    }

    public function isLearnerVisible(): bool
    {
        return $this->intendedUse === 'learner_visible';
    }
}
