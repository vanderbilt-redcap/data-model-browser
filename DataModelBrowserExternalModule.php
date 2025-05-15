<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use Exception;
use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

include_once(__DIR__ . "/classes/ProjectData.php");
include_once(__DIR__ . "/classes/JsonPDF.php");
include_once(__DIR__ . "/functions.php");

require_once(dirname(__FILE__)."/vendor/autoload.php");


class DataModelBrowserExternalModule extends \ExternalModules\AbstractExternalModule{

    function createProjectAndImportDataDictionary($value_constant,$project_title)
    {
        $project_id = $this->framework->createProject($project_title, 0);
        $path = $this->framework->getModulePath()."csv/".$value_constant."_data_dictionary.csv";
        $this->importDataDictionary($project_id,$path);

        return $project_id;
    }

    function addProjectToList($project_id, $eventId, $record, $fieldName, $value){
        $this->query("INSERT INTO ".$this->getDataTable($project_id)." (project_id, event_id, record, field_name, value) VALUES (?, ?, ?, ?, ?)",
            [$project_id, $eventId, $record, $fieldName, $value]);
    }

    function createpdf(){
//        //Only perform actions between 12am and 6am for crons that update at night
//        $hourRange = 6;
//        if (date('G') > $hourRange) {
//            // Only perform actions between 12am and 6am.
//            return;
//        }
//        $lastRunSettingName = 'last-cron-run-time-createpdf';
//        $lastRun = empty($this->getSystemSetting($lastRunSettingName)) ? $this->getSystemSetting(
//            $lastRunSettingName
//        ) : 0;
//        $hoursSinceLastRun = (time() - $lastRun) / 60 / 60;
//        if ($hoursSinceLastRun < $hourRange) {
//            // We're already run recently
//            return;
//        }
//
//        //Perform cron actions here
//        if (APP_PATH_WEBROOT[0] == '/') {
//            $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
//        }
//        if(!defined('APP_PATH_WEBROOT_ALL')) {
//            define('APP_PATH_WEBROOT_ALL', APP_PATH_WEBROOT_FULL . $APP_PATH_WEBROOT_ALL);
//        }
//        foreach ($this->getProjectsWithModuleEnabled() as $project_id) {
//            if($project_id != "") {
//                $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SETTINGS'");
//                $settingsPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];
//                if($settingsPID != "") {
//                    $RecordSetSettings = \REDCap::getData($settingsPID, 'array');
//                    $settings = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSettings)[0];
//
//                    $hasJsoncopyBeenUpdated0a = $this->hasJsoncopyBeenUpdated('0a', $settings, $project_id);
//                    $hasJsoncopyBeenUpdated0b = $this->hasJsoncopyBeenUpdated('0b', $settings, $project_id);
//                    $hasJsoncopyBeenUpdated0c = $this->hasJsoncopyBeenUpdated('0c', $settings, $project_id);
//                    if ($hasJsoncopyBeenUpdated0a || $hasJsoncopyBeenUpdated0b || $hasJsoncopyBeenUpdated0c) {
//                        $this->createAndSavePDFCron($settings, $project_id);
//                        $this->createAndSaveJSONCron($project_id);
//                    } else {
//                        $this->checkIfJsonOrPDFBlank($settings, $project_id);
//                    }
//                }
//            }
//        }
//
//        $this->setSystemSetting($lastRunSettingName, time());
    }

    function regeneratepdf(){
//        if(APP_PATH_WEBROOT[0] == '/'){
//            $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
//        }
//        if(!defined('APP_PATH_WEBROOT_ALL')){
//            define('APP_PATH_WEBROOT_ALL', APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);
//        }
//
//        foreach ($this->getProjectsWithModuleEnabled() as $project_id){
//            if($project_id != "") {
//                $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SETTINGS'");
//                $settingsData = ProjectData::getProjectInfoArray($RecordSetConstants);
//                $settingsPID = "";
//                if(!empty($settingsData) && array_key_exists(0, $settingsData) && array_key_exists("project_id",$settingsData[0])){
//                    $settingsPID = $settingsData[0]['project_id'];
//                }
//
//                if($settingsPID != "") {
//                    $RecordSetSettings = \REDCap::getData($settingsPID, 'array');
//                    $settings = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSettings)[0];
//                    if(!empty($settings)) {
//                        if (array_key_exists('des_pdf_regenerate', $settings) && array_key_exists(1, $settings['des_pdf_regenerate']) && $settings['des_pdf_regenerate'][1] == '1') {
//                            $this->createAndSavePDFCron($settings, $project_id);
//                            $this->createAndSaveJSONCron($project_id);
//
//                            #Uncheck variable
//                            $Proj = new \Project($settingsPID);
//                            $event_id = $Proj->firstEventId;
//                            $arrayRM = [];
//                            $arrayRM[1][$event_id]['des_pdf_regenerate'] = [1 => ""];//checkbox
//                            $params = [
//                                'project_id' => $settingsPID,
//                                'dataFormat' => 'array',
//                                'data' => $arrayRM,
//                                'overwriteBehavior' => "overwrite",
//                                'dateFormat' => "YMD",
//                                'type' => "flat"
//                            ];
//                            $results = \REDCap::saveData($params);
//                            \Records::addRecordToRecordListCache($settingsPID, 1, $event_id);
//                        }
//                    }
//                }
//            }
//        }
    }

