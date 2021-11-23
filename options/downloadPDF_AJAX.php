<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

require_once __DIR__ ."/../projects.php";
require_once __DIR__ .'/../vendor/autoload.php';
include_once __DIR__ ."/../functions.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$RecordSetSettings = \REDCap::getData($pidsArray['SETTINGS'], 'array');
$settings = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSettings)[0];

$option = $_REQUEST['option'];
$deprecated = $_REQUEST['deprecated'];
$draft = $_REQUEST['draft'];

$RecordSetDataModel = \REDCap::getData($pidsArray['DATAMODEL'], 'array');
$dataTable = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetDataModel);

$requested_tables = getHtmlTableCodesTableArrayExcel($module,$dataTable);
#EXEL SHEET
$filename = "CodeList_ " . date("Y-m-d_hi",time()) . ".xlsx";

$styleArray = array(
    'font'  => array(
        'size'  => 10,
        'name'  => 'Calibri'
    ),
    'alignment' => array(
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    ));

$spreadsheet = new Spreadsheet();
$spreadsheet->getDefaultStyle()->applyFromArray($styleArray);
$sheet = $spreadsheet->getActiveSheet();

///MULTIREG CONCEPTS///
#SECTION HEADERS
$section_headers = array(0=>"Table",1=>"Variable",2=>"Code",3=>"Label");
$section_headers_leters = array(0=>'A',1=>'B',2=>'C',3=>'D');
$section_headers_width = array(0=>'20',1=>'30',2=>'20',3=>'40');
$section_centered = array(0=>'0',1=>'0',2=>'1',3=>'0');
$row_number = 1;
$sheet = getExcelHeaders($sheet,$section_headers,$section_headers_leters,$section_headers_width,$row_number);
$sheet->setAutoFilter('A1:D1');
$row_number++;
$sheet = getExcelData($sheet,$requested_tables,$section_headers,$section_headers_leters,$section_centered,$row_number);

#Rename sheet
$sheet->setTitle('Codes');

$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$writer->save("php://output");


?>