<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractType;
use App\Models\ContractTypeCheckList;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use DB;
use Str;
use App\Models\User;

class ContractTypeController extends Controller
{
   
    public function __construct()
    {
        $this->middleware('permission:contract-types-browse',['only' => ['contractTypes','show']]);
        $this->middleware('permission:contract-types-add', ['only' => ['store']]);
        $this->middleware('permission:contract-types-edit', ['only' => ['update']]);
        // $this->middleware('permission:contract-types-read', ['only' => ['show']]);
        // $this->middleware('permission:contract-types-delete', ['only' => ['destroy']]);

    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function contractTypes(Request $request)
    {
        try {
            $query = ContractType::select('*')->with('checkLists')->orderBy('id','DESC');

            if(!empty($request->contract_type))
            {
                $query->where('contract_type', 'LIKE', '%'.$request->contract_type.'%');
            }
            if(!empty($request->check_list_id))
            {
                $query->whereJsonContains('check_lists', $request->check_list_id);
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
           return response(prepareResult(false, $query, trans('translate.contract_type_list')), config('httpcodes.success'));
        } catch(Exception $exception) {
            \Log::error($exception);
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'contract_type'   => 'required|unique:contract_types,contract_type',
            "check_lists.*"  => 'required|exists:check_lists,id'
        ]);
        if ($validator->fails()) {
            return response(prepareResult(true, $validator->messages(), $validator->messages()->first()), config('httpcodes.bad_request'));
        }
        
        DB::beginTransaction();
        try {
            $contractType = new ContractType;
            $contractType->contract_type = $request->contract_type;
            $contractType->check_lists  = json_encode($request->check_lists);
            $contractType->save();
            foreach ($request->check_lists as $key => $value) {
                $ctcl = new ContractTypeCheckList();
                $ctcl->contract_type_id = $contractType->id;
                $ctcl->check_list_id = $value;
                $ctcl->save();
            }
            $contractType = ContractType::with('checkLists')->find($contractType->id);
            DB::commit();            
            return response()->json(prepareResult(false, $contractType, trans('translate.contract_type_created')),config('httpcodes.created'));
        } catch(Exception $exception) {
            \Log::error($exception);
            DB::rollback();
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ContractType $contractType)
    {
        try {
            return response(prepareResult(false, $contractType, trans('translate.contract_type_detail')), config('httpcodes.success'));

        } catch(Exception $exception) {
             \Log::error($exception);
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ContractType $contractType)
    {
        $validator = \Validator::make($request->all(), [
            'contract_type'   => 'required|unique:contract_types,contract_type,'.$contractType->id,
            "check_lists.*"  => 'required|exists:check_lists,id'
        ]);
        if ($validator->fails()) {
             return response(prepareResult(true, $validator->messages(), $validator->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $contractType->contract_type  = $request->contract_type;
            $contractType->check_lists  = json_encode($request->check_lists);
            $contractType->save();
            ContractTypeCheckList::where('contract_type_id',$contractType->id)->delete();
            foreach ($request->check_lists as $key => $value) {
                $ctcl = new ContractTypeCheckList();
                $ctcl->contract_type_id = $contractType->id;
                $ctcl->check_list_id = $value;
                $ctcl->save();
            }
            DB::commit();
            return response()->json(prepareResult(false, $contractType, trans('translate.contract_type_updated')),config('httpcodes.success'));
        } catch(Exception $exception) {
            \Log::error($exception);
            DB::rollback();
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ContractType $contractType)
    {
        try {
            $contractType->delete();
            return response()->json(prepareResult(false, [], trans('translate.contract_type_deleted')), config('httpcodes.success'));            
        } catch(Exception $exception) {
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
