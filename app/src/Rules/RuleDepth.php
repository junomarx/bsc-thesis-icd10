<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CodingQuestion;

/**
 * RULE-DEPTH-01: mandatory five-character COPD coding (PAT-DEPTH-01).
 *
 * Source: SRC-AT-DOC-2026, printed pp. 12, 26 (J44.0-J44.9 recorded with five
 * characters in Austrian hospitals). Deliberately restricted to the inpatient
 * setting for this rule baseline. Unchanged predicate; fact read from
 * `question_fact` rather than a named `CaseFacts` property.
 */
final class RuleDepth
{
    public const CRITERION = 'mandatory_coding_depth_not_met';
    public const REQUIRED_CODING_LEVEL = 'five-character';

    public static function matches(CodingQuestion $question, string $submittedCode): bool
    {
        if ($question->facts->getEnum('encounter_setting') !== 'inpatient') {
            return false;
        }

        return (bool) preg_match('/^J44\.[0-9]$/', $submittedCode);
    }
}
