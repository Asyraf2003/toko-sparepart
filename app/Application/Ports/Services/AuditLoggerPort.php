<?php

declare(strict_types=1);

namespace App\Application\Ports\Services;

use App\Domain\Audit\AuditEntry;

interface AuditLoggerPort
{
    public function append(AuditEntry $entry): void;
}