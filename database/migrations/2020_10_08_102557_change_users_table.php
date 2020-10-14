<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->constrained('accounts');
        });
        DB::table('users')->update(['account_id' => 1]);
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('account_id')->nullable(false)->change();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
}
