<?php

declare(strict_types=1);

namespace Icd10Prototype\Http;

use Icd10Prototype\Evaluation\EvaluationResult;
use Icd10Prototype\Evaluation\Evaluator;
use Icd10Prototype\Evaluation\SpecificationGapException;
use Icd10Prototype\Model\ResponseInput;
use Icd10Prototype\Repository\CatalogueRepository;
use Icd10Prototype\Repository\QuestionRepository;

/**
 * POST /api/questions/{question_id}/evaluate (MODELBASE-0.2 Section 7.2 /
 * REQ-INT-01). Body: `{"response": {"type": "code", "code": "..."}}` or
 * `{"response": {"type": "none_of_above"}}` - the tagged shape, not
 * `{"option_id": "..."}, because the evaluator accepts any defined
 * evaluation-domain relation, not merely a displayed option (REQ-MOD-06); a
 * displayed-option-only shape could not address `M54.5`/`I10`/the hidden
 * J44 family members (deviation #1 flagged by the migration's own
 * spec-extraction pass - `chapter3_patient_and_question_design_plan.md`'s
 * `option_id` shape was not adopted for this reason).
 *
 * Malformed/unrecognised request shape is rejected here, before any
 * question/code lookup is attempted (REQ-RUL-05) - unchanged boundary
 * placement from RULEBASE-0.1. `intended_use` is never filtered here: the
 * verification harness must be able to evaluate the eight hidden historical
 * fixtures by ID (REQ-VER-09), exactly as the old endpoint never filtered
 * `CASE-004`/`CASE-008`.
 */
final class EvaluationController
{
    public function __construct(
        private readonly QuestionRepository $questions,
        private readonly CatalogueRepository $catalogue,
        private readonly Evaluator $evaluator,
    ) {
    }

    /** @param mixed $decodedBody the JSON-decoded request body, or null if absent/malformed */
    public function evaluate(string $questionId, mixed $decodedBody): ApiResult
    {
        $question = $this->questions->findById($questionId);
        if ($question === null) {
            return new ApiResult(404, ['error' => 'question_not_found']);
        }

        $parsed = $this->parseResponse($decodedBody);
        if (is_string($parsed)) {
            return new ApiResult(400, [
                'evaluation_status' => 'not_evaluated',
                'classification' => null,
                'reason' => $parsed,
            ]);
        }

        $record = $parsed->isCode() ? $this->catalogue->findByCode((string) $parsed->code) : null;

        try {
            $result = $this->evaluator->evaluate($question, $parsed, $record);
        } catch (SpecificationGapException $exception) {
            return new ApiResult(500, [
                'error' => 'specification_gap',
                'message' => $exception->getMessage(),
            ]);
        }

        return new ApiResult(200, $this->render($result));
    }

    /** @return ResponseInput|string a parsed response, or a not_evaluated reason string */
    private function parseResponse(mixed $decodedBody): ResponseInput|string
    {
        if (!is_array($decodedBody) || !is_array($decodedBody['response'] ?? null)) {
            return 'malformed_input';
        }

        $response = $decodedBody['response'];
        $type = $response['type'] ?? null;

        if ($type === ResponseInput::KIND_NONE_OF_ABOVE) {
            return ResponseInput::noneOfAbove();
        }

        if ($type === ResponseInput::KIND_CODE) {
            $code = $response['code'] ?? null;
            if (!is_string($code) || trim($code) === '') {
                return 'malformed_input';
            }

            return ResponseInput::code(trim($code));
        }

        return 'unsupported_response_kind';
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
            'explanation_de' => $result->explanationDe,
            'explanation_elements' => $result->explanationElements,
            'determining_rule' => $result->determiningRule,
            'matched_rules' => $result->matchedRules,
            'improvement_code' => $result->improvementCode,
        ];
    }
}
