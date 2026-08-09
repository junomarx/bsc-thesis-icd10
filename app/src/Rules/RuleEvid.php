<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CodingQuestion;

/**
 * RULE-EVID-01: COPD severity detail contradicts represented FEV1 (PAT-EVID-01).
 *
 * Source: SRC-AT-DOC-2026, printed p. 34. Only submitted five-character
 * severity forms (suffix 0-3) sharing the question's COPD base are compared;
 * an unspecified suffix `9` is never a contradiction of this rule
 * (RULE-SPEC-01 covers that situation instead), and an unrelated COPD base is
 * not given a terminal class here. Unchanged predicate; facts now read from
 * `question_fact` rather than named `CaseFacts` properties.
 */
final class RuleEvid
{
    public const CRITERION = 'case_evidence_conflict';
    private const SEVERITY_SUFFIXES = ['0', '1', '2', '3'];

    public static function matches(CodingQuestion $question, string $submittedCode, MapResult $mapResult): bool
    {
        $copdBaseCode = $question->facts->getCode('copd_base_code');

        if (!$mapResult->applicable || $copdBaseCode === null) {
            return false;
        }
        if (strlen($submittedCode) !== 6) {
            return false;
        }

        $base = substr($submittedCode, 0, 5);
        $suffixChar = substr($submittedCode, 5, 1);

        if ($base !== $copdBaseCode || !in_array($suffixChar, self::SEVERITY_SUFFIXES, true)) {
            return false;
        }

        return (int) $suffixChar !== $mapResult->expectedSuffix;
    }
}
