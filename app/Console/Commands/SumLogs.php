<?php

namespace App\Console\Commands;

use App\Models\AccessLog;
use App\Models\Original;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        $tables = $this->geLogsTables();
        $users = User::with('account')->get();
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
        foreach ($tables as $table) {
            $day = $this->getDateFromTableName($table);
            $this->info($this->getDaysFromNow($day));
            if ($this->getDaysFromNow($day) > 30) {
                Schema::dropIfExists($table);
            }
        }
        return 0;
    }

    protected function geLogsTables()
    {
        $logsTables = [];
        $AllTables = array_map('reset', DB::select('SHOW TABLES'));
        foreach ($AllTables as $table) {
            if (strpos($table, 'access_logs_') !== false) {
                $logsTables[] = $table;
            }
        }
        return $logsTables;
    }

    protected function getFilePath($originalPath)
    {
        $params = '';
        $parts = explode('/', $originalPath);
        if (strpos($parts[0], '.') === false) {
            $params = '?' . $parts[0];
            array_shift($parts);
        }
        return implode('/', $parts) . $params;
    }

    protected function getSumFromEntries($entries)
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

    protected function getDateFromTableName($table)
    {
        $parts = explode('_', $table);
        if (empty($parts[2])) {
            return '';
        }
        return substr($parts[2], 0, 4) . '-' . substr($parts[2], 4, 2) . '-' . substr($parts[2], 6, 2);
    }

    protected function getDaysFromNow($day)
    {
        try {
            $dateTable = new \DateTime($day);
            $now = new \DateTime();
            return $now->diff($dateTable)->format("%a");
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
            return 0;
        }
    }
}
