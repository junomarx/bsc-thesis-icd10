<?php

declare(strict_types=1);

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;

require dirname(__DIR__) . '/vendor/autoload.php';

$outputDirectory = $argv[1] ?? null;
if ($outputDirectory === null || !is_dir($outputDirectory)) {
    fwrite(STDERR, "Usage: php app/scripts/capture_localization_screenshots.php <existing-output-directory>\n");
    exit(2);
}

$seleniumUrl = getenv('ICD_E2E_SELENIUM_URL') ?: 'http://127.0.0.1:4444';
$baseUrl = getenv('ICD_E2E_BROWSER_BASE_URL') ?: 'http://host.docker.internal:5860';
$driver = RemoteWebDriver::create($seleniumUrl, DesiredCapabilities::chrome(), 10000, 10000);
$wait = new WebDriverWait($driver, 10);

/** @param non-empty-string $name */
function capture(RemoteWebDriver $driver, string $outputDirectory, string $name): void
{
    $path = rtrim($outputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
    $driver->takeScreenshot($path);
    fwrite(STDOUT, "$path\n");
}

try {
    $driver->manage()->window()->setSize(new WebDriverDimension(1440, 1000));
    $driver->get($baseUrl);
    $wait->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className('patient-card')));
    $driver->executeScript(
        <<<'JS'
window.localStorage.setItem('icd10-prototype:tutorial-seen-v1', 'true');
window.localStorage.setItem('icd10-prototype:locale', 'en');
window.localStorage.setItem('icd10-prototype:theme', 'light');
JS,
    );
    $driver->navigate()->refresh();
    $wait->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className('patient-card')));
    capture($driver, $outputDirectory, '01-roster-en-light.png');

    $driver->findElement(WebDriverBy::xpath("//button[normalize-space()='DE']"))->click();
    $wait->until(static fn (): bool => $driver->findElement(WebDriverBy::tagName('html'))->getAttribute('lang') === 'de-AT');
    $driver->findElement(WebDriverBy::className('theme-switch'))->click();
    $wait->until(static fn (): bool => $driver->findElement(WebDriverBy::tagName('html'))->getAttribute('data-theme') === 'dark');
    capture($driver, $outputDirectory, '02-roster-de-dark.png');

    $driver->findElement(WebDriverBy::className('tutorial-trigger'))->click();
    for ($step = 1; $step < 4; $step++) {
        $driver->findElement(WebDriverBy::cssSelector('[data-testid="tutorial-next"]'))->click();
    }
    $wait->until(static fn (): bool => trim($driver->findElement(WebDriverBy::className('tutorial-step-count'))->getText()) === 'Schritt 4 von 4');
    capture($driver, $outputDirectory, '03-tutorial-de-dark.png');
    $driver->findElement(WebDriverBy::cssSelector('[data-testid="tutorial-finish"]'))->click();

    $driver->findElement(WebDriverBy::cssSelector("[data-patient-id='PATIENT-003']"))->click();
    $wait->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className('question-view')));

    for ($attempt = 0; $attempt < 3; $attempt++) {
        $target = $driver->findElements(WebDriverBy::xpath("//label[.//strong[normalize-space()='E03.4']]"));
        if ($target !== []) {
            break;
        }
        $radios = $driver->findElements(WebDriverBy::cssSelector('.code-list input[type="radio"]'));
        if ($radios === []) {
            throw new RuntimeException('No answer option while searching for the E03.4 question.');
        }
        $radios[0]->click();
        $driver->findElement(WebDriverBy::cssSelector('.submit-bar button'))->click();
        $wait->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className('question-feedback')));
        $driver->findElement(WebDriverBy::cssSelector('.question-feedback button'))->click();
        $wait->until(static fn (): bool => $driver->findElements(WebDriverBy::className('question-feedback')) === []);
    }

    $radio = $driver->findElements(WebDriverBy::xpath("//label[.//strong[normalize-space()='E03.4']]//input[@type='radio']"));
    if ($radio === []) {
        throw new RuntimeException('Patient 003 did not expose the E03.4 option within three questions.');
    }
    $radio[0]->click();
    $driver->findElement(WebDriverBy::cssSelector('.submit-bar button'))->click();
    $wait->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::className('question-feedback')));
    $driver->executeScript('arguments[0].scrollIntoView({block: "center"});', [
        $driver->findElement(WebDriverBy::className('question-feedback')),
    ]);
    capture($driver, $outputDirectory, '04-feedback-de-e03-4-dark.png');

    $driver->findElement(WebDriverBy::xpath("//button[normalize-space()='EN']"))->click();
    $wait->until(static fn (): bool => $driver->findElement(WebDriverBy::tagName('html'))->getAttribute('lang') === 'en-GB');
    $wait->until(static fn (): bool => trim($driver->findElement(WebDriverBy::cssSelector('header h1'))->getText()) === 'ICD-10 coding practice');
    $driver->executeScript('window.scrollTo(0, 0);');
    capture($driver, $outputDirectory, '05-feedback-en-e03-4-dark.png');
} finally {
    $driver->quit();
}
