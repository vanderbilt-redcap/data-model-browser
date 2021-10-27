<?php
namespace Vanderbilt\DataModelBrowserExternalModule;
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
                $arrDiff[$key] = \Vanderbilt\DataModelBrowserExternalModule\multi_array_diff($val, $arr2[$key]);
            }else{
                if(in_array($val, $arr2)!= 1){
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
            $url = 'downloadFile.php?sname=' . $row['stored_name'] . '&file=' . urlencode($row['doc_name']);
            $base64 = base64_encode(file_get_contents(EDOC_PATH.$row['stored_name']));
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
            $dataTable = \Vanderbilt\DataModelBrowserExternalModule\generateTableArray($module,$rowTable['event_id'], $projectID,$dataTable,$tableID,$tableOrderParam);
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
                                    else if(is_array($array[$index][$data_ditionary['A'][$key]]) && !empty($array[$index][$data_ditionary['A'][$key]]) && count($instancedata[$data_ditionary['A'][$key]]) == 1 && $count==0){
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
        $recordsTable = \Vanderbilt\DataModelBrowserExternalModule\getDataRepeatingInstrumentsGroupByField($module,$project_id);
    }else{
        $recordsTable = \Vanderbilt\DataModelBrowserExternalModule\getDataRepeatingInstrumentsGroupByField($module,$project_id, array('record_id' => $tableID));
    }
    $dataFormat = $module->getChoiceLabels('data_format', $project_id);

    $dataTable['data_format_label'] = $dataFormat;
    foreach($recordsTable as $record ){

        #we sort the variables by value and keep key
        asort($record['variable_order']);

        if(!empty($record['record_id'])){//Variables
            $dataTable[$record['record_id']] = $record;
        }
    }
    #We order the tables
    \Vanderbilt\DataModelBrowserExternalModule\array_sort_by_column($dataTable, $tableOrderParam);
    return $dataTable;
}

function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }
    array_multisort($sort_col, $dir, $arr);
}


/**
 * Function that creates HTML tables with the Tables and Variables information to print on the PDF after the information has been selected
 * @param $dataTable, Tables and Variables information
 * @param $fieldsSelected, the selected fields
 * @return string, the html content
 */
