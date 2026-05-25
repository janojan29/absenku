<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassRoomResource;
use App\Models\ClassRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassRoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $classes = ClassRoom::query()
            ->orderBy('name')
            ->orderBy('jurusan')
            ->paginate(20);

        return response()->json([
            'data' => $classes->getCollection()
                ->map(fn ($item) => (new ClassRoomResource($item))->toArray($request))
                ->values(),
            'meta' => [
                'pagination' => [
                    'current_page' => $classes->currentPage(),
                    'last_page' => $classes->lastPage(),
                    'per_page' => $classes->perPage(),
                    'total' => $classes->total(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
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
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    'name' => ['Kombinasi kelas dan jurusan sudah ada.'],
                ],
            ], 422);
        }

        $classRoom = ClassRoom::query()->create($data);

        return response()->json([
            'data' => [
                'class_room' => (new ClassRoomResource($classRoom))->toArray($request),
            ],
        ]);
    }

    public function destroy(Request $request, ClassRoom $classRoom): JsonResponse
    {
        $classRoom->delete();

        return response()->json([
            'message' => 'Kelas dihapus.',
        ]);
    }
}
