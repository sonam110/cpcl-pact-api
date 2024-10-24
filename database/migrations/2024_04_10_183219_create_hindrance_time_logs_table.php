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
        Schema::create('hindrance_time_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hindrance_id')->nullable();
            $table->foreign('hindrance_id')->references('id')->on('hindrances')->onDelete('cascade');

            $table->integer('current_user_id');
            $table->string('status')->nullable();
            $table->date('opening_date')->nullable();
            $table->date('closing_date')->nullable();
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
        Schema::dropIfExists('hindrance_time_logs');
    }
};
