<?php

namespace Modules\CRM\Repositories;

use Illuminate\Support\Number;
use Modules\CRM\Models\CreditApplication;
use Modules\CRM\Contracts\CreditApplicationRepositoryInterface;
use Modules\CRM\Models\FormSubmission;

class CreditApplicationRepository implements CreditApplicationRepositoryInterface
{
    protected $model;

    public function __construct(CreditApplication $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model
            ->with(['user', 'company'])
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny(['requested_credit_limit', 'credit_reference'], 'ilike', '%' . $filters['q'] . '%')
                    ->orWhereHas('company', function ($q) use ($filters) {
                        $q->where('company_name', 'ilike', '%' . $filters['q'] . '%');
                    })
                    ->orWhereHas('category', function ($q) use ($filters) {
                        $q->where('name', 'ilike', '%' . $filters['q'] . '%');
                    });
            })
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->orderBy(
                data_get($filters, 'sort_by', 'id'),
                data_get($filters, 'order', 'desc')
            )
            ->paginate($filters['per_page'] ?? 10);
    }

    public function find(int $id)
    {
        return $this->model->with(['user', 'company', 'documents'])->findOrFail($id);
    }

    public function getWidgetCounts()
    {
        $totalRequests = $this->model->count();
        $totalApprovedCredit = $this->model->sum('approved_credit_limit');
        $totalRequestedCredit = $this->model->sum('requested_credit_limit');
        $pending = $this->model->where('status', 'PENDING')->count();

        if (request()->input('for') === 'non-existing-clients') {
            $totalRequests = FormSubmission::where('form_type', 'credit_request')->count();
        }

        return [
            'clients' => $totalRequests,
            'total_approved_credit' => Number::forHumans($totalApprovedCredit ?? 0, 2, null, true),
            'total_requested_credit' => Number::forHumans($totalRequestedCredit ?? 0, 2, null, true),
            'pending' => $pending,
        ];
    }

    public function findByCompanyId(int $companyId)
    {
        return $this->model
            ->with(['user', 'company', 'documents'])
            ->where('company_id', $companyId)
            ->orderByRaw('
                CASE
                    WHEN status = \'PENDING\' THEN 1
                    WHEN status = \'APPROVED\' THEN 2
                    WHEN status = \'REJECTED\' THEN 3
                    ELSE 4
                END ASC
            ')
            ->latest('createdAt')
            ->get();
    }

    public function approve(int $id, $approvedAmount)
    {
        $application = $this->model->findOrFail($id);
        $application->status = 'APPROVED';
        $application->approved_credit_limit = $approvedAmount;
        $application->reviewed_by = auth()->id();
        $application->reviewed_at = $application->updatedAt = now();
        $application->save();

        return $application->fresh(['user', 'company', 'documents']);
    }

    public function reject(int $id, $rejectionReason)
    {
        $application = $this->model->findOrFail($id);
        $application->status = 'REJECTED';
        $application->rejection_reason = $rejectionReason;
        $application->reviewed_by = auth()->id();
        $application->reviewed_at = $application->updatedAt = now();
        $application->save();

        return $application->fresh(['user', 'company', 'documents']);
    }

    public function bulkApprove(int $companyId)
    {
        $this->model
            ->where('company_id', $companyId)
            ->where('status', 'PENDING')
            ->each(function ($application) {
                $application->update([
                    'approved_credit_limit' => $application->requested_credit_limit,
                    'status' => 'APPROVED',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'updatedAt' => now(),
                ]);
            });

        return true;
    }

    public function bulkReject(int $companyId)
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('status', 'PENDING')
            ->update([
                'status' => 'REJECTED',
                'rejection_reason' => 'Bulk Rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'updatedAt' => now(),
            ]);
    }
}
