<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\StudentProfile;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login_identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $loginIdentifier = trim((string) $data['login_identifier']);
        $password = (string) $data['password'];
        $deviceName = trim((string) ($data['device_name'] ?? 'flutter'));
        if ($deviceName === '') {
            $deviceName = 'flutter';
        }

        $user = null;

        // Try teacher via NIP
        $teacher = Teacher::query()
            ->where('nip', $loginIdentifier)
            ->with('user')
            ->first();
        if ($teacher?->user && Hash::check($password, (string) $teacher->user->password)) {
            $user = $teacher->user;
        }

        // Try student via NISN
        if (! $user) {
            $student = StudentProfile::query()
                ->where('nis', $loginIdentifier)
                ->with('user')
                ->first();

            if ($student?->user && Hash::check($password, (string) $student->user->password)) {
                $user = $student->user;
            }
        }

        // Try admin/petugas piket via email
        if (! $user) {
            $emailUser = User::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($loginIdentifier)])
                ->first();

            $isAllowedPrivilegedRole = $emailUser && (
                (method_exists($emailUser, 'hasAnyRole') && $emailUser->hasAnyRole(['admin', 'petugas_piket']))
                || in_array((string) ($emailUser->role ?? ''), ['admin', 'petugas_piket'], true)
            );

            if ($emailUser && $isAllowedPrivilegedRole && Hash::check($password, (string) $emailUser->password)) {
                $user = $emailUser;
            }
        }

        if (! $user) {
            throw ValidationException::withMessages([
                'login_identifier' => [trans('auth.failed')],
            ]);
        }

        $user->load(['roles', 'studentProfile.classRoom', 'teacher']);
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => (new UserResource($user))->toArray($request),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->load(['roles', 'studentProfile.classRoom', 'teacher']);

        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->toArray($request),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user?->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->hasAnyRole(['admin', 'petugas_piket'])) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    'password' => ['Tidak diizinkan mengubah password untuk peran ini.'],
                ]
            ], 422);
        }

        $rules = [
            'old_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if (empty($user->whatsapp_number)) {
            $rules['whatsapp_number'] = ['required', 'string', 'regex:/^08[0-9]+$/', 'max:30'];
        } else {
            $rules['whatsapp_number'] = ['nullable', 'string', 'regex:/^08[0-9]+$/', 'max:30'];
        }

        $messages = [
            'whatsapp_number.regex' => 'Nomor WhatsApp harus diawali dengan 08 dan hanya berisi angka.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
        ];

        $data = $request->validate($rules, $messages);

        if (!Hash::check($data['old_password'], $user->password)) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    'old_password' => ['Password lama tidak sesuai.'],
                ]
            ], 422);
        }

        $updateData = [
            'password' => Hash::make($data['password']),
        ];

        if (!empty($data['whatsapp_number'])) {
            $updateData['whatsapp_number'] = $data['whatsapp_number'];
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'Password dan nomor HP berhasil diubah.',
        ]);
    }

    public function updatePhone(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->hasAnyRole(['admin', 'petugas_piket'])) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    'whatsapp_number' => ['Tidak diizinkan mengubah profil untuk peran ini.'],
                ]
            ], 422);
        }

        $data = $request->validate([
            'whatsapp_number' => ['required', 'string', 'regex:/^08[0-9]+$/', 'max:30'],
        ], [
            'whatsapp_number.regex' => 'Nomor WhatsApp harus diawali dengan 08 dan hanya berisi angka.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        $user->update([
            'whatsapp_number' => $data['whatsapp_number']
        ]);

        return response()->json([
            'message' => 'Nomor HP berhasil diperbarui.',
            'whatsapp_number' => $user->whatsapp_number,
        ]);
    }
}
