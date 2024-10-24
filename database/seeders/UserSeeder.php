<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\AppSetting;
use App\Models\Module;
use App\Models\AssigneModule;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$adminUser = new User();
    	$adminUser->role_id                 = '1';
    	$adminUser->name                    = 'admin';
    	$adminUser->email                   = 'in-fmpivotsupport@kpmg.com';
    	$adminUser->password                = \Hash::make(',.%{]p#e3MA802,');
    	$adminUser->user_type = '1';
    	$adminUser->save();
    	$admin = $adminUser;


    	$appSetting = new AppSetting();
    	$appSetting->id                      = '1';
    	$appSetting->app_name                = 'PROJECT ACTION AND COLLABORATION TRACKER';
    	$appSetting->description             = 'PROJECT ACTION AND COLLABORATION TRACKER';
    	$appSetting->email                   = 'admin@gmail.com';
    	$appSetting->mobile_no               = '9876543210';
    	$appSetting->address                 = 'Mumbai';
    	$appSetting->app_logo                = env('APP_URL').'/public/kpmg-logo.jpeg';
    	$appSetting->save();

    	$adminRole = Role::where('id','1')->first();
    	$ownerRole = Role::where('id','2')->first();
    	$epcmRole = Role::where('id','3')->first();
    	$contractorRole = Role::where('id','4')->first();
    	$ownerFinanceRole = Role::where('id','5')->first();
    	$adminUser->assignRole($adminRole);


    	$adminPermissions = Permission::whereIn('belongs_to',[1,3])->get();
        // $adminPermissions = [
        //     'user-browse',
        //     'user-read',
        //     'user-add',
        //     'user-edit',
        //     'user-delete',
        //     'role-browse',
        //     'role-read',
        //     'role-add',
        //     'role-edit',
        //     'role-delete',
        //     'dashboard-browse',
        //     'notifications-browse',
        //     'notifications-add',
        //     'notifications-edit',
        //     'notifications-delete',
        //     'all-meeting-browse',
        //     'meeting-browse',
        //     'meeting-add',
        //     'meeting-read',
        //     'meeting-edit',
        //     'meeting-delete',
        //     'notes-browse',
        //     'notes-add',
        //     'notes-read',
        //     'notes-edit',
        //     'notes-delete',
        //     'action-items-browse',
        //     'action-items-add',
        //     'action-items-read',
        //     'action-items-edit',
        //     'action-items-delete',
        //     'notifications-read',
        //     'logs-browse',
        // ];
    	foreach ($adminPermissions as $key => $permission) {
    		$adminRole->givePermissionTo($permission);
    		$admin->givePermissionTo($permission);
    	}


    	$ownerPermissions = [
    		'dashboard-browse',
    		'meeting-browse',
    		'meeting-add',
    		'meeting-edit',
    		'notes-browse',
    		'notes-add',
    		'action-items-browse',
    		'action-items-add',
    		'action-items-edit',
    		'projects-browse',
    		'invoices-browse',
    		'invoices-add',
    		'invoices-edit',
    		'invoices-action',
    		'hindrances-browse',
    		'hindrances-add',
    		'hindrances-edit',
    		'hindrances-action',
    		'hindrance-types-browse',
    		'packages-browse',
    		'contracts-browse'
    	];
    	foreach ($ownerPermissions as $key => $permission) {
    		$ownerRole->givePermissionTo($permission);
    	}


    	$epcmPermissions = [
    		'dashboard-browse',
    		'projects-browse',
    		'projects-add',
    		'invoices-browse',
    		'invoices-add',
    		'invoices-edit',
    		'invoices-action',
    		'hindrances-browse',
    		'hindrances-add',
    		'hindrances-edit',
    		'hindrances-action',
    		'hindrance-types-browse',
    		'packages-browse',
    		'packages-edit',
    		'hindrances-export',
    		'invoices-export',
    		'contracts-browse',
    		'contracts-add',
    		'contracts-edit'
    	];
    	foreach ($epcmPermissions as $key => $permission) {
    		$epcmRole->givePermissionTo($permission);
    	}

    	$contractorPermissions = [
    		'dashboard-browse',
    		'projects-browse',
    		'projects-add',
    		'invoices-browse',
    		'invoices-add',
    		'invoices-edit',
    		'invoices-action',
    		'hindrances-browse',
    		'hindrances-add',
    		'hindrances-edit',
    		'hindrances-action',
    		'hindrance-types-browse',
    		'packages-browse',
    		'packages-edit',
    		'hindrances-export',
    		'invoices-export',
    		'contracts-browse',
    		'contracts-add',
    		'contracts-edit'
    	];
    	foreach ($contractorPermissions as $key => $permission) {
    		$contractorRole->givePermissionTo($permission);
    	}

    	$ownerFinancePermissions = [
    		'dashboard-browse',
    		'meeting-browse',
    		'meeting-add',
    		'meeting-edit',
    		'notes-browse',
    		'notes-add',
    		'action-items-browse',
    		'action-items-add',
    		'action-items-edit',
    		'projects-browse',
    		'invoices-browse',
    		'invoices-add',
    		'invoices-edit',
    		'invoices-action',
    		'hindrances-browse',
    		'hindrances-add',
    		'hindrances-edit',
    		'hindrances-action',
    		'hindrance-types-browse',
    		'packages-browse',
    		'contracts-browse'
    	];
    	foreach ($ownerFinancePermissions as $key => $permission) {
    		$ownerFinanceRole->givePermissionTo($permission);
    	}
    }
}
