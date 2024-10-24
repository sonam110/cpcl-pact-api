<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use App\Models\UserType;
class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*------------Default Role-----------------------------------*/
        \DB::table('roles')->delete();
        
        Role::create([
            'id' => '1',
            'name' => 'Admin',
            'se_name' => 'Admin', 
            'guard_name' => 'api',
            'user_type' => 1,
            'description' => 'description about role',
            'is_default'=>'0', 
            
        ]);
        
        Role::create([
            'id' => '2',
            'name' => 'Owner',
            'se_name' => 'Owner', 
            'guard_name' => 'api',
            'user_type' => 2,
            'description' => 'description about role',
            'is_default'=>'0', 
            
        ]);

        Role::create([
            'id' => '3',
            'name' => 'Epcm',
            'se_name' => 'Epcm', 
            'guard_name' => 'api',
            'user_type' => 3,
            'description' => 'description about role',
            'is_default'=>'0', 
        ]);

        Role::create([
            'id' => '4',
            'name' => 'Contractor',
            'se_name' => 'Contractor', 
            'guard_name' => 'api',
            'user_type' => 4,
            'description' => 'description about role',
            'is_default'=>'0', 
            
        ]);

        Role::create([
            'id' => '5',
            'name' => 'Owner Finance',
            'se_name' => 'Owner Finance', 
            'guard_name' => 'api',
            'user_type' => 5,
            'description' => 'description about role',
            'is_default'=>'0', 
            
        ]);
        
        
    }
}
