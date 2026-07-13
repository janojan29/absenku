<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with(['studentProfile.classRoom', 'teacher', 'roles'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('whatsapp_number', 'like', $like)
                        ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'like', $like))
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

        return response()->json([
            'data' => $users->getCollection()
                ->map(fn ($item) => (new UserResource($item))->toArray($request))
                ->values(),
            'meta' => [
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $user->load(['studentProfile.classRoom', 'teacher', 'roles']);

        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->toArray($request),
            ],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        // Reuse the web validation & rules by accepting the same payload.
        // This endpoint is used by Flutter admin flows.
        $role = $request->validate([
            'role' => ['required', 'string', 'in:admin,guru,guru_walikelas,petugas_piket,siswa'],
        ])['role'];

        // Defer to the existing web controller logic constraints as much as possible.
        // For now, we only allow updating whatsapp_number + role sync (and related profiles).
        // If you need full parity with the Blade forms, extend this handler.

        $data = $request->validate([
            'whatsapp_number' => ['nullable', 'string', 'regex:/^(08|\+62|62)[0-9]+$/', 'max:30'],
        ], [
            'whatsapp_number.regex' => 'Nomor WhatsApp harus diawali dengan 08 dan hanya berisi angka.',
        ]);

        $user->update([
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);
        $user->syncRoles([$role]);
        $user->load(['studentProfile.classRoom', 'teacher', 'roles']);

        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->toArray($request),
            ],
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser && (int) $currentUser->id === (int) $user->id) {
            return response()->json([
                'message' => 'Tidak bisa menghapus akun sendiri.',
            ], 422);
        }

        if ($user->hasAnyRole(['admin', 'petugas_piket'])) {
            return response()->json([
                'message' => 'User admin/petugas piket tidak bisa dihapus. Hanya bisa ubah password.',
            ], 422);
        }

        $user->syncRoles([]);
        $user->delete();

        return response()->json([
            'message' => 'User dihapus.',
        ]);
    }
}
