<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\Teacher;
use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with(['studentProfile.classRoom', 'teacher'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';

                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('whatsapp_number', 'like', $like)
                        ->orWhereHas('roles', fn($roleQuery) => $roleQuery->where('name', 'like', $like))
                        ->orWhereHas('studentProfile', function ($studentQuery) use ($like) {
                            $studentQuery
                                ->where('nis', 'like', $like)
                                ->orWhere('jurusan', 'like', $like)
                                ->orWhere('parent_phone_wa', 'like', $like)
                                ->orWhereHas('classRoom', function ($classRoomQuery) use ($like) {
                                    $classRoomQuery
                                        ->where('name', 'like', $like)
                                        ->orWhere('jurusan', 'like', $like);
                                });
                        })
                        ->orWhereHas('teacher', function ($teacherQuery) use ($like) {
                            $teacherQuery
                                ->where('nip', 'like', $like)
                                ->orWhere('subject', 'like', $like)
                                ->orWhere('wali_kelas', 'like', $like);
                        });
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::query()->orderBy('name')->get();
        $classRooms = ClassRoom::query()
            ->orderBy('name')
            ->orderBy('jurusan')
            ->get();
        $classRoomOptions = $classRooms->map(function ($room) {
            return [
                'id' => $room->id,
                'name' => $room->name,
                'jurusan' => $room->jurusan,
            ];
        })->values();
        $jurusanOptions = ClassRoom::query()
            ->whereNotNull('jurusan')
            ->where('jurusan', '!=', '')
            ->distinct()
            ->orderBy('jurusan')
            ->pluck('jurusan');
        $picketOfficerCount = User::role('petugas_piket')->count();

        return view('admin.users.index', [
            'users' => $users,
            'q' => $q,
            'roles' => $roles,
            'classRooms' => $classRooms,
            'classRoomOptions' => $classRoomOptions,
            'jurusanOptions' => $jurusanOptions,
            'picketOfficerCount' => $picketOfficerCount,
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['studentProfile.classRoom', 'teacher', 'roles']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'whatsapp_number' => $user->whatsapp_number,
            'roles' => $user->roles->map(fn($role) => ['name' => $role->name])->values()->toArray(),
            'student_profile' => $user->studentProfile ? [
                'id' => $user->studentProfile->id,
                'nis' => $user->studentProfile->nis,
                'class_room_id' => $user->studentProfile->class_room_id,
                'class_room_name' => $user->studentProfile->classRoom?->name,
                'jurusan' => $user->studentProfile->jurusan,
                'parent_phone_wa' => $user->studentProfile->parent_phone_wa,
            ] : null,
            'teacher' => $user->teacher ? [
                'id' => $user->teacher->id,
                'nip' => $user->teacher->nip,
                'subject' => $user->teacher->subject,
                'wali_kelas' => $user->teacher->wali_kelas,
            ] : null,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $role = $request->validate([
            'role' => ['required', 'string', 'in:admin,guru,guru_walikelas,petugas_piket,siswa'],
        ])['role'];

        $currentRole = $user->getRoleNames()->first();

        // Admin & Petugas Piket: hanya boleh ubah password (tidak boleh edit role/nama/dll)
        if (in_array($currentRole, ['admin', 'petugas_piket'], true)) {
            if ($role !== $currentRole) {
                return back()->withErrors([
                    'role' => 'Role admin/petugas piket tidak bisa diubah.',
                ]);
            }

            $data = $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user->update([
                'password' => Hash::make($data['password']),
            ]);

            return back()->with('status', 'Password berhasil diperbarui.');
        }

        if ($role === 'petugas_piket') {
            if (! $user->hasRole('petugas_piket') && User::role('petugas_piket')->count() >= 2) {
                return back()->withErrors([
                    'role' => 'Maksimal hanya 2 user Petugas Piket.',
                ]);
            }
        }

        // Base validation rules for all users
        $validationRules = [
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
        ];

        // Add role-specific validation
        if ($role === 'siswa') {
            $validationRules = array_merge($validationRules, [
                'nis' => ['required', 'string', 'max:20', 'unique:student_profiles,nis,' . ($user->studentProfile?->id ?? 'NULL')],
                'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
                'parent_phone_wa' => ['nullable', 'string', 'max:30'],
            ]);
        } elseif (in_array($role, ['guru', 'guru_walikelas'], true)) {
            $validationRules = array_merge($validationRules, [
                'nip' => ['required', 'string', 'max:20', 'unique:teachers,nip,' . ($user->teacher?->id ?? 'NULL')],
                'subject' => ['nullable', 'string', 'max:150'],
                'wali_kelas' => ['nullable', 'string', 'max:100'],
            ]);

            if ($role === 'guru_walikelas') {
                $validationRules['wali_kelas'] = ['required', 'string', 'max:100'];
            }
        }

        $data = $request->validate($validationRules);

        // Update user basic info
        $user->update([
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);

        // Update role
        $user->syncRoles([$role]);

        // Handle student profile
        if ($role === 'siswa') {
            $selectedClass = ClassRoom::query()->findOrFail((int) $data['class_room_id']);

            $studentData = [
                'nis' => $data['nis'],
                'class_room_id' => $data['class_room_id'],
                'jurusan' => $selectedClass->jurusan,
                'parent_phone_wa' => $data['parent_phone_wa'] ?? null,
            ];

            if ($user->studentProfile) {
                $user->studentProfile->update($studentData);
            } else {
                $studentData['user_id'] = $user->id;
                StudentProfile::create($studentData);
            }
        }

        // Handle teacher profile
        if (in_array($role, ['guru', 'guru_walikelas'], true)) {
            $teacherData = [
                'nip' => $data['nip'],
                'subject' => $data['subject'] ?? null,
                'wali_kelas' => $data['wali_kelas'] ?? null,
            ];

            if ($user->teacher) {
                $user->teacher->update($teacherData);
            } else {
                $teacherData['user_id'] = $user->id;
                Teacher::create($teacherData);
            }
        }

        return back()->with('status', 'User diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if ($currentUser && (int) $currentUser->id === (int) $user->id) {
            return back()->withErrors([
                'delete' => 'Tidak bisa menghapus akun sendiri.',
            ]);
        }

        if ($user->hasAnyRole(['admin', 'petugas_piket'])) {
            return back()->withErrors([
                'delete' => 'User admin/petugas piket tidak bisa dihapus. Hanya bisa ubah password.',
            ]);
        }

        if (! $user->hasAnyRole(['guru', 'guru_walikelas', 'siswa'])) {
            return back()->withErrors([
                'delete' => 'Hanya user guru, guru walikelas, atau siswa yang bisa dihapus.',
            ]);
        }

        // Ensure pivot rows are removed even if foreign keys are not cascading.
        $user->syncRoles([]);

        $user->delete();

        return back()->with('status', 'User dihapus.');
    }
}
