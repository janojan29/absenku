<?php

namespace App\Http\Controllers\Api\Picket;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveQueueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pending = LeaveRequest::query()
            ->with(['user.studentProfile.classRoom'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->paginate(15, ['*'], 'pendingPage');

        $history = LeaveRequest::query()
            ->with(['user.studentProfile.classRoom', 'decidedBy'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderByDesc('decided_at')
            ->orderByDesc('updated_at')
            ->paginate(15, ['*'], 'historyPage');

        return response()->json([
            'pending' => [
                'data' => $pending->getCollection()
                    ->map(fn ($item) => (new LeaveRequestResource($item))->toArray($request))
                    ->values(),
                'meta' => [
                    'pagination' => [
                        'current_page' => $pending->currentPage(),
                        'last_page' => $pending->lastPage(),
                        'per_page' => $pending->perPage(),
                        'total' => $pending->total(),
                    ],
                ],
            ],
            'history' => [
                'data' => $history->getCollection()
                    ->map(fn ($item) => (new LeaveRequestResource($item))->toArray($request))
                    ->values(),
                'meta' => [
                    'pagination' => [
                        'current_page' => $history->currentPage(),
                        'last_page' => $history->lastPage(),
                        'per_page' => $history->perPage(),
                        'total' => $history->total(),
                    ],
                ],
            ],
        ]);
    }
}
