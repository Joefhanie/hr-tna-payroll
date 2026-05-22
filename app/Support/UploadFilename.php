<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class UploadFilename
{
    public static function build(UploadedFile $file, ?string $displayName = null, ?string $directory = null): string
    {
        $originalName = trim((string) ($displayName ?: $file->getClientOriginalName()));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $safeBaseName = Str::slug($baseName) ?: 'file';

        $timestamp = now()->format('Ymd_His');
        $fileName = $timestamp . '_' . $safeBaseName . '.' . $extension;

        if (!$directory) {
            return $fileName;
        }

        $disk = Storage::disk('public');
        $fullPath = rtrim($directory, '/') . '/' . $fileName;

        $counter = 1;
        while ($disk->exists($fullPath)) {
            $fileName = $timestamp . '_' . $safeBaseName . '_' . $counter . '.' . $extension;
            $fullPath = rtrim($directory, '/') . '/' . $fileName;
            $counter++;
        }

        return $fileName;
    }
}
