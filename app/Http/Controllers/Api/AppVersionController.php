<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppVersionController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        if (! Storage::exists('releases/latest.json')) {
            return response()->json(['message' => 'No release found.'], 404);
        }

        $release = json_decode(Storage::get('releases/latest.json'), true);

        $downloadUrl = URL::temporarySignedRoute(
            'api.app.download',
            now()->addHour(),
            ['filename' => $release['filename']],
        );

        return response()->json([
            'version' => $release['version'],
            'version_code' => $release['version_code'],
            'changelog' => $release['changelog'],
            'download_url' => $downloadUrl,
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $filename = $request->string('filename')->toString();
        $path = 'releases/'.$filename;

        if (! Storage::exists($path)) {
            abort(404);
        }

        return Storage::download($path, $filename, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
