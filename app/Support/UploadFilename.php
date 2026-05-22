<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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

        // Ensure the target directory exists on the public disk (best-effort).
        try {
            $publicRoot = config('filesystems.disks.public.root');
            if ($publicRoot) {
                $target = rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim(rtrim($directory, '/'), DIRECTORY_SEPARATOR);
                File::ensureDirectoryExists($target);
            } else {
                $diskPath = $disk->path(rtrim($directory, '/'));
                File::ensureDirectoryExists($diskPath);
            }
        } catch (\Throwable $e) {
            // ignore; directory creation is best-effort for non-local disks
        }

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
