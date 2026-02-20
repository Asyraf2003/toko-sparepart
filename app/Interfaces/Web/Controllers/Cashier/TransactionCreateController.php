<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\CreateTransactionRequest;
use App\Application\UseCases\Sales\CreateTransactionUseCase;
use Illuminate\Http\RedirectResponse;

final readonly class TransactionCreateController
{
    public function __construct(private CreateTransactionUseCase $create) {}

    public function __invoke(): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $tx = $this->create->handle(new CreateTransactionRequest(actorUserId: (int) $user->id));

        return redirect('/cashier/transactions/'.$tx->id);
    }
}
