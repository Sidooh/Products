<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFloatAccountTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('float_account_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('type', 20); // DEBIT or CREDIT
            $table->decimal('amount');
            $table->string('description');
            $table->foreignId('float_account_id')->constrained();

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
        Schema::dropIfExists('float_account_transactions');
    }
}
