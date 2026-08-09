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
 * and the application stack before running this suite. Rewritten for the
 * forward patient/question model (MODELBASE-0.2) - the case-centric
 * `.case-list`/tutorial-modal markup this drove no longer exists.
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

    protected function openRoster(): void
    {
        static::$driver->get(static::$baseUrl);
        // The <ul class="patient-list"> container renders immediately, even
        // before GET /api/patients resolves - wait for an actual card, not
        // just its (possibly still-empty) container.
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('patient-card'),
        ));
    }

    /** @return list<string> patient_id values for every rendered patient card */
    protected function rosterPatientIds(): array
    {
        $cards = static::$driver->findElements(WebDriverBy::className('patient-card'));

        return array_map(static fn ($card) => (string) $card->getAttribute('data-patient-id'), $cards);
    }

    /**
     * Sum of every patient card's "N questions" badge. The roster is the
     * only navigation surface a learner's browser ever has (no
     * client-side router, `docs/DEVELOPMENT_DOCUMENTATION.md` §7); if this
     * sum is exactly 25 (the learner-visible total), no hidden
     * `verification_only` question can be reachable through it, without
     * needing to know any hidden question's exact title.
     */
    protected function rosterTotalQuestionCount(): int
    {
        $badges = static::$driver->findElements(WebDriverBy::cssSelector('.patient-card-badges .badge'));
        $total = 0;
        foreach ($badges as $badge) {
            // Anchored on "questions" specifically - a loose "digits followed
            // by a word" pattern would also match the age badge ("68 yrs").
            if (preg_match('/^(\d+)\s+questions?$/i', trim($badge->getText()), $matches)) {
                $total += (int) $matches[1];
            }
        }

        return $total;
    }

    protected function openPatient(string $patientId): void
    {
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::cssSelector("[data-patient-id='$patientId']"),
        ))->click();

        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('question-view'),
        ));
    }

    /**
     * Question order is shuffled per playthrough (REQ-INT-03) - opening a
     * patient never guarantees which of its questions appears first. Clicks
     * through, submitting an arbitrary valid answer to any question that
     * does not contain an option matching `$codeFragment`, until one does
     * (or `$maxQuestions` is exceeded). Leaves that question on-screen,
     * unanswered, ready for the caller's own submission.
     */
    protected function navigateToQuestionWithOption(string $codeFragment, int $maxQuestions = 6): void
    {
        for ($attempt = 0; $attempt < $maxQuestions; $attempt++) {
            $matchingOption = static::$driver->findElements(
                WebDriverBy::xpath("//label[contains(., '$codeFragment')]"),
            );
            if ($matchingOption !== []) {
                return;
            }

            $radios = static::$driver->findElements(WebDriverBy::cssSelector('.code-list input[type="radio"]'));
            self::assertNotEmpty($radios, 'expected at least one option on every question');
            $radios[0]->click();
            static::$driver->findElement(WebDriverBy::cssSelector('.submit-bar button'))->click();
            $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::className('question-feedback'),
            ));

            $nextButtons = static::$driver->findElements(WebDriverBy::cssSelector('.question-feedback button'));
            $nextButtons[0]->click();
            $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::className('question-view'),
            ));
        }

        self::fail("no question with an option matching '$codeFragment' appeared within $maxQuestions questions");
    }

    /**
     * Selects the displayed option whose visible text contains `$code`
     * (option markup renders `<strong>{code}</strong> — {designation}`,
     * never the code as the radio's DOM value) and submits it.
     */
    protected function selectAndSubmitCode(string $code): void
    {
        $radio = $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::xpath("//label[contains(., '$code')]//input[@type='radio']"),
        ));
        $radio->click();

        $submit = static::$driver->findElement(WebDriverBy::cssSelector('.submit-bar button'));
        $this->wait()->until(static fn () => $submit->isEnabled());
        $submit->click();

        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('question-feedback'),
        ));
    }

    protected function waitForResultHeading(): string
    {
        return trim($this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::className('result-heading'),
        ))->getText());
    }

    protected function resultExplanationText(): string
    {
        return static::$driver->findElement(WebDriverBy::cssSelector('.question-feedback > p'))->getText();
    }

    protected function improvementText(): ?string
    {
        $elements = static::$driver->findElements(WebDriverBy::className('improvement'));

        return $elements === [] ? null : $elements[0]->getText();
    }

    /**
     * Direct HTTP call against the host-reachable app URL, independent of
     * the browser. Used to confirm the evaluation endpoint remains
     * reachable for questions with no learner-facing navigation path to
     * reach them through (TEST-E2E-02); classification correctness itself
     * is already covered by TEST-RC-01 and is not re-asserted here.
     */
    protected function postEvaluate(string $questionId, array $response): array
    {
        return self::httpRequest('POST', '/api/questions/' . $questionId . '/evaluate', ['response' => $response]);
    }

    /** Direct HTTP call confirming a question 404s on the learner-facing detail read. */
    protected function getQuestion(string $questionId): array
    {
        return self::httpRequest('GET', '/api/questions/' . $questionId);
    }

    private static function httpRequest(string $method, string $path, ?array $jsonBody = null): array
    {
        $options = [
            'method' => $method,
            'ignore_errors' => true,
        ];
        if ($jsonBody !== null) {
            $options['header'] = "Content-Type: application/json\r\n";
            $options['content'] = json_encode($jsonBody);
        }

        $context = stream_context_create(['http' => $options]);
        $body = file_get_contents(self::$apiBaseUrl . $path, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $matches)) {
                $status = (int) $matches[1];
            }
        }

        return ['status' => $status, 'body' => json_decode((string) $body, true)];
    }

    protected function wait(): WebDriverWait
    {
        return new WebDriverWait(static::$driver, 10);
    }
}
