<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Model\QuestionCodeDomainRelation;
use Icd10Prototype\Model\QuestionOption;

/**
 * RULE-NOA-01 (net new, RULEBASE-0.2 §4.3): deterministic `none_of_above`
 * set semantics. Let D(q) be the displayed code options and A(q) the
 * evaluation-domain codes carrying `accepted_reference`. `none_of_above` is
 * `correct` iff D(q) ∩ A(q) = ∅ - i.e. the question's accepted reference
 * (invariantly exactly one per question) is not among the displayed codes -
 * and `incorrect` otherwise. Terminal immediately after the gate for a
 * `none_of_above` response: no catalogue-code rule runs, because there is no
 * submitted code. A pure artefact-interaction rule; the set operation itself
 * makes no Austrian coding claim. Option *membership* must never be
 * randomized (only presentation order may be) or this rule's truth could
 * flip across playthroughs for the same question.
 */
final class RuleNoa
{
    public const CRITERION_NO_DISPLAYED_ACCEPTED = 'no_displayed_accepted_response';
    public const CRITERION_DISPLAYED_ACCEPTED_EXISTS = 'displayed_accepted_response_exists';

    public static function acceptedReferenceCode(CodingQuestion $question): ?string
    {
        foreach ($question->domain as $code => $relation) {
            if ($relation->relationKind === QuestionCodeDomainRelation::KIND_ACCEPTED_REFERENCE) {
                return $code;
            }
        }

        return null;
    }

    public static function isDisplayedCode(CodingQuestion $question, string $code): bool
    {
        foreach ($question->options as $option) {
            if ($option->optionKind === QuestionOption::KIND_CODE && $option->code === $code) {
                return true;
            }
        }

        return false;
    }

    /** True iff the displayed set contains no accepted reference. */
    public static function isCorrect(CodingQuestion $question): bool
    {
        $accepted = self::acceptedReferenceCode($question);

        return $accepted === null || !self::isDisplayedCode($question, $accepted);
    }
}
