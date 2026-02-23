<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\AddServiceLineRequest;
use App\Application\UseCases\Sales\AddServiceLineUseCase;
use Illuminate\Http\RedirectResponse;
use App\Shared\Messages\MessagesId;
use Throwable;

final readonly class TransactionServiceLineStoreController
{
    public function __construct(private AddServiceLineUseCase $useCase) {}

    public function __invoke(int $transactionId): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $data = request()->validate([
            'description' => ['required', 'string', 'min:1', 'max:255'],
            'price_manual' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        try {
            $this->useCase->handle(new AddServiceLineRequest(
                transactionId: $transactionId,
                description: (string) $data['description'],
                priceManual: (int) $data['price_manual'],
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
