<?php

namespace App\Exports;

use App\Models\SubscriptioLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\User;
use App\Models\Meeting;
use App\Models\ActionItem;
use Auth;

class MeetingTaskExport implements FromCollection, WithHeadings
{
	use Exportable;

    public $meeting_id;

    public function __construct($meeting_id)
    {
        $this->meeting_id = $meeting_id;
    }


    public function headings(): array {
        return [
            'SNO',
            // 'Organised By',
            // 'Meet Random Id',
            'Meeting Title',
            // 'Meeting Ref No',
            // 'Agenda Of Meeting',
            // 'Meeting Date',
            // 'Meeting Time Start',
            // 'Meeting Time End',
            // 'Meeting Uid',
            // 'Meeting Link',
            // 'Meeting Status',
            'Responsibility',
            'Engineer Incharge',
            'Note',
            'Mm Ref Id',
            'Date Opened',
            'Task',
            'Priority',
            'Due Date',
            'Complete Percentage',
            'Status',
            'Complete Date',
            'Verified By',
            'Verified Date',
            'Comment',
            'Created By'
        ];
    }

    public function collection()
    {
        $meetingTasks = ActionItem::where('meeting_id',$this->meeting_id)->with('meeting')->get();
        return $meetingTasks->map(function ($data, $key) {
            return [
                'SNO' => $key+1,
                // 'organised_by' => $data->meeting->organiser->name,
                // 'Meet Random Id' => $data->meeting->meetRandomId,
                'Meeting Title' => $data->meeting->meeting_title,
                // 'Meeting Ref No' => $data->meeting->meeting_ref_no,
                // 'Agenda Of Meeting' => $data->meeting->agenda_of_meeting,
                // 'Meeting Date' => $data->meeting->meeting_date,
                // 'Meeting Time Start' => $data->meeting->meeting_time_start,
                // 'Meeting Time End' => $data->meeting->meeting_time_end,
                // 'Meeting Uid' => $data->meeting->meeting_uid,
                // 'Meeting Link' => $data->meeting->meeting_link,
                // 'Meeting Status' => $data->meeting->status == 1 ? 'Active' : 'Inactive',
                'Owner' => $data->owner->name,
                'Engineer Incharge' => @$data->owner->engineer_incharge,
                'Note' => strip_tags($data->note->notes),
                'Mm Ref Id' => $data->mm_ref_id,
                'Date Opened' => $data->date_opened,
                'Task' => $data->task,
                'Priority' => $data->priority,
                'Due Date' => $data->due_date,
                'Complete Percentage' => $data->complete_percentage,
                'Status' => $data->status,
                'Complete Date' => $data->complete_date,
                'Verified By' => @User::find($data->verified_by)->name,
                'Verified Date' => $data->verified_date,
                'Comment' => $data->comment,
                'Created By' => @User::find($data->created_by)->name,
            ];
        });
    }
}
