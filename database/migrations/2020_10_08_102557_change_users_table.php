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
        Schema::table('users', function (Blueprint $table) {
            //$table->unsignedBigInteger('account_id');
            /*
             * To add the constraint to an existing column, you have to do $table->foreignId('user_id')->constrained()->change()
             */
            $table->foreignId('account_id')->constrained('accounts');
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
            $table->dropForeign('account_id');
            $table->dropColumn('account_id');
        });
    }
}
