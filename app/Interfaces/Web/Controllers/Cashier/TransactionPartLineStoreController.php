<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\AddPartLineRequest;
use App\Application\UseCases\Sales\AddPartLineUseCase;
use App\Shared\Messages\MessagesId;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class TransactionPartLineStoreController
{
    public function __construct(private AddPartLineUseCase $useCase) {}

    public function __invoke(int $transactionId): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $data = request()->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'qty' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        try {
            $this->useCase->handle(new AddPartLineRequest(
                transactionId: $transactionId,
                productId: (int) $data['product_id'],
                qty: (int) $data['qty'],
                actorUserId: (int) $user->id,
                reason: (string) $data['reason'],
            ));
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)
                ->withInput()
                ->with('error', MessagesId::error($e));
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
