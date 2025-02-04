<?php
require 'vendor/autoload.php'; // Load PHPSpreadsheet library

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['salary_sheet'])) {
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $uploadFile = $uploadDir . basename($_FILES['salary_sheet']['name']);
    $fileType = pathinfo($uploadFile, PATHINFO_EXTENSION);
    
    $allowedTypes = ['csv', 'xls', 'xlsx'];
    if (!in_array(strtolower($fileType), $allowedTypes)) {
        echo "Invalid file format. Please upload a .csv, .xls, or .xlsx file.";
        exit;
    }
    
    if (move_uploaded_file($_FILES['salary_sheet']['tmp_name'], $uploadFile)) {
        // Load the spreadsheet file
        $spreadsheet = IOFactory::load($uploadFile);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        
        $outputFilePath = $uploadDir . 'Salary_Roll_Out_Generated.txt';
        $outputFile = fopen($outputFilePath, 'w');
        
        // Write the file header
        fwrite($outputFile, "FILEHDR|ASGMARKET\n");
        
        // Process each row (assuming first row is headers, start from index 1)
        foreach ($data as $index => $row) {
            if ($index == 0) continue; // Skip header row
            
            list($employeeId, $accountNumber, $currency, $transactionType, $amount, $description) = $row;
            
            // Ensure proper spacing and formatting
            $formattedLine = sprintf("%-6s %-18s %-4s %-2s %-12s %-50s\n",
                trim($employeeId),
                trim($accountNumber),
                trim($currency),
                trim($transactionType),
                number_format((float)$amount, 1, '.', ''),
                trim($description)
            );
            
            fwrite($outputFile, $formattedLine);
        }
        
        fclose($outputFile);
        
        // Redirect to download the file
        header("Location: download.php?file=Salary_Roll_Out_Generated.txt");
        exit;
    } else {
        echo "File upload failed.";
    }
} else {
    echo "<form method='POST' enctype='multipart/form-data'>
            <input type='file' name='salary_sheet' accept='.csv,.xls,.xlsx' required>
            <button type='submit'>Upload & Generate</button>
          </form>";
}
?>