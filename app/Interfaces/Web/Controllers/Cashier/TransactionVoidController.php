<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\VoidTransactionRequest;
use App\Application\UseCases\Sales\VoidTransactionUseCase;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class TransactionVoidController
{
    public function __construct(private VoidTransactionUseCase $useCase) {}

    public function __invoke(int $transactionId): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $data = request()->validate([
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        try {
            $this->useCase->handle(new VoidTransactionRequest(
                transactionId: $transactionId,
                actorUserId: (int) $user->id,
                reason: (string) $data['reason'],
            ));
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
