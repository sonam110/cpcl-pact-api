<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/



Route::namespace('App\Http\Controllers\API\Common')->group(function () {

    Route::controller(AuthController::class)->group(function () {
        Route::get('unauthorized', 'unauthorized')->name('unauthorized');
        Route::post('login', 'login')->name('login');
        Route::post('verify-otp', 'verifyOtp')->name('verify-otp');
        Route::post('forgot-password', 'forgotPassword')->name('forgot-password');
        Route::get('reset-password/{token}','resetPassword')->name('password.reset');
        Route::post('update-password', 'updatePassword')->name('update-password');
    });

    Route::controller(MailSyncController::class)->group(function () {
        Route::get('meeting-sync', 'meetingSync')->name('meeting-sync');
    });
    Route::get('file-access/{folderName}/{fileName}', 'FileUploadController@getFile'); 
    Route::group(['middleware' => 'auth:api'],function () {
        /*---------------------Auth-routess------------------------*/
        Route::controller(AuthController::class)->group(function () {
            Route::post('logout', 'logout')->name('logout');
            Route::post('change-password', 'changePassword')->name('changePassword');
        });

        /*---------------------dashboard----------------------*/
        Route::controller(DashboardController::class)->group(function () {
            Route::post('dashboard','dashboard')->name('dashboard');
            Route::post('test-function','test')->name('test-function');
        });

        /*----------Roles------------------------------*/
        Route::controller(RoleController::class)->group(function () {
            Route::post('roles', 'roles')->name('roles');
            Route::apiResource('role', RoleController::class)->only(['store','destroy','show', 'update']);
        });

        /*---------------File Upload--------------------*/
        Route::controller(FileUploadController::class)->group(function () {
            Route::post('file-uploads', 'fileUploads')->name('file-uploads');
            Route::post('file-upload', 'store')->name('file-upload');
        });

        /*-------------Meeting--------------------*/
        Route::controller(MeetingController::class)->group(function () {
            Route::post('meetings','meetings')->name('meetings');
            Route::resource('meeting', MeetingController::class)->only([
                'store','destroy','show', 'update'
            ]);
            Route::post('meeting-action', 'action')->name('meeting-action');
            Route::post('meeting-log-data-pdf/{id}', 'meetingLogDataPdf')->name('meeting-log-data-pdf');
            Route::post('meeting-tasks-export/{id}', 'meetingTasksExport')->name('meeting-tasks-export');
        });

        /*-------------Meeting-Notes-------------------------*/
        Route::controller(NotesController::class)->group(function () {
            Route::post('notes','notes')->name('notes');
            Route::resource('note', NotesController::class)->only([
                'store','destroy','show', 'update'
            ]);
            Route::post('note-action', 'action')->name('note-action');
        });
        
        /*-------------Action-Item------------------------*/
        Route::controller(ActionItemController::class)->group(function () {
            Route::post('action-items','actionItems')->name('action-items');
            Route::resource('action-item', ActionItemController::class)->only([
                'store','destroy','show', 'update'
            ]);
            Route::post('action-item-action', 'action')->name('action-item-action');
            Route::post('action-item-send-mail', 'sendMail')->name('action-item-send-mail');
            Route::post('action-item-mail-logs', 'mailLogs')->name('action-item-mail-logs');
        });

        /*-------------Permission------------------------*/
        Route::controller(PermissionController::class)->group(function () {
            Route::post('permissions','permissions');
            Route::apiResource('permission',PermissionController::class)->only(['store','destroy','show', 'update']);
        });

        //----------------------------Notification----------------------//
        Route::controller(NotificationController::class)->group(function () {
            Route::post('/notifications','index');
            Route::apiResource('/notification', NotificationController::class)->only('store','destroy','show');
            Route::get('/notification/{id}/read', 'read');
            Route::get('/user-notification-read-all', 'userNotificationReadAll');
            Route::get('/user-notification-delete', 'userNotificationDelete');
            Route::post('/notification-check', 'notificationCheck');
            Route::get('/unread-notification-count', 'unreadNotificationsCount');
        });
       
        //----------------------------Projects----------------------//
        Route::controller(ProjectController::class)->group(function () {
            Route::post('projects', 'projects')->name('projects');
            Route::apiResource('project', ProjectController::class)->only(['store','destroy','show', 'update']);
            Route::post('project-action', 'action')->name('project-action');
        });

        //----------------------------Hindrances----------------------//
        Route::controller(HindranceController::class)->group(function () {
            Route::post('hindrances', 'hindrances')->name('hindrances');
            Route::apiResource('hindrance', HindranceController::class)->only(['store','destroy','show', 'update']);
            Route::post('hindrance-assign', 'hindranceAssign')->name('hindrance-assign');
            Route::post('hindrance-assignee-remove', 'hindranceAssigneeRemove')->name('hindrance-assignee-remove');
            Route::post('hindrance-action', 'action')->name('hindrance-action');
            Route::post('hindrance-export', 'hindranceExport')->name('hindrance-export');
            Route::post('hindrance-due-date', 'hindranceDueDate')->name('hindrance--due-date');
            Route::post('hindrance-activity-log-export/{id}', 'hindranceActivityLogExport')->name('hindrance-activity-log-export');
            Route::post('hindrance-log/{id}', 'viewLog')->name('hindrance-log');
            Route::post('hindrance-pending-resolved-data', 'pendingResolvedData')->name('hindrance-pending-resolved-data');
            Route::post('hindrance-type-wise-data', 'hindranceTypeWiseData')->name('hindrance-type-wise-data');


        });

        //----------------------------Invoices----------------------//
        Route::controller(InvoiceController::class)->group(function () {
            Route::post('invoices', 'invoices')->name('invoices');
            Route::apiResource('invoice', InvoiceController::class)->only(['store','destroy','show', 'update']);
            Route::post('invoice-action', 'action')->name('invoice-action');
            Route::post('invoice-check-group-attach', 'attachCheckGroup')->name('invoice-check-group-attach');
            Route::post('invoice-checks-verification/{id}', 'checksVerify')->name('invoice-checks-verification');
            Route::post('scan-invoice-bar-code', 'scanBarCode')->name('scan-invoice-bar-code');
            Route::post('invoice-activity-log-export/{id}', 'invoiceActivityLogExport')->name('invoice-activity-log-export');
            Route::post('invoice-export', 'invoiceExport')->name('invoice-export');
            Route::post('invoice-log/{id}', 'viewLog')->name('invoice-log');
            Route::post('invoice-assign', 'invoiceAssign')->name('invoice-assign');
            Route::post('invoice-assignee-remove', 'invoiceAssigneeRemove')->name('invoice-assignee-remove');
            Route::get('invoice-dashboard-analytics', 'dashboardAnalytics')->name('invoice-dashboard-analytics');
            Route::get('invoice-data-inr', 'invoiceDataInr')->name('invoice-data-inr');
            Route::get('invoice-print/{id}', 'invoicePrint')->name('invoice-print');
        });
        //----------------------------Invoices----------------------//
        Route::controller(InvoiceCheckController::class)->group(function () {
            Route::post('invoice-checks', 'invoiceChecks')->name('invoiceChecks');
            Route::apiResource('invoice-check', InvoiceCheckController::class)->only(['store','destroy','show', 'update']);
        });
        //----------------common---------------------//
        Route::controller(CommonController::class)->group(function () {
            Route::post('users-list', 'usersList')->name('users-list');
            Route::post('packages-list', 'packagesList')->name('packages-list');
            Route::post('projects-list', 'projectsList')->name('projects-list');
            Route::post('contracts-list', 'contractsList')->name('contracts-list');
            Route::post('hindrance-types-list', 'hindranceTypesList')->name('hindrance-types-list');
            Route::post('ra-bill-no-list', 'RaBillNoList')->name('ra-bill-no-list');
        });
    });
}); 

