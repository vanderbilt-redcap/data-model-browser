<?php
namespace Vanderbilt\DataModelBrowserExternalModule;
use Vanderbilt\DataModelBrowserExternalModule\JsonPDF;
use Vanderbilt\DataModelBrowserExternalModule\ProjectData;

function array_filter_empty($array)
{
    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            $value = array_filter_empty($value);
        }
        if (is_array($value) && empty($value)) {
            unset($array[$key]);
        }
    }
    return $array;
}

function multi_array_diff($arr1, $arr2){
    $arrDiff = array();
    foreach($arr1 as $key => $val) {
        if(isset($arr2[$key])){
            if(is_array($val)){
                $arrDiff[$key] = multi_array_diff($val, $arr2[$key]);
            }else{
                if(is_array($arr2) && in_array($val, $arr2)!= 1){
                    $arrDiff[$key] = $val;
                }
            }
        }else if(isset($val)){
            $arrDiff[$key] = $val;
        }
    }
    return $arrDiff;
}

function getCrypt($string, $action = 'e',$secret_key="",$secret_iv="" ) {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $key = hash( 'sha256', $secret_key );
    $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

    if( $action == 'e' ) {
        $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
    }
    else if( $action == 'd' ){
        $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
    }

    return $output;
}

function printFile($module,$edoc, $type){
    $file = "#";
    if($edoc != ""){
        $q = $module->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
        while ($row = $q->fetch_assoc()) {
            $url = 'downloadFile.php?sname=' . $row['stored_name'] . '&file=' . urlencode($row['doc_name'])."&NOAUTH";
            $base64 = base64_encode(file_get_contents($module->framework->getSafePath($row['stored_name'], EDOC_PATH)));
            if($type == "img"){
                $file = '<br/><div class="inside-panel-content"><img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="display: block; margin: 0 auto;"></div>';
            }else if($type == "logo"){
                $file = '<img src="data:'.$row['mime_type'].';base64,' . $base64. '" class="wiki_logo_img" style="height:40px;">';
            }else if($type == "imgpdf"){
                $file = '<div style="max-width: 450px;height: 500px;"><img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="display: block; margin: 0 auto;width:450px;height: 450px;"></div>';
            }else if($type == "url"){
                $file = $module->getUrl($url);
            }else{
                $file = '<br/><div class="inside-panel-content"><a href="'.$module->getUrl($url,true).'" target="_blank"><span class="fa fa-file-o"></span> ' . $row['doc_name'] . '</a></div>';
            }
        }
    }
    return $file;
}

/**
 * Function that searches the armID from a project and returns the data
 * @param $projectID
 * @return array|mixed
 */
function getTablesInfo($module,$projectID, $tableID="", $tableOrderParam="table_order"){
    $sql = "SELECT * FROM `redcap_events_arms` WHERE project_id ='".db_escape($projectID)."'";
    $q = db_query($sql);

    $dataTable = array();
    while ($row = db_fetch_assoc($q)){
        $sqlTable = "SELECT * FROM `redcap_events_metadata` WHERE arm_id ='".db_escape($row['arm_id'])."'";
        $qTable = db_query($sqlTable);
        while ($rowTable = db_fetch_assoc($qTable)){
            $dataTable = generateTableArray($module,$rowTable['event_id'], $projectID,$dataTable,$tableID,$tableOrderParam);
        }
    }
    return $dataTable;
}

function getDataRepeatingInstrumentsGroupByField($module,$project_id,$vars=""){
    $data_ditionary = $module->framework->dataDictionaryCSVToMetadataArray($module->framework->getModulePath()."csv/DATAMODEL_data_dictionary.csv");

    $array = array();
    $records = \REDCap::getData($project_id,'array',$vars);
    $index=0;
    foreach ($records as $record=>$record_array) {
        foreach ($record_array as $event=>$data) {
            if($event == 'repeat_instances'){
                foreach ($data as $eventarray){
                    foreach ($eventarray as $instrument=>$instrumentdata){
                        $count = 0;
                        foreach ($instrumentdata as $instance=>$instancedata){
                            foreach ($data_ditionary['B'] as $key=>$value){
                                if($instrument == $value && array_key_exists($data_ditionary['A'][$key],$instancedata)){
                                    if(!array_key_exists($data_ditionary['A'][$key],$array[$index]) || (array_key_exists($data_ditionary['A'][$key],$array[$index]) && !is_array($array[$index][$data_ditionary['A'][$key]]))){
                                        $array[$index][$data_ditionary['A'][$key]] = array();
                                    }
                                    else if(is_array($array[$index][$data_ditionary['A'][$key]]) && !empty($array[$index][$data_ditionary['A'][$key]]) && is_array($instancedata[$data_ditionary['A'][$key]]) && count($instancedata[$data_ditionary['A'][$key]]) == 1 && $count==0){
                                        $array[$index][$data_ditionary['A'][$key]] = array();
                                    }
                                    array_push($array[$index][$data_ditionary['A'][$key]],$instancedata[$data_ditionary['A'][$key]]);
                                }
                            }
                            $count++;
                        }
                    }
                }
            }else{
                $array[$index] = $data;
            }
        }
        $index++;
    }
    return $array;
}

