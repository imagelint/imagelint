<?php

namespace App\Image;

use App\Access;
use App\Original;

class AccessTracker {
    
    // Mysql could handle about 8mb (per default) of import statements. One import has about 300 bytes. We only do 2000 (instead of the theoretical 28000) at once because that's a lot faster in the performance test.
    private static $maxFlushCount = 2000;
    private $entries = [];
    private $originals = [];

    /**
     * Tracks an entry from the nginx access log
     * 
     * @param $entry
     *
     * @return bool
     */
    public function track($entry) {
        $data = $this->parse($entry);
        if(!$data) {
            return false;
        }
        $this->entries[] = $this->toAccessEntry($data);

        if(count($this->entries) > self::$maxFlushCount) {
            $this->flush();
        }
        
        return true;
    }

    /**
     * Extracts interesting information from an nginx log entry
     * 
     * @param $entry
     *
     * @return array|bool
     * @throws \Exception
     */
    private function parse($entry) {
        // Check if it's a GET
        if(substr($entry, 0, 4) !== '"GET') {
            return false;
        }
        $httpPart = strpos($entry, ' HTTP/1.1"');
        if($httpPart === false) {
            $httpPart = strpos($entry,' HTTP/2.0"');
        }
        if($httpPart === false) {
            $httpPart = strpos($entry,' HTTP/1.0"');
        }
        if($httpPart === false) {
            throw new \Exception('Invalid HTTP Method in imagelog detected');
        }
        $request = substr($entry, 5, $httpPart - 5);
        if(strlen($request) < 5 || starts_with($request, '/.well-known') || !str_contains(explode('/',$request)[1], '.')) {
            return false;
        }
        $response = substr($entry, $httpPart + 11, 3);
        if($response !== '200') {
            return false;
        }
        
        $size = intval(trim(substr($entry, $httpPart + 15)));
        
        $modifiers = trim(substr($entry, $httpPart + 15 + 1 + strlen('' . $size)));
        return [
            'path' => $request,
            'size' => $size,
            'modifier' => $modifiers
        ];
    }

    /**
     * Converts the nginx log entry data into an array which can be inserted into the accesses table
     * 
     * @param $entry
     *
     * @return mixed
     */
    private function toAccessEntry($entry) {
        $path = PathBuilder::fromNginxLog($entry['path'], $entry['modifier']);
        $entry['user_id'] = 1;
        $entry['created_at'] = date("Y-m-d H:i:s");
        $entry['original_id'] = $this->getOriginalId($path);
        $entry['modifier'] = $path->stringifyParams($path->getImagelintParameters());
        return $entry;
    }

    /**
     * Returns the id of the original image entry
     * 
     * @param PathBuilder $path
     *
     * @return mixed
     * @throws \Exception
     */
    private function getOriginalId(PathBuilder $path) {
        $originalPath = $path->getOriginalStoragePath();
        if(!isset($this->originals[$originalPath])) {
            $this->originals[$originalPath] = Original::where('path', $originalPath)->select('id')->pluck('id')->first();
            if(!$this->originals[$originalPath]) {
                throw new \Exception('Could not find original: ' . $originalPath);
            }
        }
        return $this->originals[$originalPath];
    }

    /**
     * Writes the data to the database
     */
    public function flush() {
        Access::insert($this->entries);
        $this->entries = [];
    }
}