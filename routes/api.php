<?php

use App\Http\Controllers\TelegramController;
use App\Http\Controllers\v1\Clients\AttendanceStatisticController;
use App\Http\Controllers\v1\Clients\ClientAttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\BranchController;
use App\Http\Controllers\v1\DeviceController;
use App\Http\Controllers\v1\PositionController;
use App\Http\Controllers\v2\ScheduleController;
use App\Http\Controllers\v1\AttendanceController;
use App\Http\Controllers\v1\Users\UserController;
use App\Http\Controllers\v1\Auth\EmployeeController;
use App\Http\Controllers\v1\Users\UserAttendanceController;



Route::get('data', [DeviceController::class, 'all']);
Route::post('employee/login', [EmployeeController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {

    Route::get('getme', [EmployeeController::class, 'getme']);
    Route::middleware('admincontrol')->group(function () {
        Route::post('branch/add', [BranchController::class, 'add']);
        Route::put('branch/update/{branch}', [BranchController::class, 'update']);
        Route::delete('branch/delete/{branch}', [BranchController::class, 'delete']);
        //position
        Route::post('position/add', [PositionController::class, 'add']);
        Route::put('position/update/{position}', [PositionController::class, 'update']);
        Route::delete('position/delete/{position}', [PositionController::class, 'delete']);
        //user
        Route::post('user/add', [UserController::class, 'add']);
        Route::get('users/images', [UserController::class, 'all']);
        Route::put('user/update/{user}', [UserController::class, 'update']);
        Route::post('attendance/add', [AttendanceController::class, 'add']);
        Route::post('admin/add',[EmployeeController::class, 'add']);
        Route::post('user/add_image/{id}', [UserController::class, 'add_image']);
        Route::delete('user/delete/{user}', [UserController::class, 'delete']);

        //image
        Route::delete('image/delete/{id}', [UserController::class, 'delete_image']);

        //schedule
        Route::post('schedule/create', [ScheduleController::class, 'create']);
        Route::put('schedule/update/{schedule}', [ScheduleController::class, 'update']);
        Route::delete('schedule/delete/{schedule}', [ScheduleController::class, 'delete']);
        Route::get('users/count', [ScheduleController::class, 'all']);

    });

    Route::get('positions', [PositionController::class, 'all_positions']);
    Route::get('branches', [BranchController::class, 'all']); //8
    Route::get('users/attendance', [UserAttendanceController::class, 'all']); //2
    Route::get('users/attendance/deleted', [UserAttendanceController::class, 'allv2']);//2v2
    Route::get('users/control', [UserAttendanceController::class, 'allWithAttendance']); //5
    Route::get('daily', [UserAttendanceController::class, 'daily']); //1
    Route::get('attendances/last', [UserAttendanceController::class, 'lastAttendances']); //3
    Route::get('daily/graph', [UserAttendanceController::class, 'dailyAllUsersGraph']); //4
    Route::get('user/information/{id}', [UserAttendanceController::class, 'about']); //6
    Route::get('late/comers', [UserAttendanceController::class, 'lateComersWithDetails']); //9
    Route::get('note/comers', [UserAttendanceController::class, 'noteComers']); //9
    Route::get('comers',[UserAttendanceController::class,'comers']);
    Route::get('monthly', [UserAttendanceController::class,'monthly']); //7
    Route::get('month',[UserAttendanceController::class,'month']); //7
    Route::get('users/byschedule',[UserAttendanceController::class,'usersbyschedule']);

    //clients
    Route::post('client/attendance/add', [ClientAttendanceController::class, 'add']);
    Route::get('/client/attendance/by_date', [AttendanceStatisticController::class, 'getClientsByDate']);
    Route::post('/telegram/webhook', [TelegramController::class, 'handle']);
    Route::get('/set-webhook', [TelegramController::class, 'setWebhook']);

});
// Route::get('user/infor/{id}',[UserAttendanceController::class,'about']);//6
// Route::post('attendance/addWithResponseJobs',[AttendanceController::class,'addWithResponseJobs']);
// Route::put('attendance/change',[AttendanceController::class,'change']);
// Route::get('attendance/{id}',[AttendanceController::class,'all']);
