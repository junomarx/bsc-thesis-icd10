<?php

declare(strict_types=1);

namespace Icd10Prototype\Evaluation;

/**
 * Thrown when an eligible relation reaches no terminal rule (RULEBASE-0.1
 * Section 6, last line). This indicates an incomplete rule/case
 * specification, not a learner-facing `incorrect` result, and must never be
 * silently swallowed into one.
 */
final class SpecificationGapException extends \RuntimeException
{
}
