<?php

namespace Tests\Feature;

use App\Image\ImageRequest;
use App\Image\Transformer;
use App\Models\Domain;
use App\Models\Original;
use Clockwork\Storage\Storage;
use Tests\TestCase;

class TransformerTest extends TestCase
{
    public function testWidthOnlyTransformWorks()
    {
        list($imageRequest) = $this->generateImageTransform(['il-width' => 500]);

        $this->assertTrue($imageRequest->getTmpDisk()->exists($imageRequest->getTransformPath()));
        list($width) = getimagesize($imageRequest->getTmpDisk()->path($imageRequest->getTransformPath()));
        $this->assertEquals(500, $width);
    }

    public function testHeightOnlyTransformWorks()
    {
        list($imageRequest) = $this->generateImageTransform(['il-height' => 100]);

        $this->assertTrue($imageRequest->getTmpDisk()->exists($imageRequest->getTransformPath()));
        list($width, $height) = getimagesize($imageRequest->getTmpDisk()->path($imageRequest->getTransformPath()));
        $this->assertEquals(100, $height);
    }

    public function testHeightAndWidthTransformWorks()
    {
        list($imageRequest) = $this->generateImageTransform(['il-height' => 100, 'il-width' => 100]);

        $this->assertTrue($imageRequest->getTmpDisk()->exists($imageRequest->getTransformPath()));
        list($width, $height) = getimagesize($imageRequest->getTmpDisk()->path($imageRequest->getTransformPath()));
        $this->assertEquals(100, $width);
        $this->assertEquals(100, $height);
    }

    public function testItDoesntUpsize()
    {
        list($imageRequest) = $this->generateImageTransform(['il-height' => 1000]);

        $this->assertTrue($imageRequest->getTmpDisk()->exists($imageRequest->getTransformPath()));
        list($width, $height) = getimagesize($imageRequest->getTmpDisk()->path($imageRequest->getTransformPath()));
        $this->assertNotEquals(1000, $height);
    }

    private function generateImageTransform($params) {
        $transformer = app(Transformer::class);
        $domain = 'sopamo.de';
        $imageRequest = new ImageRequest($domain, 'sopamo.de/assets/logo-07e9c912ae13424d2dd99b60b9fe70bf.png', [], $params);
        $original = new Original();
        $transformer->transform($imageRequest, $original);
        return [
            $imageRequest,
        ];
    }
}
