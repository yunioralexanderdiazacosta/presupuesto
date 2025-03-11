<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class DeleteUserController extends Controller
{
    public function __invoke(User $user)
    {
        $user->delete();
    }
}
