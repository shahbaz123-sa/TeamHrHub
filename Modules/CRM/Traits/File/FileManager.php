<?php

namespace Modules\CRM\Traits\File;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

trait FileManager
{
    public function uploadFile($file, string $path, string $name = '')
    {
        if (blank($name)) {
            $name = $file->getClientOriginalName();
        }

        throw_if($this->pathHasFileName($path), "Exclude file name from path");

        $fileNameInfo = pathinfo($name);
        throw_if(!isset($fileNameInfo['extension']) || !isset($fileNameInfo['basename']), 'File name is invalid');

        $name =  pathinfo($name, PATHINFO_FILENAME) . "_" . date('Y-m-d_H-i-s') . "." . $fileNameInfo['extension'];
        $path = $this->generateModulePath($path);

        return Storage::disk('s3')->putFileAs($path, $file, $name);
    }

    public function fetchFromUrlAndUploadImage($url, $path, $name = '')
    {
        $response = Http::get($url);
        if (!$response->ok()) {
            return null;
        }

        $imageContent = $response->body();
        $parsedUrl = parse_url($url, PHP_URL_PATH);

        $extension = pathinfo($parsedUrl, PATHINFO_EXTENSION);
        if (!$extension) {
            return null;
        }

        $path = $this->generateModulePath($path);
        $filename = $name ?: pathinfo($parsedUrl, PATHINFO_BASENAME);
        Storage::disk('s3')->put("$path/$filename", $imageContent);

        return "$path/$filename";
    }

    public function deleteFile(string $path): void
    {
        if (Storage::disk('s3')->exists($path)) {
            // Need to keep the track
            // Storage::disk('s3')->delete($path);
        }
    }

    private function generateModulePath(string $path)
    {
        return 'crm/' . ltrim($path, '/');
    }

    private function pathHasFileName(string $path): bool
    {
        $info = pathinfo($path);
        return isset($info['extension'], $info['basename']);
    }
}
