<?php

namespace Modules\HRM\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendancesExport implements FromView, WithEvents
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
    public function view(): View
    {
        return view('hrm::attendance.excel-export', $this->data);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn(); // should be J
                $fullRange  = "A1:{$highestCol}{$highestRow}";

                // 1) Global alignment (this fixes "bottom right" issue)
                $sheet->getStyle($fullRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // 2) Column widths (prevents ugly header wrapping + misalignment)
                $sheet->getColumnDimension('A')->setWidth(6);   // SR #
                $sheet->getColumnDimension('B')->setWidth(22);  // Department
                $sheet->getColumnDimension('C')->setWidth(10);  // Total
                $sheet->getColumnDimension('D')->setWidth(12);  // Present
                $sheet->getColumnDimension('E')->setWidth(12);  // Absent
                $sheet->getColumnDimension('F')->setWidth(12);  // Leave
                $sheet->getColumnDimension('G')->setWidth(10);  // WFH
                $sheet->getColumnDimension('H')->setWidth(14);  // Late / Late Arrival
                $sheet->getColumnDimension('I')->setWidth(12);  // Half Day
                $sheet->getColumnDimension('J')->setWidth(14);  // Not Marked

                // Auto row height for wrap text
                for ($r = 1; $r <= $highestRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(-1);
                }

                // Helpers
                $fill = function(string $range, string $hex) use ($sheet) {
                    $sheet->getStyle($range)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FF' . strtoupper(ltrim($hex, '#')));
                };
                $font = function(string $range, bool $bold = true, ?string $hex = null) use ($sheet) {
                    $f = $sheet->getStyle($range)->getFont();
                    $f->setBold($bold);
                    if ($hex) $f->getColor()->setARGB('FF' . strtoupper(ltrim($hex, '#')));
                };

                // 3) Detect + style key rows by their content
                for ($r = 1; $r <= $highestRow; $r++) {
                    $a = trim((string)$sheet->getCell("A{$r}")->getValue());

                    // DATE ROW (merge A:J + color)
                    if (str_starts_with($a, 'Date:')) {
                        $range = "A{$r}:J{$r}";
                        $sheet->mergeCells($range);                       // <-- IMPORTANT
                        $fill($range, '#D55D36');
                        $font($range, true, '#FFFFFF');
                        $sheet->getStyle($range)->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                            ->setVertical(Alignment::VERTICAL_CENTER);
                        continue;
                    }

                    // SUMMARY HEADER
                    if ($a === 'Total Employees') {
                        $range = "A{$r}:J{$r}";
                        $fill($range, '#FADAD3');
                        $font($range, true);
                        continue;
                    }

                    // SUMMARY VALUES (row after Total Employees)
                    $prevA = trim((string)$sheet->getCell("A" . ($r - 1))->getValue());
                    if ($prevA === 'Total Employees') {
                        $range = "A{$r}:J{$r}";
                        $fill($range, '#F7B7A1');
                        $font($range, true);
                        continue;
                    }

                    // DEPT TABLE HEADER
                    if ($a === 'SR #') {
                        $range = "A{$r}:J{$r}";
                        $fill($range, '#FADAD3');
                        $font($range, true);
                        continue;
                    }
                }
            },
        ];
    }
}
