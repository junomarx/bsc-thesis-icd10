<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\E2E;

/**
 * TEST-E2E-02: verification-only question boundary. The 8 hidden `VQ-*`
 * questions (`intended_use = verification_only`, retained to preserve the
 * 18 historical `RCBASE-0.2` regression obligations - `REQ-VER-09`) must
 * not be offered through the learner-facing patient roster, while
 * remaining reachable by the technical verification path. The frontend has
 * no client-side router (`docs/DEVELOPMENT_DOCUMENTATION.md` §7), so the
 * roster is the only navigation surface a learner's browser ever has -
 * confirming the exclusion there confirms it completely.
 */
final class VerificationOnlyQuestionVisibilityTest extends SeleniumTestCase
{
    private const HIDDEN_QUESTION_IDS = ['VQ-001', 'VQ-002', 'VQ-003', 'VQ-004', 'VQ-005', 'VQ-006', 'VQ-007', 'VQ-008'];

    public function testRosterExposesExactlyTheLearnerVisibleQuestionCount(): void
    {
        $this->openRoster();

        self::assertCount(6, $this->rosterPatientIds(), 'expected all 6 patients to render');
        // 25 learner-visible questions total (3+3+3+5+5+6); if any of the 8
        // hidden VQ-* questions were reachable through this roster, the sum
        // of every card's "N questions" badge would exceed 25.
        self::assertSame(25, $this->rosterTotalQuestionCount());
    }

    /**
     * Not a browser interaction - there is no UI path to reach these
     * questions to click through in the first place. This confirms the
     * exclusion above is a navigation-layer choice, not a removal of the
     * underlying evaluation capability (the legacy `RC-00*-01` rows already
     * assert the exact classification via TEST-RC-01; this only
     * re-confirms reachability through the same boundary a learner's
     * browser talks to).
     */
    public function testHiddenQuestionsRemainReachableThroughTheEvaluationEndpoint(): void
    {
        // One already-verified (TEST-RC-01) submission per hidden question.
        $submissions = [
            'VQ-001' => ['type' => 'code', 'code' => 'J44.02'],
            'VQ-002' => ['type' => 'code', 'code' => 'J44.12'],
            'VQ-003' => ['type' => 'code', 'code' => 'Z01.6'],
            'VQ-004' => ['type' => 'code', 'code' => 'Z01.6'],
            'VQ-005' => ['type' => 'code', 'code' => 'J44.00'],
            'VQ-006' => ['type' => 'code', 'code' => 'J44.11'],
            'VQ-007' => ['type' => 'code', 'code' => 'J44.03'],
            'VQ-008' => ['type' => 'code', 'code' => 'Z01.6'],
        ];

        foreach ($submissions as $questionId => $response) {
            $result = $this->postEvaluate($questionId, $response);

            self::assertSame(200, $result['status'], "$questionId: evaluate endpoint status");
            self::assertSame('classified', $result['body']['evaluation_status'] ?? null, "$questionId: evaluation_status");
        }
    }

    public function testHiddenQuestionsAreNotFoundOnTheLearnerFacingDetailRead(): void
    {
        foreach (self::HIDDEN_QUESTION_IDS as $questionId) {
            $result = $this->getQuestion($questionId);

            self::assertSame(404, $result['status'], "$questionId: GET /api/questions/{id} must 404 for verification_only");
        }
    }
}
