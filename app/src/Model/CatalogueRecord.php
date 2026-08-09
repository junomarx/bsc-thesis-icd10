<?php

declare(strict_types=1);

namespace Icd10Prototype\Model;

/**
 * One SUBSET-0.2 record: the four DIAGLIST fields retained by REQ-DAT-04.
 * Unchanged by the MODELBASE-0.2 migration.
 */
final class CatalogueRecord
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $marker,
        public readonly string $designation,
        public readonly string $shortDesignation,
    ) {
    }
}
