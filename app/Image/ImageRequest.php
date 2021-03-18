<?php

namespace App\Image;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The ImageRequest class represents a request to transform a given input image
 */
class ImageRequest {

    private $domain;
    private $url;
    private $foreignParameters;
    private $internalParameters;

    private static $imagelintParameters = [
        'imagelintwebp',
        'imagelintavif',
        'il-width',
        'il-height',
        'il-dpr',
        'il-lossy',
    ];

    /**
     * @param $query
     * @param $allParameters
     * @param false $acceptsWebp
     * @param false $acceptsAvif
     * @return ImageRequest
     */
    public static function fromRequest($query, $allParameters, $acceptsWebp = false, $acceptsAvif = false) {
        $expl = explode('/',$query);
        $domain = $expl[0];
        $url = $query;
        $foreignParameters = [];
        $imagelintParameters = [
            'imagelintwebp' => $acceptsWebp,
            'imagelintavif' => $acceptsAvif,
        ];
        if(count($allParameters) > 0) {
            $foreignParameters = self::parseForeignParameters($allParameters);
            $imagelintParameters = $imagelintParameters + self::parseImagelintParameters($allParameters);
        }

        return new self($domain,$url,$foreignParameters,$imagelintParameters);
    }

    public function __construct($domain, $url, $foreignParameters = [], $parameters = []) {
        $this->domain = $domain;
        $this->url = $url;
        $this->foreignParameters = $foreignParameters;
        $this->internalParameters = $parameters;
    }

    public function getDomain() {
        return $this->domain;
    }

    public function getAllParams() {
        return $this->foreignParameters + $this->internalParameters;
    }

    public function getImagelintParameters() {
        return $this->internalParameters;
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
     * Get the local disk where we work on the image
     *
     * @return Filesystem
     */
    public function getTmpDisk(): Filesystem
    {
        return Storage::disk(config('imagelint.tmp_disk', 'local'));
    }

    /**
     * Get the disk where we store the final image
     *
     * @return Filesystem
     */
    public function getOutputDisk(): Filesystem
    {
        return Storage::disk(config('imagelint.output_cache_disk', 'local'));
    }

    /**
     * Creates our internal directory path for the file which only represents the request image
     * but ignores the internal parameters
     *
     * @return string
     */
    public function getBasePath() {
        $params = '';
        if ($this->foreignParameters) {
            $params = $this->stringifyParams($this->foreignParameters) . '/';
        }
        return $this->sanitize($params . $this->url);
    }

    /**
     * Creates our internal directory path for the file which includes our internal imagelint parameters
     * @return string|string[]
     */
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
    public function getInputPath() {
        $basePath = $this->getBasePath();
        if (substr($basePath,0,1) !== '/') {
            $basePath = '/' . $basePath;
        }
        return 'input' . $basePath;
    }

    /**
     * Returns the path where we let the transformers work on the image
     *
     * @return string
     */
    public function getTransformPath() {
        $basePath = $this->getSpecificPath();
        if (substr($basePath,0,1) !== '/') {
            $basePath = '/' . $basePath;
        }
        return 'transform' . $basePath;
    }

    /**
     * Returns the path where we let the compression binaries work on the image
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
     * Returns the path where we store the final image
     *
     * @return string
     */
    public function getOutputPath() {
        $path = $this->getSpecificPath();
        if (!Str::startsWith($path, '/')) {
            $path = '/' . $path;
        }
        return 'output' . $path;
    }

    /**
     * Returns the file type of the output image
     *
     * @return string
     */
    public function getOutFileType() : string {
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

    /**
     * Returns the file type of the original image
     *
     * @return string
     */
    public function getInFileType() : string {
        $path = $this->getTmpDisk()->path($this->getInputPath());
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

    /**
     * Returns the URL where we get the original image file from
     *
     * @return string
     */
    public function getDownloadUrl() : string {
        if(!$this->foreignParameters) {
            return $this->url;
        } else {
            return $this->url . '?' . $this->stringifyParams($this->foreignParameters);
        }
    }

    /**
     * @param array $params
     * @return string
     */
    public function stringifyParams(array $params) : string {
        $out = [];
        foreach($params as $k => $v) {
            $out[] = $k . '=' . $v;
        }

        return implode('&',$out);
    }

    /**
     * Makes sure the input path is safe to handle
     *
     * @param string $input
     * @return string
     */
    private function sanitize(string $input) : string {
        return str_replace('../', '', $input);
    }

    /**
     * Extracts foreign parameters (the ones, that are not imagelint internal parameters)
     *
     * @param array $parameters
     * @return array
     */
    public static function parseForeignParameters(array $parameters) : array {
        return array_filter($parameters, function($key) {
            return !in_array($key, self::$imagelintParameters);
        },ARRAY_FILTER_USE_KEY);
    }

    /**
     * Extracts internal parameters (the ones, that we use in imagelint to specify width, quality, etc)
     *
     * @param array $parameters
     * @return array
     */
    public static function parseImagelintParameters(array $parameters) : array {
        return array_filter($parameters,function ($key) {
            return in_array($key,self::$imagelintParameters);
        },ARRAY_FILTER_USE_KEY);
    }
}
