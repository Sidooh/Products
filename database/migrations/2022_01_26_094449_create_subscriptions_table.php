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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->timestamp('start_date');

            // TODO: Find if laravel fixed issue of consecutive timestamps and remove nullable below
            $table->timestamp('end_date')->nullable();

            $table->string('status')->default('PENDING'); // PENDING / ACTIVE / EXPIRED

            $table->foreignId('account_id')->unsigned();
            $table->foreignId('subscription_type_id')->constrained()->cascadeOnDelete();

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
        Schema::dropIfExists('subscriptions');
    }
};
