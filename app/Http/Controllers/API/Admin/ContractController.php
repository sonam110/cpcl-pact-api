<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\ContractEpcm;
use App\Models\ContractOwner;
use App\Models\Notification;
use App\Models\User;
use Validator;
use Auth;
use Exception;
use DB;;
class ContractController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:contracts-browse',['only' => ['contracts','show']]);
        $this->middleware('permission:contracts-add', ['only' => ['store']]);
        $this->middleware('permission:contracts-edit', ['only' => ['update']]);
        // $this->middleware('permission:contracts-read', ['only' => ['show']]);
        // $this->middleware('permission:contracts-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //listing contracts   
    public function contracts(Request $request)
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
            $query = Contract::orderby($column,$dir)->with('contractEpcms.epcm:id,name,email,mobile_number','contractOwners.owner:id,name,email,mobile_number','contractor:id,name,email,mobile_number');

            // if (auth()->user()->role_id != 1) {
            //     $query->where('user_id', auth()->id());
            // }
            
            if(!empty($request->name))
            {
                $query->where('name', 'LIKE', '%'.$request->name.'%');
            }
            if(!empty($request->contract_number))
            {
                $query->where('contract_number',$request->contract_number);
            }
            if(!empty($request->contractor_id))
            {
                $query->where('contractor_id',$request->contractor_id);
            }
            if(!empty($request->status))
            {
                $query->where('status',$request->status);
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

            return response(prepareResult(false, $query, trans('translate.contract_list')), config('httpcodes.success'));
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
    //creating new contract
    public function store(Request $request)
    {
        $validation = \Validator::make($request->all(), [
            'contract_number'      => 'required|unique:contracts,contract_number',
            "epcms"  => 'required|array|max:2',
            "owners"  => 'required|array|max:4',
            "epcms.*"  => 'exists:users,id',
            "owners.*"  => 'exists:users,id',
            'contractor_id' => 'required|exists:users,id',
            'package' =>'required',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $contract = new Contract;
            $contract->user_id              = auth()->id();
            $contract->contract_number = $request->contract_number;
            $contract->contract_name = $request->contract_name;
            $contract->contractor_id = $request->contractor_id;
            $contract->package = $request->package;
            $contract->vendor_code = $request->vendor_code;
            $contract->description = $request->description;
            $contract->total_contract_value = $request->total_contract_value;
            $contract->save();

            foreach ($request->epcms as $key => $epcm) {
                $contractEpcm = new ContractEpcm;
                $contractEpcm->contract_id = $contract->id;
                $contractEpcm->epcm_id = $epcm;
                $contractEpcm->save();

                $notification = new Notification;
                $notification->user_id              = $epcm;
                $notification->sender_id            = auth()->id();
                $notification->status_code          = 'success';
                $notification->type                 = 'Contract';
                $notification->title                = 'New Contract Assigned';
                $notification->message              = 'New Contract '.$contract->contract_number.' Assigned.';
                $notification->read_status          = false;
                $notification->data_id              = $contract->id;
                $notification->save();
            }

            foreach ($request->owners as $key => $owner) {
                $contractEpcm = new ContractOwner;
                $contractEpcm->contract_id = $contract->id;
                $contractEpcm->owner_id = $owner;
                $contractEpcm->save();

                 $notification = new Notification;
                $notification->user_id              = $owner;
                $notification->sender_id            = auth()->id();
                $notification->status_code          = 'success';
                $notification->type                 = 'Contract';
                $notification->title                = 'New Contract Assigned';
                $notification->message              = 'New Contract '.$contract->contract_number.' Assigned.';
                $notification->read_status          = false;
                $notification->data_id              = $contract->id;
                $notification->save();
            }

            $contract = Contract::with('contractEpcms.epcm:id,name,email,mobile_number','contractOwners.owner:id,name,email,mobile_number','contractor:id,name,email,mobile_number')->find($contract->id);
            DB::commit();
            return response(prepareResult(false, $contract, trans('translate.contract_created')),config('httpcodes.created'));
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
    //view contract
    public function show(Contract $contract)
    {
        try
        {
            $contract = Contract::with('contractEpcms.epcm:id,name,email,mobile_number','contractOwners.owner:id,name,email,mobile_number','contractor:id,name,email,mobile_number')->find($contract->id);
            return response(prepareResult(false, $contract, trans('translate.fetched_detail')), config('httpcodes.success'));
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
    //update contracts data
    public function update(Request $request, $id)
    {
        $validation = \Validator::make($request->all(), [
            'contract_number'     => 'required|unique:contracts,contract_number,'.$id,
            "epcms"  => 'required|array|max:2',
            "owners"  => 'required|array|max:4',
            "epcms.*"  => 'exists:users,id',
            "owners.*"  => 'exists:users,id',
            'contractor_id' => 'required|exists:users,id',
            'package' =>'required',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $contract = Contract::where('id',$id)->first();
            if(!$contract)
            {
                return response(prepareResult(true, [],trans('translate.record_not_found')), config('httpcodes.not_found'));
            }
            $contract->contract_number = $request->contract_number;
            $contract->contract_name = $request->contract_name;
            $contract->contractor_id = $request->contractor_id;
            $contract->package = $request->package;
            $contract->vendor_code = $request->vendor_code;
            $contract->description = $request->description;
            $contract->save();

            ContractEpcm::where('contract_id',$id)->delete();
            ContractOwner::where('contract_id',$id)->delete();
            foreach ($request->epcms as $key => $epcm) {
                $contractEpcm = new ContractEpcm;
                $contractEpcm->contract_id = $contract->id;
                $contractEpcm->epcm_id = $epcm;
                $contractEpcm->save();

                $notification = new Notification;
                $notification->user_id              = $epcm;
                $notification->sender_id            = auth()->id();
                $notification->status_code          = 'success';
                $notification->type                 = 'Contract';
                $notification->title                = 'New Contract Assigned';
                $notification->message              = 'New Contract '.$contract->contract_number.' Assigned.';
                $notification->read_status          = false;
                $notification->data_id              = $contract->id;
                $notification->save();
            }

            foreach ($request->owners as $key => $owner) {
                $contractEpcm = new ContractOwner;
                $contractEpcm->contract_id = $contract->id;
                $contractEpcm->owner_id = $owner;
                $contractEpcm->save();

                 $notification = new Notification;
                $notification->user_id              = $owner;
                $notification->sender_id            = auth()->id();
                $notification->status_code          = 'success';
                $notification->type                 = 'Contract';
                $notification->title                = 'New Contract Assigned';
                $notification->message              = 'New Contract '.$contract->contract_number.' Assigned.';
                $notification->read_status          = false;
                $notification->data_id              = $contract->id;
                $notification->save();
            }

            $contract = Contract::with('contractEpcms.epcm:id,name,email,mobile_number','contractOwners.owner:id,name,email,mobile_number','contractor:id,name,email,mobile_number')->find($contract->id);
            DB::commit();
            return response(prepareResult(false, $contract, trans('translate.contract_updated')),config('httpcodes.success'));
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
    //delete contract
    public function destroy($id)
    {
        try {
            $contract= Contract::where('id',$id)->first();
            if (!is_object($contract)) {
                 return response(prepareResult(true, [],trans('translate.record_not_found')), config('httpcodes.not_found'));
            }
            
            $deleteContract = $contract->delete();
            return response(prepareResult(false, [], trans('translate.contract_deleted')), config('httpcodes.success'));
        }
        catch(Exception $exception) {
            return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
