<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Model\QuestionCodeDomainRelation;

/**
 * RULE-REL-SPEC-01 (net new, RULEBASE-0.2 §4.2): the generic counterpart to
 * RULE-SPEC-01 - an explicit, source-audited `less_specific_supported`
 * relation, always carrying an `improvement_code` that itself resolves to
 * this question's `accepted_reference` (enforced at hydration time,
 * `QuestionRepository::assertImprovementCodesResolve()`). Shares
 * RULE-SPEC-01's `CRITERION` string by design (both are "the same feedback
 * class for the same underlying reason," one source-specific, one generic) -
 * callers must key off `determining_rule`, never reverse-engineer which
 * rule fired from the criterion string alone. Never triggered by code
 * morphology, a `.9` suffix, or designation wording (REQ-RUL-02/07).
 */
final class RuleRelSpec
{
    public const CRITERION = 'supported_specificity_not_used';

    public static function matches(CodingQuestion $question, string $submittedCode): bool
    {
        $relation = $question->relationFor($submittedCode);

        return $relation !== null && $relation->relationKind === QuestionCodeDomainRelation::KIND_LESS_SPECIFIC_SUPPORTED;
    }
}
