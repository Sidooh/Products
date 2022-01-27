<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFloatAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('float_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('type', 20);
            $table->double('balance', 10,  2)->default(0);
            $table->morphs('accountable');
            $table->foreignId('account_id')->unsigned();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('float_accounts');
    }
}
