<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\CompleteTransactionRequest;
use App\Application\UseCases\Sales\CompleteTransactionUseCase;
use Illuminate\Http\RedirectResponse;
use App\Shared\Messages\MessagesId;
use Throwable;

final readonly class TransactionCompleteCashController
{
    public function __construct(private CompleteTransactionUseCase $useCase) {}

    public function __invoke(int $transactionId): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $data = request()->validate([
            'cash_received' => ['nullable', 'integer', 'min:0'],
        ]);

        $cashReceived = isset($data['cash_received']) ? (int) $data['cash_received'] : null;

        try {
            $this->useCase->handle(new CompleteTransactionRequest(
                transactionId: $transactionId,
                paymentMethod: 'CASH',
                actorUserId: (int) $user->id,
                cashReceived: $cashReceived,
            ));
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', MessagesId::error($e));
        }

        return redirect('/cashier/transactions/today');
    }
}
