<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Integration;

use Icd10Prototype\Config;
use Icd10Prototype\Db;
use Icd10Prototype\Evaluation\LocalizedFactFormatter;
use Icd10Prototype\Model\QuestionFact;

/**
 * Localization-specific runtime audit over every RCBASE-0.3 response. The
 * oracle is read only by this test harness, never loaded into the application
 * database or production image (ArchitectureIsolationTest covers that path).
 */
final class LocalizationTest extends DatabaseTestCase
{
    private const ORACLE_SHA256 = '21c3f02697fe9b20028ec1121d28fce3389c027705372ae08c43f894b3342540';

    public function testAllReferenceExplanationsAreCompleteLocalizedAndSemanticallyUnchanged(): void
    {
        $oraclePath = dirname(__DIR__, 3) . '/prototype_baseline/verification/reference_responses_0_3.csv';
        self::assertSame(self::ORACLE_SHA256, hash_file('sha256', $oraclePath), 'RCBASE-0.3 must remain byte-identical');

        $classes = [];
        $rules = [];
        $criteria = [];
        $seenRules = [];
        $sawCorrectNoa = false;
        $sawIncorrectNoa = false;
        $sawPostinfectiousAetiology = false;
        $sawUnconsciousState = false;

        foreach (ReferenceResponseTest::referenceResponses() as $vector) {
            [$rcId, $questionId, $response, $expectedStatus, $expectedClass, $expectedRule, $expectedCriterion] = $vector;
            $body = static::$app->evaluationController->evaluate($questionId, ['response' => $response])->body;

            self::assertSame($expectedStatus, $body['evaluation_status'], "$rcId status");
            self::assertSame($expectedClass, $body['classification'], "$rcId class");
            self::assertSame($expectedRule, $body['determining_rule'], "$rcId rule");
            self::assertSame($expectedCriterion, $body['criterion'], "$rcId criterion");
            self::assertIsString($body['explanation'], "$rcId English explanation");
            self::assertIsString($body['explanation_de'], "$rcId German explanation");
            self::assertNotSame('', trim($body['explanation']), "$rcId English explanation");
            self::assertNotSame('', trim($body['explanation_de']), "$rcId German explanation");

            self::assertDoesNotMatchRegularExpression(
                '/\b(?:dokumentiert|Angaben|widerspricht|unterstützt|Kodierung|Keine der genannten|Schweregrad)\b/ui',
                $body['explanation'],
                "$rcId German prose leaked into English",
            );
            self::assertDoesNotMatchRegularExpression(
                '/\b(?:Documented|Current consciousness|Recorded GFR|Time since|declared acceptable|is accepted)\b/ui',
                $body['explanation_de'],
                "$rcId English learner prose leaked into German",
            );
            self::assertDoesNotMatchRegularExpression('/\b[a-z]+_[a-z_]+\b/', $body['explanation'], "$rcId raw fact/reason key in English");
            self::assertDoesNotMatchRegularExpression('/\b[a-z]+_[a-z_]+\b/', $body['explanation_de'], "$rcId raw fact/reason key in German");
            self::assertDoesNotMatchRegularExpression('/\bRULE-[A-Z-]+-\d+\b/', $body['explanation'], "$rcId rule ID outside technical details");
            self::assertDoesNotMatchRegularExpression('/\bRULE-[A-Z-]+-\d+\b/', $body['explanation_de'], "$rcId rule ID outside technical details");

            $classes[$expectedClass] = ($classes[$expectedClass] ?? 0) + 1;
            $rules[$expectedRule] = ($rules[$expectedRule] ?? 0) + 1;
            $criteria[$expectedCriterion] = ($criteria[$expectedCriterion] ?? 0) + 1;
            $seenRules[$expectedRule] = true;

            if ($expectedRule === 'RULE-NOA-01') {
                $sawCorrectNoa = $sawCorrectNoa || $expectedClass === 'correct';
                $sawIncorrectNoa = $sawIncorrectNoa || $expectedClass === 'incorrect';
            }
            $sawPostinfectiousAetiology = $sawPostinfectiousAetiology
                || str_contains($body['explanation_de'], 'postinfektiöse Ätiologie');
            $sawUnconsciousState = $sawUnconsciousState
                || str_contains($body['explanation_de'], 'weiterhin bewusstlos');
        }

        ksort($classes);
        ksort($rules);
        self::assertSame(['correct' => 33, 'incorrect' => 90, 'suboptimal' => 20], $classes);
        self::assertSame([
            'RULE-CORRECT-01' => 31,
            'RULE-DEPTH-01' => 3,
            'RULE-EVID-01' => 9,
            'RULE-NOA-01' => 25,
            'RULE-REL-HARD-01' => 53,
            'RULE-REL-SPEC-01' => 17,
            'RULE-SPEC-01' => 3,
            'RULE-STATUS-01' => 2,
        ], $rules);
        self::assertCount(8, $seenRules);
        self::assertNotEmpty($criteria);
        self::assertTrue($sawCorrectNoa, 'correct none-of-the-above branch covered');
        self::assertTrue($sawIncorrectNoa, 'incorrect none-of-the-above branch covered');
        self::assertTrue($sawPostinfectiousAetiology, 'value-aware aetiology feedback covered');
        self::assertTrue($sawUnconsciousState, 'value-aware consciousness feedback covered');
    }

