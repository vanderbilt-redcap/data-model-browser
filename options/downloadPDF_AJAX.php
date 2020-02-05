<?php
define('NOAUTH',true);
require_once dirname(dirname(__FILE__)) . "/base.php";
require_once dirname(dirname(__FILE__)) . "/dompdf/autoload.inc.php";
require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$projectDESSettings = new \Plugin\Project(DES_SETTINGS);
$RecordSetSettings= new \Plugin\RecordSet($projectDESSettings, array(\Plugin\RecordSet::getKeyComparatorPair($projectDESSettings->getFirstFieldName(),"!=") => ""));
$settings = $RecordSetSettings->getDetails()[0];

$option = $_REQUEST['option'];
$deprecated = $_REQUEST['deprecated'];
$draft = $_REQUEST['draft'];

$dataTable = getTablesInfo(DES_DATAMODEL);
if($option == "2"){

    $requested_tables = getHtmlTableCodesTableArrayExcel($dataTable);
    #EXEL SHEET
    $filename = "code_list_ " . date("Y-m-d_hi",time()) . ".xlsx";

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
}else{
    if(!empty($dataTable)) {
        # Get selected rows;
        $tableHtml = generateTablesHTML_pdf($dataTable,$draft,$deprecated);
    }
    #FIRST PAGE
    $first_page = "<tr><td align='center'>";
    $first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>International Epidemiology Databases to Evaluate AIDS (IeDEA)</span></p>";
    $first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>STANDARD OPERATING PROCEDURES FOR DATA TRANSFER</span></p><br/>";
    $first_page .= "<p><span style='font-size: 14pt;font-weight: bold;'>Request Version: ".date('d F Y')."</span></p><br/>";
    $first_page .= "<span style='font-size: 12pt'>";
    $first_page .= "</span></td></tr></table>";

    #SECOND PAGE
    $second_page = "<p><span style='font-size: 12pt'>".$tableHtml[1]."</span></p>";

    $page_num = '<style>.footer .page-number:after { content: counter(page); } .footer { position: fixed; bottom: 0px;color:grey }a{text-decoration: none;}</style>';

    $img = 'data:image/png;base64,'.base64_encode(file_get_contents(loadImg($settings['des_logo'],$secret_key,$secret_iv,'../../img/IeDEA-logo-200px.png','pdf')));

    $html_pdf = "<html><body style='font-family:\"Calibri\";font-size:10pt;'>".$page_num
        ."<div class='footer' style='left: 590px;'><span class='page-number'>Page </span></div>"
        ."<div class='mainPDF'><table style='width: 100%;'><tr><td align='center'><img src='".$img."' style='width:200px;padding-bottom: 30px;'></td></tr></table></div>"
        ."<div class='mainPDF' id='page_html_style'><table style='width: 100%;'>".$first_page."<div style='page-break-before: always;'></div>"
        ."<div class='mainPDF'>".$second_page."<div style='page-break-before: always;'></div>"
        ."<p><span style='font-size:16pt'><strong>6. Requested DES Tables</strong></span></p>"
        .$tableHtml[0]
        ."</div></div>"
        . "</body></html>";

    $filename = $settings['des_wkname']."_DES_".date("Y-m-d_hi",time());

    //DOMPDF
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html_pdf);
    $dompdf->setPaper('A4', 'portrait');
    ob_start();
    $dompdf->render();
    //#Download option
    $dompdf->stream($filename);

}

?>