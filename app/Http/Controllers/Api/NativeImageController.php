<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NativeImageController extends Controller
{
    /**
     * Store a native filesystem image path (NativePHP camera/gallery)
     * by reading the file on-device and saving it to public storage.
     * Returns a public URL and stored path usable by the recipe controller.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'native_path' => ['required', 'string', 'max:500'],
        ]);

        $nativePath = $request->string('native_path')->toString();

        if (! file_exists($nativePath)) {
            return response()->json(['error' => 'File not found on device.'], 422);
        }

        $contents = file_get_contents($nativePath);

        if ($contents === false) {
            return response()->json(['error' => 'Could not read file.'], 422);
        }

        $filename = 'recipes/temp/'.Str::uuid().'.jpg';
        Storage::disk('public')->put($filename, $contents);

        return response()->json([
            'stored_path' => $filename,
            'url' => Storage::disk('public')->url($filename),
        ]);
    }
}