function generateTablesHTML_pdf($module,$dataTable,$draft,$deprecated, $project_id, $dataModelPID){
    $tableHtml = "";
    $requested_tables = "<ol>";
    $table_counter = 0;
    $dataformatChoices = $module->getChoiceLabels('data_format', $dataModelPID);
    $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='CODELIST'");
    $codeListPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];
    foreach ($dataTable as $data) {
        if (!empty($data['record_id'])) {
            $found = false;
            $htmlCodes = '';
            if($data['table_status'] == "1" || !array_key_exists("table_status",$data) || $data['table_status'] == "" || ($data['table_status'] == "2" && $deprecated == "true") || ($data['table_status'] == "0" && $draft == "true")) {
                $requested_tables .= "<li><a href='#anchor_" . $data['record_id'] . "' style='text-decoration:none'>" . $data["table_name"] . "</a></li>";
                foreach ($data['variable_order'] as $id => $value) {
                    $record_varname = !array_key_exists($id, $data['variable_name']) ? $data['variable_name'][''] : $data['variable_name'][$id];
                    $record_varname_id = empty($id) ? $data['record_id'] . '_1' : $data['record_id'] . '_' . $id;
                    #We add the new Header table tags
                    if ($found == false) {
                        $table_draft = "background-color: #f0f0f5";
                        $table_draft_tdcolor = "background-color: lightgray";
                        $table_draft_text = "";

                        switch ($data['table_category']) {
                            case 'main':
                                $table_draft = "background-color: #FFC000";
                                break;
                            case 'labs':
                                $table_draft = "background-color: #9cce77";
                                break;
                            case 'dis':
                                $table_draft = "background-color: #87C1E9";
                                break;
                            case 'meds':
                                $table_draft = "background-color: #FB8153";
                                break;
                            case 'preg':
                                $table_draft = "background-color: #D7AEFF";
                                break;
                            case 'meta':
                                $table_draft = "background-color: #BEBEBE";
                                break;
                            default:
                                $table_draft = "background-color: #f0f0f5";
                                break;
                        }
                        if (array_key_exists('table_status', $data)) {
                            if ($data['table_status'] == 0 && $draft == "true") {
                                $table_draft = "background-color: #ffffcc;";
                            }
                            $table_draft_tdcolor = ($data['table_status'] == 0) ? "background-color: #999999;" : "background-color: lightgray";
                            $table_draft_text = ($data['table_status'] == 0) ? '<span style="color: red;font-style: italic">(DRAFT)</span>' : "";
                        }

                        $breakLine = '';
                        if ($table_counter > 0) {
                            $breakLine = '<div style="page-break-before: always;"></div>';
                        }
                        $table_counter++;

                        $url = $module->getUrl("browser.php?&pid=".$project_id.'&tid=' . $data['record_id'] . '&option=variables');
                        $htmlHeader = $breakLine . '<p style="' . $table_draft . '"><span style="font-size:16px"><strong><a href="' . $url . '" name="anchor_' . $data['record_id'] . '" target="_blank" style="text-decoration:none">' . $data["table_name"] . '</a></span> ' . $table_draft_text . '</strong> - ' . $data['table_definition'] . '</p>';
                        if (array_key_exists('text_top', $data) && !empty($data['text_top']) && $data['text_top'] != "") {
                            $htmlHeader .= '<div  style="border-color: white;font-style: italic">' . htmlspecialchars($data["text_top"]) . '</div>';
                        }
                        $htmlHeader .= '<table border ="1px" style="border-collapse: collapse;width: 100%;">
                        <tr style="' . $table_draft_tdcolor . '">
                            <td style="padding: 5px;width:30%">Field</td>
                            <td style="padding: 5px">Format</td>
                            <td style="padding: 5px">Description</td>
                        </tr>';
                        $found = true;
                        $tableHtml .= $htmlHeader;
                    }

                    if ($data['variable_status'][$id] == "1" || ($data['variable_status'][$id] == "2" && $deprecated == "true") || ($data['variable_status'][$id] == "0" && $draft == "true")) {
                        $variable_status = "";
                        $variable_text = "";
                        if (array_key_exists('variable_status', $data) && array_key_exists($id, $data['variable_status'])) {
                            if ($data['variable_status'][$id] == "0" && $draft == "true") {//DRAFT
                                $variable_status = "style='background-color: #ffffe6;'";
                                $variable_text = "<span style='color:red;font-weight:bold'>DRAFT</span><br/>";
                            } else if ($data['variable_status'][$id] == "2" && $deprecated == "true") {//DEPRECATED
                                $variable_status = "style='background-color: #ffe6e6;'";
                                $variable_text = "<span style='color:red;font-weight:bold'>DEPRECATED</span><br/>";
                            }
                        }

                        #We add the Content rows
                        $url = $module->getUrl("browser.php?&pid=".$project_id.'&tid=' . $data['record_id'] . '&vid=' . $id . '&option=variableInfo');
                        $tableHtml .= '<tr record_id="' . $record_varname_id . '" ' . $variable_status . '>
                                <td style="padding: 5px"><a href="' .$url .'" target="_blank" style="text-decoration:none">' . $record_varname . '</a></td>
                                <td style="width:160px;padding: 5px">';

                        $dataFormat = $dataformatChoices[$data['data_format'][$id]];
                        if ($data['has_codes'][$id] != '1') {
                            #do nothing
                        } else if ($data['has_codes'][$id] == '1') {
                            if (!empty($data['code_list_ref'][$id])) {
                                $RecordSetCodeList = \REDCap::getData($codeListPID, 'array', array('record_id' => $data['code_list_ref'][$id]));
                                $codeformat = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetCodeList)[0];
                                if ($codeformat['code_format'] == '1') {
                                    $codeOptions = empty($codeformat['code_list']) ? $data['code_text'][$id] : explode(" | ", $codeformat['code_list']);
                                    if (!empty($codeOptions[0])) {
                                        $dataFormat .= "<div style='padding-left:15px'>";
                                    }
                                    foreach ($codeOptions as $option) {
                                        $dataFormat .= htmlspecialchars($option) . "<br/>";
                                    }
                                    if (!empty($codeOptions[0])) {
                                        $dataFormat .= "</div>";
                                    }
                                } else if ($codeformat['code_format'] == '3') {
                                    $dataFormat = "Numeric<br/>";
                                    if (array_key_exists('code_file', $codeformat) && $data['codes_print'][$id] == '1') {
                                        $dataFormat .= "<a href='#codelist_" . $data['record_id'] . "' style='cursor:pointer;text-decoration: none'>See Code List</a><br/>";
                                        $htmlCodes .= "<table  border ='0' style='width: 100%;' record_id='" . $record_varname . "'><tr><td><a href='#' name='codelist_" . $data['record_id'] . "' style='text-decoration: none'><strong>" . $data['variable_name'][$id] . " code list:</strong></a><br/></td></tr></table>" . getHtmlCodesTable($codeformat['code_file'], $htmlCodes, $record_varname);
                                    }
                                } else if ($codeformat['code_format'] == '4') {
                                    $dataFormat = "<a href='https://bioportal.bioontology.org/ontologies/" . $codeformat['code_ontology'] . "' target='_blank'>See Ontology Link</a><br/>";
                                }
                            }
                        }

                        $description = htmlspecialchars(empty($data["description"][$id]) ? $data["description"][''] : $data["description"][$id]);
                        if (!empty($data['description_extra'][$id])) {
                            $description .= "<br/><i>" . htmlspecialchars($data['description_extra'][$id]) . "</i>";
                        }

                        $tableHtml .= $dataFormat . '</td><td style="padding: 5px">' . $variable_text . $description . '</td></tr>';
                    }
                }
                if ($found) {
                    $tableHtml .= "</table><br/>";
                    if (array_key_exists('text_bottom', $data) && !empty($data['text_bottom']) && $data['text_bottom'] != "") {
                        $tableHtml .= '<p  style="border-color: white;font-style: italic">' . $data["text_bottom"] . '</p><br/>';
                    }
                }
                if (!empty($htmlCodes))
                    $tableHtml .= $htmlCodes . '<br/>';
            }
        }
    }
    $requested_tables .= "</ol>";

    $pdf_content = array(0=>$tableHtml,1=>$requested_tables);
    return $pdf_content;
}


