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
        Schema::create('product_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('provider');
            $table->string('account_number');

            $table->foreignId('account_id')->unsigned();
            $table->index(['account_id', 'provider']);
            $table->unique(['account_id', 'provider', 'account_number']);

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
        Schema::dropIfExists('product_accounts');
    }
};
