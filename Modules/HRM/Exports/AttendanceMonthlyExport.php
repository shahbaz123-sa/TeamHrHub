<?php

namespace Modules\HRM\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceMonthlyExport implements FromArray, WithHeadings, WithStyles
{
    protected array $rows;
    protected array $filters;

    public function __construct(array $rows, array $filters = [])
    {
        $this->rows = $rows;
        $this->filters = $filters;
    }

    /**
     * Format minutes into "Xh Ym" string
     */
    protected function formatMinutes(?int $minutes): string
    {
        $minutes = $minutes ?? 0;
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%dh %dm', $h, $m);
    }

    public function array(): array
    {
        // Normalize rows and ensure 0 for empty counts
        return array_map(function ($r) {
            return [
                $r['name'] ?? '-',
                $r['employee_code'] ?? '',
                $r['department'] ?? '-',
                $r['designation'] ?? '-',
                (int)($r['total_working_days'] ?? 0),
                (int)($r['present'] ?? 0),
                (int)($r['wfh'] ?? 0),
                (int)($r['short_leave'] ?? 0),
                (int)($r['half_leave'] ?? ($r['half_day'] ?? 0)),
                (int)($r['total_present'] ?? 0),
                (int)($r['leave'] ?? 0),
                (int)($r['absent'] ?? 0),
                (int)($r['late_arrivals'] ?? 0),
                (int)($r['not_marked'] ?? 0),
                // Half day original (if present)
                (int)($r['half_day'] ?? 0),
                // New columns: Allocated Hours, Worked Hours (formatted)
                $this->formatMinutes(isset($r['allocated_minutes']) ? (int)$r['allocated_minutes'] : 0),
                $this->formatMinutes(isset($r['worked_minutes']) ? (int)$r['worked_minutes'] : 0),
            ];
        }, $this->rows ?? []);
    }

    public function headings(): array
    {
        return [
            'Employee Name', 'Code', 'Department', 'Designation', 'Total Working Days', 'On Time', 'WFH', 'Short Leave', 'Half Leave', 'Total Present', 'Leave', 'Absent', 'Late Arrivals', 'Didn\'t Mark', 'Half Day', 'Allocated Hours', 'Worked Hours'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = count($this->rows) + 1; // +1 for header row

        // Header style: bold with orange tint matching PDF
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FADAD3'], // light orange
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Borders for all cells - set normal black thin borders
        $sheet->getStyle('A1:Q' . $rowCount)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(24); // Employee Name
        $sheet->getColumnDimension('B')->setWidth(14); // Code
        $sheet->getColumnDimension('C')->setWidth(20); // Department
        $sheet->getColumnDimension('D')->setWidth(18); // Designation
        $sheet->getColumnDimension('E')->setWidth(18); // Total Working Days
        $sheet->getColumnDimension('F')->setWidth(10); // Present
        $sheet->getColumnDimension('G')->setWidth(10); // Leave
        $sheet->getColumnDimension('H')->setWidth(10); // Absent
        $sheet->getColumnDimension('I')->setWidth(10); // WFH
        $sheet->getColumnDimension('J')->setWidth(12); // Late Arrivals
        $sheet->getColumnDimension('K')->setWidth(14); // Didn't Mark
        $sheet->getColumnDimension('L')->setWidth(10); // Half Day
        $sheet->getColumnDimension('M')->setWidth(10); // Half Leave
        $sheet->getColumnDimension('N')->setWidth(10); // Short Leave
        $sheet->getColumnDimension('O')->setWidth(16); // Allocated Hours
        $sheet->getColumnDimension('P')->setWidth(16);
        $sheet->getColumnDimension('Q')->setWidth(16); // Worked Hours

        // Align numeric/status columns center (E..N)
        $sheet->getStyle('E2:N' . $rowCount)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Align hours columns center as well (O..P)
        $sheet->getStyle('O2:Q' . $rowCount)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Tint status columns to match PDF colors for data rows (from row 2)
        if ($rowCount > 1) {
            // Present: light green
            $sheet->getStyle('F2:F' . $rowCount)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D6FFE6');
            // Leave: light cyan
            $sheet->getStyle('G2:G' . $rowCount)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DFF3F7');
            $sheet->getStyle('H2:H' . $rowCount)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DFF3F7');
            $sheet->getStyle('I2:I' . $rowCount)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DFF3F7');
            // Absent: light red
            $sheet->getStyle('J2:J' . $rowCount)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFCCCC');
        }

        return [];
    }
}

