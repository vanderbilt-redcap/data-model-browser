<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use Exception;
use REDCap;

require_once 'vendor/autoload.php';

class DataModelBrowserExternalModule extends \ExternalModules\AbstractExternalModule{

    public function __construct(){
        parent::__construct();
    }

    function cronbigdata(){

    }

    function createProjectAndImportDataDictionary($value_constant)
    {
        $project_id = $this->framework->createProject(ucfirst(strtolower($value_constant." - Data Model Browser")), 0);
        $path = $this->module->getModulePath()."csv\\".$value_constant."_data_dictionary.csv";
        $this->framework->importDataDictionary($project_id,$path);

        return $project_id;
    }

    function addProjectToList($project_id, $eventId, $record, $fieldName, $value){
        $this->query("INSERT INTO redcap_data (project_id, event_id, record, field_name, value) VALUES (?, ?, ?, ?, ?)",
            [$project_id, $eventId, $record, $fieldName, $value]);
    }

    function createpdf(){
        include_once("projects.php");
        include_once("functions.php");

        $settings = \REDCap::getData(array('project_id'=>DES_SETTINGS),'array')[1][$this->framework->getEventId(DES_SETTINGS)];
        $hasJsoncopyBeenUpdated0a = $this->hasJsoncopyBeenUpdated('0a',$settings);
        $hasJsoncopyBeenUpdated0b = $this->hasJsoncopyBeenUpdated('0b',$settings);
        if($hasJsoncopyBeenUpdated0a || $hasJsoncopyBeenUpdated0b){
            $this->createAndSavePDFCron($settings);
            $this->createAndSaveJSONCron();
        }
    }

    function hasJsoncopyBeenUpdated($type,$settings){
        if(ENVIRONMENT == "DEV"){
            $qtype = $this->query("SELECT MAX(record) as record FROM redcap_data WHERE project_id=? AND field_name=? and value=? order by record",[DES_JSONCOPY,'type',$type]);
        }else{
            $qtype = $this->query("SELECT MAX(CAST(record AS Int)) as record FROM redcap_data WHERE project_id=? AND field_name=? and value=? order by record",[DES_JSONCOPY,'type',$type]);
        }
        $rowtype = $qtype->fetch_assoc();

        $jsoncocpy = getProjectInfoArray(DES_JSONCOPY,array('record_id' => $rowtype['record']))[0];
        $today = date("Y-m-d");
        if($jsoncocpy["jsoncopy_file"] != "" && strtotime(date("Y-m-d",strtotime($jsoncocpy['json_copy_update_d']))) == strtotime($today)){
            return true;
        }else if(empty($jsoncocpy) || strtotime(date("Y-m-d",strtotime($jsoncocpy['json_copy_update_d']))) == "" || !array_key_exists('json_copy_update_d',$jsoncocpy) || !array_key_exists('des_pdf',$settings) || $settings['des_pdf'] == ""){
            $this->checkAndUpdatJSONCopyProject($type,$rowtype['record'],$jsoncocpy,$settings);
            return true;
        }
        return false;
    }

    function createAndSavePDFCron($settings){
        $dataTable = getTablesInfo($this,DES_DATAMODEL);

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

        $img = 'data:image/png;base64,'.base64_encode(file_get_contents($this->loadImg($settings['des_logo'],'../../img/IeDEA-logo-200px.png','pdf')));

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
        $q = $this->query("INSERT INTO redcap_edocs_metadata (stored_name,mime_type,doc_name,doc_size,file_extension,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
            [$storedName,'application/octet-stream',$reportHash.".pdf",$filesize,'.pdf','0',DES_SETTINGS,date('Y-m-d h:i:s')]);
        $docId = db_insert_id();

        //Add document DB ID to project
        $Proj = new \Project(DES_SETTINGS);
        $event_id = $Proj->firstEventId;
        $json = json_encode(array(array('record_id' => 1, 'des_update_d' => date("Y-m-d H:i:s"),'des_pdf'=>$docId)));
        $results = \Records::saveData(DES_SETTINGS, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache(DES_SETTINGS, 1,$event_id);


        if($settings['des_pdf_notification_email'] != "") {
            $link = $this->getUrl("downloadFile.php?sname=".$storedName."&file=". $filename.".pdf");
            $goto = APP_PATH_WEBROOT_ALL . "DataEntry/index.php?pid=".DES_SETTINGS."&page=pdf&id=1";

            $subject = "New PDF Generated in ".$settings['des_doc_title'];
            $message = "<div>Changes have been detected and a new PDF has been generated.</div><br/>".
                "<div>You can <a href='".$link."'>download the pdf</a> or <a href='".$goto."'>go to the settings project</a>.</div><br/>";

            $environment = "";
            if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
                $environment = " ".ENVIRONMENT;
            }

            $sender = $settings['accesslink_sender_email'];
            if($settings['accesslink_sender_email'] == ""){
                $sender = "noreply@vumc.org";
            }


            $emails = explode(';', $settings['des_pdf_notification_email']);
            foreach ($emails as $email) {
                \REDCap::email($email, $sender, $subject.$environment, $message,"","",$settings['accesslink_sender_name']);
            }
        }
    }

