<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmailStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only viewers for audit_logs and email_logs.
 */
class LogController extends Controller
{
    public function audit(Request $request): View
    {
        return view('admin.logs.audit', [
            'logs' => AuditLog::with('user')
                ->when($request->input('action'), fn ($q, $v) => $q->where('action', 'like', "{$v}%"))
                ->when($request->input('user'), fn ($q, $v) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$v}%")->orWhere('username', 'like', "%{$v}%")))
                ->when($request->date('from'), fn ($q, $d) => $q->where('created_at', '>=', $d->startOfDay()))
                ->when($request->date('to'), fn ($q, $d) => $q->where('created_at', '<=', $d->endOfDay()))
                ->latest('id')->paginate(50)->withQueryString(),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action')
                ->map(fn ($a) => explode('.', $a)[0])->unique()->values(),
        ]);
    }

    public function email(Request $request): View
    {
        return view('admin.logs.email', [
            'logs' => EmailLog::with('user')
                ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
                ->when($request->input('recipient'), fn ($q, $v) => $q->where('recipient', 'like', "%{$v}%"))
                ->latest('id')->paginate(50)->withQueryString(),
            'statuses' => EmailStatus::cases(),
        ]);
    }
}
