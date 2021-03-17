<?php

namespace App\Image;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class PathBuilder
 * Builds file paths based on image requests
 *
 * @package App\Image
 */
class PathBuilder {

    private $domain = null;
    private $url = null;
    private $foreignParameters = null;
    private $parameters = null;

    private static $imagelintParameters = ['imagelintwebp','imagelintavif','il-width','il-height','il-dpr','il-lossy'];

    public static function fromRequest($query, $parameters, $acceptsWebp = false, $acceptsAvif = false) {
        $expl = explode('/',$query);
        $domain = $expl[0];
        $url = $query;
        $foreignParameters = [];
        $imagelintParameters = [
            'imagelintwebp' => $acceptsWebp,
            'imagelintavif' => $acceptsAvif,
        ];
        if($parameters) {
            $foreignParameters = self::parseForeignParameters($parameters);
            $imagelintParameters = $imagelintParameters + self::parseImagelintParameters($parameters);
        }

        return new self($domain,$url,$foreignParameters,$imagelintParameters);
    }

    public function __construct($domain, $url, $foreignParameters = [], $parameters = []) {
        $this->domain = $domain;
        $this->url = $url;
        $this->foreignParameters = $foreignParameters;
        $this->parameters = $parameters;
    }

    public function getDomain() {
        return $this->domain;
    }

    public function getAllParams() {
        return $this->foreignParameters + $this->parameters;
    }

    public function getImagelintParameters() {
        return $this->parameters;
    }

    /**
     * Returns the second & first level part of the domain of a query
     * E.g.: example.com
     *
     * @return string
     * @throws \Exception
     */
    public function getSecondLevelDomain() {
        $domain = $this->getDomain();
        $expl = explode('.', $domain);
        if(count($expl) < 2) {
            throw new \Exception('Invalid Domain');
        }
        return $expl[count($expl) - 2] . '.' . $expl[count($expl) - 1];
    }

    /**
     * Creates our internal directory path for the file
     *
     * @return mixed
     */
    public function getBasePath() {
        $params = '';
        if ($this->foreignParameters) {
            $params = $this->stringifyParams($this->foreignParameters) . '/';
        }
        return $this->sanitize($params . $this->url);
    }

    public function getSpecificPath() {
        $params = '';
        if($this->getImagelintParameters()) {
            $params = $this->stringifyParams($this->getImagelintParameters()) . '/';
        }
        if ($this->foreignParameters) {
            $params .= $this->stringifyParams($this->foreignParameters) . '/';
        }
        return $this->sanitize($params . $this->url);
    }

    /**
     * Returns the path to the cached original image
     *
     * @return string
     */
    public function getCachePath() {
        $basePath = $this->getBasePath();
        if (substr($basePath,0,1) !== '/') {
            $basePath = '/' . $basePath;
        }
        return 'cache' . $basePath;
    }

    /**
     * Returns the path where we let the image binaries work on the images
     *
     * @return string
     */
    public function getCompressPath() {
        $path = $this->getSpecificPath();
        if (!Str::startsWith($path, '/')) {
            $path = '/' . $path;
        }
        return 'compress' . $path;
    }

    /**
     * Returns the path where we let the image binaries work on the images
     *
     * @return string
     */
    public function getTransformPath() {
        $basePath = $this->getBasePath();
        if (substr($basePath,0,1) !== '/') {
            $basePath = '/' . $basePath;
        }
        return 'transform' . $basePath;
    }

    public function getFinalPath() {
        $query = $this->url;
        if (substr($query, 0, 1) !== '/') {
            $query = '/' . $query;
        }
        $params = $this->foreignParameters + $this->parameters;
        $webp = Arr::get($this->parameters, 'imagelintwebp', false);
        if(isset($params['imagelintwebp'])) {
            unset($params['imagelintwebp']);
        }
        return 'data/' . $this->stringifyParams($params) . ($webp ? '/webp' : '') . $query;
    }

    public function getOutFileType() {
        // TODO: Figure out a way how to get the mime type from the file which might be in a remote storage
        return 'image/webp';
        $path = $this->getFinalPath();
        $imageInfo = getimagesize($path);
        if(!$imageInfo) {
            if(pathinfo($path)['extension'] === 'svg') {
                return 'image/svg+xml';
            } else {
                return 'image/webp';
            }
        } else {
            return $imageInfo['mime'];
        }
    }

    public function getInFileType() {
        $tmpStorage = Storage::disk(config('imagelint.tmp_disk', 'local'));
        $path = $tmpStorage->path($this->getTransformPath());
        $imageInfo = getimagesize($path);
        if(!$imageInfo) {
            if(pathinfo($path)['extension'] === 'svg') {
                return 'image/svg+xml';
            } else {
                return 'image/webp';
            }
        } else {
            return $imageInfo['mime'];
        }
    }

    public function getDownloadUrl() {
        if(!$this->foreignParameters) {
            return $this->url;
        } else {
            return $this->url . '?' . $this->stringifyParams($this->foreignParameters);
        }
    }

    public function stringifyParams($params) {
        $out = [];
        foreach($params as $k => $v) {
            $out[] = $k . '=' . $v;
        }

        return implode('&',$out);
    }

    private function sanitize($input) {
        return str_replace('../', '', $input);
    }

    public static function parseForeignParameters($parameters) {
        return array_filter($parameters, function($key) {
            return !in_array($key, self::$imagelintParameters);
        },ARRAY_FILTER_USE_KEY);
    }

    public static function parseImagelintParameters($parameters) {
        return array_filter($parameters,function ($key) {
            return in_array($key,self::$imagelintParameters);
        },ARRAY_FILTER_USE_KEY);
    }
}
