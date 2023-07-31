<?php

declare(strict_types=1);

namespace Assessment\Unit;

use Assessment\Assessment;
use Assessment\AssessmentCycle;
use Assessment\AssessmentCycle\IssueAssessment\IssueAssessmentProcess;
use Assessment\AssessmentCycle\IssueAssessment\Policy\AssessmentTimeLimitPreserved;
use Assessment\AssessmentCycle\IssueAssessment\Policy\SupervisorHasAcriveContractWithClient;
use Assessment\AssessmentCycle\IssueAssessment\Policy\SupervisorHasAuthorityForStandard;
use Assessment\AssessmentCycleId;
use Assessment\AssessmentCycleSnapshot;
use Assessment\AssessmentId;
use Assessment\ClientId;
use Assessment\Command\IssueAssessment;
use Assessment\Evaluation;
use Assessment\Event\AssessmentCycleStarted;
use Assessment\Event\AssessmentIssued;
use Assessment\Event\AssessmentReplaced;
use Assessment\SupervisorId;
use Ecotone\Lite\EcotoneLite;
use Munus\Collection\GenericList;
use PHPUnit\Framework\TestCase;

final class IssueAssessmentTest extends TestCase
{
    public function test_assessment_can_be_issued_for_first_time_when_all_policies_are_satisfied(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [AssessmentCycle::class],
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
        $evaluation = Evaluation::PASSED;
        $issuedBy = new SupervisorId(1);

        self::assertEquals(
            [
                new AssessmentCycleStarted($assessmentCycleId),
                new AssessmentIssued($assessmentCycleId, $assessmentId, $issuedAt, $issuedBy, $evaluation),
            ],
            $ecotone
                ->sendCommand(new IssueAssessment($assessmentCycleId, $assessmentId, $issuedAt, $issuedBy, $evaluation))
                ->getRecordedEvents()
        );

        self::assertEquals(
            new AssessmentCycleSnapshot($assessmentCycleId, [new AssessmentCycle\Assessment($assessmentId, $issuedAt, $issuedBy, $evaluation)]),
            $ecotone->sendQueryWithRouting(AssessmentCycle::SNAPSHOT_QUERY, metadata: ['aggregate.id' => (string) $assessmentCycleId])
        );
    }

