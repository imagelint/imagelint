<?php

namespace Tests\Feature;

use App\Image\Compressor;
use App\Image\ImageRequest;
use App\Image\Transformer;
use App\Models\Domain;
use App\Models\Original;
use Clockwork\Storage\Storage;
use Tests\TestCase;

class CompressorTest extends TestCase
{
    public function testPngCompressionWorks()
    {
        list($imageRequest) = $this->generateImageCompression(['imagelintwebp' => false]);

        $this->assertTrue($imageRequest->getOutputDisk()->exists($imageRequest->getOutputPath()));
        $inputSize = $imageRequest->getTmpDisk()->size($imageRequest->getInputPath());
        $outputSize = $imageRequest->getOutputDisk()->size($imageRequest->getOutputPath());

        $this->assertTrue($inputSize > $outputSize, 'The output image isnt smaller than the input');

        $mimeType = mime_content_type($imageRequest->getOutputDisk()->path($imageRequest->getOutputPath()));

        $this->assertEquals('image/png', $mimeType);
    }

    public function testWebpCompressionWorks()
    {
        list($imageRequest) = $this->generateImageCompression(['imagelintwebp' => true]);

        $this->assertTrue($imageRequest->getOutputDisk()->exists($imageRequest->getOutputPath()));
        $inputSize = $imageRequest->getTmpDisk()->size($imageRequest->getInputPath());
        $outputSize = $imageRequest->getOutputDisk()->size($imageRequest->getOutputPath());

        $this->assertTrue($inputSize > $outputSize, 'The output image isnt smaller than the input');

        $mimeType = mime_content_type($imageRequest->getOutputDisk()->path($imageRequest->getOutputPath()));

        $this->assertEquals('image/webp', $mimeType);
    }

    private function generateImageCompression($params = []) {
        $compressor = app(Compressor::class);
        $domain = 'sopamo.de';
        $imageRequest = new ImageRequest($domain, 'sopamo.de/assets/logo-07e9c912ae13424d2dd99b60b9fe70bf.png', [], $params);
        $original = new Original();
        $compressor->compress($imageRequest, $original);
        return [
            $imageRequest,
        ];
    }
}