/**
 * Function that parses the CVS file and transforms the content into a table
 * @param $code_file, the code in the db of the csv file
 * @param $htmlCodes, the html table with the content
 * @return string, the html table with the content
 */
function getHtmlCodesTable($code_file,$htmlCodes,$id){
    $csv = parseCSVtoArray($code_file);
    if(!empty($csv)) {
        $htmlCodes = '<table border="1px" style="border-collapse: collapse;" record_id="'. $id .'">';
        foreach ($csv as $header => $content) {
            $htmlCodes .= '<tr style="border: 1px solid #000;">';
            foreach ($content as $col => $value) {
                #Convert to UTF-8 to avoid weird characters
                $value = mb_convert_encoding($value,'UTF-8','HTML-ENTITIES');
                if ($header == 0) {
                    $htmlCodes .= '<td>' . $col . '</td>';
                } else {
                    $htmlCodes .= '<td>' . $value . '</td>';
                }
            }
            $htmlCodes .= '</tr>';
        }
        $htmlCodes .= '</table><br>';
    }
    return $htmlCodes;
}

function getHtmlTableCodesTableArrayExcel($module,$dataTable){
    $data_array = array();
    $dataFormat = $module->getChoiceLabels('data_format', DES_DATAMODEL);
    foreach ($dataTable as $data) {
        if (!empty($data['record_id']) && ($data['table_status'] == "1"  || !array_key_exists('table_status',$data))) {
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
                            $RecordSetCodeList = \REDCap::getData(DES_CODELIST, 'array', array('record_id' => $data['code_list_ref'][$id]));
                            $codeformat = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetCodeList)[0];
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
                                        $data_array = \Vanderbilt\DataModelBrowserExternalModule\getHtmlCodesTableArrayExcel($data_array, $data_code_array, $codeformat['code_file']);
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

function getHtmlCodesTableArrayExcel($data_array,$data_code_array,$code_file)
{
    $csv = parseCSVtoArray($code_file);
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
 * Table list with anchor links for the PDF
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

/**
 * Function that creates a JSON copy of the Harmonist 0A: Data Model
 * @return string , the JSON
 */
function createProject0AJSON($module, $project_id){
    $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='DATAMODEL'");
    $dataModelPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

    $dataFormat = $module->getChoiceLabels('data_format', $dataModelPID);

    $RecordSetDataModel = \REDCap::getData($dataModelPID, 'array', null);
    $dataTable = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetDataModel);
    foreach ($dataTable as $data) {
        if($data['table_name'] != "") {
            $jsonVarArray['variables'] = array();
            foreach ($data['variable_order'] as $id => $value) {
                if ($data['variable_name'][$id] != '') {
                    $has_codes = 'N';
                    if ($data['has_codes'][$id] == '1')
                        $has_codes = 'Y';

                    $code_list_ref = $data['code_list_ref'][$id];
                    if ($data['code_list_ref'][$id] == '') {
                        $code_list_ref = 'NULL';
                    }

                    $jsonVarArray['variables'][trim($data['variable_name'][$id])] = array();
                    $variables_array = array(
                        "data_format" => trim($dataFormat[$data['data_format'][$id]]),
                        "variable_status" => $data['variable_status'][$id],
                        "description" => htmlentities($data['description'][$id]),
                        "variable_required" => $data['variable_required'][$id][1],
                        "variable_key" => $data['variable_key'][$id][1],
                        "variable_deprecated_d" => $data['variable_deprecated_d'][$id],
                        "variable_replacedby" => $data['variable_replacedby'][$id],
                        "variable_deprecatedinfo" => htmlentities($data['variable_deprecatedinfo'][$id]),
                        "has_codes" => $has_codes,
                        "code_list_ref" => $code_list_ref,
                        "variable_order" => $data['variable_order'][$id],
                        "variable_missingaction" => $data['variable_missingaction'][$id][1]
                    );
                    $jsonVarArray['variables'][$data['variable_name'][$id]] = $variables_array;
                }
            }
            $jsonVarArray['table_required'] = $data['table_required'][1];
            $jsonVarArray['table_category'] = $data['table_category'];
            $jsonVarArray['table_order'] = $data['table_order'];
            $jsonArray[trim($data['table_name'])] = $jsonVarArray;
        }
    }
    #we save the new JSON
    if(!empty($jsonArray)){
        $record_id = \Vanderbilt\DataModelBrowserExternalModule\saveJSONCopy('0a', $jsonArray, $module, $project_id);
    }

    return array('jsonArray' => json_encode($jsonArray,JSON_FORCE_OBJECT),'record_id' =>$record_id);
}
/**
 * Function that creates a JSON copy of the Harmonist 0A: Data Model
 * @return string, the JSON
 */
function createProject0BJSON($module, $project_id){
    $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='CODELIST'");
    $codeListPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

    $RecordSetCodeList = \REDCap::getData($codeListPID, 'array', null);
    $dataTable = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetCodeList);
    foreach ($dataTable as $data) {
        $jsonArray[$data['record_id']] = array();
        if ($data['code_format'] == '1') {
            $jsonVarContentArray  = array();
            $codeOptions = explode(" | ", $data['code_list']);
            foreach ($codeOptions as $option) {
                list($key, $val) = explode("=", htmlentities($option));
                $jsonVarContentArray[htmlentities(trim($key))] = htmlentities(trim($val));
            }
        }else if($data['code_format'] == '3'){
            $jsonVarContentArray  = array();
            $csv = parseCSVtoArray($data['code_file']);
            foreach ($csv as $header=>$content){
                if($header != 0){
                    //Convert to UTF-8 to avoid weird characters
                    $value = mb_convert_encoding(htmlentities($content['Definition']), 'UTF-8','HTML-ENTITIES');
                    $jsonVarContentArray[trim($content['Code'])] = htmlentities(trim($value));
                }
            }
        }
        $jsonArray[$data['record_id']] = $jsonVarContentArray;
    }
    #we save the new JSON
    if(!empty($jsonArray)){
        $record_id = \Vanderbilt\DataModelBrowserExternalModule\saveJSONCopy('0b', $jsonArray, $module, $project_id);
    }

    return array('jsonArray' => json_encode($jsonArray,JSON_FORCE_OBJECT),'record_id' =>$record_id);
}

/**
 * Function that creates a JSON copy of the Harmonist 0C: Data Model Metadata
 * @return string, the JSON
 */
function createProject0CJSON($module, $pidsArray){
    $dataTablerecords = \REDCap::getData($pidsArray['DATAMODELMETADATA'], 'array');
    $dataTable = ProjectData::getProjectInfoArray($dataTablerecords)[0];
    $jsonArray = array();
    $jsonArray['project_name'] = $dataTable['project_name'];
    $jsonArray['datamodel_name'] = $dataTable['datamodel_name'];
    $jsonArray['datamodel_abbrev'] = $dataTable['datamodel_abbrev'];
    $jsonArray['datamodel_url_y'] = $dataTable['datamodel_url_y'];
    $jsonArray['datamodel_url'] = $dataTable['datamodel_url'];
    $jsonArray['hub_y'] = $dataTable['hub_y'];
    $jsonArray['sd_ext'] = $dataTable['sd_ext'];
    $jsonArray['ed_ext'] = $dataTable['ed_ext'];
    $jsonArray['date_approx_y'] = $dataTable['date_approx_y'];
    $jsonArray['date_approx'] = $dataTable['date_approx'];
    $jsonArray['n_age_groups'] = $dataTable['n_age_groups'];
    $jsonArray['age_1_lower'] = $dataTable['age_1_lower'];
    $jsonArray['age_1_upper'] = $dataTable['age_1_upper'];
    $jsonArray['age_2_lower'] = $dataTable['age_2_lower'];
    $jsonArray['age_2_upper'] = $dataTable['age_2_upper'];
    $jsonArray['age_3_lower'] = $dataTable['age_3_lower'];
    $jsonArray['age_3_upper'] = $dataTable['age_3_upper'];
    $jsonArray['age_4_lower'] = $dataTable['age_4_lower'];
    $jsonArray['age_4_upper'] = $dataTable['age_4_upper'];
    $jsonArray['age_5_lower'] = $dataTable['age_5_lower'];
    $jsonArray['age_5_upper'] = $dataTable['age_5_upper'];
    $jsonArray['age_6_lower'] = $dataTable['age_6_lower'];
    $jsonArray['age_6_upper'] = $dataTable['age_6_upper'];

    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableJsonName($pidsArray['DATAMODEL'], $dataTable['index_tablename'],'index_tablename',$jsonArray);
    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableJsonName($pidsArray['DATAMODEL'], $dataTable['group_tablename'],'group_tablename',$jsonArray);
    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableJsonName($pidsArray['DATAMODEL'], $dataTable['height_table'],'height_table',$jsonArray);

    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['patient_id_var'],'patient_id_var',$jsonArray);
    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['default_group_var'],'default_group_var',$jsonArray);
    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['birthdate_var'],'birthdate_var',$jsonArray);
    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['death_date_var'],'death_date_var',$jsonArray);
    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['age_date_var'],'age_date_var',$jsonArray);
    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['enrol_date_var'],'enrol_date_var',$jsonArray);
    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['height_var'],'height_var',$jsonArray);
    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['height_date'],'height_date',$jsonArray);
    $jsonArray = \Vanderbilt\DataModelBrowserExternalModule\getTableVariableJsonName($pidsArray['DATAMODEL'], $dataTable['height_units'],'height_units',$jsonArray);

    #save files data
    $jsonArray['project_logo_100_40'] = base64_encode(file_get_contents(\Vanderbilt\DataModelBrowserExternalModule\getFile($this, $dataTable['project_logo_100_40'],'pdf')));
    $jsonArray['project_logo_50_20'] = base64_encode(file_get_contents(\Vanderbilt\DataModelBrowserExternalModule\getFile($this, $dataTable['project_logo_50_20'],'pdf')));
    $jsonArray['sample_dataset'] = base64_encode(file_get_contents(\Vanderbilt\DataModelBrowserExternalModule\getFile($this, $dataTable['sample_dataset'],'pdf')));

    #we save the new JSON
    if(!empty($jsonArray)){
        $record_id = \Vanderbilt\DataModelBrowserExternalModule\saveJSONCopy('0c', $jsonArray, $module, $pidsArray['JSONCOPY']);
    }

    return array('jsonArray' => json_encode($jsonArray,JSON_FORCE_OBJECT),'record_id' =>$record_id);
}

