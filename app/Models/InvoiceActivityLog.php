<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceActivityLog extends Model
{
    use HasFactory;
    protected $fillable = ['invoice_id','performed_by','action','description'];
    public function performedBy()
    {
         return $this->belongsTo(User::class, 'performed_by', 'id');
    }
}
