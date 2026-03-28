<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function (): JsonResponse {
    return response()->json([
        'agent' => 'criterion',
        'status' => 'operational',
        'version' => config('criterion.version', '1.0.0'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::post('/prompt', function (Request $request): JsonResponse {
    $validated = $request->validate([
        'message' => 'required|string|max:2000',
        'conversation_id' => 'nullable|string',
    ]);

    $agent = app(\App\Agent\CriterionAgent::class);

    $response = $agent->respond($validated['message'], $validated['conversation_id'] ?? null);

    return response()->json([
        'response' => $response,
        'conversation_id' => $validated['conversation_id'],
    ]);
});

Route::post('/webhooks/bifrost', function (Request $request): JsonResponse {
    $payload = $request->all();

    // Future: dispatch Bifrost events to agent
    return response()->json(['received' => true]);
});