function getTableVariableJsonName($project_id,$data,$varName,$jsonArray){
    if($data != ""){
        $variable = explode(":",$data);
        $dataTableDataModelRecords = \REDCap::getData($project_id, 'array',array('record_id' => $variable[0]));
        $tableData = ProjectData::getProjectInfoArray($dataTableDataModelRecords)[0];
        if($variable[1] == "1"){
            $variable[1] = "";
        }
        $jsonArray[$varName] = $tableData['table_name'].":".$tableData['variable_name'][$variable[1]];
    }
    return $jsonArray;
}

function getTableJsonName($project_id,$data,$varName,$jsonArray){
    if($data != ""){
        $dataTableDataModelRecords = \REDCap::getData($project_id, 'array',array('record_id' => $data));
        $tableData = ProjectData::getProjectInfoArray($dataTableDataModelRecords)[0];
        $jsonArray[$varName] = $tableData['table_name'];
    }
    return $jsonArray;
}

function saveJSONCopy($type, $jsonArray, $module, $project_id){
    $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='JSONCOPY'");
    $jsoncopyPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];
    #create and save file with json
    $filename = "jsoncopy_file_".$type."_".date("YmdsH").".txt";
    $storedName = date("YmdsH")."_pid".$jsoncopyPID."_".\Vanderbilt\DataModelBrowserExternalModule\getRandomIdentifier(6).".txt";

    $file = fopen(EDOC_PATH.$storedName,"wb");
    fwrite($file,json_encode($jsonArray,JSON_FORCE_OBJECT));
    fclose($file);

    $output = file_get_contents(EDOC_PATH.$storedName);
    $filesize = file_put_contents(EDOC_PATH.$storedName, $output);

    //Save document on DB
    $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
        [$storedName,$filename,$filesize,'txt','application/octet-stream','0',$jsoncopyPID,date('Y-m-d h:i:s')]);
    $docId = db_insert_id();

    #we check the version
    $data = \Vanderbilt\DataModelBrowserExternalModule\returnJSONCopyVersion($type, $jsoncopyPID);
    $lastversion = $data['lastversion'] + 1;
    #save the project
    $Proj = new \Project($jsoncopyPID);
    $event_id = $Proj->firstEventId;
    $record_id = $module->framework->addAutoNumberedRecord($jsoncopyPID);
    $json = json_encode(array(array('record_id'=>$record_id, 'type'=>$type,'jsoncopy_file'=>$docId,'json_copy_update_d'=>date("Y-m-d H:i:s"),"version" => $lastversion)));
    $results = \REDCap::saveData($jsoncopyPID, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    \Records::addRecordToRecordListCache($jsoncopyPID, $record_id,$event_id);

    return $record_id;
}
/**
 * Function that returns the version of the JSON Copy project
 * @param $type, the project type
 * @return int|string, the version
 */
