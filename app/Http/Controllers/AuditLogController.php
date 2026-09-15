<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->can('audit.view'), 403);
        $logs = AuditLog::with('user')->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))->latest('created_at')->paginate(30)->withQueryString();

        return view('audit.index', ['logs' => $logs, 'users' => User::orderBy('name')->get(), 'modules' => AuditLog::distinct()->orderBy('module')->pluck('module'), 'actions' => AuditLog::distinct()->orderBy('action')->pluck('action'), 'filters' => $request->all()]);
    }

    public function show(AuditLog $auditLog): View
    {
        abort_unless(auth()->user()->can('audit.view'), 403);

        return view('audit.show', ['log' => $auditLog->load('user')]);
    }
}
