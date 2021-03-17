<?php

namespace App\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileExistsException;

/**
 * Downloads the original image
 *
 * @package App\Image
 */
class Downloader
{

    /**
     * Downloads the file and returns the storage path and size
     *
     * @param $path
     *
     * @return int The size of the original file
     */
    public function download(PathBuilder $path) {
        $cachePath = $path->getCachePath();

        $remote = $this->buildRemoteURL($path->getDownloadUrl());

        $cacheStorage = Storage::disk(config('imagelint.cache_disk', 'local'));

        $cacheStorage->put($cachePath, fopen($remote,'r'));

        $size = $cacheStorage->size($cachePath);

        return $size;
    }

    public function buildRemoteURL($url) {
        // Try to request the file via https, if that doesn't work use http
        try {
            $client = new \GuzzleHttp\Client();
            $client->request('HEAD','https://' . $url);
            $url = 'https://' . $url;
        } catch(\Exception $e) {
            $url = 'http://' . $url;
        }
        return $url;
    }
}
