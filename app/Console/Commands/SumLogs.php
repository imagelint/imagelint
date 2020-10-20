<?php

namespace App\Console\Commands;

use App\Models\Original;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $this->info(implode(', ', $tables));
        $users = User::with('account')->get();
        foreach ($users as $user) {
            $originals = Original::where('user_id', $user->id)->get(['id', 'path']);
            foreach ($originals as $original) {
                $filePath = $this->getFilePath($original->path);
                $this->info($filePath);
            }
            $this->info($user->account->account);
            Log::debug($user->account);
        }
        /*
        $originals = Original::where('account_id', $user->account_id)->get()->transform(function ($domain) {
            return $domain->only(['id', 'domain', 'created_at']);
        });
        */
        return 0;
    }

    public function geLogsTables()
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

    public function getFilePath($originalPath)
    {
        $parts = explode('/', $originalPath);
        if(strpos( $parts[0], '.') === false) {
            array_shift($parts);
        }
        return implode('/', $parts);
    }
}
