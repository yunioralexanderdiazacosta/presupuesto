<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoginLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin');
    }

    public function __invoke(Request $request)
    {
        $term = $request->term ?? '';

        $logs = LoginLog::with('user.team')
            ->when($term, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                })
                ->orWhereHas('user.team', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('logged_in_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('LoginLogs', compact('logs', 'term'));
    }
}
