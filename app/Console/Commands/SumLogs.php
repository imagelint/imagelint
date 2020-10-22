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
        $tables = $this->geLogTables();
        $originals = collect([]);
        Original::select('id', 'path')->orderBy('id')->chunk(1000, function ($entries) use (&$originals)
    {
        $this->info(count($entries));
            foreach ($entries as $entry) {
                $filePath = $this->getFilePath($entry->path);
                $this->info($filePath);
                $originals->put($filePath, $entry);
            }
        });
        Log::debug(json_encode($originals->all()));

        /*
        $users = User::get();
        foreach ($users as $user) {
            $originals = Original::where('user_id', $user->id)->get(['id', 'path']);
            foreach ($originals as $original) {
                $filePath = $this->getFilePath($original->path);
                $this->info($filePath);
                foreach ($tables as $table) {
                    $day = $this->getDateFromTableName($table);
                    if ($this->getDaysFromNow($day) > 1) {
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
            if (strpos($table, 'access_logs_') !== false) {
                $logsTables[] = $table;
            }
        }
        return $logsTables;
    }

    private function getFilePath($originalPath)
    {
        $params = '';
        $parts = explode('/', $originalPath);
        if (!Str::startsWith($parts[0], '.')) {
            $params = '?' . $parts[0];
            array_shift($parts);
        }
        return implode('/', $parts) . $params;
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
