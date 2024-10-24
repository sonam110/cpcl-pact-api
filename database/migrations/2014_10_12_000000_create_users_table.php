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
        if (!Schema::connection('kpmg_master_db')->hasTable('users')) 
        {
            Schema::connection('kpmg_master_db')->create('users', function ($table) {
                $table->id();
                $table->string('name', 500);
                $table->string('email', 500);
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::connection('kpmg_master_db')->hasTable('modules')) 
        {
            Schema::connection('kpmg_master_db')->create('modules', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::connection('kpmg_master_db')->hasTable('assigne_modules')) 
        {
            Schema::connection('kpmg_master_db')->create('assigne_modules', function ($table) {
                $table->id();
                $table->unsignedBigInteger('module_id');
                $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('epcm_id')->nullable();
            $table->foreign('epcm_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('owner_id')->nullable();
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');

            $table->integer('user_type')->comment('1: admin, 2: owner, 3: EPCM, 4: contractor, 5: owner finance');

            $table->unsignedBigInteger('package_id')->nullable();
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');

            $table->string('name', 500);
            $table->string('user_name', 500)->nullable();
            $table->string('engineer_incharge')->nullable();
            $table->string('email', 500);
            $table->string('password')->comment('Hash OR bcrypt');
            $table->unsignedBigInteger('role_id');
            $table->string('mobile_number')->nullable();
            $table->text('address')->nullable();
            $table->string('designation')->nullable();
            $table->tinyInteger('kmeet')->default(1)->comment('1=Active,2=Inactive');
            $table->tinyInteger('invoice')->default(1)->comment('1=Active,2=Inactive');
            $table->tinyInteger('hindrance')->default(1)->comment('1=Active,2=Inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive,2=Delete');
            $table->date('password_last_updated')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('kpmg_master_db')->dropIfExists('users');
        Schema::connection('kpmg_master_db')->dropIfExists('modules');
        Schema::connection('kpmg_master_db')->dropIfExists('assigne_modules');
        Schema::dropIfExists('users');
    }
};
