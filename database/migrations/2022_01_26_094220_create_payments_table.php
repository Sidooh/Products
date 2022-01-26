<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->morphs('payable');
            $table->float('amount');
            $table->string('status', 10); // PENDING or COMPLETE
            $table->string('type', 15); // ['MOBILE', 'SIDOOH', 'BANK', 'PAYPAL', 'OTHER'] payment methods?
            $table->string('subtype', 15); // 'STK', 'C2B', 'CBA', 'WALLET', 'BONUS'
            $table->morphs('payment');

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
        Schema::dropIfExists('payments');
    }
}
