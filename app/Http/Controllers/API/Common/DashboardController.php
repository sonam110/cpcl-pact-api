<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Auth;
use DB;
use App\Models\ActionItem;
use App\Models\MeetingMailLog;
use App\Models\Meeting;
use App\Models\MeetingDocument;
use App\Models\Attendee;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Hindrance;
use App\Models\Invoice;
use App\Mail\TaskReminderMail;
use Mail;
use App\Models\ActionItemController;
class DashboardController extends Controller
{
    //DashBoard--Report-Data
    public function dashboard()
    {
        try {
            $user = getUser();
            $data = [];
            // if($user->role_id == 1)
            // {
            //     $data['userCount'] = User::where('role_id','!=','1')->count();
            //     $data['meetingCount'] = Meeting::count();
            //     $data['todayMeetingCount'] = Meeting::whereDate('meeting_date',date('Y-m-d'))->count();
            // }
            // elseif($user->role_id == 2)
            // {

            // }


            // if ($user->can('user-browse'))
            // {
            //      $data['userCount'] =User::where('user_type','!=','1')->count();
            // }
            // if ($user->can('meeting-browse'))
            // {
            //     $data['meetingCount'] = Meeting::count();
            //     $data['todayMeetingCount'] = Meeting::whereDate('meeting_date',date('Y-m-d'))->count();
            // }
            // if ($user->can('hindrances-browse'))
            // {
            //     $data['hindranceCount'] = Hindrance::count();
            // }
            // if ($user->can('invoices-browse'))
            // {
            //     $data['InvoiceCount'] = Invoice::count();
            // }   

            if($user->kmeet == 1)
            {
                $data['userCount'] = User::where('role_id','!=','1')->count();
                $data['meetingCount'] = Meeting::count();
                $data['todayMeetingCount'] = Meeting::whereDate('meeting_date',date('Y-m-d'))->count();
            }
            if ($user->can('hindrances-browse'))
            {  
                if(auth()->user()->user_type == 2){
                    $data['hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->count();

                    $data['pending_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','Like','%'.'pending_with_owner'.'%')
                    ->count();

                    $data['under_review_by_emcm_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','under_review_by_emcm')
                    ->count();

                    $data['under_review_by_owner_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','under_review_by_owner')
                    ->count();

                    $data['on_hold_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','on_hold')
                    ->count();

                    $data['rejected_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->whereIn('status',['rejected_by_epcm','rejected_by_owner','rejected_by_admin'])
                    ->count();

                    $data['resend_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','resend')
                    ->count();

                    $data['verified_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','verified')
                    ->count();

                    $data['approved_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','approved')
                    ->count();

                    $data['resolved_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','resolved')
                    ->count();
                }
                elseif(auth()->user()->user_type == 3){
                    $data['hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('epcm_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->count();

                    $data['pending_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('epcm_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','Like','%'.'pending_with_epcm'.'%')
                    ->count();

                    $data['under_review_by_emcm_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('epcm_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','under_review_by_emcm')
                    ->count();

                    $data['under_review_by_owner_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('epcm_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','under_review_by_owner')
                    ->count();

                    $data['on_hold_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('epcm_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','on_hold')
                    ->count();

                    $data['rejected_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('epcm_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->whereIn('status',['rejected_by_epcm','rejected_by_owner','rejected_by_admin'])
                    ->count();

                    $data['resend_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('epcm_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','resend')
                    ->count();

                    $data['verified_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('epcm_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','verified')
                    ->count();

                    $data['approved_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('epcm_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','approved')
                    ->count();

                    $data['resolved_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('epcm_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','resolved')
                    ->count();
                }
                elseif(auth()->user()->user_type == 4){
                    $data['hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('contractor_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->count();

                    $data['pending_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('contractor_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','Like','%'.'pending_with'.'%')
                    ->count();

                    $data['under_review_by_emcm_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('contractor_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','under_review_by_emcm')
                    ->count();

                    $data['under_review_by_owner_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('contractor_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','under_review_by_owner')
                    ->count();

                    $data['on_hold_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('contractor_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','on_hold')
                    ->count();

                    $data['rejected_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('contractor_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->whereIn('status',['rejected_by_epcm','rejected_by_owner','rejected_by_admin'])
                    ->count();

                    $data['resend_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('contractor_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','resend')
                    ->count();

                    $data['verified_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('contractor_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','verified')
                    ->count();

                    $data['approved_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('contractor_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','approved')
                    ->count();

                    $data['resolved_hindranceCount'] = Hindrance::select('hindrances.*')
                    ->leftJoin('hindrance_assignees', function($join){
                        $join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
                    })
                    ->distinct(['hindrances.id'])
                    ->where(function($q) {
                        $q->where('contractor_id', auth()->id())
                        ->orWhere('created_by', auth()->id())
                        ->orWhere('hindrance_assignees.assigned_to', auth()->id());
                    })
                    ->where('status','resolved')
                    ->count();
                }
                else
                {

                    $data['hindranceCount'] = Hindrance::count();
                    $data['pending_hindranceCount'] = Hindrance::whereIn('status',['pending_with_epcm','pending_with_owner'])->count();
                    $data['under_review_by_emcm_hindranceCount'] = Hindrance::where('status','under_review_by_emcm')->count();
                    $data['under_review_by_owner_hindranceCount'] = Hindrance::where('status','under_review_by_owner')->count();
                    $data['on_hold_hindranceCount'] = Hindrance::where('status','on_hold')->count();
                    $data['rejected_hindranceCount'] = Hindrance::whereIn('status',['rejected_by_epcm','rejected_by_owner','rejected_by_admin'])->count();
                    $data['resend_hindranceCount'] = Hindrance::where('status','resend')->count();
                    $data['verified_hindranceCount'] = Hindrance::where('status','verified')->count();
                    $data['approved_hindranceCount'] = Hindrance::where('status','approved')->count();
                    $data['resolved_hindranceCount'] = Hindrance::where('status','resolved')->count();
                }
            }
            if ($user->can('invoices-browse'))
            {
                if(auth()->user()->user_type == 2){
                    $data['InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.owners', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_to_contractor_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.owners', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','returned_to_contractor')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['pending_with_owner_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.owners', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','pending_with_owner')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_to_epcm_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.owners', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','returned_to_epcm')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['pending_with_epcm_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.owners', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','pending_with_epcm')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_to_owner_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.owners', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','returned_to_owner')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['approved_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.owners', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->whereIn('invoices.status',['pending_with_owner_finance','pending_with_treasurer'])
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['paid_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.owners', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','paid')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.owners', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','LIKE','%'.'returned_'.'%')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['pending_with_owner_finance_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.owners', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->whereIn('invoices.status',['pending_with_owner_finance','pending_with_treasurer'])
                    ->distinct(['invoices.id'])
                    ->count();
                }
                elseif(auth()->user()->user_type == 3){
                    $data['InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.epcms', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_to_contractor_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.epcms', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','returned_to_contractor')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['pending_with_owner_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.epcms', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','pending_with_owner')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_to_epcm_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.epcms', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','returned_to_epcm')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['pending_with_epcm_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.epcms', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','pending_with_epcm')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_to_owner_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.epcms', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','returned_to_owner')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['approved_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.epcms', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->whereIn('invoices.status',['pending_with_owner_finance','pending_with_treasurer'])
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['paid_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.epcms', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','paid')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.epcms', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','LIKE','%'.'returned_'.'%')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['pending_with_owner_finance_InvoiceCount'] = Invoice::where(function($q) {
                        $q->whereJsonContains('invoices.epcms', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->whereIn('invoices.status',['pending_with_owner_finance','pending_with_treasurer'])
                    ->distinct(['invoices.id'])
                    ->count();
                }
                elseif(auth()->user()->user_type == 4){
                    $data['InvoiceCount'] = Invoice::where(function($q) {
                        $q->where('invoices.contractor_id', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_to_contractor_InvoiceCount'] = Invoice::where(function($q) {
                        $q->where('invoices.contractor_id', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','returned_to_contractor')
                    ->distinct(['invoices.id'])
                    ->count();


                    $data['pending_with_owner_InvoiceCount'] = Invoice::where(function($q) {
                        $q->where('invoices.contractor_id', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','pending_with_owner')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_to_epcm_InvoiceCount'] = Invoice::where(function($q) {
                        $q->where('invoices.contractor_id', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','returned_to_epcm')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['pending_with_epcm_InvoiceCount'] = Invoice::where(function($q) {
                        $q->where('invoices.contractor_id', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','pending_with_epcm')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_to_owner_InvoiceCount'] = Invoice::where(function($q) {
                        $q->where('invoices.contractor_id', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','returned_to_owner')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['approved_InvoiceCount'] = Invoice::where(function($q) {
                        $q->where('invoices.contractor_id', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->whereIn('invoices.status',['pending_with_owner_finance','pending_with_treasurer'])
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['paid_InvoiceCount'] = Invoice::where(function($q) {
                        $q->where('invoices.contractor_id', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','paid')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['returned_InvoiceCount'] = Invoice::where(function($q) {
                        $q->where('invoices.contractor_id', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->where('invoices.status','LIKE','%'.'returned_'.'%')
                    ->distinct(['invoices.id'])
                    ->count();

                    $data['pending_with_owner_finance_InvoiceCount'] = Invoice::where(function($q) {
                        $q->where('invoices.contractor_id', auth()->id())
                        ->orWhere('invoices.created_by', auth()->id());
                    })
                    ->whereIn('invoices.status',['pending_with_owner_finance','pending_with_treasurer'])
                    ->distinct(['invoices.id'])
                    ->count();
                }
                else
                {
                    $data['InvoiceCount'] = Invoice::count();
                    $data['returned_to_contractor_InvoiceCount'] = Invoice::where('status','returned_to_contractor')->count();
                    $data['pending_with_owner_InvoiceCount'] = Invoice::where('status','pending_with_owner')->count();
                    $data['returned_to_owner_InvoiceCount'] = Invoice::where('status','returned_to_owner')->count();
                    $data['pending_with_epcm_InvoiceCount'] = Invoice::where('status','pending_with_epcm')->count();
                    $data['returned_to_epcm_InvoiceCount'] = Invoice::where('status','returned_to_epcm')->count();
                    
                    $data['approved_InvoiceCount'] = Invoice::whereIn('status',['pending_with_treasurer','pending_with_owner_finance'])->count();
                    $data['paid_InvoiceCount'] = Invoice::where('status','paid')->count();
                    $data['returned_InvoiceCount'] = Invoice::whereIn('status',['returned_to_contractor','returned_to_epcm','returned_to_owner'])->count();
                    $data['pending_with_owner_finance_InvoiceCount'] = Invoice::whereIn('invoices.status',['pending_with_owner_finance','pending_with_treasurer'])->count();
                }

                // //----------------Total Invoice Count----------------//
                // if(auth()->user()->user_type == 2){
                //     $data['InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.owners', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                // //     ->count();
                // }
                // elseif(auth()->user()->user_type == 3){
                //     $data['InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.epcms', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 4){
                //     $data['InvoiceCount'] = Invoice::where(function($q) {
                //         $q->where('invoices.contractor_id', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // else
                // {
                //     $data['InvoiceCount'] = Invoice::count();
                // }

                // //----------------Total Pendin With Owner Invoice Count----------------//
                // if(auth()->user()->user_type == 2){
                //     $data['pending_with_owner_InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.owners', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                // //     ->where('invoices.status','pending_with_owner')
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 3){
                //     $data['pending_with_owner_InvoiceCount'] = Invoice::where('invoices.status','pending_with_owner')
                //     ->where(function($q) {
                //         $q->whereJsonContains('invoices.epcms', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 4){
                //     $data['pending_with_owner_InvoiceCount'] = Invoice::where('invoices.status','pending_with_owner')
                //     ->where(function($q) {
                //         $q->where('invoices.contractor_id', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // else
                // {
                //     $data['pending_with_owner_InvoiceCount'] = Invoice::where('invoices.status','pending_with_owner')
                //     ->count();
                // }

                // //----------------Total Returned To Owner Invoice Count----------------//
                // if(auth()->user()->user_type == 2){
                //     $data['returned_to_owner_InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.owners', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                // //     ->where('invoices.status','returned_to_owner')
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 3){
                //     $data['returned_to_owner_InvoiceCount'] = Invoice::where('invoices.status','returned_to_owner')
                //     ->where(function($q) {
                //         $q->whereJsonContains('invoices.epcms', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 4){
                //     $data['returned_to_owner_InvoiceCount'] = Invoice::where('invoices.status','returned_to_owner')
                //     ->where(function($q) {
                //         $q->where('invoices.contractor_id', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // else
                // {
                //     $data['returned_to_owner_InvoiceCount'] = Invoice::where('invoices.status','returned_to_owner')
                //     ->count();
                // }

                // //----------------Total Pendin With Epcm Invoice Count----------------//
                // if(auth()->user()->user_type == 2){
                //     $data['pending_with_epcm_InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.owners', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                // //     ->where('invoices.status','pending_with_epcm')
                //     // ->where(function($q) {
                //     //     $q->whereJsonContains('invoices.epcms', auth()->id())
                //     //     ->orWhere('invoices.created_by', auth()->id());
                //     // })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 3){
                //     $data['pending_with_epcm_InvoiceCount'] = Invoice::where('invoices.status','pending_with_epcm')
                //     ->where(function($q) {
                //         $q->whereJsonContains('invoices.epcms', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 4){
                //     $data['pending_with_epcm_InvoiceCount'] = Invoice::where('invoices.status','pending_with_epcm')
                //     ->where(function($q) {
                //         $q->where('invoices.contractor_id', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // else
                // {
                //     $data['pending_with_epcm_InvoiceCount'] = Invoice::where('invoices.status','pending_with_epcm')
                //     ->count();
                // }

                // //----------------Total Returned To Epcm Invoice Count----------------//
                // if(auth()->user()->user_type == 2){
                //     $data['returned_to_epcm_InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.owners', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                // //     ->where('invoices.status','returned_to_epcm')
                //     // ->where(function($q) {
                //     //     $q->whereJsonContains('invoices.epcms', auth()->id())
                //     //     ->orWhere('invoices.created_by', auth()->id());
                //     // })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 3){
                //     $data['returned_to_epcm_InvoiceCount'] = Invoice::where('invoices.status','returned_to_epcm')
                //     ->where(function($q) {
                //         $q->whereJsonContains('invoices.epcms', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 4){
                //     $data['returned_to_epcm_InvoiceCount'] = Invoice::where('invoices.status','returned_to_epcm')
                //     ->where(function($q) {
                //         $q->where('invoices.contractor_id', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // else
                // {
                //     $data['returned_to_epcm_InvoiceCount'] = Invoice::where('invoices.status','returned_to_epcm')
                //     ->count();
                // }

                // //----------------Total Returned To Contractor Invoice Count----------------//
                // if(auth()->user()->user_type == 2){
                //     $data['returned_to_contractor_InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.owners', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                // //     ->where('invoices.status','returned_to_contractor')
                //     // ->where(function($q) {
                //     //     $q->where('invoices.contractor_id', auth()->id())
                //     //     ->orWhere('invoices.created_by', auth()->id());
                //     // })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 3){
                //     $data['returned_to_contractor_InvoiceCount'] = Invoice::where('invoices.status','returned_to_contractor')
                //     ->where(function($q) {
                //         $q->whereJsonContains('invoices.epcms', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 4){
                //     $data['returned_to_contractor_InvoiceCount'] = Invoice::where('invoices.status','returned_to_contractor')
                //     ->where(function($q) {
                //         $q->where('invoices.contractor_id', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // else
                // {
                //     $data['returned_to_contractor_InvoiceCount'] = Invoice::where('invoices.status','returned_to_contractor')
                //     ->count();
                // }

                // //----------------Total Returned To Owner Invoice Count----------------//
                // if(auth()->user()->user_type == 2){
                //     $data['returned_to_owner_InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.owners', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                // //     ->where('invoices.status','returned_to_owner')
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 3){
                //     $data['returned_to_owner_InvoiceCount'] = Invoice::where('invoices.status','returned_to_owner')
                //     ->where(function($q) {
                //         $q->whereJsonContains('invoices.epcms', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 4){
                //     $data['returned_to_owner_InvoiceCount'] = Invoice::where('invoices.status','returned_to_owner')
                //     ->where(function($q) {
                //         $q->where('invoices.contractor_id', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // else
                // {
                //     $data['returned_to_owner_InvoiceCount'] = Invoice::where('invoices.status','returned_to_owner')
                //     ->count();
                // }

                // //----------------Total Approved Invoice Count----------------//
                // if(auth()->user()->user_type == 2){
                //     $data['approved_InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.owners', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                // //     ->whereIn('invoices.status',['pending_with_treasurer','approved'])
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 3){
                //     $data['approved_InvoiceCount'] = Invoice::whereIn('invoices.status',['pending_with_treasurer','approved'])
                //     ->where(function($q) {
                //         $q->whereJsonContains('invoices.epcms', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 4){
                //     $data['approved_InvoiceCount'] = Invoice::whereIn('invoices.status',['pending_with_treasurer','approved'])
                //     ->where(function($q) {
                //         $q->where('invoices.contractor_id', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // else
                // {
                //     $data['approved_InvoiceCount'] = Invoice::whereIn('invoices.status',['pending_with_treasurer','approved'])
                //     ->count();
                // }


                // //----------------Total Paid Invoice Count----------------//
                // if(auth()->user()->user_type == 2){
                //     $data['paid_InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.owners', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                // //     ->where('invoices.status','paid')
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 3){
                //     $data['paid_InvoiceCount'] = Invoice::where('invoices.status','paid')
                //     ->where(function($q) {
                //         $q->whereJsonContains('invoices.epcms', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 4){
                //     $data['paid_InvoiceCount'] = Invoice::where('invoices.status','paid')
                //     ->where(function($q) {
                //         $q->where('invoices.contractor_id', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // else
                // {
                //     $data['paid_InvoiceCount'] = Invoice::where('invoices.status','paid')
                //     ->count();
                // }

                // //----------------Total Returned Invoice Count----------------//
                // if(auth()->user()->user_type == 2){
                //     $data['returned_InvoiceCount'] = Invoice::where(function($q) {
                //         $q->whereJsonContains('invoices.owners', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                // //     ->where('invoices.status','LIKE','%'.'returned'.'%')
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 3){
                //     $data['returned_InvoiceCount'] = Invoice::where('invoices.status','LIKE','%'.'returned'.'%')
                //     ->where(function($q) {
                //         $q->whereJsonContains('invoices.epcms', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // elseif(auth()->user()->user_type == 4){
                //     $data['returned_InvoiceCount'] = Invoice::where('invoices.status','LIKE','%'.'returned'.'%')
                //     ->where(function($q) {
                //         $q->where('invoices.contractor_id', auth()->id())
                //         ->orWhere('invoices.created_by', auth()->id());
                //     })
                //     ->count();
                // }
                // else
                // {
                //     $data['returned_InvoiceCount'] = Invoice::where('invoices.status','LIKE','%'.'returned'.'%')
                //     ->count();
                // }
            }

            return prepareResult(false, $data, 'Dashboard' ,config('httpcodes.success'));    
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }  
    }
}
