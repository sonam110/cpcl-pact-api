<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceRejection;
use App\Models\InvoiceCheck;
use App\Models\CheckList;
use App\Models\InvoiceCheckVerification;
use App\Models\Notification;
use App\Models\ContractEpcm;
use App\Models\ContractOwner;
use App\Models\InvoiceEpcm;
use App\Models\Contract;
use App\Models\User;
use Validator;
use Auth;
use Exception;
use DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use DNS1D;
use DNS2D;
use App\Models\InvoiceActivityLog;
use App\Models\InvoiceAssignedOwner;
use App\Models\InvoiceTimeLog;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InvoiceActivityLogExport;
use App\Exports\InvoiceExportNew;
use App\Mail\InvoiceUpdatesMail;
use Mail;
use PDF;
use App\Models\AppSetting;
use Str;

class InvoiceController extends Controller
{
	public function __construct()
	{
		$this->middleware('permission:invoices-browse',['only' => ['invoices','show']]);
		$this->middleware('permission:invoices-add', ['only' => ['store']]);
		$this->middleware('permission:invoices-action', ['only' => ['action','checksVerify']]);
		$this->middleware('permission:invoices-edit', ['only' => ['update']]);
		// $this->middleware('permission:invoices-read', ['only' => ['show']]);
		// $this->middleware('permission:invoices-delete', ['only' => ['destroy']]);
		$this->middleware('permission:invoices-export', ['only' => ['invoiceExport']]);
		$this->middleware('permission:invoices-log', ['only' => ['invoiceActivityLogExport','viewLog']]);
	}
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //listing invoices   
    public function invoices(Request $request)
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
    		$query = Invoice::select('invoices.*')
    		->orderBy('invoices.'.$column,$dir)
    		->distinct(['invoices.id'])
    		->with('epcms.epcm:id,name,email,mobile_number','owners.user:id,name,email,mobile_number','contractor:id,name,email','contract','lastActionPerformedBy:id,name,user_type','invoiceTimeLogs');

            //retrieving data according to log in user user type

