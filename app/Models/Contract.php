<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','contract_number','contract_name','contractor_id','package','vendor_code','description','total_contract_value'];

    public function contractEpcms()
    {
    	return $this->hasMany(ContractEpcm::class,'contract_id','id');
    }
    public function contractOwners()
    {
        return $this->hasMany(ContractOwner::class,'contract_id','id');
    }
    public function contractor()
    {
    	return $this->belongsTo(User::class,'contractor_id','id');
    }
    
}
