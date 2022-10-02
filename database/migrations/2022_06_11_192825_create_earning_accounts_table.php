<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('earning_accounts', function(Blueprint $table) {
            $table->id();

            $table->string('type'); //PURCHASES / SUBSCRIPTIONS / MERCHANTS ...
            $table->decimal('self_amount', 12, 4)->default(0);
            $table->decimal('invite_amount', 12, 4)->default(0);

            $table->foreignId('account_id')->unsigned();
            $table->unique(['account_id', 'type']);

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
        Schema::dropIfExists('earning_accounts');
    }
};
