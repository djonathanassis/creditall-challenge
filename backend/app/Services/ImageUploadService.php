<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    public function upload(UploadedFile $file, string $directory = 'products'): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $fullDirectory = $directory . '/' . date('Y/m');

        return $file->storeAs($fullDirectory, $filename, 'public');
    }

    public function delete(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function getUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return asset('storage/' . $path);
    }

    public function validateImage(UploadedFile $file): bool
    {
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $allowedTypes, true)) {
            return false;
        }

        if ($file->getSize() > 2048 * 1024) {
            return false;
        }

        return true;
    }
}
