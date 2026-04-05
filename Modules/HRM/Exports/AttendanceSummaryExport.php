<?php

namespace Modules\HRM\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AttendanceSummaryExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;
    protected $filters;

    public function __construct($data, $filters)
    {
        $this->data = collect($data);
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->data->map(function($item, $index) {
            return [
                'sr' => $index + 1,
                'date' => Carbon::parse($item->date)->format('d-m-Y'),
                'total_employees' => $item->total_employees,
                'total_present' => $item->present_or_late_count,
                'present' => $item->present_count,
                'late' => $item->late_count,
                'absent' => $item->absent_count,
                'approved_leaves' => $item->approved_leaves,
                'full_leaves' => $item->full_leaves,
                'half_leaves' => $item->half_leaves,
                'short_leaves' => $item->short_leaves,
                'on_time_percentage' => $item->on_time_percentage ? $item->on_time_percentage . '%' : '0.0%',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'SR #',
            'Date',
            'Total Employees',
            'Total Present',
            'Present',
            'Late',
            'Absent',
            'Approved Leaves',
            'Full Leaves',
            'Half Leaves',
            'Short Leaves',
            'On-Time %',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FF6B6B'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Auto-size columns
        foreach(range('A','J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Center align all data cells
        $lastRow = $this->data->count() + 1;
        $sheet->getStyle('A2:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add borders to all cells
        $sheet->getStyle('A1:J' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Alternate row colors for better readability
        for($i = 2; $i <= $lastRow; $i++) {
            if($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':J' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF3E6'],
                    ],
                ]);
            }
        }

        return [];
    }

    public function title(): string
    {
        return 'Attendance Summary';
    }
}

