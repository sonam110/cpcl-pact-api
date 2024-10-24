<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceTimeLog extends Model
{
    use HasFactory;
    protected $appends = ['current_users','current_user'];
    protected $fillable = ['invoice_id','current_user_id','status','opening_date','closing_date'];
    public function currentUser()
    {
         return $this->belongsTo(User::class, 'current_user_id', 'id');
    }

    public function getCurrentUsersAttribute()
    {
    	$ids = explode(',', $this->current_user_id);
        $users = User::whereIn('id',$ids)->get(['id','name']);
        return $users;
    }

    public function getCurrentUserAttribute()
    {
    	$ids = explode(',', $this->current_user_id);
    	$current_user = [];
        $users = User::whereIn('id',$ids)->get(['id','name']);
       	foreach ($users as $key => $value) {
       		$current_user[] = $value->name;
       	}
       	return implode(',', $current_user);
    }
}
