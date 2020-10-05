<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model {
    public $timestamps = false;

    public static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function isExist($domain, $userId) {
        $result = $this->where('user_id', $userId)->where('domain', $domain)->first();
        if(empty($result))
            return true;
        return false;
    }
}
