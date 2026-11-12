<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __invoke(): Response
    {
        $logs = AuditLog::with(['actor:id,name', 'targetUser:id,name,username'])
            ->latest('created_at')
            ->paginate(30)
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action->value,
                'actor_name' => $log->actor->name,
                'target_name' => $log->targetUser->name,
                'reason' => $log->reason,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at->format('d M Y, H:i'),
            ]);

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
        ]);
    }
}
