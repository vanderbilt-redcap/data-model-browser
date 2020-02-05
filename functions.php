<?php

function printFile($module,$edoc, $type){
    $file = "";
    if($edoc != ""){
        $q = $module->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
        while ($row = $q->fetch_assoc()) {
            $url = 'downloadFile.php?sname=' . $row['stored_name'] . '&file=' . urlencode($row['doc_name']);
            $base64 = base64_encode(file_get_contents(EDOC_PATH.$row['stored_name']));
            if($type == "img"){
                $file = '<br/><div class="inside-panel-content"><img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="display: block; margin: 0 auto;"></div>';
            }else if($type == "logo"){
                $file = '<img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="padding-bottom: 30px;width: 450px;">';
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
 * Function that returns the info array from a specific project
 * @param $project, the project id
 * @param $info_array, array that contains the conditionals
 * @param string $type, if its single or a multidimensional array
 * @return array, the info array
 */
function getProjectInfoArray($project, $info_array="", $type="", $fieldName="", $order=false){
//    $Project = new \Plugin\Project($project);
//    if(empty($info_array)){
//        $RecordSet = new \Plugin\RecordSet($Project, array(\Plugin\RecordSet::getKeyComparatorPair($Project->getFirstFieldName(),"!=") => ""));
//    }else{
//        $RecordSet = new \Plugin\RecordSet($Project, $info_array);
//    }
//
//    if(!empty($fieldName)) {
//        #Order by fieldname
//        $RecordSet->mergeSortRecords($fieldName,$order);
//    }
//
//    if($type == "simple"){
//        $wgroup = $RecordSet->getDetails()[0];
//    }else{
//        $wgroup = $RecordSet->getDetails();
//    }

    $array = array();
    $records = \REDCap::getData($project,'array',$info_array);
    foreach ($records as $event) {
        foreach ($event as $data) {
            array_push($array,$data);
        }
    }

    return $array;
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
    $data_ditionary = $module->framework->excelToArray($module->framework->getModulePath()."csv\DATAMODEL_data_dictionary.csv");

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
//                                        print_array($data_ditionary['A'][$key]);
//                                        print_array($array[$index][$data_ditionary['A'][$key]]);
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
        asort($record['variable_order']);

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


/**
 * Function that creates HTML tables with the Tables and Variables information to print on the PDF after the information has been selected
 * @param $dataTable, Tables and Variables information
 * @param $fieldsSelected, the selected fields
 * @return string, the html content
 */
function generateTablesHTML_pdf($dataTable,$draft,$deprecated){
    $tableHtml = "";
    $requested_tables = "<ol>";
    $table_counter = 0;
    foreach ($dataTable as $data) {
        if (!empty($data['record_id'])) {
            $found = false;
            $htmlCodes = '';
            if($data['table_status'] == "1" || !array_key_exists("table_status",$data) || ($data['table_status'] == "2" && $deprecated == "true") || ($data['table_status'] == "0" && $draft == "true")) {
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

                        $htmlHeader = $breakLine . '<p style="' . $table_draft . '"><span style="font-size:16px"><strong><a href="' . APP_PATH_WEBROOT_FULL . '/plugins/des/index.php?tid=' . $data['record_id'] . '&page=variables" name="anchor_' . $data['record_id'] . '" target="_blank" style="text-decoration:none">' . $data["table_name"] . '</a></span> ' . $table_draft_text . '</strong> - ' . $data['table_definition'] . '</p>';
                        if (array_key_exists('text_top', $data) && !empty($data['text_top']) && $data['text_top'] != "") {
                            $htmlHeader .= '<div  style="border-color: white;font-style: italic">' . $data["text_top"] . '</div>';
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
                        $tableHtml .= '<tr record_id="' . $record_varname_id . '" ' . $variable_status . '>
                                <td style="padding: 5px"><a href="' . APP_PATH_WEBROOT_FULL . '/plugins/des/index.php?tid=' . $data['record_id'] . '&vid=' . $id . '&page=variableInfo" target="_blank" style="text-decoration:none">' . $record_varname . '</a></td>
                                <td style="width:160px;padding: 5px">';

                        $dataFormat = $dataTable['data_format_label'][$data['data_format'][$id]];
                        if ($data['has_codes'][$id] == '0') {
                            if (!empty($data['code_text'][$id])) {
                                $dataFormat .= "<br/>" . $data['code_text'][$id];
                            }
                        } else if ($data['has_codes'][$id] == '1') {
                            if (!empty($data['code_list_ref'][$id])) {
                                $codeformat = getProjectInfoArray(DES_CODELIST, array('record_id' => $data['code_list_ref'][$id]), 'simple');

                                if ($codeformat['code_format'] == '1') {
                                    $codeOptions = empty($codeformat['code_list']) ? $data['code_text'][$id] : explode(" | ", $codeformat['code_list']);
                                    if (!empty($codeOptions[0])) {
                                        $dataFormat .= "<div style='padding-left:15px'>";
                                    }
                                    foreach ($codeOptions as $option) {
                                        $dataFormat .= $option . "<br/>";
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

                        $description = empty($data["description"][$id]) ? $data["description"][''] : $data["description"][$id];
                        if (!empty($data['description_extra'][$id])) {
                            $description .= "<br/><i>" . $data['description_extra'][$id] . "</i>";
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

function getHtmlTableCodesTableArrayExcel($dataTable){
    $data_array = array();
    $ProjectTable = new \Plugin\Project(DES_DATAMODEL);
    $dataFormat = \Plugin\Project::convertEnumToArray($ProjectTable->getMetadata('data_format')->getElementEnum());
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
                            $codeformat = getProjectInfoArray(DES_CODELIST, array('record_id' => $data['code_list_ref'][$id]), 'simple');
                            if ($codeformat['code_format'] == '1') {
                                $codeOptions = empty($codeformat['code_list']) ? $data['code_text'][$id] : explode(" | ", $codeformat['code_list']);
                                foreach ($codeOptions as $option) {
                                    $var_codes = preg_split("/((?<!['\"])=(?!['\"]))/", $option);
                                    $data_code_array[2] = trim($var_codes[0]);
                                    $data_code_array[3] = trim($var_codes[1]);
                                    array_push($data_array, $data_code_array);
                                }
                            } else {
                                if ($codeformat['code_format'] == '3') {
                                    if (array_key_exists('code_file', $codeformat) && $data['codes_print'][$id] == '1') {
                                        $data_array = getHtmlCodesTableArrayExcel($data_array, $data_code_array, $codeformat['code_file']);
                                    }
                                } else if ($codeformat['code_format'] == '4') {
                                    $data_code_array[2] = 'https://bioportal.bioontology.org/ontologies/' . $codeformat['code_ontology'];
                                    array_push($data_array, $data_code_array);
                                }
                            }
                        }
                    } else if (!empty($data['code_text'][$id])) {
                        $data_code_array[2] = $dataFormat[$data['data_format'][$id]];
                        $data_code_array[3] = $description;
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

function getImageToDisplay($edoc){
    $img_logo = '';
    if($edoc != ''){
        $sql = "SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id='" . db_escape($edoc)."'";
        $q = db_query($sql);

        if ($error = db_error()) {
            die($sql . ': ' . $error);
        }

        while ($row = db_fetch_assoc($q)) {
            $img_logo = 'options/downloadFile.php?sname=' . $row['stored_name'] . '&file=' . $row['doc_name'];
        }
    }

    return $img_logo;
}

function loadImg($imgEdoc,$secret_key,$secret_iv,$default,$option=""){
    $img = $default;
    if($imgEdoc != ''){
        $sql = "SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id='" . db_escape($imgEdoc)."'";
        $q = db_query($sql);

        while ($row = db_fetch_assoc($q)) {
            if($option == 'pdf'){
                $img = EDOC_PATH.$row['stored_name'];
            }else{
                $img = 'downloadFile.php?sname='.$row['stored_name']."&file=". urlencode($row['doc_name']);
            }
        }
    }
    return $img;
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

function hasJsoncopyBeenUpdated($type,$settings){
    if(ENVIRONMENT == "DEV"){
        $sqltype = "SELECT MAX(record) as record FROM redcap_data WHERE project_id='".db_escape(DES_JSONCOPY)."' AND field_name='".db_escape('type')."' and value='".db_escape($type)."' order by record";
    }else{
        $sqltype = "SELECT MAX(CAST(record AS Int)) as record FROM redcap_data WHERE project_id='".db_escape(DES_JSONCOPY)."' AND field_name='".db_escape('type')."' and value='".db_escape($type)."' order by record";
    }

    $qtype = db_query($sqltype);

    if ($error = db_error()) {
        die($sqltype . ': ' . $error);
    }

    $rowtype = db_fetch_assoc($qtype);
    $projectCopy = new \Plugin\Project(DES_JSONCOPY);
    $RecordSetCopy = new \Plugin\RecordSet($projectCopy, array('record_id' => $rowtype['record']));
    $jsoncocpy = $RecordSetCopy->getDetails()[0];
    $today = date("Y-m-d");
    if($jsoncocpy["jsoncopy_file"] != "" && strtotime(date("Y-m-d",strtotime($jsoncocpy['json_copy_update_d']))) == strtotime($today)){
        return true;
    }else if(strtotime(date("Y-m-d",strtotime($jsoncocpy['json_copy_update_d']))) == "" || !array_key_exists('json_copy_update_d',$jsoncocpy) || !array_key_exists('des_pdf',$settings) || $settings['des_pdf'] == ""){
        $record = \Plugin\Record::createRecordFromId($projectCopy,$rowtype['record']);
        $record->updateDetails(array('json_copy_update_d' => date("Y-m-d H:i:s")),true);
        return true;
    }
    return false;
}

function createAndSavePDFCron($settings,$secret_key,$secret_iv){
   $dataTable = getTablesInfo($module,DES_DATAMODEL);

    if(!empty($dataTable)) {
        $tableHtml = generateTablesHTML_pdf($dataTable,false,false);
    }
    #FIRST PAGE
    $first_page = "<tr><td align='center'>";
    $first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>".$settings['des_pdf_title']."</span></p>";
    $first_page .= "<p><span style='font-size: 16pt;font-weight: bold;'>".$settings['des_pdf_subtitle']."</span></p><br/>";
    $first_page .= "<p><span style='font-size: 14pt;font-weight: bold;'>Version: ".date('d F Y')."</span></p><br/>";
    $first_page .= "<p><span style='font-size: 14pt;font-weight: bold;'>".$settings['des_pdf_text']."</span></p><br/>";
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
        ."<p><span style='font-size:16pt'><strong>DES Tables</strong></span></p>"
        .$tableHtml[0]
        ."</div></div>"
        . "</body></html>";

   $filename = $settings['des_wkname']."_DES_".date("Y-m-d_hi",time());
    //SAVE PDF ON DB
    $reportHash = $filename;
    $storedName = md5($reportHash);

    //DOMPDF
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html_pdf);
    $dompdf->setPaper('A4', 'portrait');
    ob_start();
    $dompdf->render();
    //#Download option
    $output = $dompdf->output();
    $filesize = file_put_contents(EDOC_PATH.$storedName, $output);

    //Save document on DB
    $sql = "INSERT INTO redcap_edocs_metadata (stored_name,mime_type,doc_name,doc_size,file_extension,gzipped,project_id,stored_date) VALUES
          ('".db_escape($storedName)."','".db_escape('application/octet-stream')."','".db_escape($reportHash.".pdf")."',".db_escape($filesize).",'".db_escape('.pdf')."','".db_escape('0')."','".db_escape(DES_SETTINGS)."','".db_escape(date('Y-m-d h:i:s'))."')";
    db_query($sql);
    $docId = db_insert_id();


    //Add document DB ID to project
    $project = new \Plugin\Project(DES_SETTINGS);
    $record = \Plugin\Record::createRecordFromId($project,1);
    $record->updateDetails(array('des_update_d' => date("Y-m-d H:i:s")),true);
    $record->updateDetails(array('des_pdf' => $docId),true);
    \Records::addRecordToRecordListCache($project->getProjectId(), $record->getId(),$project->getArmNum());

    if($settings['des_pdf_notification_email'] != "") {
        $link = APP_PATH_PLUGIN."/downloadFile.php?code=".getCrypt("sname=".$storedName."&file=". $filename.".pdf&edoc=".$docId,'e',$secret_key,$secret_iv);
        $goto = APP_PATH_WEBROOT_ALL . "DataEntry/index.php?pid=".DES_SETTINGS."&page=pdf&id=1";

        $subject = "New DES PDF Generated";
        $message = "<div>Changes have been detected and a new PDF has been generated.</div><br/>".
            "<div>You can <a href='".$link."'>download the pdf</a> or <a href='".$goto."'>go to the settings project</a>.</div><br/>";

        $environment = "";
        if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
            $environment = " ".ENVIRONMENT;
        }

        $emails = explode(';', $settings['des_pdf_notification_email']);
        foreach ($emails as $email) {
            \REDCap::email($email, $settings['accesslink_sender_email'], $subject.$environment, $message,"","",$settings['accesslink_sender_name']);
        }
    }
}

function createAndSaveJSONCron($settings,$secret_key,$secret_iv){
    $dataTable = getProjectInfoArray(DES_DATAMODEL);
    $ProjectTable = new \Plugin\Project(DES_DATAMODEL);
    $dataFormat = \Plugin\Project::convertEnumToArray($ProjectTable->getMetadata('data_format')->getElementEnum());
    foreach ($dataTable as $data) {
        $jsonVarArrayAux = array();
        foreach ($data['variable_order'] as $id => $value) {
            if($data['variable_name'][$id] != ''){
                $url = 'index.php?pid=' . DES_DATAMODEL . '&tid=' . $data['record_id'] . '&vid=' . $id . '&page=variableInfo';

                $jsonVarArrayAux[trim($data['variable_name'][$id])] = array();
                $variables_array  = array(
                    "instance" => $id,
                    "description" => $data['description'][$id],
                    "description_extra" => $data['description_extra'][$id],
                    "code_list_ref" => $data['code_list_ref'][$id],
                    "data_format" => trim($dataFormat[$data['data_format'][$id]]),
                    "code_text" => $data['code_text'][$id],
                    "variable_link" => $url
                );
                $jsonVarArrayAux[$data['variable_name'][$id]] = $variables_array;
            }
        }
        $jsonVarArray = $jsonVarArrayAux;
        $urltid = 'index.php?pid=' . DES_DATAMODEL . '&tid=' . $data['record_id'] . '&page=variables';
        $jsonVarArray['table_link'] = $urltid;
        $jsonArray[trim($data['table_name'])] = $jsonVarArray;
    }

    #we save the new JSON
    if(!empty($jsonArray)){
        saveJSONCopy($jsonArray);
    }
}

function saveJSONCopy($jsonArray){
    #create and save file with json
    $filename = "jsoncopy_file_variable_search_".date("YmdsH").".txt";
    $storedName = date("YmdsH")."_pid".DES_JSONCOPY."_".getRandomIdentifier(6).".txt";

    $file = fopen(EDOC_PATH.$storedName,"wb");
    fwrite($file,json_encode($jsonArray,JSON_FORCE_OBJECT));
    fclose($file);

    $output = file_get_contents(EDOC_PATH.$storedName);
    $filesize = file_put_contents(EDOC_PATH.$storedName, $output);

    $sql = "INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES ('".db_escape($storedName)."','".db_escape($filename)."','".db_escape($filesize)."','".db_escape('txt')."','".db_escape('application/octet-stream')."','".db_escape('0')."','".db_escape(DES_SETTINGS)."','".db_escape(date('Y-m-d h:i:s'))."')";
    $q = db_query($sql);
    $docId = db_insert_id();

    #save the project
    $project = new \Plugin\Project(DES_SETTINGS);
    $record = \Plugin\Record::createRecordFromId($project,1);
    $record->updateDetails(array('des_variable_search' => $docId),true);

    \Records::addRecordToRecordListCache($project->getProjectId(), $record->getId(),$project->getArmNum());
}

function getFileLink($edoc, $secret_key,$secret_iv){
    $file_row = '';
    if($edoc != "") {
        $sql = "SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id='" . db_escape($edoc)."'";
        $q = db_query($sql);

        if ($error = db_error()) {
            die($sql . ': ' . $error);
        }

        while ($row = db_fetch_assoc($q)) {
            $file_row = APP_PATH_PLUGIN."/downloadFile.php?code=" . getCrypt("sname=" . $row['stored_name'] . "&file=" . urlencode($row['doc_name']) . "&edoc=" . $edoc , 'e', $secret_key, $secret_iv);
        }
    }
    return $file_row;
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
        $csv = createArrayFromCSV(EDOC_PATH,$rowTableCSV['stored_name']);
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
?>