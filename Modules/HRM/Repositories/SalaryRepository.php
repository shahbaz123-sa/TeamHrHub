<?php

namespace Modules\HRM\Repositories;

use Modules\HRM\Models\EmployeeSalary;
use Modules\HRM\Models\EmployeeSalaryHistory;
use Modules\HRM\Models\TaxSlab;
use Modules\HRM\Contracts\SalaryRepositoryInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalaryRepository implements SalaryRepositoryInterface
{
    public function paginate(array $filters = []): Paginator
    {
        $query = EmployeeSalary::with(['employee.department', 'employee.designation', 'employee.branch', 'payslipType', 'taxSlab']);

        // Apply filters
        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['effective_date_from'])) {
            $query->where('effective_date', '>=', $filters['effective_date_from']);
        }

        if (isset($filters['effective_date_to'])) {
            $query->where('effective_date', '<=', $filters['effective_date_to']);
        }

        if (isset($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->whereHas('employee', function ($q) use ($searchTerm) {
                $q->where('name', 'ilike', "%{$searchTerm}%")
                  ->orWhere('employee_code', 'ilike', "%{$searchTerm}%");
            });
        }

        // Apply sorting
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $orderBy = $filters['orderBy'] ?? 'desc';
        $query->orderBy($sortBy, $orderBy);

        $perPage = $filters['per_page'] ?? 10;
        if ($perPage == -1) {
            $perPage = 1000;
        }

        return $query->paginate($perPage);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->preparePayload($data);
            $salary = EmployeeSalary::create($payload);
            $this->logHistory($salary, 'created', $payload);

            return $salary->load(['employee.department', 'employee.designation', 'employee.branch', 'payslipType', 'taxSlab']);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $salary = EmployeeSalary::findOrFail($id);
            $payload = $this->preparePayload(array_merge($salary->toArray(), $data), $salary);
            $salary->update($payload);
            $salary->refresh();

            $this->logHistory($salary, 'updated', $payload);

            return $salary->load(['employee.department', 'employee.designation', 'employee.branch', 'payslipType', 'taxSlab']);
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $salary = EmployeeSalary::findOrFail($id);
            $snapshot = $salary->toArray();
            $deleted = $salary->delete();
            $this->logHistory($salary, 'deleted', $snapshot);

            return $deleted;
        });
    }

    public function restore($id)
    {
        throw new \Exception('Restore functionality not available for salary records');
    }

    public function forceDelete($id)
    {
        return $this->delete($id);
    }

    public function getByEmployee($employeeId)
    {
        return EmployeeSalary::where('employee_id', $employeeId)
            ->with(['employee.department', 'employee.designation', 'employee.branch', 'payslipType', 'taxSlab'])
            ->orderBy('effective_date', 'desc')
            ->get();
    }

    public function getCurrentSalary($employeeId)
    {
        return EmployeeSalary::where('employee_id', $employeeId)
            ->where('status', 1)
            ->with(['employee.department', 'employee.designation', 'employee.branch', 'payslipType', 'taxSlab'])
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    public function getHistoryByEmployee($employeeId)
    {
        return EmployeeSalaryHistory::where('employee_id', $employeeId)
            ->with(['taxSlab', 'performedBy'])
            ->orderByDesc('created_at')
            ->get();
    }

    private function preparePayload(array $data, ?EmployeeSalary $current = null): array
    {
        $payload = [
            'employee_id' => $data['employee_id'] ?? $current?->employee_id,
            'payslip_type_id' => $data['payslip_type_id'] ?? $current?->payslip_type_id,
            'amount' => $data['amount'] ?? $current?->amount,
            'effective_date' => $data['effective_date'] ?? $current?->effective_date,
            'status' => $data['status'] ?? $current?->status ?? true,
            'is_tax_applicable' => (bool)($data['is_tax_applicable'] ?? $current?->is_tax_applicable ?? false),
        ];

        if ($payload['is_tax_applicable']) {
            $slab = $this->resolveTaxSlab($payload['amount'], $data['tax_slab_id'] ?? $current?->tax_slab_id);
            $payload['tax_slab_id'] = $slab?->id;
            $payload['tax_amount'] = $this->calculateTaxAmount($payload['amount'], $slab);
        } else {
            $payload['tax_slab_id'] = null;
            $payload['tax_amount'] = 0;
        }

        return $payload;
    }

    private function resolveTaxSlab($amount, $taxSlabId = null): ?TaxSlab
    {
        if ($taxSlabId) {
            return TaxSlab::find($taxSlabId);
        }

        $annualAmount = $this->toAnnual($amount);
        if ($annualAmount === null) {
            return null;
        }

        return TaxSlab::where('min_salary', '<=', $annualAmount)
            ->where(function ($query) use ($annualAmount) {
                $query->whereNull('max_salary')->orWhere('max_salary', '>=', $annualAmount);
            })
            ->orderBy('min_salary', 'desc')
            ->first();
    }

    public function calculateTaxAmount($amount, $taxSlab = null): float
    {
         $slab = $taxSlab instanceof TaxSlab ? $taxSlab : null;

         if (!$slab && $amount !== null) {
             $slab = $this->resolveTaxSlab($amount, $taxSlab);
         }

         if (!$slab || $amount === null) {
             return 0.0;
         }

         $annualAmount = $this->toAnnual($amount);
         if ($annualAmount === null) {
             return 0.0;
         }

         $fixedAnnual = (float)$slab->fixed_amount;
         $rate = (float)$slab->tax_rate;
         $thresholdAnnual = (float)$slab->exceeding_threshold;
         $taxable = max(0, $annualAmount - $thresholdAnnual);
         $percentAmount = $taxable * ($rate / 100);
         $annualTax = $fixedAnnual + $percentAmount;

         return round($annualTax / 12, 2);
    }

    private function toAnnual($monthlyAmount): ?float
    {
        if ($monthlyAmount === null) {
            return null;
        }

        return round((float)$monthlyAmount * 12, 2);
    }

    private function logHistory(EmployeeSalary $salary, string $action, array $payload = []): void
    {
        EmployeeSalaryHistory::create([
            'employee_id' => $salary->employee_id,
            'employee_salary_id' => $salary->id,
            'action' => $action,
            'amount' => $payload['amount'] ?? $salary->amount,
            'tax_amount' => $payload['tax_amount'] ?? $salary->tax_amount,
            'is_tax_applicable' => $payload['is_tax_applicable'] ?? $salary->is_tax_applicable,
            'tax_slab_id' => $payload['tax_slab_id'] ?? $salary->tax_slab_id,
            'effective_date' => $payload['effective_date'] ?? $salary->effective_date,
            'status' => $payload['status'] ?? $salary->status,
            'payload' => $payload,
            'performed_by' => Auth::id(),
        ]);
    }

    public function refreshTaxForSalary(EmployeeSalary $salary)
    {
        return DB::transaction(function () use ($salary) {
            $salary->refresh();
            $payload = $this->preparePayload($salary->toArray(), $salary);

            $salary->update($payload);
            $salary->refresh();

            $this->logHistory($salary, 'tax_recalculated', $payload);

            return $salary;
        });
    }
}
