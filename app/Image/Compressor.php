<?php

namespace App\Image;

use App\Models\Original;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Compressor
{
    private $modifiers;
    private $in;
    private $out;
    private $original;
    private $imageRequest;
    private $quality;
    private $originalSize;

    public function compress(ImageRequest $imageRequest, Original $original)
    {
        $this->original = $original;
        $this->imageRequest = $imageRequest;
        $params = $this->imageRequest->getAllParams();

        $modifiers = [
            'width' => Arr::get($params, 'il-width', null),
            'height' => Arr::get($params, 'il-height', null),
            'dpr' => Arr::get($params, 'il-dpr', 1),
            'lossy' => Arr::get($params, 'il-lossy', false),
            'webp' => Arr::get($params, 'imagelintwebp', false),
            'avif' => Arr::get($params, 'il-avif', false) && Arr::get($params, 'imagelintavif', false),
        ];

        $this->modifiers = $modifiers;
        if ($quality = Arr::get($params, 'il-quality', null)) {
            $this->quality = (int)$quality;
        }

        $this->in = $this->imageRequest->getTmpDisk()->path($this->imageRequest->getTransformPath());
        $this->out = $this->imageRequest->getTmpDisk()->path($this->imageRequest->getCompressPath());

        $this->makeDirectory($this->out);

        $this->copyToOut();

        $filetype = $this->imageRequest->getInFileType();
        if ((bool)$this->modifiers['avif'] === true && $filetype !== 'image/svg+xml') {
            $this->compressAvif();
        } elseif ($this->modifiers['webp'] === true && $filetype !== 'image/svg+xml') {
            $this->compressWebp();
        } else {
            switch ($filetype) {
                case 'image/jpeg':
                    $this->compressJpg();
                    break;
                case 'image/png':
                    $this->compressPng();
                    break;
                case 'image/svg+xml':
                    $this->compressSVG();
                    break;
            }
        }

        $compressedStream = $this->imageRequest->getTmpDisk()->readStream($this->imageRequest->getCompressPath());
        return $this->imageRequest->getOutputDisk()->put($this->imageRequest->getOutputPath(), $compressedStream);
    }

    public function compressOnTheFly($in, $out, $quality)
    {
        $this->in = $in;
        $this->out = $out;
        $this->quality = $quality;
        $modifiers = [
            'dpr' => 1,
            'lossy' => false,
            'webp' => true,
        ];
        $this->modifiers = $modifiers;
        $this->compressWebp();
    }

    private function copyToOut()
    {
        $this->imageRequest->getTmpDisk()->copy($this->imageRequest->getTransformPath(), $this->imageRequest->getCompressPath());
        $this->originalSize = filesize($this->out);
        // Filesize is cached, so make sure to clean up the cache
        clearstatcache(true, $this->out);
    }

    public function compressWebp()
    {
        $size = getimagesize($this->in);
        $filetype = $size['mime'];
        if ($filetype === 'image/png' && !$this->modifiers['lossy']) {
            $params = '-near_lossless ' . (100 - $this->getQuality());
        } else {
            $params = '-q ' . $this->getQuality();
        }
        $params .= ' -m 6 -af -mt';
        $command = base_path('bin/cwebp')
            . ' ' .
            escapeshellarg($this->out)
            . ' ' . $params . ' -o ' .
            escapeshellarg($this->out);
        $output = null;
        exec($command, $output);

        if ($this->originalSize <= filesize($this->out)) {
            $this->copyToOut();
        }
    }

    public function compressPng()
    {
        exec(base_path('bin/pngcrush') . ' -blacken -bail -rem alla -reduce -ow ' . escapeshellarg($this->out));
        exec(base_path('bin/zopflipng') . ' --lossy_transparent -y ' . escapeshellarg($this->out) . ' ' . escapeshellarg($this->out));
        if ($this->originalSize <= filesize($this->out)) {
            $this->copyToOut();
        }
    }

    public function compressSVG()
    {
        exec('svgo --multipass ' . escapeshellarg($this->out));
        if ($this->originalSize <= filesize($this->out)) {
            $this->copyToOut();
        }
    }

    public function compressJpg()
    {
        exec(base_path('bin/jpegoptim') . ' -s -o --all-normal -m' . $this->getQuality() . ' ' . escapeshellarg($this->out) . ' --dest=' . escapeshellarg(dirname($this->out)));
        if ($this->originalSize <= filesize($this->out)) {
            $this->copyToOut();
        }
    }

    public function compressAvif()
    {
        // Compression level (0..63), [default: 25]
        $quality = 63 - floor((int)$this->getQuality() / 100 * 63);
        if($quality<0) {
            $quality = 0;
        }
        if($quality>63) {
            $quality = 63;
        }
        $dest = str_replace('.'.File::extension($this->out), '.avif', $this->out);
        exec(base_path('bin/avif') . ' -e ' . escapeshellarg($this->out) . ' -o ' . escapeshellarg($dest) . ' -q ' . $quality);

        if ($this->originalSize <= filesize($dest)) {
            $this->copyToOut();
        } else {
            File::move($dest, $this->out);
        }
    }

    private function getQuality()
    {
        if ($this->quality) {
            return $this->quality;
        } else {
            $quality = $this->original->quality;
            if (!$quality) {
                $quality = 85;
            }
            return $quality;
        }
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
