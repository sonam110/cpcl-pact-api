<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceCheckVerification extends Model
{
    use HasFactory;
    protected $fillable = ['invoice_id','check','status'];
}
