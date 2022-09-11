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
    public function up(): void
    {
        Schema::create('enterprise_accounts', function(Blueprint $table) {
            $table->id();

            $table->string('type', 20);
            $table->boolean('active')->default(false);

            $table->foreignId('account_id')->unsigned();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->unique(['account_id', 'enterprise_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('enterprise_accounts');
    }
};
