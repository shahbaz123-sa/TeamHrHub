<?php

namespace Modules\CRM\Contracts;

interface CustomerRepositoryInterface
{
    public function paginate();
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function updateStatus(int $id, string $status);
    public function delete(int $id);
    public function getWidgetCounts();
    public function updateCompanyStatus(int $id, string $status);
    public function uploadCompanyDocument(int $id, $file, string $documentType);
    public function deleteCompanyDocument(int $id, int $documentId);
}
