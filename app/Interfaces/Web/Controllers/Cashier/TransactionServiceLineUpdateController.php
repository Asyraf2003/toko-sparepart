<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\UpdateServiceLineRequest;
use App\Application\UseCases\Sales\UpdateServiceLineUseCase;
use App\Shared\Messages\MessagesId;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class TransactionServiceLineUpdateController
{
    public function __invoke(int $transactionId, int $lineId, UpdateServiceLineUseCase $uc): RedirectResponse
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
            $uc->handle(new UpdateServiceLineRequest(
                transactionId: $transactionId,
                serviceLineId: $lineId,
                description: (string) $data['description'],
                priceManual: (int) $data['price_manual'],
                actorUserId: (int) $user->id,
                reason: (string) $data['reason'],
            ));
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', MessagesId::error($e));
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
