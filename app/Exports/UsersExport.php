<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UsersExport implements FromView, ShouldAutoSize
{
    public $term;

    public function __construct($term)
    {
        $this->term = $term;
    }

    public function view(): View
    {
        $user = Auth::user();

        $users = User::where('team_id', $user->team_id)->when($this->term, function ($query, $search) {
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

        return view('excels.users', [
            'users' => $users
        ]);
    }
}
