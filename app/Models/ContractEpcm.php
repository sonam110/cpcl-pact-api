<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractEpcm extends Model
{
    use HasFactory;

    public function epcm()
    {
    	return $this->belongsTo(User::class,'epcm_id','id');
    }
}
