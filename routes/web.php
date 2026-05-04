<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\SchoolSettingController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\ClassRoomController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\PicketOfficerController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\Picket\LeaveApprovalController;
use App\Http\Controllers\Teacher\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Temporary route for updating emails
Route::get('/update-emails', function () {
    $affected = \Illuminate\Support\Facades\DB::update("UPDATE users SET email = REPLACE(email, '@sekolah.local', '@gmail.com') WHERE email LIKE '%@sekolah.local'");
    return "Berhasil memperbarui {$affected} email dari @sekolah.local menjadi @gmail.com!";
});

Route::get('/dashboard', function () {
    $user = request()->user();

    if ($user && method_exists($user, 'hasRole')) {
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.users.index');
        }

        if ($user->hasAnyRole(['guru', 'guru_walikelas', 'petugas_piket'])) {
            return redirect()->route('teacher.dashboard');
        }

        if ($user->hasRole('siswa')) {
            return redirect()->route('attendance.index');
        }
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:siswa')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
            ->middleware('throttle:5,1')
            ->name('attendance.check-in');
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
            ->middleware('throttle:5,1')
            ->name('attendance.check-out');

        Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    });

    Route::prefix('piket')->middleware('role:petugas_piket')->group(function () {
        Route::get('/leave-requests', function () {
            return view('picket.leave-requests');
        })->name('picket.leave-requests.index');

        Route::post('/leave-requests/{leaveRequest}/approve', [LeaveApprovalController::class, 'approve'])->name('picket.leave-requests.approve');
        Route::post('/leave-requests/{leaveRequest}/reject', [LeaveApprovalController::class, 'reject'])->name('picket.leave-requests.reject');
    });

    Route::prefix('guru')->group(function () {
        Route::get('/dashboard', function () {
            return view('teacher.dashboard');
        })->middleware('role:guru|guru_walikelas|petugas_piket')->name('teacher.dashboard');

        Route::middleware('role:guru_walikelas|petugas_piket')->group(function () {
            Route::get('/reports/attendance', [ReportController::class, 'index'])->name('teacher.report');
            Route::get('/reports/attendance/excel', [ReportController::class, 'exportExcel'])->name('teacher.report.excel');
            Route::get('/reports/attendance/pdf', [ReportController::class, 'exportPdf'])->name('teacher.report.pdf');
        });
    });

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/settings', [SchoolSettingController::class, 'edit'])->name('admin.settings.edit');
        Route::patch('/settings', [SchoolSettingController::class, 'update'])->name('admin.settings.update');

        Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('admin.users.show');
        Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');

        Route::get('/picket-officers/create', [PicketOfficerController::class, 'create'])->name('admin.picket-officers.create');
        Route::post('/picket-officers', [PicketOfficerController::class, 'store'])->name('admin.picket-officers.store');

        Route::get('/class-rooms', [ClassRoomController::class, 'index'])->name('admin.class-rooms.index');
        Route::post('/class-rooms', [ClassRoomController::class, 'store'])->name('admin.class-rooms.store');
        Route::delete('/class-rooms/{classRoom}', [ClassRoomController::class, 'destroy'])->name('admin.class-rooms.destroy');

        Route::get('/students', [StudentController::class, 'index'])->name('admin.students.index');
        Route::get('/students/create', [StudentController::class, 'create'])->name('admin.students.create');
        Route::get('/students/{user}/edit', [StudentController::class, 'edit'])->name('admin.students.edit');
        Route::post('/students', [StudentController::class, 'store'])->name('admin.students.store');
        Route::patch('/students/{user}', [StudentController::class, 'update'])->name('admin.students.update');

        Route::get('/teachers', [TeacherController::class, 'index'])->name('admin.teachers.index');
        Route::get('/teachers/create', [TeacherController::class, 'create'])->name('admin.teachers.create');
        Route::get('/teachers/{user}/edit', [TeacherController::class, 'edit'])->name('admin.teachers.edit');
        Route::post('/teachers', [TeacherController::class, 'store'])->name('admin.teachers.store');
        Route::patch('/teachers/{user}', [TeacherController::class, 'update'])->name('admin.teachers.update');
    });
});

require __DIR__ . '/auth.php';
