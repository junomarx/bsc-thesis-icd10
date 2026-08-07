<?php

declare(strict_types=1);

namespace Icd10Prototype\Evaluation;

/**
 * Terminal shape fixed by RULEBASE-0.1 Section 2 / MODELBASE-0.1 Section 10.1.
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
        public readonly ?array $explanationElements,
        public readonly ?array $matchedRules,
        public readonly ?string $improvementCode,
    ) {
    }

    public static function notEvaluated(string $reason): self
    {
        return new self('not_evaluated', null, $reason, null, null, null, null, null, null);
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
            $explanationElements,
            $matchedRules,
            $improvementCode,
        );
    }
}
