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
        Schema::create("payments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("transaction_id")->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger("payment_id")->unique();
            $table->decimal('amount');
            $table->string('type', 20);
            $table->string('subtype', 20);
            $table->string('status', 20)->default(Status::PENDING->name);
            $table->string('extra'); //e.g. voucher used to pay
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
        Schema::dropIfExists("payments");
    }
};
