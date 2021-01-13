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

    public function __construct(Downloader $downloader, Compressor $compressor, Transformer $transformer) {
        $this->downloader = $downloader;
        $this->compressor = $compressor;
        $this->transformer = $transformer;
    }

    public function image($query) {
        if($query == '/') {
            return app()->abort(404);
        }
        try {
            $path = PathBuilder::fromRequest($query, Request::all(), Request::accepts('image/webp'), Request::accepts('image/avif'));
        } catch(\Exception $e) {
            report($e);
            return app()->abort(400);
        }

        $domain = $path->getSecondLevelDomain();
        $domain = Domain::where('domain', $domain)->first();

        if(!$domain) {
            return app()->abort(403);
        }

        $basePath = $path->getBasePath();

        $original = Original::where('path', $basePath)->first();
        if(!$original) {
            $originalSize = $this->downloader->download($path);
            $original = new Original();
            $original->path = $basePath;
            $original->domain_id = $domain->id;
            $original->size = $originalSize;
            $original->user_id = 1;
            $original->save();
        } else {
            if(!file_exists($path->getCachePath())) {
                $this->downloader->download($path);
            }
        }
        if(!file_exists($path->getFinalPath())) {
            $this->transformer->transform($path, $original);
            CompressImage::dispatchAfterResponse($path, $original);
        }

        header("Content-type: {$path->getOutFileType()}");
        readfile($path->getFinalPath());
    }
}