    public function testPromptedHybridSentenceIsCorrectedInBothLanguages(): void
    {
        $result = static::$app->evaluationController->evaluate('Q-003-02', [
            'response' => ['type' => 'code', 'code' => 'E03.4'],
        ])->body;

        self::assertSame(
            'E03.4 conflicts with the documented fact that the aetiology is postinfectious.',
            $result['explanation'],
        );
        self::assertSame(
            'E03.4 widerspricht der dokumentierten Angabe, dass eine postinfektiöse Ätiologie vorliegt.',
            $result['explanation_de'],
        );
        self::assertStringNotContainsString('Documented aetiology', $result['explanation_de']);
    }

    public function testEveryFactLinkedToLocalizedFeedbackHasAValueAwareFormatter(): void
    {
        $pdo = Db::connect(Config::fromEnvironment());
        $rows = $pdo->query(<<<'SQL'
            SELECT DISTINCT d.relation_kind, f.fact_key, f.value_type,
                f.value_text, f.value_integer, f.value_decimal, f.value_boolean,
                f.value_code, f.value_enum, f.unit, f.learner_label
            FROM question_relation_fact rf
            JOIN question_code_domain d
              ON d.question_baseline_id = rf.question_baseline_id
             AND d.question_id = rf.question_id
             AND d.subset_baseline_id = rf.subset_baseline_id
             AND d.code = rf.code
            JOIN question_fact f
              ON f.question_baseline_id = rf.question_baseline_id
             AND f.question_id = rf.question_id
             AND f.fact_key = rf.fact_key
            WHERE d.relation_kind IN (
                'fact_conflict', 'temporal_context_conflict', 'less_specific_supported'
            )
            ORDER BY d.relation_kind, f.fact_key
            SQL)->fetchAll(\PDO::FETCH_ASSOC);

        self::assertCount(58, $rows, 'all distinct feedback-linked fact/relation combinations');
        foreach ($rows as $row) {
            $value = match ($row['value_type']) {
                QuestionFact::TYPE_TEXT => (string) $row['value_text'],
                QuestionFact::TYPE_INTEGER => (int) $row['value_integer'],
                QuestionFact::TYPE_DECIMAL => (float) $row['value_decimal'],
                QuestionFact::TYPE_BOOLEAN => (bool) $row['value_boolean'],
                QuestionFact::TYPE_CODE => (string) $row['value_code'],
                QuestionFact::TYPE_ENUM => (string) $row['value_enum'],
            };
            $clauses = LocalizedFactFormatter::clauses(new QuestionFact(
                (string) $row['fact_key'],
                (string) $row['value_type'],
                $value,
                $row['unit'] !== null ? (string) $row['unit'] : null,
                (string) $row['learner_label'],
                null,
            ));

            self::assertNotSame('', trim($clauses['en']), $row['fact_key'] . ' English');
            self::assertNotSame('', trim($clauses['de']), $row['fact_key'] . ' German');
            self::assertDoesNotMatchRegularExpression('/\b[a-z]+_[a-z_]+\b/', $clauses['en']);
            self::assertDoesNotMatchRegularExpression('/\b[a-z]+_[a-z_]+\b/', $clauses['de']);
        }
    }
}
