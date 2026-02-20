<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\OpenTransactionRequest;
use App\Application\UseCases\Sales\OpenTransactionUseCase;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class TransactionOpenController
{
    public function __construct(private OpenTransactionUseCase $useCase) {}

    public function __invoke(int $transactionId): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        try {
            $this->useCase->handle(new OpenTransactionRequest(
                transactionId: $transactionId,
                actorUserId: (int) $user->id,
            ));
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
