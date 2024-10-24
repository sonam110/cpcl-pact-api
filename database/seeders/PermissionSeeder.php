<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      app()['cache']->forget('spatie.permission.cache');
      
       // create roles and assign existing permissions
      $permissions = [
        [
          'name' => 'user-browse',
          'guard_name' => 'api',
          'se_name' => 'user-browse',
          'group_name' => 'user',
          'description' => NULL,
          'belongs_to' => '1'
        ],
        // [
        //   'name' => 'user-read',
        //   'guard_name' => 'api',
        //   'se_name' => 'user-read',
        //   'group_name' => 'user',
        //   'description' => NULL,
        //   'belongs_to' => '1'
        // ],
        [
          'name' => 'user-add',
          'guard_name' => 'api',
          'se_name' => 'user-create',
          'group_name' => 'user',
          'description' => NULL,
          'belongs_to' => '1'
        ],
        [
          'name' => 'user-edit',
          'guard_name' => 'api',
          'se_name' => 'user-edit',
          'group_name' => 'user',
          'description' => NULL,
          'belongs_to' => '1'
        ],
        // [
        //   'name' => 'user-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'user-delete',
        //   'group_name' => 'user',
        //   'description' => NULL,
        //   'belongs_to' => '1'
        // ],
        [
          'name' => 'role-browse',
          'guard_name' => 'api',
          'se_name' => 'role-browse',
          'group_name' => 'role',
          'description' => NULL,
          'belongs_to' => '1'
        ],
        // [
        //   'name' => 'role-read',
        //   'guard_name' => 'api',
        //   'se_name' => 'role-read',
        //   'group_name' => 'role',
        //   'description' => NULL,
        //   'belongs_to' => '1'
        // ],
        [
          'name' => 'role-add',
          'guard_name' => 'api',
          'se_name' => 'role-add',
          'group_name' => 'role',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'role-edit',
          'guard_name' => 'api',
          'se_name' => 'role-edit',
          'group_name' => 'role',
          'description' => NULL,
          'belongs_to' => '1'
        ],
        // [
        //   'name' => 'role-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'role-delete',
        //   'group_name' => 'role',
        //   'description' => NULL,
        //   'belongs_to' => '1'
        // ],
        [
          'name' => 'dashboard-browse',
          'guard_name' => 'api',
          'se_name' => 'dashboard-browse',
          'group_name' => 'dashboard',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'notifications-browse',
          'guard_name' => 'api',
          'se_name' => 'notifications-browse',
          'group_name' => 'notifications',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'notifications-add',
          'guard_name' => 'api',
          'se_name' => 'notifications-add',
          'group_name' => 'notifications',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'notifications-edit',
          'guard_name' => 'api',
          'se_name' => 'notifications-edit',
          'group_name' => 'notifications',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'notifications-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'notifications-delete',
        //   'group_name' => 'notifications',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'all-meeting-browse',
          'guard_name' => 'api',
          'se_name' => 'all-meeting-browse',
          'group_name' => 'meeting',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'meeting-browse',
          'guard_name' => 'api',
          'se_name' => 'meeting-browse',
          'group_name' => 'meeting',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'meeting-add',
          'guard_name' => 'api',
          'se_name' => 'meeting-add',
          'group_name' => 'meeting',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'meeting-read',
        //   'guard_name' => 'api',
        //   'se_name' => 'meeting-read',
        //   'group_name' => 'meeting',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'meeting-edit',
          'guard_name' => 'api',
          'se_name' => 'meeting-edit',
          'group_name' => 'meeting',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'meeting-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'meeting-delete',
        //   'group_name' => 'meeting',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'notes-browse',
          'guard_name' => 'api',
          'se_name' => 'notes-browse',
          'group_name' => 'notes',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'notes-add',
          'guard_name' => 'api',
          'se_name' => 'notes-add',
          'group_name' => 'notes',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'notes-read',
        //   'guard_name' => 'api',
        //   'se_name' => 'notes-read',
        //   'group_name' => 'notes',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'notes-edit',
          'guard_name' => 'api',
          'se_name' => 'notes-edit',
          'group_name' => 'notes',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'notes-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'notes-delete',
        //   'group_name' => 'notes',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'action-items-browse',
          'guard_name' => 'api',
          'se_name' => 'action-items-browse',
          'group_name' => 'action-items',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'action-items-add',
          'guard_name' => 'api',
          'se_name' => 'action-items-add',
          'group_name' => 'action-items',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'action-items-read',
        //   'guard_name' => 'api',
        //   'se_name' => 'action-items-read',
        //   'group_name' => 'action-items',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'action-items-edit',
          'guard_name' => 'api',
          'se_name' => 'action-items-edit',
          'group_name' => 'action-items',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'action-items-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'categories-delete',
        //   'group_name' => 'action-items',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'notifications-read',
          'guard_name' => 'api',
          'se_name' => 'notifications-read',
          'group_name' => 'notifications',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
        'name' => 'logs-browse',
        'guard_name' => 'api',
        'se_name' => 'logs-browse',
        'group_name' => 'logs',
        'description' => NULL,
        'belongs_to' => '1'
        ],
        [
          'name' => 'projects-browse',
          'guard_name' => 'api',
          'se_name' => 'projects-browse',
          'group_name' => 'projects',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'projects-add',
          'guard_name' => 'api',
          'se_name' => 'projects-add',
          'group_name' => 'projects',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'projects-read',
        //   'guard_name' => 'api',
        //   'se_name' => 'projects-read',
        //   'group_name' => 'projects',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'projects-edit',
          'guard_name' => 'api',
          'se_name' => 'projects-edit',
          'group_name' => 'projects',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'projects-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'projects-delete',
        //   'group_name' => 'projects',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'invoices-browse',
          'guard_name' => 'api',
          'se_name' => 'invoices-browse',
          'group_name' => 'invoices',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'invoices-add',
          'guard_name' => 'api',
          'se_name' => 'invoices-add',
          'group_name' => 'invoices',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'invoices-read',
        //   'guard_name' => 'api',
        //   'se_name' => 'invoices-read',
        //   'group_name' => 'invoices',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'invoices-edit',
          'guard_name' => 'api',
          'se_name' => 'invoices-edit',
          'group_name' => 'invoices',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'invoices-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'invoices-delete',
        //   'group_name' => 'invoices',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'invoices-action',
          'guard_name' => 'api',
          'se_name' => 'invoices-action',
          'group_name' => 'invoices',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'invoices-export',
          'guard_name' => 'api',
          'se_name' => 'invoices-export',
          'group_name' => 'invoices',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'invoices-log',
          'guard_name' => 'api',
          'se_name' => 'invoices-log',
          'group_name' => 'invoices',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'hindrances-browse',
          'guard_name' => 'api',
          'se_name' => 'hindrances-browse',
          'group_name' => 'hindrances',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'hindrances-add',
          'guard_name' => 'api',
          'se_name' => 'hindrances-add',
          'group_name' => 'hindrances',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'hindrances-read',
        //   'guard_name' => 'api',
        //   'se_name' => 'hindrances-read',
        //   'group_name' => 'hindrances',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'hindrances-edit',
          'guard_name' => 'api',
          'se_name' => 'hindrances-edit',
          'group_name' => 'hindrances',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'hindrances-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'hindrances-delete',
        //   'group_name' => 'hindrances',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'hindrances-action',
          'guard_name' => 'api',
          'se_name' => 'hindrances-action',
          'group_name' => 'hindrances',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'hindrances-export',
          'guard_name' => 'api',
          'se_name' => 'hindrances-export',
          'group_name' => 'hindrances',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'hindrances-log',
          'guard_name' => 'api',
          'se_name' => 'hindrances-log',
          'group_name' => 'hindrances',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'hindrance-types-browse',
          'guard_name' => 'api',
          'se_name' => 'hindrance-types-browse',
          'group_name' => 'hindrance-types',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'hindrance-types-add',
          'guard_name' => 'api',
          'se_name' => 'hindrance-types-add',
          'group_name' => 'hindrance-types',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'hindrance-types-read',
        //   'guard_name' => 'api',
        //   'se_name' => 'hindrance-types-read',
        //   'group_name' => 'hindrance-types',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'hindrance-types-edit',
          'guard_name' => 'api',
          'se_name' => 'hindrance-types-edit',
          'group_name' => 'hindrance-types',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'hindrance-types-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'hindrance-types-delete',
        //   'group_name' => 'hindrances',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'packages-browse',
          'guard_name' => 'api',
          'se_name' => 'packages-browse',
          'group_name' => 'packages',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'packages-add',
          'guard_name' => 'api',
          'se_name' => 'packages-add',
          'group_name' => 'packages',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'packages-read',
        //   'guard_name' => 'api',
        //   'se_name' => 'packages-read',
        //   'group_name' => 'packages',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'packages-edit',
          'guard_name' => 'api',
          'se_name' => 'packages-edit',
          'group_name' => 'packages',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'packages-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'packages-delete',
        //   'group_name' => 'packages',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'contracts-browse',
          'guard_name' => 'api',
          'se_name' => 'contracts-browse',
          'group_name' => 'contracts',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'contracts-add',
          'guard_name' => 'api',
          'se_name' => 'contracts-add',
          'group_name' => 'contracts',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'contracts-read',
          'guard_name' => 'api',
          'se_name' => 'contracts-read',
          'group_name' => 'contracts',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        [
          'name' => 'contracts-edit',
          'guard_name' => 'api',
          'se_name' => 'contracts-edit',
          'group_name' => 'contracts',
          'description' => NULL,
          'belongs_to' => '3'
        ],
        // [
        //   'name' => 'contracts-delete',
        //   'guard_name' => 'api',
        //   'se_name' => 'contracts-delete',
        //   'group_name' => 'contracts',
        //   'description' => NULL,
        //   'belongs_to' => '3'
        // ],
        [
          'name' => 'app-setting-browse',
          'guard_name' => 'api',
          'se_name' => 'app-setting-browse',
          'group_name' => 'app-setting',
          'description' => NULL,
          'belongs_to' => '1'
        ],
      ];
      foreach ($permissions as $key => $permission) {
        Permission::create($permission);
      }

    }
}
