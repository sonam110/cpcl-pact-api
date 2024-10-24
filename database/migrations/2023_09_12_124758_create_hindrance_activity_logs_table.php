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
        Schema::create('hindrance_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hindrance_id');
            $table->foreign('hindrance_id')->references('id')->on('hindrances')->onDelete('cascade');

            $table->unsignedBigInteger('performed_by');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('cascade');

            $table->string('action');
            $table->text('description');
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
        Schema::dropIfExists('hindrance_activity_logs');
    }
};
