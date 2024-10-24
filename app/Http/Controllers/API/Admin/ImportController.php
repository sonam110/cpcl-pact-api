<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Str;
use DB;
use Auth;
use App\Imports\HindrancesImport;
use App\Imports\InvoicesImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Hindrance;
use App\Models\Contract;
use App\Models\InvoiceEpcm;
use App\Models\ContractEpcm;
use App\Models\ContractOwner;
use App\Models\InvoiceAssignedOwner;
use App\Models\InvoiceCheckVerification;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use DNS1D;
use DNS2D;


class ImportController extends Controller
{
	public function invoicesImport(Request $request) 
	{
		$validation = \Validator::make($request->all(), [
			'file'     => 'required|mimes:xlsx,csv',
			'contractor_id'=>'required|exists:users,id'
		]);

		if ($validation->fails()) {
			return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
		}

		$fileArray = array();
		$file = $request->file;
		$extension = strtolower($file->getClientOriginalExtension());

		DB::beginTransaction();
		try 
		{
			$imports = Excel::toArray(new InvoicesImport, $request->file);
			$datas = $imports[0];
			if(auth()->user()->user_type == 3)
			{
				$status = 'pending_with_owner';
			}
			else
			{
				$status = 'pending_with_epcm';
			}
			foreach($imports[0] as $key => $import) 
			{
				if(sizeof($imports[0])==$key)
				{
					break;
				}
				if(!empty($import['invoice_no']))
				{
					$invoice = Invoice::where('invoice_no', $import['invoice_no'])->first();
					if(!$invoice)
					{
						$unique_no = time().rand(1000,9999);
						$barcode =  DNS1D::getBarcodePNGPath($unique_no, 'C128',3,80,array(0,0,0), true);
						$uploaded_files = [];
						$files = explode(',', $import['uploaded_files']);
						foreach ($files as $key => $value) {
							$filePath = 'uploads/' . $value;
							if(env('APP_ENV', 'local')==='production')
			                {
			                    $callApi = secure_url('api/file-access/'.$filePath);
			                }
			                else
			                {
			                    $callApi = url('api/file-access/'.$filePath);
			                }
							$uploaded_files[] = ['file_name'=>$callApi,'file_extension'=>'','uploading_file_name'=>$value] ;
						}

						$epcms = $request->epcms;
						$owners = $request->owners;

						//create contract with contract number if data is not there
						$contract = Contract::where('contract_number',$import['contract_number'])->first();
						if (empty($contract)) {
							$contract = new Contract;
							$contract->user_id         	= auth()->id();
							$contract->contract_number 	= $import['contract_number'];
							$contract->contractor_id 	= $request->contractor_id;
							$contract->description 		= '';
							$contract->save();
						}

						// ContractEpcm::where('contract_id',$contract->id)->delete();
						foreach ($epcms as $key => $value) {
							$contractEpcm = new ContractEpcm;
							$contractEpcm->contract_id = $contract->id;
							$contractEpcm->epcm_id = $value;
							$contractEpcm->save();
						}

						foreach ($owners as $key => $value) {
							$contractEpcm = new ContractOwner;
							$contractEpcm->contract_id = $contract->id;
							$contractEpcm->owner_id = $value;
							$contractEpcm->save();
						}


						$contractor = User::find($request->contractor_id);

						$invoice                 = new Invoice;
						$invoice->unique_no      = $unique_no;
						$invoice->contractor_id  = $request->contractor_id;
						$invoice->contract_number= $import['contract_number'];
			    		$invoice->ra_bill_number = $import['ra_bill_number'];
						$invoice->epcms        	 = json_encode($epcms);
						$invoice->owners       	 = json_encode($owners);
						$invoice->invoice_no     = $import['invoice_no'];
						$invoice->invoice_type   = $import['invoice_type'];
						$invoice->description    = $import['description'];
						$invoice->notes          = $import['notes'];
						$invoice->vendor_name    = $contractor->name;;
						$invoice->vendor_contact_number  = $contractor->mobile_number;
						$invoice->vendor_contact_email   = $contractor->email;
						$invoice->basic_amount   = $import['basic_amount'];
						$invoice->gst_amount     = $import['gst_amount'];
						$invoice->total_amount   = $import['total_amount'];
						$invoice->package        = $import['package'];
						$invoice->status         = $status;
						$invoice->barcode        = $barcode;
						$invoice->uploaded_files = json_encode($uploaded_files);
						$invoice->approved_date  = $import['approved_date'] ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($import['approved_date']) : NULL;
						$invoice->paid_date      = $import['paid_date'] ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($import['paid_date']) : NULL;
						$invoice->invoice_date   = $import['invoice_date'] ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($import['invoice_date']) : NULL;
						$invoice->created_by     = auth()->id();
						$invoice->creator_user_type     = auth()->user()->user_type;
						$invoice->save();

						foreach ($epcms as $key => $epcm) {
							$invoiceEpcm = new InvoiceEpcm();
							$invoiceEpcm->invoice_id = $invoice->id;
							$invoiceEpcm->epcm_id = $epcm;
							$invoiceEpcm->save();
						}

						foreach ($owners as $key => $owner) {
							$invoiceAssignedOwner = new InvoiceAssignedOwner();
							$invoiceAssignedOwner->invoice_id = $invoice->id;
							$invoiceAssignedOwner->owner_id = $owner;
							$invoiceAssignedOwner->save();
						}
					}
				}
			}
			DB::commit();
			return response(prepareResult(false, [], trans('translate.data_imported')),config('httpcodes.created'));
		} catch (\Throwable $e) {
			\Log::error($e);
			DB::rollback();
			return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
		}
	}


