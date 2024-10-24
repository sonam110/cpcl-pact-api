<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Hindrance extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [ 'contractor_id','epcm_id','owner_id','contract_number','hindrance_code','hindrance_type','description','package','uploaded_files','vendor_name','vendor_contact_number','notes','reason_of_rejection','action_performed','approved_date','resolved_date','status','due_date','priority','vendor_contact_email','reason_for_assignment','rejection_update_description'];



    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->useLogName('Hindrance')
            ->setDescriptionForEvent(fn(string $eventName) => "Hindrance has been {$eventName}");;
    }

    public function epcm()
    {
         return $this->belongsTo(User::class, 'epcm_id', 'id');
    }
    public function owner()
    {
         return $this->belongsTo(User::class, 'owner_id', 'id');
    }
    public function createdBy()
    {
         return $this->belongsTo(User::class, 'created_by', 'id');
    }
    // public function project()
    // {
    //      return $this->belongsTo(Project::class, 'project_id', 'id');
    // }

    public function contractor()
    {
         return $this->belongsTo(User::class, 'contractor_id', 'id');
    }

    public function hindranceActivityLogs()
    {
         return $this->hasMany(HindranceActivityLog::class, 'hindrance_id', 'id')->with('performedBy:id,name,email,mobile_number');
    }

    public function hindranceTimeLogs()
    {
         return $this->hasMany(HindranceTimeLog::class, 'hindrance_id', 'id')->with('currentUser:id,name');
    }

    public function assignees()
    {
         return $this->hasMany(HindranceAssignee::class, 'hindrance_id', 'id')->with('assignedTo:id,name,email,mobile_number');
    }

    public function contract()
    {
         return $this->belongsTo(Contract::class, 'contract_number', 'contract_number')->with('contractEpcms.epcm:id,name,email,mobile_number');
    }
}
