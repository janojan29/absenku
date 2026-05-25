<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $teachers = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['guru', 'guru_walikelas']))
            ->with(['teacher', 'roles'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->where('name', 'like', $like)
                        ->orWhere('whatsapp_number', 'like', $like)
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
            'data' => $teachers->getCollection()
                ->map(fn ($item) => (new UserResource($item))->toArray($request))
                ->values(),
            'meta' => [
                'pagination' => [
                    'current_page' => $teachers->currentPage(),
                    'last_page' => $teachers->lastPage(),
                    'per_page' => $teachers->perPage(),
                    'total' => $teachers->total(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'teacher_role' => ['required', 'string', 'in:guru,guru_walikelas'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'nip' => ['required', 'string', 'max:50', 'unique:teachers,nip'],
            'subject' => ['nullable', 'string', 'max:150'],
            'wali_kelas' => ['nullable', 'string', 'max:100'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
        ]);

        if ($data['teacher_role'] === 'guru_walikelas' && empty($data['wali_kelas'])) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    'wali_kelas' => ['Keterangan wali kelas wajib diisi untuk role Guru Walikelas.'],
                ],
            ], 422);
        }

        $generatedEmailLocalPart = preg_replace('/[^A-Za-z0-9]/', '', (string) $data['nip']);
        $generatedEmail = strtolower($generatedEmailLocalPart) . '@sekolah.local';

        $user = User::create([
            'name' => $data['name'],
            'email' => $generatedEmail,
            'password' => Hash::make($data['password']),
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);
        $user->assignRole($data['teacher_role']);

        Teacher::create([
            'user_id' => $user->id,
            'nip' => $data['nip'],
            'subject' => $data['subject'] ?? null,
            'wali_kelas' => $data['wali_kelas'] ?? null,
        ]);

        $user->load(['roles', 'teacher']);

        return response()->json([
            'data' => [
                'teacher' => (new UserResource($user))->toArray($request),
            ],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if (! $user->hasAnyRole(['guru', 'guru_walikelas'])) {
            return response()->json([
                'message' => 'User ini bukan role guru.',
            ], 422);
        }

        $data = $request->validate([
            'teacher_role' => ['required', 'string', 'in:guru,guru_walikelas'],
            'nip' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('teachers', 'nip')->ignore($user->teacher?->id),
            ],
            'subject' => ['nullable', 'string', 'max:150'],
            'wali_kelas' => ['nullable', 'string', 'max:100'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
        ]);

        if ($data['teacher_role'] === 'guru_walikelas' && empty($data['wali_kelas'])) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    'wali_kelas' => ['Keterangan wali kelas wajib diisi untuk role Guru Walikelas.'],
                ],
            ], 422);
        }

        $user->update([
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);
        $user->syncRoles([$data['teacher_role']]);

        if ($user->teacher) {
            $user->teacher->update([
                'nip' => $data['nip'],
                'subject' => $data['subject'],
                'wali_kelas' => $data['wali_kelas'] ?? null,
            ]);
        } else {
            Teacher::create([
                'user_id' => $user->id,
                'nip' => $data['nip'],
                'subject' => $data['subject'],
                'wali_kelas' => $data['wali_kelas'] ?? null,
            ]);
        }

        $user->load(['roles', 'teacher']);

        return response()->json([
            'data' => [
                'teacher' => (new UserResource($user))->toArray($request),
            ],
        ]);
    }
}
