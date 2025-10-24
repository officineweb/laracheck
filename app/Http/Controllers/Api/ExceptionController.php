<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exception;
use App\Models\Site;
use Illuminate\Http\Request;

class ExceptionController extends Controller
{
    public function store(Request $request)
    {
        $site = Site::where('key', $request->header('Authorization'))->first();

        if (! $site) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        $validated = $request->validate([
            'exception' => 'required|string',
            'line' => 'nullable|string',
            'file' => 'nullable|string',
            'class' => 'nullable|string',
            'host' => 'nullable|string',
            'env' => 'nullable|string',
            'method' => 'nullable|string',
            'code' => 'nullable|integer',
            'fullUrl' => 'nullable|string',
            'error' => 'nullable|string',
            'user' => 'nullable|array',
            'storage' => 'nullable|array',
            'executor' => 'nullable|array',
            'additional' => 'nullable|array',
        ]);

        $code = $validated['code'] ?? 500;

        // Auto-fix 4xx errors (client errors) - no notifications
        $status = ($code >= 400 && $code < 500) ? Exception::FIXED : Exception::OPEN;

        $exception = Exception::create([
            'site_id' => $site->id,
            'exception' => $validated['exception'],
            'line' => $validated['line'] ?? null,
            'file' => $validated['file'] ?? null,
            'class' => $validated['class'] ?? null,
            'host' => $validated['host'] ?? null,
            'env' => $validated['env'] ?? null,
            'method' => $validated['method'] ?? null,
            'code' => $code,
            'full_url' => $validated['fullUrl'] ?? null,
            'error' => $validated['error'] ?? null,
            'user' => $validated['user'] ?? null,
            'storage' => $validated['storage'] ?? null,
            'executor' => $validated['executor'] ?? null,
            'additional' => $validated['additional'] ?? null,
            'status' => $status,
        ]);

        // Update site's last exception timestamp
        $site->update(['last_exception_at' => now()]);

        return response()->json([
            'success' => true,
            'exception_id' => $exception->id,
        ], 201);
    }

    public function show(Request $request, $hash)
    {
        $exception = Exception::where('publish_hash', $hash)->firstOrFail();

        return response()->json([
            'exception' => $exception->load(['site']),
        ]);
    }
}
