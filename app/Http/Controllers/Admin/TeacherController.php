<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $teachers = User::query()
            ->whereHas('roles', fn($query) => $query->whereIn('name', ['guru', 'guru_walikelas']))
            ->with(['teacher'])
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

        return view('admin.teachers.index', [
            'teachers' => $teachers,
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('admin.teachers.create');
    }

    public function edit(User $user): View|RedirectResponse
    {
        if (! $user->hasAnyRole(['guru', 'guru_walikelas'])) {
            return redirect()->route('admin.teachers.index')->withErrors([
                'teacher' => 'User ini bukan role guru.',
            ]);
        }

        $user->load(['teacher']);

        return view('admin.teachers.edit', [
            'teacher' => $user,
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            return back()->withInput()->withErrors([
                'wali_kelas' => 'Keterangan wali kelas wajib diisi untuk role Guru Walikelas.',
            ]);
        }

        $generatedEmailLocalPart = preg_replace('/[^A-Za-z0-9]/', '', (string) $data['nip']);
        $generatedEmail = strtolower($generatedEmailLocalPart) . '@sekolah.local';

        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $generatedEmail,
            'password' => Hash::make($data['password']),
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);

        // Assign role
        $user->assignRole($data['teacher_role']);

        // Create teacher profile
        Teacher::create([
            'user_id' => $user->id,
            'nip' => $data['nip'],
            'subject' => $data['subject'] ?? null,
            'wali_kelas' => $data['wali_kelas'] ?? null,
        ]);

        return redirect()->route('admin.teachers.index')->with('status', 'Guru baru berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $user->hasAnyRole(['guru', 'guru_walikelas'])) {
            return back()->withErrors(['teacher' => 'User ini bukan role guru.']);
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
            return back()->withInput()->withErrors([
                'wali_kelas' => 'Keterangan wali kelas wajib diisi untuk role Guru Walikelas.',
            ]);
        }

        // Update user
        $user->update([
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);

        $user->syncRoles([$data['teacher_role']]);

        // Update teacher profile
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

        return redirect()->route('admin.teachers.index')->with('status', 'Data guru diperbarui.');
    }
}
