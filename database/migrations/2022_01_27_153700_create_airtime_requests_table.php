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
        Schema::create('airtime_requests', function (Blueprint $table) {
            $table->id();

            $table->string('message');
            $table->smallInteger('num_sent');
            $table->decimal('amount');
            $table->string('discount');
            $table->string('description');

            $table->foreignId('transaction_id')->nullable();

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
        Schema::dropIfExists('airtime_requests');
    }
};
