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
        Schema::create('at_airtime_responses', function(Blueprint $table) {
            $table->id();

            $table->string('phone', 12);
            $table->string('message');
            $table->decimal('amount');
            $table->string('status', 15)->default('SENT');
            $table->string('discount');
            $table->string('description')->nullable();
            $table->string('request_id')->index();

            $table->foreignId('at_airtime_request_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

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
        Schema::dropIfExists('airtime_responses');
    }
};
