<?php

declare(strict_types=1);

namespace Assessment;

enum LockType: string
{
    case Suspension = 'suspension';

    case Withdrawal = 'Withdrawal';
}
