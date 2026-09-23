<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PublicImage
{
    /**
     * @return list<string>
     */
    public static function rules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
        ];
    }

    public static function faviconRules(): array
    {
        return ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,webp,svg', 'max:1024'];
    }

    public static function store(Request $request, string $field, string $directory, ?string $current = null): ?string
    {
        if (! $request->hasFile($field)) {
            return $current;
        }

        /** @var UploadedFile $file */
        $file = $request->file($field);

        if ($current) {
            static::delete($current);
        }

        return $file->store($directory, 'public');
    }

    public static function url(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }

    public static function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
