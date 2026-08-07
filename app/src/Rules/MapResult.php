<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

/**
 * Deterministic output of RULE-MAP-01. Not itself a feedback class.
 */
final class MapResult
{
    private function __construct(
        public readonly bool $applicable,
        public readonly ?int $expectedSuffix,
        public readonly ?string $expectedSpecificCode,
    ) {
    }

    public static function notApplicable(): self
    {
        return new self(false, null, null);
    }

    public static function derived(int $suffix, string $expectedSpecificCode): self
    {
        return new self(true, $suffix, $expectedSpecificCode);
    }
}
