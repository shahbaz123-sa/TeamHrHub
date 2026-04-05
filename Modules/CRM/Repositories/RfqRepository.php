<?php

namespace Modules\CRM\Repositories;

use Modules\CRM\Models\Rfq;
use Modules\CRM\Models\FormSubmission;
use Modules\CRM\Traits\File\FileManager;
use Illuminate\Support\Facades\Notification;
use Modules\CRM\Contracts\RfqRepositoryInterface;
use Modules\CRM\Notifications\Rfq\StatusUpdatedNotification;

class RfqRepository implements RfqRepositoryInterface
{
    use FileManager;

    protected $model;

    public function __construct(Rfq $model)
    {
        $this->model = $model;
    }

    public function paginate(array $filters = [])
    {
        return $this->model
            ->with(['user', 'company', 'item'])
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->where('reference_no', 'ilike', '%' . $filters['q'] . '%')
                    ->orWhereHas('user', function ($q) use ($filters) {
                        $q->where('email', 'ilike', '%' . $filters['q'] . '%');
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
        return $this->model->with(['user', 'item.product'])->findOrFail($id);
    }

    public function getWidgetCounts()
    {
        $clients = app(CustomerRepository::class)->getB2bCustomerCount();
        $rfq = $this->model->count();
        $rfqToOrder = $this->model->whereIn('status', ['completed'])->count();
        $pending = $this->model->where('status', 'pending')->count();

        if (request()->input('for') === 'non-existing-clients') {
            $rfq = FormSubmission::where('form_type', 'rfq')->count();
        }

        return [
            'clients' => $clients,
            'rfq' => $rfq,
            'rfq_to_order' => $rfqToOrder,
            'pending' => $pending,
        ];
    }

    public function assignManager(int $id, int $userId)
    {
        $rfq = $this->model->findOrFail($id);
        $rfq->assigned_to = $userId;
        $rfq->save();

        return $rfq->fresh();
    }

    public function createQuotation(int $id, array $data)
    {
        $rfq = $this->model->findOrFail($id);

        if (isset($data['invoice'])) {
            $fileName = $data['invoice']->getClientOriginalName();
            $data['invoice'] = $this->uploadFile(
                $data['invoice'],
                "rfqs/{$rfq->reference_no}/quotations",
                $fileName
            );
        }

        $rfq->quotation()->create($data);
        $rfq->status = 'quotation_sent';
        $rfq->save();

        $rfq = $rfq->fresh();

        Notification::route('webhook', null)->notify(new StatusUpdatedNotification($rfq));

        return $rfq;
    }
}
