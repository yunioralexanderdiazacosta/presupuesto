<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Session;

class ClearSessionOnLogout
{
    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Logout  $event
     * @return void
     */
    public function handle(Logout $event)
    {
        // Limpiar todas las variables de sesión personalizadas
        Session::forget('season_id');
        Session::forget('price');
        
        // Opcional: Limpiar toda la sesión
        // Session::flush();
    }
}
