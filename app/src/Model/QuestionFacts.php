<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * Keyed collection of one question's `QuestionFact` rows, replacing the named
 * scalar properties `CaseFacts` used to expose directly (`encounterSetting`,
 * `copdBaseCode`, ...). A missing key returns null from every typed getter
 * rather than throwing: most of the 25 forward learner questions have no
 * COPD/status-relevant facts at all, and RULE-STATUS-01/DEPTH-01/EVID-01/
 * SPEC-01/MAP-01 must degrade to "does not match" rather than error when
 * their input facts are simply absent for a question they don't apply to.
 */
final class QuestionFacts
{
    /** @param array<string, QuestionFact> $byKey */
    public function __construct(private readonly array $byKey)
    {
    }

    public function get(string $factKey): ?QuestionFact
    {
        return $this->byKey[$factKey] ?? null;
    }

    public function getEnum(string $factKey): ?string
    {
        $fact = $this->get($factKey);

        return $fact !== null && $fact->valueType === QuestionFact::TYPE_ENUM ? (string) $fact->value : null;
    }

    public function getCode(string $factKey): ?string
    {
        $fact = $this->get($factKey);

        return $fact !== null && $fact->valueType === QuestionFact::TYPE_CODE ? (string) $fact->value : null;
    }

    public function getDecimal(string $factKey): ?float
    {
        $fact = $this->get($factKey);

        return $fact !== null && $fact->valueType === QuestionFact::TYPE_DECIMAL ? (float) $fact->value : null;
    }

    public function getBool(string $factKey): ?bool
    {
        $fact = $this->get($factKey);

        return $fact !== null && $fact->valueType === QuestionFact::TYPE_BOOLEAN ? (bool) $fact->value : null;
    }

    public function getText(string $factKey): ?string
    {
        $fact = $this->get($factKey);

        return $fact !== null && $fact->valueType === QuestionFact::TYPE_TEXT ? (string) $fact->value : null;
    }
}
