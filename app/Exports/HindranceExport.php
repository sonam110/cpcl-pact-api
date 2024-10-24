<?php

namespace App\Exports;

use App\Models\SubscriptioLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\User;
use App\Models\Hindrance;
use Auth;

class HindranceExport implements FromCollection, WithHeadings
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
            'Contract Number',
            'Contractor Id',
            'Epcm Id',
            'Owner Id',
            'Hindrance Code',
            'Hindrance Date',
            'Description',
            'Hindrance Type',
            'Vendor Name',
            'Vendor Contact Number',
            'Vendor Contact Email',
            'Contacted Person',
            'Package',
            'Notes',
            'Reason Of Rejection',
            'Status',
            'Approved Date',
            'Resolved Date',
            'Action Performed',
            'Uploaded Files',
            'Due Date',
            'Priority',
            "Created By",
            "Created At"
        ];
    }

    public function collection()
    {
        $hindrances = Hindrance::orderBy('id','desc');
        if(!empty($this->data['contractor_id']))
        {
            $hindrances->where('contractor_id',$this->data['contractor_id']);
        }
        if(!empty($this->data['status']))
        {
            $hindrances->where('status',$this->data['status']);
        }
        if(!empty($this->data['from_date']))
        {
            $hindrances->where('created_at','>=',$this->data['from_date']);
        }
         if(!empty($this->data['to_date']))
        {
            $hindrances->where('created_at','<=',$this->data['to_date']);
        }
        $hindrances = $hindrances->get();
    	return $hindrances->map(function ($data, $key) {
            return [
                'SNO' => $key+1,
                'Contract Number' => $data->contract_number,
                'Contractor Id' => $data->contractor_id,
                'Epcm Id' => $data->epcm_id,
                'Owner Id' => $data->owner_id,
                'Hindrance Code' => $data->hindrance_code,
                'Hindrance Date' => $data->hindrance_date,
                'Description' => $data->description,
                'Hindrance Type' => $data->hindrance_type,
                'Vendor Name' => $data->vendor_name,
                'Vendor Contact Number' => $data->vendor_contact_number,
                'Vendor Contact Email' => $data->vendor_contact_email,
                'Contacted Person' => $data->contacted_person,
                'Package' => $data->package,
                'Notes' => $data->notes,
                'Reason Of Rejection' => $data->reason_of_rejection,
                'Status' => $data->status,
                'Approved Date' => $data->approved_date,
                'Resolved Date' => $data->resolved_date,
                'Action Performed' => $data->action_performed,
                'Uploaded Files'=> $data->uploaded_files,
                'Due Date'=> $data->due_date,
                'Priority'=> $data->priority,
                'Created By' => $data->created_by,
                'Created At' => $data->created_at
            ];
    	});
    }
}
