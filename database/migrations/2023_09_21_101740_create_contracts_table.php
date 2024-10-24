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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('contractor_id')->nullable();
            $table->foreign('contractor_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('contract_type_id')->nullable();
            $table->foreign('contract_type_id')->references('id')->on('contract_types')->onDelete('cascade');
            
            $table->string('package');
            $table->string('contract_name')->nullable();
            $table->string('contract_number')->unique();
            $table->text('description')->nullable();
            $table->float('total_contract_value',10,2)->default(0)->nullable();
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
        Schema::dropIfExists('contracts');
    }
};
