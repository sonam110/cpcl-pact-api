<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Module;
use App\Models\AssigneModule;
use Validator;
use Auth;
use Exception;
use DB;
use Mail;
use Str;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user-browse',['only' => ['users','show']]);
        $this->middleware('permission:user-add', ['only' => ['store']]);
        $this->middleware('permission:user-edit', ['only' => ['update','userAction']]);
        // $this->middleware('permission:user-read', ['only' => ['show']]);
        // $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    
    public function users(Request $request)
    {
        try {
            $query = User::orderBy('created_at','DESC')->with('role:id,name,se_name','epcm:id,name,email,mobile_number','owner:id,name,email,mobile_number','package');
            
            if(auth()->user()->role_id!=1)
            {
                $query->where('role_id','!=','1');
            }

            if(!empty($request->email))
            {
                $checkEmails = User::select('id','email')
                    ->get();
                $ids = [];
                foreach ($checkEmails as $key => $checkEmail) 
                {
                    if($checkEmail->email==$request->email)
                    {
                        $ids[] = $checkEmail->id;
                    }
                }
                $query->whereIn('id', $ids);
            }

            if(!empty($request->name))
            {
                $checkNames = User::select('id','name')
                    ->get();
                $name_ids = [];
                foreach ($checkNames as $key => $checkName) 
                {
                    if(strpos( strtolower($checkName), strtolower($request->name)))
                    {
                        $name_ids[] = $checkName->id;
                    }
                }
                $query->whereIn('id', $name_ids);
                // $query->where('name', 'LIKE', '%'.$request->name.'%');
            }
            if(!empty($request->designation))
            {
                $query->where('designation', $request->designation);
            }
            if(!empty($request->user_type))
            {
                $query->where('user_type', $request->user_type);
            }
            if(!empty($request->mobile_number))
            {
                $query->where('mobile_number', 'LIKE', '%'.$request->mobile_number.'%');
            }
            if($request->status=='active')
            {
                $query->where('status', 1);
            }
            elseif($request->status=='inactive')
            {
                $query->where('status', 0);
            }

            if(!empty($request->role_id))
            {
                $query->where('role_id', $request->role_id);
            }
            if(!empty($request->user_type_arr))
            {
                $query->whereIn('user_type', $request->user_type_arr);
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

            return response(prepareResult(false, $query, trans('translate.user_list')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
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
        $validation = \Validator::make($request->all(), [
            'name'      => 'required|regex:/^[a-zA-Z0-9-_ ]+$/',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6',
            // 'package_id'  => 'exists:packages,id',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        if(checkUserExist($request->email))
        {
            return response(prepareResult(true, trans('translate.user_already_exist_with_this_email'), trans('translate.user_already_exist_with_this_email')), config('httpcodes.internal_server_error'));
        }

        DB::beginTransaction();
        try {
        	$x = 0; $y = 0; $z = 0;
        	if($request->kmeet == 1)
        	{
        		$x = 1;
        	}
			if($request->hindrance == 1)
        	{
        		$y = 1;
        	}
        	if($request->invoice == 1)
        	{
                $z = 1;
        	}
        	// if(($x + $y + $z) == 0)
        	// {
        	// 	return response(prepareResult(true, trans('translate.atleast_one_module_should_be_assigned_to_user'), trans('translate.atleast_one_module_should_be_assigned_to_user')), config('httpcodes.bad_request'));
        	// }
            $password = "pact@2023";
            $email = $request->email;
            
            $role = Role::find($request->role_id);
            $user = new User;
            $user->role_id      = $role->id;
            $user->name         = $request->name;
            $user->user_name    = $request->user_name;
            $user->email        = strtolower($email);
            $user->user_type    = $role->user_type;
            $user->package_id    = $request->package_id;
            $user->password     =  Hash::make($password);
            $user->mobile_number= $request->mobile_number;
            $user->address      = $request->address;
            $user->designation  = $request->designation;
            $user->kmeet        = $request->kmeet;
            $user->hindrance    = $request->hindrance;
            $user->invoice      = $request->invoice;
            $user->owner_id     = $request->owner_id;
            $user->epcm_id      = $request->epcm_id;
            $user->engineer_incharge      = $request->engineer_incharge;
            $user->created_by   = auth()->user()->id;
            $user->save();
            $user['id'] = $user->id;

            //Role and permission sync
            $role = Role::where('id', $request->role_id)->first();
            $permissions = $role->permissions->pluck('name');
            
            $user->assignRole($role->name);
            foreach ($permissions as $key => $permission) {
                $user->givePermissionTo($permission);
            }
            if($request->kmeet == 1)
            {
                $kmeetPermission = Permission::where('name','meeting-browse')->first()->name;
                $user->givePermissionTo($kmeetPermission);
            }
            if($request->hindrance == 1)
            {
                $hindrancePermission = Permission::where('name','hindrances-browse')->first()->name;
                $user->givePermissionTo($hindrancePermission);
            }
            if($request->invoice == 1)
            {
                $invoicePermission = Permission::where('name','invoices-browse')->first()->name;
                $user->givePermissionTo($invoicePermission);
            }

            //Delete if entry exists
            DB::table('password_resets')->where('email', $email)->delete();

            $token = \Str::random(64);
            DB::table('password_resets')->insert([
              'email' => $email, 
              'token' => $token, 
              'created_at' => \Carbon\Carbon::now()
            ]);

            $baseRedirURL = env('FRONT_URL');
            // Login credentials are following - email:'.$user->email.' , password:'.$randomNo.'.
            $content = [
                "name" => $user->name,
                "body" => 'You have been registered.<br>To reset your password Please click on the link -> <a href='.$baseRedirURL.'/reset-password/'.$token.' style="color: #000;font-size: 18px;text-decoration: underline, font-family: Roboto Condensed, sans-serif;"  target="_blank">Reset your password </a>',
            ];

            if (env('IS_MAIL_ENABLE', false) == true) {
               
                $recevier = Mail::to($email)->send(new WelcomeMail($content));
            }

            DB::commit();
            return response()->json(prepareResult(false, $user, trans('translate.user_created')),config('httpcodes.created'));
        } catch (\Throwable $e) {
            \Log::error($e);
            DB::rollback();
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $userinfo = User::select('*')
                ->where('role_id','!=','1')
                ->with('role:id,name,se_name','epcm:id,name,email,mobile_number','owner:id,name,email,mobile_number','assignedHindrances','package')->find($id);
            if($userinfo)
            {
                return response(prepareResult(false, $userinfo, trans('translate.user_detail')), config('httpcodes.success'));
            }
            return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validation = \Validator::make($request->all(), [
            'name'      => 'required|regex:/^[a-zA-Z0-9-_ ]+$/',
            'email'     => 'email|required|unique:users,email,'.$id,
            // 'package_id'  => 'exists:packages,id',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try 
        {
            $user = User::where('id',$id)->first();
        
            if(!$user)
            {
                return response()->json(prepareResult(true, [], trans('translate.user_not_exist')), config('httpcodes.not_found'));
            }
            if($user->role_id=='1')
            {                
                $user->name = $request->name;
                $user->mobile_number = $request->mobile_number;
                $user->designation = $request->designation;
                $user->save();
            }
            else
            {
                //check User email
                $getUsers = User::select('email')->where('id', '!=', $id)->get();
                foreach ($getUsers as $key => $existUser) 
                {
                    if($request->email==$existUser->email)
                    {
                        return response(prepareResult(true, trans('translate.user_already_exist_with_this_email'), trans('translate.user_already_exist_with_this_email')), config('httpcodes.internal_server_error'));
                    }
                }
                $role = Role::find($request->role_id);

                $user->role_id = $role->id;
                $user->name = $request->name;
                $user->user_name    = $request->user_name;
                $user->package_id    = $request->package_id;
                $user->user_type  = $role->user_type;
                $user->mobile_number = $request->mobile_number;
                $user->address = $request->address;
                $user->designation = $request->designation;
                $user->kmeet        = $request->kmeet;
                $user->hindrance    = $request->hindrance;
                $user->invoice      = $request->invoice;
                $user->owner_id    = $request->owner_id;
                $user->epcm_id      = $request->epcm_id;
                $user->engineer_incharge      = $request->engineer_incharge;
                $user->save();
            }

            //delete old role and permissions
            DB::table('model_has_roles')->where('model_id', $user->id)->delete();
            DB::table('model_has_permissions')->where('model_id', $user->id)->delete();

            //Role and permission sync
            $role = Role::where('id', $request->role_id)->first();
            $permissions = $role->permissions->pluck('name');
            
            $user->assignRole($role->name);
            foreach ($permissions as $key => $permission) {
                $user->givePermissionTo($permission);
            }
           
            DB::commit();
            return response()->json(prepareResult(false, $user, trans('translate.user_updated')),config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            DB::rollback();
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Action performed on the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param Request  $request
     * @return \Illuminate\Http\Response
     */
    public function userAction(Request $request)
    {
        try {
            $validation = \Validator::make($request->all(), [
                'action'   => 'required',
                "ids"    => "required|array|min:1",
                "ids.*"  => "required|distinct|min:1|exists:users,id",

            ]);
           
            if ($validation->fails()) {
                return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
            }
            if($request->action =='active'){

                $userDelete = User::whereIn('id',$request->ids)->update(['status'=>'1']);
                return response()->json(prepareResult(false, [],trans('translate.user_activated'), config('httpcodes.success')));
            }
            if($request->action =='inactive'){

                $userDelete = User::whereIn('id',$request->ids)->update(['status'=>'0']);
                return response()->json(prepareResult(false, [], trans('translate.user_inactivated'), config('httpcodes.success')));
            }
            
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
