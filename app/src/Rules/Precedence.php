<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

/**
 * RULE-PREC-01: deterministic conflict/terminal policy, isolated as a pure
 * function over already-computed rule-match booleans so TEST-PREC-01 can
 * exercise combinations that need not occur in SUBSET-0.1 (e.g. all three
 * hard rules matching at once) without asserting such a case exists.
 */
final class Precedence
{
    public const CLASS_INCORRECT = 'incorrect';
    public const CLASS_SUBOPTIMAL = 'suboptimal';
    public const CLASS_CORRECT = 'correct';

    /** Stable priority for the single reported determining hard rule. */
    private const HARD_PRIORITY = ['RULE-STATUS-01', 'RULE-DEPTH-01', 'RULE-EVID-01'];

    /**
     * @param list<string> $hardMatches
     * Independent of storage/iteration order: scans the fixed priority list,
     * not the input array's order.
     */
    public static function primaryHardRule(array $hardMatches): ?string
    {
        foreach (self::HARD_PRIORITY as $candidate) {
            if (in_array($candidate, $hardMatches, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param list<string> $hardMatches
     * Returns null for the specification/conformance gap (RULEBASE-0.1
     * Section 6, last line) - never `incorrect` by default.
     */
    public static function terminalClass(array $hardMatches, bool $specMatches, bool $acceptMatches): ?string
    {
        if ($hardMatches !== []) {
            return self::CLASS_INCORRECT;
        }
        if ($specMatches) {
            return self::CLASS_SUBOPTIMAL;
        }
        if ($acceptMatches) {
            return self::CLASS_CORRECT;
        }

        return null;
    }
}
