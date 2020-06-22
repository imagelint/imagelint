<?php

namespace App\Comparator;

use Exception;
use phpUri;
use voku\helper\HtmlDomParser;

class ImageExtractor {
    const CSSURLREGEX = "|url\\(['\"]*(.*?)['\"]*\\)|i";

    public function extract($url) {
        if(!$this->isValidUrl($url)) {
            return false;
        }
        $html = file_get_contents($url);
        $images = $this->getImageURLs($html);

        $base = strtok($url,'?');
        $images = array_map(function ($image) use ($base) {
            return phpUri::parse($base)->join($image);
        },$images);

        return $images;
    }

    private function getImageURLs($html) {
        $images = [];
        try {
            $html = HtmlDomParser::str_get_html($html);
        } catch(Exception $e) {
            return [];
        }
        foreach($html->find('img') as $element) {
            $src = $element->src;
            if($this->hasValidExtension($src)) {
                $images[] = $src;
            }
        }
        foreach($html->find('*[style]') as $element) {
            $element->style = preg_replace_callback(self::CSSURLREGEX,function ($matches) {
                $src = $matches[1];
                if($this->hasValidExtension($src)) {
                    $images[] = $src;
                }
            },$element->style);
        }
        return array_values(array_unique($images));
    }


    private function isValidUrl($url) {
        return filter_var($url,FILTER_VALIDATE_URL) !== false;
    }

    private function hasValidExtension($url) {
        $validExtensions = ['jpg', 'jpeg', 'png', 'svg'];
        return in_array(strtolower(pathinfo(parse_url($url,PHP_URL_PATH),PATHINFO_EXTENSION)), $validExtensions);
    }
}
