<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Model\QuestionCodeDomainRelation;

/**
 * RULE-REL-HARD-01 (net new, RULEBASE-0.2 §4.1): a code's evaluation-domain
 * relation is an explicit, source-audited hard incompatibility -
 * `fact_conflict` (the response contradicts a documented question fact) or
 * `temporal_context_conflict` (the response is wrong for the documented
 * timing/context). Deliberately not `submitted_code != accepted_code`: only
 * relations the authoring/audit process actually classified this way trigger
 * it (REQ-RUL-07) - `source_rule_resolved`/`less_specific_supported` never
 * do, no matter how "wrong" a code might look.
 */
final class RuleRelHard
{
    public const CRITERION_FACT_CONFLICT = 'documented_fact_conflict';
    public const CRITERION_TEMPORAL_CONTEXT_CONFLICT = 'temporal_context_conflict';

    private const HARD_KINDS = [
        QuestionCodeDomainRelation::KIND_FACT_CONFLICT,
        QuestionCodeDomainRelation::KIND_TEMPORAL_CONTEXT_CONFLICT,
    ];

    public static function matches(CodingQuestion $question, string $submittedCode): bool
    {
        $relation = $question->relationFor($submittedCode);

        return $relation !== null && in_array($relation->relationKind, self::HARD_KINDS, true);
    }

    public static function criterionFor(QuestionCodeDomainRelation $relation): string
    {
        return $relation->relationKind === QuestionCodeDomainRelation::KIND_FACT_CONFLICT
            ? self::CRITERION_FACT_CONFLICT
            : self::CRITERION_TEMPORAL_CONTEXT_CONFLICT;
    }
}
