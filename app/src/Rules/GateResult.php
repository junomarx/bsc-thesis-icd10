<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

/**
 * Output of RULE-GATE-01. `reason` is populated only when not eligible, and
 * is one of the four permitted reasons `APIBASE-0.1` §4.2 fixes as
 * RULE-GATE-01's complete vocabulary: `outside_active_subset`,
 * `undefined_question_relation` (kept as `undefined_case_relation` below -
 * not renamed, despite RULEBASE-0.2's own prose using the newer name - so
 * the 18 historical regression expectations, which may assert an exact
 * reason string, are not put at risk by a cosmetic rename, REQ-VER-09),
 * `missing_required_question_fact` (kept as `missing_required_case_fact`
 * for the same reason), and `none_option_not_defined`.
 * `malformed_input`/`unsupported_response_kind` are explicitly *not* gate
 * reasons - they are HTTP/controller errors produced before this class is
 * ever constructed (see `EvaluationController::parseResponse()`).
 */
final class GateResult
{
    public const REASON_OUTSIDE_ACTIVE_SUBSET = 'outside_active_subset';
    public const REASON_UNDEFINED_CASE_RELATION = 'undefined_case_relation';
    public const REASON_MISSING_REQUIRED_CASE_FACT = 'missing_required_case_fact';
    public const REASON_NONE_OPTION_NOT_DEFINED = 'none_option_not_defined';

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
