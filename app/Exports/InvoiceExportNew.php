<?php

namespace App\Exports;

use App\Models\SubscriptioLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\User;
use App\Models\Invoice;
use App\Models\InvoiceTimeLog;
use Auth;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class InvoiceExportNew implements FromCollection, WithHeadings, WithColumnWidths
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
            'Created Date',
            'Invoice Date',
            'SAP PO No',
            'Contractor Name',
            // 'Package Name',
            'Epcm Name',
            // 'Owner Name',
            'Engineer Incharge',
            'Invoice No',
            'Bill No',
            'Basic Amount',
            'GST Amount',
            'Total Amount',
            'Status',
            'OverAll Delay In Days',
            'Delay From Last Status'
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
            $invoices->whereDate('created_at','>=',$this->data['from_date']);
        }
         if(!empty($this->data['to_date']))
        {
            $invoices->whereDate('created_at','<=',$this->data['to_date']);
        }
        $invoices = $invoices->get();
        return $invoices->map(function ($data, $key) {
            $contractor_name = User::withoutGlobalScope('user_id')->find($data->contractor_id)->name;

            $epcm_name = [];
            $owner_name = [];
            $epcmIds = json_decode($data->epcms);
            $epcmNames = User::withoutGlobalScope('user_id')
                ->whereIn('id', $epcmIds)
                ->get(['name']);
            foreach ($epcmNames as $key1 => $value) {
                $epcm_name[] = $value->name;
            }
            $epcm_name = implode(',', $epcm_name);

            // $ownerIds = json_decode($data->owners);
            // if (empty($ownerIds)) {
            //     $ownerIds = [$data->contractor->owner_id];
            // }
            // $ownerNames = User::withoutGlobalScope('user_id')
            //     ->whereIn('id', $ownerIds)
            //     ->get(['name']);
            // foreach ($ownerNames as $key2 => $value) {
            //     $owner_name[] = $value->name;
            // }
            // $owner_name = implode(',', $owner_name);

            $invoice_date = date_create($data->invoice_date ? $data->invoice_date : $data->created_at);
            $created_at = date_create($data->created_at);
            $invoice_date_only = date_create($data->invoice_date ? $data->invoice_date : $data->created_at);

            $paid_date = date_create($data->paid_date ? $data->paid_date : now());
            $timeLogCurr = InvoiceTimeLog::where('invoice_id',$data->id)->latest()->first();
            // $timeLog = InvoiceTimeLog::where('invoice_id',$data->id)->where('id','!=',$timeLogCurr->id)->latest()->first();
            // $opening_date = date_create($timeLog->opening_date ? $timeLog->opening_date : $timeLog->created_at);
            // $closing_date = date_create($timeLog->closing_date ? $timeLog->closing_date : $timeLogCurr->created_at);

            if(!empty($timeLogCurr))
            {
                $opening_date = date_create($timeLogCurr->created_at);
            }
            else
            {
                $opening_date = $created_at;
            }

            $closing_date = date_create(now());

            if($data->status == 'paid')
            {
                $oadid = NULL;
                $dfls = NULL;
            }
            else
            {
                $oadid = date_diff($created_at,$paid_date)->format("%R%a days");
                $dfls = date_diff($opening_date,$closing_date)->format("%R%a days");
            }

            
            return [
                'SNO' => $key+1,
                'Created Date' => $created_at->format('d M Y'),
                'Invoice Date' => $invoice_date_only->format('d M Y'),
                'SAP PO No' => $data->contract_number,
                'Contractor Name' => $contractor_name,
                // 'Package Name' => $data->package,
                'Epcm Name' => $epcm_name,
                // 'Owner Name' => $owner_name,
                'Engineer Incharge' => @$data->contractor->engineer_incharge,
                'Invoice No' => $data->invoice_no,
                'Bill No' => " " . $data->unique_no,
                'Basic Amount' => $data->basic_amount,
                'GST Amount' => $data->gst_amount,
                'Total Amount' => $data->basic_amount + $data->gst_amount,
                'Status' => $data->status,
                'OverAll Delay In Days' => $oadid,
                'Delay From Last Status' => $dfls
            ];
        });
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // SNO
            'B' => 20,  // SAP PO No
            'C' => 20,  // Contractor Name
            'D' => 20,  // EPCM Name
            'E' => 25,  // Engineer Incharge
            'F' => 20,  // Invoice No
            'G' => 20,  // Bill No
            'H' => 15,  // Basic Amount
            'I' => 15,  // GST Amount
            'J' => 15,  // Total Amount
            'K' => 20,  // Status
            'L' => 25,  // OverAll Delay In Days
            'M' => 25,  // Delay From Last Status
        ];
    }
}
