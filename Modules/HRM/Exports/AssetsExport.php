<?php

namespace Modules\HRM\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AssetsExport implements FromView, WithEvents
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('hrm::assets.excel-export', $this->data);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();
                $fullRange  = "A1:{$highestCol}{$highestRow}";

                $sheet->getStyle($fullRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // reasonable widths
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(14);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(18);

                for ($r = 1; $r <= $highestRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(-1);
                }

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

                // Find header row (SR #) and insert title
                $headerRow = null;
                for ($r = 1; $r <= $highestRow; $r++) {
                    $a = trim((string)$sheet->getCell("A{$r}")->getValue());
                    if ($a === 'SR #') {
                        $headerRow = $r;
                        break;
                    }
                }

                if ($headerRow) {
                    $sheet->insertNewRowBefore($headerRow, 1);
                    $highestRow = $sheet->getHighestRow();
                    $titleRow = $headerRow;
                    $titleRange = "A{$titleRow}:{$highestCol}{$titleRow}";
                    $sheet->setCellValue("A{$titleRow}", 'Assets Report');
                    if ($highestCol !== 'A') $sheet->mergeCells($titleRange);
                    $sheet->getStyle($titleRange)->getFont()->setBold(true)->setSize(14);
                    $sheet->getStyle($titleRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $headerRow = $headerRow + 1;
                    $headerRange = "A{$headerRow}:{$highestCol}{$headerRow}";
                    $fill($headerRange, '#FADAD3');
                    $font($headerRange, true);

                    $lightShade = '#FEFAF7';
                    for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
                        $firstCell = trim((string)$sheet->getCell("A{$r}")->getValue());
                        if ($firstCell === '') continue;
                        $relative = $r - $headerRow;
                        if ($relative % 2 === 1) {
                            $rowRange = "A{$r}:{$highestCol}{$r}";
                            $fill($rowRange, $lightShade);
                        }
                    }
                } else {
                    $fill("A1:{$highestCol}1", '#FADAD3');
                }
            },
        ];
    }
}

