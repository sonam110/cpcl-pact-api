<?php

namespace App\Imports;

use App\Models\Hindrance;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use DB;
use Hash;
use Auth;
use Str;

class InvoicesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return true;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function startRow(): int 
    {
         return 2;
    }
    
}
