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
use Illuminate\Support\Facades\DB;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;

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
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'jurusan' => ['required', 'string', 'max:100'],
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'nis' => ['nullable', 'string', 'max:50'],
            'parent_phone_wa' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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

        $updateData = [
            'name' => $data['name'],
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

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

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new StudentsImport();
        Excel::import($import, $data['file']);

        $errors = [];
        $created = 0;
        $updated = 0;

        $classRooms = ClassRoom::query()
            ->select(['id', 'name', 'jurusan'])
            ->get();

        $normalize = static function (?string $value): string {
            $value = trim((string) $value);
            $value = preg_replace('/\s+/', ' ', $value) ?? '';

            return mb_strtolower($value);
        };

        foreach ($import->rows() as $index => $row) {
            $rowNumber = $index + 2;

            $name = trim((string) ($row['nama'] ?? $row['name'] ?? ''));
            $nis = trim((string) ($row['nisn'] ?? $row['nis'] ?? ''));
            $kelas = trim((string) ($row['kelas'] ?? ''));
            $jurusan = trim((string) ($row['jurusan'] ?? ''));
            $parentPhone = trim((string) ($row['nohp_orangtua'] ?? $row['no_hp_orangtua'] ?? $row['nohp_ortu'] ?? $row['no_hp_ortu'] ?? ''));
            $studentPhone = trim((string) ($row['nohp_siswa'] ?? $row['no_hp_siswa'] ?? ''));

            if ($name === '' || $nis === '' || $kelas === '' || $jurusan === '') {
                $errors[] = "Baris {$rowNumber}: Kolom wajib tidak lengkap.";
                continue;
            }

            if (!preg_match('/^[a-zA-Z\s.,\'\-]+$/', $name)) {
                $errors[] = "Baris {$rowNumber}: Nama siswa hanya boleh berisi huruf, spasi, dan tanda baca nama.";
                continue;
            }

            if (!preg_match('/^[0-9]+$/', $nis)) {
                $errors[] = "Baris {$rowNumber}: NISN harus berupa angka.";
                continue;
            }

            if ($parentPhone !== '' && !preg_match('/^(08|\+62|62)[0-9]+$/', $parentPhone)) {
                $errors[] = "Baris {$rowNumber}: Nomor HP orang tua harus diawali dengan 08, 62, atau +62.";
                continue;
            }

            if ($studentPhone !== '' && !preg_match('/^(08|\+62|62)[0-9]+$/', $studentPhone)) {
                $errors[] = "Baris {$rowNumber}: Nomor HP siswa harus diawali dengan 08, 62, atau +62.";
                continue;
            }

            $existingProfile = StudentProfile::query()->where('nis', $nis)->first();

            $classRoom = $classRooms->first(function ($classRoom) use ($kelas, $jurusan, $normalize) {
                return $normalize($classRoom->name) === $normalize($kelas)
                    && $normalize($classRoom->jurusan) === $normalize($jurusan);
            });

            if (! $classRoom) {
                $classRoom = $classRooms->first(function ($classRoom) use ($kelas, $normalize) {
                    return $normalize($classRoom->name) === $normalize($kelas);
                });
            }

            if (! $classRoom) {
                $errors[] = "Baris {$rowNumber}: Kelas '{$kelas}' dengan jurusan '{$jurusan}' tidak ditemukan.";
                continue;
            }

            $generatedEmailLocalPart = preg_replace('/[^A-Za-z0-9]/', '', $nis);
            $generatedEmail = strtolower($generatedEmailLocalPart) . '@sekolah.local';

            DB::transaction(function () use ($name, $generatedEmail, $nis, $classRoom, $parentPhone, $studentPhone, $existingProfile, &$created, &$updated) {
                if ($existingProfile) {
                    $user = clone $existingProfile->user; // Avoid caching issues
                    if ($user) {
                        $user->update([
                            'name' => $name,
                            'whatsapp_number' => $studentPhone !== '' ? $studentPhone : null,
                        ]);
                    }
                    
                    $existingProfile->update([
                        'class_room_id' => $classRoom->id,
                        'jurusan' => $classRoom->jurusan,
                        'parent_phone_wa' => $parentPhone !== '' ? $parentPhone : null,
                    ]);
                    
                    $updated++;
                } else {
                    $user = User::create([
                        'name' => $name,
                        'email' => $generatedEmail,
                        'password' => Hash::make('siswa123'),
                        'whatsapp_number' => $studentPhone !== '' ? $studentPhone : null,
                    ]);

                    $user->assignRole('siswa');

                    StudentProfile::create([
                        'user_id' => $user->id,
                        'class_room_id' => $classRoom->id,
                        'nis' => $nis,
                        'jurusan' => $classRoom->jurusan,
                        'parent_phone_wa' => $parentPhone !== '' ? $parentPhone : null,
                    ]);

                    $created++;
                }
            });
        }

        $statusMsg = "Import selesai: {$created} siswa baru ditambahkan, {$updated} diperbarui.";
        if (! empty($errors)) {
            $statusMsg .= ' (' . count($errors) . ' gagal).';
            return response()->json([
                'message' => $statusMsg,
                'errors' => $errors,
                'created' => $created,
                'updated' => $updated,
            ], 422);
        }
        return response()->json([
            'message' => $statusMsg,
            'created' => $created,
            'updated' => $updated,
        ]);
    }

    public function downloadTemplate()
    {
        return Excel::download(new \App\Exports\StudentsTemplateExport(), 'template-import-siswa.xlsx');
    }

    public function bulkDeleteByClass(Request $request): JsonResponse
    {
        $data = $request->validate([
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
        ]);

        $classRoomId = (int) $data['class_room_id'];

        $studentUserIds = StudentProfile::query()
            ->where('class_room_id', $classRoomId)
            ->pluck('user_id');

        if ($studentUserIds->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada siswa pada kelas tersebut.',
            ], 404);
        }

        DB::transaction(function () use ($classRoomId, $studentUserIds) {
            StudentProfile::query()->where('class_room_id', $classRoomId)->delete();
            User::query()->role('siswa')->whereIn('id', $studentUserIds)->delete();
        });

        return response()->json([
            'message' => 'Semua akun siswa pada kelas terpilih berhasil dihapus.',
        ]);
    }

    public function bulkUpdateClass(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'to_class_room_id' => ['required', 'integer', 'exists:class_rooms,id', 'different:from_class_room_id'],
        ], [
            'to_class_room_id.different' => 'Kelas asal dan kelas tujuan harus berbeda.',
        ]);

        $fromClassId = (int) $data['from_class_room_id'];
        $toClassId = (int) $data['to_class_room_id'];

        $fromClass = ClassRoom::query()->findOrFail($fromClassId);
        $toClass = ClassRoom::query()->findOrFail($toClassId);

        if (mb_strtolower(trim($fromClass->jurusan ?? '')) !== mb_strtolower(trim($toClass->jurusan ?? ''))) {
            return response()->json([
                'message' => 'Jurusan kelas asal dan kelas tujuan harus sama.',
                'errors' => [
                    'to_class_room_id' => ['Jurusan kelas asal dan kelas tujuan harus sama.']
                ]
            ], 422);
        }

        $toClassHasStudents = StudentProfile::query()
            ->where('class_room_id', $toClassId)
            ->exists();

        if ($toClassHasStudents) {
            return response()->json([
                'message' => 'Kelas tujuan masih memiliki siswa. Kosongkan kelas tujuan terlebih dahulu untuk menghindari penumpukan.',
                'errors' => [
                    'to_class_room_id' => ['Kelas tujuan masih memiliki siswa. Kosongkan kelas tujuan terlebih dahulu untuk menghindari penumpukan.']
                ]
            ], 422);
        }

        $count = StudentProfile::query()
            ->where('class_room_id', $fromClassId)
            ->count();

        if ($count === 0) {
            return response()->json([
                'message' => 'Tidak ada siswa pada kelas asal tersebut.',
                'errors' => [
                    'from_class_room_id' => ['Tidak ada siswa pada kelas asal tersebut.']
                ]
            ], 422);
        }

        DB::transaction(function () use ($fromClassId, $toClassId, $toClass) {
            StudentProfile::query()
                ->where('class_room_id', $fromClassId)
                ->update([
                    'class_room_id' => $toClassId,
                    'jurusan' => $toClass->jurusan,
                ]);
        });

        return response()->json([
            'message' => "Berhasil memindahkan {$count} siswa ke kelas {$toClass->name}.",
        ]);
    }
}
