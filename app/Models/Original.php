<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Original extends Model {
    public $timestamps = false;

    public static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function clearCache() {
        foreach(\File::directories(storage_path('app/public/data')) as $dir) {
            foreach([$dir . '/' . $this->path, $dir . '/webp/' . $this->path] as $path) {
                if(\File::exists($path)) {
                    unlink($path);
                }
            }
        }
    }
}
