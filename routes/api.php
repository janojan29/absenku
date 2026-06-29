<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\Picket\LeaveApprovalController as ApiLeaveApprovalController;
use App\Http\Controllers\Api\Picket\LeaveQueueController;
use App\Http\Controllers\Api\Teacher\DashboardController as ApiTeacherDashboardController;
use App\Http\Controllers\Api\Teacher\ReportController as ApiTeacherReportController;
use App\Http\Controllers\Api\Admin\SchoolSettingController as ApiSchoolSettingController;
use App\Http\Controllers\Api\Admin\UserManagementController as ApiUserManagementController;
use App\Http\Controllers\Api\Admin\ClassRoomController as ApiClassRoomController;
use App\Http\Controllers\Api\Admin\StudentController as ApiStudentController;
use App\Http\Controllers\Api\Admin\TeacherController as ApiTeacherController;
use App\Http\Controllers\Api\Admin\PicketOfficerController as ApiPicketOfficerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);

    Route::middleware('role:siswa')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index']);
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
        Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    });

    Route::prefix('picket')->middleware('role:petugas_piket')->group(function () {
        Route::get('/leave-requests', [LeaveQueueController::class, 'index']);
        Route::post('/leave-requests/{leaveRequest}/approve', [ApiLeaveApprovalController::class, 'approve']);
        Route::post('/leave-requests/{leaveRequest}/reject', [ApiLeaveApprovalController::class, 'reject']);
    });

    Route::prefix('teacher')->middleware('role:guru|guru_walikelas|petugas_piket')->group(function () {
        Route::get('/dashboard', [ApiTeacherDashboardController::class, 'index']);
        Route::get('/reports/attendance', [ApiTeacherReportController::class, 'index']);
        Route::get('/reports/attendance/excel', [ApiTeacherReportController::class, 'exportExcel']);
        Route::get('/reports/attendance/pdf', [ApiTeacherReportController::class, 'exportPdf']);
    });

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/settings', [ApiSchoolSettingController::class, 'show']);
        Route::patch('/settings', [ApiSchoolSettingController::class, 'update']);

        Route::get('/users', [ApiUserManagementController::class, 'index']);
        Route::get('/users/{user}', [ApiUserManagementController::class, 'show']);
        Route::patch('/users/{user}', [ApiUserManagementController::class, 'update']);
        Route::delete('/users/{user}', [ApiUserManagementController::class, 'destroy']);

        Route::post('/picket-officers', [ApiPicketOfficerController::class, 'store']);

        Route::get('/class-rooms', [ApiClassRoomController::class, 'index']);
        Route::post('/class-rooms', [ApiClassRoomController::class, 'store']);
        Route::delete('/class-rooms/{classRoom}', [ApiClassRoomController::class, 'destroy']);

        Route::get('/students', [ApiStudentController::class, 'index']);
        Route::post('/students', [ApiStudentController::class, 'store']);
        Route::post('/students/import', [ApiStudentController::class, 'import']);
        Route::patch('/students/{user}', [ApiStudentController::class, 'update']);

        Route::get('/teachers', [ApiTeacherController::class, 'index']);
        Route::post('/teachers', [ApiTeacherController::class, 'store']);
        Route::patch('/teachers/{user}', [ApiTeacherController::class, 'update']);
    });
});
