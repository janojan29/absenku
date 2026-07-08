<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Provides an HTTP endpoint to trigger scheduled attendance tasks.
 *
 * This is the fallback mechanism: if the system cron is not running
 * `php artisan schedule:run`, an external service (e.g., cron-job.org,
 * UptimeRobot, or a simple curl) can ping this endpoint every minute
 * to trigger the attendance checks.
 *
 * The endpoint is protected by a bearer token defined in the .env file.
 */
class CronTriggerController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expectedToken = config('app.cron_secret');

        if (empty($expectedToken)) {
            return response()->json([
                'status' => 'error',
                'message' => 'CRON_SECRET not configured on server.',
            ], 500);
        }

        $providedToken = $request->bearerToken() ?? $request->query('token');

        if (! hash_equals($expectedToken, (string) $providedToken)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            Artisan::call('attendance:run-scheduled');
            $output = Artisan::output();
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'ok',
            'output' => trim($output),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
