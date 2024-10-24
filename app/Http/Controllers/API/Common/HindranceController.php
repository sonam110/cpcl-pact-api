<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HindranceAssignee;
use App\Models\Hindrance;
use App\Models\HindranceTimeLog;
use App\Models\HindranceActivityLog;
use App\Models\Notification;
use App\Models\User;
use App\Models\HindranceType;
use Validator;
use Auth;
use Exception;
use DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\HindranceExport;
use App\Exports\HindranceActivityLogExport;
class HindranceController extends Controller
{
	public function __construct()
	{
		$this->middleware('permission:hindrances-browse',['only' => ['hindrances','show']]);
		$this->middleware('permission:hindrances-add', ['only' => ['store']]);
		$this->middleware('permission:hindrances-action', ['only' => ['action']]);
		$this->middleware('permission:hindrances-edit', ['only' => ['update']]);
        // $this->middleware('permission:hindrances-read', ['only' => ['show']]);
        // $this->middleware('permission:hindrances-delete', ['only' => ['destroy']]);
		$this->middleware('permission:hindrances-export', ['only' => ['hindranceExport']]);
		$this->middleware('permission:hindrances-log', ['only' => ['hindranceActivityLogExport','viewLog']]);
	}
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //listing hindrances   
    public function hindrances(Request $request)
    {
    	try {
    		$column = 'id';
    		$dir = 'Desc';
    		if(!empty($request->sort))
    		{
    			if(!empty($request->sort['column']))
    			{
    				$column = $request->sort['column'];
    			}
    			if(!empty($request->sort['dir']))
    			{
    				$dir = $request->sort['dir'];
    			}
    		}
    		$query = Hindrance::select('hindrances.*')
    		->leftJoin('hindrance_assignees', function($join){
    			$join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
    		})->distinct(['hindrances.id'])->orderBy($column,$dir)->with('epcm:id,name,email,mobile_number','owner:id,name,email,mobile_number','contractor:id,name,email','assignees','contract','hindranceTimeLogs');
    		if(auth()->user()->user_type == 2){
    			$query->where(function($q) {
    				$q->where('owner_id', auth()->id())
    				->orWhere('created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 3){
    			$query->where(function($q) {
    				$q->where('epcm_id', auth()->id())
    				->orWhere('created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 4){
    			$query->where(function($q) {
    				$q->where('contractor_id', auth()->id())
    				->orWhere('created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			});
    		}

    		if(!empty($request->epcm_id))
    		{
    			$query->where('epcm_id',$request->epcm_id);
    		}
    		if(!empty($request->contractor_id))
    		{
    			$query->where('contractor_id',$request->contractor_id);
    		}
    		if(!empty($request->owner_id))
    		{
    			$query->where('owner_id',$request->owner_id);
    		}

    		if(!empty($request->created_at))
    		{
    			$query->where('created_at',$request->created_at);
    		}
    		if(!empty($request->priority))
    		{
    			$query->where('priority',$request->priority);
    		}

    		// if(!empty($request->title))
    		// {
    		// 	$query->where('title', 'LIKE', '%'.$request->title.'%');
    		// }

    		if(!empty($request->hindrance_code))
    		{
    			$query->where('hindrance_code', 'LIKE', '%'.$request->hindrance_code.'%');
    		}

    		if(!empty($request->status))
    		{
    			if ($request->status == 'all') {
                    # code...
    			}
    			else
    			{
    				$query->where('status',$request->status);
    			}
    		}
    		if(!empty($request->status_arr))
    		{
    			{
    				$query->whereIn('status',$request->status_arr);
    			}
    		}
    		if(!empty($request->approved_date))
    		{
    			$query->where('approved_date',$request->approved_date);
    		}

    		if(!empty($request->per_page_record))
    		{
    			$perPage = $request->per_page_record;
    			$page = $request->input('page', 1);
    			$total = $query->count();
    			$result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

    			$pagination =  [
    				'data' => $result,
    				'total' => $total,
    				'current_page' => $page,
    				'per_page' => $perPage,
    				'last_page' => ceil($total / $perPage)
    			];
    			$query = $pagination;
    		}
    		else
    		{
    			$query = $query->get();
    		}

    		return response(prepareResult(false, $query, trans('translate.hindrance_list')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //creating new hindrance
    public function store(Request $request)
    {
    	$validation = \Validator::make($request->all(), [
    		'contract_number'      => 'required|exists:contracts,contract_number',
    		'contractor_id' => 'required|exists:users,id',
    		'vendor_name'      => 'required',
    		// 'vendor_contact_number'      => 'required|numeric',
    		'vendor_contact_email'      => 'required|email',
    		// 'project_id' => 'required|exists:projects,id'
    	]);

    	if ($validation->fails()) {
    		return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
    	}

    	DB::beginTransaction();
    	try {


    		$contractor = User::find($request->contractor_id);

    		if(auth()->user()->user_type == 3)
    		{
    			$status = 'pending_with_owner';
    			$current_user_id = $contractor->owner_id;
    		}
    		else
    		{
    			$status = 'pending_with_epcm';
    			$current_user_id = $contractor->epcm_id;
    		}

    		// $check_pon = Hindrance::where('contract_number',$request->contract_number)->count();
    		// if($check_pon > 0)
    		// {
    		// 	return response(prepareResult(true, ['Dublicate Purchase Order Number'], trans('translate.purchase_order_number_dublicate')),config('httpcodes.created'));
    		// }
    		$hindrance = new Hindrance;
    		$hindrance->contractor_id   = $contractor->id;
    		// $hindrance->project_id      = $request->project_id;
    		$hindrance->hindrance_code  = time().rand(1000,9999);
    		$hindrance->hindrance_type  = $request->hindrance_type;
    		$hindrance->contract_number = $request->contract_number;
    		$hindrance->hindrance_date  = $request->hindrance_date;
    		$hindrance->contacted_person= $request->contacted_person;
    		$hindrance->description     = $request->description;
    		$hindrance->package         = $request->package;
    		$hindrance->uploaded_files  = json_encode($request->uploaded_files);
    		$hindrance->vendor_name 	= $request->vendor_name;
    		$hindrance->vendor_contact_number = $request->vendor_contact_number;
    		$hindrance->vendor_contact_email = $request->vendor_contact_email;
    		$hindrance->status 			= $status;
    		$hindrance->epcm_id 		= $contractor->epcm_id;
    		$hindrance->owner_id 		= $contractor->owner_id;
    		$hindrance->notes 			= $request->notes;
    		$hindrance->due_date        = $request->due_date;
    		$hindrance->priority        = $request->priority;
    		$hindrance->creator_user_type     = auth()->user()->user_type;
    		$hindrance->created_by 		= auth()->id();
    		$hindrance->save();

            //notify admin about new hindrance
    		$notification = new Notification;
    		$notification->user_id              = $contractor->owner_id;
    		$notification->sender_id            = auth()->id();
    		$notification->status_code          = 'success';
    		$notification->type                	= 'Hindrance';
    		$notification->title                = 'New Issue Raised';
    		$notification->message              = 'New Issue wit SAP PO number '.$hindrance->contract_number.' Raised.';
    		$notification->read_status          = false;
    		$notification->data_id              = $hindrance->id;
    		$notification->save();

    		$notification = new Notification;
    		$notification->user_id              = $contractor->epcm_id;
    		$notification->sender_id            = auth()->id();
    		$notification->status_code          = 'success';
    		$notification->type                	= 'Hindrance';
    		$notification->title                = 'New Issue  Raised';
    		$notification->message              = 'New Issue with SAP PO number '.$hindrance->contract_number.' Raised.';
    		$notification->read_status          = false;
    		$notification->data_id              = $hindrance->id;
    		$notification->save();

    		$hindranceActivityLog = new HindranceActivityLog;
    		$hindranceActivityLog->hindrance_id = $hindrance->id;
    		$hindranceActivityLog->performed_by = auth()->id();
    		$hindranceActivityLog->action = 'hindrance-created';
    		$hindranceActivityLog->description = 'new hindrance raised';
    		$hindranceActivityLog->save();

            //creating time log

    		$hindranceTimeLog = new HindranceTimeLog;
    		$hindranceTimeLog->hindrance_id = $hindrance->id;
    		$hindranceTimeLog->current_user_id = $current_user_id;
    		$hindranceTimeLog->status = $status;
    		$hindranceTimeLog->opening_date = date('Y-m-d');
    		$hindranceTimeLog->closing_date = NULL;
    		$hindranceTimeLog->save();

    		DB::commit();
    		return response(prepareResult(false, $hindrance, trans('translate.hindrance_created')),config('httpcodes.created'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		DB::rollback();
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //view hindrance
    public function show(Hindrance $hindrance)
    {
    	try
    	{
    		if(auth()->user()->can('hindrances-log'))
    		{
    			$hindrance = Hindrance::with('epcm:id,name,email,mobile_number','owner:id,name,email,mobile_number','contractor:id,name,email','contract','assignees','hindranceActivityLogs','hindranceTimeLogs.currentUser:id,name,email')->find($hindrance->id);
    		}
    		else
    		{
    			$hindrance = Hindrance::with('epcm:id,name,email,mobile_number','owner:id,name,email,mobile_number','contractor:id,name,email','contract','assignees','hindranceTimeLogs.currentUser:id,name,email')->find($hindrance->id);

    		}
    		return response(prepareResult(false, $hindrance, trans('translate.hindrance_detail')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //update hindrances data
    public function update(Request $request, $id)
    {
    	$validation = \Validator::make($request->all(), [
    		'contract_number'      => 'required|exists:contracts,contract_number',
    		'contractor_id' => 'required|exists:users,id',
    		'vendor_name'      => 'required',
    		// 'vendor_contact_number'      => 'required|numeric',
    		'vendor_contact_email'      => 'required|email',
    		// 'project_id' => 'required|exists:projects,id'
    	]);

    	if ($validation->fails()) {
    		return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
    	}

    	DB::beginTransaction();
    	try {
    		$hindrance = Hindrance::where('id',$id)->first();
    		if(!$hindrance)
    		{
    			return response(prepareResult(true, [],trans('translate.record_not_found')), config('httpcodes.not_found'));
    		}

    		// $check_pon = Hindrance::where('contract_number',$request->contract_number)->where('id','!=',$id)->count();
    		// if($check_pon > 0)
    		// {
    		// 	return response(prepareResult(true, ['Dublicate Purchase Order Number'], trans('translate.purchase_order_number_dublicate')),config('httpcodes.created'));
    		// }

    		$contractor = User::find($request->contractor_id); 

    		if(auth()->user()->user_type == 3)
    		{
    			$status = 'pending_with_owner';
    			$current_user_id = $contractor->owner_id;
    		}
    		else
    		{
    			$status = 'pending_with_epcm';
    			$current_user_id = $contractor->epcm_id;
    		}

    		$hindrance->contractor_id   		= $contractor->id;
    		$hindrance->hindrance_type  		= $request->hindrance_type;
    		$hindrance->contract_number 		= $request->contract_number;
    		$hindrance->hindrance_date  		= $request->hindrance_date;
    		$hindrance->contacted_person		= $request->contacted_person;
    		$hindrance->description     		= $request->description;
    		$hindrance->package         		= $request->package;
    		$hindrance->uploaded_files  		= json_encode($request->uploaded_files);
    		$hindrance->vendor_name     		= $request->vendor_name;
    		$hindrance->vendor_contact_number 	= $request->vendor_contact_number;
    		$hindrance->vendor_contact_email 	= $request->vendor_contact_email;
    		$hindrance->status            		= $status;
    		$hindrance->epcm_id         		= $contractor->epcm_id;
    		$hindrance->owner_id        		= $contractor->owner_id;
    		$hindrance->notes           		= $request->notes;
    		$hindrance->due_date        		= $request->due_date;
    		$hindrance->reason_of_rejection     = $hindrance->reason_of_rejection;
    		$hindrance->rejection_update_description     = $request->rejection_update_description;
    		$hindrance->priority        		= $request->priority;
    		$hindrance->save();

    		$hindranceActivityLog = new HindranceActivityLog;
    		$hindranceActivityLog->hindrance_id = $hindrance->id;
    		$hindranceActivityLog->performed_by = auth()->id();
    		$hindranceActivityLog->action = $request->description;
    		$hindranceActivityLog->description = $request->rejection_update_description;
    		$hindranceActivityLog->save();


            //if user take status resend then only,  epcms and owner will be notified 

    		if($request->status == 'resend')
    		{
    			$notification = new Notification;
    			$notification->user_id              = $contractor->owner_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Issue updated';
    			$notification->message              = 'Issue '.$hindrance->contract_number.' updated.';
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();

    			$notification = new Notification;
    			$notification->user_id              = $contractor->epcm_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Issue updated';
    			$notification->message              = 'Issue '.$hindrance->contract_number.' updated.';
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();

                //creating time log

    			$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    			$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    			$hindranceTimeLog = new HindranceTimeLog;
    			$hindranceTimeLog->hindrance_id = $hindrance->id;
    			$hindranceTimeLog->current_user_id = $current_user_id;
    			$hindranceTimeLog->status = $status;
    			$hindranceTimeLog->opening_date = date('Y-m-d');
    			$hindranceTimeLog->closing_date = NULL;
    			$hindranceTimeLog->save();
    		}



    		DB::commit();
    		return response(prepareResult(false, $hindrance, trans('translate.hindrance_updated')),config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		DB::rollback();
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //delete hindrance
    public function destroy($id)
    {
    	try {
    		$hindrance= Hindrance::where('id',$id)->first();
    		if (!is_object($hindrance)) {
    			return response(prepareResult(true, [],trans('translate.record_not_found')), config('httpcodes.not_found'));
    		}

    		$deleteHindrance = $hindrance->delete();
    		return response(prepareResult(false, [], trans('translate.hindrance_deleted')), config('httpcodes.success'));
    	}
    	catch(Exception $exception) {
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    //performed action on hindrances
    public function action(Request $request)
    {
    	$validation = \Validator::make($request->all(), [
    		'ids.*'      => 'required|exists:hindrances,id',
    		'action'      => 'required',
    	]);

    	if ($validation->fails()) {
    		return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
    	}
    	DB::beginTransaction();
    	try 
    	{

    		$ids = $request->ids;
    		$message = trans('translate.invalid_action');

    		$hindrances = Hindrance::whereIn('id',$ids)->get();
    		foreach ($hindrances as $key => $hindrance) {
    			if($request->action == 'delete')
    			{
    				$hindrance->delete();
    				$message = trans('translate.deleted');
    			}
    			elseif($request->action == 'rejected')
    			{
    				if(auth()->user()->user_type == 1)
    				{
    					$status = 'rejected_by_admin';
    				}
    				elseif(auth()->user()->user_type == 2)
    				{
    					$status = 'rejected_by_owner';
    				}
    				elseif(auth()->user()->user_type == 3)
    				{
    					$status = 'rejected_by_epcm';
    				}
    				$hindrance->update(['status'=>$status,"reason_of_rejection" => $request->reason_of_rejection]);
    				$message = trans('translate.rejected');
    				if (auth()->id() != $hindrance->contractor_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->contractor_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Rejected';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been rejected  by '.auth()->user()->name.' because of '.$request->reason_of_rejection;
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->owner_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->owner_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Rejected';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been rejected  by '.auth()->user()->name.' because of '.$request->reason_of_rejection;
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->epcm_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->epcm_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Rejected';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been rejected  by '.auth()->user()->name.' because of '.$request->reason_of_rejection;
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				$hindranceActivityLog = new HindranceActivityLog;
    				$hindranceActivityLog->hindrance_id = $hindrance->id;
    				$hindranceActivityLog->performed_by = auth()->id();
    				$hindranceActivityLog->action = 'hindrance-rejected';
    				$hindranceActivityLog->description = $request->reason_of_rejection;
    				$hindranceActivityLog->save();

                    //creating time log

    				$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    				$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    				$hindranceTimeLog = new HindranceTimeLog;
    				$hindranceTimeLog->hindrance_id = $hindrance->id;
    				$hindranceTimeLog->current_user_id = $hindrance->created_by;
    				$hindranceTimeLog->status = 'rejected';
    				$hindranceTimeLog->opening_date = date('Y-m-d');
    				$hindranceTimeLog->closing_date = NULL;
    				$hindranceTimeLog->save();
    			}
    			elseif($request->action == 'pending_with_owner')
    			{
    				Hindrance::whereIn('id',$ids)->update(['status'=>"pending_with_owner"]);
    				$message = trans('translate.pending_with_owner');
    				if (auth()->id() != $hindrance->contractor_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->contractor_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Pending With Owner';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is pending with owner.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->owner_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->owner_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Pending With Owner';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is pending with owner.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->epcm_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->epcm_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Pending With Owner';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is pending with owner.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				$hindranceActivityLog = new HindranceActivityLog;
    				$hindranceActivityLog->hindrance_id = $hindrance->id;
    				$hindranceActivityLog->performed_by = auth()->id();
    				$hindranceActivityLog->action = 'hindrance-pending-with-owner';
    				$hindranceActivityLog->description = 'Hindrance Pending With Owner';
    				$hindranceActivityLog->save();

                    //creating time log

    				$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    				$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    				$hindranceTimeLog = new HindranceTimeLog;
		    		$hindranceTimeLog->hindrance_id = $hindrance->id;
		    		$hindranceTimeLog->current_user_id = auth()->id();
		    		$hindranceTimeLog->status = 'approved';
		    		$hindranceTimeLog->opening_date = date('Y-m-d');
		    		$hindranceTimeLog->closing_date = date('Y-m-d');
		    		$hindranceTimeLog->save();

    				$hindranceTimeLog = new HindranceTimeLog;
    				$hindranceTimeLog->hindrance_id = $hindrance->id;
    				$hindranceTimeLog->current_user_id = $hindrance->owner_id;
    				$hindranceTimeLog->status = 'pending_with_owner';
    				$hindranceTimeLog->opening_date = date('Y-m-d');
    				$hindranceTimeLog->closing_date = NULL;
    				$hindranceTimeLog->save();
    			}
    			elseif($request->action == 're-assign')
    			{
    				$hindrance->update(['status'=>"re-assigned"]);
    				$message = trans('translate.re-assigned');
    				if (auth()->id() != $hindrance->contractor_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->contractor_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Re-Assigned';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been re-assigned  by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->owner_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->owner_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Re-Assigned';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been re-assigned  by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->epcm_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->epcm_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Re-Assigned';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been re-assigned  by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				$hindranceActivityLog = new HindranceActivityLog;
    				$hindranceActivityLog->hindrance_id = $hindrance->id;
    				$hindranceActivityLog->performed_by = auth()->id();
    				$hindranceActivityLog->action = 'hindrance-re-assigned';
    				$hindranceActivityLog->description = 'hindrance-re-assigned';
    				$hindranceActivityLog->save();

    				$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    				$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    				$hindranceTimeLog = new HindranceTimeLog;
    				$hindranceTimeLog->hindrance_id = $hindrance->id;
    				$hindranceTimeLog->current_user_id = $request->assigned_to ? $request->assigned_to : $hindrance->created_by;
    				$hindranceTimeLog->status = 're-assign';
    				$hindranceTimeLog->opening_date = date('Y-m-d');
    				$hindranceTimeLog->closing_date = NULL;
    				$hindranceTimeLog->save();
    			}
    			elseif($request->action == 'resolved')
    			{
    				$hindrance->update(['status'=>"resolved",'resolved_date'=>date('Y-m-d'),'action_performed'=>$request->action_performed]);
    				$message = trans('translate.resolved');
    				if (auth()->id() != $hindrance->contractor_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->contractor_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Resolved';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been resolved by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->owner_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->owner_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Resolved';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been resolved by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->epcm_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->epcm_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Resolved';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been resolved by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				$hindranceActivityLog = new HindranceActivityLog;
    				$hindranceActivityLog->hindrance_id = $hindrance->id;
    				$hindranceActivityLog->performed_by = auth()->id();
    				$hindranceActivityLog->action = 'hindrance-resolved';
    				$hindranceActivityLog->description = 'hindrance resolved';
    				$hindranceActivityLog->save();

    				$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    				$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    				$hindranceTimeLog = new HindranceTimeLog;
    				$hindranceTimeLog->hindrance_id = $hindrance->id;
    				$hindranceTimeLog->current_user_id = $hindrance->owner_id;
    				$hindranceTimeLog->status = 'resolved';
    				$hindranceTimeLog->opening_date = date('Y-m-d');
    				$hindranceTimeLog->closing_date = NULL;
    				$hindranceTimeLog->save();
    			}
    			elseif($request->action == 'on_hold')
    			{
    				Hindrance::whereIn('id',$ids)->update(['status'=>"on_hold"]);
    				$message = trans('translate.on_hold');
    				if (auth()->id() != $hindrance->contractor_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->contractor_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance On Hold';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been on-hold by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->owner_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->owner_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance On Hold';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been on-hold by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->epcm_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->epcm_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance On Hold';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been on-hold by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				$hindranceActivityLog = new HindranceActivityLog;
    				$hindranceActivityLog->hindrance_id = $hindrance->id;
    				$hindranceActivityLog->performed_by = auth()->id();
    				$hindranceActivityLog->action = 'hindrance-on-hold';
    				$hindranceActivityLog->description = 'hindrance status on-hold';
    				$hindranceActivityLog->save();

    				$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    				if($lastHindranceTL->current_user_id != auth()->id())
    				{

    					$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    					$hindranceTimeLog = new HindranceTimeLog;
    					$hindranceTimeLog->hindrance_id = $hindrance->id;
    					$hindranceTimeLog->current_user_id = auth()->id();
    					$hindranceTimeLog->status = 'on_hold';
    					$hindranceTimeLog->opening_date = date('Y-m-d');
    					$hindranceTimeLog->closing_date = NULL;
    					$hindranceTimeLog->save();
    				}
    			}
    			elseif($request->action == 'under_review_by_owner')
    			{
    				Hindrance::whereIn('id',$ids)->update(['status'=>"under_review_by_owner"]);
    				$message = trans('translate.under_review_by_owner');
    				if (auth()->id() != $hindrance->contractor_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->contractor_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Under Review By Owner';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is under review by owner.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->owner_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->owner_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Under Review By Owner';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is under review by owner.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->epcm_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->epcm_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Under Review By Owner';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is under review by owner.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				$hindranceActivityLog = new HindranceActivityLog;
    				$hindranceActivityLog->hindrance_id = $hindrance->id;
    				$hindranceActivityLog->performed_by = auth()->id();
    				$hindranceActivityLog->action = 'hindrance-under-review-by-owner';
    				$hindranceActivityLog->description = 'Hindrance Under Review By Owner';
    				$hindranceActivityLog->save();

    				$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    				if ($lastHindranceTL->current_user_id != $hindrance->owner_id) {
    					$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    					$hindranceTimeLog = new HindranceTimeLog;
    					$hindranceTimeLog->hindrance_id = $hindrance->id;
    					$hindranceTimeLog->current_user_id = $hindrance->owner_id;
    					$hindranceTimeLog->status = 'under_review_by_owner';
    					$hindranceTimeLog->opening_date = date('Y-m-d');
    					$hindranceTimeLog->closing_date = NULL;
    					$hindranceTimeLog->save();
    				}
    				
    			}
    			elseif($request->action == 'under_review_by_epcm')
    			{
    				Hindrance::whereIn('id',$ids)->update(['status'=>"under_review_by_epcm"]);
    				$message = trans('translate.under_review_by_epcm');
    				if (auth()->id() != $hindrance->contractor_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->contractor_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Under Review By Epcm';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is under review by epcm.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->owner_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->owner_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Under Review By Epcm';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is under review by epcm.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->epcm_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->epcm_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Under Review By Epcm';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is under review by epcm.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				$hindranceActivityLog = new HindranceActivityLog;
    				$hindranceActivityLog->hindrance_id = $hindrance->id;
    				$hindranceActivityLog->performed_by = auth()->id();
    				$hindranceActivityLog->action = 'hindrance-under-review-by-epcm';
    				$hindranceActivityLog->description = 'Hindrance Under Review By Epcm';
    				$hindranceActivityLog->save();

    				$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    				if ($lastHindranceTL->current_user_id != $hindrance->epcm_id) {
    					$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    					$hindranceTimeLog = new HindranceTimeLog;
    					$hindranceTimeLog->hindrance_id = $hindrance->id;
    					$hindranceTimeLog->current_user_id = $hindrance->epcm_id;
    					$hindranceTimeLog->status = 'under_review_by_epcm';
    					$hindranceTimeLog->opening_date = date('Y-m-d');
    					$hindranceTimeLog->closing_date = NULL;
    					$hindranceTimeLog->save();
    				}
    				
    			}
    			elseif($request->action == 'approved')
    			{
    				Hindrance::whereIn('id',$ids)->update(['status'=>"approved","approved_date"=>date('Y-m-d')]);
    				$message = trans('translate.approved');
    				if (auth()->id() != $hindrance->contractor_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->contractor_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Approved';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been approved by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->owner_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->owner_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Approved';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been approved by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->epcm_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->epcm_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                	= 'Hindrance';
    					$notification->title                = 'Hindrance Approved';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been approved by '.auth()->user()->name.'.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				$hindranceActivityLog = new HindranceActivityLog;
    				$hindranceActivityLog->hindrance_id = $hindrance->id;
    				$hindranceActivityLog->performed_by = auth()->id();
    				$hindranceActivityLog->action = 'hindrance-approved';
    				$hindranceActivityLog->description = ']hindrance approved';
    				$hindranceActivityLog->save();

    				$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    				$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    				$hindranceTimeLog = new HindranceTimeLog;
    				$hindranceTimeLog->hindrance_id = $hindrance->id;
    				$hindranceTimeLog->current_user_id = $hindrance->owner_id;
    				$hindranceTimeLog->status = 'approved';
    				$hindranceTimeLog->opening_date = date('Y-m-d');
    				$hindranceTimeLog->closing_date = NULL;
    				$hindranceTimeLog->save();
    			}
    			elseif($request->action == 'assign_to_owner')
    			{
    				Hindrance::whereIn('id',$ids)->update(['status'=>"pending_with_owner"]);
    				$message = trans('translate.pending_with_owner');
    				if (auth()->id() != $hindrance->contractor_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->contractor_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Pending With Owner';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is pending with owner.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->owner_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->owner_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Pending With Owner';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is pending with owner.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				if (auth()->id() != $hindrance->epcm_id) {
    					$notification = new Notification;
    					$notification->user_id              = $hindrance->epcm_id;
    					$notification->sender_id            = auth()->id();
    					$notification->status_code          = 'success';
    					$notification->type                 = 'Hindrance';
    					$notification->title                = 'Hindrance Pending With Owner';
    					$notification->message              = 'Issue Raised with '.$hindrance->contract_number.' is pending with owner.';
    					$notification->read_status          = false;
    					$notification->data_id              = $hindrance->id;
    					$notification->save();
    				}
    				$hindranceActivityLog = new HindranceActivityLog;
    				$hindranceActivityLog->hindrance_id = $hindrance->id;
    				$hindranceActivityLog->performed_by = auth()->id();
    				$hindranceActivityLog->action = 'hindrance-pending-with-owner';
    				$hindranceActivityLog->description = 'Hindrance Pending With Owner';
    				$hindranceActivityLog->save();

                    //creating time log

    				$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    				$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    				$hindranceTimeLog = new HindranceTimeLog;
    				$hindranceTimeLog->hindrance_id = $hindrance->id;
    				$hindranceTimeLog->current_user_id = $request->assigned_to;
    				$hindranceTimeLog->status = 'pending_with_owner';
    				$hindranceTimeLog->opening_date = date('Y-m-d');
    				$hindranceTimeLog->closing_date = NULL;
    				$hindranceTimeLog->save();

    				$hindranceAssignee = new HindranceAssignee;
    				$hindranceAssignee->hindrance_id = $hindrance->id;
    				$hindranceAssignee->assigned_to = $request->assigned_to;
    				$hindranceAssignee->save();
    			}
    		}

    		$hindrances = Hindrance::whereIn('id',$ids)->get();
    		DB::commit();
    		return response(prepareResult(false, $hindrances, $message), config('httpcodes.success'));
    	}
    	catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }


    //Assign hindrance to Assignees
    public function hindranceAssign(Request $request)
    {
    	$validation = \Validator::make($request->all(), [
    		'assignees'      => 'required|array',
    		'hindrance_id' => 'required|exists:hindrances,id',
    		'reason_for_assignment' => 'required'
    	]);

    	if ($validation->fails()) {
    		return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
    	}

    	DB::beginTransaction();
    	try {
    		$hindrance = Hindrance::where('id',$request->hindrance_id)->first();
    		if(!$hindrance)
    		{
    			return response(prepareResult(true, [],trans('translate.record_not_found')), config('httpcodes.not_found'));
    		}
    		Hindrance::where('id',$hindrance->id)->update(['status'=>"pending_with_owner","reason_for_assignment" => $request->reason_for_assignment]);
    		$assignees = [];
    		HindranceAssignee::where('hindrance_id',$hindrance->id)->delete();
    		foreach ($request->assignees as $key => $value) {
    			$hindranceAssignee = new HindranceAssignee;
    			$hindranceAssignee->hindrance_id = $hindrance->id;
    			$hindranceAssignee->assigned_to = $value;
    			$hindranceAssignee->save();
    			if (!empty(User::find($value))) {
    				$assignees[] = User::find($value)->name;
    			}

    			$notification = new Notification;
    			$notification->user_id              = $value;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'New Hindrance Assigned';
    			$notification->message              = 'New Hindrance '.$hindrance->contract_number.' Assigned.';
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}
    		$assignees = implode(', ', $assignees);

    		$hindranceActivityLog = new HindranceActivityLog;
    		$hindranceActivityLog->hindrance_id = $hindrance->id;
    		$hindranceActivityLog->performed_by = auth()->id();
    		$hindranceActivityLog->action = 'hindrance-assigned';
    		$hindranceActivityLog->description = $request->reason_for_assignment;
    		$hindranceActivityLog->save();

            //creating time log

    		$lastHindranceTL = HindranceTimeLog::orderBy('id','DESC')->first();
    		$lastHindranceTL->update(['closing_date' => date('Y-m-d')]);

    		$hindranceTimeLog = new HindranceTimeLog;
    		$hindranceTimeLog->hindrance_id = $hindrance->id;
    		$hindranceTimeLog->current_user_id = auth()->id();
    		$hindranceTimeLog->status = 'approved';
    		$hindranceTimeLog->opening_date = date('Y-m-d');
    		$hindranceTimeLog->closing_date = date('Y-m-d');
    		$hindranceTimeLog->save();

    		$hindranceTimeLog = new HindranceTimeLog;
    		$hindranceTimeLog->hindrance_id = $hindrance->id;
    		$hindranceTimeLog->current_user_id = $request->assignees[0];
    		$hindranceTimeLog->status = 'pending_with_owner';
    		$hindranceTimeLog->opening_date = date('Y-m-d');
    		$hindranceTimeLog->closing_date = NULL;
    		$hindranceTimeLog->save();

    		if (auth()->id() != $hindrance->contractor_id) {
    			$notification = new Notification;
    			$notification->user_id              = $hindrance->contractor_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Hindrance Assigned';
    			$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been assigned  by '.auth()->user()->name.' to '.$assignees;
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}
    		if (auth()->id() != $hindrance->owner_id) {
    			$notification = new Notification;
    			$notification->user_id              = $hindrance->owner_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Hindrance Assigned';
    			$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been assigned  by '.auth()->user()->name.' to '.$assignees;
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}
    		if (auth()->id() != $hindrance->epcm_id) {
    			$notification = new Notification;
    			$notification->user_id              = $hindrance->epcm_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Hindrance Assigned';
    			$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been assigned  by '.auth()->user()->name.' to '.$assignees;
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}

    		$hindrance = Hindrance::where('id',$request->hindrance_id)->with('assignees')->first();
    		DB::commit();
    		return response(prepareResult(false, $hindrance, trans('translate.hindrance_assigned')),config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		DB::rollback();
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    //Remove hindrance Assignees
    public function hindranceAssigneeRemove(Request $request)
    {
    	$validation = \Validator::make($request->all(), [
    		'assignees'      => 'required|array',
    		'hindrance_id' => 'required|exists:hindrances,id'
    	]);

    	if ($validation->fails()) {
    		return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
    	}

    	DB::beginTransaction();
    	try {
    		$hindranceAssignee = HindranceAssignee::where('hindrance_id',$request->hindrance_id)->whereIn('assigned_to',$request->assignees)->delete();
    		$hindrance = Hindrance::where('id',$request->hindrance_id)->first();
    		if(!$hindrance)
    		{
    			return response(prepareResult(true, [],trans('translate.record_not_found')), config('httpcodes.not_found'));
    		}
    		$assignees = [];
    		foreach ($request->assignees as $key => $value) {
    			if (!empty(User::find($value))) {
    				$assignees[] = User::find($value)->name;
    			}

    			$notification = new Notification;
    			$notification->user_id              = $value;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Hindrance Assignee Removed';
    			$notification->message              = 'Hindrance '.$hindrance->contract_number.' Assigned to you has been removed.';
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}
    		$assignees = implode(', ', $assignees);

    		$hindranceActivityLog = new HindranceActivityLog;
    		$hindranceActivityLog->hindrance_id = $hindrance->id;
    		$hindranceActivityLog->performed_by = auth()->id();
    		$hindranceActivityLog->action = 'hindrance-assignee-removed';
    		$hindranceActivityLog->description = 'hindrance assignee-removed';
    		$hindranceActivityLog->save();

    		if (auth()->id() != $hindrance->contractor_id) {
    			$notification = new Notification;
    			$notification->user_id              = $hindrance->contractor_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Hindrance Assignee removed';
    			$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been removed  by '.auth()->user()->name.' from '.$assignees;
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}
    		if (auth()->id() != $hindrance->owner_id) {
    			$notification = new Notification;
    			$notification->user_id              = $hindrance->owner_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Hindrance Assignee removed';
    			$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been removed  by '.auth()->user()->name.' from '.$assignees;
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}
    		if (auth()->id() != $hindrance->epcm_id) {
    			$notification = new Notification;
    			$notification->user_id              = $hindrance->epcm_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Hindrance Assignee removed';
    			$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been removed  by '.auth()->user()->name.' from '.$assignees;
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}

    		DB::commit();
    		return response(prepareResult(false, $hindrance, trans('translate.hindrance_removed')),config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		DB::rollback();
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    // Update hindrance due date and priority
    public function hindranceDueDate(Request $request)
    {
    	$validation = \Validator::make($request->all(), [
    		'due_date'      => 'required',
    		'hindrance_id' => 'required|exists:hindrances,id',
    		'priority' => "required|in:1,2,3",
    	]);

    	if ($validation->fails()) {
    		return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
    	}

    	DB::beginTransaction();
    	try {
    		Hindrance::where('id',$request->hindrance_id)
    		->update([
    			'due_date'=>$request->due_date,
    			'priority'=>$request->priority
    		]);
    		$hindrance = Hindrance::find($request->hindrance_id);

    		$hindranceActivityLog = new HindranceActivityLog;
    		$hindranceActivityLog->hindrance_id = $hindrance->id;
    		$hindranceActivityLog->performed_by = auth()->id();
    		$hindranceActivityLog->action = 'hindrance-due-date-updated';
    		$hindranceActivityLog->description = 'hindrance due-date-updated';
    		$hindranceActivityLog->save();

    		if (auth()->id() != $hindrance->contractor_id) {
    			$notification = new Notification;
    			$notification->user_id              = $hindrance->contractor_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Hindrance Due Date Updated';
    			$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been updated  by '.auth()->user()->name.' .';
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}
    		if (auth()->id() != $hindrance->owner_id) {
    			$notification = new Notification;
    			$notification->user_id              = $hindrance->owner_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Hindrance Due Date Updated';
    			$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been updated  by '.auth()->user()->name.' .';
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}
    		if (auth()->id() != $hindrance->epcm_id) {
    			$notification = new Notification;
    			$notification->user_id              = $hindrance->epcm_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Hindrance';
    			$notification->title                = 'Hindrance Due Date Updated';
    			$notification->message              = 'Issue Raised with '.$hindrance->contract_number.'  has been updated  by '.auth()->user()->name.' .';
    			$notification->read_status          = false;
    			$notification->data_id              = $hindrance->id;
    			$notification->save();
    		}

    		DB::commit();
    		return response(prepareResult(false, $hindrance, trans('translate.hindrance_updated')),config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		DB::rollback();
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    //Export hindrances list data
    public function hindranceExport(Request $request)
    {
    	try{
    		if(auth()->user()->user_type == 4)
    		{
    			$contractor_id = auth()->id();
    		}
    		else
    		{
    			$contractor_id = $request->contractor_id;
    		}
    		$data = [
    			"contractor_id" => $contractor_id,
    			"status" => $request->status,
    			"from_date" => $request->from_date,
    			"to_date" => $request->to_date
    		];

    		$rand = rand(10000,99999);

    		$excel = Excel::store(new HindranceExport($data), 'hindrances/'.$rand.'.xlsx' , 'export_path');

    		return response(prepareResult(false,['url' => '/exports/hindrances/'.$rand.'.xlsx'], trans('translate.data_exported')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    //Export Activity log of perticular hindrance
    public function hindranceActivityLogExport($hindrance_id)
    {
    	try{
    		$rand = rand(10000,99999);

    		$excel = Excel::store(new HindranceActivityLogExport($hindrance_id), 'hindrance_logs/'.$rand.'.xlsx' , 'export_path');

    		return response(prepareResult(false,['url' => '/exports/hindrance_logs/'.$rand.'.xlsx'], trans('translate.data_exported')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    //view activity log of a perticular hindrance 
    public function viewLog($hindrance_id)
    {
    	try{
    		$hindranceLog = HindranceActivityLog::where('hindrance_id',$hindrance_id)->get();

    		return response(prepareResult(false,$hindranceLog, trans('translate.fetched_records')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }


    public function pendingResolvedData(Request $request)
    {
    	try {

    		$data = [];
    		$pending = Hindrance::select('hindrances.id','hindrances.hindrance_code','hindrances.created_by','hindrances.hindrance_date','hindrances.status')
    		->leftJoin('hindrance_assignees', function($join){
    			$join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
    		})
    		->distinct(['hindrances.id'])
    		->orderBy('id','ASC')
    		->where('status','!=','resolved')
    		->with('createdBy:id,name');
    		if (auth()->user()->user_type == 1) {
    		}
    		elseif(auth()->user()->user_type == 2){
    			$pending->where(function($q) {
    				$q->where('owner_id', auth()->id())
    				->orWhere('created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 3){
    			$pending->where(function($q) {
    				$q->where('epcm_id', auth()->id())
    				->orWhere('created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 4){
    			$pending->where(function($q) {
    				$q->where('contractor_id', auth()->id())
    				->orWhere('created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			});
    		}
    		if(!empty($request->contractor_id))
    		{
    			$pending->where('contractor_id',$request->contractor_id);
    		}

    		if(!empty($request->from_date))
    		{
    			$pending->whereDate('hindrance_date','>=',$request->from_date);
    		}
    		if(!empty($request->to_date))
    		{
    			$pending->whereDate('hindrance_date','<=',$request->to_date);
    		}
    		$pending = $pending->get();

    		$resolved = Hindrance::select('hindrances.id','hindrances.hindrance_code','hindrances.created_by','hindrances.hindrance_date','hindrances.status')
    		->leftJoin('hindrance_assignees', function($join){
    			$join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
    		})
    		->distinct(['hindrances.id'])
    		->orderBy('id','ASC')
    		->where('status','resolved')
    		->with('createdBy:id,name');
    		if (auth()->user()->user_type == 1) {
    		}
    		elseif(auth()->user()->user_type == 2){
    			$resolved->where(function($q) {
    				$q->where('owner_id', auth()->id())
    				->orWhere('created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 3){
    			$resolved->where(function($q) {
    				$q->where('epcm_id', auth()->id())
    				->orWhere('created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 4){
    			$resolved->where(function($q) {
    				$q->where('contractor_id', auth()->id())
    				->orWhere('created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			});
    		}
    		if(!empty($request->contractor_id))
    		{
    			$resolved->where('contractor_id',$request->contractor_id);
    		}

    		if(!empty($request->from_date))
    		{
    			$resolved->whereDate('hindrance_date','>=',$request->from_date);
    		}
    		if(!empty($request->to_date))
    		{
    			$resolved->whereDate('hindrance_date','<=',$request->to_date);
    		}
    		$resolved = $resolved->get();
    		$data['pending'] = $pending;
    		$data['pending_count'] = count($pending);
    		$data['resolved'] = $resolved;
    		$data['resolved_count'] = count($resolved);
    		return response(prepareResult(false, $data, trans('translate.hindrance_list')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }


    public function hindranceTypeWiseData(Request $request)
    {
    	try {

    		$data = [];
    		$date_before_4_weeks =  date('Y-m-d',strtotime('- 4 week'));
    		$date_before_2_weeks =  date('Y-m-d',strtotime('- 2 week'));
    		$invoiceTimeLog = [];

    		if (auth()->user()->user_type == 2) {
    			$hindranceTimeLog['pending_with_epcm_gtn_4weeks'] = HindranceTimeLog::select('hindrance_time_logs.*')
    			->leftJoin('hindrances', 'hindrance_time_logs.hindrance_id', '=', 'hindrances.id')
    			->leftJoin('hindrance_assignees', 'hindrances.id', '=', 'hindrance_assignees.hindrance_id')
    			->where(function ($query) {
    				$query->where('hindrances.owner_id', auth()->id())
    				->orWhere('hindrances.created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			})
    			->whereNull('hindrance_time_logs.closing_date')
    			->where('hindrance_time_logs.opening_date', '<', $date_before_4_weeks)
    			->whereIn('hindrance_time_logs.status', ['pending_with_epcm', 'under_review_by_epcm'])
    			->count(DB::raw('DISTINCT hindrance_time_logs.id'));


    			$hindranceTimeLog['pending_with_epcm_btwn_2_4weeks'] = HindranceTimeLog::select('hindrance_time_logs.*')
    			->leftJoin('hindrances', 'hindrance_time_logs.hindrance_id', '=', 'hindrances.id')
    			->leftJoin('hindrance_assignees', 'hindrances.id', '=', 'hindrance_assignees.hindrance_id')
    			->where(function ($query) {
    				$query->where('hindrances.owner_id', auth()->id())
    				->orWhere('hindrances.created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			})
    			->whereNull('hindrance_time_logs.closing_date')
    			->where('opening_date','>=',$date_before_4_weeks)
    			->where('opening_date','<',$date_before_2_weeks)
    			->whereIn('hindrance_time_logs.status', ['pending_with_epcm', 'under_review_by_epcm'])
    			->count(DB::raw('DISTINCT hindrance_time_logs.id'));

    			$hindranceTimeLog['pending_with_epcm_ltn_2_4weeks'] = HindranceTimeLog::select('hindrance_time_logs.*')
    			->leftJoin('hindrances', 'hindrance_time_logs.hindrance_id', '=', 'hindrances.id')
    			->leftJoin('hindrance_assignees', 'hindrances.id', '=', 'hindrance_assignees.hindrance_id')
    			->where(function ($query) {
    				$query->where('hindrances.owner_id', auth()->id())
    				->orWhere('hindrances.created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			})
    			->whereNull('hindrance_time_logs.closing_date')
    			->where('opening_date','>=',$date_before_2_weeks)
    			->whereIn('hindrance_time_logs.status', ['pending_with_epcm', 'under_review_by_epcm'])
    			->count(DB::raw('DISTINCT hindrance_time_logs.id'));

    			$hindranceTimeLog['pending_with_owner_gtn_4weeks'] = HindranceTimeLog::select('hindrance_time_logs.*')
    			->leftJoin('hindrances', 'hindrance_time_logs.hindrance_id', '=', 'hindrances.id')
    			->leftJoin('hindrance_assignees', 'hindrances.id', '=', 'hindrance_assignees.hindrance_id')
    			->where(function ($query) {
    				$query->where('hindrances.owner_id', auth()->id())
    				->orWhere('hindrances.created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			})
    			->whereNull('hindrance_time_logs.closing_date')
    			->where('hindrance_time_logs.opening_date','<',$date_before_4_weeks)
    			->whereIn('hindrance_time_logs.status', ['pending_with_owner', 'under_review_by_owner'])
    			->count(DB::raw('DISTINCT hindrance_time_logs.id'));

    			$hindranceTimeLog['pending_with_owner_btwn_2_4weeks'] = HindranceTimeLog::select('hindrance_time_logs.*')
    			->leftJoin('hindrances', 'hindrance_time_logs.hindrance_id', '=', 'hindrances.id')
    			->leftJoin('hindrance_assignees', 'hindrances.id', '=', 'hindrance_assignees.hindrance_id')
    			->where(function ($query) {
    				$query->where('hindrances.owner_id', auth()->id())
    				->orWhere('hindrances.created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			})
    			->whereNull('hindrance_time_logs.closing_date')
    			->where('opening_date','>=',$date_before_4_weeks)
    			->where('opening_date','<',$date_before_2_weeks)
    			->whereIn('hindrance_time_logs.status', ['pending_with_owner', 'under_review_by_owner'])
    			->count(DB::raw('DISTINCT hindrance_time_logs.id'));

    			$hindranceTimeLog['pending_with_owner_ltn_2_4weeks'] = HindranceTimeLog::select('hindrance_time_logs.*')
    			->leftJoin('hindrances', 'hindrance_time_logs.hindrance_id', '=', 'hindrances.id')
    			->leftJoin('hindrance_assignees', 'hindrances.id', '=', 'hindrance_assignees.hindrance_id')
    			->where(function ($query) {
    				$query->where('hindrances.owner_id', auth()->id())
    				->orWhere('hindrances.created_by', auth()->id())
    				->orWhere('hindrance_assignees.assigned_to', auth()->id());
    			})
    			->whereNull('hindrance_time_logs.closing_date')
    			->where('opening_date','>=',$date_before_2_weeks)
    			->whereIn('hindrance_time_logs.status', ['pending_with_owner', 'under_review_by_owner'])
    			->count(DB::raw('DISTINCT hindrance_time_logs.id'));
    		}
    		else
    		{
    			$hindranceTimeLog['pending_with_epcm_gtn_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','<',$date_before_4_weeks)->whereIn('status',['pending_with_epcm','under_review_by_epcm'])->count();
    			$hindranceTimeLog['pending_with_epcm_btwn_2_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_4_weeks)->where('opening_date','<',$date_before_2_weeks)->whereIn('status',['pending_with_epcm','under_review_by_epcm'])->count();
    			$hindranceTimeLog['pending_with_epcm_ltn_2_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_2_weeks)->whereIn('status',['pending_with_epcm','under_review_by_epcm'])->count();
    			$hindranceTimeLog['pending_with_owner_gtn_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','<',$date_before_4_weeks)->whereIn('status',['pending_with_owner','under_review_by_owner'])->count();
    			$hindranceTimeLog['pending_with_owner_btwn_2_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_4_weeks)->where('opening_date','<',$date_before_2_weeks)->whereIn('status',['pending_with_owner','under_review_by_owner'])->count();
    			$hindranceTimeLog['pending_with_owner_ltn_2_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_2_weeks)->whereIn('status',['pending_with_owner','under_review_by_owner'])->count();
    		}


    		$data['dashboard_analytics'][] = $hindranceTimeLog;

    		$types = HindranceType::all();
    		foreach ($types as $key => $type) {
    			$count = Hindrance::leftJoin('hindrance_assignees', function($join){
    				$join->on('hindrances.id', '=', 'hindrance_assignees.hindrance_id');
    			})
    			->distinct(['hindrances.id'])
    			->orderBy('id','ASC')
    			->where('hindrance_type',$type->name);
    			if (auth()->user()->user_type == 1) {
    			}
    			elseif(auth()->user()->user_type == 2){
    				$count->where(function($q) {
    					$q->where('owner_id', auth()->id())
    					->orWhere('created_by', auth()->id())
    					->orWhere('hindrance_assignees.assigned_to', auth()->id());
    				});
    			}
    			elseif(auth()->user()->user_type == 3){
    				$count->where(function($q) {
    					$q->where('epcm_id', auth()->id())
    					->orWhere('created_by', auth()->id())
    					->orWhere('hindrance_assignees.assigned_to', auth()->id());
    				});
    			}
    			elseif(auth()->user()->user_type == 4){
    				$count->where(function($q) {
    					$q->where('contractor_id', auth()->id())
    					->orWhere('created_by', auth()->id())
    					->orWhere('hindrance_assignees.assigned_to', auth()->id());
    				});
    			}
    			$count = $count->count();
    			$data['category_wise_data'][$type->name] = $count;
    		}
    		return response(prepareResult(false, $data, trans('translate.hindrance_list')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    public function dashboardAnalytics()
    {
    	try{
    		$date_before_4_weeks =  date('Y-m-d',strtotime('- 4 week'));
    		$date_before_2_weeks =  date('Y-m-d',strtotime('- 2 week'));
    		$invoiceTimeLog = [];
    		$hindranceTimeLog['pending_with_epcm_gtn_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','<',$date_before_4_weeks)->whereIn('status',['pending_with_epcm','under_review_by_epcm'])->count();
    		$hindranceTimeLog['pending_with_epcm_btwn_2_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_4_weeks)->where('opening_date','<',$date_before_2_weeks)->whereIn('status',['pending_with_epcm','under_review_by_epcm'])->count();
    		$hindranceTimeLog['pending_with_epcm_ltn_2_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_2_weeks)->whereIn('status',['pending_with_epcm','under_review_by_epcm'])->count();
    		$hindranceTimeLog['pending_with_owner_gtn_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','<',$date_before_4_weeks)->whereIn('status',['pending_with_owner','under_review_by_owner'])->count();
    		$hindranceTimeLog['pending_with_owner_btwn_2_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_4_weeks)->where('opening_date','<',$date_before_2_weeks)->whereIn('status',['pending_with_owner','under_review_by_owner'])->count();
    		$hindranceTimeLog['pending_with_owner_ltn_2_4weeks'] = HindranceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_2_weeks)->whereIn('status',['pending_with_owner','under_review_by_owner'])->count();
    		return response(prepareResult(false,$hindranceTimeLog, trans('translate.fetched_records')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }
}
