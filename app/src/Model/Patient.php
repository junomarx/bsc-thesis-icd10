<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * One `patient_definition` row plus its ordered `patient_context_item` rows.
 * A patient is a learner/session container (REQ-MOD-03) - it is not itself
 * the atomic evaluation unit; that remains `CodingQuestion`.
 */
final class Patient
{
    /** @param list<PatientContextItem> $contextItems */
    public function __construct(
        public readonly string $patientId,
        public readonly string $displayName,
        public readonly int $ageYears,
        public readonly string $sex,
        public readonly string $selfDescribedBackground,
        public readonly string $historyAvailability,
        public readonly string $difficultyRole,
        public readonly string $generalHealthSummary,
        public readonly bool $synthetic,
        public readonly array $contextItems,
    ) {
    }
}
