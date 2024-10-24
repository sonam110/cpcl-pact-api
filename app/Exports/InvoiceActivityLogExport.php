<?php

namespace App\Exports;

use App\Models\SubscriptioLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\User;
use App\Models\InvoiceActivityLog;
use Auth;

class InvoiceActivityLogExport implements FromCollection, WithHeadings
{
	use Exportable;

    public $invoice_id;

    public function __construct($invoice_id)
    {
        $this->invoice_id = $invoice_id;
    }


    public function headings(): array {
        return [
            'SNO',
            "Performed By",
            "Action",
            "Description",
            "Created At",
        ];
    }

    public function collection()
    {
        $invoiceActivityLogs = InvoiceActivityLog::where('invoice_id',$this->invoice_id)->get();
    	return $invoiceActivityLogs->map(function ($data, $key) {
            return [
                'SNO' => $key+1,
                'Performed By' => User::find($data->performed_by)->name,
                'Action' => $data->action,
                'Description' => $data->description,
                'Created At' => $data->created_at
            ];
    	});
    }
}
