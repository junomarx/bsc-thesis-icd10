<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * One `question_code_domain` row: a code's evaluation-domain relation to a
 * question, replacing `CaseFacts::$responseDomain`'s boolean `is_acceptable`
 * map. Evaluation-domain membership is distinct from displayed-option
 * membership (REQ-MOD-06) - a relation may exist here for a code that never
 * appears in `question_option` (e.g. `M54.5` for `Q-004-05`).
 */
final class QuestionCodeDomainRelation
{
    public const KIND_ACCEPTED_REFERENCE = 'accepted_reference';
    public const KIND_LESS_SPECIFIC_SUPPORTED = 'less_specific_supported';
    public const KIND_FACT_CONFLICT = 'fact_conflict';
    public const KIND_TEMPORAL_CONTEXT_CONFLICT = 'temporal_context_conflict';
    public const KIND_SOURCE_RULE_RESOLVED = 'source_rule_resolved';

    public function __construct(
        public readonly string $code,
        public readonly string $relationKind,
        public readonly ?string $reasonKey,
        public readonly ?string $improvementCode,
        public readonly string $sourceAuditRef,
    ) {
    }
}
