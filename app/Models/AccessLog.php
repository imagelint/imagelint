<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    public function original() {
        return $this->belongsTo(Original::class);
    }
}
