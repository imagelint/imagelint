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
        $headers = ['Name', 'JPG - time (ms)', 'JPG - memory'];
        $results = [];
        $path = $this->argument('path');
        $this->info('Path dir: ' . $path);
        @mkdir($path . '/output');
        $files = File::files($path);
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();
        foreach ($files as $file) {
            $dest = $path . '/output/' . $file->getRelativePathname();

            $jpgStartTime = microtime(true);
            $this->compressJpg($file->getPathname(), $dest);
            $jpgEndTime = microtime(true);
            $jpgMemory = memory_get_peak_usage();
            $results[] = [$file->getRelativePathname(), ($jpgEndTime - $jpgStartTime) * 1000, $jpgMemory];

            $bar->advance();
        }
        $bar->finish();
        $this->table($headers, $results);
        return 0;
    }

    public function compressWebp()
    {
        $size = getimagesize($this->in);
        $filetype = $size['mime'];
        if ($filetype === 'image/png' && !$this->modifiers['lossy']) {
            $params = '-near_lossless ' . (100 - $this->getQuality());
        } else {
            $params = '-q ' . $this->getQuality();
        }
        $params .= ' -m 6 -af -mt';
        $command = base_path('bin/cwebp')
            . ' ' .
            escapeshellarg($this->out)
            . ' ' . $params . ' -o ' .
            escapeshellarg($this->out);
        $output = null;
        exec($command, $output);

        if ($this->originalSize <= filesize($this->out)) {
            $this->restoreInToOut();
        }
    }

    public function compressPng()
    {
        exec(base_path('bin/pngcrush') . ' -blacken -bail -rem alla -reduce -ow ' . escapeshellarg($this->out));
        exec(base_path('bin/zopflipng') . ' --lossy_transparent -y ' . escapeshellarg($this->out) . ' ' . escapeshellarg($this->out));
        if ($this->originalSize <= filesize($this->out)) {
            $this->restoreInToOut();
        }
    }

    public function compressSVG()
    {
        exec('svgo --multipass ' . escapeshellarg($this->out));
        if ($this->originalSize <= filesize($this->out)) {
            $this->restoreInToOut();
        }
    }

    public function compressJpg($file, $destination)
    {
        exec(base_path('bin/jpegoptim') . ' -s --all-normal ' . escapeshellarg($file) . ' --dest=' . escapeshellarg(dirname($destination)));
    }

    public function compressAvif()
    {
        // Compression level (0..63), [default: 25]
        $quality = 63 - floor((int)$this->getQuality() / 100 * 63);
        if ($quality < 0) {
            $quality = 0;
        }
        if ($quality > 63) {
            $quality = 63;
        }
        $dest = str_replace('.' . File::extension($this->out), '.avif', $this->out);
        exec(base_path('bin/avif') . ' -e ' . escapeshellarg($this->out) . ' -o ' . escapeshellarg($dest) . ' -q ' . $quality);
        File::move($dest, $this->out);
        if ($this->originalSize <= filesize($this->out)) {
            $this->restoreInToOut();
        }
    }
}
