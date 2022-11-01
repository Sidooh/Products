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
        Schema::create('transactions', function(Blueprint $table) {
            $table->id();

            $table->string('initiator', 20);
            $table->string('type', 20); // PAYMENT or WITHDRAWAL : TRANSFER? (P2P, B2B)
            $table->decimal('amount');
            $table->string('status', 20)->default(Status::PENDING->value);
            $table->string('description');
            $table->string('destination')->nullable();

            $table->foreignId('account_id')->unsigned();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate();

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
        Schema::dropIfExists('transactions');
    }
};
