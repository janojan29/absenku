<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\ClassRoom;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $students = User::query()
            ->role('siswa')
            ->with(['studentProfile.classRoom', 'roles'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->where('name', 'like', $like)
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
                        });
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'data' => $students->getCollection()
                ->map(fn ($item) => (new UserResource($item))->toArray($request))
                ->values(),
            'meta' => [
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'jurusan' => ['required', 'string', 'max:100'],
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'nis' => ['required', 'string', 'max:50', 'unique:student_profiles,nis'],
            'parent_phone_wa' => ['nullable', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
        ]);

        $selectedClass = ClassRoom::query()->findOrFail((int) $data['class_room_id']);
        if ((string) $selectedClass->jurusan !== (string) $data['jurusan']) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    'class_room_id' => ['Kelas tidak sesuai dengan jurusan yang dipilih.'],
                ],
            ], 422);
        }

        $data['jurusan'] = $selectedClass->jurusan;

        $generatedEmailLocalPart = preg_replace('/[^A-Za-z0-9]/', '', (string) $data['nis']);
        $generatedEmail = strtolower($generatedEmailLocalPart) . '@sekolah.local';

        $user = User::create([
            'name' => $data['name'],
            'email' => $generatedEmail,
            'password' => Hash::make($data['password']),
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);
        $user->assignRole('siswa');

        StudentProfile::create([
            'user_id' => $user->id,
            'class_room_id' => $data['class_room_id'],
            'nis' => $data['nis'],
            'jurusan' => $data['jurusan'] ?? null,
            'parent_phone_wa' => $data['parent_phone_wa'] ?? null,
        ]);

        $user->load(['roles', 'studentProfile.classRoom']);

        return response()->json([
            'data' => [
                'student' => (new UserResource($user))->toArray($request),
            ],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if (! $user->hasRole('siswa')) {
            return response()->json([
                'message' => 'User ini bukan role siswa.',
            ], 422);
        }

        $data = $request->validate([
            'jurusan' => ['required', 'string', 'max:100'],
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'nis' => ['nullable', 'string', 'max:50'],
            'parent_phone_wa' => ['nullable', 'string', 'max:30'],
        ]);

        $selectedClass = ClassRoom::query()->findOrFail((int) $data['class_room_id']);
        if ((string) $selectedClass->jurusan !== (string) $data['jurusan']) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    'class_room_id' => ['Kelas tidak sesuai dengan jurusan yang dipilih.'],
                ],
            ], 422);
        }

        $data['jurusan'] = $selectedClass->jurusan;

        StudentProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'class_room_id' => $data['class_room_id'],
                'nis' => $data['nis'],
                'jurusan' => $data['jurusan'],
                'parent_phone_wa' => $data['parent_phone_wa'],
            ]
        );

        $user->load(['roles', 'studentProfile.classRoom']);

        return response()->json([
            'data' => [
                'student' => (new UserResource($user))->toArray($request),
            ],
        ]);
    }
}
