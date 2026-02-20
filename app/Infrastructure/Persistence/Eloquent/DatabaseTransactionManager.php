<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Ports\Services\TransactionManagerPort;
use Illuminate\Support\Facades\DB;

final class DatabaseTransactionManager implements TransactionManagerPort
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