/**
 * Function that generates an array with the table name and event information
 * @param $event_id, the event identificator
 * @param $projectID, the project we want to search in
 * @param $dataTable, the array we are going to fill up
 * @return mixed, the array $dataTable we are going to fill up
 */
function generateTableArray($module,$event_id, $project_id, $dataTable,$tableID,$tableOrderParam){
    if(empty($tableID)){
        $recordsTable = getDataRepeatingInstrumentsGroupByField($module,$project_id);
    }else{
        $recordsTable = getDataRepeatingInstrumentsGroupByField($module,$project_id, array('record_id' => $tableID));
    }
    $dataFormat = $module->getChoiceLabels('data_format', $project_id);

    $dataTable['data_format_label'] = $dataFormat;
    foreach($recordsTable as $record ){

        #we sort the variables by value and keep key
        if(is_array($record['variable_order'])) {
            asort($record['variable_order']);
        }

        if(!empty($record['record_id'])){//Variables
            $dataTable[$record['record_id']] = $record;
        }
    }
    #We order the tables
    array_sort_by_column($dataTable, $tableOrderParam);
    return $dataTable;
}

function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }
    array_multisort($sort_col, $dir, $arr);
}

function getHtmlTableCodesTableArrayExcel($module,$dataTable,$pidsArray){
    $data_array = array();
    $dataFormat = $module->getChoiceLabels('data_format', $pidsArray['DATAMODEL']);
    foreach ($dataTable as $data) {
        if (!empty($data['record_id']) && ($data['table_status'] == "1"  || !array_key_exists('table_status',$data) || $data['table_status'] != 3)) {
            $data_code_array = array();
            foreach ($data['variable_order'] as $id=>$value) {
                if($data['variable_status'][$id] == "1" && $data['has_codes'][$id] == "1") {
                    $data_code_array[0] = $data["table_name"];
                    $data_code_array[1] = !array_key_exists($id, $data['variable_name']) ? $data['variable_name'][''] : $data['variable_name'][$id];

                    $description = empty($data["description"][$id]) ? $data["description"][''] : $data["description"][$id];
                    if (!empty($data['description_extra'][$id])) {
                        $description .= "\n" . $data['description_extra'][$id];
                    }
                    if ($data['has_codes'][$id] == '1') {
                        if (!empty($data['code_list_ref'][$id])) {
                            $RecordSetCodeList = \REDCap::getData($pidsArray['CODELIST'], 'array', array('record_id' => $data['code_list_ref'][$id]));
                            $codeformat = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetCodeList,$pidsArray['CODELIST'])[0];
                            if ($codeformat['code_format'] == '1') {
                                $codeOptions = empty($codeformat['code_list']) ? $data['code_text'][$id] : explode(" | ", $codeformat['code_list']);
                                foreach ($codeOptions as $option) {
                                    $var_codes = preg_split("/((?<!['\"])=(?!['\"]))/", $option);
                                    $data_code_array[2] = htmlentities(trim($var_codes[0]));
                                    $data_code_array[3] = htmlentities(trim($var_codes[1]));
                                    array_push($data_array, $data_code_array);
                                }
                            } else {
                                if ($codeformat['code_format'] == '3') {
                                    if (array_key_exists('code_file', $codeformat) && $data['codes_print'][$id] == '1') {
                                        $data_array = \Vanderbilt\DataModelBrowserExternalModule\getHtmlCodesTableArrayExcel($module, $data_array, $data_code_array, $codeformat['code_file']);
                                    }
                                } else if ($codeformat['code_format'] == '4') {
                                    $data_code_array[2] = 'https://bioportal.bioontology.org/ontologies/' . $codeformat['code_ontology'];
                                    array_push($data_array, $data_code_array);
                                }
                            }
                        }
                    } else if (!empty($data['code_text'][$id])) {
                        $data_code_array[2] = htmlentities($dataFormat[$data['data_format'][$id]]);
                        $data_code_array[3] = htmlentities($description);
                        array_push($data_array, $data_code_array);
                    }
                }
            }
        }
    }
    return $data_array;
}

function getHtmlCodesTableArrayExcel($module, $data_array,$data_code_array,$code_file)
{
    $csv = \Vanderbilt\DataModelBrowserExternalModule\parseCSVtoArray($module,$code_file);
    if (!empty($csv)) {
        foreach ($csv as $header => $content) {
            if ($header != 0) {
                $index = 2;
                foreach ($content as $col => $value) {
                    #Convert to UTF-8 to avoid weird characters
                    $value = mb_convert_encoding($value, 'UTF-8', 'HTML-ENTITIES');
                    $data_code_array[$index] = $value;
                    $index++;
                }
                array_push($data_array,$data_code_array);
            }
        }
    }
    return $data_array;
}

