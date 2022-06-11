<?php

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
        Schema::table('transactions', function (Blueprint $table) {
            //
            if (!Schema::hasColumn('transactions', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('account_id');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
