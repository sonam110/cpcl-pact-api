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
        Schema::create('contract_type_check_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_type_id')->nullable();
            $table->foreign('contract_type_id')->references('id')->on('contract_types')->onDelete('cascade');
            
            $table->unsignedBigInteger('check_list_id')->nullable();
            $table->foreign('check_list_id')->references('id')->on('check_lists')->onDelete('cascade');
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
        Schema::dropIfExists('contract_type_check_lists');
    }
};
