<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HindranceAssignee extends Model
{
    use HasFactory;
    protected $fillable = ['hindrance_id','assigned_to'];
    public function assignedTo()
    {
         return $this->belongsTo(User::class, 'assigned_to', 'id');
    }
}
