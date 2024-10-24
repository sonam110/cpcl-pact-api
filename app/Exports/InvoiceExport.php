<?php

namespace App\Exports;

use App\Models\SubscriptioLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\User;
use App\Models\Invoice;
use Auth;

class InvoiceExport implements FromCollection, WithHeadings
{
	use Exportable;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }


    public function headings(): array {
        return [
            'SNO',
            'Contractor Id',
            'Contract Number',
            'Epcms',
            'Owners',
            'Unique No',
            'Invoice No',
            'Description',
            'Basic Amount',
            'GST Amount',
            'Total Amount',
            'Invoice Type',
            'Vendor Name',
            'Vendor Contact Number',
            'Vendor Contact Email',
            'Package',
            'Notes',
            'Invoice Date',
            'Status',
            'Approved Date',
            'Paid Date',
            'Uploaded Files',
            "Created By",
            "Created At"
        ];
    }

    public function collection()
    {
        $invoices = Invoice::orderBy('id','desc');
        if(!empty($this->data['contractor_id']))
        {
            $invoices->where('contractor_id',$this->data['contractor_id']);
        }
        if(!empty($this->data['status']))
        {
            $invoices->where('status',$this->data['status']);
        }
        if(!empty($this->data['from_date']))
        {
            $invoices->where('created_at','>=',$this->data['from_date']);
        }
         if(!empty($this->data['to_date']))
        {
            $invoices->where('created_at','<=',$this->data['to_date']);
        }
        $invoices = $invoices->get();
    	return $invoices->map(function ($data, $key) {
            return [
                'SNO' => $key+1,
                'Contractor Id' => $data->contractor_id,
                'Contract Number' => $data->contract_number,
                'Epcm Id' => json_decode($data->epcms),
                'Owners' => json_decode($data->owners),
                'Unique No' => $data->unique_no,
                'Invoice No' => $data->invoice_no,
                'Description' => $data->description,
                'Basic Amount' => $data->basic_amount,
                'GST Amount' => $data->gst_amount,
                'Total Amount' => $data->total_amount,
                'Invoice Type' => $data->invoice_type,
                'Vendor Name' => $data->vendor_name,
                'Vendor Contact Number' => $data->vendor_contact_number,
                'Vendor Contact Email' => $data->vendor_contact_email,
                'Package' => $data->package,
                'Notes' => $data->notes,
                'Invoice Date' => $data->invoice_date,
                'Status' => $data->status,
                'Approved Date' => $data->approved_date,
                'Paid Date' => $data->paid_date,
                'Uploaded Files'=> $data->uploaded_files,
                'Created By' => $data->created_by,
                'Created At' => $data->created_at
            ];
    	});
    }
}
