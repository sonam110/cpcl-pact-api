<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Notification;
use App\Models\User;
use Validator;
use Auth;
use Exception;
use DB;;
class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:projects-browse',['only' => ['projects','show']]);
        $this->middleware('permission:projects-add', ['only' => ['store']]);
        $this->middleware('permission:projects-edit', ['only' => ['update','action']]);
        // $this->middleware('permission:projects-read', ['only' => ['show']]);
        // $this->middleware('permission:projects-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //listing projects   
    public function projects(Request $request)
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
            $query = Project::orderby($column,$dir)->with('user:id,name,email');

            // if (auth()->user()->role_id != 1) {
            //     $query->where('user_id', auth()->id());
            // }
            
            if(!empty($request->name))
            {
                $query->where('name', 'LIKE', '%'.$request->name.'%');
            }
            if(!empty($request->projectId))
            {
                $query->where('projectId',$request->projectId);
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

            return response(prepareResult(false, $query, trans('translate.project_list')), config('httpcodes.success'));
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
    //creating new project
    public function store(Request $request)
    {
        $validation = \Validator::make($request->all(), [
            // 'projectId'      => 'required|unique:projects,projectId',
            'name'      => 'required|regex:/^[a-zA-Z0-9-_ @#,."\']+$/',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $project = new Project;
            $project->user_id  = auth()->id();
            $project->projectId = $request->projectId;
            $project->name = $request->name;
            $project->description = $request->description;
            $project->status = $request->status ? $request->status : 1;
            $project->save();

            //notify admin about new project
            $notification = new Notification;
            $notification->user_id              = User::first()->id;
            $notification->sender_id            = auth()->id();
            $notification->status_code          = 'success';
            $notification->type                = 'Project';
            // $notification->event                = 'Created';
            $notification->title                = 'New Project Created';
            $notification->message              = 'New Project '.$project->name.' created.';
            $notification->read_status          = false;
            $notification->data_id              = $project->id;
            $notification->save();
            DB::commit();
            return response(prepareResult(false, $project, trans('translate.project_created')),config('httpcodes.created'));
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
    //view project
    public function show(Project $project)
    {
        try
        {
            $project = Project::with('user:id,name,email')->find($project->id);
            return response(prepareResult(false, $project, trans('translate.project_detail')), config('httpcodes.success'));
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
    //update projects data
    public function update(Request $request, $id)
    {
        $validation = \Validator::make($request->all(), [
            'name'      => 'required|regex:/^[a-zA-Z0-9-_ @#,."\']+$/',
            // 'projectId'     => 'required|unique:projects,projectId,'.$id
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $project = Project::where('id',$id)->first();
            if(!$project)
            {
                return response(prepareResult(true, [],trans('translate.record_not_found')), config('httpcodes.not_found'));
            }
            $project->projectId = $request->projectId;
            $project->name = $request->name;
            $project->description = $request->description;
            $project->status = $request->status ? $request->status : 1;
            $project->save();

            DB::commit();
            return response(prepareResult(false, $project, trans('translate.project_updated')),config('httpcodes.success'));
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
    //delete project
    public function destroy($id)
    {
        try {
            $project= Project::where('id',$id)->first();
            if (!is_object($project)) {
                 return response(prepareResult(true, [],trans('translate.record_not_found')), config('httpcodes.not_found'));
            }
            
            $deleteProject = $project->delete();
            return response(prepareResult(false, [], trans('translate.project_deleted')), config('httpcodes.success'));
        }
        catch(Exception $exception) {
            return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    //performed action on projects
    public function action(Request $request)
    {
        $validation = \Validator::make($request->all(), [
            'ids'      => 'required',
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
            if($request->action == 'delete')
            {
                $projects = Project::whereIn('id',$ids)->delete();
                $message = trans('translate.project_deleted');
            }
            elseif($request->action == 'inactive')
            {
                Project::whereIn('id',$ids)->update(['status'=>"2"]);
                $message = trans('translate.project_inactivated');
            }
            elseif($request->action == 'active')
            {
                Project::whereIn('id',$ids)->update(['status'=>"1"]);
                $message = trans('translate.project_activated');
            }
            $projects = Project::whereIn('id',$ids)->get();
            DB::commit();
            return response(prepareResult(false, $projects, $message), config('httpcodes.success'));
        }
        catch (\Throwable $e) {
            \Log::error($e);
            return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
