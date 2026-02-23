<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\UpdatePartLineQtyRequest;
use App\Application\UseCases\Sales\UpdatePartLineQtyUseCase;
use App\Shared\Messages\MessagesId;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class TransactionPartLineUpdateQtyController
{
    public function __invoke(int $transactionId, int $lineId, UpdatePartLineQtyUseCase $uc): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $data = request()->validate([
            'qty' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        try {
            $uc->handle(new UpdatePartLineQtyRequest(
                transactionId: $transactionId,
                lineId: $lineId,
                newQty: (int) $data['qty'],
                actorUserId: (int) $user->id,
                reason: (string) $data['reason'],
            ));
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', MessagesId::error($e));
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
