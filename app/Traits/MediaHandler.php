<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait MediaHandler
{
    public static function upload($fileValidated, $path)
    {
        $originalName = $fileValidated->getClientOriginalName();
        $safeName = str_replace(' ', '-', $originalName);
        $vidName = rand(1,100000) . now()->format('YmdHis') . $safeName;

        return $fileValidated->storeAs($path, $vidName, 'public');
    }

    public static function updateMedia($media, $path, $storagePath=null, $disk='public')
    {
        if ($storagePath && Storage::disk($disk)->exists($storagePath)) {
            Storage::disk($disk)->delete($storagePath);
        }

        return self::upload($media, $path);
    }

    public static function deleteMedia($storagePath, $disk='public')
    {
        if ($storagePath && Storage::disk($disk)->exists($storagePath)) {
            Storage::disk($disk)->delete($storagePath);
        }
    }
}