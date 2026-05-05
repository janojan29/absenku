<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $students = User::query()
            ->role('siswa')
            ->with(['studentProfile.classRoom'])
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

        return view('admin.students.index', [
            'students' => $students,
            'q' => $q,
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

    public function store(Request $request): RedirectResponse
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

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $user->hasRole('siswa')) {
            return back()->withErrors(['student' => 'User ini bukan role siswa.']);
        }

        $data = $request->validate([
            'jurusan' => ['required', 'string', 'max:100'],
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'nis' => ['nullable', 'string', 'max:50'],
            'parent_phone_wa' => ['nullable', 'string', 'max:30'],
        ]);

        $selectedClass = ClassRoom::query()->findOrFail((int) $data['class_room_id']);
        if ((string) $selectedClass->jurusan !== (string) $data['jurusan']) {
            return back()->withInput()->withErrors([
                'class_room_id' => 'Kelas tidak sesuai dengan jurusan yang dipilih.',
            ]);
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

        return redirect()->route('admin.students.index')->with('status', 'Profil siswa diperbarui.');
    }
}