    function createAndSaveJSONCron(){
        $dataTable = getTablesInfo($this,DES_DATAMODEL);
        $dataFormat = $this->getChoiceLabels('data_format', DES_DATAMODEL);

        foreach ($dataTable as $data) {
            $jsonVarArrayAux = array();
            if($data['table_name'] != "") {
                foreach ($data['variable_order'] as $id => $value) {
                    if ($data['variable_name'][$id] != '') {
                        $url = $this->getUrl("browser.php?pid=" . $_GET['pid'] . '&tid=' . $data['record_id'] . '&vid=' . $id . '&option=variableInfo');
                        $jsonVarArrayAux[trim($data['variable_name'][$id])] = array();
                        $variables_array = array(
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
                $urltid = $this->getUrl("browser.php?pid=" . $_GET['pid'] . '&tid=' . $data['record_id'] . '&option=variables');
                $jsonVarArray['table_link'] = $urltid;
                $jsonArray[trim($data['table_name'])] = $jsonVarArray;
            }
        }
        #we save the new JSON
        if(!empty($jsonArray)){
            $this->saveJSONCopyVarSearch($jsonArray);
        }
    }

    function saveJSONCopyVarSearch($jsonArray){
        #create and save file with json
        $filename = "jsoncopy_file_variable_search_".date("YmdsH").".txt";
        $storedName = date("YmdsH")."_pid".DES_SETTINGS."_".getRandomIdentifier(6).".txt";

        $file = fopen(EDOC_PATH.$storedName,"wb");
        fwrite($file,json_encode($jsonArray,JSON_FORCE_OBJECT));
        fclose($file);

        $output = file_get_contents(EDOC_PATH.$storedName);
        $filesize = file_put_contents(EDOC_PATH.$storedName, $output);

        //Save document on DB
        $q = $this->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
            [$storedName,$filename,$filesize,'txt','application/octet-stream','0',DES_SETTINGS,date('Y-m-d h:i:s')]);
        $docId = db_insert_id();

        //Add document DB ID to project
        $Proj = new \Project(DES_SETTINGS);
        $event_id = $Proj->firstEventId;
        $json = json_encode(array(array('record_id' => 1, 'des_variable_search' => $docId)));
        $results = \Records::saveData(DES_SETTINGS, 'json', $json,'normal', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
        \Records::addRecordToRecordListCache(DES_SETTINGS, 1,$event_id);
    }

    function checkAndUpdatJSONCopyProject($type,$last_record,$jsoncocpy,$settings){
        if($jsoncocpy["jsoncopy_file"] != ""){
            $q = $this->query("SELECT stored_name,doc_name,doc_size,mime_type FROM redcap_edocs_metadata WHERE doc_id=?",[$jsoncocpy["jsoncopy_file"]]);

            while ($row = $q->fetch_assoc()) {
                $path = EDOC_PATH.$row['stored_name'];
                $strJsonFileContents = file_get_contents($path);
                $last_array = json_decode($strJsonFileContents, true);
                $array_data = call_user_func_array("createProject".strtoupper($type)."JSON",array($this));
                $new_array = json_decode($array_data['jsonArray'],true);

                $result_prev = array_filter_empty(multi_array_diff($last_array,$new_array));
                $result = array_filter_empty(multi_array_diff($new_array,$last_array));
                $record = $array_data['record_id'];
            }
        }else{
            $array_data = call_user_func_array("createProject".strtoupper($type)."JSON",array($this));
            $result = json_decode($array_data['jsonArray'],true);
            $result_prev = "";
            $record = $array_data['record_id'];
        }
        if(!empty($record)){

            $environment = "";
            if(ENVIRONMENT == 'DEV' || ENVIRONMENT == 'TEST'){
                $environment = " ".ENVIRONMENT;
            }

            $sender = $settings['accesslink_sender_email'];
            if($settings['accesslink_sender_email'] == ""){
                $sender = "noreply@vumc.org";
            }

            $link = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . DES_JSONCOPY . "&arm=1&id=" . $record;
            $subject = "Changes in the DES ".strtoupper($type)." detected ";
            $message = "<div>The following changes have been detected in the DES ".strtoupper($type)." and a new record #".$record." has been created:</div><br/>".
                "<div>Last record:". $last_record."</div><br/>".
                "<div>To see the record <a href='".$link."'>click here</a></div><br/>".
                "<ul><pre>".print_r($result,true)."</pre>".
                "<span style='color:#777'><pre><em>".print_r($result_prev,true)."</em></pre></ul></span>";

            if($settings['des_0a0b_email'] != "") {
                $emails = explode(';', $settings['des_0a0b_email']);
                foreach ($emails as $email) {
                    \REDCap::email($email, $sender, $subject.$environment, $message,"","",$settings['accesslink_sender_name']);
                }
            }
        }

    }

    function loadImg($imgEdoc,$default,$option=""){
        $img = $default;
        if($imgEdoc != ''){
            $q = $this->query("SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id=?",[$imgEdoc]);

            while ($row = $q->fetch_assoc()) {
                if($option == 'pdf'){
                    $img = EDOC_PATH.$row['stored_name'];
                }else{
                    $img = 'downloadFile.php?sname='.$row['stored_name']."&file=". urlencode($row['doc_name']);
                }
            }
        }
        return $img;
    }
}