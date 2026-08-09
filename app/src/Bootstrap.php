<?php

declare(strict_types=1);

namespace Icd10Prototype;

use Icd10Prototype\Evaluation\Evaluator;
use Icd10Prototype\Http\EvaluationController;
use Icd10Prototype\Http\PatientController;
use Icd10Prototype\Http\QuestionController;
use Icd10Prototype\Repository\BaselineRepository;
use Icd10Prototype\Repository\CatalogueRepository;
use Icd10Prototype\Repository\PatientRepository;
use Icd10Prototype\Repository\QuestionRepository;

/**
 * Wires repositories/evaluator against one PDO connection and the active
 * PROTOBASE-1.1 (MODELBASE-0.2) baseline identity. Kept separate from the
 * front controller so tests can build the same graph against a test
 * database.
 */
final class Bootstrap
{
    public readonly PatientController $patientController;
    public readonly QuestionController $questionController;
    public readonly EvaluationController $evaluationController;

    public function __construct(\PDO $pdo)
    {
        $baseline = (new BaselineRepository($pdo))->current();
        $catalogue = new CatalogueRepository($pdo, $baseline->subsetBaselineId);
        $patients = new PatientRepository($pdo, $baseline->patientBaselineId);
        $questions = new QuestionRepository($pdo, $baseline->questionBaselineId, $baseline->subsetBaselineId);

        $this->patientController = new PatientController($patients, $questions);
        $this->questionController = new QuestionController($questions, $catalogue);
        $this->evaluationController = new EvaluationController($questions, $catalogue, new Evaluator());
    }

    public static function fromEnvironment(): self
    {
        return new self(Db::connect(Config::fromEnvironment()));
    }
}
