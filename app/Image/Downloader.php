<?php

namespace App\Image;
use Illuminate\Support\Facades\File;
use League\Flysystem\FileExistsException;

/**
 * Class Downloader
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
        
        $this->makeDirectory($cachePath);
        $remote = $this->buildRemoteURL($path->getDownloadUrl());
        
        file_put_contents($cachePath, fopen($remote,'r'));
        
        $size = filesize($cachePath);

        return $size;
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
            } catch(FileExistsException $e) {
                // Due to multiple requests at once it might happen that the directoy already exists
            }
        }
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