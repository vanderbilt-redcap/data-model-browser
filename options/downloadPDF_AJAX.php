<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

require_once __DIR__ ."/../projects.php";
require_once __DIR__ .'/../vendor/autoload.php';
include_once __DIR__ ."/../functions.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

$RecordSetDataModel = \REDCap::getData($pidsArray['DATAMODEL'], 'array');
$dataTable = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetDataModel,$pidsArray['DATAMODEL']);
$requested_tables = getHtmlTableCodesTableArrayExcel($module,$dataTable,$pidsArray);

#EXEL SHEET
$filename = "CodeList_ " . date("Y-m-d_hi",time()) . ".csv";

// Create a new spreadsheet instance
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

#SECTION HEADERS
$sectionHeaders = [0=>"Table",1=>"Variable",2=>"Code",3=>"Label"];
$sectionLeters = [0=>'A',1=>'B',2=>'C',3=>'D'];

$row_number = 1;
foreach ($sectionHeaders as $key => $value) {
    $sheet->setCellValue($sectionLeters[$key].$row_number, $value);
}

#DATA
$row_number++;
foreach ($requested_tables as $row => $data) {
    foreach ($sectionHeaders as $index => $header) {
        $sheet->setCellValue($sectionLeters[$index].$row_number, $data[$index]);
    }
    $row_number++;
}

// Save the spreadsheet as a CSV file
$writer = new Csv($spreadsheet);

// Set CSV-specific options
$writer->setDelimiter(','); // Default is ','
$writer->setEnclosure('"'); // Default is '"'
$writer->setLineEnding("\r\n"); // Default is PHP_EOL
$writer->setSheetIndex(0); // Default is 0

//Download file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$writer->save("php://output");
?>

