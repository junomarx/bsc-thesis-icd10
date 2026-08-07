<?php

declare(strict_types=1);

namespace Icd10Prototype\Http;

use Icd10Prototype\Evaluation\EvaluationResult;
use Icd10Prototype\Evaluation\Evaluator;
use Icd10Prototype\Evaluation\SpecificationGapException;
use Icd10Prototype\Repository\CaseRepository;
use Icd10Prototype\Repository\CatalogueRepository;

/**
 * POST /api/cases/{case_id}/evaluate (MODELBASE-0.1 Section 7 / TEST-API-01).
 *
 * One submitted_code string per request. Missing, blank, or array/list
 * submissions are rejected before any case/code lookup is attempted -
 * REQ-RUL-05 requires that unsupported input never be silently classified.
 */
final class EvaluationController
{
    public function __construct(
        private readonly CaseRepository $cases,
        private readonly CatalogueRepository $catalogue,
        private readonly Evaluator $evaluator,
    ) {
    }

    /** @param mixed $decodedBody the JSON-decoded request body, or null if absent/malformed */
    public function evaluate(string $caseId, mixed $decodedBody): ApiResult
    {
        $case = $this->cases->findById($caseId);
        if ($case === null) {
            return new ApiResult(404, ['error' => 'case_not_found']);
        }

        $validation = $this->validateSubmittedCode($decodedBody);
        if ($validation !== null) {
            return new ApiResult(400, [
                'evaluation_status' => 'not_evaluated',
                'classification' => null,
                'reason' => $validation,
            ]);
        }

        $submittedCode = trim($decodedBody['submitted_code']);
        $record = $this->catalogue->findByCode($submittedCode);

        try {
            $result = $this->evaluator->evaluate($case, $record, $submittedCode);
        } catch (SpecificationGapException $exception) {
            return new ApiResult(500, [
                'error' => 'specification_gap',
                'message' => $exception->getMessage(),
            ]);
        }

        return new ApiResult(200, $this->render($result));
    }

    private function validateSubmittedCode(mixed $decodedBody): ?string
    {
        if (!is_array($decodedBody) || !array_key_exists('submitted_code', $decodedBody)) {
            return 'malformed_input';
        }

        $value = $decodedBody['submitted_code'];
        if (!is_string($value) || trim($value) === '') {
            return 'malformed_input';
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function render(EvaluationResult $result): array
    {
        if ($result->evaluationStatus === 'not_evaluated') {
            return [
                'evaluation_status' => 'not_evaluated',
                'classification' => null,
                'reason' => $result->reason,
            ];
        }

        return [
            'evaluation_status' => 'classified',
            'classification' => $result->classification,
            'criterion' => $result->criterion,
            'explanation' => $result->explanation,
            'explanation_elements' => $result->explanationElements,
            'determining_rule' => $result->determiningRule,
            'matched_rules' => $result->matchedRules,
            'improvement_code' => $result->improvementCode,
        ];
    }
}
