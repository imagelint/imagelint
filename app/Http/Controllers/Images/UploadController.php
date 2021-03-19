<?php

namespace App\Http\Controllers\Images;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUploadedImages;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Uploads the file to the configured storage disk
     *
     * @param StoreUploadedImages $request
     * @return \Illuminate\Http\Response
     */
    public function upload(StoreUploadedImages $request)
    {
        $file = $request->file('file');

        $diskName = config('imagelint.upload_disk', 'public');
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $hashName = $file->hashName();
        $folder = 'images' . DIRECTORY_SEPARATOR . substr($hashName, 0, 2);
        $filename .= $filename . '-' . $hashName;

        $path = $request->file->storeAs($folder, $filename, $diskName);

        return Response::make(Storage::disk($diskName)->url($path));
    }
}
