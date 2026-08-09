<?php

declare(strict_types=1);

namespace Icd10Prototype\Evaluation;

/**
 * Terminal shape fixed by RULEBASE-0.2 Section 2 / MODELBASE-0.2 Section 7.
 * Unchanged shape from RULEBASE-0.1 - it already generalizes across every
 * determining rule, including the net-new RULE-REL-HARD-01/REL-SPEC-01/
 * NOA-01.
 *
 * `explanationDe` is additive (project-owner request, 9 August 2026): the
 * frontend's EN/DE UI switch was translating chrome and content but not the
 * evaluator's own prose, which stayed English regardless of locale. Every
 * `classified()` call site in Evaluator.php must supply both languages -
 * required, not optional, so a future rule can't silently ship English-only.
 */
final class EvaluationResult
{
    /**
     * @param array<string, mixed>|null $explanationElements
     * @param list<string>|null $matchedRules
     */
    private function __construct(
        public readonly string $evaluationStatus,
        public readonly ?string $classification,
        public readonly ?string $reason,
        public readonly ?string $determiningRule,
        public readonly ?string $criterion,
        public readonly ?string $explanation,
        public readonly ?string $explanationDe,
        public readonly ?array $explanationElements,
        public readonly ?array $matchedRules,
        public readonly ?string $improvementCode,
    ) {
    }

    public static function notEvaluated(string $reason): self
    {
        return new self('not_evaluated', null, $reason, null, null, null, null, null, null, null);
    }

    /**
     * @param array<string, mixed> $explanationElements
     * @param list<string> $matchedRules
     */
    public static function classified(
        string $classification,
        string $determiningRule,
        string $criterion,
        string $explanation,
        string $explanationDe,
        array $explanationElements,
        array $matchedRules,
        ?string $improvementCode,
    ): self {
        return new self(
            'classified',
            $classification,
            null,
            $determiningRule,
            $criterion,
            $explanation,
            $explanationDe,
            $explanationElements,
            $matchedRules,
            $improvementCode,
        );
    }
}
