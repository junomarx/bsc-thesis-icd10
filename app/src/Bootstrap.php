<?php

declare(strict_types=1);

namespace Icd10Prototype;

use Icd10Prototype\Evaluation\Evaluator;
use Icd10Prototype\Http\CaseController;
use Icd10Prototype\Http\EvaluationController;
use Icd10Prototype\Repository\BaselineRepository;
use Icd10Prototype\Repository\CaseRepository;
use Icd10Prototype\Repository\CatalogueRepository;

/**
 * Wires repositories/evaluator against one PDO connection and the active
 * PROTOBASE-0.1 baseline identity. Kept separate from the front controller
 * so tests can build the same graph against a test database.
 */
final class Bootstrap
{
    public readonly CaseController $caseController;
    public readonly EvaluationController $evaluationController;

    public function __construct(\PDO $pdo)
    {
        $baseline = (new BaselineRepository($pdo))->current();
        $catalogue = new CatalogueRepository($pdo, $baseline->subsetBaselineId);
        $cases = new CaseRepository($pdo, $baseline->caseBaselineId, $baseline->subsetBaselineId);

        $this->caseController = new CaseController($cases, $catalogue);
        $this->evaluationController = new EvaluationController($cases, $catalogue, new Evaluator());
    }

    public static function fromEnvironment(): self
    {
        return new self(Db::connect(Config::fromEnvironment()));
    }
}
