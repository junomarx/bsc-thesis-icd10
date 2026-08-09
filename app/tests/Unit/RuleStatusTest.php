<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\RuleStatus;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/** TEST-STATUS-01: "!" status predicate, isolated from acceptance logic. */
final class RuleStatusTest extends TestCase
{
    public function testStatusAInpatientMainMarkedMatches(): void
    {
        $question = Fixtures::statusQuestion('STATUS-A', 'inpatient', null, ['Z01.6' => false]);

        self::assertTrue(RuleStatus::matches($question, Fixtures::record('Z01.6', '!')));
    }

    public function testStatusBHospitalOutpatientLkfScoredMatches(): void
    {
        $question = Fixtures::statusQuestion('STATUS-B', 'hospital_outpatient', true, ['Z01.6' => false]);

        self::assertTrue(RuleStatus::matches($question, Fixtures::record('Z01.6', '!')));
    }

    public function testStatusCHospitalOutpatientNotLkfScoredDoesNotMatch(): void
    {
        $question = Fixtures::statusQuestion('STATUS-C', 'hospital_outpatient', false, ['Z01.6' => true]);

        self::assertFalse(RuleStatus::matches($question, Fixtures::record('Z01.6', '!')));
    }

    public function testStatusDAdditionalDiagnosisInpatientDoesNotMatch(): void
    {
        $question = Fixtures::statusQuestion('STATUS-D', 'inpatient', null, ['Z01.6' => false], diagnosisRole: 'additional');

        self::assertFalse(RuleStatus::matches($question, Fixtures::record('Z01.6', '!')));
    }

    public function testStatusENoMarkerInpatientDoesNotMatch(): void
    {
        $question = Fixtures::statusQuestion('STATUS-E', 'inpatient', null, ['Z00.0' => false]);

        self::assertFalse(RuleStatus::matches($question, Fixtures::record('Z00.0', null)));
    }
}
