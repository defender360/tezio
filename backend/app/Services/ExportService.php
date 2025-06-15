<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExportService
{
    /**
     * Export data to Excel format
     */
    public function exportToExcel($data, $filename, $metadata = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add metadata
        if (!empty($metadata)) {
            $this->addMetadata($sheet, $metadata);
            $currentRow = count($metadata) + 2;
        } else {
            $currentRow = 1;
        }
        
        // Process different data sections
        foreach ($data as $section => $sectionData) {
            $currentRow = $this->addSection($sheet, $section, $sectionData, $currentRow);
            $currentRow += 2; // Add spacing between sections
        }
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Create writer and save
        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path('app/temp/' . $filename . '.xlsx');
        
        // Ensure directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }
        
        $writer->save($tempPath);
        
        return $tempPath;
    }
    
    /**
     * Export data to CSV format
     */
    public function exportToCsv($data, $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $currentRow = 1;
        
        // Flatten data for CSV
        foreach ($data as $section => $sectionData) {
            if (is_array($sectionData) && !empty($sectionData)) {
                // Add section header
                $sheet->setCellValue('A' . $currentRow, $section);
                $currentRow++;
                
                // Add data
                if (isset($sectionData[0]) && is_array($sectionData[0])) {
                    // Array of arrays - treat as table data
                    $headers = array_keys($sectionData[0]);
                    foreach ($headers as $col => $header) {
                        $sheet->setCellValueByColumnAndRow($col + 1, $currentRow, $header);
                    }
                    $currentRow++;
                    
                    foreach ($sectionData as $row) {
                        $col = 1;
                        foreach ($row as $value) {
                            $sheet->setCellValueByColumnAndRow($col, $currentRow, $value);
                            $col++;
                        }
                        $currentRow++;
                    }
                } else {
                    // Key-value pairs
                    foreach ($sectionData as $key => $value) {
                        $sheet->setCellValue('A' . $currentRow, $key);
                        $sheet->setCellValue('B' . $currentRow, $value);
                        $currentRow++;
                    }
                }
                
                $currentRow++; // Add empty row between sections
            }
        }
        
        // Create writer and save
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->setLineEnding("\r\n");
        
        $tempPath = storage_path('app/temp/' . $filename . '.csv');
        
        // Ensure directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }
        
        $writer->save($tempPath);
        
        return $tempPath;
    }
    
    /**
     * Export data to PDF format
     */
    public function exportToPdf($data, $filename, $type = 'analytics', $metadata = [])
    {
        $viewName = $this->getViewForType($type);
        
        $pdf = PDF::loadView($viewName, [
            'data' => $data,
            'metadata' => array_merge([
                'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'generated_by' => auth()->user()->name ?? 'System'
            ], $metadata)
        ]);
        
        $pdf->setPaper('A4', 'landscape');
        
        $tempPath = storage_path('app/temp/' . $filename . '.pdf');
        
        // Ensure directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }
        
        $pdf->save($tempPath);
        
        return $tempPath;
    }
    
    /**
     * Add metadata to spreadsheet
     */
    protected function addMetadata($sheet, $metadata)
    {
        $row = 1;
        
        foreach ($metadata as $key => $value) {
            $sheet->setCellValue('A' . $row, ucfirst(str_replace('_', ' ', $key)) . ':');
            $sheet->setCellValue('B' . $row, $value);
            
            // Style the label
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            
            $row++;
        }
        
        return $row;
    }
    
    /**
     * Add a data section to spreadsheet
     */
    protected function addSection($sheet, $sectionName, $data, $startRow)
    {
        $currentRow = $startRow;
        
        // Add section title
        $sheet->setCellValue('A' . $currentRow, ucfirst(str_replace('_', ' ', $sectionName)));
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(14);
        $currentRow++;
        
        if (is_array($data) && !empty($data)) {
            if (isset($data[0]) && is_array($data[0])) {
                // Table data
                $currentRow = $this->addTableData($sheet, $data, $currentRow);
            } else {
                // Key-value data
                $currentRow = $this->addKeyValueData($sheet, $data, $currentRow);
            }
        }
        
        return $currentRow;
    }
    
    /**
     * Add table data to spreadsheet
     */
    protected function addTableData($sheet, $data, $startRow)
    {
        if (empty($data)) return $startRow;
        
        $currentRow = $startRow;
        
        // Add headers
        $headers = array_keys($data[0]);
        $col = 1;
        foreach ($headers as $header) {
            $cellAddress = $this->getCellAddress($col, $currentRow);
            $sheet->setCellValue($cellAddress, ucfirst(str_replace('_', ' ', $header)));
            
            // Style header
            $sheet->getStyle($cellAddress)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $col++;
        }
        $currentRow++;
        
        // Add data rows
        foreach ($data as $row) {
            $col = 1;
            foreach ($row as $value) {
                $cellAddress = $this->getCellAddress($col, $currentRow);
                $sheet->setCellValue($cellAddress, $value);
                $col++;
            }
            $currentRow++;
        }
        
        // Add borders to table
        $lastCol = $this->getColumnLetter(count($headers));
        $tableRange = 'A' . ($startRow) . ':' . $lastCol . ($currentRow - 1);
        $sheet->getStyle($tableRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB']
                ]
            ]
        ]);
        
        return $currentRow;
    }
    
    /**
     * Add key-value data to spreadsheet
     */
    protected function addKeyValueData($sheet, $data, $startRow)
    {
        $currentRow = $startRow;
        
        foreach ($data as $key => $value) {
            $sheet->setCellValue('A' . $currentRow, ucfirst(str_replace('_', ' ', $key)) . ':');
            $sheet->setCellValue('B' . $currentRow, $value);
            
            // Style the label
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
            
            $currentRow++;
        }
        
        return $currentRow;
    }
    
    /**
     * Get cell address from column and row
     */
    protected function getCellAddress($col, $row)
    {
        return $this->getColumnLetter($col) . $row;
    }
    
    /**
     * Convert column number to letter
     */
    protected function getColumnLetter($col)
    {
        $letter = '';
        while ($col > 0) {
            $col--;
            $letter = chr(65 + ($col % 26)) . $letter;
            $col = intval($col / 26);
        }
        return $letter;
    }
    
    /**
     * Get PDF view name based on export type
     */
    protected function getViewForType($type)
    {
        return match($type) {
            'incidents' => 'exports.pdf.incidents',
            'changes' => 'exports.pdf.changes',
            'sla' => 'exports.pdf.sla',
            'workload' => 'exports.pdf.workload',
            'executive' => 'exports.pdf.executive',
            'team' => 'exports.pdf.team',
            default => 'exports.pdf.default'
        };
    }
    
    /**
     * Schedule a report for regular generation
     */
    public function scheduleReport($reportConfig)
    {
        // This would typically save to a database table
        // For now, we'll return a confirmation
        return [
            'id' => uniqid('report_'),
            'name' => $reportConfig['name'],
            'type' => $reportConfig['type'],
            'frequency' => $reportConfig['frequency'],
            'next_run' => $this->calculateNextRun($reportConfig['frequency']),
            'status' => 'scheduled'
        ];
    }
    
    /**
     * Calculate next run time based on frequency
     */
    protected function calculateNextRun($frequency)
    {
        return match($frequency) {
            'daily' => Carbon::tomorrow()->setTime(6, 0),
            'weekly' => Carbon::now()->next(Carbon::MONDAY)->setTime(6, 0),
            'monthly' => Carbon::now()->firstOfNextMonth()->setTime(6, 0),
            default => Carbon::tomorrow()->setTime(6, 0)
        };
    }
}