<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCritTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crit_tokens', function (Blueprint $table) {
            $table->string('domain', 100)->unique();
            $table->string('email', 100);
            $table->string('token', 500);
            $table->string('tariff', 100);
            $table->string('order_number', 100);
            $table->date('date_activation');
            $table->date('date_expiration');
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
        Schema::dropIfExists('crit_tokens');
    }
}