function returnJSONCopyVersion($type, $jsoncopyID){
    $RecordSetJsonCopy = \REDCap::getData($jsoncopyID, 'array', null,null,null,null,false,false,false,"[type]='".$type."'");
    $datatype = ProjectData::getProjectInfoArray($RecordSetJsonCopy)[0];
    $lastversion = 0;
    $record_id = 0;
    $data = array();
    if(empty($datatype)){
        $lastversion = '0';
    }else{
        #we get the last version
        foreach($datatype as $data)
        {
            if($data['version'] > $lastversion)
            {
                $lastversion = $data['version'];
                $record_id = $data['record_id'];
            }
        }
    }
    $data['lastversion'] = $lastversion;
    $data['id'] = $record_id;

    return $data;
}
function isUserExpiredOrSuspended($module,$username,$field){
    $result = $module->query("SELECT * FROM redcap_user_information WHERE username = ? AND ".$field." IS NOT NULL",[$username]);
    if (db_num_rows($result) > 0) {
        return true;
    }
    return false;
}
/**
 * Function that searches the file name in the database, parses it and returns an array with the content
 * @param $DocID, the id of the document
 * @return array, the generated array with the data
 */
function parseCSVtoArray($DocID){
    $sqlTableCSV = "SELECT * FROM `redcap_edocs_metadata` WHERE doc_id = '".$DocID."'";
    $qTableCSV = db_query($sqlTableCSV);
    $csv = array();
    while ($rowTableCSV = db_fetch_assoc($qTableCSV)) {
        $csv = \Vanderbilt\DataModelBrowserExternalModule\createArrayFromCSV(EDOC_PATH,$rowTableCSV['stored_name']);
    }
    return $csv;
}
/**
 * Function that parses de CSV file to an Array
 * @param $filepath, the path of the file
 * @param $filename, the file name
 * @return array, the generated array with the CSV data
 */
