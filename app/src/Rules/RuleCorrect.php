<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Model\QuestionCodeDomainRelation;

/**
 * RULE-CORRECT-01: declared accepted reference after all applicable rules
 * clear. Membership alone never overrides a hard or graded match; the
 * Evaluator only consults this rule once those have cleared. Replaces the
 * old boolean `is_acceptable` lookup with a `relation_kind` check - the
 * `is_acceptable` column no longer exists at all in MODELBASE-0.2.
 */
final class RuleCorrect
{
    public const CRITERION = 'accepted_response';

    public static function matches(CodingQuestion $question, string $submittedCode): bool
    {
        $relation = $question->relationFor($submittedCode);

        return $relation !== null && $relation->relationKind === QuestionCodeDomainRelation::KIND_ACCEPTED_REFERENCE;
    }
}
