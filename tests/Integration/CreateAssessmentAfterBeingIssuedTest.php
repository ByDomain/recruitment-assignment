<?php

declare(strict_types=1);

namespace Assessment\Integration;

use Assessment\Assessment;
use Assessment\AssessmentCycle;
use Assessment\AssessmentCycle\IssueAssessment\IssueAssessmentProcess;
use Assessment\AssessmentCycle\IssueAssessment\Policy\AssessmentTimeLimitPreserved;
use Assessment\AssessmentCycle\IssueAssessment\Policy\SupervisorHasAcriveContractWithClient;
use Assessment\AssessmentCycle\IssueAssessment\Policy\SupervisorHasAuthorityForStandard;
use Assessment\AssessmentCycleId;
use Assessment\AssessmentId;
use Assessment\AssessmentSnapshot;
use Assessment\ClientId;
use Assessment\Command\IssueAssessment;
use Assessment\Evaluation;
use Assessment\Event\AssessmentCreated;
use Assessment\Event\AssessmentCycleStarted;
use Assessment\Event\AssessmentIssued;
use Assessment\SupervisorId;
use Ecotone\Lite\EcotoneLite;
use Munus\Collection\GenericList;
use PHPUnit\Framework\TestCase;

final class CreateAssessmentAfterBeingIssuedTest extends TestCase
{
    public function test_after_being_issued_assessment_gets_created(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [AssessmentCycle::class, Assessment::class],
            containerOrAvailableServices: [
                new IssueAssessmentProcess(GenericList::of(
                    new AssessmentTimeLimitPreserved([]),
                    new SupervisorHasAcriveContractWithClient([1 => [1]]),
                    new SupervisorHasAuthorityForStandard([1 => ['standard']]),
                ))
            ]
        );

        $assessmentCycleId = new AssessmentCycleId(new ClientId(1), 'standard');
        $assessmentId = new AssessmentId(1);
        $issuedAt = new \DateTimeImmutable();
        $expireAt = $issuedAt->modify(sprintf('+%d days', Assessment::ASSESSMENT_VALIDITY_PERIOD_IN_DAYS));
        $evaluation = Evaluation::PASSED;
        $issuedBy = new SupervisorId(1);

        self::assertEquals(
            [
                new AssessmentCycleStarted($assessmentCycleId),
                new AssessmentIssued($assessmentCycleId, $assessmentId, $issuedAt, $issuedBy, $evaluation),
                new AssessmentCreated($assessmentId, $assessmentCycleId,  $issuedAt, $expireAt),
            ],
            $ecotone
                ->sendCommand(new IssueAssessment($assessmentCycleId, $assessmentId, $issuedAt, $issuedBy, $evaluation))
                ->getRecordedEvents()
        );

        self::assertEquals(
            new AssessmentSnapshot($assessmentId, $issuedAt, $expireAt),
            $ecotone->sendQueryWithRouting(Assessment::SNAPSHOT_QUERY, metadata: ['aggregate.id' => $assessmentId->id])
        );
    }
}
