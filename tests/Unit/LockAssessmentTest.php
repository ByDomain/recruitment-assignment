<?php

declare(strict_types=1);

namespace Assessment\Unit;

use Assessment\Assessment;
use Assessment\AssessmentCycleId;
use Assessment\AssessmentId;
use Assessment\ClientId;
use Assessment\Command\ExpireAssessment;
use Assessment\Command\LockAssessment;
use Assessment\Event\AssessmentCreated;
use Assessment\Event\AssessmentExpired;
use Assessment\Event\AssessmentLocked;
use Assessment\LockType;
use Ecotone\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;

final class LockAssessmentTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestIncomplete('assessment locks to be implemented');
    }

    public function test_active_assessment_can_be_locked(): void
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
                new AssessmentLocked($assessmentId, LockType::Suspension),
            ],
            $ecotone
                ->withEventsFor(
                    identifiers: $assessmentId,
                    aggregateClass: Assessment::class,
                    events: [
                        new AssessmentCreated($assessmentId, $assessmentCycleId, $issuedAt, $expireAt),
                    ]
                )
                ->sendCommand(new LockAssessment($assessmentId, LockType::Suspension))
                ->getRecordedEvents()
        );
    }

    public function test_inactive_assessment_cannot_be_locked(): void
    {

    }

    public function test_locked_assessment_can_be_unlocked(): void
    {

    }

    public function test_unlocked_assessment_wont_be_unlocked_again(): void
    {

    }
}
