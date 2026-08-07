<?php

declare(strict_types=1);

namespace Icd10Prototype\Repository;

use Icd10Prototype\Model\BaselineIdentity;

final class BaselineRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function current(): BaselineIdentity
    {
        $statement = $this->pdo->query('SELECT * FROM prototype_baseline LIMIT 2');
        $rows = $statement->fetchAll();

        if (count($rows) !== 1) {
            throw new \RuntimeException(sprintf('expected exactly one prototype_baseline row, found %d', count($rows)));
        }

        $row = $rows[0];

        return new BaselineIdentity(
            $row['prototype_baseline_id'],
            $row['model_baseline_id'],
            $row['requirements_catalogue_version'],
            $row['source_register_version'],
            $row['domain_baseline_id'],
            $row['rule_baseline_id'],
            $row['case_baseline_id'],
            $row['subset_baseline_id'],
            $row['catalogue_edition'],
            $row['diaglist_sha256'],
        );
    }
}
