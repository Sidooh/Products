<?php

use App\Enums\Status;
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
        Schema::create('savings_transactions', function(Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('savings_id')->unique();
            $table->decimal('amount');
            $table->string('description');
            $table->string('type', 32); // CREDIT or DEBIT
            $table->string('status', 32)->default(Status::PENDING->name); //    Use enums
            $table->json('extra')->nullable(); //e.g. destination

            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

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
        Schema::dropIfExists('savings_transactions');
    }
};
