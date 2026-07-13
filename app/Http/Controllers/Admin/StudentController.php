<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\StudentsTemplateExport;
use App\Imports\StudentsImport;
use App\Models\ClassRoom;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $classes = ClassRoom::query()
            ->orderBy('jurusan')
            ->orderBy('name')
            ->get();

        return view('admin.students.index', [
            'classes' => $classes,
        ]);
    }

    public function edit(User $user): View|RedirectResponse
    {
        if (! $user->hasRole('siswa')) {
            return redirect()->route('admin.students.index')->withErrors([
                'student' => 'User ini bukan role siswa.',
            ]);
        }

        $user->load(['studentProfile.classRoom']);

        $classes = ClassRoom::query()
            ->orderBy('jurusan')
            ->orderBy('name')
            ->get();

        $jurusans = $classes
            ->pluck('jurusan')
            ->filter()
            ->unique()
            ->values();

        return view('admin.students.edit', [
            'student' => $user,
            'classes' => $classes,
            'jurusans' => $jurusans,
        ]);
    }

    public function create(): View
    {
        $classes = ClassRoom::query()
            ->orderBy('jurusan')
            ->orderBy('name')
            ->get();

        $jurusans = $classes
            ->pluck('jurusan')
            ->filter()
            ->unique()
            ->values();

        return view('admin.students.create', [
            'classes' => $classes,
            'jurusans' => $jurusans,
        ]);
    }

    public function importForm(): View
    {
        return view('admin.students.import');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'regex:/^[a-zA-Z\s.,\'\-]+$/', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'jurusan' => ['required', 'string', 'max:100'],
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'nis' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:50', 'unique:student_profiles,nis'],
            'parent_phone_wa' => ['nullable', 'string', 'regex:/^(08|\+62|62)[0-9]+$/', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'regex:/^(08|\+62|62)[0-9]+$/', 'max:30'],
        ], [
            'name.regex' => 'Nama siswa hanya boleh berisi huruf, spasi, dan tanda baca nama.',
            'nis.regex' => 'NISN harus berupa angka.',
            'parent_phone_wa.regex' => 'Nomor HP orang tua harus diawali dengan 08 dan hanya berisi angka.',
            'whatsapp_number.regex' => 'Nomor WhatsApp siswa harus diawali dengan 08 dan hanya berisi angka.',
        ]);

        $selectedClass = ClassRoom::query()->findOrFail((int) $data['class_room_id']);
        if ((string) $selectedClass->jurusan !== (string) $data['jurusan']) {
            return back()->withInput()->withErrors([
                'class_room_id' => 'Kelas tidak sesuai dengan jurusan yang dipilih.',
            ]);
        }

        $data['jurusan'] = $selectedClass->jurusan;

        $generatedEmailLocalPart = preg_replace('/[^A-Za-z0-9]/', '', (string) $data['nis']);
        $generatedEmail = strtolower($generatedEmailLocalPart) . '@sekolah.local';

        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $generatedEmail,
            'password' => Hash::make($data['password']),
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);

        // Assign siswa role
        $user->assignRole('siswa');

        // Create student profile
        StudentProfile::create([
            'user_id' => $user->id,
            'class_room_id' => $data['class_room_id'],
            'nis' => $data['nis'],
            'jurusan' => $data['jurusan'] ?? null,
            'parent_phone_wa' => $data['parent_phone_wa'] ?? null,
        ]);

        return redirect()->route('admin.students.index')->with('status', 'Siswa baru berhasil ditambahkan.');
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new StudentsImport();
        Excel::import($import, $data['file']);

        $errors = [];
        $created = 0;

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

            if (StudentProfile::query()->where('nis', $nis)->exists()) {
                $errors[] = "Baris {$rowNumber}: NISN {$nis} sudah terdaftar.";
                continue;
            }

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

            DB::transaction(function () use ($name, $generatedEmail, $nis, $classRoom, $parentPhone, $studentPhone, &$created) {
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
            });
        }

        if (! empty($errors)) {
            return redirect()
                ->route('admin.students.import')
                ->with('import_errors', $errors)
                ->with('status', "Import selesai: {$created} siswa berhasil ditambahkan, " . count($errors) . ' gagal.');
        }

        return redirect()
            ->route('admin.students.import')
            ->with('status', "Import selesai: {$created} siswa berhasil ditambahkan.");
    }

    public function downloadTemplate()
    {
        return Excel::download(new StudentsTemplateExport(), 'template-import-siswa.xlsx');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $user->hasRole('siswa')) {
            return back()->withErrors(['student' => 'User ini bukan role siswa.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'regex:/^[a-zA-Z\s.,\'\-]+$/', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'regex:/^(08|\+62|62)[0-9]+$/', 'max:30'],
            'jurusan' => ['required', 'string', 'max:100'],
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'nis' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
                'max:50',
                \Illuminate\Validation\Rule::unique('student_profiles', 'nis')->ignore($user->studentProfile?->id),
            ],
            'parent_phone_wa' => ['nullable', 'string', 'regex:/^(08|\+62|62)[0-9]+$/', 'max:30'],
        ], [
            'name.regex' => 'Nama siswa hanya boleh berisi huruf, spasi, dan tanda baca nama.',
            'nis.regex' => 'NISN harus berupa angka.',
            'whatsapp_number.regex' => 'Nomor WhatsApp siswa harus diawali dengan 08 dan hanya berisi angka.',
            'parent_phone_wa.regex' => 'Nomor HP orang tua harus diawali dengan 08 dan hanya berisi angka.',
        ]);

        $selectedClass = ClassRoom::query()->findOrFail((int) $data['class_room_id']);
        if ((string) $selectedClass->jurusan !== (string) $data['jurusan']) {
            return back()->withInput()->withErrors([
                'class_room_id' => 'Kelas tidak sesuai dengan jurusan yang dipilih.',
            ]);
        }

        $data['jurusan'] = $selectedClass->jurusan;

        // Update User
        $user->update([
            'name' => $data['name'],
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);

        // Update Student Profile
        StudentProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'class_room_id' => $data['class_room_id'],
                'nis' => $data['nis'],
                'jurusan' => $data['jurusan'],
                'parent_phone_wa' => $data['parent_phone_wa'],
            ]
        );

        return redirect()->route('admin.students.index')->with('status', 'Profil siswa diperbarui.');
    }

    public function bulkDeleteByClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
        ]);

        $classRoomId = (int) $data['class_room_id'];
        $studentUserIds = StudentProfile::query()
            ->where('class_room_id', $classRoomId)
            ->pluck('user_id');

        if ($studentUserIds->isEmpty()) {
            return redirect()
                ->route('admin.students.index')
                ->with('status', 'Tidak ada siswa pada kelas tersebut.');
        }

        DB::transaction(function () use ($classRoomId, $studentUserIds) {
            StudentProfile::query()->where('class_room_id', $classRoomId)->delete();
            User::query()->role('siswa')->whereIn('id', $studentUserIds)->delete();
        });

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Semua akun siswa pada kelas terpilih berhasil dihapus.');
    }

    public function bulkUpdateClass(Request $request): RedirectResponse
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
            return redirect()
                ->route('admin.students.index')
                ->withErrors([
                    'to_class_room_id' => 'Jurusan kelas asal dan kelas tujuan harus sama.',
                ]);
        }

        $toClassHasStudents = StudentProfile::query()
            ->where('class_room_id', $toClassId)
            ->exists();

        if ($toClassHasStudents) {
            return redirect()
                ->route('admin.students.index')
                ->withErrors([
                    'to_class_room_id' => 'Kelas tujuan masih memiliki siswa. Kosongkan kelas tujuan terlebih dahulu untuk menghindari penumpukan.',
                ]);
        }

        $count = StudentProfile::query()
            ->where('class_room_id', $fromClassId)
            ->count();

        if ($count === 0) {
            return redirect()
                ->route('admin.students.index')
                ->withErrors([
                    'from_class_room_id' => 'Tidak ada siswa pada kelas asal tersebut.',
                ]);
        }

        DB::transaction(function () use ($fromClassId, $toClassId, $toClass) {
            StudentProfile::query()
                ->where('class_room_id', $fromClassId)
                ->update([
                    'class_room_id' => $toClassId,
                    'jurusan' => $toClass->jurusan,
                ]);
        });

        return redirect()
            ->route('admin.students.index')
            ->with('status', "Berhasil memindahkan {$count} siswa ke kelas {$toClass->name}.");
    }
}
