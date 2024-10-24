<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HindranceTimeLog extends Model
{
    use HasFactory;
    protected $fillable = ['invoice_id','current_user_id','status','opening_date','closing_date'];
    public function currentUser()
    {
         return $this->belongsTo(User::class, 'current_user_id', 'id');
    }
}
