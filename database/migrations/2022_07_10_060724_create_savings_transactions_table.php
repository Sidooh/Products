<?php

use App\Enums\Status;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->nullable();
            $table->string('type', 20); // CREDIT or DEBIT
            $table->decimal('amount');
            $table->string('status', 20)->default(Status::PENDING->name); //    Use enums
            $table->string('description');

            $table->foreignIdFor(Transaction::class, 'transaction_id');

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
        Schema::dropIfExists('savings_transactions');
    }
};
