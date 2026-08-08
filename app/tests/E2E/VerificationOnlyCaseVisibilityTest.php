<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\E2E;

/**
 * TEST-E2E-02: verification-only case boundary. `CASE-004` and `CASE-008`
 * (both `intended_use = verification_only` — see
 * chapter3_reference_case_coverage_plan.md CASEPLAN-0.2) must not be
 * offered through the learner-facing case list, while remaining reachable
 * by the technical verification path. The frontend has no client-side
 * router (docs/DEVELOPMENT_DOCUMENTATION.md §7), so the case list is the
 * only navigation surface a learner's browser ever has - confirming it is
 * absent there confirms the exclusion completely.
 */
final class VerificationOnlyCaseVisibilityTest extends SeleniumTestCase
{
    public function testVerificationOnlyCasesAreAbsentFromTheLearnerCaseList(): void
    {
        $this->openCaseList();
        $labels = $this->caseListButtonLabels();

        self::assertNotEmpty($labels, 'expected at least the learner-visible cases to render');
        foreach ($labels as $label) {
            self::assertStringNotContainsString('CASE-004', $label);
            self::assertStringNotContainsString('CASE-008', $label);
        }
    }

    /**
     * Not a browser interaction - there is no UI path to reach these cases
     * to click through in the first place. This confirms the exclusion
     * above is a navigation-layer choice, not a removal of the underlying
     * evaluation capability (RC-004-01/RC-008-01 already assert the exact
     * classification via TEST-RC-01; this only re-confirms reachability
     * through the same boundary a learner's browser talks to).
     */
    public function testVerificationOnlyCasesRemainReachableThroughTheEvaluationEndpoint(): void
    {
        foreach (['CASE-004', 'CASE-008'] as $caseId) {
            $response = $this->postEvaluate($caseId, 'Z01.6');

            self::assertSame(200, $response['status'], "$caseId: evaluate endpoint status");
            self::assertSame('classified', $response['body']['evaluation_status'] ?? null, "$caseId: evaluation_status");
            self::assertSame('incorrect', $response['body']['classification'] ?? null, "$caseId: classification");
        }
    }
}
