<?php

namespace App\Exports;

use App\Models\SubscriptioLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\User;
use App\Models\HindranceActivityLog;
use Auth;

class HindranceActivityLogExport implements FromCollection, WithHeadings
{
	use Exportable;

    public $hindrance_id;

    public function __construct($hindrance_id)
    {
        $this->hindrance_id = $hindrance_id;
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
        $hindranceActivityLogs = HindranceActivityLog::where('hindrance_id',$this->hindrance_id)->get();
    	return $hindranceActivityLogs->map(function ($data, $key) {
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
