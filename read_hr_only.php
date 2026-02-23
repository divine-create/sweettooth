&lt;?php

// Simple script to read just the HR.xlsx file and extract employee data

require_once __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Define the path to your HR Excel file
$hrFilePath = __DIR__.'/real data/HR.xlsx';

if (!file_exists($hrFilePath)) {
    echo "HR.xlsx file does not exist at $hrFilePath\n";
    return;
}

try {
    echo "Processing HR.xlsx...\n";
    
    // Load the Excel file
    $spreadsheet = IOFactory::load($hrFilePath);
    
    // Get all worksheets
    $worksheets = $spreadsheet->getAllSheets();
    
    $hrData = [];
    
    foreach ($worksheets as $worksheet) {
        $sheetName = $worksheet->getTitle();
        echo "Processing worksheet: $sheetName\n";
        
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
        
        $hrData[$sheetName] = $rows;
        
        // Print first few rows to see structure
        echo "First few rows from $sheetName:\n";
        for ($i = 0; $i &lt; min(10, count($rows)); $i++) {
            echo "Row " . ($i == 0 ? "Header: " : "Data " . $i . ": ");
            foreach ($rows[$i] as $j => $value) {
                echo "[$j] " . (is_null($value) ? 'NULL' : $value) . " | ";
            }
            echo "\n";
        }
        echo "\n";
    }
    
    echo "Completed processing HR.xlsx\n";
    
} catch (Exception $e) {
    echo "Error processing HR.xlsx: " . $e->getMessage() . "\n";
}
