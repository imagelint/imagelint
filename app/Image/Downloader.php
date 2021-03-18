<?php

namespace App\Image;
use GuzzleHttp\Client;

/**
 * Downloads the original image
 *
 * @package App\Image
 */
class Downloader
{

    /**
     * Downloads the file and returns it's size
     *
     * @param ImageRequest $imageRequest
     *
     * @return int The size of the original file
     */
    public function download(ImageRequest $imageRequest) : int {
        $cachePath = $imageRequest->getInputPath();

        $remote = $this->buildRemoteURL($imageRequest->getDownloadUrl());

        $imageRequest->getTmpDisk()->put($cachePath, fopen($remote,'r'));

        return $imageRequest->getTmpDisk()->size($cachePath);
    }

    public function buildRemoteURL($url) {
        // Try to request the file via https, if that doesn't work use http
        try {
            $client = new Client();
            $client->request('HEAD','https://' . $url);
            $url = 'https://' . $url;
        } catch(\Exception $e) {
            $url = 'http://' . $url;
        }
        return $url;
    }
}