Route::namespace('App\Http\Controllers\API\Admin')->group(function () {

    Route::controller(AppSettingController::class)->group(function () {
        Route::get('app-setting','appSetting')->name('app-setting');
    });

    Route::group(['middleware' => 'auth:api'],function () {
        /*---------------------User------------------------*/
        Route::controller(UserController::class)->group(function () {
            Route::post('users','users')->name('users');
            Route::post('user-action','userAction')->name('user-action');
            Route::resource('user', UserController::class)->only([
                'store','show', 'update'
            ]);
        });

        /*---------------------App-Setting------------------------*/
        Route::controller(AppSettingController::class)->group(function () {
            Route::post('update-setting','updateSetting')->name('update-setting');
        });

        /*---------------------All system activity Logs------------------------*/
        Route::controller(ActivityController::class)->group(function () {
            Route::post('activities', 'activities')->name('activities');
            Route::get('activities-info/{activity_id}', 'activitiesInfo')->name('activities-info');
        });

        /*---------------------Logs------------------------*/
        Route::controller(LogController::class)->group(function () {
            Route::post('logs','logs')->name('logs');
        });

        /*----------Packages------------------------------*/
        Route::controller(PackageController::class)->group(function () {
            Route::post('packages', 'packages')->name('packages');
            Route::apiResource('package', PackageController::class)->only(['store','destroy','show', 'update']);
        });

        /*----------HindranceTypes------------------------------*/
        Route::controller(HindranceTypeController::class)->group(function () {
            Route::post('hindrance-types', 'hindranceTypes')->name('hindrance-types');
            Route::apiResource('hindrance-type', HindranceTypeController::class)->only(['store','destroy','show', 'update']);
        });

        /*----------Imports------------------------------*/
        Route::controller(ImportController::class)->group(function () {
            Route::post('invoices-import', 'invoicesImport')->name('invoices-import');
            Route::post('hindrances-import', 'hindrancesImport')->name('hindrances-import');
            Route::get('import-hindrances-sample-file', 'hindranceSampleFile')->name('import-hindrances-sample-file');
            Route::get('import-invoices-sample-file', 'invoiceSampleFile')->name('import-invoices-sample-file');
        });

        /*----------Contracts------------------------------*/
        Route::controller(ContractController::class)->group(function () {
            Route::post('contracts', 'contracts')->name('contracts');
            Route::apiResource('contract', ContractController::class)->only(['store','destroy','show', 'update']);
        });

        /*----------Check-List------------------------------*/
        Route::controller(CheckListController::class)->group(function () {
            Route::post('check-lists', 'checkLists')->name('check-lists');
            Route::apiResource('check-list', CheckListController::class)->only(['store','destroy','show', 'update']);
        });

        /*----------Contract-Type------------------------------*/
        Route::controller(ContractTypeController::class)->group(function () {
            Route::post('contract-types', 'contractTypes')->name('contract-types');
            Route::apiResource('contract-type', ContractTypeController::class)->only(['store','destroy','show', 'update']);
        });

    });
});    




