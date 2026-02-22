<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessExcelData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:excel-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract data from Excel files for migration purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Excel data extraction...');
        
        // Define the path to your Excel files
        $dataDir = base_path('real_data/');
        $outputFile = base_path('extracted_data.json');

        // Excel files to process
        $excelFiles = [
            'HR.xlsx',
            'HOT KITCHEN RECIPES EXCEL.xlsx',
            'PRODUCTION_ANALYSIS_DECEMBER_2025.xlsx',
            'PRODUCTION_ANALYSIS_DECEMBER_2025 (1).xlsx',
            'SWEETTOOTH PRODUCT PRICES.xlsx',
            'SWEETTOOTH STAFFS.xlsx'
        ];

        $extractedData = [];

        foreach ($excelFiles as $fileName) {
            $filePath = $dataDir . $fileName;
            
            if (!file_exists($filePath)) {
                $this->error("File does not exist: $filePath");
                continue;
            }
            
            try {
                $this->info("Processing $fileName...");
                
                // Load the Excel file
                $spreadsheet = IOFactory::load($filePath);
                
                // Get all worksheets
                $worksheets = $spreadsheet->getAllSheets();
                
                foreach ($worksheets as $worksheet) {
                    $sheetName = $worksheet->getTitle();
                    $rows = [];
                    
                    // Get all rows
                    foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
                        $rowData = [];
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false); // Include empty cells
                        
                        foreach ($cellIterator as $cell) {
                            $rowData[] = $cell->getValue();
                        }
                        
                        // Skip empty rows
                        if (count(array_filter($rowData)) > 0) {
                            $rows[] = $rowData;
                        }
                    }
                    
                    $extractedData[$fileName][$sheetName] = $rows;
                }
                
                $this->info("Completed processing $fileName");
            } catch (\Exception $e) {
                $this->error("Error processing $fileName: " . $e->getMessage());
            }
        }

        // Save extracted data to JSON file
        file_put_contents($outputFile, json_encode($extractedData, JSON_PRETTY_PRINT));

        $this->info("Data extraction completed. Results saved to $outputFile");

        // Also try to identify branch information if available
        foreach ($extractedData as $fileName => $fileData) {
            foreach ($fileData as $sheetName => $rows) {
                // Look for potential branch data in headers
                if (isset($rows[0])) {
                    $header = array_map('strtolower', $rows[0]);
                    
                    // Check if this sheet contains branch-like information
                    if (in_array('branch', $header) || in_array('branch name', $header) || in_array('location', $header)) {
                        $this->info("Potential branch data found in $fileName - $sheetName:");
                        
                        // Extract first few rows to see structure
                        for ($i = 0; $i < min(5, count($rows)); $i++) {
                            $this->line("Row " . ($i == 0 ? "Header: " : "Data " . $i . ": ") . implode(", ", $rows[$i]));
                        }
                        $this->newLine();
                    }
                    
                    // Check if this sheet contains employee data
                    if (in_array('employee', $header) || in_array('first name', $header) || in_array('last name', $header) || in_array('staff id', $header)) {
                        $this->info("Potential employee data found in $fileName - $sheetName:");
                        
                        // Extract first few rows to see structure
                        for ($i = 0; $i < min(5, count($rows)); $i++) {
                            $this->line("Row " . ($i == 0 ? "Header: " : "Data " . $i . ": ") . implode(", ", $rows[$i]));
                        }
                        $this->newLine();
                    }
                }
            }
        }
    }
}
