<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;

class BenchmarkRequests extends Command
{
    protected $url;
    protected $width = 500;
    protected $height = 500;
    protected $index = 0;
    protected $amount = 0;
    protected $amountBrunches;
    protected $amountOfBrunch;
    protected $client;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benchmark:requests {url} {amount=100} {amountOfBrunch=50}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It tests HTTP requests to a server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $promises = [];
        $this->url = $this->argument('url');
        $this->amount = $this->argument('amount');
        $this->amountOfBrunch = $this->argument('amountOfBrunch');
        $this->amountBrunches = ceil($this->amount / $this->amountOfBrunch);

        if(!app()->environment('production') && strpos($this->url, 'imagelint.com') !== false) {
            $this->error('You can\'t use imagelint.com on a development server!');
            exit;
        }
        for($i=0;$i<$this->amountBrunches;$i++) {
            $promise = new Promise();
            $promise
                ->then(function ($value) {
                    // Return a value and don't break the chain
                    $this->info($value);
                })
                // This then is executed after the first then and receives the value
                // returned from the first then.
                ->then(function ($value) {
                    $this->info($value);
                });
            if($this->doBunch()) {
                $promise->resolve('did bunch '.$i);
            }
            $promises[] = &$promise;
        }
        foreach ($promises as $promise) {
            $promise->wait();
        }

        return 0;
    }
    public function doBunch() {
        $promises = [];
        for($i = 0; $i<$this->amountOfBrunch;$i++) {
            if($this->index == $this->amount) {
                break;
            }
            $promises[] = $this->client->getAsync($this->url, ['il-width' => $this->width, 'il-height' => $this->height])->then(
                function (Response $response) {
                    if($this->index % 2 - 0) {
                        $this->width++;
                    } else {
                        $this->height++;
                    }
                    $this->index++;
                    $this->info('Request '.$this->index.': ok. Params: il-width='.$this->width.', il-height='.$this->height);
                    return $response->getBody();
                }, function ($exception) {
                $this->info('Request'.$this->index.': error');

                return $exception->getMessage();
            });
        }
        foreach ($promises as $promise) {
            $promise->wait();
        }
        return true;
    }
}
