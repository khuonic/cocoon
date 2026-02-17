<?php

namespace App\Http\Controllers\Api;

use App\Enums\SyncAction;
use App\Http\Controllers\Controller;
use App\Services\SyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(private SyncService $syncService) {}

    /**
     * Receive changes from a device and apply them.
     */
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'changes' => ['required', 'array', 'max:100'],
            'changes.*.type' => ['required', 'string'],
            'changes.*.uuid' => ['required', 'string'],
            'changes.*.action' => ['required', 'string', 'in:created,updated,deleted'],
            'changes.*.data' => ['nullable', 'array'],
            'changes.*.updated_at' => ['required', 'date'],
        ]);

        $applied = 0;
        $rejected = 0;

        foreach ($request->input('changes') as $change) {
            $success = $this->syncService->applyChange(
                type: $change['type'],
                uuid: $change['uuid'],
                action: SyncAction::from($change['action']),
                data: $change['data'] ?? null,
                updatedAt: Carbon::parse($change['updated_at']),
            );

            if ($success) {
                $applied++;
            } else {
                $rejected++;
            }
        }

        return response()->json([
            'applied' => $applied,
            'rejected' => $rejected,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Return changes since a given timestamp.
     */
    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'since' => ['required', 'date'],
        ]);

        $since = Carbon::parse($request->input('since'));
        $deviceId = $request->header('X-Device-Id');

        $changes = $this->syncService->getChangesSince($since, $deviceId);

        return response()->json([
            'changes' => $changes->values(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Full sync: receive all client data + return all server data.
     */
    public function full(Request $request): JsonResponse
    {
        $request->validate([
            'changes' => ['nullable', 'array', 'max:500'],
            'changes.*.type' => ['required', 'string'],
            'changes.*.uuid' => ['required', 'string'],
            'changes.*.action' => ['required', 'string', 'in:created,updated,deleted'],
            'changes.*.data' => ['nullable', 'array'],
            'changes.*.updated_at' => ['required', 'date'],
        ]);

        $applied = 0;
        $rejected = 0;

        foreach ($request->input('changes', []) as $change) {
            $success = $this->syncService->applyChange(
                type: $change['type'],
                uuid: $change['uuid'],
                action: SyncAction::from($change['action']),
                data: $change['data'] ?? null,
                updatedAt: Carbon::parse($change['updated_at']),
            );

            if ($success) {
                $applied++;
            } else {
                $rejected++;
            }
        }

        $allData = $this->syncService->getAllData();

        return response()->json([
            'applied' => $applied,
            'rejected' => $rejected,
            'changes' => $allData->values(),
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
