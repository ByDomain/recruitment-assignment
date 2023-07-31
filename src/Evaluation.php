<?php

declare(strict_types=1);

namespace Assessment;

enum Evaluation: string
{
    case PASSED = 'passed';

    case FAILED = 'failed';
}
