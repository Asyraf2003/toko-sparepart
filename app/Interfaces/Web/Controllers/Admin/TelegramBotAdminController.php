<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class TelegramBotAdminController
{
    public function index(Request $request)
    {
        $user = $request->user();

        $linked = DB::table('telegram_links')
            ->where('user_id', $user->id)
            ->first(['chat_id', 'linked_at']);

        $pendingTokens = DB::table('telegram_pairing_tokens')
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->limit(5)
            ->get(['expires_at']);

        return response()->view('admin.telegram.index', [
            'linked' => $linked,
            'pendingTokens' => $pendingTokens,
            'lastToken' => session('telegram_pairing_token'),
        ]);
    }

    public function createPairingToken(Request $request): RedirectResponse
    {
        $user = $request->user();

        $ttl = (int) config('services.telegram_ops.pairing_token_ttl_minutes', 10);
        if ($ttl <= 0) {
            $ttl = 10;
        }

        $token = Str::random(32);
        $hash = hash('sha256', $token);

        DB::table('telegram_pairing_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => $hash,
            'expires_at' => now()->addMinutes($ttl),
            'used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('telegram_pairing_token', $token);
    }
}
