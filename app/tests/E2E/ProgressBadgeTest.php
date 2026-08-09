<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Frontend-only supplementary coverage for the localStorage-backed progress
 * badges (docs/UX_UI_SPECIFICATION.md §3.7). No upstream TEST-* identifier -
 * this is presentation-layer state with no backend equivalent, so it is not
 * part of chapter3_test_catalogue.md's upstream-controlled catalogue.
 */
final class ProgressBadgeTest extends SeleniumTestCase
{
    public function testCaseCardReflectsLastClassificationAfterSubmission(): void
    {
        $this->openCaseList();
        self::assertSame('not_attempted', $this->progressStatusFor('CASE-001'));

        $this->openCase('CASE-001');
        $this->searchAndSubmitCode('J44.02');
        self::assertSame('Correct', $this->waitForResultHeading());

        static::$driver->findElement(WebDriverBy::xpath("//button[text()='Back to cases']"))->click();
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('case-list'),
        ));

        self::assertSame('correct', $this->progressStatusFor('CASE-001'));
    }

    private function progressStatusFor(string $caseId): string
    {
        $badge = static::$driver->findElement(
            WebDriverBy::cssSelector("[data-case-id='$caseId'] [data-progress-status]"),
        );

        return $badge->getAttribute('data-progress-status');
    }
}
