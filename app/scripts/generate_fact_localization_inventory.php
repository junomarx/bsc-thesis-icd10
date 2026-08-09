<?php

declare(strict_types=1);

use Icd10Prototype\Evaluation\LocalizedFactFormatter;
use Icd10Prototype\Model\QuestionFact;

$repositoryRoot = dirname(__DIR__, 2);
require $repositoryRoot . '/app/src/Evaluation/SpecificationGapException.php';
require $repositoryRoot . '/app/src/Model/QuestionFact.php';
require $repositoryRoot . '/app/src/Evaluation/LocalizedFactFormatter.php';

/** @return list<array<string, string>> */
function csvRows(string $path): array
{
    $handle = fopen($path, 'r');
    $header = fgetcsv($handle, escape: '\\');
    $rows = [];
    while (($row = fgetcsv($handle, escape: '\\')) !== false) {
        $rows[] = array_combine($header, $row);
    }
    fclose($handle);

    return $rows;
}

$data = $repositoryRoot . '/prototype_baseline/data/';
$facts = [];
foreach (csvRows($data . 'question_facts_0_1.csv') as $row) {
    $facts[$row['question_id'] . '|' . $row['fact_key']] = $row;
}
$domain = [];
foreach (csvRows($data . 'question_code_domain_0_1.csv') as $row) {
    $domain[$row['question_id'] . '|' . $row['code']] = $row;
}

$entries = [];
foreach (csvRows($data . 'question_relation_facts_0_1.csv') as $relationFact) {
    $relation = $domain[$relationFact['question_id'] . '|' . $relationFact['code']] ?? null;
    if ($relation === null || !in_array($relation['relation_kind'], [
        'fact_conflict', 'temporal_context_conflict', 'less_specific_supported',
    ], true)) {
        continue;
    }
    $row = $facts[$relationFact['question_id'] . '|' . $relationFact['fact_key']];
    $value = match ($row['value_type']) {
        QuestionFact::TYPE_TEXT => $row['value_text'],
        QuestionFact::TYPE_INTEGER => (int) $row['value_integer'],
        QuestionFact::TYPE_DECIMAL => (float) $row['value_decimal'],
        QuestionFact::TYPE_BOOLEAN => strtolower($row['value_boolean']) === 'true',
        QuestionFact::TYPE_CODE => $row['value_code'],
        QuestionFact::TYPE_ENUM => $row['value_enum'],
    };
    $fact = new QuestionFact(
        $row['fact_key'],
        $row['value_type'],
        $value,
        $row['unit'] !== '' ? $row['unit'] : null,
        $row['learner_label'],
        $row['source_context_item_id'] !== '' ? $row['source_context_item_id'] : null,
    );
    $clauses = LocalizedFactFormatter::clauses($fact);
    $normalized = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
    $id = implode(':', ['feedback-fact', $relation['relation_kind'], $row['fact_key'] . '=' . $normalized]);
    $entries[$id] = [
        'id' => $id,
        'location' => 'prototype_baseline/data/question_facts_0_1.csv#' . $row['fact_key']
            . ' + app/src/Evaluation/LocalizedFactFormatter.php',
        'en' => $clauses['en'],
        'de' => $clauses['de'],
        'interface_area' => 'value-aware relation feedback',
        'interpolation' => [],
        'origin' => 'typed database fact plus backend localized formatter',
        'audit_result' => 'pass_after_correction',
        'correction_made' => 'English-only learner_label replaced by a localized clause containing the represented fact value.',
        'intentional_non_translated' => false,
    ];
}

ksort($entries);
echo json_encode(array_values($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
