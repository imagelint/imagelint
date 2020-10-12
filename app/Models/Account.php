<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public function domains()
    {
        return $this->hasMany(Domain::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
