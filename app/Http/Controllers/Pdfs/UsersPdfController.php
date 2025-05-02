<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class UsersPdfController extends Controller
{
    public function __invoke(Request $request)
    { 
        $user = Auth::user();

        $users = User::where('team_id', $user->team_id)->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
        })->whereNot('id', $user->id)
        ->get()
        ->transform(function($value){
            return [
                'id' => $value->id,
                'name' => $value->name,
                'email' => $value->email,
                'role'  => $value->getRoleNames()[0] == 'Normal' ? 'Digitador' : 'Administrador',
                'status' => $value->status,
                'created_at' => $value->created_at 
            ];
        });


        $pdf = Pdf::loadView('pdfs.users', ['users' => $users]);

        return $pdf->stream();  
    }
}
