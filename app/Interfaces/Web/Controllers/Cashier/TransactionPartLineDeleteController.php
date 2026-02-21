<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\DeletePartLineRequest;
use App\Application\UseCases\Sales\DeletePartLineUseCase;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class TransactionPartLineDeleteController
{
    public function __invoke(int $transactionId, int $lineId, DeletePartLineUseCase $uc): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $data = request()->validate([
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        try {
            $uc->handle(new DeletePartLineRequest(
                transactionId: $transactionId,
                lineId: $lineId,
                actorUserId: (int) $user->id,
                reason: (string) $data['reason'],
            ));
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
