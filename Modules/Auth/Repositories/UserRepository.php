<?php

namespace Modules\Auth\Repositories;

use Modules\Auth\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class UserRepository implements UserRepositoryInterface
{
    public function all(array $filters = []): Collection
    {
        return $this->queryBuilder($filters)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->queryBuilder($filters)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function find(string $id): ?User
    {
        return User::find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return User::findOrFail($id)->update($data);
    }

    public function delete(int $id): bool
    {
        return User::findOrFail($id)->delete();
    }

    public function getStats(): array
    {
        return Cache::remember('user_stats_' . now()->format('Y-m-d_H'), now()->addHour(), function () {
            return [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'pending_users' => User::where('status', 'pending')->count(),
                'paid_users' => User::where('plan', '!=', 'basic')->count(),
                'total_users_change' => $this->calculatePercentageChange('total'),
                'active_users_change' => $this->calculatePercentageChange('active'),
                'pending_users_change' => $this->calculatePercentageChange('pending'),
                'paid_users_change' => $this->calculatePercentageChange('paid'),
            ];
        });
    }

    protected function calculatePercentageChange(string $type): float
    {
        $period = now()->subWeek();

        $currentCount = match ($type) {
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'pending' => User::where('status', 'pending')->count(),
            'paid' => User::where('plan', '!=', 'basic')->count(),
            default => 0,
        };

        $previousCount = match ($type) {
            'total' => User::where('created_at', '<', $period)->count(),
            'active' => User::where('status', 'active')
                ->where('created_at', '<', $period)
                ->count(),
            'pending' => User::where('status', 'pending')
                ->where('created_at', '<', $period)
                ->count(),
            'paid' => User::where('plan', '!=', 'basic')
                ->where('created_at', '<', $period)
                ->count(),
            default => 0,
        };

        if ($previousCount === 0) {
            return 0;
        }

        return round((($currentCount - $previousCount) / $previousCount) * 100, 2);
    }

    public function getRoles(): array
    {
        return [
            'admin' => 'Admin',
            'author' => 'Author',
            'editor' => 'Editor',
            'maintainer' => 'Maintainer',
            'subscriber' => 'Subscriber',
        ];
    }

    public function getPlans(): array
    {
        return [
            'basic' => 'Basic',
            'company' => 'Company',
            'enterprise' => 'Enterprise',
            'team' => 'Team',
        ];
    }

    public function getStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    protected function queryBuilder(array $filters = [])
    {
        $query = User::query();

        if (!empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'ilike', '%' . $filters['q'] . '%')
                    ->orWhere('email', 'ilike', '%' . $filters['q'] . '%');
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['plan'])) {
            $query->where('plan', $filters['plan']);
        }

        if (!empty($filters['sortBy']) && !empty($filters['orderBy'])) {
            $query->orderBy($filters['sortBy'], $filters['orderBy']);
        } else {
            // Default ordering if no sort specified
            $query->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc');
        }

        return $query;
    }
}
