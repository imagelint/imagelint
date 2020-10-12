<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Schema::table('domains', function (Blueprint $table) {
                $table->dropColumn('account');
                $table->dropIndex('user_id');
                $table->dropColumn('user_id');
                $table->foreignId('account_id')->nullable()->constrained('accounts');
            });
            DB::table('domains')->update(array('account_id' => 1));
            Schema::table('domains', function (Blueprint $table) {
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
        Schema::table('domains', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
            $table->string('account')->index();
            $table->unsignedInteger('user_id')->index('user_id');
        });
    }
}
