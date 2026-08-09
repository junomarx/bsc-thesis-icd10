<?php

declare(strict_types=1);

namespace Icd10Prototype\Repository;

use Icd10Prototype\Model\Patient;
use Icd10Prototype\Model\PatientContextItem;

/**
 * Reads `patient_definition` and its ordered `patient_context_item` rows.
 * Every patient is learner-facing (REQ-MOD-03); the `verification_only`
 * boundary lives on `coding_question`, not on patients.
 */
final class PatientRepository
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly string $patientBaselineId,
    ) {
    }

    public function findById(string $patientId): ?Patient
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM patient_definition WHERE patient_baseline_id = :baseline AND patient_id = :patient_id',
        );
        $statement->execute(['baseline' => $this->patientBaselineId, 'patient_id' => $patientId]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @return list<Patient> */
    public function listAll(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM patient_definition WHERE patient_baseline_id = :baseline ORDER BY patient_id',
        );
        $statement->execute(['baseline' => $this->patientBaselineId]);

        return array_map($this->hydrate(...), $statement->fetchAll());
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Patient
    {
        $contextStatement = $this->pdo->prepare(
            'SELECT * FROM patient_context_item WHERE patient_baseline_id = :baseline AND patient_id = :patient_id ORDER BY canonical_position',
        );
        $contextStatement->execute(['baseline' => $this->patientBaselineId, 'patient_id' => $row['patient_id']]);

        $contextItems = array_map(
            static fn (array $contextRow): PatientContextItem => new PatientContextItem(
                $contextRow['context_item_id'],
                $contextRow['item_type'],
                $contextRow['information_source'],
                $contextRow['display_text'],
                (int) $contextRow['canonical_position'],
            ),
            $contextStatement->fetchAll(),
        );

        return new Patient(
            $row['patient_id'],
            $row['display_name'],
            (int) $row['age_years'],
            $row['sex'],
            $row['self_described_background'],
            $row['history_availability'],
            $row['difficulty_role'],
            $row['general_health_summary'],
            (bool) $row['synthetic'],
            $contextItems,
        );
    }
}
