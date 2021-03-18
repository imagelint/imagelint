<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanLocalFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localfolders:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes all cache and tmp data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cacheStorage = Storage::disk(config('imagelint.cache_disk', 'local'));
        $tmpStorage = Storage::disk(config('imagelint.tmp_disk', 'local'));
        $compressedStorage = Storage::disk(config('imagelint.compressed_disk', 'local'));

        $cacheStorage->deleteDirectory('cache');
        $tmpStorage->deleteDirectory('transform');
        $compressedStorage->deleteDirectory('compress');
        return 0;
    }
}
