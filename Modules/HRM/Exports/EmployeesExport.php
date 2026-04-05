<?php

namespace Modules\HRM\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class EmployeesExport implements FromView, WithEvents
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('hrm::employee_rules.excel-export', $this->data);
    }

    public function registerEvents(): array
    {
        $employees = collect($this->data['employees'] ?? []);

        return [
            AfterSheet::class => function (AfterSheet $event) use ($employees) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();
                $fullRange  = "A1:{$highestCol}{$highestRow}";

                $sheet->getStyle($fullRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // Column widths tuned for the current export structure
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(22);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(24);
                $sheet->getColumnDimension('E')->setWidth(20);
                $sheet->getColumnDimension('F')->setWidth(24);
                $sheet->getColumnDimension('G')->setWidth(22);
                $sheet->getColumnDimension('H')->setWidth(18);
                $sheet->getColumnDimension('I')->setWidth(28);
                $sheet->getColumnDimension('J')->setWidth(28);
                $sheet->getColumnDimension('K')->setWidth(18);
                $sheet->getColumnDimension('L')->setWidth(18);

                $showTerminationDate = (int) data_get($this->data, 'filters.employment_status_id') != 1;
                if ($showTerminationDate) {
                    $sheet->getColumnDimension('M')->setWidth(20);
                }

                if ($highestRow >= 2) {
                    $cnicColumn = $showTerminationDate ? 'H' : 'G';
                    $cnicRange = sprintf('%s2:%s%d', $cnicColumn, $cnicColumn, $highestRow);
                    $sheet->getStyle($cnicRange)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_TEXT);

                    $rowIndex = 2;
                    foreach ($employees as $employee) {
                        $cnicValue = is_array($employee)
                            ? ($employee['cnic'] ?? null)
                            : ($employee->cnic ?? null);

                        if ($cnicValue !== null && $cnicValue !== '') {
                            $sheet->setCellValueExplicit(
                                "{$cnicColumn}{$rowIndex}",
                                (string) $cnicValue,
                                DataType::TYPE_STRING
                            );
                        }

                        $rowIndex++;
                    }
                }

                for ($r = 1; $r <= $highestRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(-1);
                }
            }
        ];
    }
}
