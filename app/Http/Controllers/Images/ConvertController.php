<?php

namespace App\Http\Controllers\Images;

use App\Http\Controllers\Controller;
use App\Image\Transformer;
use App\Jobs\CompressImage;
use App\Models\Domain;
use App\Image\Compressor;
use App\Image\Downloader;
use App\Image\ImageRequest;
use App\Models\Original;
use Illuminate\Support\Facades\Request;

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
            $imageRequest = ImageRequest::fromRequest($query, Request::all(), Request::accepts('image/webp'), Request::accepts('image/avif'));
        } catch (\Exception $e) {
            report($e);
            return app()->abort(400);
        }

        $domain = $imageRequest->getSecondLevelDomain();
        $domain = Domain::where('domain', $domain)->first();

        if (!$domain) {
            return app()->abort(403);
        }

        $basePath = $imageRequest->getBasePath();

        $original = Original::where('path', $basePath)->first();
        if (!$original) {
            $originalSize = $this->downloader->download($imageRequest);
            $original = new Original();
            $original->path = $basePath;
            $original->domain_id = $domain->id;
            $original->size = $originalSize;
            $original->user_id = 1;
            $original->save();
        }

        $isAlreadyCompressed = $imageRequest->getOutputDisk()->exists($imageRequest->getOutputPath());
        if (!$isAlreadyCompressed) {
            if (!$imageRequest->getTmpDisk()->exists($imageRequest->getInputPath())) {
                $this->downloader->download($imageRequest);
            }
            $this->transformer->transform($imageRequest, $original);

            CompressImage::dispatchAfterResponse($imageRequest, $original);
            $readStream = $imageRequest->getTmpDisk()->readStream($imageRequest->getTransformPath());
        } else {
            $readStream = $imageRequest->getOutputDisk()->readStream($imageRequest->getOutputPath());
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
                'Content-Type' => $imageRequest->getOutFileType(),
                'Cache-Control' => $isAlreadyCompressed ? 'max-age=3600, public' : 'private',
            ]
        );
    }
}
