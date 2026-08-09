<?php

declare(strict_types=1);

namespace Icd10Prototype\Repository;

use Icd10Prototype\Model\CatalogueRecord;

/**
 * Reads only the 99 SUBSET-0.2 records (catalogue_code). No verification
 * oracle table exists in this schema and none is queried here (TEST-ARC-01).
 */
final class CatalogueRepository
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly string $subsetBaselineId,
    ) {
    }

    public function findByCode(string $code): ?CatalogueRecord
    {
        $statement = $this->pdo->prepare(
            'SELECT code, marker, designation, short_designation FROM catalogue_code WHERE subset_baseline_id = :subset AND code = :code',
        );
        $statement->execute(['subset' => $this->subsetBaselineId, 'code' => $code]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @return list<CatalogueRecord> */
    public function findByCodes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $statement = $this->pdo->prepare(
            "SELECT code, marker, designation, short_designation FROM catalogue_code WHERE subset_baseline_id = ? AND code IN ($placeholders) ORDER BY code",
        );
        $statement->execute([$this->subsetBaselineId, ...$codes]);

        return array_map($this->hydrate(...), $statement->fetchAll());
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): CatalogueRecord
    {
        return new CatalogueRecord(
            $row['code'],
            $row['marker'],
            $row['designation'],
            $row['short_designation'],
        );
    }
}
