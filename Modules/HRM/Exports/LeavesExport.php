<?php

namespace Modules\HRM\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LeavesExport implements FromView, WithEvents
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('hrm::leaves.excel-export', $this->data);
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

                // set some reasonable widths for first columns
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(22);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(20);

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

                // Find the header row (where first cell is 'SR #')
                $headerRow = null;
                for ($r = 1; $r <= $highestRow; $r++) {
                    $a = trim((string)$sheet->getCell("A{$r}")->getValue());
                    if ($a === 'SR #') {
                        $headerRow = $r;
                        break;
                    }
                }

                // If header found, insert a merged title row above it and place logo
                if ($headerRow) {
                    // Insert a new row before headerRow for the title
                    $sheet->insertNewRowBefore($headerRow, 1);
                    // Recalculate highest row
                    $highestRow = $sheet->getHighestRow();
                    // Title is now at $headerRow (pushed header down by 1)
                    $titleRow = $headerRow;
                    $titleRange = "A{$titleRow}:{$highestCol}{$titleRow}";
                    // Write title
                    $sheet->setCellValue("A{$titleRow}", 'Leave Report');
                    // Merge and style title across full width
                    if ($highestCol !== 'A') $sheet->mergeCells($titleRange);
                    $sheet->getStyle($titleRange)->getFont()->setBold(true)->setSize(14);
                    $sheet->getStyle($titleRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // NOTE: company logo insertion removed per request

                    // Header has been pushed down by 1
                    $headerRow = $headerRow + 1;
                    // Style header row across full width
                    $headerRange = "A{$headerRow}:{$highestCol}{$headerRow}";
                    $fill($headerRange, '#FADAD3');
                    $font($headerRange, true);

                    // Shade alternate data rows starting after header (use requested color)
                    $lightShade = '#FEFAF7';
                    for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
                        // skip totally empty rows
                        $firstCell = trim((string)$sheet->getCell("A{$r}")->getValue());
                        if ($firstCell === '') continue;
                        $relative = $r - $headerRow;
                        if ($relative % 2 === 1) {
                            $rowRange = "A{$r}:{$highestCol}{$r}";
                            $fill($rowRange, $lightShade);
                        }
                    }
                } else {
                    // fallback: color whole header if 'SR #' not found — color first row
                    $fill("A1:{$highestCol}1", '#FADAD3');
                }
            },
        ];
    }
}

