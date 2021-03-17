<?php

namespace App\Http\Controllers\Images;

use App\Http\Controllers\Controller;
use App\Image\Transformer;
use App\Jobs\CompressImage;
use App\Models\Domain;
use App\Image\Compressor;
use App\Image\Downloader;
use App\Image\PathBuilder;
use App\Models\Original;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class ConvertController extends Controller
{
    /**
     * @var Downloader
     */
    private $downloader;
    /**
     * @var Compressor
     */
    private $compressor;
    /**
     * @var Transformer
     */
    private $transformer;

    public function __construct(Downloader $downloader, Compressor $compressor, Transformer $transformer)
    {
        $this->downloader = $downloader;
        $this->compressor = $compressor;
        $this->transformer = $transformer;
    }

    public function image($query)
    {
        if ($query == '/') {
            return app()->abort(404);
        }
        try {
            $path = PathBuilder::fromRequest($query, Request::all(), Request::accepts('image/webp'), Request::accepts('image/avif'));
        } catch (\Exception $e) {
            report($e);
            return app()->abort(400);
        }

        $domain = $path->getSecondLevelDomain();
        $domain = Domain::where('domain', $domain)->first();

        if (!$domain) {
            return app()->abort(403);
        }

        $basePath = $path->getBasePath();

        $original = Original::where('path', $basePath)->first();
        if (!$original) {
            $originalSize = $this->downloader->download($path);
            $original = new Original();
            $original->path = $basePath;
            $original->domain_id = $domain->id;
            $original->size = $originalSize;
            $original->user_id = 1;
            $original->save();
        } else {
            if (!Storage::disk(config('imagelint.cache_disk', 'local'))->exists($path->getCachePath())) {
                $this->downloader->download($path);
            }
        }
        $compressedStorage = Storage::disk(config('imagelint.compressed_disk', 'local'));
        $isCompressed = $compressedStorage->exists($path->getCompressPath());
        if (!$isCompressed) {
            $this->transformer->transform($path, $original);
            CompressImage::dispatchAfterResponse($path, $original);
            $tmpStorage = Storage::disk(config('imagelint.tmp_disk', 'local'));
            $readStream = $tmpStorage->readStream($path->getTransformPath());
        } else {
            $readStream = $compressedStorage->readStream($path->getCompressPath());
        }

        // Finish any potential previous output. There should be none, but if there is, we need this in order for the streaming to work
        if (ob_get_level()) {
            ob_end_clean();
        }

        return response()->stream(
            function () use ($readStream) {
                fpassthru($readStream);
            },
            200,
            [
                'Content-Type' => $path->getOutFileType(),
                'Cache-Control' => $isCompressed ? 'max-age=3600, public' : 'private',
            ]
        );
    }
}
