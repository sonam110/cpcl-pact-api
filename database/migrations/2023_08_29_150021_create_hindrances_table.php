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
        Schema::create('hindrances', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('project_id');
            // $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            
            $table->unsignedBigInteger('contractor_id');
            $table->foreign('contractor_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('epcm_id');
            $table->foreign('epcm_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('owner_id');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // $table->string('title');
            $table->string('contract_number');
            $table->string('hindrance_code');
            $table->string('hindrance_type');
            $table->text('description')->nullable();
            $table->string('package');
            $table->text('uploaded_files');
            $table->string('vendor_name');
            $table->string('vendor_contact_number')->nullable();
            $table->string('vendor_contact_email');
            $table->string('notes',244);
            $table->string('reason_of_rejection')->nullable();
            $table->string('rejection_update_description')->nullable();
            $table->string('rejected_by')->nullable();
            $table->enum('status',['under_review_by_epcm','under_review_by_owner','resolved','on_hold','rejected_by_epcm','rejected_by_owner','rejected_by_admin', 'approved','pending_with_epcm','pending_with_owner','resend','re-assigned'])->default('pending_with_epcm');
            $table->text('action_performed')->nullable();
            $table->date('approved_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('hindrance_date')->nullable();
            $table->string('contacted_person')->nullable();
            $table->integer('priority')->nullable()->comment('1:high,2:medium,3:low');
            $table->text('reason_for_assignment')->nullable();
            $table->enum('creator_user_type',[1,2,3,4])->nullable()->comment('creator user type 3 for epcm, 4 for contractor, 1 for admin, 2 for owner');
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
        Schema::dropIfExists('hindrances');
    }
};
