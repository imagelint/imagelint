<?php

namespace App\Console\Commands;

use App\Models\AccessLog;
use App\Models\Original;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SumLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:sum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command sums logs';

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
        $count = 0;
        $tables = $this->geLogTables();
        $tables=['access_logs_20201019'];
        $originals = collect([]);
        $originalEntries = Original::select('id', 'path')->get();
        foreach ($originalEntries as $entry) {
            $filePath = $this->getFilePathFromOriginal($entry->path);
            //$this->info($filePath);
            $originals->put($filePath, $entry);
        }
        Log::debug(json_encode($originals));
        foreach ($tables as $table) {
            try {
                $dataToSave = collect([]);
                $accessLogs = collect([]);
                $day = $this->getDateFromTableName($table);
                if ($this->getDaysFromNow($day) > 1) {
                    DB::table($table)->select('request', 'size')->orderBy('created_at')->chunk(1000, function ($logEntries) use (&$accessLogs, &$originals, &$count) {
                        foreach ($logEntries as $log) {
                            $filePath = $this->getFilePathFromLog($log->request);
                            if($originals->has($filePath)) {
                                $original = $originals->get($filePath);
                                $this->info($log->request);
                                if($accessLogs->has($original->id)) {
                                    $accessLog = $accessLogs->get($original->id);
                                    $logToCollection = [
                                        'path' => $filePath,
                                        'size' => $accessLog['size'] +  $log->size,
                                        'amount' => $accessLog['amount'] + 1,

                                    ];
                                } else {
                                    $logToCollection = [
                                        'path' => $filePath,
                                        'size' => $log->size,
                                        'amount' => 1,
                                    ];
                                }
                                $accessLogs->put($original->id, $logToCollection);
                            }
                        }
                    });
                    Log::debug($accessLogs->all());
                    /*
                    $accessLogsGrouped = $accessLogs->groupBy('path');
                    Log::debug($accessLogsGrouped->all());
                    $originals->each(function ($original, $path) use (
                        $accessLogs
                    ) {
                        if($path === 'lukasz-socha.pl/wp-content/uploads/2014/03/slider2.jpg?il-height=75&il-avif=1') {
                            //$this->info($path);
                            if(!empty($accessLogsGrouped[$path])) {
                                $accessLogsForPath = $accessLogsGrouped[$path];
                                $this->info(count($accessLogsForPath));
                            }
                        }
                    });
                    */
                }
            } catch (\Exception $e) {
                $this->info($e->getMessage());
            }
        }

        /*
        $users = User::get();
        foreach ($users as $user) {
            $originals = Original::where('user_id', $user->id)->get(['id', 'path']);
            foreach ($originals as $original) {
                $filePath = $this->getFilePath($original->path);
                $this->info($filePath);
                foreach ($tables as $table) {
                    $day = $this->getDateFromTableName($table);
                    if ($this->getDaysFromNow($day) > 1) {g
                        $entries = DB::table($table)->where('request', 'like', '%' . $filePath . '%')->get();
                        $result = $this->getSumFromEntries($entries);

                        if ($result['amount'] > 0) {
                            $accessLog = new AccessLog();
                            $accessLog->day = $day;
                            $accessLog->size = $result['size'];
                            $accessLog->amount = $result['amount'];
                            $accessLog->original_id = $original->id;
                            $accessLog->save();
                        }
                    }
                }
            }
        }
        */
        /*
         * It works ok
        foreach ($tables as $table) {
            try{
                $day = $this->getDateFromTableName($table);
                if ($this->getDaysFromNow($day) > 30) {
                    Schema::dropIfExists($table);
                }
            } catch (\Exception $e) {
                Log::debug($e->getMessage());
            }
        }
         */
        return 0;
    }

    private function geLogTables()
    {
        $logsTables = [];
        $allTables = array_map('reset', DB::select('SHOW TABLES'));
        foreach ($allTables as $table) {
            if (Str::startsWith($table, 'access_logs_')) {
                $logsTables[] = $table;
            }
        }
        return $logsTables;
    }

    private function getFilePathFromOriginal($path)
    {
        $params = '';
        $parts = explode('/', $path);
        if (!Str::contains($parts[0], '.')) {
            $params = '?' . $parts[0];
            array_shift($parts);
        }
        return implode('/', $parts) . $params;
    }

    private function getFilePathFromLog($path)
    {
        $parts = explode(' ', $path);
        if (empty($parts[1])) {
            throw new \Exception('A file path doesn\'t exist');
        }
        return Str::before(ltrim($parts[1], '/'), '?');
    }

    private function getSumFromEntries($entries)
    {
        $result = [
            'size' => 0,
            'amount' => count($entries),
        ];
        foreach ($entries as $entry) {
            $result['size'] += $entry->size;
        }
        return $result;
    }

    private function getDateFromTableName($table)
    {
        $parts = explode('_', $table);
        if (empty($parts[2])) {
            throw new \Exception('Wrong table name');
        }
        return substr($parts[2], 0, 4) . '-' . substr($parts[2], 4, 2) . '-' . substr($parts[2], 6, 2);
    }

    private function getDaysFromNow($day)
    {
        $dateTable = new \DateTime($day);
        $now = new \DateTime();
        return $now->diff($dateTable)->format("%a");
    }
}
