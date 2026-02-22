#!/usr/bin/env php
&lt;?php

// Script to read Excel files and extract branch and employee data

require_once __DIR__.'/vendor/autoload.php';

use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Define the path to your Excel files
$dataDir = __DIR__.'/real data/';
$outputFile = __DIR__.'/extracted_data.json';

// Excel files to process
$excelFiles = [
    'HR.xlsx',
    'HOT KITCHEN RECIPES EXCEL.xlsx',
    'PRODUCTION_ANALYSIS_DECEMBER_2025.xlsx',
    'PRODUCTION_ANALYSIS_DECEMBER_2025 (1).xlsx',
    'SWEETTOOTH PRODUCT PRICES.xlsx'
];

$extractedData = [];

foreach ($excelFiles as $fileName) {
    $filePath = $dataDir . $fileName;
    
    if (!file_exists($filePath)) {
        echo "File does not exist: $filePath\n";
        continue;
    }
    
    try {
        echo "Processing $fileName...\n";
        
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
        
        echo "Completed processing $fileName\n";
    } catch (Exception $e) {
        echo "Error processing $fileName: " . $e->getMessage() . "\n";
    }
}

// Save extracted data to JSON file
file_put_contents($outputFile, json_encode($extractedData, JSON_PRETTY_PRINT));

echo "Data extraction completed. Results saved to $outputFile\n";

// Also try to identify branch information if available
$branchInfo = [];
foreach ($extractedData as $fileName => $fileData) {
    foreach ($fileData as $sheetName => $rows) {
        // Look for potential branch data in headers
        if (isset($rows[0])) {
            $header = array_map('strtolower', $rows[0]);
            
            // Check if this sheet contains branch-like information
            if (in_array('branch', $header) || in_array('branch name', $header) || in_array('location', $header)) {
                echo "Potential branch data found in $fileName - $sheetName:\n";
                
                // Extract first few rows to see structure
                for ($i = 0; $i &lt; min(5, count($rows)); $i++) {
                    echo "Row " . ($i == 0 ? "Header: " : "Data " . $i . ": ") . implode(", ", $rows[$i]) . "\n";
                }
                echo "\n";
            }
            
            // Check if this sheet contains employee data
            if (in_array('employee', $header) || in_array('first name', $header) || in_array('last name', $header) || in_array('staff id', $header)) {
                echo "Potential employee data found in $fileName - $sheetName:\n";
                
                // Extract first few rows to see structure
                for ($i = 0; $i &lt; min(5, count($rows)); $i++) {
                    echo "Row " . ($i == 0 ? "Header: " : "Data " . $i . ": ") . implode(", ", $rows[$i]) . "\n";
                }
                echo "\n";
            }
        }
    }
}