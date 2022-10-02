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
        Schema::create('merchants', function(Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code', 15)->unique();
            $table->string('contact_name', 32);
            $table->string('contact_phone', 15);
            $table->decimal('balance', 10, 4)->default(0);

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
        Schema::dropIfExists('merchants');
    }
};
