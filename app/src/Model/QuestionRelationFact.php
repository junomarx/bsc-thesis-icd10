<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * One `question_relation_fact` row: links a `question_code_domain` relation
 * to the specific `question_fact` that justifies it, so a hard/graded
 * explanation can name *which* documented fact makes a response wrong or
 * less specific, rather than asserting "wrong" with no traceable basis.
 */
final class QuestionRelationFact
{
    public const ROLE_SUPPORTS_REFERENCE = 'supports_reference';
    public const ROLE_CONFLICTS_WITH_RESPONSE = 'conflicts_with_response';
    public const ROLE_SUPPORTS_SPECIFICITY = 'supports_specificity';
    public const ROLE_SUPPORTS_TEMPORAL_CONTEXT = 'supports_temporal_context';
    public const ROLE_SUPPORTS_SOURCE_RULE = 'supports_source_rule';

    public function __construct(
        public readonly string $code,
        public readonly string $factKey,
        public readonly string $relationRole,
    ) {
    }
}
