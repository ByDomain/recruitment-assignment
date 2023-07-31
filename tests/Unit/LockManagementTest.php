<?php

declare(strict_types=1);

namespace Assessment\Unit;

use PHPUnit\Framework\TestCase;

final class LockManagementTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestIncomplete('assessment locks to be implemented');
    }

    public function test_lock_can_be_reinstated_when_assessment_is_unlocked(): void
    {

    }

    public function test_suspension_lock_can_be_changed_to_withdrawal(): void
    {

    }

    public function test_reinstated_suspension_lock_wont_be_changed_to_withdrawal(): void
    {

    }

    public function test_withdrawal_lock_wont_be_changed_to_withdrawal_again(): void
    {

    }
}
