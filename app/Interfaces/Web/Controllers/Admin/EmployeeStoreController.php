<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class EmployeeStoreController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:190'],
            'is_active' => ['nullable', 'in:1'],
        ]);

        DB::table('employees')->insert([
            'name' => (string) $data['name'],
            'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/admin/employees');
    }
}
