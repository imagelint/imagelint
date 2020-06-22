<?php
namespace App\Http\Controllers\Images;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Image\Compressor;
use App\Image\Downloader;
use App\Image\PathBuilder;
use App\Models\Original;
use Error;
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

    public function __construct(Downloader $downloader, Compressor $compressor) {
        $this->downloader = $downloader;
        $this->compressor = $compressor;
    }

    public function image($query) {
        if($query == '/') {
            return app()->abort(404);
        }
        try {
            $path = PathBuilder::fromRequest($query, Request::all());
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

        $this->compressor->compress($path, $original);

        header("Content-type: {$path->getOutFileType()}");
        readfile($path->getFinalPath());
        exit;
    }
}
