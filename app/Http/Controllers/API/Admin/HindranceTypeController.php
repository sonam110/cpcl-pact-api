<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\HindranceType;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use DB;
use Str;
use App\Models\User;

class HindranceTypeController extends Controller
{
   
    public function __construct()
    {
        $this->middleware('permission:hindrance-types-browse',['only' => ['hindranceTypes','show']]);
        $this->middleware('permission:hindrance-types-add', ['only' => ['store']]);
        $this->middleware('permission:hindrance-types-edit', ['only' => ['update']]);
        // $this->middleware('permission:hindrance-types-read', ['only' => ['show']]);
        // $this->middleware('permission:hindrance-types-delete', ['only' => ['destroy']]);

    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function hindranceTypes(Request $request)
    {
        try {
            $query = HindranceType::select('*');

            if(!empty($request->name))
            {
                $query->where('name', 'LIKE', '%'.$request->name.'%');
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
           return response(prepareResult(false, $query, trans('translate.hindrance_type_list')), config('httpcodes.success'));
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
            'name'   => 'required|unique:hindrance_types,name'
        ]);
        if ($validator->fails()) {
            return response(prepareResult(true, $validator->messages(), $validator->messages()->first()), config('httpcodes.bad_request'));
        }
        
        DB::beginTransaction();
        try {
            $hindranceType = new HindranceType;
            $hindranceType->name = $request->name;
            $hindranceType->description  = $request->description;
            $hindranceType->save();
            DB::commit();            
            return response()->json(prepareResult(false, $hindranceType, trans('translate.hindrance_type_created')),config('httpcodes.created'));
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
    public function show(HindranceType $hindranceType)
    {
        try {
            return response(prepareResult(false, $hindranceType, trans('translate.hindrance_type_detail')), config('httpcodes.success'));

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
    public function update(Request $request, HindranceType $hindranceType)
    {
        $validator = \Validator::make($request->all(), [
            'name'   => 'required|unique:hindrance_types,name,'.$hindranceType->id
        ]);
        if ($validator->fails()) {
             return response(prepareResult(true, $validator->messages(), $validator->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $hindranceType->name  = $request->name;
            $hindranceType->description  = $request->description;
            $hindranceType->save();
            DB::commit();
            return response()->json(prepareResult(false, $hindranceType, trans('translate.hindrance_type_updated')),config('httpcodes.success'));
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
    public function destroy(HindranceType $hindranceType)
    {
        try {
            $hindranceType->delete();
            return response()->json(prepareResult(false, [], trans('translate.hindrance_type_deleted')), config('httpcodes.success'));            
        } catch(Exception $exception) {
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
