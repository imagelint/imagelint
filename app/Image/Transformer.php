<?php

namespace App\Image;

use App\Models\Original;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Transformer
{
    /**
     * @var Downloader
     */
    private $downloader;

    public function __construct(Downloader $downloader) {
        $this->downloader = $downloader;
    }

    private $modifiers;
    private $in;
    private $out;
    private $original;
    private $quality;

    public function transform(ImageRequest $imageRequest, Original $original)
    {
        $this->original = $original;
        $params = $imageRequest->getAllParams();

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

        $this->in = $imageRequest->getTmpDisk()->path($imageRequest->getInputPath());
        $this->out = $imageRequest->getTmpDisk()->path($imageRequest->getTransformPath());

        $this->makeDirectory($imageRequest);

        $this->copyToOut($imageRequest);

        $filetype = $imageRequest->getOutFileType();
        if ($filetype !== 'image/svg+xml') {
            $this->resize();
        }

        return true;
    }

    private function copyToOut(ImageRequest $imageRequest)
    {
        if (!$imageRequest->getTmpDisk()->exists($imageRequest->getInputPath())) {
            $this->downloader->download($imageRequest);
        }
        $imageRequest->getTmpDisk()->copy($imageRequest->getInputPath(), $imageRequest->getTransformPath());
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
    private function makeDirectory(ImageRequest $imageRequest) {
        $imageRequest->getTmpDisk()->makeDirectory(basename($imageRequest->getTransformPath()));
    }
}
