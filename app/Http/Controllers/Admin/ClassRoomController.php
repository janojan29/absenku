<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassRoomController extends Controller
{
    public function index(): View
    {
        $classes = ClassRoom::query()
            ->orderBy('name')
            ->orderBy('jurusan')
            ->paginate(20);

        return view('admin.class-rooms.index', [
            'classes' => $classes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'jurusan' => ['required', 'string', 'max:100'],
        ]);

        $exists = ClassRoom::query()
            ->where('name', $data['name'])
            ->where('jurusan', $data['jurusan'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'name' => 'Kombinasi kelas dan jurusan sudah ada.',
            ])->withInput();
        }

        ClassRoom::query()->create($data);

        return back()->with('status', 'Kelas ditambahkan.');
    }

    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        $classRoom->delete();

        return back()->with('status', 'Kelas dihapus.');
    }
}
