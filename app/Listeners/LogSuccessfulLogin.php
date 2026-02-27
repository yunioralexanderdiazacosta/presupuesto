<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin
{
    /**
     * Registra el login exitoso del usuario en la tabla login_logs.
     */
    public function handle(Login $event): void
    {
        try {
            LoginLog::create([
                'user_id'      => $event->user->id,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
                'logged_in_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('No se pudo registrar login: ' . $e->getMessage());
        }
    }
}
