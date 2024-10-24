<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTypeCheckList extends Model
{
    use HasFactory;

    public function checkList()
    {
    	$this->belongsTo(CheckList::class,'check_list_id','id');
    }
}
