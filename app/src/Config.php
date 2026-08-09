<?php

declare(strict_types=1);

namespace Icd10Prototype;

/**
 * Reads the ICD_DB_* environment contract used by prototype_stack/compose.yaml
 * and the PROTOBASE-1.1 Python tooling (prototype_baseline/persistence_candidate/).
 */
final class Config
{
    public function __construct(
        public readonly string $dbHost,
        public readonly int $dbPort,
        public readonly string $dbName,
        public readonly string $dbUser,
        public readonly string $dbPassword,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $name = getenv('ICD_DB_NAME');
        $user = getenv('ICD_DB_USER');
        if ($name === false || $name === '' || $user === false || $user === '') {
            throw new \RuntimeException('ICD_DB_NAME and ICD_DB_USER are required');
        }

        return new self(
            getenv('ICD_DB_HOST') !== false ? getenv('ICD_DB_HOST') : '127.0.0.1',
            (int) (getenv('ICD_DB_PORT') !== false ? getenv('ICD_DB_PORT') : '3306'),
            $name,
            $user,
            getenv('ICD_DB_PASSWORD') !== false ? getenv('ICD_DB_PASSWORD') : '',
        );
    }
}