function createArrayFromCSV($filepath,$filename, $addHeader = false){
    $file = $filepath.$filename;
    $csv = array_map('str_getcsv', file($file));
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
function parseCSVtoLink($DocID){
    $sqlTableCSV = "SELECT * FROM `redcap_edocs_metadata` WHERE doc_id = '".$DocID."'";
    $qTableCSV = db_query($sqlTableCSV);
    $link = "";
    while ($rowTableCSV = db_fetch_assoc($qTableCSV)) {
        $link = "sname=" . $rowTableCSV['stored_name'] . "&file=" . $rowTableCSV['doc_name'];
    }
    return $link;
}

function getRandomIdentifier($length = 6) {
    $output = "";
    $startNum = pow(32,5) + 1;
    $endNum = pow(32,6);
    while($length > 0) {

        # Generate a number between 32^5 and 32^6, then convert to a 6 digit string
        $randNum = mt_rand($startNum,$endNum);
        $randAlphaNum = numberToBase($randNum,32);

        if($length >= 6) {
            $output .= $randAlphaNum;
        }
        else {
            $output .= substr($randAlphaNum,0,$length);
        }
        $length -= 6;
    }

    return $output;
}

function numberToBase($number, $base) {
    $newString = "";
    while($number > 0) {
        $lastDigit = $number % $base;
        $newString = convertDigit($lastDigit, $base).$newString;
        $number -= $lastDigit;
        $number /= $base;
    }

    return $newString;
}

function convertDigit($number, $base) {
    if($base > 192) {
        chr($number);
    }
    else if($base == 32) {
        $stringArray = "ABCDEFGHJLKMNPQRSTUVWXYZ23456789";

        return substr($stringArray,$number,1);
    }
    else {
        if($number < 192) {
            return chr($number + 32);
        }
        else {
            return "";
        }
    }
}

function getFile($module, $edoc, $type){
    $file = "#";
    if($edoc != ""){
        $q = $module->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
        while ($row = $q->fetch_assoc()) {
            $url = 'downloadFile.php?sname=' . $row['stored_name'] . '&file=' . urlencode($row['doc_name']);
            $base64 = base64_encode(file_get_contents(EDOC_PATH.$row['stored_name']));
            if($type == "img"){
                $file = '<br/><div class="inside-panel-content"><img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="display: block; margin: 0 auto;"></div>';
            }else if($type == "logo"){
                $file = '<img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="padding-bottom: 30px;width: 450px;">';
            }else if($type == "src") {
                $file = 'data:' . $row['mime_type'] . ';base64,' . $base64;
            }else if($type == "pdf") {
                $file = EDOC_PATH.$row['stored_name'];
            }else if($type == "imgpdf"){
                $file = '<div style="max-width: 450px;height: 500px;"><img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="display: block; margin: 0 auto;width:450px;height: 450px;"></div>';
            }else if($type == "url") {
                $file = $module->getUrl($url);
            }else{
                $file = '<br/><div class="inside-panel-content"><a href="'.$module->getUrl($url,true).'" target="_blank"><span class="fa fa-file-o"></span> ' . $row['doc_name'] . '</a></div>';
            }
        }
    }
    return $file;
}
?>