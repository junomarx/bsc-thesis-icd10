<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\E2E;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use PHPUnit\Framework\TestCase;

/**
 * Base class for TEST-E2E-01/02: drives the actual React -> PHP -> MySQL
 * path through a real browser via Selenium, against whatever is running at
 * ICD_E2E_BASE_URL. See tests/E2E/README.md for how to bring up Selenium
 * and the application stack before running this suite.
 */
abstract class SeleniumTestCase extends TestCase
{
    protected static RemoteWebDriver $driver;
    protected static string $baseUrl;
    private static string $apiBaseUrl;

    public static function setUpBeforeClass(): void
    {
        $seleniumUrl = getenv('ICD_E2E_SELENIUM_URL') ?: 'http://127.0.0.1:4444';
        // What the browser (running inside the Selenium container) navigates
        // to; may differ from the host-reachable URL used for direct HTTP
        // checks below.
        static::$baseUrl = getenv('ICD_E2E_BROWSER_BASE_URL') ?: 'http://host.docker.internal:5860';
        self::$apiBaseUrl = getenv('ICD_E2E_BASE_URL') ?: 'http://127.0.0.1:5860';

        static::$driver = RemoteWebDriver::create($seleniumUrl, DesiredCapabilities::chrome(), 10000, 10000);
    }

    public static function tearDownAfterClass(): void
    {
        static::$driver->quit();
    }

    protected function openCaseList(): void
    {
        static::$driver->get(static::$baseUrl);
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('case-list'),
        ));
        $this->dismissTutorialIfPresent();
    }

    /**
     * The first-visit tutorial modal (docs/UX_UI_SPECIFICATION.md §3.2)
     * auto-shows once per browser profile (localStorage flag). A fresh
     * WebDriver session has no prior visit, so it would otherwise cover
     * the case list and intercept the next click - dismiss it exactly as
     * a real first-time learner would, rather than special-casing test
     * mode out of the real first-visit behaviour.
     */
    private function dismissTutorialIfPresent(): void
    {
        $closeButtons = static::$driver->findElements(WebDriverBy::className('tutorial-close'));
        if ($closeButtons === []) {
            return;
        }

        $closeButtons[0]->click();
        $this->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(
            WebDriverBy::className('tutorial-overlay'),
        ));
    }

    protected function caseListButtonLabels(): array
    {
        $buttons = static::$driver->findElements(WebDriverBy::cssSelector('.case-list button'));

        return array_map(static fn ($button) => $button->getText(), $buttons);
    }

    protected function openCase(string $caseId): void
    {
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::xpath("//button[contains(., '$caseId')]"),
        ))->click();

        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('code-list'),
        ));
    }

    protected function searchAndSubmitCode(string $code): void
    {
        $search = static::$driver->findElement(WebDriverBy::id('code-search'));
        $search->clear();
        $search->sendKeys($code);

        $radio = $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::cssSelector("input[type='radio'][value='$code']"),
        ));
        $radio->click();

        $submit = static::$driver->findElement(WebDriverBy::xpath("//button[text()='Submit code']"));
        $this->wait()->until(static fn () => $submit->isEnabled());
        $submit->click();
    }

    protected function waitForResultHeading(): string
    {
        return trim($this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('result-heading'),
        ))->getText());
    }

    protected function resultExplanationText(): string
    {
        return static::$driver->findElement(WebDriverBy::cssSelector('section p'))->getText();
    }

    protected function improvementText(): ?string
    {
        $elements = static::$driver->findElements(WebDriverBy::className('improvement'));

        return $elements === [] ? null : $elements[0]->getText();
    }

    /**
     * Direct HTTP call against the host-reachable app URL, independent of
     * the browser. Used only to confirm the evaluation endpoint remains
     * reachable for cases with no learner-facing navigation path to reach
     * them through (TEST-E2E-02); classification correctness itself is
     * already covered by TEST-RC-01 and is not re-asserted here.
     */
    protected function postEvaluate(string $caseId, string $submittedCode): array
    {
        $url = self::$apiBaseUrl . '/api/cases/' . $caseId . '/evaluate';
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode(['submitted_code' => $submittedCode]),
                'ignore_errors' => true,
            ],
        ]);

        $body = file_get_contents($url, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $matches)) {
                $status = (int) $matches[1];
            }
        }

        return ['status' => $status, 'body' => json_decode($body, true)];
    }

    protected function wait(): WebDriverWait
    {
        return new WebDriverWait(static::$driver, 10);
    }
}
