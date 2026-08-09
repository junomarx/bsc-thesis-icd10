<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * One atomic `coding_question`: the evaluation unit, replacing `CaseFacts`.
 * RULE-* predicates may consume only `$facts` (REQ-MOD-01) - never `$prompt`
 * or a patient's context prose. `$domain` (evaluation-domain membership) is
 * distinct from `$options` (displayed-option membership, REQ-MOD-06):
 * `relationFor()` may return a relation for a code absent from `$options`.
 */
final class CodingQuestion
{
    public const USE_LEARNER_VISIBLE = 'learner_visible';
    public const USE_VERIFICATION_ONLY = 'verification_only';

    /**
     * @param array<string, QuestionCodeDomainRelation> $domain code => relation
     * @param array<string, list<QuestionRelationFact>> $relationFacts code => linked facts
     * @param list<QuestionOption> $options
     */
    public function __construct(
        public readonly string $questionId,
        public readonly ?string $patientId,
        public readonly string $title,
        public readonly string $prompt,
        public readonly string $intendedUse,
        public readonly int $canonicalPosition,
        public readonly ?string $legacyCaseId,
        public readonly string $sourceAuditRef,
        public readonly QuestionFacts $facts,
        public readonly array $domain,
        public readonly array $relationFacts,
        public readonly array $options,
    ) {
    }

    public function hasDefinedRelation(string $code): bool
    {
        return array_key_exists($code, $this->domain);
    }

    public function relationFor(string $code): ?QuestionCodeDomainRelation
    {
        return $this->domain[$code] ?? null;
    }

    /** @return list<QuestionRelationFact> */
    public function relationFactsFor(string $code): array
    {
        return $this->relationFacts[$code] ?? [];
    }

    public function isLearnerVisible(): bool
    {
        return $this->intendedUse === self::USE_LEARNER_VISIBLE;
    }

    public function hasNoneOfAboveOption(): bool
    {
        foreach ($this->options as $option) {
            if ($option->optionKind === QuestionOption::KIND_NONE_OF_ABOVE) {
                return true;
            }
        }

        return false;
    }
}
