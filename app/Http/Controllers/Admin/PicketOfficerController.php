<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PicketOfficerController extends Controller
{
    public function create(): View
    {
        $existingCount = User::role('petugas_piket')->count();

        return view('admin.picket-officers.create', [
            'existingCount' => $existingCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (User::role('petugas_piket')->count() >= 2) {
            return back()->withErrors([
                'role' => 'Maksimal hanya 2 user Petugas Piket.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);

        $user->assignRole('petugas_piket');

        return redirect()->route('admin.users.index')->with('status', 'Petugas Piket berhasil ditambahkan.');
    }
}
