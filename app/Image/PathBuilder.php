<?php

namespace App\Image;
use Illuminate\Support\Arr;

/**
 * Class PathBuilder
 * Builds paths based on image requests
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

    public function getOriginalStoragePath() {
        if($this->foreignParameters) {
            return $this->stringifyParams($this->foreignParameters) . '/' . $this->url;
        }
        return $this->url;
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
        if ($this->foreignParameters) {
            $params = $this->stringifyParams($this->foreignParameters) . '/';
        } else {
            $params = '';
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
        return storage_path('app/public/cache' . $basePath);
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
        return storage_path('app/public/data/' . $this->stringifyParams($params) . ($webp ? '/webp' : '') . $query);
    }

    public function getOutFileType() {
        $path = $this->getFinalPath();
        $is = getimagesize($path);
        if(!$is) {
            if(pathinfo($path)['extension'] === 'svg') {
                return 'image/svg+xml';
            } else {
                return 'image/webp';
            }
        } else {
            return $is['mime'];
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
