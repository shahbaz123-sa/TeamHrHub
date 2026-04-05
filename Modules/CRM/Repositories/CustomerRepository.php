<?php

namespace Modules\CRM\Repositories;

use Exception;
use Modules\CRM\Models\Customer;
use Illuminate\Support\Facades\Storage;
use Modules\CRM\Contracts\CustomerRepositoryInterface;

class CustomerRepository implements CustomerRepositoryInterface
{
  protected $model;

  public function __construct(Customer $model)
  {
    $this->model = $model;
  }

  public function paginate(array $filters = [])
  {
    return $this->model
      ->when(isset($filters['q']), function ($query) use ($filters) {
        $query->whereAny(['username', 'email', 'phone_number'], 'ilike', "%{$filters['q']}%");
      })
      ->when(isset($filters['status']), function ($query) use ($filters) {
        if (data_get($filters, 'type') == 'B2B') {
          $query->whereHas('company', fn($companyQuery) => $companyQuery->where('company.status', $filters['status']));
        } else {
          $query->where('status', $filters['status']);
        }
      })
      ->when(isset($filters['type']), function ($query) use ($filters) {
        $query->where('type', $filters['type']);

        if ($filters['type'] === 'B2B') {
          $query->whereHas('company');
        }
      })
      ->orderBy(
        data_get($filters, 'sort_by', 'id'),
        data_get($filters, 'order', 'desc')
      )
      ->paginate($filters['per_page'] ?? 10);
  }

  public function find(int $id)
  {
    return $this->model
      ->with(['profile', 'shippingAddresses', 'company'])
      ->findOrFail($id);
  }

  public function create(array $data)
  {
    // 
  }

  public function update(int $id, array $data)
  {
    // 
  }

  public function updateStatus(int $id, string $status)
  {
    $model = $this->model->findOrFail($id);
    $model->status = $status;
    $model->save();
    return $model;
  }

  public function updateCompanyStatus(int $id, string $status)
  {
    $model = $this->model->findOrFail($id);

    if ($model->company) {
      $model->company->update([
        'status' => $status
      ]);
    }

    return $model;
  }

  public function uploadCompanyDocument(int $id, $file, string $documentType)
  {
    $model = $this->model->with('company')->findOrFail($id);

    throw_if(!$model->company, new Exception('Company not found for this customer'));

    $company = $model->company;
    $companyName = str_replace(" ", "_", $company->company_name);

    $path = "{$companyName}/docs";
    $uploadedPath = $this->uploadCompanyFile($file, $path);

    $doc = $company->documents()->create([
      'document_type' => $documentType,
      'document_url' => $uploadedPath,
    ]);

    return $doc;
  }

  public function deleteCompanyDocument(int $id, int $documentId)
  {
    $model = $this->model->with('company')->findOrFail($id);

    throw_if(!$model->company, new Exception('Company not found for this customer'));

    $company = $model->company;

    $doc = $company->documents()->findOrFail($documentId);

    if ($doc->document_url) {
      $this->deleteFile($doc->document_url);
    }

    $company->documents()->where('id', $documentId)->delete();

    return true;
  }

  public function delete(int $id)
  {
    // 
  }

  public function getWidgetCounts()
  {
    $type = request('type', 'B2B');

    $customers = $this->model
      ->where('type', $type)
      ->when($type === 'B2B', fn($query) => $query->whereHas('company'))
      ->when($type === 'B2C', fn($query) => $query)
      ->count();

    $pending = $this->model
      ->where('type', $type)
      ->when($type === 'B2B', fn($query) => $query->whereHas('company', fn($companyQuery) => $companyQuery->where('company.status', 'PENDING')))
      ->when($type === 'B2C', fn($query) => $query->where('status', 'PENDING')->orWhereNull('status'))
      ->count();

    $completed = $this->model->where('type', $type)->where('status', 'COMPLETED')->count();
    $deleted = $this->model->where('type', $type)->where('status', 'DELETED')->count();

    $approved = $this->model
      ->where('type', $type)
      ->whereHas('company', fn($companyQuery) => $companyQuery->where('company.status', 'APPROVED'))
      ->count();

    $rejected = $this->model
      ->where('type', $type)
      ->whereHas('company', fn($companyQuery) => $companyQuery->where('company.status', 'REJECTED'))
      ->count();

    return [
      'customers' => $customers,
      'pending' => $pending,
      'completed' => $completed,
      'deleted' => $deleted,
      'approved' => $approved,
      'rejected' => $rejected,
    ];
  }

  public function getB2bCustomerCount()
  {
    return $this->model->where('type', 'B2B')->count();
  }

  public function uploadCompanyFile($file, string $path)
  {
    $name = $file->getClientOriginalName();

    $fileNameInfo = pathinfo($name);
    throw_if(!isset($fileNameInfo['extension']) || !isset($fileNameInfo['basename']), 'File name is invalid');

    $name = date('Y-m-d_H-i-s') . "-" . pathinfo($name, PATHINFO_BASENAME);
    $path = 'web/B2BCompanies/' . ltrim($path, '/');

    return Storage::disk('s3')->putFileAs($path, $file, $name);
  }

  public function deleteFile(string $path): void
  {
    if (Storage::disk('s3')->exists($path)) {
      // Need to keep the track
      // Storage::disk('s3')->delete($path);
    }
  }
}
