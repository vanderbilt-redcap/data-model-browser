<?php
namespace Vanderbilt\DataModelBrowserExternalModule;


class ProjectData
{
    public $default_value;


    public static function getProjectsContantsArray(){
        $projects_array = array(0=>'SETTINGS',1=>'DATAMODEL',2=>'CODELIST',3=>'DATAMODELMETADATA', 4=>'JSONCOPY');

        return $projects_array;
    }

    public static function getProjectsTitlesArray(){
        $projects_titles_array = array(0=>'Settings',1=>'Data Model (0A)',2=>'Code Lists (0B)',3=>'Toolkit Metadata (0C)', 4=>'JSON Files');

        return $projects_titles_array;
    }


    /**
     * Function that returns the info array from a specific project
     * @param $project, the project id
     * @param $info_array, array that contains the conditionals
     * @param string $type, if its single or a multidimensional array
     * @return array, the info array
     */
    public static function getProjectInfoArray($records){
        $array = array();
        foreach ($records as $event) {
            foreach ($event as $data) {
                array_push($array,$data);
            }
        }

        return $array;
    }

    public static function getProjectInfoArrayRepeatingInstruments($records,$filterLogic=null){
        $array = array();
        $found = array();
        $index=0;
        foreach ($filterLogic as $filterkey => $filtervalue){
            array_push($found, false);
        }
        foreach ($records as $record=>$record_array) {
            $count = 0;
            foreach ($filterLogic as $filterkey => $filtervalue){
                $found[$count] = false;
                $count++;
            }
            foreach ($record_array as $event=>$data) {
                if($event == 'repeat_instances'){
                    foreach ($data as $eventarray){
                        $datarepeat = array();
                        foreach ($eventarray as $instrument=>$instrumentdata){
                            $count = 0;
                            foreach ($instrumentdata as $instance=>$instancedata){
                                foreach ($instancedata as $field_name=>$value){
                                    if(!array_key_exists($field_name,$array[$index])){
                                        $array[$index][$field_name] = array();
                                    }

                                    if($value != "" && (!is_array($value) || (is_array($value) && !empty($value)))){
                                        $datarepeat[$field_name][$instance] = $value;
                                        $count = 0;
                                        foreach ($filterLogic as $filterkey => $filtervalue){
                                            if($value == $filtervalue && $field_name == $filterkey){
                                                $found[$count] = true;
                                            }
                                            $count++;
                                        }
                                    }

                                }
                                $count++;
                            }
                        }
                        foreach ($datarepeat as $field=>$datai){
                            #check if non repeatable value is empty and add repeatable value
                            #empty value or checkboxes
                            if($array[$index][$field] == "" || (is_array($array[$index][$field]) && empty($array[$index][$field][1]))){
                                $array[$index][$field] = $datarepeat[$field];
                            }
                        }
                    }
                }else{
                    $array[$index] = $data;
                    foreach ($data as $fname=>$fvalue) {
                        $count = 0;
                        foreach ($filterLogic as $filterkey => $filtervalue){
                            if($fvalue == $filtervalue && $fname == $filterkey){
                                $found[$count] = true;
                            }
                            $count++;
                        }
                    }
                }
            }
            $found_total = true;
            foreach ($found as $fname=>$fvalue) {
                if($fvalue == false){
                    $found_total = false;
                    break;
                }
            }
            if(!$found_total && $filterLogic != null){
                unset($array[$index]);
            }

            $index++;
        }
        return $array;
    }

    public function setDefaultValues($project_id){
        $data_dictionary_settings = \REDCap::getDataDictionary($project_id, 'array',false);
        $default_value = array();
        foreach ($data_dictionary_settings as $row) {
            if($row['field_annotation'] != "" && strpos($row['field_annotation'], "@DEFAULT") !== false){
                $text = trim(explode("@DEFAULT=", $row['field_annotation'])[1],'\'"');;
                $default_value[$project_id][$row['field_name']] = $text;
            }

        }
        $this->default_value = $default_value;
    }

    public function getDefaultValues($project_id){
        return $this->default_value[$project_id];
    }

    public static function getPIDsArray($project_id){
        $projects_array = self::getProjectsContantsArray();
        $pidsArray = array();
        foreach ($projects_array as $constant){
            $RecordSetHarmonist = \REDCap::getData($project_id, 'array', null,null,null,null,false,false,false,"[project_constant]='".$constant."'");
            $pid = self::getProjectInfoArray($RecordSetHarmonist)[0]['project_id'];
            if($pid != ""){
                $pidsArray[$constant] = $pid;
            }
        }
        $pidsArray['PROJECTS'] = $project_id;
        return $pidsArray;
    }

    public static function getEnvironment(){
        if(preg_match("/vanderbilt.edu/i", SERVER_NAME)){
            #Other institutions
            define("ENVIRONMENT", "PROD");
        }else if (SERVER_NAME == "redcap.vanderbilt.edu") {
            define("ENVIRONMENT", "PROD");
        }else  if (SERVER_NAME == "redcaptest.vanderbilt.edu") {
            define("ENVIRONMENT", "TEST");
        }else {
            define("ENVIRONMENT", "DEV");
        }
    }
}
?>