<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Evaluation\Evaluator;
use Icd10Prototype\Evaluation\LocalizedFactFormatter;
use Icd10Prototype\Evaluation\SpecificationGapException;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LocalizationTest extends TestCase
{
    #[DataProvider('factClauses')]
    public function testValueAwareFactClausesAreBilingual(string $factKey, string $value, string $expectedEn, string $expectedDe): void
    {
        $clauses = LocalizedFactFormatter::clauses(Fixtures::enumFact($factKey, $value));

        self::assertSame($expectedEn, $clauses['en']);
        self::assertSame($expectedDe, $clauses['de']);
    }

    public static function factClauses(): array
    {
        return [
            'aetiology' => [
                'etiology', 'postinfectious',
                'the aetiology is postinfectious',
                'eine postinfektiöse Ätiologie vorliegt',
            ],
            'consciousness' => [
                'consciousness_state', 'unconscious',
                'the patient remains unconscious',
                'der Patient weiterhin bewusstlos ist',
            ],
        ];
    }

    public function testUnsupportedFactCannotFallBackToAnInternalKeyOrEnglishLabel(): void
    {
        $this->expectException(SpecificationGapException::class);
        LocalizedFactFormatter::clauses(Fixtures::enumFact('unknown_internal_fact', 'unknown_value'));
    }

    public function testCorrectExplanationUsesSupportedResponseWording(): void
    {
        $question = Fixtures::question('LOCAL-CORRECT', [], [
            'F41.1' => Fixtures::acceptedReference('F41.1'),
        ]);
        $result = (new Evaluator())->evaluate($question, Fixtures::code('F41.1'), Fixtures::record('F41.1'));

        self::assertSame(
            'F41.1 is supported by the documented information as an appropriate code for this question.',
            $result->explanation,
        );
        self::assertSame(
            'F41.1 wird durch die dokumentierten Angaben als passende Kodierung unterstützt.',
            $result->explanationDe,
        );
    }

    #[DataProvider('noneOfAboveWording')]
    public function testBothNoneOfAboveBranchesUseNaturalSupportedResponseWording(
        bool $displayAccepted,
        string $expectedClass,
        string $expectedEnglish,
        string $expectedGerman,
    ): void {
        $options = [Fixtures::noneOfAboveOption('NOA-O2', 2)];
        if ($displayAccepted) {
            array_unshift($options, Fixtures::codeOption('NOA-O1', 'F41.1', 1));
        }
        $question = Fixtures::question('LOCAL-NOA', [], [
            'F41.1' => Fixtures::acceptedReference('F41.1'),
        ], [], $options);
        $result = (new Evaluator())->evaluate($question, Fixtures::noneOfAbove(), null);

        self::assertSame($expectedClass, $result->classification);
        self::assertSame($expectedEnglish, $result->explanation);
        self::assertSame($expectedGerman, $result->explanationDe);
    }

    public static function noneOfAboveWording(): array
    {
        return [
            'correct' => [
                false,
                'correct',
                'None of the displayed codes is supported by the documented information as an appropriate response. Therefore, “None of the above” is correct.',
                'Keiner der angezeigten Codes wird durch die dokumentierten Angaben als passende Kodierung unterstützt. Daher ist „Keine der genannten“ richtig.',
            ],
            'incorrect' => [
                true,
                'incorrect',
                'The displayed codes include a response supported by the documented information. Therefore, “None of the above” is not correct here.',
                'Unter den angezeigten Codes befindet sich eine durch die dokumentierten Angaben unterstützte Antwort. Daher ist „Keine der genannten“ hier nicht richtig.',
            ],
        ];
    }
}
