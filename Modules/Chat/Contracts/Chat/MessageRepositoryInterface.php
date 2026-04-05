<?php

namespace Modules\Chat\Contracts\Chat;

use Illuminate\Http\UploadedFile;

interface MessageRepositoryInterface
{
    public function paginate(array $filters = []);
    public function create(array $data);
    public function uploadAttachment(UploadedFile $file, array $data);
}
