<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModule extends Model
{
    protected $fillable = ['user_id', 'module_key'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
