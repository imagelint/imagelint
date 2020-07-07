<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logtables:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates database tables for the next 7 days which syslog-ng uses to store data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Creates tables for the next 7 days
        $tablesToCreate = 7;
        $today = Carbon::now();
        while($tablesToCreate >= 0) {
            // The table name and order of columns has to be identical to the one generated in /docker/syslog-ng/syslog-ng.conf
            $tableName = 'access_logs_' . $today->clone()->addDays($tablesToCreate)->format("Ymd");

            if (!Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table) {
                    // We don't strictly need an id here, but some deployments might want to replicate the database
                    // and the replication algorithm needs a primary key on each table.
                    $table->id();
                    // We add indizes to these three columns, because we want to create statistics on these fields and mysql is a lot faster
                    // when it can operate on indizes only. The tradeoff is insert performance, which might be an issue at some point.
                    $table->dateTime('created_at')->index();
                    $table->string('account', 60)->index();
                    $table->unsignedInteger('size')->index();
                    $table->string('request', 2000);
                    $table->string('log', 2100);
                });
                $this->info('Created table ' . $tableName);
            }
            $tablesToCreate--;
        }
    }
}
