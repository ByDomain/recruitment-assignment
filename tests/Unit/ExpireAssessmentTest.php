<?php

declare(strict_types=1);

namespace Assessment\Unit;

use Assessment\Assessment;
use Assessment\AssessmentCycleId;
use Assessment\AssessmentId;
use Assessment\ClientId;
use Assessment\Command\ExpireAssessment;
use Assessment\Event\AssessmentCreated;
use Assessment\Event\AssessmentExpired;
use Ecotone\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;

final class ExpireAssessmentTest extends TestCase
{
    public function test_after_expiration_date_exceeds_assessment_will_expire(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [Assessment::class],
        );

        $assessmentId = new AssessmentId(1);
        $assessmentCycleId = new AssessmentCycleId(new ClientId(1), 'standard');
        $issuedAt = new \DateTimeImmutable();
        $expireAt = new \DateTimeImmutable('+5 days');

        self::assertEquals(
            [
                new AssessmentExpired($assessmentId, $expireAt),
            ],
            $ecotone
                ->withEventsFor(
                    identifiers: $assessmentId,
                    aggregateClass: Assessment::class,
                    events: [
                        new AssessmentCreated($assessmentId, $assessmentCycleId, $issuedAt, $expireAt),
                    ]
                )
                ->sendCommand(new ExpireAssessment($assessmentId, $expireAt->modify('+1 day')))
                ->getRecordedEvents()
        );
    }

    public function test_assessment_cannot_expire_before_expiration_date(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [Assessment::class],
        );

        $assessmentId = new AssessmentId(1);
        $assessmentCycleId = new AssessmentCycleId(new ClientId(1), 'standard');
        $issuedAt = new \DateTimeImmutable();
        $expireAt = new \DateTimeImmutable('+5 days');

        self::assertEquals(
            [],
            $ecotone
                ->withEventsFor(
                    identifiers: $assessmentId,
                    aggregateClass: Assessment::class,
                    events: [
                        new AssessmentCreated($assessmentId, $assessmentCycleId, $issuedAt, $expireAt),
                    ]
                )
                ->sendCommand(new ExpireAssessment($assessmentId, $expireAt->modify('-1 day')))
                ->getRecordedEvents()
        );
    }

    public function test_locked_assessment_can_still_expire(): void
    {
        $this->markTestIncomplete('assessment locks to be implemented');
    }
}
