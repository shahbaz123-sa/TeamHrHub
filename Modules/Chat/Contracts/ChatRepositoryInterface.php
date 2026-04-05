<?php

namespace Modules\Chat\Contracts;

interface ChatRepositoryInterface
{
    public function paginate(array $filters = []);
}
