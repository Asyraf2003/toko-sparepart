<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Audit\ShowAuditLogUseCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AuditLogShowController
{
    public function __invoke(int $auditLogId, Request $request, ShowAuditLogUseCase $uc): View
    {
        $detail = $uc->handle($auditLogId);
        if ($detail === null) {
            abort(404);
        }

        return view('admin.audit_logs.show', [
            'a' => $detail,
        ]);
    }
}
