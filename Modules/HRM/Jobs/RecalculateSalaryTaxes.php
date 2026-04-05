<?php

namespace Modules\HRM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\HRM\Contracts\SalaryRepositoryInterface;
use Modules\HRM\Models\EmployeeSalary;

class RecalculateSalaryTaxes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly ?int $taxSlabId = null,
        private readonly ?float $minAnnualAmount = null,
        private readonly ?float $maxAnnualAmount = null,
    ) {
    }

    public function handle(SalaryRepositoryInterface $salaryRepository): void
    {
        $monthlyMin = $this->minAnnualAmount !== null ? round($this->minAnnualAmount / 12, 2) : null;
        $monthlyMax = $this->maxAnnualAmount !== null ? round($this->maxAnnualAmount / 12, 2) : null;

        $query = EmployeeSalary::query()
            ->where('is_tax_applicable', true)
            ->when($this->taxSlabId, fn ($q) => $q->where('tax_slab_id', $this->taxSlabId))
            ->when($monthlyMin !== null, fn ($q) => $q->where('amount', '>=', $monthlyMin))
            ->when($monthlyMax !== null, fn ($q) => $q->where('amount', '<=', $monthlyMax))
            ->orderBy('id');

        $query->chunkById(200, function ($salaries) use ($salaryRepository) {
            foreach ($salaries as $salary) {
                $salaryRepository->refreshTaxForSalary($salary);
            }
        });
    }
}

