<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\E2E;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * TEST-E2E-01: learner workflow across the three feedback classes, driven
 * through a real browser against `PATIENT-001`'s `Q-001-01`
 * (`chapter3_test_catalogue.md` §7 / `RC-001-*`). For every vector, the
 * learner opens the roster, opens the patient, selects and submits one
 * option, and receives the resulting class plus explanation without any
 * manual alteration of the evaluation result.
 */
final class LearnerWorkflowTest extends SeleniumTestCase
{
    #[DataProvider('threeClassSubmissions')]
    public function testQ00101SubmissionRendersExpectedFeedback(
        string $submittedCode,
        string $expectedHeading,
        string $expectedExplanationFragment,
        ?string $expectedImprovementCode,
    ): void {
        $this->openRoster();
        $this->openPatient('PATIENT-001');
        // Question order is shuffled per playthrough (REQ-INT-03) - find
        // the COPD question (Q-001-01) regardless of where it landed.
        $this->navigateToQuestionWithOption('J44');
        $this->selectAndSubmitCode($submittedCode);

        self::assertSame($expectedHeading, $this->waitForResultHeading());
        self::assertStringContainsString($expectedExplanationFragment, $this->resultExplanationText());

        if ($expectedImprovementCode !== null) {
            self::assertStringContainsString($expectedImprovementCode, (string) $this->improvementText());
        } else {
            self::assertNull($this->improvementText());
        }
    }

    public static function threeClassSubmissions(): array
    {
        return [
            'correct: J44.02' => ['J44.02', 'Correct', 'supported by the documented information', null],
            'suboptimal: J44.09' => ['J44.09', 'Suboptimal', 'FEV1 severity unspecified', 'J44.02'],
            // RULE-EVID-01 also supplies an improvement code as corrective
            // direction even though the result is `incorrect`, not only for
            // `suboptimal` - see chapter3_rule_catalogue_0_2.md RULE-EVID-01.
            'incorrect: J44.01' => ['J44.01', 'Incorrect', 'conflicts with the represented stable-phase FEV1', 'J44.02'],
        ];
    }
}
