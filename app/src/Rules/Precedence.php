<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

/**
 * RULE-PREC-01: deterministic conflict/terminal policy, isolated as a pure
 * function over already-computed rule-match booleans so TEST-PREC-01 can
 * exercise combinations that need not occur in SUBSET-0.2 without asserting
 * such a case exists. Extended for RULEBASE-0.2: hard priority gains a
 * fourth slot (`RULE-REL-HARD-01`, after the three source-specific hard
 * rules); graded matches are no longer a single boolean, since a question
 * can now match either the source-specific `RULE-SPEC-01` or the generic
 * `RULE-REL-SPEC-01` (never both by data-validation design, but the
 * priority list resolves it deterministically either way).
 */
final class Precedence
{
    public const CLASS_INCORRECT = 'incorrect';
    public const CLASS_SUBOPTIMAL = 'suboptimal';
    public const CLASS_CORRECT = 'correct';

    /** Stable priority for the single reported determining hard rule. */
    private const HARD_PRIORITY = ['RULE-STATUS-01', 'RULE-DEPTH-01', 'RULE-EVID-01', 'RULE-REL-HARD-01'];

    /** Stable priority for the single reported determining graded rule. */
    private const GRADED_PRIORITY = ['RULE-SPEC-01', 'RULE-REL-SPEC-01'];

    /**
     * @param list<string> $hardMatches
     * Independent of storage/iteration order: scans the fixed priority list,
     * not the input array's order.
     */
    public static function primaryHardRule(array $hardMatches): ?string
    {
        return self::firstByPriority(self::HARD_PRIORITY, $hardMatches);
    }

    /** @param list<string> $gradedMatches */
    public static function primaryGradedRule(array $gradedMatches): ?string
    {
        return self::firstByPriority(self::GRADED_PRIORITY, $gradedMatches);
    }

    /**
     * @param list<string> $hardMatches
     * @param list<string> $gradedMatches
     * Returns null for the specification/conformance gap (RULEBASE-0.2 §6,
     * last line) - never `incorrect` by default.
     */
    public static function terminalClass(array $hardMatches, array $gradedMatches, bool $acceptMatches): ?string
    {
        if ($hardMatches !== []) {
            return self::CLASS_INCORRECT;
        }
        if ($gradedMatches !== []) {
            return self::CLASS_SUBOPTIMAL;
        }
        if ($acceptMatches) {
            return self::CLASS_CORRECT;
        }

        return null;
    }

    /**
     * @param list<string> $priority
     * @param list<string> $matches
     */
    private static function firstByPriority(array $priority, array $matches): ?string
    {
        foreach ($priority as $candidate) {
            if (in_array($candidate, $matches, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
