<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Modules\HRM\Exports\TaxSlabExport;
use Modules\HRM\Http\Requests\StoreTaxSlabRequest;
use Modules\HRM\Http\Requests\UpdateTaxSlabRequest;
use Modules\HRM\Http\Resources\TaxSlabResource;
use Modules\HRM\Models\TaxSlab;
use Modules\HRM\Jobs\RecalculateSalaryTaxes;

class TaxSlabController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = TaxSlab::query();
        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $slabs = $query->orderBy('min_salary')->orderBy('max_salary')->get();

        return TaxSlabResource::collection($slabs);
    }

    public function store(StoreTaxSlabRequest $request)
    {
        $slab = TaxSlab::create($request->validated());

        RecalculateSalaryTaxes::dispatch(
            null,
            (float) $slab->min_salary,
            $slab->max_salary === null ? null : (float) $slab->max_salary,
        );

        return new TaxSlabResource($slab);
    }

    public function show(TaxSlab $taxSlab)
    {
        return new TaxSlabResource($taxSlab);
    }

    public function update(UpdateTaxSlabRequest $request, TaxSlab $taxSlab)
    {
        $taxSlab->update($request->validated());

        RecalculateSalaryTaxes::dispatch($taxSlab->id);
        RecalculateSalaryTaxes::dispatch(
            null,
            (float) $taxSlab->min_salary,
            $taxSlab->max_salary === null ? null : (float) $taxSlab->max_salary,
        );

        return new TaxSlabResource($taxSlab);
    }

    public function destroy(TaxSlab $taxSlab)
    {
        $slabId = $taxSlab->id;
        $taxSlab->delete();

        RecalculateSalaryTaxes::dispatch($slabId);

        return response()->noContent();
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->getFormattedSlabs($request);

        $data = [
            'rows' => $rows,
            'filters' => ['q' => $request->get('q')],
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'total_records' => $rows->count(),
        ];

        $pdf = Pdf::loadView('hrm::payroll.tax-slab-pdf', $data)->setPaper('A4', 'landscape');

        return $pdf->download('tax_slabs_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->getFormattedSlabs($request);

        $data = [
            'rows' => $rows,
            'filters' => ['q' => $request->get('q')],
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'total_records' => $rows->count(),
        ];

        return Excel::download(new TaxSlabExport($data), 'tax_slabs_' . now()->format('Ymd_His') . '.xlsx');
    }

    private function getFormattedSlabs(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = TaxSlab::query();
        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        return $query
            ->orderBy('min_salary')
            ->orderBy('max_salary')
            ->get()
            ->map(function (TaxSlab $slab) {
                $minSalary = (float) $slab->min_salary;
                $maxSalary = $slab->max_salary === null ? null : (float) $slab->max_salary;
                $fixedAmount = (float) $slab->fixed_amount;
                $threshold = (float) $slab->exceeding_threshold;

                return [
                    'id' => $slab->id,
                    'name' => $slab->name,
                    'min_salary' => $minSalary,
                    'min_salary_monthly' => round($minSalary / 12, 2),
                    'max_salary' => $maxSalary,
                    'max_salary_monthly' => $maxSalary === null ? null : round($maxSalary / 12, 2),
                    'tax_rate' => (float) $slab->tax_rate,
                    'fixed_amount' => $fixedAmount,
                    'fixed_amount_monthly' => round($fixedAmount / 12, 2),
                    'exceeding_threshold' => $threshold,
                    'exceeding_threshold_monthly' => round($threshold / 12, 2),
                    'updated_at' => optional($slab->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
    }
}
