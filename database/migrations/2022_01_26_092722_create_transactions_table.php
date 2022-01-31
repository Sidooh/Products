<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('initiator', 20);
            $table->string('type', 20); // PAYMENT or WITHDRAWAL : TRANSFER? (P2P, B2B)
            $table->decimal('amount');
            $table->string('status', 20)->default('PENDING'); //    Use enums
            $table->string('destination')->nullable();
            $table->string('description');

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
        Schema::dropIfExists('transactions');
    }
}
