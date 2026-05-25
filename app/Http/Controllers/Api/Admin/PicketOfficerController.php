<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PicketOfficerController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (User::role('petugas_piket')->count() >= 2) {
            return response()->json([
                'message' => 'Maksimal hanya 2 user Petugas Piket.',
            ], 422);
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

        return response()->json([
            'message' => 'Petugas Piket berhasil ditambahkan.',
        ]);
    }
}
