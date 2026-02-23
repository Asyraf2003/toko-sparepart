<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\UseCases\Sales\OpenTransactionRequest;
use App\Application\UseCases\Sales\OpenTransactionUseCase;
use App\Shared\Messages\MessagesId;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class TransactionOpenController
{
    public function __construct(private OpenTransactionUseCase $useCase) {}

    public function __invoke(int $transactionId): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        // Patch fields: hanya key yang dikirim request yang akan masuk ke $fields.
        // Jika form lain (Simpan Nota) tidak mengirim field customer, maka $fields kosong -> tidak overwrite.
        $fields = request()->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $this->useCase->handle(new OpenTransactionRequest(
                transactionId: $transactionId,
                actorUserId: (int) $user->id,
                fields: $fields,
            ));
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', MessagesId::error($e));
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