    function hasJsoncopyBeenUpdated($type,$settings, $project_id){
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='JSONCOPY'");
        $jsoncopyPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];
        if(defined('ENVIRONMENT') && ENVIRONMENT == "DEV"){
            $qtype = $this->query("SELECT MAX(record) as record FROM ".$this->getDataTable($jsoncopyPID)." WHERE project_id=? AND field_name=? and value=? order by record",[$jsoncopyPID,'type',$type]);
        }else{
            $qtype = $this->query("SELECT MAX(CAST(record AS Int)) as record FROM ".$this->getDataTable($jsoncopyPID)." WHERE project_id=? AND field_name=? and value=? order by record",[$jsoncopyPID,'type',$type]);
        }
        $rowtype = $qtype->fetch_assoc();

        if($this->projectHasData($type,$project_id)) {
            $RecordSetJsonCopy = \REDCap::getData($jsoncopyPID, 'array', array('record_id' => $rowtype['record']));
            $jsoncopy = ProjectData::getProjectInfoArray($RecordSetJsonCopy)[0];
            $today = date("Y-m-d");

            if ($jsoncopy["jsoncopy_file"] != "" && strtotime(date("Y-m-d", strtotime($jsoncopy['json_copy_update_d']))) == strtotime($today)) {
                return true;
            } else if (empty($jsoncopy) || strtotime(date("Y-m-d", strtotime($jsoncopy['json_copy_update_d']))) != strtotime($today) || strtotime(date("Y-m-d", strtotime($jsoncopy['json_copy_update_d']))) == "" || !array_key_exists('json_copy_update_d', $jsoncopy) || !array_key_exists('des_pdf', $settings) || $settings['des_pdf'] == "") {
                return $this->checkAndUpdateJSONCopyProject($type, $rowtype['record'], $jsoncopy, $settings, $project_id);
            }
        }
        return false;
    }

    function projectHasData($type,$project_id){
        $constant = "";
        if($type == "0a"){
            $constant = "DATAMODEL";
        }else if($type == "0b"){
            $constant = "CODELIST";
        }else if($type == "0c"){
            $constant = "DATAMODELMETADATA";
        }
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='".$constant."'");
        $pid = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];
        if($pid != ""){
            $RecordSetProject= \REDCap::getData($pid, 'array');
            $projectData = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetProject);
            if(!empty($projectData)){
                return true;
            }
        }
        return false;
    }

    function checkIfJsonOrPDFBlank($settings, $project_id){
        if($this->projectHasData("0a",$project_id)) {
            if ($settings['des_pdf'] == "" || !array_key_exists('des_pdf', $settings)) {
                $this->createAndSavePDFCron($settings, $project_id);
            }
            if ($settings['des_variable_search'] == "" || !array_key_exists('des_variable_search', $settings)) {
                $this->createAndSaveJSONCron($project_id);
            }
        }
    }

    function createAndSavePDFCron($settings, $project_id){
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='DATAMODEL'");
        $dataModelPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SETTINGS'");
        $settingsPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        $RecordSetDataModel = \REDCap::getData($dataModelPID, 'array');
        $dataTable = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetDataModel);
        if(!empty($dataTable)) {
            $tableHtml = JsonPDF::generateTablesHTML_pdf($this, $dataTable,false,false, $project_id, $dataModelPID);
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

        $img = JsonPDF::getFile($this,  $this->arrayKeyExistsReturnValue($settings,'des_pdf_logo'),'src');

        $html_pdf = "<html><head><meta http-equiv='Content-Type' content='text/html' charset='UTF-8' /><style>* { font-family: DejaVu Sans, sans-serif; }</style></head><body style='font-family:\"Calibri\";font-size:10pt;'>".$page_num
            ."<div class='footer' style='left: 590px;'><span class='page-number'>Page </span></div>"
            ."<div class='mainPDF'><table style='width: 100%;'><tr><td align='center'><img src='".$img."' style='width:200px;padding-bottom: 30px;'></td></tr></table></div>"
            ."<div class='mainPDF' id='page_html_style'><table style='width: 100%;'>".$first_page."<div style='page-break-before: always;'></div>"
            ."<div class='mainPDF'>".$second_page."<div style='page-break-before: always;'></div>"
            ."<p><span style='font-size:16pt'><strong>DES Tables</strong></span></p>"
            .$tableHtml[0]
            ."</div></div>"
            . "</body></html>";

        $filename = $settings['des_wkname']."_DataModel_".date("Y-m-d_hi",time()).".pdf";
        //SAVE JsonPDF ON DB
        $reportHash = $filename;
        $storedName = md5($reportHash);
        $filePath = EDOC_PATH.$storedName;

        //DOMPDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html_pdf);
        $dompdf->setPaper('A4', 'portrait');
        $options = $dompdf->getOptions();
        $options->setChroot(EDOC_PATH);
        $dompdf->setOptions($options);
        ob_start();
        $dompdf->render();
        //#Download option
        $output = $dompdf->output();
        $filesize = file_put_contents($this->framework->getSafePath($filePath, EDOC_PATH), $output);

        //Save document on DB
        $docId = \REDCap::storeFile($filePath, $settingsPID, $filename);
        unlink($filePath);

        #Add document DB ID to project
        $Proj = new \Project($settingsPID);
        $event_id = $Proj->firstEventId;
        $json = json_encode(array(array('record_id' => 1, 'des_update_d' => date("Y-m-d H:i:s"),'des_pdf'=>$docId)));
        $results = \Records::saveData($settingsPID, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($settingsPID, 1,$event_id);

        if($settings['des_pdf_notification_email'] != "") {
            $url = "";
            $q_edoc = $this->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$docId]);
            $row_edoc = $q_edoc->fetch_assoc();
            $url = "downloadFile.php?NOATUH&pid=" . $project_id. "&sname=" . $row_edoc['stored_name'] . '&file=' . urlencode($row_edoc['doc_name']);
            $link = $this->getUrl($url);

            $goto = APP_PATH_WEBROOT_ALL . "DataEntry/index.php?pid=".$settingsPID."&page=pdf&id=1";

            $q = $this->query("select app_title from redcap_projects where project_id = ? limit 1",[$settingsPID]);
            $row = $q->fetch_assoc();
            $project_title = $row['app_title'];

            $subject = "New JsonPDF Generated in ".$settings['des_doc_title'];
            $message = "<div>Changes have been detected and a new JsonPDF has been generated in ".$project_title.".</div><br/>".
                "<div>You can <a href='".$link."'>download the pdf</a> or <a href='".$goto."'>go to the settings project</a>.</div><br/>";

            $environment = "";
            if(defined('ENVIRONMENT') && (ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST')){
                $environment = " - ".ENVIRONMENT;
            }
            $sender = $this->arrayKeyExistsReturnValue($settings,'accesslink_sender_email');
            if($sender == ""){
                $sender = "noreply@vumc.org";
            }

            $attachments = array(
                $filename.".pdf" => $this->framework->getSafePath($storedName, EDOC_PATH)
            );

            $emails = explode(';', $settings['des_pdf_notification_email']);
            foreach ($emails as $email) {
                \REDCap::email($email, $sender, $subject.$environment, $message,"","",$this->arrayKeyExistsReturnValue($settings,'accesslink_sender_name'),$attachments);
            }
        }
    }

    function createAndSaveJSONCron($project_id){
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='DATAMODEL'");
        $dataModelPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        $RecordSetDataModel = \REDCap::getData($dataModelPID, 'array');
        $dataTable = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetDataModel);
        $dataFormat = $this->getChoiceLabels('data_format', $dataModelPID);

        foreach ($dataTable as $data) {
            $jsonVarArrayAux = array();
            if($data['table_name'] != "") {
                foreach ($data['variable_order'] as $id => $value) {
                    if ($data['variable_name'][$id] != '') {
                        $url = $this->getUrl("browser.php?pid=" . $project_id . '&tid=' . $data['record_id'] . '&vid=' . $id . '&option=variableInfo');
                        $jsonVarArrayAux[trim($data['variable_name'][$id])] = array();
                        $variables_array = array(
                            "instance" => $id,
                            "description" => $this->arrayKeyExistsReturnValue($data,'description',$id),
                            "description_extra" => $this->arrayKeyExistsReturnValue($data,'description_extra',$id),
                            "code_list_ref" => $this->arrayKeyExistsReturnValue($data,'code_list_ref',$id),
                            "data_format" => trim($dataFormat[$this->arrayKeyExistsReturnValue($data,'data_format',$id)]),
                            "code_text" => $this->arrayKeyExistsReturnValue($data,'code_text',$id),
                            "variable_link" => $url
                        );
                        $jsonVarArrayAux[$data['variable_name'][$id]] = $variables_array;
                    }
                }
                $jsonVarArray = $jsonVarArrayAux;
                $urltid = $this->getUrl("browser.php?pid=" . $project_id . '&tid=' . $data['record_id'] . '&option=variables');
                $jsonVarArray['table_link'] = $urltid;
                $jsonArray[trim($data['table_name'])] = $jsonVarArray;
            }
        }
        #we save the new JSON
        if(!empty($jsonArray)){
            $this->saveJSONCopyVarSearch($jsonArray, $project_id);
        }
    }

    function saveJSONCopyVarSearch($jsonArray, $project_id){
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='SETTINGS'");
        $settingsPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        #create and save file with json
        $filename = "jsoncopy_file_variable_search_".date("YmdsH").".txt";
        $storedName = date("YmdsH")."_pid".$settingsPID."_".JsonPDF::getRandomIdentifier(6).".txt";

        $file = fopen($this->framework->getSafePath($storedName, EDOC_PATH),"wb");
        fwrite($file,json_encode($jsonArray,JSON_FORCE_OBJECT));
        fclose($file);

        $output = file_get_contents($this->framework->getSafePath($storedName, EDOC_PATH));
        $filesize = file_put_contents($this->framework->getSafePath($storedName, EDOC_PATH), $output);

        //Save document on DB
        $q = $this->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
            [$storedName,$filename,$filesize,'txt','application/octet-stream','0',$settingsPID,date('Y-m-d h:i:s')]);
        $docId = db_insert_id();

        //Add document DB ID to project
        $Proj = new \Project($settingsPID);
        $event_id = $Proj->firstEventId;
        $json = json_encode(array(array('record_id' => 1, 'des_variable_search' => $docId)));
        $results = \Records::saveData($settingsPID, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache($settingsPID, 1,$event_id);
    }

    function checkAndUpdateJSONCopyProject($type, $last_record, $jsoncocpy, $settings, $project_id){
        $RecordSetConstants = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='JSONCOPY'");
        $jsoncopyPID = ProjectData::getProjectInfoArray($RecordSetConstants)[0]['project_id'];

        $jsonPdf = new JsonPDF;
        $record = "";
        if($jsoncocpy["jsoncopy_file"] != ""){
            $q = $this->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$jsoncocpy["jsoncopy_file"]]);
            while ($row = $q->fetch_assoc()) {
                $path = $this->framework->getSafePath($row['stored_name'], EDOC_PATH);
                $strJsonFileContents = file_get_contents($path);
                $last_array = json_decode($strJsonFileContents, true);
                $array_data = call_user_func_array(array($jsonPdf, "createProject".strtoupper($type)."JSON"),array($this, $project_id, false));
                $new_array = json_decode($array_data['jsonArray'],true);

                if($type == "0c"){
                    $result_prev = array_filter_empty(array_diff_assoc($last_array,$new_array));
                    $result = array_filter_empty(array_diff_assoc($new_array,$last_array));
                }else{
                    //multidimensional projects
                    $result_prev = array_filter_empty(multi_array_diff($last_array,$new_array));
                    $result = array_filter_empty(multi_array_diff($new_array,$last_array));
                }

                if($result_prev != $result){
                    $record = $jsonPdf->saveJSONCopy($type, $new_array, $this, $project_id);
                }
            }
        }else{
            $array_data = call_user_func_array(array($jsonPdf, "createProject".strtoupper($type)."JSON"),array($this, $project_id, true));
            $result = json_decode($array_data['jsonArray'],true);
            $result_prev = "";
            $record = $array_data['record_id'];
        }

        if($last_record == ""){
            $last_record = "<i>None</i>";
        }

        if(!empty($record)){
            $environment = "";
            if(defined('ENVIRONMENT') && (ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST')){
                $environment = " ".ENVIRONMENT;
            }

            $sender = $this->arrayKeyExistsReturnValue($settings,'accesslink_sender_email');
            if($sender == ""){
                $sender = "noreply@vumc.org";
            }

            $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $jsoncopyPID . "&arm=1&id=" . $record;
            $subject = "Changes in the DES ".strtoupper($type)." detected ";
            $message = "<div>The following changes have been detected in the DES ".strtoupper($type)." and a new record #".$record." has been created:</div><br/>".
                "<div>Last record: ". $last_record."</div><br/>".
                "<div>To see the record <a href='".$link."'>click here</a></div><br/>".
                "<ul><pre>".print_r($result,true)."</pre>".
                "<span style='color:#777'><pre><em>".print_r($result_prev,true)."</em></pre></ul></span>";

            if($settings['des_0a0b_email'] != "") {
                $emails = explode(';', $settings['des_0a0b_email']);
                foreach ($emails as $email) {
                    \REDCap::email($email, $sender, $subject.$environment, $message,"","",$this->arrayKeyExistsReturnValue($settings,'accesslink_sender_name'));
                }
            }
            return true;
        }
        return false;
    }

    function loadImg($imgEdoc,$default,$option=""){
        $img = $default;
        if($imgEdoc != ''){
            $q = $this->query("SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id=?",[$imgEdoc]);

            while ($row = $q->fetch_assoc()) {
                if($option == 'pdf'){
                    $img = $this->framework->getSafePath($row['stored_name'], EDOC_PATH);
                }else{
                    $img = 'downloadFile.php?sname='.$row['stored_name']."&file=". urlencode($row['doc_name']);
                }
            }
        }
        return $img;
    }

    function importDataDictionary($project_id,$path){
        $dictionary_array = $this->dataDictionaryCSVToMetadataArray($path, 'array');

        //Return warnings and errors from file (and fix any correctable errors)
        list ($errors_array, $warnings_array, $dictionary_array) = \MetaData::error_checking($dictionary_array);
        // Save data dictionary in metadata table
        $sql_errors = $this->saveMetadataCSV($dictionary_array,$project_id);

        // Display any failed queries to Super Users, but only give minimal info of error to regular users
        if (count($sql_errors) > 0) {
            throw new Exception("There was an error importing ".$path." Data Dictionary");
        }
    }

    function dataDictionaryCSVToMetadataArray($csvFilePath, $returnType = null)
    {
        $dd_column_var = array("0" => "field_name", "1" => "form_name","2" => "section_header", "3" => "field_type",
            "4" => "field_label", "5" => "select_choices_or_calculations","6" => "field_note", "7" => "text_validation_type_or_show_slider_number",
            "8" => "text_validation_min", "9" => "text_validation_max","10" => "identifier", "11" => "branching_logic",
            "12" => "required_field", "13" => "custom_alignment","14" => "question_number", "15" => "matrix_group_name",
            "16" => "matrix_ranking", "17" => "field_annotation"
        );

        // Set up array to switch out Excel column letters
        $cols = \MetaData::getCsvColNames();

        // Extract data from CSV file and rearrange it in a temp array
        $newdata_temp = array();
        $i = 1;

        // Set commas as default delimiter (if can't find comma, it will revert to tab delimited)
        $delimiter 	  = ",";
        $removeQuotes = false;

        if (($handle = fopen($csvFilePath, "rb")) !== false)
        {
            // Loop through each row
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false)
            {
                // Skip row 1
                if ($i == 1)
                {
                    ## CHECK DELIMITER
                    // Determine if comma- or tab-delimited (if can't find comma, it will revert to tab delimited)
                    $firstLine = implode(",", $row);
                    // If we find X number of tab characters, then we can safely assume the file is tab delimited
                    $numTabs = 6;
                    if (substr_count($firstLine, "\t") > $numTabs)
                    {
                        // Set new delimiter
                        $delimiter = "\t";
                        // Fix the $row array with new delimiter
                        $row = explode($delimiter, $firstLine);
                        // Check if quotes need to be replaced (added via CSV convention) by checking for quotes in the first line
                        // If quotes exist in the first line, then remove surrounding quotes and convert double double quotes with just a double quote
                        $removeQuotes = (substr_count($firstLine, '"') > 0);
                    }
                    // Increment counter
                    $i++;
                    // Check if legacy column Field Units exists. If so, tell user to remove it (by returning false).
                    // It is no longer supported but old values defined prior to 4.0 will be preserved.
                    if (strpos(strtolower($row[2]), "units") !== false)
                    {
                        return false;
                    }
                    continue;
                }
                if($returnType == null){
                    // Loop through each row and create array
                    $json_aux = array();
                    foreach ($row as $key => $value){
                        $json_aux[$dd_column_var[$key]] = $value;
                    }
                    $newdata_temp[$json_aux['field_name']] = $json_aux;
                }else if($returnType == 'array'){
                    // Loop through each column in this row
                    for ($j = 0; $j < count($row); $j++) {
                        // If tab delimited, compensate sightly
                        if ($delimiter == "\t") {
                            // Replace characters
                            $row[$j] = str_replace("\0", "", $row[$j]);
                            // If first column, remove new line character from beginning
                            if ($j == 0) {
                                $row[$j] = str_replace("\n", "", ($row[$j]));
                            }
                            // If the string is UTF-8, force convert it to UTF-8 anyway, which will fix some of the characters
                            if (function_exists('mb_detect_encoding') && mb_detect_encoding($row[$j]) == "UTF-8") {
                                $row[$j] = utf8_encode($row[$j]);
                            }
                            // Check if any double quotes need to be removed due to CSV convention
                            if ($removeQuotes) {
                                // Remove surrounding quotes, if exist
                                if (substr($row[$j], 0, 1) == '"' && substr($row[$j], -1) == '"') {
                                    $row[$j] = substr($row[$j], 1, -1);
                                }
                                // Remove any double double quotes
                                $row[$j] = str_replace("\"\"", "\"", $row[$j]);
                            }
                        }
                        // Add to array
                        $newdata_temp[$cols[$j + 1]][$i] = $row[$j];
                    }
                }
                $i++;
            }
            fclose($handle);
        } else {
            // ERROR: File is missing
            throw new Exception("ERROR. File is missing!");
        }

        // If file was tab delimited, then check if it left an empty row on the end (typically happens)
        if ($delimiter == "\t" && $newdata_temp['A'][$i-1] == "")
        {
            // Remove the last row from each column
            foreach (array_keys($newdata_temp) as $this_col)
            {
                unset($newdata_temp[$this_col][$i-1]);
            }
        }

        // Return array with data dictionary values
        return $newdata_temp;

    }

    // Save metadata when in DD array format
    private function saveMetadataCSV($dictionary_array, $project_id, $appendFields=false, $preventLogging=false)
    {
        $status = 0;
        $Proj = new \Project($project_id);

        // If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
        $metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";

        // DEV ONLY: Only run the following actions (change rights level, events designation) if in Development
        if ($status < 1)
        {
            // If new forms are being added, give all users "read-write" access to this new form
            $existing_form_names = array();
            if (!$appendFields) {
                $results = $this->query("select distinct form_name from ".$metadata_table." where project_id = ?",[$project_id]);
                while ($row = $results->fetch_assoc()) {
                    $existing_form_names[] = $row['form_name'];
                }
            }
            $newforms = array();
            foreach (array_unique($dictionary_array['B']) as $new_form) {
                if (!in_array($new_form, $existing_form_names)) {
                    //Add rights for EVERY user for this new form
                    $newforms[] = $new_form;
                    //Add all new forms to redcap_events_forms table
                    $this->query("insert into redcap_events_forms (event_id, form_name) select m.event_id, ?
                                                              from redcap_events_arms a, redcap_events_metadata m
                                                              where a.project_id = ? and a.arm_id = m.arm_id",[$new_form,$project_id]);

                }
            }
            if(!empty($newforms)){
                //Add new forms to rights table
                $data_entry = "[".implode(",1][",$newforms).",1]";
                $this->query("update redcap_user_rights set data_entry = concat(data_entry,?) where project_id = ? ",[$data_entry,$project_id]);
            }

            //Also delete form-level user rights for any forms deleted (as clean-up)
            if (!$appendFields) {
                foreach (array_diff($existing_form_names, array_unique($dictionary_array['B'])) as $deleted_form) {
                    //Loop through all 3 data_entry rights level states to catch all instances
                    for ($i = 0; $i <= 2; $i++) {
                        $deleted_form_sql = '['.$deleted_form.','.$i.']';
                        $this->query("update redcap_user_rights set data_entry = replace(data_entry,?,'') where project_id = ? ",[$deleted_form_sql,$project_id]);
                    }
                    //Delete all instances in redcap_events_forms
                    $this->query("delete from redcap_events_forms where event_id in
							(select m.event_id from redcap_events_arms a, redcap_events_metadata m, redcap_projects p where a.arm_id = m.arm_id
							and p.project_id = a.project_id and p.project_id = ?) and form_name = ?",[$project_id,$deleted_form]);
                }
            }

            ## CHANGE FOR MULTIPLE SURVEYS????? (Should we ALWAYS assume that if first form is a survey that we should preserve first form as survey?)
            // If using first form as survey and form is renamed in DD, then change form_name in redcap_surveys table to the new form name
            if (!$appendFields && isset($Proj->forms[$Proj->firstForm]['survey_id']))
            {
                $columnB = $dictionary_array['B'];
                $newFirstForm = array_shift(array_unique($columnB));
                unset($columnB);
                // Do not rename in table if the new first form is ALSO a survey (assuming it even exists)
                if ($newFirstForm != '' && $Proj->firstForm != $newFirstForm && !isset($Proj->forms[$newFirstForm]['survey_id']))
                {
                    // Change form_name of survey to the new first form name
                    $this->query("update redcap_surveys set form_name = ? where survey_id = ?",[$newFirstForm,$Proj->forms[$Proj->firstForm]['survey_id']]);
                }
            }
        }

        // Build array of existing form names and their menu names to try and preserve any existing menu names
        $q = $this->query("select form_name, form_menu_description from $metadata_table where project_id = ? and form_menu_description is not null",[$project_id]);
        $existing_form_menus = array();
        while ($row = $q->fetch_assoc()) {
            $existing_form_menus[$row['form_name']] = $row['form_menu_description'];
        }

        // Before wiping out current metadata, obtain values in table not contained in data dictionary to preserve during carryover (e.g., edoc_id)
        $q = $this->query("select field_name, edoc_id, edoc_display_img, stop_actions, field_units, video_url, video_display_inline
				from $metadata_table where project_id = ?
				and (edoc_id is not null or stop_actions is not null or field_units is not null or video_url is not null)",[$project_id]);
        $extra_values = array();
        while ($row = $q->fetch_assoc())
        {
            if (!empty($row['edoc_id'])) {
                // Preserve edoc values
                $extra_values[$row['field_name']]['edoc_id'] = $row['edoc_id'];
                $extra_values[$row['field_name']]['edoc_display_img'] = $row['edoc_display_img'];
            }
            if ($row['stop_actions'] != "") {
                // Preserve stop_actions value
                $extra_values[$row['field_name']]['stop_actions'] = $row['stop_actions'];
            }
            if ($row['field_units'] != "") {
                // Preserve field_units value (no longer included in data dictionary but will be preserved if defined before 4.0)
                $extra_values[$row['field_name']]['field_units'] = $row['field_units'];
            }
            if ($row['video_url'] != "") {
                // Preserve video_url value
                $extra_values[$row['field_name']]['video_url'] = $row['video_url'];
                $extra_values[$row['field_name']]['video_display_inline'] = $row['video_display_inline'];
            }
        }

        // Determine if we need to replace ALL fields or append to existing fields
        if ($appendFields) {
            // Only append new fields to existing metadata (as opposed to replacing them all)
            $q = $this->query("select max(field_order)+1 from $metadata_table where project_id = ?",[$project_id]);
            $field_order = $q;
        } else {
            // Default field order value
            $field_order = 1;
            // Delete all instances of metadata for this project to clean out before adding new
            $this->query("delete from $metadata_table where project_id = ?", [$project_id]);
        }

        // Capture any SQL errors
        $sql_errors = array();
        // Create array to keep track of form names for building form_menu_description logic
        $form_names = array();
        // Set up exchange values for replacing legacy back-end values
        $convertValType = array("integer"=>"int", "number"=>"float");
        $convertFldType = array("notes"=>"textarea", "dropdown"=>"select", "drop-down"=>"select");

        // Loop through data dictionary array and save into metadata table
        foreach (array_keys($dictionary_array['A']) as $i)
        {
            // If this is the first field of a form, generate form menu description for upcoming form
            // If form menu description already exists, it may have been customized, so keep old value
            $form_menu = "";
            if (!in_array($dictionary_array['B'][$i], $form_names)) {
                if (isset($existing_form_menus[$dictionary_array['B'][$i]])) {
                    // Use existing value if form existed previously
                    $form_menu = $existing_form_menus[$dictionary_array['B'][$i]];
                } else {
                    // Create menu name on the fly
                    $form_menu = ucwords(str_replace("_", " ", $dictionary_array['B'][$i]));
                }
            }
            // Deal with hard/soft validation checktype for text fields
            $valchecktype = ($dictionary_array['D'][$i] == "text") ? "'soft_typed'" : "NULL";
            // Swap out Identifier "y" with "1"
            $dictionary_array['K'][$i] = (strtolower(trim($dictionary_array['K'][$i])) == "y") ? "1" : "NULL";
            // Swap out Required Field "y" with "1"	(else "0")
            $dictionary_array['M'][$i] = (strtolower(trim($dictionary_array['M'][$i])) == "y") ? "1" : "'0'";
            // Format multiple choices
            if ($dictionary_array['F'][$i] != "" && $dictionary_array['D'][$i] != "calc" && $dictionary_array['D'][$i] != "slider" && $dictionary_array['D'][$i] != "sql") {
                $dictionary_array['F'][$i] = str_replace(array("|","\n"), array("\\n"," \\n "), $dictionary_array['F'][$i]);
            }
            // Do replacement of front-end values with back-end equivalents
            if (isset($convertFldType[$dictionary_array['D'][$i]])) {
                $dictionary_array['D'][$i] = $convertFldType[$dictionary_array['D'][$i]];
            }
            if ($dictionary_array['H'][$i] != "" && $dictionary_array['D'][$i] != "slider") {
                // Replace with legacy/back-end values
                if (isset($convertValType[$dictionary_array['H'][$i]])) {
                    $dictionary_array['H'][$i] = $convertValType[$dictionary_array['H'][$i]];
                }
            } elseif ($dictionary_array['D'][$i] == "slider" && $dictionary_array['H'][$i] != "" && $dictionary_array['H'][$i] != "number") {
                // Ensure sliders only have validation type of "" or "number" (to display number value or not)
                $dictionary_array['H'][$i] = "";
            }
            // Make sure question_num is 10 characters or less
            if (strlen($dictionary_array['O'][$i]) > 10) $dictionary_array['O'][$i] = substr($dictionary_array['O'][$i], 0, 10);
            // Swap out Matrix Rank "y" with "1" (else "0")
            $dictionary_array['Q'][$i] = (strtolower(trim($dictionary_array['Q'][$i])) == "y") ? "1" : "'0'";
            // Remove any hex'ed double-CR characters in field labels, etc.
            $dictionary_array['E'][$i] = str_replace("\x0d\x0d", "\n\n", $dictionary_array['E'][$i]);
            $dictionary_array['C'][$i] = str_replace("\x0d\x0d", "\n\n", $dictionary_array['C'][$i]);
            $dictionary_array['F'][$i] = str_replace("\x0d\x0d", "\n\n", $dictionary_array['F'][$i]);
            // Insert edoc_id and slider display values that should be preserved
            $edoc_id 		  = isset($extra_values[$dictionary_array['A'][$i]]['edoc_id']) ? $extra_values[$dictionary_array['A'][$i]]['edoc_id'] : NULL;
            $edoc_display_img = isset($extra_values[$dictionary_array['A'][$i]]['edoc_display_img']) ? $extra_values[$dictionary_array['A'][$i]]['edoc_display_img'] : "0";
            $stop_actions 	  = isset($extra_values[$dictionary_array['A'][$i]]['stop_actions']) ? $extra_values[$dictionary_array['A'][$i]]['stop_actions'] : "";
            $field_units	  = isset($extra_values[$dictionary_array['A'][$i]]['field_units']) ? $extra_values[$dictionary_array['A'][$i]]['field_units'] : "";
            $video_url	  	  = isset($extra_values[$dictionary_array['A'][$i]]['video_url']) ? $extra_values[$dictionary_array['A'][$i]]['video_url'] : "";
            $video_display_inline = isset($extra_values[$dictionary_array['A'][$i]]['video_display_inline']) ? $extra_values[$dictionary_array['A'][$i]]['video_display_inline'] : "0";

            $sql = "insert into $metadata_table (project_id, field_name, form_name, field_units, element_preceding_header, "
                . "element_type, element_label, element_enum, element_note, element_validation_type, element_validation_min, "
                . "element_validation_max, field_phi, branching_logic, element_validation_checktype, form_menu_description, "
                . "field_order, field_req, edoc_id, edoc_display_img, custom_alignment, stop_actions, question_num, "
                . "grid_name, grid_rank, misc, video_url, video_display_inline) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            $q = $this->query($sql,
                [
                    $project_id,
                    $this->checkNull($dictionary_array['A'][$i]),
                    $this->checkNull($dictionary_array['B'][$i]),
                    $this->checkNull($field_units),
                    $this->checkNull($dictionary_array['C'][$i]),
                    $this->checkNull($dictionary_array['D'][$i]),
                    $this->checkNull($dictionary_array['E'][$i]),
                    $this->checkNull($dictionary_array['F'][$i]),
                    $this->checkNull($dictionary_array['G'][$i]),
                    $this->checkNull($dictionary_array['H'][$i]),
                    $this->checkNull($dictionary_array['I'][$i]),
                    $this->checkNull($dictionary_array['J'][$i]),
                    $dictionary_array['K'][$i],
                    $this->checkNull($dictionary_array['L'][$i]),
                    $valchecktype,
                    $this->checkNull($form_menu),
                    $field_order,
                    $dictionary_array['M'][$i],
                    $edoc_id,
                    $edoc_display_img,
                    $this->checkNull($dictionary_array['N'][$i]),
                    $this->checkNull($stop_actions),
                    $this->checkNull($dictionary_array['O'][$i]),
                    $this->checkNull($dictionary_array['P'][$i]),
                    $dictionary_array['Q'][$i],
                    $this->checkNull(isset($dictionary_array['R']) ? $dictionary_array['R'][$i] : null),
                    $this->checkNull($video_url),
                    "'".$video_display_inline."'"
                ]
            );
            //Insert into table
            if ($q) {
                // Increment field order
                $field_order++;
            } else {
                //Log this error
                $sql_errors[] = $sql;
            }


            //Add Form Status field if we're on the last field of a form
            if (isset($dictionary_array['B'][$i]) && $dictionary_array['B'][$i] != $dictionary_array['B'][$i+1]) {
                $form_name = $dictionary_array['B'][$i];
                $q = $this->insertFormStatusField($metadata_table, $project_id, $form_name, $field_order);
                //Insert into table
                if ($q) {
                    // Increment field order
                    $field_order++;
                } else {
                    //Log this error
                    // $sql_errors[] = $sql;
                }
            }

            //Add form name to array for later checking for form_menu_description
            $form_names[] = $dictionary_array['B'][$i];

        }

        // Logging
        if (!$appendFields && !$preventLogging) {
            \Logging::logEvent("",$metadata_table,"MANAGE",$project_id,"project_id = ".$project_id,"Upload data dictionary");
        }
        // Return any SQL errors
        return $sql_errors;
    }

    public function clearProjectCache(){
        $this->setPrivateVariable('project_cache', [], 'Project');
    }

    protected function setPrivateVariable($name, $value, $target = null)
    {
        $class = new \ReflectionClass($target);
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property->setValue($this, $value);
    }

    public function getDataTable($project_id){
        return method_exists('\REDCap', 'getDataTable') ? \REDCap::getDataTable($project_id) : "redcap_data";
    }

    public function loadREDCapJS(){
        if (method_exists(get_parent_class($this), 'loadREDCapJS')) {
            parent::loadREDCapJS();
        } else {
            ?>
            <script src='<?=APP_PATH_WEBROOT?>Resources/webpack/js/bundle.js'></script>
            <?php
        }
    }

    public function arrayKeyExistsReturnValue($array, $key, $key2=null) {
        if(array_key_exists($key, $array)) {
            if($key2 != null && $key2 != "") {
                if(is_array($array[$key]) && array_key_exists($key2, $array[$key])) {
                    return $array[$key][$key2];
                }else{
                    return "";
                }
            }else{
                return $array[$key];
            }
        }
        return "";
    }
}