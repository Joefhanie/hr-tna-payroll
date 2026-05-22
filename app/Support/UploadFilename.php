<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadFilename
{
    public static function build(UploadedFile $file, ?string $displayName = null): string
    {
        $originalName = trim((string) ($displayName ?: $file->getClientOriginalName()));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $safeBaseName = Str::slug($baseName) ?: 'file';

        return now()->format('Ymd_Hi') . '_' . $safeBaseName . '.' . $extension;
    }
}