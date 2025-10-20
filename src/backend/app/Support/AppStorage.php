<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class AppStorage {
    public static function put(string $path, string $contents, ?string $disk = null): bool {
        return Storage::disk($disk ?? config('filesystems.default'))->put($path, $contents);
    }

    public static function get(string $path, ?string $disk = null): ?string {
        return Storage::disk($disk ?? config('filesystems.default'))->get($path);
    }

    public static function url(string $path, ?string $disk = null): string {
        $disk ??= config('filesystems.default');

        if ($disk === 'local') {
            return Storage::disk($disk)->path($path);
        }

        return Storage::disk($disk)->url($path);
    }

    public static function delete(string $path, ?string $disk = null): bool {
        return Storage::disk($disk ?? config('filesystems.default'))->delete($path);
    }

    public static function exists(string $path, ?string $disk = null): bool {
        return Storage::disk($disk ?? config('filesystems.default'))->exists($path);
    }

    public static function putFileAs(string $path, string $file, string $name, ?string $disk = null): string {
        return Storage::disk($disk ?? config('filesystems.default'))->putFileAs($path, $file, $name);
    }

    public static function temporaryUrl(string $path, \DateTimeInterface $expiration, ?string $disk = null): string {
        $disk ??= config('filesystems.default');

        if ($disk === 'local') {
            return Storage::disk($disk)->path($path);
        }

        return Storage::disk($disk)->temporaryUrl($path, $expiration);
    }

    public static function getDefaultDisk(?string $disk = null): Filesystem {
        return Storage::disk($disk ?? config('filesystems.default'));
    }
}
