<?php

namespace App\Image;

use App\Original;

class OriginalTracker {
    public function track($path, $originalSize) {
        $original = Original::where('user_id', 1)
            ->where('path', $path)
            ->count();
        if(!$original) {
            $original = new Original();
            $original->path = $path;
            $original->user_id = 1;
            $original->domain = parse_url($path,PHP_URL_HOST);
            $original->size = $originalSize;
            $original->save();
        }
    }
}