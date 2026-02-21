<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use RuntimeException;

final class ReasonRequired extends RuntimeException
{
    public function __construct(string $message = 'reason is required')
    {
        parent::__construct($message);
    }
}
