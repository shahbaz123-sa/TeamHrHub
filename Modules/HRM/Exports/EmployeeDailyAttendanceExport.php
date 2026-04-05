<?php
namespace Modules\HRM\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeDailyAttendanceExport implements FromArray, WithHeadings, WithStyles
{
    protected $data;
    protected $month;

    public function __construct($data, $month)
    {
        $this->data = $data;
        $this->month = $month;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data['employees'] as $employee) {
            $row = [
                $employee['name'],
            ];
            foreach ($this->data['dates'] as $date) {
                $val = $employee['attendance'][$date] ?? '-';
                $row[] = $val;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    public function headings(): array
    {
        $headings = ['Employee Name'];
        foreach ($this->data['dates'] as $date) {
            $headings[] = date('d', strtotime($date));
        }
        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:Z1')->getFont()->setBold(true);
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getDefaultColumnDimension()->setWidth(4);
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(15);
        // Set color for status columns
        $colIndex = 3;
        foreach ($this->data['dates'] as $date) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setWidth(4);
            for ($row = 2; $row <= count($this->data['employees']) + 1; $row++) {
                $status = $this->data['employees'][$row-2]['attendance'][$date] ?? '';
                $color = match (strtoupper($status)) {
                    'P' => '43a047', // success
                    'A' => 'e53935', // error
                    'LT' => 'fbc02d', // warning
                    'SL', 'HL', 'L', 'H' => '29b6f6', // info
                    'NM' => '1976d2', // primary
                    default => 'BDBDBD', // grey
                };
                $sheet->getStyle("{$colLetter}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($color);
                $sheet->getStyle("{$colLetter}{$row}")->getFont()->getColor()->setARGB('FFFFFF');
            }
            $colIndex++;
        }
        return [];
    }
}
