<?php

namespace Modules\CRM\Contracts;

interface FormSubmissionRepositoryInterface
{
    public function paginate(array $filters = []);
    public function find(int $id);
}
