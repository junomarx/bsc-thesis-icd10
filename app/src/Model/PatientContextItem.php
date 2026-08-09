<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * One `patient_context_item` row: learner-visible presentation data only.
 * Never an evaluator input (REQ-MOD-04) - only typed `question_fact` rows may
 * be consumed by rules, no matter how similar a context item's `display_text`
 * looks to a fact.
 */
final class PatientContextItem
{
    public function __construct(
        public readonly string $contextItemId,
        public readonly string $itemType,
        public readonly string $informationSource,
        public readonly string $displayText,
        public readonly int $canonicalPosition,
    ) {
    }
}
