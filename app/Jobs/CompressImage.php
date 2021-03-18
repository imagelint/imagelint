<?php

namespace App\Jobs;

use App\Image\Compressor;
use App\Image\ImageRequest;
use App\Models\Original;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompressImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var ImageRequest
     */
    private $imageRequest;
    /**
     * @var Original
     */
    private $original;

    /**
     * Create a new job instance.
     *
     * @param ImageRequest $imageRequest
     * @param Original $original
     */
    public function __construct(ImageRequest $imageRequest, Original $original)
    {
        $this->imageRequest = $imageRequest;
        $this->original = $original;
    }

    /**
     * Execute the job.
     *
     * @param Compressor $compressor
     * @return void
     */
    public function handle(Compressor $compressor)
    {
        $compressor->compress($this->imageRequest, $this->original);
    }
}