    /**
     * @dataProvider subsequentAssessments
     */
    public function test_subsequent_assessment_must_preserve_time_limit(\DateTimeImmutable $previousAssessmentIssueDate, Evaluation $previousAssessmentEvaluation, \DateTimeImmutable $subsequentAssessmentIssueDate): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [AssessmentCycle::class],
            containerOrAvailableServices: [
                new IssueAssessmentProcess(GenericList::of(new AssessmentTimeLimitPreserved([Evaluation::PASSED->value => 180, Evaluation::FAILED->value => 30])))
            ]
        );

        $assessmentCycleId = new AssessmentCycleId(new ClientId(1), 'standard');

        $this->expectException(\DomainException::class);

        $ecotone
                ->withEventsFor(
                    identifiers: $assessmentCycleId,
                    aggregateClass: AssessmentCycle::class,
                    events: [
                        new AssessmentCycleStarted($assessmentCycleId),
                        new AssessmentIssued($assessmentCycleId, new AssessmentId(1), $previousAssessmentIssueDate, new SupervisorId(1), $previousAssessmentEvaluation),
                    ]
                )
                ->sendCommand(new IssueAssessment($assessmentCycleId, new AssessmentId(2), $subsequentAssessmentIssueDate, new SupervisorId(1), Evaluation::PASSED))
        ;
    }

    public static function subsequentAssessments(): \Generator
    {
        yield 'after passed assessment before 180 days' => [
            'previousAssessmentIssueDate' => new \DateTimeImmutable('2023-01-01'),
            'previousAssessmentEvaluation' => Evaluation::PASSED,
            'subsequentAssessmentIssueDate' => (new \DateTimeImmutable('2023-01-01'))->modify('+150 days'),
        ];
        yield 'after passed assessment at 180 days' => [
            'previousAssessmentIssueDate' => new \DateTimeImmutable('2023-01-01'),
            'previousAssessmentEvaluation' => Evaluation::PASSED,
            'subsequentAssessmentIssueDate' => (new \DateTimeImmutable('2023-01-01'))->modify('+180 days'),
        ];
        yield 'after failed assessment before 30 days' => [
            'previousAssessmentIssueDate' => new \DateTimeImmutable('2023-01-01'),
            'previousAssessmentEvaluation' => Evaluation::FAILED,
            'subsequentAssessmentIssueDate' => (new \DateTimeImmutable('2023-01-01'))->modify('+20 days'),
        ];
        yield 'after failed assessment at 30 days' => [
            'previousAssessmentIssueDate' => new \DateTimeImmutable('2023-01-01'),
            'previousAssessmentEvaluation' => Evaluation::FAILED,
            'subsequentAssessmentIssueDate' => (new \DateTimeImmutable('2023-01-01'))->modify('+30 days'),
        ];
    }

    public function test_subsequent_assessment_can_replace_active_assessment(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [AssessmentCycle::class],
            containerOrAvailableServices: [new IssueAssessmentProcess(GenericList::of(new AssessmentTimeLimitPreserved([])))]
        );

        $assessmentCycleId = new AssessmentCycleId(new ClientId(1), 'standard');

        self::assertEquals(
            [
                new AssessmentIssued($assessmentCycleId, new AssessmentId(2), new \DateTimeImmutable('2023-07-01'), new SupervisorId(1), Evaluation::PASSED),
                new AssessmentReplaced($assessmentCycleId, new AssessmentId(1), new AssessmentId(2)),
            ],
            $ecotone
                ->withEventsFor(
                    identifiers: $assessmentCycleId,
                    aggregateClass: AssessmentCycle::class,
                    events: [
                        new AssessmentCycleStarted($assessmentCycleId),
                        new AssessmentIssued($assessmentCycleId, new AssessmentId(1), new \DateTimeImmutable('2023-01-01'), new SupervisorId(1), Evaluation::PASSED),
                    ]
                )
                ->sendCommand(new IssueAssessment($assessmentCycleId, new AssessmentId(2), new \DateTimeImmutable('2023-07-01'), new SupervisorId(1), Evaluation::PASSED))
                ->getRecordedEvents()
        );

        self::assertEquals(
            new AssessmentCycleSnapshot($assessmentCycleId, [
                new AssessmentCycle\Assessment(new AssessmentId(1), new \DateTimeImmutable('2023-01-01'), new SupervisorId(1), Evaluation::PASSED),
                new AssessmentCycle\Assessment(new AssessmentId(2), new \DateTimeImmutable('2023-07-01'), new SupervisorId(1), Evaluation::PASSED),
            ]),
            $ecotone->sendQueryWithRouting(AssessmentCycle::SNAPSHOT_QUERY, metadata: ['aggregate.id' => (string) $assessmentCycleId])
        );
    }

    /**
     * @dataProvider activeContracts
     */
    public function test_assessment_wont_be_issued_when_client_does_not_have_active_contract_with_supervisor(array $activeContracts): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [AssessmentCycle::class],
            containerOrAvailableServices: [
                new IssueAssessmentProcess(GenericList::of(new SupervisorHasAcriveContractWithClient($activeContracts)))
            ]
        );

        $assessmentCycleId = new AssessmentCycleId(new ClientId(1), 'standard');

        $this->expectException(\DomainException::class);

        $ecotone->sendCommand(new IssueAssessment($assessmentCycleId, new AssessmentId(2), new \DateTimeImmutable(), new SupervisorId(1), Evaluation::PASSED));
    }

    public static function activeContracts(): \Generator
    {
        yield 'supervisor has no contracts' => [[]];
        yield 'supervisor has no contract with client' => [[1 => []]];
    }

    /**
     * @dataProvider supervisorStandards
     */
    public function test_assessment_wont_be_issued_when_supervisor_has_no_authority_for_standard(array $supervisorStandards): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [AssessmentCycle::class],
            containerOrAvailableServices: [
                new IssueAssessmentProcess(GenericList::of(new SupervisorHasAuthorityForStandard($supervisorStandards)))
            ]
        );

        $assessmentCycleId = new AssessmentCycleId(new ClientId(1), 'standard');

        $this->expectException(\DomainException::class);

        $ecotone->sendCommand(new IssueAssessment($assessmentCycleId, new AssessmentId(2), new \DateTimeImmutable(), new SupervisorId(1), Evaluation::PASSED));
    }

    public static function supervisorStandards(): \Generator
    {
        yield 'supervisor has no authority' => [[]];
        yield 'supervisor has no authority for requested standard' => [[1 => []]];
    }
}
