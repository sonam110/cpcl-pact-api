<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceAssignedOwner extends Model
{
    use HasFactory;
    public function user()
    {
         return $this->belongsTo(User::class, 'owner_id', 'id');
    }
}
