<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicStorageController extends Controller
{
    public function show(string $path): StreamedResponse
    {
        $normalizedPath = trim($path, '/');

        // Prevent path traversal and empty path access.
        abort_if($normalizedPath === '' || str_contains($normalizedPath, '..'), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($normalizedPath), 404);

        $headers = [
            'Cache-Control' => 'public, max-age=3600',
        ];

        $mimeType = $disk->mimeType($normalizedPath);
        if (is_string($mimeType) && $mimeType !== '') {
            $headers['Content-Type'] = $mimeType;
        }

        return $disk->response($normalizedPath, null, $headers);
    }
}
