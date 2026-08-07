<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CaseFacts;

/**
 * RULE-CORRECT-01: declared acceptable response after all applicable rules
 * clear. Membership alone (`is_acceptable`) never overrides a hard or graded
 * match; the Evaluator only consults this rule once those have cleared.
 */
final class RuleCorrect
{
    public const CRITERION = 'accepted_response';

    public static function matches(CaseFacts $case, string $submittedCode): bool
    {
        return $case->isAcceptable($submittedCode);
    }
}
