<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BenchmarkBinaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benchmark:binaries {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It tests binaries to compress images';

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
        $headers = ['Name', 'JPG - time (ms)', 'JPG - memory (MB)', 'WEBP - time (ms)', 'WEBP - memory (MB)', 'AVIF - time (ms)', 'AVIF - memory (MB)', 'width*height (px)'];
        $results = [];
        $path = $this->argument('path');
        $this->info('Path dir: ' . $path);
        @mkdir($path . '/output');
        $files = File::files($path);
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();
        foreach ($files as $file) {
            list($width, $height, $type, $attr) = getimagesize($file->getPathname());
            $dest = $path . '/output/' . $file->getRelativePathname();

            $jpgStartTime = microtime(true);
            $jpgMemory = $this->compressJpg($file->getPathname(), $dest);
            $jpgEndTime = microtime(true);

            $webpStartTime = microtime(true);
            $webpMemory = $this->compressWebp($file->getPathname(), $dest);
            $webpEndTime = microtime(true);

            $avifStartTime = microtime(true);
            $avifMemory = $this->compressAvif($file->getPathname(), $dest);
            $avifEndTime = microtime(true);

            $results[] = [$file->getRelativePathname(), ($jpgEndTime - $jpgStartTime) * 1000, $jpgMemory/1024, ($webpEndTime - $webpStartTime) * 1000, $webpMemory/1024, ($avifEndTime - $avifStartTime) * 1000, $avifMemory/1024, $width*$height];

            $bar->advance();
        }
        $bar->finish();
        $this->table($headers, $results);
        return 0;
    }

    public function compressWebp($file, $destination)
    {
        $dest = str_replace('.' . File::extension($destination), '.webp', $destination);
        $params = ' -m 6 -af -mt';
        $command = 'time -o compress-webp.txt -f "%M" bin/cwebp ' . escapeshellarg($file)
            . ' ' . $params . ' -o ' .
            escapeshellarg($dest);
        $output=null;
        exec($command, $output);
        $file = File::get('compress-webp.txt');
        File::delete('compress-webp.txt');
        return (int)$file;
    }

    public function compressJpg($file, $destination)
    {
        exec('time -o compress-jpg.txt -f "%M" bin/jpegoptim -s --all-normal ' . escapeshellarg($file) . ' --dest=' . escapeshellarg(dirname($destination)));
        $file = File::get('compress-jpg.txt');
        File::delete('compress-jpg.txt');
        return (int)$file;
    }

    public function compressAvif($file, $destination)
    {
        $dest = str_replace('.' . File::extension($destination), '.avif', $destination);
        exec('time  -o compress-avif.txt -f "%M" bin/avif -e ' . escapeshellarg($file) . ' -o ' . escapeshellarg($dest));
        $file = File::get('compress-avif.txt');
        File::delete('compress-avif.txt');
        return (int)$file;
    }
}
