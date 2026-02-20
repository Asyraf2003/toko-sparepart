<?php

declare(strict_types=1);

namespace App\Application\Ports\Services;

interface TransactionManagerPort
{
    /**
     * Executes the callback inside a DB transaction boundary.
     *
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
