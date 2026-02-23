<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\DeleteServiceLineRequest;
use App\Application\UseCases\Sales\DeleteServiceLineUseCase;
use Illuminate\Http\RedirectResponse;
use App\Shared\Messages\MessagesId;
use Throwable;

final readonly class TransactionServiceLineDeleteController
{
    public function __invoke(int $transactionId, int $lineId, DeleteServiceLineUseCase $uc): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $data = request()->validate([
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        try {
            $uc->handle(new DeleteServiceLineRequest(
                transactionId: $transactionId,
                serviceLineId: $lineId,
                actorUserId: (int) $user->id,
                reason: (string) $data['reason'],
            ));
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', MessagesId::error($e));
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