	public function hindrancesImport(Request $request) 
	{
		$validation = \Validator::make($request->all(), [
			'file'     => 'required|mimes:xlsx,csv'
		]);

		if ($validation->fails()) {
			return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
		}

		$fileArray = array();
		$file = $request->file;
		$extension = strtolower($file->getClientOriginalExtension());

		DB::beginTransaction();
		try 
		{
			
			$imports = Excel::toArray(new HindrancesImport, $request->file);
			$datas = $imports[0];
			foreach($imports[0] as $key => $import) 
			{
				if(sizeof($imports[0])==$key)
				{
					break;
				}
				if(!empty($import['title']))
				{
					$hindrance = Hindrance::where('title', $import['title'])->first();
					if(!$hindrance)
					{
						$hindrance_code = time().rand(1000,9999);
						$uploaded_files = [];
						$files = explode(',', $import['uploaded_files']);
						foreach ($files as $key => $value) {
							$filePath = 'uploads/' . $value;
							if(env('APP_ENV', 'local')==='production')
			                {
			                    $callApi = secure_url('api/file-access/'.$filePath);
			                }
			                else
			                {
			                    $callApi = url('api/file-access/'.$filePath);
			                }
							$uploaded_files[] = ['file_name'=>$callApi,'file_extension'=>'','uploading_file_name'=>$value] ;
						}
						$hindrance                 = new Hindrance;
						$hindrance->hindrance_code = $hindrance_code;
						$hindrance->project_id  = $import['project_id'];
						$hindrance->contractor_id  = $import['contractor_id'];
						$hindrance->epcm_id        = $import['epcm_id'];
						$hindrance->owner_id       = $import['owner_id'];
						$hindrance->title     = $import['title'];
						$hindrance->hindrance_type   = $import['hindrance_type'];
						$hindrance->description    = $import['description'];
						$hindrance->notes          = $import['notes'];
						$hindrance->vendor_name    = $import['vendor_name'];
						$hindrance->vendor_contact_number  = $import['vendor_contact_number'];
						$hindrance->vendor_contact_email  = $import['vendor_contact_email'];
						$hindrance->package        = $import['package'];
						$hindrance->status         = $import['status'];
						$hindrance->uploaded_files = json_encode($uploaded_files);
						$hindrance->reason_of_rejection = $import['reason_of_rejection'];
						$hindrance->approved_date  = $import['approved_date']? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($import['approved_date']) : NULL;
						$hindrance->resolved_date      = $import['resolved_date']? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($import['resolved_date']) : NULL;
						$hindrance->action_performed         = $import['action_performed'];
						$hindrance->due_date         = $import['due_date']? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($import['due_date']) : NULL;
						$hindrance->priority         = $import['priority'];
						$hindrance->created_by     = auth()->id();
						$hindrance->save();
					}
				}
			}
			DB::commit();
			return response(prepareResult(false, [], trans('translate.data_imported')),config('httpcodes.created'));
		} catch (\Throwable $e) {
			\Log::error($e);
			DB::rollback();
			return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
		}
	}

	public function hindranceSampleFile()
	{
		$filepath = 'sample-files/sample-hindrance-import.xlsx';
		return response(prepareResult(false, $filepath, trans('translate.sample_file_download_path')),config('httpcodes.success'));
	}

	public function invoiceSampleFile()
	{
		$filepath = 'sample-files/sample-invoice-import.xlsx';
		return response(prepareResult(false, $filepath, trans('translate.sample_file_download_path')),config('httpcodes.success'));
	}
}
