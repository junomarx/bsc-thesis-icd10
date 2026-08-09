<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Frontend-only coverage for the explicit light/dark appearance setting.
 * The test fixes its initial value rather than depending on Selenium's host
 * preference, then checks the live palette, tutorial, and reload boundary.
 */
final class ThemeTest extends SeleniumTestCase
{
    private const STORAGE_KEY = 'icd10-prototype:theme';

    public function testDarkThemeCanBeSelectedAndPersistsAcrossReload(): void
    {
        static::$driver->get(static::$baseUrl);
        static::$driver->executeScript(
            'window.localStorage.setItem(arguments[0], "light");'
                . 'window.localStorage.setItem("icd10-prototype:tutorial-seen-v1", "true");',
            [self::STORAGE_KEY],
        );
        static::$driver->navigate()->refresh();
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('patient-card'),
        ));

        self::assertSame('light', $this->appliedTheme());
        $lightBackground = $this->bodyBackground();

        $toggle = static::$driver->findElement(WebDriverBy::cssSelector('[data-testid="theme-toggle"]'));
        self::assertSame('false', $toggle->getAttribute('aria-pressed'));
        self::assertSame('Switch to dark mode', $toggle->getAttribute('title'));
        $toggle->click();

        $this->waitForTheme('dark');
        self::assertSame('true', $toggle->getAttribute('aria-pressed'));
        self::assertSame('Switch to light mode', $toggle->getAttribute('title'));
        self::assertSame(
            'dark',
            static::$driver->executeScript(
                'return window.localStorage.getItem(arguments[0]);',
                [self::STORAGE_KEY],
            ),
        );
        self::assertSame(
            'dark',
            static::$driver->executeScript('return getComputedStyle(document.documentElement).colorScheme;'),
        );

        $darkBackground = $this->bodyBackground();
        self::assertNotSame($lightBackground, $darkBackground);

        // The tutorial is layered above the roster and must consume the same
        // dark surface token, rather than retaining a hard-coded light card.
        static::$driver->findElement(WebDriverBy::className('tutorial-trigger'))->click();
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('tutorial-dialog'),
        ));
        $surfaceColours = static::$driver->executeScript(<<<'JS'
            const dialog = document.querySelector('.tutorial-dialog');
            const probe = document.createElement('span');
            probe.style.backgroundColor = getComputedStyle(document.documentElement)
                .getPropertyValue('--color-surface');
            document.body.appendChild(probe);
            const colours = [
                getComputedStyle(dialog).backgroundColor,
                getComputedStyle(probe).backgroundColor,
            ];
            probe.remove();
            return colours;
            JS);
        self::assertSame($surfaceColours[1], $surfaceColours[0]);
        $this->dismissTutorialIfPresent();

        static::$driver->navigate()->refresh();
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('patient-card'),
        ));
        self::assertSame('dark', $this->appliedTheme());
        self::assertSame($darkBackground, $this->bodyBackground());

        $toggle = static::$driver->findElement(WebDriverBy::cssSelector('[data-testid="theme-toggle"]'));
        $toggle->click();
        $this->waitForTheme('light');
        self::assertSame(
            'light',
            static::$driver->executeScript(
                'return window.localStorage.getItem(arguments[0]);',
                [self::STORAGE_KEY],
            ),
        );
    }

    private function appliedTheme(): string
    {
        return (string) static::$driver
            ->findElement(WebDriverBy::tagName('html'))
            ->getAttribute('data-theme');
    }

    private function bodyBackground(): string
    {
        return (string) static::$driver->executeScript(
            'return getComputedStyle(document.body).backgroundColor;',
        );
    }

    private function waitForTheme(string $expected): void
    {
        $this->wait()->until(fn (): bool => $this->appliedTheme() === $expected);
    }
}
