<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;

/**
 * Frontend-only coverage for REQ-UI-01's guided orientation: the tutorial
 * appears automatically once per browser, advances as a four-step flow,
 * stays dismissed after reload, remains manually reopenable from the
 * persistent header, and closes through either keyboard or outside-pointer
 * dismissal. This guards the localStorage and Selenium interaction contract
 * that the former always-expanded orientation panel did not have.
 */
final class TutorialTest extends SeleniumTestCase
{
    private const STORAGE_KEY = 'icd10-prototype:tutorial-seen-v1';

    public function testTutorialIsFirstVisitOnlyAndCanBeReopenedManually(): void
    {
        // Establish the app origin before clearing its localStorage, then
        // reload as a deterministic first visit for this test.
        static::$driver->get(static::$baseUrl);
        static::$driver->executeScript(
            'window.localStorage.removeItem(arguments[0]);',
            [self::STORAGE_KEY],
        );
        static::$driver->navigate()->refresh();

        $dialog = $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('tutorial-dialog'),
        ));
        self::assertSame('true', $dialog->getAttribute('aria-modal'));
        self::assertSame('Step 1 of 4', $this->tutorialStepCount());

        $this->tutorialButton('tutorial-next')->click();
        $this->waitForTutorialStep('Step 2 of 4');

        // Both directions are part of the walkthrough, not just four static
        // paragraphs hidden behind one dismissal control.
        $this->tutorialButton('tutorial-back')->click();
        $this->waitForTutorialStep('Step 1 of 4');

        for ($step = 2; $step <= 4; $step++) {
            $this->tutorialButton('tutorial-next')->click();
            $this->waitForTutorialStep("Step $step of 4");
        }

        $this->tutorialButton('tutorial-finish')->click();
        $this->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(
            WebDriverBy::className('tutorial-dialog'),
        ));
        self::assertSame(
            'true',
            static::$driver->executeScript('return window.localStorage.getItem(arguments[0]);', [self::STORAGE_KEY]),
        );

        static::$driver->navigate()->refresh();
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('patient-card'),
        ));
        self::assertSame([], static::$driver->findElements(WebDriverBy::className('tutorial-dialog')));

        $trigger = static::$driver->findElement(WebDriverBy::className('tutorial-trigger'));
        $trigger->click();
        $dialog = $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('tutorial-dialog'),
        ));
        self::assertSame('Step 1 of 4', $this->tutorialStepCount());

        // Escape closes a manually opened tour and focus returns to the
        // persistent trigger that opened it.
        $dialog->sendKeys(WebDriverKeys::ESCAPE);
        $this->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(
            WebDriverBy::className('tutorial-dialog'),
        ));
        self::assertTrue((bool) static::$driver->executeScript(
            'return document.activeElement === document.querySelector(".tutorial-trigger");',
        ));

        // Clicking the dimmed area outside the dialog is the pointer/touch
        // equivalent of Escape. A dialog click still bubbles to the backdrop,
        // so production code must distinguish target from currentTarget.
        $trigger->click();
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('tutorial-dialog'),
        ));
        static::$driver->executeScript('document.elementFromPoint(1, 1).click();');
        $this->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(
            WebDriverBy::className('tutorial-dialog'),
        ));
        self::assertTrue((bool) static::$driver->executeScript(
            'return document.activeElement === document.querySelector(".tutorial-trigger");',
        ));
    }

    private function tutorialButton(string $testId)
    {
        return static::$driver->findElement(WebDriverBy::cssSelector("[data-testid='$testId']"));
    }

    private function tutorialStepCount(): string
    {
        return trim(static::$driver->findElement(WebDriverBy::className('tutorial-step-count'))->getText());
    }

    private function waitForTutorialStep(string $expected): void
    {
        $this->wait()->until(fn (): bool => $this->tutorialStepCount() === $expected);
    }
}