/***PHP SPREADSHEET***/

function getExcelHeaders($sheet,$headers,$letters,$width,$row_number){
    foreach ($headers as $index=>$header) {
        $sheet->setCellValue($letters[$index] . $row_number, $header);
        $sheet->getStyle($letters[$index] . $row_number)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle($letters[$index].$row_number)->getFill()->getStartColor()->setARGB('4db8ff');
        $sheet->getStyle($letters[$index].$row_number)->getFont()->setBold( true );
        $sheet->getStyle($letters[$index].$row_number)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
        $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setHorizontal('center');
        $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setWrapText(true);

        $sheet->getColumnDimension($letters[$index])->setAutoSize(false);
        $sheet->getColumnDimension($letters[$index])->setWidth($width[$index]);
    }
    return $sheet;
}

function getExcelData($sheet,$data_array,$headers,$letters,$section_centered,$row_number){
    $active_n_found = false;
    foreach ($data_array as $row => $data) {
        foreach ($headers as $index => $header) {
            $sheet->setCellValue($letters[$index].$row_number, $data[$index]);
            $sheet->getStyle($letters[$index].$row_number)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setWrapText(true);
            if($section_centered[$index] == "1"){
                $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setHorizontal('center');
            }
        }
        if( $active_n_found){
            foreach ($headers as $index=>$header) {
                $sheet->getStyle($letters[$index] . $row_number)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle($letters[$index].$row_number)->getFill()->getStartColor()->setARGB('e6e6e6');
            }
            $active_n_found = false;
        }
        $row_number++;
    }
    return $sheet;
}

/**
 * Table list with anchor links for the JsonPDF
 * @param $dataTable
 * @return string
 */
function generateRequestedTablesList_pdf($dataTable,$draft,$deprecated){
    $requested_tables = "<ol>";
    foreach ($dataTable as $data) {
        if (!empty($data['record_id']) && ($data['table_status'] == "1" || !array_key_exists("table_status",$data) || ($data['table_status'] == "2" && $deprecated == "true") || ($data['table_status'] == "0" && $draft == "true"))) {
            $requested_tables .= "<li><a href='#anchor_" . $data['record_id'] . "' style='text-decoration:none'>" . $data["table_name"] . "</a></li>";
        }
    }
    $requested_tables .= "</ol>";
    return $requested_tables;
}

function isUserExpiredOrSuspended($module,$username,$field){
    $result = $module->query("SELECT ".$field." FROM redcap_user_information WHERE username = ?",[$username]);
    while($row = db_fetch_assoc($result)){
        if($row[$field] == null || $row[$field] == "" || strtotime($row[$field]) > strtotime(date("Y-m-d"))) {
            #Not Expired
            return false;
        }
    }
    #User Expired
    return true;
}
/**
 * Function that searches the file name in the database, parses it and returns an array with the content
 * @param $DocID, the id of the document
 * @return array, the generated array with the data
 */
function parseCSVtoArray($module,$DocID){
    $q = $module->query("SELECT * FROM `redcap_edocs_metadata` WHERE doc_id = ?",[$DocID]);
    $csv = array();
    while ($rowTableCSV = $q->fetch_assoc()) {
        $csv = \Vanderbilt\DataModelBrowserExternalModule\createArrayFromCSV($module->framework->getSafePath($rowTableCSV['stored_name'], EDOC_PATH));
    }
    return $csv;
}
/**
 * Function that parses de CSV file to an Array
 * @param $filepath, the path of the file
 * @param $filename, the file name
 * @return array, the generated array with the CSV data
 */
function createArrayFromCSV($filepath, $addHeader = false){
    $csv = array_map('str_getcsv', file($filepath));
    array_walk($csv, function(&$a) use ($csv) {
        $a = array_combine($csv[0], $a);
    });
    if($addHeader){
        # remove column header
        array_shift($csv);
    }

    return $csv;
}
/**
 * Function that searches the file name in the database and returns a string with the link info to download the file
 * @param $DocID, the id of the document
 * @return string, the parameters needed to create a link and download the file
 */
function parseCSVtoLink($module,$DocID){
    $q = $module->query("SELECT * FROM `redcap_edocs_metadata` WHERE doc_id = ?",[$DocID]);
    $link = "";
    while ($rowTableCSV = $q->fetch_assoc()) {
        $link = "sname=" . $rowTableCSV['stored_name'] . "&file=" . $rowTableCSV['doc_name'];
    }
    return $link;
}

function getDataTable($project_id){
    return method_exists('\REDCap', 'getDataTable') ? \REDCap::getDataTable($project_id) : "redcap_data";
}
?>