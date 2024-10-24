<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckList;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use DB;
use Str;
use App\Models\User;
use Validator;

class CheckListController extends Controller
{
   
    public function __construct()
    {
        $this->middleware('permission:check-lists-browse',['only' => ['check-lists','show']]);
        $this->middleware('permission:check-lists-add', ['only' => ['store']]);
        $this->middleware('permission:check-lists-edit', ['only' => ['update']]);
        // $this->middleware('permission:check-lists-read', ['only' => ['show']]);
        // $this->middleware('permission:check-lists-delete', ['only' => ['destroy']]);

    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function checkLists(Request $request)
    {
        try {
            $query = CheckList::select('*');

            if(!empty($request->check))
            {
                $query->where('check', 'LIKE', '%'.$request->check.'%');
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
           return response(prepareResult(false, $query, trans('translate.checklist_list')), config('httpcodes.success'));
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
            'checks'   => 'required|array',
            "checks.*.check"  => 'required|unique:check_lists,check'
        ]);
        if ($validator->fails()) {
            return response(prepareResult(true, $validator->messages(), $validator->messages()->first()), config('httpcodes.bad_request'));
        }
        
        DB::beginTransaction();
        try {

            $ids = [];
            foreach ($request->checks as $key => $check) {
                // $validator = Validator::make($request->all(),[      
                //     "checks.*.check"  => 'required|unique:check_lists,check']);
                // if ($validator->fails()) {
                //     return response(prepareResult(true, $validator->messages(), $validator->messages()->first()), config('httpcodes.bad_request'));
                // }
                $checkList = new CheckList;
                $checkList->check = $check['check'];
                $checkList->value  = $check['value'];
                $checkList->save();
                $ids[] =  $checkList->id;
            }
            $checkLists = CheckList::whereIn('id',$ids)->get();
            DB::commit();            
            return response()->json(prepareResult(false, $checkLists, trans('translate.checklist_created')),config('httpcodes.created'));
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
    public function show(CheckList $checkList)
    {
        try {
            return response(prepareResult(false, $checkList, trans('translate.checklist_detail')), config('httpcodes.success'));

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
    public function update(Request $request, CheckList $checkList)
    {
        $validator = \Validator::make($request->all(), [
            'check'   => 'required|unique:check_lists,check,'.$checkList->id
        ]);
        if ($validator->fails()) {
             return response(prepareResult(true, $validator->messages(), $validator->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $checkList->check  = $request->check;
            $checkList->value  = $request->value;
            $checkList->save();
            DB::commit();
            return response()->json(prepareResult(false, $checkList, trans('translate.checklist_updated')),config('httpcodes.success'));
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
    public function destroy(CheckList $checkList)
    {
        try {
            $checkList->delete();
            return response()->json(prepareResult(false, [], trans('translate.checklist_deleted')), config('httpcodes.success'));            
        } catch(Exception $exception) {
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
