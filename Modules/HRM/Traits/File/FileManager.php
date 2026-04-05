<?php

namespace Modules\HRM\Traits\File;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait FileManager
{
    public function uploadFile(UploadedFile $file, string $path, string $name = '')
    {
        if (blank($name)) {
            $name = $file->getClientOriginalName();
        }

        throw_if(self::pathHasFileName($path), "Exclude file name from path");

        $fileNameInfo = pathinfo($name);
        throw_if(!isset($fileNameInfo['extension']) || !isset($fileNameInfo['basename']), 'File name is invalid');

        $name =  pathinfo($name, PATHINFO_FILENAME) . "_" . date('Y-m-d_H-i-s') . "." . $fileNameInfo['extension'];
        $path = self::generateModulePath($path);

        return Storage::disk('s3')->putFileAs($path, $file, $name);
    }

    public function deleteFile(string $path): void
    {
        if (Storage::disk('s3')->exists($path)) {
            // Need to keep the track
//             Storage::disk('s3')->delete($path);
        }
    }

    private static function generateModulePath(string $path)
    {
        return 'hrm/' . ltrim($path, '/');
    }

    private static function pathHasFileName(string $path): bool
    {
        $info = pathinfo($path);
        return isset($info['extension']) && isset($info['basename']);
    }
}
