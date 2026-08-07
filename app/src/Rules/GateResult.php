<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

/**
 * Output of RULE-GATE-01. `reason` is populated only when not eligible, and
 * is one of the permitted validation reasons in RULEBASE-0.1 Section 5.
 */
final class GateResult
{
    public const REASON_OUTSIDE_ACTIVE_SUBSET = 'outside_active_subset';
    public const REASON_UNDEFINED_CASE_RELATION = 'undefined_case_relation';
    public const REASON_MISSING_REQUIRED_CASE_FACT = 'missing_required_case_fact';

    private function __construct(
        public readonly bool $eligible,
        public readonly ?string $reason,
    ) {
    }

    public static function eligible(): self
    {
        return new self(true, null);
    }

    public static function fail(string $reason): self
    {
        return new self(false, $reason);
    }
}
