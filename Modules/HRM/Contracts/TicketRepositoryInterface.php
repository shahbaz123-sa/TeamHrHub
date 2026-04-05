<?php

namespace Modules\HRM\Contracts;

interface TicketRepositoryInterface
{
    public function paginate($filters = []);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
