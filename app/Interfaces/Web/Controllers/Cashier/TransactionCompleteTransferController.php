<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\CompleteTransactionRequest;
use App\Application\UseCases\Sales\CompleteTransactionUseCase;
use App\Shared\Messages\MessagesId;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class TransactionCompleteTransferController
{
    public function __construct(private CompleteTransactionUseCase $useCase) {}

    public function __invoke(int $transactionId): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        try {
            $this->useCase->handle(new CompleteTransactionRequest(
                transactionId: $transactionId,
                paymentMethod: 'TRANSFER',
                actorUserId: (int) $user->id,
            ));
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', MessagesId::error($e));
        }

        return redirect('/cashier/transactions/today');
    }
}
