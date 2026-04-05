<?php

namespace Modules\HRM\Repositories;

use Illuminate\Http\UploadedFile;
use Modules\HRM\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Modules\HRM\Contracts\TicketRepositoryInterface;
use Modules\HRM\Traits\File\FileManager;

class TicketRepository implements TicketRepositoryInterface
{
    use FileManager;

    public function paginate($filters = [])
    {
        $user = auth()->user();
        $userId = optional($user->employee)->id;

        $query = Ticket::with(['employee', 'department', 'poc', 'category', 'employee.user'])
            ->when(!empty($filters['q']), function ($q) use ($filters) {
                $q->where('ticket_code', 'ilike', '%' . $filters['q'] . '%')
                    ->where('description', 'ilike', '%' . $filters['q'] . '%')
                    ->orWhereHas('employee', function ($q) use ($filters) {
                        $q->where('name', 'ilike', '%' . $filters['q'] . '%');
                    });
            })
            ->when(!empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(!empty($filters['department_id']), function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            })
            ->when((!empty($filters['start_date']) && !empty($filters['end_date'])), function ($q) use ($filters) {
                $q->whereBetween('start_date', [$filters['start_date'], $filters['end_date']]);
            })

            ->when(auth()->user()->onlyEmployee(), function ($q) use ($userId) {
                $q->where('employee_id', auth()->user()->employee->id)
                    ->orWhere('poc_id', $userId);
            })
            ->when(auth()->user()->hasRole('Manager') &&  !auth()->user()->hasRole(['Hr']), function ($q) use ($userId) {
                $q->whereAny(['employee_id', 'reporting_to'], '=', $userId)
                    ->orWhere('poc_id', $userId);
            });

        return $query
            ->latest('created_at')
            ->paginate($filters['per_page'] ?? 10);
    }

    public function find($id)
    {
        return Ticket::with(['employee', 'department', 'poc', 'category'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $user = auth()->user();

            $attachment = isset($data['attachment']) && $data['attachment'] instanceof UploadedFile ? $data['attachment'] : null;
            $ticketId = $this->generateSequentialTicketId();




            if (filled($attachment)) {
                if (!$user->employee) {
                    $employeCode = config('constants.supder_admin_code');
                } else {
                    $employeCode = $user->employee->employee_code;
                }
                $attachment = $this->uploadFile($data['attachment'], "tickets/{$employeCode}/" . $ticketId);
            }

            $data['attachment'] = $attachment;
            $data['ticket_code'] = $ticketId;
            $data['start_date'] = now();

            return Ticket::create($data);
        });
    }

    /**
     * Generate sequential ticket ID
     * Format: TKT-YYYY-NNNNNN (e.g., TKT-2024-000001)
     */
    private function generateSequentialTicketId()
    {
        $currentYear = date('Y');
        $prefix = "TKT-{$currentYear}-";

        // Get the last ticket code for current year
        $lastTicket = Ticket::where('ticket_code', 'ilike', $prefix . '%')
            ->orderBy('ticket_code', 'desc')
            ->first();

        if ($lastTicket) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastTicket->ticket_code, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            // First ticket of the year
            $nextNumber = 1;
        }

        // Format with leading zeros (6 digits)
        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {

            $user = auth()->user();

            $ticket = Ticket::findOrFail($id);
            $attachment = isset($data['attachment']) && $data['attachment'] instanceof UploadedFile ? $data['attachment'] : null;

            if (isset($data['attachment'])) {
                if (!$user->employee) {
                    $employeCode = config('constants.supder_admin_code');
                } else {
                    $employeCode = $user->employee->employee_code;
                }
                if ($ticket->attachment) {
                    $this->deleteFile($ticket->attachment);
                }

                if (filled($attachment)) {
                    $attachment = $this->uploadFile($data['attachment'], "tickets/{$employeCode}/" . $ticket->ticket_code);
                }
            }

            $data['attachment'] = $attachment;
            $ticket->update($data);
            return $ticket->fresh(['employee', 'department', 'poc', 'category']);
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $ticket = Ticket::findOrFail($id);
            if ($ticket->attachment) {
                $this->deleteFile($ticket->attachment);
            }
            return (bool) $ticket->delete();
        });
    }
}
