<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Frontend-only supplementary coverage for the `sessionStorage`-backed
 * per-patient completion badge (`REQ-UI-02`, `App.jsx`'s
 * `completedPatientIds`). No upstream TEST-* identifier - this is
 * presentation-layer state with no backend equivalent, so it is not part
 * of `chapter3_test_catalogue.md`'s upstream-controlled catalogue.
 * Replaces the case-centric per-case `localStorage` attempt/classification
 * badge this once drove (`lib/progress.js`), which no longer exists:
 * completion is now patient-level (every question answered), not a
 * per-question last-classification record.
 */
final class ProgressBadgeTest extends SeleniumTestCase
{
    public function testPatientCardShowsCompletedBadgeOnlyAfterEveryQuestionIsAnswered(): void
    {
        $this->openRoster();
        self::assertFalse($this->patientCardHasCompletedBadge('PATIENT-001'), 'must not be marked completed before any attempt');

        $this->openPatient('PATIENT-001');

        for ($i = 0; $i < 3; $i++) {
            $radios = static::$driver->findElements(WebDriverBy::cssSelector('.code-list input[type="radio"]'));
            $radios[0]->click();
            static::$driver->findElement(WebDriverBy::cssSelector('.submit-bar button'))->click();
            $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::className('question-feedback'),
            ));
            static::$driver->findElement(WebDriverBy::cssSelector('.question-feedback button'))->click();

            if ($i < 2) {
                $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::className('question-view'),
                ));
            } else {
                $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::className('review-list'),
                ));
            }
        }

        static::$driver->findElement(WebDriverBy::cssSelector('.result-actions .link-button'))->click();
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('patient-list'),
        ));

        self::assertTrue($this->patientCardHasCompletedBadge('PATIENT-001'), 'must be marked completed once every question is answered');
    }

    private function patientCardHasCompletedBadge(string $patientId): bool
    {
        $badges = static::$driver->findElements(
            WebDriverBy::cssSelector("[data-patient-id='$patientId'] .badge-completed"),
        );

        return $badges !== [];
    }
}
