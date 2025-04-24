<?php
namespace Vanderbilt\DataModelBrowserExternalModule;


class JsonPDF
{
    /**
     * Function that creates HTML tables with the Tables and Variables information to print on the JsonPDF after the information has been selected
     * @param $dataTable, Tables and Variables information
     * @param $fieldsSelected, the selected fields
     * @return string, the html content
     */
    public static function generateTablesHTML_pdf($module,$dataTable,$draft,$deprecated, $project_id, $dataModelPID){
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
                                    $variable_text = "<span style='color:#ff0000;font-weight:bold'>DRAFT</span><br/>";
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
                                            $htmlCodes .= "<table  border ='0' style='width: 100%;' record_id='" . $record_varname . "'><tr><td><a href='#' name='codelist_" . $data['record_id'] . "' style='text-decoration: none'><strong>" . $data['variable_name'][$id] . " code list:</strong></a><br/></td></tr></table>" . self::getHtmlCodesTable($module, $codeformat['code_file'], $htmlCodes, $record_varname);
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
    public static function getHtmlCodesTable($module,$code_file,$htmlCodes,$id){
        $csv = self::parseCSVtoArray($module,$code_file);
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

    /**
     * Function that searches the file name in the database, parses it and returns an array with the content
     * @param $DocID, the id of the document
     * @return array, the generated array with the data
     */
    public static function parseCSVtoArray($module,$DocID){
        $q = $module->query("SELECT * FROM `redcap_edocs_metadata` WHERE doc_id = ?",[$DocID]);
        $csv = array();
        while ($rowTableCSV = $q->fetch_assoc()) {
            $csv = self::createArrayFromCSV($module->framework->getSafePath($rowTableCSV['stored_name'], EDOC_PATH),$rowTableCSV['stored_name']);
        }
        return $csv;
    }

    /**
     * Function that parses de CSV file to an Array
     * @param $filepath, the path of the file
     * @param $filename, the file name
     * @return array, the generated array with the CSV data
     */
    public static function createArrayFromCSV($filepath,$filename, $addHeader = false){
        $csv = array_map('str_getcsv', file($filepath));
        #Remove hidden characters in file
        $csv[0][0] = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv[0][0]));
        $csv[0][1] = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv[0][1]));
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
     * Function that returns the version of the JSON Copy project
     * @param $type, the project type
     * @return int|string, the version
     */
    public static function returnJSONCopyVersion($type, $jsoncopyID){
        $RecordSetJsonCopy = \REDCap::getData($jsoncopyID, 'array', null,null,null,null,false,false,false,"[type]='".$type."'");
        $datatype = ProjectData::getProjectInfoArray($RecordSetJsonCopy);

        $record_id = 0;
        $data = array();
        if(empty($datatype) || $datatype == null){
            $lastversion = 0;
        }else{
            $lastversion = 0;
            foreach ($datatype as $version_data){
                if($lastversion < $version_data['version']){
                    $lastversion = $version_data['version'];
                    $record_id = $version_data['record_id'];
                }
            }
        }
        $data['lastversion'] = $lastversion;
        $data['id'] = $record_id;

        return $data;
    }

    public static function saveJSONCopy($type, $jsonArray, $module, $project_id){
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='JSONCOPY'");
        $jsoncopyPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        #create and save file with json
        $filename = "jsoncopy_file_".$type."_".date("YmdsH").".txt";
        $storedName = date("YmdsH")."_pid".$jsoncopyPID."_".self::getRandomIdentifier(6).".txt";

        $file = fopen($module->framework->getSafePath($storedName, EDOC_PATH),"wb");
        fwrite($file,json_encode($jsonArray,JSON_FORCE_OBJECT));
        fclose($file);

        $output = file_get_contents($module->framework->getSafePath($storedName, EDOC_PATH));
        $filesize = file_put_contents($module->framework->getSafePath($storedName, EDOC_PATH), $output);

        //Save document on DB
        $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
            [$storedName,$filename,$filesize,'txt','application/octet-stream','0',$jsoncopyPID,date('Y-m-d h:i:s')]);
        $docId = db_insert_id();

        #we check the version
        $data = self::returnJSONCopyVersion($type, $jsoncopyPID);
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
     * Function that creates a JSON copy of the Harmonist 0A: Data Model
     * @return string , the JSON
     */
    public static function createProject0AJSON($module, $project_id, $save=true){
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
                        $variables_array  = array(
                            "data_format" => trim($dataFormat[$data['data_format'][$id]]),
                            "variable_status" => $data['variable_status'][$id],
                            "description" => $data['description'][$id],
                            "variable_required" => $data['variable_required'][$id][1],
                            "variable_key" => $data['variable_key'][$id][1],
                            "variable_deprecated_d" => $module->arrayKeyExistsReturnValue($data,'variable_deprecated_d',$id),
                            "variable_replacedby" => $module->arrayKeyExistsReturnValue($data,'variable_replacedby',$id),
                            "variable_splitdate_m" => $module->arrayKeyExistsReturnValue($data,'variable_splitdate_m',$id),
                            "variable_splitdate_d" => $module->arrayKeyExistsReturnValue($data,'variable_splitdate_d',$id),
                            "variable_splitdate_y" => $data['variable_splitdate_y'][$id][1],
                            "variable_deprecatedinfo" => $module->arrayKeyExistsReturnValue($data,'variable_deprecatedinfo',$id),
                            "has_codes" => $has_codes,
                            "code_list_ref" => $code_list_ref,
                            "variable_order" => $module->arrayKeyExistsReturnValue($data,'variable_order',$id),
                            "variable_missingaction" => $data['variable_missingaction'][$id][1],
                            "variable_reportcomplete" => $data['variable_reportcomplete'][$id][1],
                            "variable_indexid" => $data['variable_indexid'][$id][1]
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
        if(!empty($jsonArray) && $save){
            $record_id = self::saveJSONCopy('0a', $jsonArray, $module, $project_id);
        }
        return array('jsonArray' => json_encode($jsonArray,JSON_FORCE_OBJECT),'record_id' =>$record_id);
    }
    /**
     * Function that creates a JSON copy of the Harmonist 0A: Data Model
     * @return string, the JSON
     */
    public static function createProject0BJSON($module, $project_id, $save=true){
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
                    list($key, $val) = explode("=", $option);
                    $jsonVarContentArray[trim($key)] = trim($val);
                }
            }else if($data['code_format'] == '3'){
                $jsonVarContentArray  = array();
                $csv = self::parseCSVtoArray($module, $data['code_file']);
                foreach ($csv as $header=>$content){
                    if($header != 0){
                        //Convert to UTF-8 to avoid weird characters
                        $value = mb_convert_encoding($content['Definition'], 'UTF-8','HTML-ENTITIES');
                        $code = trim(mb_convert_encoding($content['Code'], 'UTF-8','HTML-ENTITIES'));
                        $jsonVarContentArray[$code] = trim($value);
                    }
                }
            }
            $jsonArray[$data['record_id']] = $jsonVarContentArray;
        }

        #we save the new JSON
        if(!empty($jsonArray) && $save){
            $record_id = self::saveJSONCopy('0b', $jsonArray, $module, $project_id);
        }
        return array('jsonArray' => json_encode($jsonArray,JSON_FORCE_OBJECT),'record_id' =>$record_id);
    }

    /**
     * Function that creates a JSON copy of the Harmonist 0C: Data Model Metadata
     * @return string, the JSON
     */
    public static function createProject0CJSON($module, $project_id, $save=true){
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='DATAMODELMETADATA'");
        $dataModelMetadataPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        $jsonArray = array();
        $record_id = "";
        if($dataModelMetadataPID != "") {
            $dataTablerecords = \REDCap::getData($dataModelMetadataPID, 'array');
            $dataTable = ProjectData::getProjectInfoArray($dataTablerecords)[0];

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

            $RecordSetConstants = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[project_constant]='DATAMODEL'");
            $dataModelPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

            $jsonArray = self::getTableJsonName($dataModelPID, $dataTable['index_tablename'], 'index_tablename', $jsonArray);
            $jsonArray = self::getTableJsonName($dataModelPID, $dataTable['group_tablename'], 'group_tablename', $jsonArray);
            $jsonArray = self::getTableJsonName($dataModelPID, $dataTable['height_table'], 'height_table', $jsonArray);

            $jsonArray = self::getTableVariableJsonName($dataModelPID, $dataTable['patient_id_var'], 'patient_id_var', $jsonArray);
            $jsonArray = self::getTableVariableJsonName($dataModelPID, $dataTable['default_group_var'], 'default_group_var', $jsonArray);
            $jsonArray = self::getTableVariableJsonName($dataModelPID, $dataTable['birthdate_var'], 'birthdate_var', $jsonArray);
            $jsonArray = self::getTableVariableJsonName($dataModelPID, $dataTable['death_date_var'], 'death_date_var', $jsonArray);
            $jsonArray = self::getTableVariableJsonName($dataModelPID, $dataTable['age_date_var'], 'age_date_var', $jsonArray);
            $jsonArray = self::getTableVariableJsonName($dataModelPID, $dataTable['enrol_date_var'], 'enrol_date_var', $jsonArray);
            $jsonArray = self::getTableVariableJsonName($dataModelPID, $dataTable['height_var'], 'height_var', $jsonArray);
            $jsonArray = self::getTableVariableJsonName($dataModelPID, $dataTable['height_date'], 'height_date', $jsonArray);
            $jsonArray = self::getTableVariableJsonName($dataModelPID, $dataTable['height_units'], 'height_units', $jsonArray);

            #save files data
            $jsonArray['project_logo_100_40'] = base64_encode(file_get_contents(self::getFile($module, $dataTable['project_logo_100_40'], 'pdf')));
            $jsonArray['project_logo_50_20'] = base64_encode(file_get_contents(self::getFile($module, $dataTable['project_logo_50_20'], 'pdf')));
            $jsonArray['sample_dataset'] = base64_encode(file_get_contents(self::getFile($module, $dataTable['sample_dataset'], 'pdf')));

            #we save the new JSON
            if (!empty($jsonArray) && $save) {
                $record_id = self::saveJSONCopy('0c', $jsonArray, $module, $project_id);
            }
        }

        return array('jsonArray' => json_encode($jsonArray,JSON_FORCE_OBJECT),'record_id' =>$record_id);
    }

    public static function getTableVariableJsonName($project_id,$data,$varName,$jsonArray){
        if($data != ""){
            $variable = explode(":",$data);
            $dataTableDataModelRecords = \REDCap::getData($project_id, 'array',array('record_id' => $variable[0]));
            $tableData = ProjectData::getProjectInfoArrayRepeatingInstruments($dataTableDataModelRecords);
            $jsonArray[$varName] = $tableData[0]['table_name'].":".$tableData[0]['variable_name'][$variable[1]];
        }
        return $jsonArray;
    }

    public static function getTableJsonName($project_id,$data,$varName,$jsonArray){
        if($data != ""){
            $dataTableDataModelRecords = \REDCap::getData($project_id, 'array',array('record_id' => $data));
            $tableData = ProjectData::getProjectInfoArray($dataTableDataModelRecords)[0];
            $jsonArray[$varName] = $tableData['table_name'];
        }
        return $jsonArray;
    }

    public static function getFile($module, $edoc, $type){
        $file = "#";
        if($edoc != ""){
            $q = $module->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$edoc]);
            while ($row = $q->fetch_assoc()) {
                $url = 'downloadFile.php?sname=' . $row['stored_name'] . '&file=' . urlencode($row['doc_name']);
                $base64 = base64_encode(file_get_contents($module->framework->getSafePath($row['stored_name'], EDOC_PATH)));
                if($type == "img"){
                    $file = '<br/><div class="inside-panel-content"><img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="display: block; margin: 0 auto;"></div>';
                }else if($type == "logo"){
                    $file = '<img src="data:'.$row['mime_type'].';base64,' . $base64. '" style="padding-bottom: 30px;width: 450px;">';
                }else if($type == "src") {
                    $file = 'data:' . $row['mime_type'] . ';base64,' . $base64;
                }else if($type == "pdf") {
                    $file = $module->framework->getSafePath($row['stored_name'], EDOC_PATH);
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


    public static function numberToBase($number, $base) {
        $newString = "";
        while($number > 0) {
            $lastDigit = $number % $base;
            $newString = self::convertDigit($lastDigit, $base).$newString;
            $number -= $lastDigit;
            $number /= $base;
        }

        return $newString;
    }

    public static function convertDigit($number, $base) {
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

    public static function getRandomIdentifier($length = 6) {
        $output = "";
        $startNum = pow(32,5) + 1;
        $endNum = pow(32,6);
        while($length > 0) {

            # Generate a number between 32^5 and 32^6, then convert to a 6 digit string
            $randNum = mt_rand($startNum,$endNum);
            $randAlphaNum = self::numberToBase($randNum,32);

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
}
?>
