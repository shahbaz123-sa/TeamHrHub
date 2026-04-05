<?php

namespace Modules\Chat\Repositories;

use Modules\Chat\Contracts\ChatRepositoryInterface;
use Modules\Chat\Models\Chat\Message\User;
use Modules\Chat\Models\Chat\Session;
use Modules\HRM\Repositories\EmployeeRepository;

class ChatRepository implements ChatRepositoryInterface
{
    public function paginate(array $filters = [])
    {
        $empId = auth()->user()->employee->id ?? null;

        if ($empId && $empId > 0 && app(EmployeeRepository::class)->isRfqManager(auth()->user())) {
            Session::whereDoesntHave('participants', function ($query) use ($empId) {
                $query->where('userId', $empId);
            })->each(function ($session) use ($empId) {
                $session->participants()->create([
                    'userId' => $empId,
                    'userType' => 'MANAGER',
                    'joinedAt' => now(),
                ]);
            });
        }

        return User::with(['profile', 'company'])
            ->whereHas('sentMessages', function ($query) {
                $query->whereHas('session', function ($sessionQuery) {
                    $sessionQuery->whereHas('participants');
                });
            })
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereHas('profile', function ($query) use ($filters) {
                    $query->whereRaw('"first_name" || \' \' || "last_name" ILIKE ?', ["%{$filters['q']}%"]);
                })
                    ->orWhereHas('company', function ($query) use ($filters) {
                        $query->whereAny(['company_name'], 'ilike', "%{$filters['q']}%");
                    });
            })
            ->orderBy(
                data_get($filters, 'sort_by', 'updated_at'),
                data_get($filters, 'order_by', 'desc')
            )
            ->paginate(data_get($filters, 'per_page', 10));
    }
}
