<?php

namespace App\Jobs;

use App\Image\Compressor;
use App\Image\PathBuilder;
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
     * @var PathBuilder
     */
    private $path;
    /**
     * @var Original
     */
    private $original;

    /**
     * Create a new job instance.
     *
     * @param PathBuilder $path
     * @param Original $original
     */
    public function __construct(PathBuilder $path, Original $original)
    {
        $this->path = $path;
        $this->original = $original;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /** @var Compressor $compressor */
        $compressor = app(Compressor::class);
        $compressor->compress($this->path, $this->original);
    }
}
