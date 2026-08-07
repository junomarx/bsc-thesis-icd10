<?php

declare(strict_types=1);

namespace Icd10Prototype\Repository;

use Icd10Prototype\Model\CaseFacts;

/**
 * Reads case_definition and its case_code_domain relations. `is_acceptable`
 * is legitimate runtime data (RULE-CORRECT-01 input) and is loaded here; the
 * HTTP layer decides what, if anything, to expose to the learner from it.
 */
final class CaseRepository
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly string $caseBaselineId,
        private readonly string $subsetBaselineId,
    ) {
    }

    public function findById(string $caseId): ?CaseFacts
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM case_definition WHERE case_baseline_id = :baseline AND case_id = :case_id AND subset_baseline_id = :subset',
        );
        $statement->execute([
            'baseline' => $this->caseBaselineId,
            'case_id' => $caseId,
            'subset' => $this->subsetBaselineId,
        ]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @return list<CaseFacts> */
    public function listLearnerVisible(): array
    {
        $statement = $this->pdo->prepare(
            "SELECT * FROM case_definition WHERE case_baseline_id = :baseline AND subset_baseline_id = :subset AND intended_use = 'learner_visible' ORDER BY case_id",
        );
        $statement->execute(['baseline' => $this->caseBaselineId, 'subset' => $this->subsetBaselineId]);

        return array_map($this->hydrate(...), $statement->fetchAll());
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): CaseFacts
    {
        $domainStatement = $this->pdo->prepare(
            'SELECT code, is_acceptable FROM case_code_domain WHERE case_baseline_id = :baseline AND case_id = :case_id AND subset_baseline_id = :subset',
        );
        $domainStatement->execute([
            'baseline' => $this->caseBaselineId,
            'case_id' => $row['case_id'],
            'subset' => $this->subsetBaselineId,
        ]);

        $responseDomain = [];
        foreach ($domainStatement->fetchAll() as $domainRow) {
            $responseDomain[$domainRow['code']] = (bool) $domainRow['is_acceptable'];
        }

        return new CaseFacts(
            $row['case_id'],
            $row['short_description'],
            $row['encounter_setting'],
            $row['diagnosis_role'],
            $row['inpatient_lkf_scored'] === null ? null : (bool) $row['inpatient_lkf_scored'],
            $row['copd_base_code'],
            $row['fev1_stable_pct_predicted'] === null ? null : (float) $row['fev1_stable_pct_predicted'],
            $responseDomain,
            $row['intended_use'],
        );
    }
}