    		if(auth()->user()->user_type == 2){
    			// $query->join('invoice_assigned_owners', function($join) use ($request) {
    			// 	$join->on('invoices.id', '=', 'invoice_assigned_owners.invoice_id')
    			// 	->where('invoice_assigned_owners.owner_id',auth()->id())
    			// 	->orWhere('invoices.owner_id', auth()->id())
    			// 	->orWhere('invoices.created_by', auth()->id());
    			// });
    			$query->where(function($q) {
    				$q->whereJsonContains('invoices.owners', auth()->id())
    				->orWhere('invoices.created_by', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 3){
    			$query->where(function($q) {
    				$q->whereJsonContains('invoices.epcms', auth()->id())
    				->orWhere('invoices.created_by', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 4){
    			$query->where(function($q) {
    				$q->where('invoices.contractor_id', auth()->id())
    				->orWhere('invoices.created_by', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 5){
    			// $query->whereIn('invoices.status',['approved','pending_with_owner_finance','pending_with_treasurer','paid']);
    		}


    		if(!empty($request->invoice_no))
    		{
    			$query->where('invoices.invoice_no',$request->invoice_no);
    		}
    		if(!empty($request->sap_po_number))
    		{
    			$query->where('invoices.contract_number',$request->sap_po_number);
    		}
    		if(!empty($request->vendor_id))
    		{
    			$query->where('invoices.contractor_id',$request->vendor_id);
    		}
    		if(!empty($request->package))
    		{
    			$query->where('invoices.package','LIKE','%'.$request->package.'%');
    		}
    		if(!empty($request->contractor_email))
    		{
    			$query->where('invoices.vendor_contact_email','LIKE','%'.$request->contractor_email.'%');
    		}
    		if(!empty($request->contractor_contact_number))
    		{
    			$query->where('invoices.vendor_contact_number','LIKE','%'.$request->contractor_contact_number.'%');
    		}
    		if(!empty($request->bill_entry_date))
    		{
    			$query->where('invoices.created_at','LIKE','%'.$request->bill_entry_date.'%');
    		}
    		if(!empty($request->invoice_date))
    		{
    			$query->where('invoices.invoice_date',$request->invoice_date);
    		}
    		if(!empty($request->ra_bill_number))
    		{
    			$query->where('invoices.ra_bill_number',$request->ra_bill_number);
    		}
    		if(!empty($request->status))
    		{
    			if($request->status == 'all')
    			{

    			}
    			elseif($request->status == 'returned')
    			{
    				$query->where('invoices.status','LIKE','%'.$request->status.'%');
    			}
    			elseif($request->status == 'pending_with_owner_finance')
    			{
    		    	$query->whereIn('invoices.status', ['pending_with_treasurer','pending_with_owner_finance']);
    			}
    			else
    			{
    				$query->where('invoices.status',$request->status);
    			}
    		}
    		else
    		{
    			if($request->ignore_default_filter != "yes")
    			{
    				if(auth()->user()->user_type == 2)
    				{
    					$query->where('invoices.status', 'pending_with_owner');
    				}
    				elseif(auth()->user()->user_type == 3)
    				{
    					$query->where('invoices.status', 'pending_with_epcm');
    				}
    				elseif(auth()->user()->user_type == 4)
    				{
    					$query->where('invoices.status', 'returned_to_contactor');
    				}
    				elseif(auth()->user()->user_type == 5)
    				{
    					$query->whereIn('invoices.status', ['pending_with_treasurer','pending_with_owner_finance']);
    				}
    			}
    		}

    		// if(!empty($request->current_user_id))
    		// {
    		// 	$query->join('invoice_time_logs', function($join) use ($request) {
    		// 		$join->on('invoices.id', '=', 'invoice_time_logs.invoice_id')
    		// 		->where('invoice_time_logs.current_user_id',$request->current_user_id)
    		// 		->where('closing_date',NULL);
    		// 	});
    		// }
    		if (!empty($request->current_user_id)) {
    			$query->join('invoice_time_logs', function($join) use ($request) {
    				$join->on('invoices.id', '=', 'invoice_time_logs.invoice_id')
    				->whereRaw('FIND_IN_SET(?, invoice_time_logs.current_user_id)', [$request->current_user_id])
    				->whereNull('invoice_time_logs.closing_date');
    			});
    		}


    		if(!empty($request->creator_user_type))
    		{
    			$query->where('invoices.creator_user_type',$request->creator_user_type);
    		}
    		if(!empty($request->owner_id))
    		{
    			$query->whereJsonContains('invoices.owners',$request->owner_id);
    		}
    		if(!empty($request->epcm_id))
    		{
    			$query->whereJsonContains('invoices.epcms',$request->epcm_id);
    		}
    		if(!empty($request->approved_date))
    		{
    			$query->where('invoices.approved_date',$request->approved_date);
    		}
    		if(!empty($request->paid_date))
    		{
    			$query->where('invoices.paid_date',$request->paid_date);
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

    		return response(prepareResult(false, $query, trans('translate.invoice_list')), config('httpcodes.success'));
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
    //creating new invoice
    public function store(Request $request)
    {
    	$validation = \Validator::make($request->all(), [
    		'invoice_no'      => 'required|unique:invoices,invoice_no',
    		'vendor_name'      => 'required',
    		'vendor_contact_email'      => 'required|email',
    		'invoice_type' => 'required|in:services,supply,Running Account Bill,Final Bill,Mobilization/Advance',
    		'contractor_id' => 'required|exists:users,id',
    		'contract_number' => 'required|exists:contracts,contract_number',
    		'package' =>'required'

    	]);

    	if ($validation->fails()) {
    		return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
    	}

    	DB::beginTransaction();
    	try {
    		$unique_no = time().rand(1000,9999);//autogenerated

            //generating barcode using autogenerated unique number
    		$barcode =  DNS1D::getBarcodePNGPath($unique_no, 'C128',3,80,array(0,0,0), true);

            //getting contract data using contract number
    		$contract = Contract::where('contract_number',$request->contract_number)->first();
    		$epcm_ids = [];
    		$epcms = ContractEpcm::where('contract_id',$contract->id)->get(['epcm_id']);
    		foreach ($epcms as $key => $value) {
    			$epcm_ids[] = $value->epcm_id;
    		};

    		$owner_ids = [];
    		$owners = ContractOwner::where('contract_id',$contract->id)->get(['owner_id']);
    		foreach ($owners as $key => $value) {
    			$owner_ids[] = $value->owner_id;
    		};

    		$contractor = User::find($request->contractor_id);
    		if (empty($epcm_ids)) {
    			$epcm_ids[] = $contractor->epcm_id;
    		}
    		if (empty($owner_ids)) {
    			$owner_ids[] = $contractor->owner_id;
    		}

    		if(auth()->user()->user_type == 3)
    		{
    			$status = 'pending_with_owner';
    			$current_user_id = implode(',', $owner_ids);
    		}
    		else
    		{
    			$status = 'pending_with_epcm';
    			$current_user_id = implode(',', $epcm_ids);
    		}

    		$invoice = new Invoice;
    		$invoice->contractor_id       	= $contractor->id;
    		$invoice->unique_no             = $unique_no;
    		$invoice->currency = $request->currency ? $request->currency : 'INR';
    		$invoice->contract_number       = $request->contract_number;
    		$invoice->epcms                 = json_encode($epcm_ids);
    		$invoice->owners                = json_encode($owner_ids);
    		$invoice->barcode               = $barcode;
    		$invoice->invoice_no  			= $request->invoice_no;
    		$invoice->ra_bill_number        = $request->ra_bill_number;
    		$invoice->invoice_type  		= $request->invoice_type;
    		$invoice->package           	= $request->package;
    		$invoice->basic_amount          = $request->basic_amount;
    		$invoice->gst_amount            = $request->gst_amount;
    		$invoice->total_amount          = $request->total_amount;
    		$invoice->invoice_date          = $request->invoice_date;
    		$invoice->vendor_name 			= $request->vendor_name;
    		$invoice->vendor_contact_number = $request->vendor_contact_number;
    		$invoice->vendor_contact_email 	= $request->vendor_contact_email;
    		$invoice->status 				= $status;
    		$invoice->notes 				= $request->notes;
    		$invoice->created_by            = auth()->id();
    		$invoice->creator_user_type     = auth()->user()->user_type;
    		$invoice->description 		    = $request->description;
    		$invoice->last_action_performed_by      = auth()->id();
    		$invoice->uploaded_files        = json_encode($request->uploaded_files);
    		$invoice->save();


            //assigning and notifying owners
    		foreach ($owner_ids as $key => $owner_id) {
    			$notification = new Notification;
    			$notification->user_id              = $owner_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                	= 'Invoice';
    			$notification->title                = 'New Invoice Uploaded';
    			$notification->message              = 'New Invoice '.$invoice->invoice_no.' Uploaded.';
    			$notification->read_status          = false;
    			$notification->data_id              = $invoice->id;
    			$notification->save();

    			$invoiceAssignedOwner =  new InvoiceAssignedOwner;
    			$invoiceAssignedOwner->owner_id = $owner_id;
    			$invoiceAssignedOwner->invoice_id = $invoice->id;
    			$invoiceAssignedOwner->save();
    		}

            //assigning and notifying epcms
    		foreach ($epcm_ids as $key => $epcm) {
    			$notification = new Notification;
    			$notification->user_id              = $epcm;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                 = 'Invoice';
    			$notification->title                = 'New Invoice Uploaded';
    			$notification->message              = 'New Invoice '.$invoice->invoice_no.' Uploaded.';
    			$notification->read_status          = false;
    			$notification->data_id              = $invoice->id;
    			$notification->save();

    			$invoiceEpcm = new InvoiceEpcm();
    			$invoiceEpcm->invoice_id = $invoice->id;
    			$invoiceEpcm->epcm_id = $epcm;
    			$invoiceEpcm->save();
    		}

            //creating activity log
    		$invoiceActivityLog = new InvoiceActivityLog;
    		$invoiceActivityLog->invoice_id = $invoice->id;
    		$invoiceActivityLog->performed_by = auth()->id();
    		$invoiceActivityLog->action = 'invoice-created';
    		$invoiceActivityLog->description = 'new invoice uploaded';
    		$invoiceActivityLog->save();

            //creating time log

    		$invoiceTimeLog = new InvoiceTimeLog;
    		$invoiceTimeLog->invoice_id = $invoice->id;
    		$invoiceTimeLog->current_user_id = $current_user_id;
    		$invoiceTimeLog->status = $status;
    		$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    		$invoiceTimeLog->opening_date = date('Y-m-d');
    		$invoiceTimeLog->closing_date = NULL;
    		$invoiceTimeLog->save();

    		$invoice = Invoice::with('epcms.epcm:id,name,email,mobile_number','owners.user:id,name,email,mobile_number','contractor:id,name,email')->find($invoice->id);

    		DB::commit();
    		return response(prepareResult(false, $invoice, trans('translate.invoice_created')),config('httpcodes.created'));
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
    //view invoice
    public function show(Invoice $invoice)
    {
    	try
    	{
    		if(auth()->user()->can('invoices-log'))
    		{
    			$invoice = Invoice::with('epcms.epcm:id,name,email,mobile_number','owners.user:id,name,email,mobile_number','contractor:id,name,email','invoiceActivityLogs.performedBy:id,name,email','contract','lastActionPerformedBy:id,name,user_type','invoiceTimeLogs')->find($invoice->id);
    		}
    		else
    		{ 
    			$invoice = Invoice::with('epcms.epcm:id,name,email,mobile_number','owners.user:id,name,email,mobile_number','contractor:id,name,email','contract','lastActionPerformedBy:id,name,user_type','invoiceTimeLogs')->find($invoice->id);
    		}
    		return response(prepareResult(false, $invoice, trans('translate.invoice_detail')), config('httpcodes.success'));
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
    //update invoices data
    public function update(Request $request, $id)
    {
    	$validation = \Validator::make($request->all(), [
    		'invoice_no'     => 'required|unique:invoices,invoice_no,'.$id,
    		'vendor_name'      => 'required',
    		// 'vendor_contact_number'      => 'required|numeric',
    		'vendor_contact_email'      => 'required|email',
    		'invoice_type' => "required|in:services,supply,Running Account Bill,Final Bill,Mobilization/Advance",
    		'contractor_id' => 'required|exists:users,id',
    		'contract_number' => 'required|exists:contracts,contract_number',
    		"package" =>'required'
    	]);

    	if ($validation->fails()) {
    		return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
    	}

    	DB::beginTransaction();
    	try {
    		$invoice = Invoice::find($id);
    		// $check_pon = Invoice::where('contract_number',$request->contract_number)->where('id','!=',$id)->count();
    		// if($check_pon > 0)
    		// {
    		// 	return response(prepareResult(true, ['Dublicate SAP PO (Contract) Number'], trans('translate.sap_po_number_dublicate')),config('httpcodes.bad_request'));
    		// }
    		if(!$invoice)
    		{
    			return response(prepareResult(true, [],trans('translate.record_not_found')), config('httpcodes.not_found'));
    		}
    		$contract = Contract::where('contract_number',$request->contract_number)->first();
    		$epcm_ids = [];
    		$epcms = ContractEpcm::where('contract_id',$contract->id)->get(['epcm_id']);
    		foreach ($epcms as $key => $value) {
    			$epcm_ids[] = $value->epcm_id;
    		};

    		$owner_ids = [];
    		$owners = ContractOwner::where('contract_id',$contract->id)->get(['owner_id']);
    		foreach ($owners as $key => $value) {
    			$owner_ids[] = $value->owner_id;
    		};

    		$contractor = User::find($request->contractor_id);
    		if (empty($epcm_ids)) {
    			$epcm_ids[] = $contractor->epcm_id;
    		}
    		if (empty($owner_ids)) {
    			$owner_ids[] = $contractor->owner_id;
    		}

    		if(auth()->user()->user_type == 3)
    		{
    			$status = 'pending_with_owner';
    			$current_user_id = implode(',', $owner_ids);
    		}
    		else
    		{
    			$status = 'pending_with_epcm';
    			$current_user_id = implode(',', $epcm_ids);
    		}

    		$invoice->contractor_id       	= $contractor->id;
    		$invoice->contract_number       = $request->contract_number;
    		$invoice->epcms                 = json_encode($epcm_ids);
    		$invoice->owners                = json_encode($owner_ids);
    		$invoice->invoice_no  			= $request->invoice_no;
    		$invoice->currency = $request->currency ? $request->currency : 'INR';
    		$invoice->ra_bill_number        = $request->ra_bill_number;
    		$invoice->invoice_type  		= $request->invoice_type;
    		$invoice->package           	= $request->package;
    		$invoice->basic_amount          = $request->basic_amount;
    		$invoice->gst_amount            = $request->gst_amount;
    		$invoice->total_amount          = ($request->basic_amount + $request->gst_amount);
    		$invoice->invoice_date          = $request->invoice_date;
    		$invoice->vendor_name 			= $request->vendor_name;
    		$invoice->vendor_contact_number = $request->vendor_contact_number;
    		$invoice->vendor_contact_email 	= $request->vendor_contact_email;
    		$invoice->status 				= $status;
    		$invoice->notes 				= $request->notes;
    		$invoice->description 		    = $request->description;
    		$invoice->uploaded_files        = json_encode($request->uploaded_files);
    		$invoice->last_action_performed_by      = auth()->id();
    		$invoice->save();


    		
    		//assigning owners
    		InvoiceAssignedOwner::where('invoice_id',$invoice->id)->delete();
    		foreach ($owner_ids as $key => $owner_id) {
    			$invoiceAssignedOwner =  new InvoiceAssignedOwner;
    			$invoiceAssignedOwner->owner_id = $owner_id;
    			$invoiceAssignedOwner->invoice_id = $invoice->id;
    			$invoiceAssignedOwner->save();
    		}

            //assigning epcms
    		InvoiceEpcm::where('invoice_id',$invoice->id)->delete();
    		foreach ($epcm_ids as $key => $epcm) {
    			$invoiceEpcm = new InvoiceEpcm();
    			$invoiceEpcm->invoice_id = $invoice->id;
    			$invoiceEpcm->epcm_id = $epcm;
    			$invoiceEpcm->save();
    		}

    		//notify admin about new invoice
    		if($request->status == 'resend')
    		{
    			$notification = new Notification;
    			$notification->user_id              = $contractor->owner_id;
    			$notification->sender_id            = auth()->id();
    			$notification->status_code          = 'success';
    			$notification->type                	= 'Invoice';
    			$notification->title                = 'Invoice Resent';
    			$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.' Resent.';
    			$notification->read_status          = false;
    			$notification->data_id              = $invoice->id;
    			$notification->save();

    			foreach ($epcm_ids as $key => $epcm) {
    				$notification = new Notification;
    				$notification->user_id              = $epcm;
    				$notification->sender_id            = auth()->id();
    				$notification->status_code          = 'success';
    				$notification->type                 = 'Invoice';
    				$notification->title                = 'New Invoice Uploaded';
    				$notification->message              = 'New Invoice '.$invoice->invoice_no.' Uploaded.';
    				$notification->read_status          = false;
    				$notification->data_id              = $invoice->id;
    				$notification->save();
    			}

    			foreach ($owner_ids as $key => $owner) {
    				$notification = new Notification;
    				$notification->user_id              = $owner;
    				$notification->sender_id            = auth()->id();
    				$notification->status_code          = 'success';
    				$notification->type                 = 'Invoice';
    				$notification->title                = 'New Invoice Uploaded';
    				$notification->message              = 'New Invoice '.$invoice->invoice_no.' Uploaded.';
    				$notification->read_status          = false;
    				$notification->data_id              = $invoice->id;
    				$notification->save();
    			}

    			$invoiceActivityLog = new InvoiceActivityLog;
    			$invoiceActivityLog->invoice_id = $invoice->id;
    			$invoiceActivityLog->performed_by = auth()->id();
    			$invoiceActivityLog->action = 'invoice-resent';
    			$invoiceActivityLog->description = 'invoice resent';
    			$invoiceActivityLog->save();
    		}
    		else
    		{
    			$invoiceActivityLog = new InvoiceActivityLog;
    			$invoiceActivityLog->invoice_id = $invoice->id;
    			$invoiceActivityLog->performed_by = auth()->id();
    			$invoiceActivityLog->action = 'invoice-updated';
    			$invoiceActivityLog->description = 'invoice updated';
    			$invoiceActivityLog->save();
    		}

            //creating time log

    		$lastInvoiceTL = InvoiceTimeLog::orderBy('id','DESC')->first();
    		$lastInvoiceTL->update(['closing_date' => date('Y-m-d')]);

    		$invoiceTimeLog = new InvoiceTimeLog;
    		$invoiceTimeLog->invoice_id = $invoice->id;
    		$invoiceTimeLog->current_user_id = $current_user_id;
    		$invoiceTimeLog->status = $status;
    		$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    		$invoiceTimeLog->opening_date = date('Y-m-d');
    		$invoiceTimeLog->closing_date = NULL;
    		$invoiceTimeLog->save();

    		$invoice = Invoice::with('epcms.epcm:id,name,email,mobile_number','owners.user:id,name,email,mobile_number','contractor:id,name,email','lastActionPerformedBy:id,name,user_type','lastActionPerformedBy:id,name,user_type')->find($invoice->id);
    		DB::commit();
    		return response(prepareResult(false, $invoice, trans('translate.invoice_updated')),config('httpcodes.success'));
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
    //delete invoice
    public function destroy($id)
    {
    	try {
    		$invoice = Invoice::find($id);
    		if (!is_object($invoice)) {
    			return response(prepareResult(true, [],trans('translate.record_not_found')), config('httpcodes.not_found'));
    		}
    		InvoiceActivityLog::where('invoice_id',$id)->delete();
    		InvoiceTimeLog::where('invoice_id',$id)->delete();
    		InvoiceEpcm::where('invoice_id',$id)->delete();
    		invoiceAssignedOwner::where('invoice_id',$id)->delete();
    		InvoiceCheckVerification::where('invoice_id',$id)->delete();
    		InvoiceRejection::where('invoice_id',$id)->delete();
    		$invoice->delete();
    		return response(prepareResult(false, [], trans('translate.invoice_deleted')), config('httpcodes.success'));
    	}
    	catch(Exception $exception) {
    		return response(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    //performed action on invoices
    public function action(Request $request)
    {
    	$validation = \Validator::make($request->all(), [
    		'ids.*'      => 'required|exists:invoices,id',
    		'action'      => 'required'
    	]);

    	if ($validation->fails()) {
    		return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
    	}
    	DB::beginTransaction();
    	try 
    	{

    		$ids = $request->ids;
    		$message = trans('translate.invalid_action');

    		$invoices = Invoice::whereIn('id',$ids)->get();
    
    		foreach ($invoices as $key => $invoice) {

    			//Getting user ids to be notified 
    			$notify_user_ids = [$invoice->contractor_id];
    			if(!empty($invoice->epcms)){
        			foreach (json_decode($invoice->epcms) as $key => $epcm) {
        				$notify_user_ids[] = $epcm;
        			}
    			}
    			if(!empty($invoice->owners)){
        			foreach (json_decode($invoice->owners) as $key => $owner) {
        				$notify_user_ids[] = $owner;
        			}
    			}

    			if($request->action == 'delete')
    			{
    				$invoice->delete();
    				$message = trans('translate.deleted');
    			}
    			elseif($request->action == 'returned_to_contractor')
    			{
                    //updating status returned_to_contractor
    				Invoice::whereIn('id',$ids)->update(['last_action_performed_by'=>auth()->id(),'description'=>$request->description,'status'=>"returned_to_contractor"]);
    				$message = trans('translate.returned_to_contractor');

    				foreach ($notify_user_ids as $key => $value) {
    					if (auth()->id() != $value) {
                            //Notification to users
    						$notification = new Notification;
    						$notification->user_id              = $value;
    						$notification->sender_id            = auth()->id();
    						$notification->status_code          = 'success';
    						$notification->type                 = 'Invoice';
    						$notification->title                = 'Invoice Returned To Contractor';
    						$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.' is returned to contractor.';
    						$notification->read_status          = false;
    						$notification->data_id              = $invoice->id;
    						$notification->save();

                            //Mail to users
    						$owner = User::withoutGlobalScope('user_id')->find($value);
    						if (env('IS_MAIL_ENABLE', false) == true) {
    							$content = [
    								"name" =>$owner->name,
    								"body" => 'Invoice with Invoice Number '.$invoice->invoice_no.' is returned to contractor.',

    							];
    							$recevier = Mail::to($owner->email)->send(new InvoiceUpdatesMail($content));
    						}
    					}
    				}
                    // Invoice Activity Log
    				$invoiceActivityLog = new InvoiceActivityLog;
    				$invoiceActivityLog->invoice_id = $invoice->id;
    				$invoiceActivityLog->performed_by = auth()->id();
    				$invoiceActivityLog->action = 'invoice-returned-to-contractor';
    				$invoiceActivityLog->description = $request->description;
    				$invoiceActivityLog->save();

                    //creating time log

    				$lastInvoiceTL = InvoiceTimeLog::orderBy('id','DESC')->first();
    				$lastInvoiceTL->update(['closing_date' => date('Y-m-d')]);

    				$invoiceTimeLog = new InvoiceTimeLog;
    				$invoiceTimeLog->invoice_id = $invoice->id;
    				$invoiceTimeLog->current_user_id = $request->assigned_to;
    				$invoiceTimeLog->status = 'returned_to_contractor';
    				$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    				$invoiceTimeLog->opening_date = date('Y-m-d');
    				$invoiceTimeLog->closing_date = NULL;
    				$invoiceTimeLog->save();
    			}
    			elseif($request->action == 'pending_with_owner')
    			{
                    //updating status pending_with_epcm
    				Invoice::whereIn('id',$ids)->update(['last_action_performed_by'=>auth()->id(),'description'=>$request->description,'status'=>"pending_with_owner"]);
    				$message = trans('translate.pending_with_owner');
    				
    				foreach ($notify_user_ids as $key => $value) {
    					if (auth()->id() != $value) {
    						$notification = new Notification;
    						$notification->user_id              = $value;
    						$notification->sender_id            = auth()->id();
    						$notification->status_code          = 'success';
    						$notification->type                 = 'Invoice';
    						$notification->title                = 'Invoice Pending with Owner';
    						$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.' is pending with owner.';
    						$notification->read_status          = false;
    						$notification->data_id              = $invoice->id;
    						$notification->save();

    						$owner = User::withoutGlobalScope('user_id')->find($value);

    						if (env('IS_MAIL_ENABLE', false) == true) {
    							$content = [
    								"name" =>$owner->name,
    								"body" => 'Invoice with Invoice Number '.$invoice->invoice_no.' is pending with owner.',

    							];
    							$recevier = Mail::to($owner->email)->send(new InvoiceUpdatesMail($content));
    						}
    					}
    				}

    				$invoiceActivityLog = new InvoiceActivityLog;
    				$invoiceActivityLog->invoice_id = $invoice->id;
    				$invoiceActivityLog->performed_by = auth()->id();
    				$invoiceActivityLog->action = 'invoice-pending-with-owner';
    				$invoiceActivityLog->description = $request->description;
    				$invoiceActivityLog->save();

                    //creating time log

    				$lastInvoiceTL = InvoiceTimeLog::orderBy('id','DESC')->first();
    				$lastInvoiceTL->update(['closing_date' => date('Y-m-d')]);

    				$invoiceTimeLog = new InvoiceTimeLog;
    				$invoiceTimeLog->invoice_id = $invoice->id;
    				$invoiceTimeLog->current_user_id = auth()->id();
    				$invoiceTimeLog->status = 'approved';
    				$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    				$invoiceTimeLog->opening_date = date('Y-m-d');
    				$invoiceTimeLog->closing_date = date('Y-m-d');
    				$invoiceTimeLog->save();

    				$invoiceTimeLog = new InvoiceTimeLog;
    				$invoiceTimeLog->invoice_id = $invoice->id;
    				$invoiceTimeLog->current_user_id = implode(',', json_decode($invoice->owners));
    				$invoiceTimeLog->status = 'pending_with_owner';
    				$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    				$invoiceTimeLog->opening_date = date('Y-m-d');
    				$invoiceTimeLog->closing_date = NULL;
    				$invoiceTimeLog->save();
    			}
    			elseif($request->action == 'returned_to_epcm')
    			{
                    //updating status returned_to_epcm
    				Invoice::whereIn('id',$ids)->update(['last_action_performed_by'=>auth()->id(),'description'=>$request->description,'status'=>"returned_to_epcm"]);
    				$message = trans('translate.returned_to_epcm');
    				
    				foreach ($notify_user_ids as $key => $value) {
    					if (auth()->id() != $value) {
    						$notification = new Notification;
    						$notification->user_id              = $value;
    						$notification->sender_id            = auth()->id();
    						$notification->status_code          = 'success';
    						$notification->type                 = 'Invoice';
    						$notification->title                = 'Invoice Returned To Epcm';
    						$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.' is returned to epcm.';
    						$notification->read_status          = false;
    						$notification->data_id              = $invoice->id;
    						$notification->save();

    						$owner = User::withoutGlobalScope('user_id')->find($value);

    						if (env('IS_MAIL_ENABLE', false) == true) {
    							$content = [
    								"name" =>$owner->name,
    								"body" => 'Invoice with Invoice Number '.$invoice->invoice_no.' is returned to epcm.',

    							];
    							$recevier = Mail::to($owner->email)->send(new InvoiceUpdatesMail($content));
    						}
    					}
    				}

    				$invoiceActivityLog = new InvoiceActivityLog;
    				$invoiceActivityLog->invoice_id = $invoice->id;
    				$invoiceActivityLog->performed_by = auth()->id();
    				$invoiceActivityLog->action = 'invoice-returned-to-epcm';
    				$invoiceActivityLog->description = $request->description;
    				$invoiceActivityLog->save();

                    //creating time log

    				$lastInvoiceTL = InvoiceTimeLog::orderBy('id','DESC')->first();
    				$lastInvoiceTL->update(['closing_date' => date('Y-m-d')]);

    				$invoiceTimeLog = new InvoiceTimeLog;
    				$invoiceTimeLog->invoice_id = $invoice->id;
    				$invoiceTimeLog->current_user_id = implode(',', json_decode($invoice->epcms));
    				$invoiceTimeLog->status = 'returned_to_epcm';
    				$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    				$invoiceTimeLog->opening_date = date('Y-m-d');
    				$invoiceTimeLog->closing_date = NULL;
    				$invoiceTimeLog->save();
    			}
    			elseif($request->action == 'pending_with_owner_finance')
    			{
                    //updating status returned_to_epcm
    				Invoice::whereIn('id',$ids)->update(['last_action_performed_by'=>auth()->id(),'description'=>$request->description,'status'=>"pending_with_owner_finance",'assigned_to' => ($request->assigned_to ? $request->assigned_to : $invoice->owner_id)]);
    				$message = trans('translate.pending_with_owner_finance');    				

    				foreach ($notify_user_ids as $key => $value) {
    					if (auth()->id() != $value) {
    						$notification = new Notification;
    						$notification->user_id              = $value;
    						$notification->sender_id            = auth()->id();
    						$notification->status_code          = 'success';
    						$notification->type                 = 'Invoice';
    						$notification->title                = 'Invoice Assigned To Owner Finance';
    						$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.' is pending with owner_finance.';
    						$notification->read_status          = false;
    						$notification->data_id              = $invoice->id;
    						$notification->save();

    						$owner = User::withoutGlobalScope('user_id')->find($value);

    						if (env('IS_MAIL_ENABLE', false) == true) {
    							$content = [
    								"name" =>$owner->name,
    								"body" => 'Invoice with Invoice Number '.$invoice->invoice_no.' is pending with Owner Finance.',

    							];
    							$recevier = Mail::to($owner->email)->send(new InvoiceUpdatesMail($content));
    						}
    					}
    				}

    				$invoiceActivityLog = new InvoiceActivityLog;
    				$invoiceActivityLog->invoice_id = $invoice->id;
    				$invoiceActivityLog->performed_by = auth()->id();
    				$invoiceActivityLog->action = 'invoice-pending-with-owner-finance';
    				$invoiceActivityLog->description = $request->description;
    				$invoiceActivityLog->save();

                    //creating time log

    				$lastInvoiceTL = InvoiceTimeLog::orderBy('id','DESC')->first();
    				$lastInvoiceTL->update(['closing_date' => date('Y-m-d')]);

    				$invoiceTimeLog = new InvoiceTimeLog;
    				$invoiceTimeLog->invoice_id = $invoice->id;
    				$invoiceTimeLog->current_user_id = auth()->id();
    				$invoiceTimeLog->status = 'approved';
    				$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    				$invoiceTimeLog->opening_date = date('Y-m-d');
    				$invoiceTimeLog->closing_date = date('Y-m-d');
    				$invoiceTimeLog->save();

    				$invoiceTimeLog = new InvoiceTimeLog;
    				$invoiceTimeLog->invoice_id = $invoice->id;
    				$invoiceTimeLog->current_user_id = $request->assigned_to;
    				$invoiceTimeLog->status = 'pending_with_owner_finance';
    				$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    				$invoiceTimeLog->opening_date = date('Y-m-d');
    				$invoiceTimeLog->closing_date = NULL;
    				$invoiceTimeLog->save();
    			}
    			elseif($request->action == 'returned_to_owner')
    			{
                    //updating status returned_to_epcm
    				Invoice::whereIn('id',$ids)->update(['last_action_performed_by'=>auth()->id(),'description'=>$request->description,'status'=>"returned_to_owner"]);
    				$message = trans('translate.returned_to_owner');
    				

    				foreach ($notify_user_ids as $key => $value) {
    					if (auth()->id() != $value) {
    						$notification = new Notification;
    						$notification->user_id              = $value;
    						$notification->sender_id            = auth()->id();
    						$notification->status_code          = 'success';
    						$notification->type                	= 'Invoice';
    						$notification->title                = 'Invoice Returned To Owner';
    						$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.' is returned to owner.';
    						$notification->read_status          = false;
    						$notification->data_id              = $invoice->id;
    						$notification->save();

    						$owner = User::withoutGlobalScope('user_id')->find($value);

    						if (env('IS_MAIL_ENABLE', false) == true) {
    							$content = [
    								"name" =>$owner->name,
    								"body" => 'Invoice with Invoice Number '.$invoice->invoice_no.' is returned to owner.',

    							];
    							$recevier = Mail::to($owner->email)->send(new InvoiceUpdatesMail($content));
    						}
    					}
    				}



    				$invoiceAssignedOwner =  new InvoiceAssignedOwner;
    				$invoiceAssignedOwner->owner_id = $request->assigned_to;
    				$invoiceAssignedOwner->invoice_id = $invoice->id;
    				$invoiceAssignedOwner->save();

    				$invoiceActivityLog = new InvoiceActivityLog;
    				$invoiceActivityLog->invoice_id = $invoice->id;
    				$invoiceActivityLog->performed_by = auth()->id();
    				$invoiceActivityLog->action = 'invoice-returned-to-owner';
    				$invoiceActivityLog->description = $request->description;
    				$invoiceActivityLog->save();

                    //creating time log

    				$lastInvoiceTL = InvoiceTimeLog::orderBy('id','DESC')->first();
    				$lastInvoiceTL->update(['closing_date' => date('Y-m-d')]);

    				$invoiceTimeLog = new InvoiceTimeLog;
    				$invoiceTimeLog->invoice_id = $invoice->id;
    				$invoiceTimeLog->current_user_id = implode(',', json_decode($invoice->owners));
    				$invoiceTimeLog->status = 'returned_to_owner';
    				$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    				$invoiceTimeLog->opening_date = date('Y-m-d');
    				$invoiceTimeLog->closing_date = NULL;
    				$invoiceTimeLog->save();
    			}
    			elseif($request->action == 'approved')
    			{
                    //updating status approved
    				Invoice::whereIn('id',$ids)->update(['last_action_performed_by'=>auth()->id(),'description'=>$request->description,'status'=>"pending_with_treasurer",'approved_date'=> date('Y-m-d')]);
    				$message = trans('translate.approved');
    				
    				foreach ($notify_user_ids as $key => $value) {
    					if (auth()->id() != $value) {
    						$notification = new Notification;
    						$notification->user_id              = $value;
    						$notification->sender_id            = auth()->id();
    						$notification->status_code          = 'success';
    						$notification->type                 = 'Invoice';
    						$notification->title                = 'Invoice Approved';
    						$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.'  has been approved by '.auth()->user()->name.'.';
    						$notification->read_status          = false;
    						$notification->data_id              = $invoice->id;
    						$notification->save();

    						$owner = User::withoutGlobalScope('user_id')->find($value);

    						if (env('IS_MAIL_ENABLE', false) == true) {
    							$content = [
    								"name" =>$owner->name,
    								"body" => 'Invoice with Invoice Number '.$invoice->invoice_no.'  has been approved by '.auth()->user()->name.'.',

    							];
    							$recevier = Mail::to($owner->email)->send(new InvoiceUpdatesMail($content));
    						}
    					}
    				}

    				$invoiceActivityLog = new InvoiceActivityLog;
    				$invoiceActivityLog->invoice_id = $invoice->id;
    				$invoiceActivityLog->performed_by = auth()->id();
    				$invoiceActivityLog->action = 'invoice-approved';
    				$invoiceActivityLog->description = $request->description;
    				$invoiceActivityLog->save();

                    //creating time log

    				$lastInvoiceTL = InvoiceTimeLog::orderBy('id','DESC')->first();
    				$lastInvoiceTL->update(['closing_date' => date('Y-m-d')]);

    				$invoiceTimeLog = new InvoiceTimeLog;
    				$invoiceTimeLog->invoice_id = $invoice->id;
    				$invoiceTimeLog->current_user_id = $request->assigned_to ? $request->assigned_to : auth()->id();
    				$invoiceTimeLog->status = 'approved';
    				$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    				$invoiceTimeLog->opening_date = date('Y-m-d');
    				$invoiceTimeLog->closing_date = date('Y-m-d');
    				$invoiceTimeLog->save();

    				$invoiceTimeLog = new InvoiceTimeLog;
    				$invoiceTimeLog->invoice_id = $invoice->id;
    				$invoiceTimeLog->current_user_id = $request->assigned_to ? $request->assigned_to : auth()->id();
    				$invoiceTimeLog->status = 'pending_with_treasurer';
    				$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    				$invoiceTimeLog->opening_date = date('Y-m-d');
    				$invoiceTimeLog->closing_date = NULL;
    				$invoiceTimeLog->save();
    			}
    			elseif($request->action == 'paid')
    			{
    			    
    			   
                    //updating status paid
    				Invoice::whereIn('id',$ids)->update(['last_action_performed_by'=>auth()->id(),'description'=>$request->description,'status'=>"paid",'paid_date'=> date('Y-m-d'),'paid_amount'=> $request->paid_amount]);
    				$message = trans('translate.paid');
                    
    				foreach ($notify_user_ids as $key => $value) {
    					if (auth()->id() != $value) {
    						$notification = new Notification;
    						$notification->user_id              = $value;
    						$notification->sender_id            = auth()->id();
    						$notification->status_code          = 'success';
    						$notification->type                	= 'Invoice';
    						$notification->title                = 'Invoice Amount Paid';
    						$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.'  has been paid by '.auth()->user()->name.'.';
    						$notification->read_status          = false;
    						$notification->data_id              = $invoice->id;
    						$notification->save();

    						$owner = User::withoutGlobalScope('user_id')->find($value);

    						if (env('IS_MAIL_ENABLE', false) == true) {
    							$content = [
    								"name" =>$owner->name,
    								"body" => 'Invoice with Invoice Number '.$invoice->invoice_no.'  has been paid by '.auth()->user()->name.'.',

    							];
    							$recevier = Mail::to($owner->email)->send(new InvoiceUpdatesMail($content));
    						}
    					}
    				}
    				// if (auth()->id() != $invoice->contractor_id) {
    				// 	$notification = new Notification;
    				// 	$notification->user_id              = $invoice->contractor_id;
    				// 	$notification->sender_id            = auth()->id();
    				// 	$notification->status_code          = 'success';
    				// 	$notification->type                	= 'Invoice';
    				// 	$notification->title                = 'Invoice Amount Paid';
    				// 	$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.'  has been paid by '.auth()->user()->name.' and the paid amount is: '.$request->paid_amount;
    				// 	$notification->read_status          = false;
    				// 	$notification->data_id              = $invoice->id;
    				// 	$notification->save();

    				// 	$contractor = User::withoutGlobalScope('user_id')->find($invoice->contractor_id);
    				// 	if (env('IS_MAIL_ENABLE', false) == true) {
    				// 		$content = [
    				// 			"name" =>$contractor->name,
    				// 			"body" => 'Invoice with Invoice Number '.$invoice->invoice_no.'  has been paid by '.auth()->user()->name.'.',

    				// 		];
    				// 		$recevier = Mail::to($contractor->email)->send(new InvoiceUpdatesMail($content));
    				// 	}
    				// }
    				// foreach ($invoice->owners as $key => $value) {
        //                 if (auth()->id() != $value) {
	    			// 		$notification = new Notification;
	    			// 		$notification->user_id              = $$value;
	    			// 		$notification->sender_id            = auth()->id();
	    			// 		$notification->status_code          = 'success';
	    			// 		$notification->type                	= 'Invoice';
	    			// 		$notification->title                = 'Invoice Amount Paid';
	    			// 		$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.'  has been paid by '.auth()->user()->name.'.';
	    			// 		$notification->read_status          = false;
	    			// 		$notification->data_id              = $invoice->id;
	    			// 		$notification->save();

	    			// 		$owner = User::withoutGlobalScope('user_id')->find($$value);

	    			// 		if (env('IS_MAIL_ENABLE', false) == true) {
	    			// 			$content = [
	    			// 				"name" =>$owner->name,
	    			// 				"body" => 'Invoice with Invoice Number '.$invoice->invoice_no.'  has been paid by '.auth()->user()->name.'.',

	    			// 			];
	    			// 			$recevier = Mail::to($owner->email)->send(new InvoiceUpdatesMail($content));
	    			// 		}
	    			// 	}
    				// }
    				// foreach ($invoice->owners as $key => $value) {
        //                 if (auth()->id() != $value) {
	    			// 		$notification = new Notification;
	    			// 		$notification->user_id              = $value;
	    			// 		$notification->sender_id            = auth()->id();
	    			// 		$notification->status_code          = 'success';
	    			// 		$notification->type                	= 'Invoice';
	    			// 		$notification->title                = 'Invoice Amount Paid';
	    			// 		$notification->message              = 'Invoice with Invoice Number '.$invoice->invoice_no.'  has been paid by '.auth()->user()->name.'.';
	    			// 		$notification->read_status          = false;
	    			// 		$notification->data_id              = $invoice->id;
	    			// 		$notification->save();

	    			// 		$epcm = User::withoutGlobalScope('user_id')->find($value);

	    			// 		if (env('IS_MAIL_ENABLE', false) == true) {
	    			// 			$content = [
	    			// 				"name" =>$epcm->name,
	    			// 				"body" => 'Invoice with Invoice Number '.$invoice->invoice_no.'  has been paid by '.auth()->user()->name.'.',

	    			// 			];
	    			// 			$recevier = Mail::to($epcm->email)->send(new InvoiceUpdatesMail($content));
	    			// 		}
	    			// 	}
    				// }
    				$invoiceActivityLog = new InvoiceActivityLog;
    				$invoiceActivityLog->invoice_id = $invoice->id;
    				$invoiceActivityLog->performed_by = auth()->id();
    				$invoiceActivityLog->action = 'invoice-paid';
    				$invoiceActivityLog->description = $request->description;
    				$invoiceActivityLog->save();

                    //creating time log

    				$lastInvoiceTL = InvoiceTimeLog::orderBy('id','DESC')->first();
    				$lastInvoiceTL->update(['closing_date' => date('Y-m-d')]);

    				$invoiceTimeLog = new InvoiceTimeLog;
    				$invoiceTimeLog->invoice_id = $invoice->id;
    				$invoiceTimeLog->current_user_id = $request->assigned_to ? $request->assigned_to : auth()->id();
    				$invoiceTimeLog->status = 'paid';
    				$invoiceTimeLog->hardcopy_recieved_date = $request->hardcopy_recieved_date ? $request->hardcopy_recieved_date : date('Y-m-d');
    				$invoiceTimeLog->opening_date = date('Y-m-d');
    				$invoiceTimeLog->closing_date = date('Y-m-d');
    				$invoiceTimeLog->save();
    			}
    		}

    		$invoices = Invoice::whereIn('id',$ids)->with('lastActionPerformedBy:id,name,user_type')->get();
    		DB::commit();
    		return response(prepareResult(false, $invoices, $message), config('httpcodes.success'));
    	}
    	catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }



    //get all data of invoice using barcode sacan
    public function scanBarCode(Request $request)
    {
    	try
    	{
    		$invoice = Invoice::with('epcms.epcm:id,name,email,mobile_number','owners.user:id,name,email,mobile_number','invoiceActivityLogs.performedBy:id,name,email')->where('unique_no',$request->barcode_number)->first();
    		return response(prepareResult(false, $invoice, trans('translate.invoice_detail')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    //Exportt invoice Activity Log of a perticular Invoice 
    public function invoiceActivityLogExport($invoice_id)
    {
    	try{
    		$rand = rand(10000,99999);

    		$excel = Excel::store(new InvoiceActivityLogExport($invoice_id), 'invoice_logs/'.$rand.'.xlsx' , 'export_path');

    		return response(prepareResult(false,['url' => '/exports/invoice_logs/'.$rand.'.xlsx'], trans('translate.data_exported')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    //Exportt invoice list according to provided data filter 
    public function invoiceExport(Request $request)
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

    		$excel = Excel::store(new InvoiceExportNew($data), 'invoices/'.$rand.'.xlsx' , 'export_path');

    		return response(prepareResult(false,['url' => '/exports/invoices/'.$rand.'.xlsx'], trans('translate.data_exported')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

    //showing activity log of pericular invoice
    public function viewLog($invoice_id)
    {
    	try{
    		$invoiceLog = InvoiceActivityLog::with('performedBy:id,name,email')->where('invoice_id',$invoice_id)->get();
    		return response(prepareResult(false,$invoiceLog, trans('translate.fetched_records')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }

     //Invoice log Data of perticular Invoice Pdf
    public function invoicePrint($invoice_id)
    {
    	try{
    		$invoice =Invoice::find($invoice_id);
    		$FileName = Str::slug($invoice->invoice_no).'.pdf';
    		$pdf = PDF::loadView('invoice_print',compact('invoice'));
    		$FilePath = 'uploads/' . $FileName;
    		\Storage::disk('public')->put($FilePath, $pdf->output(), 'public');

    		if(env('APP_ENV', 'local')==='production')
    		{
    			$callApi = secure_url('api/file-access/'.$FilePath);
    		}
    		else
    		{
    			$callApi = url('api/file-access/'.$FilePath);
    		}
            //$path = Storage::path('public/'.$FilePath);
            //$mime = "application/pdf";

    		return response(prepareResult(false, $callApi, trans('translate.data_exported')), config('httpcodes.success'));
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
    		$appSetting = AppSetting::first();
    		$invoice_ids = [];
    		$owners = InvoiceAssignedOwner::where('owner_id',auth()->id())->get(['invoice_id']);
    		foreach ($owners as $key => $value) {
    			$invoice_ids[] = $value->invoice_id;
    		}
    		$invoices = Invoice::where('created_by',auth()->id())->get(['id']);
    		foreach ($invoices as $key => $value) {
    			$invoice_ids[] = $value->id;
    		}
    		$invoiceTimeLog = [];
    		if (auth()->user()->user_type == 2) {
    			$invoiceTimeLog['pending_with_epcm_gtn_4weeks'] = InvoiceTimeLog::whereIn('invoice_id',$invoice_ids)->where('closing_date',NULL)->where('opening_date','<',$date_before_4_weeks)->whereIn('status',['pending_with_epcm','returned_to_epcm'])->count();
    			$invoiceTimeLog['pending_with_epcm_btwn_2_4weeks'] = InvoiceTimeLog::whereIn('invoice_id',$invoice_ids)->where('closing_date',NULL)->where('opening_date','>=',$date_before_4_weeks)->where('opening_date','<',$date_before_2_weeks)->whereIn('status',['pending_with_epcm','returned_to_epcm'])->count();
    			$invoiceTimeLog['pending_with_epcm_ltn_2_4weeks'] = InvoiceTimeLog::whereIn('invoice_id',$invoice_ids)->where('closing_date',NULL)->where('opening_date','>=',$date_before_2_weeks)->whereIn('status',['pending_with_epcm','returned_to_epcm'])->count();
    			$invoiceTimeLog['pending_with_owner_gtn_4weeks'] = InvoiceTimeLog::whereIn('invoice_id',$invoice_ids)->where('closing_date',NULL)->where('opening_date','<',$date_before_4_weeks)->whereIn('status',['pending_with_owner','returned_to_owner'])->count();
    			$invoiceTimeLog['pending_with_owner_btwn_2_4weeks'] = InvoiceTimeLog::whereIn('invoice_id',$invoice_ids)->where('closing_date',NULL)->where('opening_date','>=',$date_before_4_weeks)->where('opening_date','<',$date_before_2_weeks)->whereIn('status',['pending_with_owner','returned_to_owner'])->count();
    			$invoiceTimeLog['pending_with_owner_ltn_2_4weeks'] = InvoiceTimeLog::whereIn('invoice_id',$invoice_ids)->where('closing_date',NULL)->where('opening_date','>=',$date_before_2_weeks)->whereIn('status',['pending_with_owner','returned_to_owner'])->count();
    			$invoiceTimeLog['pending_with_owner_finance_gtn_4weeks'] = InvoiceTimeLog::whereIn('invoice_id',$invoice_ids)->where('closing_date',NULL)->where('opening_date','<',$date_before_4_weeks)->whereIn('status',['pending_with_treasurer','pending_with_owner_finance'])->count();
    			$invoiceTimeLog['pending_with_owner_finance_btwn_2_4weeks'] = InvoiceTimeLog::whereIn('invoice_id',$invoice_ids)->where('closing_date',NULL)->where('opening_date','>=',$date_before_4_weeks)->where('opening_date','<',$date_before_2_weeks)->whereIn('status',['pending_with_treasurer','pending_with_owner_finance'])->count();
    			$invoiceTimeLog['pending_with_owner_finance_ltn_2_4weeks'] = InvoiceTimeLog::whereIn('invoice_id',$invoice_ids)->where('closing_date',NULL)->where('opening_date','>=',$date_before_2_weeks)->whereIn('status',['pending_with_treasurer','pending_with_owner_finance'])->count();
    		}
    		else
    		{
    			$invoiceTimeLog['pending_with_epcm_gtn_4weeks'] = InvoiceTimeLog::where('closing_date',NULL)->where('opening_date','<',$date_before_4_weeks)->whereIn('status',['pending_with_epcm','returned_to_epcm'])->count();
    			$invoiceTimeLog['pending_with_epcm_btwn_2_4weeks'] = InvoiceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_4_weeks)->where('opening_date','<',$date_before_2_weeks)->whereIn('status',['pending_with_epcm','returned_to_epcm'])->count();
    			$invoiceTimeLog['pending_with_epcm_ltn_2_4weeks'] = InvoiceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_2_weeks)->whereIn('status',['pending_with_epcm','returned_to_epcm'])->count();
    			$invoiceTimeLog['pending_with_owner_gtn_4weeks'] = InvoiceTimeLog::where('closing_date',NULL)->where('opening_date','<',$date_before_4_weeks)->whereIn('status',['pending_with_owner','returned_to_owner'])->count();
    			$invoiceTimeLog['pending_with_owner_btwn_2_4weeks'] = InvoiceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_4_weeks)->where('opening_date','<',$date_before_2_weeks)->whereIn('status',['pending_with_owner','returned_to_owner'])->count();
    			$invoiceTimeLog['pending_with_owner_ltn_2_4weeks'] = InvoiceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_2_weeks)->whereIn('status',['pending_with_owner','returned_to_owner'])->count();
    			$invoiceTimeLog['pending_with_owner_finance_gtn_4weeks'] = InvoiceTimeLog::where('closing_date',NULL)->where('opening_date','<',$date_before_4_weeks)->whereIn('status',['pending_with_treasurer','pending_with_owner_finance'])->count();
    			$invoiceTimeLog['pending_with_owner_finance_btwn_2_4weeks'] = InvoiceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_4_weeks)->where('opening_date','<',$date_before_2_weeks)->whereIn('status',['pending_with_treasurer','pending_with_owner_finance'])->count();
    			$invoiceTimeLog['pending_with_owner_finance_ltn_2_4weeks'] = InvoiceTimeLog::where('closing_date',NULL)->where('opening_date','>=',$date_before_2_weeks)->whereIn('status',['pending_with_treasurer','pending_with_owner_finance'])->count();
    		}

    		return response(prepareResult(false,$invoiceTimeLog, trans('translate.fetched_records')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }


    public function invoiceDataInr(Request $request)
    {
    	try {
    		$query = Invoice::select('invoices.id','invoices.package','invoices.contract_number','invoices.basic_amount','invoices.gst_amount','invoices.total_amount','invoices.paid_amount','contracts.vendor_code','contracts.total_contract_value')->where('invoices.status','paid');

    		$query->join('contracts', function($join) use ($request) {
    			$join->on('invoices.contract_number', '=', 'contracts.contract_number');
    		});

            //retrieving data according to log in user user type
    		if(auth()->user()->user_type == 2){
    			// $query->join('invoice_assigned_owners', function($join) use ($request) {
    			// 	$join->on('invoices.id', '=', 'invoice_assigned_owners.invoice_id')
    			// 	->where('invoice_assigned_owners.owner_id',auth()->id())
    			// 	->orWhere('invoices.owner_id', auth()->id())
    			// 	->orWhere('invoices.created_by', auth()->id());
    			// });
    			$query->where(function($q) {
    				$q->whereJsonContains('invoices.owners', auth()->id())
    				->orWhere('invoices.created_by', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 3){
    			$query->where(function($q) {
    				$q->whereJsonContains('invoices.epcms', auth()->id())
    				->orWhere('invoices.created_by', auth()->id());
    			});
    		}
    		elseif(auth()->user()->user_type == 4){
    			$query->where(function($q) {
    				$q->where('invoices.contractor_id', auth()->id())
    				->orWhere('invoices.created_by', auth()->id());
    			});
    		}

    		$query = $query->distinct(['invoices.id'])->get();

    		return response(prepareResult(false, $query, trans('translate.invoice_list')), config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }
}
