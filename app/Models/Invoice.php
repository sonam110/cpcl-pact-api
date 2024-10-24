<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    // protected $appends = ['is_checks_verified'];
    protected $fillable = ['contractor_id','epcms','owners','unique_no','invoice_no','description','amount','invoice_type','vendor_name','vendor_contact_number','vendor_contact_email','package','notes','reason_of_rejection','status','approved_date','paid_date','uploaded_files','barcode','invoice_check_id','assigned_to','last_action_performed_by', 'paid_amount'];

    public function epcms()
    {
         return $this->hasMany(InvoiceEpcm::class, 'invoice_id', 'id');
    }
    public function owner()
    {
         return $this->belongsTo(User::class, 'owner_id', 'id');
    }
    public function owners()
    {
         return $this->hasMany(InvoiceAssignedOwner::class, 'invoice_id', 'id');
    }

    public function lastActionPerformedBy()
    {
         return $this->belongsTo(User::class, 'last_action_performed_by', 'id');
    }
    
    public function contractor()
    {
         return $this->belongsTo(User::class, 'contractor_id', 'id');
    }
    public function invoiceCheckVerifications()
    {
         return $this->hasMany(InvoiceCheckVerification::class, 'invoice_id', 'id');
    }
    public function invoiceActivityLogs()
    {
         return $this->hasMany(InvoiceActivityLog::class, 'invoice_id', 'id')->with('performedBy:id,name,email,mobile_number');
    }
    public function invoiceTimeLogs()
    {
         return $this->hasMany(InvoiceTimeLog::class, 'invoice_id', 'id');
    }

    
    public function contract()
    {
         return $this->belongsTo(Contract::class, 'contract_number', 'contract_number')->with('contractEpcms.epcm:id,name,email,mobile_number');
    }

    // public function getIsChecksVerifiedAttribute()
    // {
    //     $checks = InvoiceCheckVerification::where('invoice_id',$this->id)->whereIn('status',['pending','not approved'])->count();
    //     if($checks >= 1)
    //     {
    //         $is_checks_verified = 0;
    //     }
    //     else
    //     {
    //         $is_checks_verified = 1;
    //     }
    //     return $is_checks_verified;
        

    // }
}
