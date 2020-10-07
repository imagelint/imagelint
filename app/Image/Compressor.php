<?php

namespace App\Image;

use App\Models\Original;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class Compressor
{

    private $modifiers;
    private $in;
    private $out;
    private $original;
    private $quality;
    private $originalSize;

    public function compress(PathBuilder $path, Original $original)
    {
        $this->original = $original;
        $params = $path->getAllParams();

        $modifiers = [
            'width' => Arr::get($params, 'il-width', null),
            'height' => Arr::get($params, 'il-height', null),
            'dpr' => Arr::get($params, 'il-dpr', 1),
            'lossy' => Arr::get($params, 'il-lossy', false),
            'webp' => Arr::get($params, 'imagelintwebp', false) === 'true',
            'avif' => Arr::get($params, 'il-avif', false),
        ];

        $this->modifiers = $modifiers;
        if ($quality = Arr::get($params, 'il-quality', null)) {
            $this->quality = (int)$quality;
        }
        $this->in = $path->getCachePath();
        $this->out = $path->getFinalPath();


        if (File::exists($this->out)) {
            return;
        }

        if (!File::exists(dirname($this->out))) {
            @File::makeDirectory(dirname($this->out), 0755, true);
        }

        $this->copyToOut();

        $filetype = $path->getOutFileType();
        if ($filetype !== 'image/svg+xml') {
            $this->resize();
        }
        if ((bool)$this->modifiers['avif'] === true) {
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

        return true;
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
        $this->copyToOut();
        $this->compressWebp();
    }

    private function copyToOut()
    {
        File::copy($this->in, $this->out);
        $this->originalSize = filesize($this->out);
        // Filesize is cached, so make sure to clean up the cache
        clearstatcache();
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

        // TODO: Handle cropping & resizing via the image binaries
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
            $this->restoreInToOut();
        }
    }

    public function compressPng()
    {
        exec(base_path('bin/pngcrush') . ' -blacken -bail -rem alla -reduce -ow ' . escapeshellarg($this->out));
        exec(base_path('bin/zopflipng') . ' --lossy_transparent -y ' . escapeshellarg($this->out) . ' ' . escapeshellarg($this->out));
        if ($this->originalSize <= filesize($this->out)) {
            $this->restoreInToOut();
        }
    }

    public function compressSVG()
    {
        exec('svgo --multipass ' . escapeshellarg($this->out));
        if ($this->originalSize <= filesize($this->out)) {
            $this->restoreInToOut();
        }
    }

    public function compressJpg()
    {
        exec(base_path('bin/jpegoptim') . ' -s --all-normal -m ' . $this->getQuality() . ' ' . escapeshellarg($this->out) . ' --dest=' . escapeshellarg(dirname($this->out)));
        if ($this->originalSize <= filesize($this->out)) {
            $this->restoreInToOut();
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
        File::move($dest, $this->out);
        if ($this->originalSize <= filesize($this->out)) {
            $this->restoreInToOut();
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

    private function restoreInToOut()
    {
        unlink($this->out);
        $this->copyToOut();
        $this->resize();
    }
}
