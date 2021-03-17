<?php

namespace App\Image;

use App\Models\Original;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Transformer
{

    private $modifiers;
    private $in;
    private $out;
    private $original;
    private $quality;

    public function transform(PathBuilder $path, Original $original)
    {
        $this->original = $original;
        $params = $path->getAllParams();

        $modifiers = [
            'width' => Arr::get($params, 'il-width', null),
            'height' => Arr::get($params, 'il-height', null),
            'dpr' => Arr::get($params, 'il-dpr', 1),
            'lossy' => Arr::get($params, 'il-lossy', false),
        ];

        $this->modifiers = $modifiers;
        if ($quality = Arr::get($params, 'il-quality', null)) {
            $this->quality = (int)$quality;
        }

        $cacheStorage = Storage::disk(config('imagelint.cache_disk', 'local'));
        $tmpStorage = Storage::disk(config('imagelint.tmp_disk', 'local'));

        $this->in = $cacheStorage->path($path->getCachePath());
        $this->out = $tmpStorage->path($path->getTransformPath());

        $this->makeDirectory($this->out);

        $this->copyToOut();

        $filetype = $path->getOutFileType();
        if ($filetype !== 'image/svg+xml') {
            $this->resize();
        }

        return true;
    }

    private function copyToOut()
    {
        File::copy($this->in, $this->out);
    }

    private function resize()
    {
        $width = $this->modifiers['width'];
        $height = $this->modifiers['height'];
        if (!$width && !$height) {
            return;
        }

        $dpr = floatval($this->modifiers['dpr']);
        if ($width) {
            $width *= $dpr;
        }
        if ($height) {
            $height *= $dpr;
        }

        $img = Image::make($this->out);
        if ($width && $height) {
            $img->fit($width, $height, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        $img->save();
    }

    /**
     * Creates the directory where the original file gets stored
     *
     * @param $path
     */
    private function makeDirectory($path) {
        $path = dirname($path);
        if(!File::exists($path)) {
            try {
                @File::makeDirectory($path,0755,true);
            } catch(\Exception $e) {
                // Due to multiple requests at once it might happen that the directoy already exists
            }
        }
    }
}
