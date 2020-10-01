<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Image\Compressor;
use App\Image\Downloader;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;

class PreviewController extends Controller
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

    public function preview($quality, $image, Request $request) {
        $image = $this->downloader->buildRemoteURL($image);
        $in = tempnam(sys_get_temp_dir(),md5($image) . '-in');
        $out = tempnam(sys_get_temp_dir(),md5($image) . '-out');
        if(filter_var($image,FILTER_VALIDATE_URL) === false) {
            app()->abort(403, 'Invalid URL');
        }
        file_put_contents($in,fopen($image,'r'));

        $this->compressor->compressOnTheFly($in, $out, intval($quality));

        $size = getimagesize($in);

        $filesizeIn = filesize($in);
        $filesizeOut = filesize($out);

        $response = [
            'image' => 'data:image/webp;base64,' . base64_encode(file_get_contents($out)),
            'width' => $size[0],
            'height' => $size[1],
            'filesize_original' => $filesizeIn,
            'percentage' => round(100 - ($filesizeOut / $filesizeIn) * 100, 2)
        ];
        unlink($in);
        unlink($out);
        return Response::json($response);
    }
}
