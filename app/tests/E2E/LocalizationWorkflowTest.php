<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Browser-level localization audit: every learner patient, context record,
 * question, option list, feedback screen and completion screen is rendered in
 * both supported locales through the real React/PHP/MySQL path.
 */
final class LocalizationWorkflowTest extends SeleniumTestCase
{
    #[DataProvider('locales')]
    public function testCompleteLearnerWorkflowInBothLocales(
        string $locale,
        string $languageButton,
        string $htmlLanguage,
        array $expectedTitles,
    ): void {
        $this->openRoster();
        static::$driver->findElement(WebDriverBy::xpath("//button[normalize-space()='$languageButton']"))->click();
        $this->wait()->until(fn (): bool => static::$driver->findElement(WebDriverBy::tagName('html'))->getAttribute('lang') === $htmlLanguage);

        $this->auditTutorial($locale);

        self::assertCount(6, $this->rosterPatientIds());
        $questionCounts = [
            'PATIENT-001' => 3,
            'PATIENT-002' => 3,
            'PATIENT-003' => 3,
            'PATIENT-004' => 5,
            'PATIENT-005' => 5,
            'PATIENT-006' => 6,
        ];
        $contextCounts = [5, 5, 5, 5, 5, 7];
        $seenTitles = [];
        $seenContextItems = 0;

        foreach ($questionCounts as $patientId => $questionCount) {
            $this->openPatient($patientId);

            for ($index = 0; $index < $questionCount; $index++) {
                $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::cssSelector('.code-list input[type="radio"]'),
                ));

                $title = trim(static::$driver->findElement(WebDriverBy::cssSelector('.question-view h2'))->getText());
                $prompt = trim(static::$driver->findElement(WebDriverBy::className('question-prompt'))->getText());
                self::assertContains($title, $expectedTitles, "$locale unexpected question title");
                self::assertNotSame('', $prompt, "$locale empty prompt for $title");
                $seenTitles[$title] = true;

                $labels = static::$driver->findElements(WebDriverBy::className('code-option'));
                self::assertNotEmpty($labels, "$locale empty option list for $title");
                foreach ($labels as $label) {
                    self::assertNotSame('', trim($label->getText()), "$locale empty answer option for $title");
                    self::assertStringNotContainsString('none_of_above', $label->getText());
                }

                if ($index === 0) {
                    static::$driver->findElement(WebDriverBy::className('patient-dossier-toggle'))->click();
                    $items = static::$driver->findElements(WebDriverBy::cssSelector('.patient-context-list li'));
                    self::assertCount($contextCounts[array_search($patientId, array_keys($questionCounts), true)], $items);
                    $seenContextItems += count($items);
                    foreach ($items as $item) {
                        self::assertNotSame('', trim($item->getText()));
                    }
                    static::$driver->findElement(WebDriverBy::className('patient-dossier-toggle'))->click();
                }

                $radios = static::$driver->findElements(WebDriverBy::cssSelector('.code-list input[type="radio"]'));
                static::$driver->executeScript('arguments[0].scrollIntoView({block: "center"});', [$radios[0]]);
                $radios[0]->click();
                static::$driver->findElement(WebDriverBy::cssSelector('.submit-bar button'))->click();
                $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::className('question-feedback'),
                ));

                $explanation = $this->resultExplanationText();
                self::assertNotSame('', trim($explanation));
                self::assertStringNotContainsString('learner_label', $explanation);
                self::assertDoesNotMatchRegularExpression('/\b[a-z]+_[a-z_]+\b/', $explanation);
                if ($locale === 'de') {
                    self::assertDoesNotMatchRegularExpression('/\b(?:Documented|Current consciousness|Recorded GFR|declared acceptable)\b/i', $explanation);
                } else {
                    self::assertDoesNotMatchRegularExpression('/\b(?:dokumentiert|widerspricht|Angaben|Kodierung|Keine der genannten)\b/ui', $explanation);
                }

                // Internal identifiers are absent from ordinary result prose
                // and appear only after the explicitly labelled disclosure.
                self::assertStringNotContainsString('RULE-', $explanation);
                static::$driver->findElement(WebDriverBy::cssSelector('.technical-details summary'))->click();
                self::assertStringContainsString('RULE-', static::$driver->findElement(WebDriverBy::className('technical-details'))->getText());

                static::$driver->findElement(WebDriverBy::cssSelector('.question-feedback button'))->click();
                if ($index + 1 < $questionCount) {
                    $this->wait()->until(fn (): bool => static::$driver->findElements(WebDriverBy::className('question-feedback')) === []);
                }
            }

            $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::className('review-heading'),
            ));
            self::assertCount($questionCount, static::$driver->findElements(WebDriverBy::className('review-item')));
            static::$driver->findElement(WebDriverBy::cssSelector('.result-actions .link-button'))->click();
            $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::className('patient-card'),
            ));
        }

        self::assertCount(25, $seenTitles);
        self::assertSame(32, $seenContextItems);
        self::assertEqualsCanonicalizing($expectedTitles, array_keys($seenTitles));
    }

    public function testAllLearnerReachableRuleBranchesAndBothNoaBranchesRenderBilingually(): void
    {
        $vectors = [
            ['PATIENT-001', 'J44.02', 'J44.02', 'RULE-CORRECT-01'],
            ['PATIENT-001', 'J44.01', 'J44.01', 'RULE-EVID-01'],
            ['PATIENT-001', 'J44.09', 'J44.09', 'RULE-SPEC-01'],
            ['PATIENT-003', 'E03.4', 'E03.4', 'RULE-REL-HARD-01'],
            ['PATIENT-001', 'M17.9', 'M17.9', 'RULE-REL-SPEC-01'],
            ['PATIENT-001', 'J44.02', null, 'RULE-NOA-01'],
            ['PATIENT-004', 'M54.2', null, 'RULE-NOA-01'],
        ];

        foreach (['en' => 'EN', 'de' => 'DE'] as $locale => $buttonText) {
            foreach ($vectors as [$patientId, $questionCode, $submittedCode, $expectedRule]) {
                $this->openRoster();
                static::$driver->findElement(WebDriverBy::xpath("//button[normalize-space()='$buttonText']"))->click();
                $this->openPatient($patientId);
                $this->navigateToQuestionWithExactOption($questionCode);

                if ($submittedCode === null) {
                    static::$driver->findElement(WebDriverBy::xpath('//label[.//em]//input[@type="radio"]'))->click();
                    static::$driver->findElement(WebDriverBy::cssSelector('.submit-bar button'))->click();
                    $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className('question-feedback')));
                } else {
                    $this->selectAndSubmitExactCode($submittedCode);
                }

                $explanation = $this->resultExplanationText();
                self::assertNotSame('', trim($explanation), "$locale $expectedRule explanation");
                static::$driver->findElement(WebDriverBy::cssSelector('.technical-details summary'))->click();
                $technical = static::$driver->findElement(WebDriverBy::className('technical-details'))->getText();
                self::assertStringContainsString($expectedRule, $technical, "$locale expected determining rule");
            }
        }

        // RULE-DEPTH-01 and RULE-STATUS-01 are intentionally evaluation-domain
        // or verification-only branches in the frozen dataset, so they have no
        // displayed learner option. Their bilingual output is exercised across
        // RCBASE-0.3 by Integration/LocalizationTest.
    }

    public static function locales(): array
    {
        return [
            'British English' => ['en', 'EN', 'en-GB', [
                'Respiratory coding task', 'Metabolic coding task', 'Musculoskeletal coding task',
                'Cardiac rhythm coding task', 'Renal coding task', 'Mental-health coding task',
                'Neurological coding task', 'Endocrine coding task', 'Anxiety coding task',
                'Coexisting-condition coding task', 'Ophthalmic coding task', 'Mood-disorder coding task',
                'Haematology coding task', 'Current-finding coding task', 'Psychiatric coding task',
                'Movement-disorder coding task', 'Dermatology coding task', 'Lipid-disorder coding task',
                'Blood-pressure coding task', 'Prior cardiac-record coding task', 'Sequela coding task',
                'Prior epilepsy-record coding task', 'Prior cognitive-record coding task',
                'Prior urological-record coding task', 'Current unconsciousness coding task',
            ]],
            'Austrian German' => ['de', 'DE', 'de-AT', [
                'Kodieraufgabe Atmungssystem', 'Kodieraufgabe Stoffwechsel', 'Kodieraufgabe Bewegungsapparat',
                'Kodieraufgabe Herzrhythmus', 'Kodieraufgabe Niere', 'Kodieraufgabe Psychische Gesundheit',
                'Kodieraufgabe Neurologie', 'Kodieraufgabe Endokrinologie', 'Kodieraufgabe Angststörung',
                'Kodieraufgabe Begleiterkrankung', 'Kodieraufgabe Augenheilkunde', 'Kodieraufgabe Affektive Störung',
                'Kodieraufgabe Hämatologie', 'Kodieraufgabe Aktueller Befund', 'Kodieraufgabe Psychiatrie',
                'Kodieraufgabe Bewegungsstörung', 'Kodieraufgabe Dermatologie', 'Kodieraufgabe Fettstoffwechselstörung',
                'Kodieraufgabe Blutdruck', 'Kodieraufgabe Kardiologischer Vorbefund', 'Kodieraufgabe Folgezustand',
                'Kodieraufgabe Epilepsie-Vorbefund', 'Kodieraufgabe Kognitiver Vorbefund',
                'Kodieraufgabe Urologischer Vorbefund', 'Kodieraufgabe Aktuelle Bewusstlosigkeit',
            ]],
        ];
    }

    private function auditTutorial(string $locale): void
    {
        static::$driver->findElement(WebDriverBy::className('tutorial-trigger'))->click();
        for ($step = 1; $step <= 4; $step++) {
            $count = trim(static::$driver->findElement(WebDriverBy::className('tutorial-step-count'))->getText());
            self::assertSame($locale === 'de' ? "Schritt $step von 4" : "Step $step of 4", $count);
            self::assertNotSame('', trim(static::$driver->findElement(WebDriverBy::cssSelector('.tutorial-step h3'))->getText()));
            if ($step < 4) {
                static::$driver->findElement(WebDriverBy::cssSelector('[data-testid="tutorial-next"]'))->click();
            }
        }
        static::$driver->findElement(WebDriverBy::cssSelector('[data-testid="tutorial-finish"]'))->click();
        $this->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::className('tutorial-dialog')));
    }

    private function navigateToQuestionWithExactOption(string $code, int $maxQuestions = 6): void
    {
        for ($attempt = 0; $attempt < $maxQuestions; $attempt++) {
            // Advancing removes the feedback before React has necessarily
            // committed the next question. Wait for its option list before
            // inspecting it; otherwise a slower CI browser can expose a
            // transient empty radio collection.
            $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('.code-list input[type="radio"]'),
            ));
            if (static::$driver->findElements(WebDriverBy::xpath("//label[.//strong[normalize-space()='$code']]")) !== []) {
                return;
            }
            $radios = static::$driver->findElements(WebDriverBy::cssSelector('.code-list input[type="radio"]'));
            $radios[0]->click();
            static::$driver->findElement(WebDriverBy::cssSelector('.submit-bar button'))->click();
            $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className('question-feedback')));
            static::$driver->findElement(WebDriverBy::cssSelector('.question-feedback button'))->click();
            $this->wait()->until(fn (): bool => static::$driver->findElements(WebDriverBy::className('question-feedback')) === []);
        }
        self::fail("No question with exact option $code found");
    }

    private function selectAndSubmitExactCode(string $code): void
    {
        static::$driver->findElement(WebDriverBy::xpath("//label[.//strong[normalize-space()='$code']]//input[@type='radio']"))->click();
        static::$driver->findElement(WebDriverBy::cssSelector('.submit-bar button'))->click();
        $this->wait()->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className('question-feedback')));
    }
}
