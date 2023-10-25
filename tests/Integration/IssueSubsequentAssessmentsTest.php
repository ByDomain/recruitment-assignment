<?php

declare(strict_types=1);

namespace Assessment\Integration;

use Assessment\Assessment;
use Assessment\AssessmentCycle;
use Assessment\AssessmentCycle\IssueAssessment\IssueAssessmentProcess;
use Assessment\AssessmentCycle\IssueAssessment\Policy\AssessmentTimeLimitPreserved;
use Assessment\AssessmentCycle\IssueAssessment\Policy\SupervisorHasActiveContractWithClient;
use Assessment\AssessmentCycle\IssueAssessment\Policy\SupervisorHasAuthorityForStandard;
use Assessment\AssessmentCycleId;
use Assessment\AssessmentCycleSnapshot;
use Assessment\AssessmentId;
use Assessment\AssessmentSnapshot;
use Assessment\ClientId;
use Assessment\Command\IssueAssessment;
use Assessment\Evaluation;
use Assessment\Event\AssessmentCreated;
use Assessment\Event\AssessmentCycleStarted;
use Assessment\Event\AssessmentIssued;
use Assessment\Event\AssessmentReplaced;
use Assessment\Event\AssessmentWasReplaced;
use Assessment\Interceptor\BeforeAssessmentReplaced;
use Assessment\SupervisorId;
use Ecotone\Lite\EcotoneLite;
use Munus\Collection\GenericList;
use PHPUnit\Framework\TestCase;

final class IssueSubsequentAssessmentsTest extends TestCase
{
    public function test_issuing_subsequent_assessments(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [AssessmentCycle::class, Assessment::class, BeforeAssessmentReplaced::class],
            containerOrAvailableServices: [
                new BeforeAssessmentReplaced(),
                new IssueAssessmentProcess(GenericList::of(
                    new AssessmentTimeLimitPreserved([Evaluation::PASSED->value => 180, Evaluation::FAILED->value => 60]),
                    new SupervisorHasActiveContractWithClient([1 => [1]]),
                    new SupervisorHasAuthorityForStandard([1 => ['standard']]),
                ))
            ]
        );

        $assessmentCycleId = new AssessmentCycleId(new ClientId(1), 'standard');
        $issuedBy = new SupervisorId(1);

        $assessment1Id = new AssessmentId(1);
        $issued1At = new \DateTimeImmutable();
        $expire1At = $issued1At->modify(sprintf('+%d days', Assessment::ASSESSMENT_VALIDITY_PERIOD_IN_DAYS));
        $evaluation1 = Evaluation::PASSED;

        self::assertEquals(
            [
                new AssessmentCycleStarted($assessmentCycleId),
                new AssessmentIssued($assessmentCycleId, $assessment1Id, $issued1At, $issuedBy, $evaluation1),
                new AssessmentCreated($assessment1Id, $assessmentCycleId,  $issued1At, $expire1At),
            ],
            $ecotone
                ->sendCommand(new IssueAssessment($assessmentCycleId, $assessment1Id, $issued1At, $issuedBy, $evaluation1))
                ->getRecordedEvents()
        );

        self::assertEquals(
            new AssessmentSnapshot($assessment1Id, $issued1At, $expire1At, true),
            $ecotone->sendQueryWithRouting(Assessment::SNAPSHOT_QUERY, metadata: ['aggregate.id' => $assessment1Id])
        );

        $assessment2Id = new AssessmentId(2);
        $issued2At = $issued1At->modify('+200 days');
        $expire2At = $issued2At->modify(sprintf('+%d days', Assessment::ASSESSMENT_VALIDITY_PERIOD_IN_DAYS));
        $evaluation2 = Evaluation::FAILED;

        self::assertEquals(
            [
                new AssessmentIssued($assessmentCycleId, $assessment2Id, $issued2At, $issuedBy, $evaluation2),
                new AssessmentCreated($assessment2Id, $assessmentCycleId,  $issued2At, $expire2At),
                new AssessmentReplaced($assessmentCycleId, $assessment1Id, $assessment2Id),
                new AssessmentWasReplaced($assessment1Id, $assessment2Id),
            ],
            $ecotone
                ->sendCommand(new IssueAssessment($assessmentCycleId, $assessment2Id, $issued2At, $issuedBy, $evaluation2))
                ->getRecordedEvents()
        );

        self::assertEquals(
            new AssessmentSnapshot($assessment2Id, $issued2At, $expire2At, true),
            $ecotone->sendQueryWithRouting(Assessment::SNAPSHOT_QUERY, metadata: ['aggregate.id' => $assessment2Id])
        );

        $assessment3Id = new AssessmentId(3);
        $issued3At = $issued2At->modify('+70 days');
        $expire3At = $issued3At->modify(sprintf('+%d days', Assessment::ASSESSMENT_VALIDITY_PERIOD_IN_DAYS));
        $evaluation3 = Evaluation::PASSED;

        self::assertEquals(
            [
                new AssessmentIssued($assessmentCycleId, $assessment3Id, $issued3At, $issuedBy, $evaluation3),
                new AssessmentCreated($assessment3Id, $assessmentCycleId,  $issued3At, $expire3At),
                new AssessmentReplaced($assessmentCycleId, $assessment2Id, $assessment3Id),
                new AssessmentWasReplaced($assessment2Id, $assessment3Id),
            ],
            $ecotone
                ->sendCommand(new IssueAssessment($assessmentCycleId, $assessment3Id, $issued3At, $issuedBy, $evaluation3))
                ->getRecordedEvents()
        );

        self::assertEquals(
            new AssessmentSnapshot($assessment3Id, $issued3At, $expire3At, true),
            $ecotone->sendQueryWithRouting(Assessment::SNAPSHOT_QUERY, metadata: ['aggregate.id' => $assessment3Id])
        );

        self::assertFalse($ecotone->sendQueryWithRouting(Assessment::IS_ACTIVE_QUERY, metadata: ['aggregate.id' => $assessment1Id]));
        self::assertFalse($ecotone->sendQueryWithRouting(Assessment::IS_ACTIVE_QUERY, metadata: ['aggregate.id' => $assessment2Id]));
        self::assertTrue($ecotone->sendQueryWithRouting(Assessment::IS_ACTIVE_QUERY, metadata: ['aggregate.id' => $assessment3Id]));

        self::assertEquals(
            new AssessmentCycleSnapshot(
                $assessmentCycleId,
                [
                    new AssessmentCycle\Assessment($assessment1Id, $issued1At, $issuedBy, $evaluation1),
                    new AssessmentCycle\Assessment($assessment2Id, $issued2At, $issuedBy, $evaluation2),
                    new AssessmentCycle\Assessment($assessment3Id, $issued3At, $issuedBy, $evaluation3),
                ]
            ),
            $ecotone->sendQueryWithRouting(AssessmentCycle::SNAPSHOT_QUERY, metadata: ['aggregate.id' => $assessmentCycleId])
        );
    }
}
