<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait MediaHandler
{
    public static function upload($fileValidated, $path)
    {
        $extension = $fileValidated->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        return $fileValidated->storeAs($path, $fileName, 'public');
    }

    public static function updateMedia($media, $path, $storagePath = null, $disk = 'public')
    {
        if ($storagePath && Storage::disk($disk)->exists($storagePath)) {
            Storage::disk($disk)->delete($storagePath);
        }

        return self::upload($media, $path);
    }

    public static function deleteMedia($storagePath, $disk = 'public')
    {
        if ($storagePath && Storage::disk($disk)->exists($storagePath)) {
            Storage::disk($disk)->delete($storagePath);
        }
    }
}