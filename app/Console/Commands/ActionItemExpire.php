<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Models\User;
use App\Models\ActionItem;
use Mail;
use App\Mail\ActionItemExpireMail;
use Illuminate\Support\Carbon;
class ActionItemExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:task-expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $allactionItems = ActionItem::whereDate('due_date','<',date('Y-m-d'))
            ->whereNotIn('status',['completed','verified'])
            ->get();

        foreach ($allactionItems as $value) 
        {
            $user = User::find($value->owner_id);
            if($user)
            {
                $content = [
                    "name" => $user->name,
                    "body" => 'Your Assigned Task for meeting  '.$value->meeting->meeting_title.' has excedded the due date.',
                ];

                if (env('IS_MAIL_ENABLE', false) == true) {
                   
                    $recevier = Mail::to($user->email)->send(new ActionItemExpireMail($content));
                }
            }
        }
        return;
    }
}
