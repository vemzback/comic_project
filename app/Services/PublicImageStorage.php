<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PublicImageStorage
{
    public function store(
        ?UploadedFile $file,
        string $directory,
        string $validationField,
        ?string $fallback = null,
    ): ?string {
        if (! $file) {
            return filled($fallback) ? $fallback : null;
        }

        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                $validationField => ['The uploaded file is invalid.'],
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = 'media_'.time().'_'.bin2hex(random_bytes(4)).'.'.$extension;

        return $file->storeAs($directory, $filename, 'public');
    }

    public function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
