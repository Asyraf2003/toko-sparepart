<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\System;

use App\Application\UseCases\System\PingUseCase;
use Illuminate\Http\JsonResponse;

final class PingController
{
    public function __invoke(PingUseCase $useCase): JsonResponse
    {
        $out = $useCase->handle();

        return response()->json($out->toArray());
    }
}
