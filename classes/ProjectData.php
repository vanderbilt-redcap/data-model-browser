<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use Project;
use REDCap;

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

    public static function getProjectInfoArrayRepeatingInstruments(
        $records,
        $project_id,
        $filterLogic = null,
        $option = null
    ) {
        $array = array();
        $found = array();
        $index = 0;
        if (is_array($filterLogic) && $filterLogic != null) {
            foreach ($filterLogic as $filterkey => $filtervalue) {
                array_push($found, false);
            }
        }
        foreach ($records as $record => $record_array) {
            $count = 0;
            if(is_array($filterLogic) && !empty($filterLogic)) {
                foreach ($filterLogic as $filterkey => $filtervalue) {
                    $found[$count] = false;
                    $count++;
                }
            }
            foreach ($record_array as $event => $data) {
                if ($event == 'repeat_instances') {
                    foreach ($data as $eventarray) {
                        $datarepeat = array();
                        foreach ($eventarray as $instrument => $instrumentdata) {
                            $count = 0;
                            foreach ($instrumentdata as $instance => $instancedata) {
                                foreach ($instancedata as $field_name => $value) {
                                    if (!empty($array[$index]) && !array_key_exists($field_name, $array[$index])) {
                                        $array[$index][$field_name] = array();
                                    }
                                    if ($value != "" && (!is_array($value) || (is_array($value) && !empty($value)))) {
                                        $datarepeat[$field_name][$instance] = $value;
                                        $count = 0;
                                        if(is_array($filterLogic) && !empty($filterLogic)) {
                                            foreach ($filterLogic as $filterkey => $filtervalue) {
                                                if ($value == $filtervalue && $field_name == $filterkey) {
                                                    $found[$count] = true;
                                                }
                                                $count++;
                                            }
                                        }
                                    }
                                    if (array_key_exists($index, $array) && array_key_exists($field_name, $array[$index]) && is_array($array[$index][$field_name]) &&
                                        ProjectData::isCheckbox($field_name, $project_id) && is_array($value) && array_key_exists(1,$value) && !empty($value[1])) {
                                        $array[$index][$field_name][$instance] = $value[1];
                                    }
                                }
                                $count++;
                            }
                        }
                        foreach ($datarepeat as $field => $datai) {
                            #check if non repeatable value is empty and add repeatable value
                            #empty value or checkboxes
                            if ($array[$index][$field] == "" || (is_array(
                                        $array[$index][$field]
                                    ) && empty($array[$index][$field]))) {
                                $array[$index][$field] = $datarepeat[$field];
                            } else {
                                if (is_array($datai) && $option == "json") {
                                    #only for the JSON format
                                    $array[$index][$field] = $datarepeat[$field];
                                }
                            }
                        }
                    }
                } else {
                    $array[$index] = $data;
                    foreach ($data as $fname => $fvalue) {
                        $count = 0;
                        if(is_array($filterLogic) && !empty($filterLogic)) {
                            foreach ($filterLogic as $filterkey => $filtervalue) {
                                if ($fvalue == $filtervalue && $fname == $filterkey) {
                                    $found[$count] = true;
                                }
                                $count++;
                            }
                        }
                    }
                }
            }
            $found_total = true;
            foreach ($found as $fname => $fvalue) {
                if ($fvalue == false) {
                    $found_total = false;
                    break;
                }
            }
            if (!$found_total && $filterLogic != null) {
                unset($array[$index]);
            }

            $index++;
        }
        return $array;
    }

    public static function isCheckbox($field_name, $project_id)
    {
        $Proj = new Project($project_id);
        // If field is invalid, return false
        if (!isset($Proj->metadata[$field_name])) {
            return false;
        }
        // Array to translate back-end field type to front-end (some are different, e.g. "textarea"=>"notes")
        $fieldTypeTranslator = array('textarea' => 'notes', 'select' => 'dropdown');
        // Get field type
        $fieldType = $Proj->metadata[$field_name]['element_type'];
        // Translate field type, if needed
        if (isset($fieldTypeTranslator[$fieldType])) {
            $fieldType = $fieldTypeTranslator[$fieldType];
        }
        unset ($Proj);
        if ($fieldType == "checkbox") {
            return true;
        }
        return false;
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
        if(!defined("ENVIRONMENT")) {
            if (preg_match("/vumc.org/i", SERVER_NAME)) {
                #Other institutions
                define("ENVIRONMENT", "PROD");
            } else {
                if (SERVER_NAME == "redcap.vumc.org") {
                    define("ENVIRONMENT", "PROD");
                } else {
                    if (SERVER_NAME == "redcaptest.vumc.org") {
                        define("ENVIRONMENT", "TEST");
                    } else {
                        define("ENVIRONMENT", "DEV");
                    }
                }
            }
        }
    }

    public static function renderCodeOptions($codeList, $fallbackText, $outputFormat = 'div')
    {
        // Split the code list into options using " | " delimiter
        $codeOptions = empty($codeList) ? [$fallbackText] : explode(" | ", $codeList);

        // If the code list is empty, return an empty string
        if (empty($codeOptions[0])) {
            return '';
        }

        // Initialize the output HTML
        $output = '';

        // Render in table format
        if ($outputFormat === 'table') {
            $output .= "<div class='wiki'><div class='panel panel-default'>";
            $output .= "<table border='1' class='table table-bordered table-hover' style='font-size: 13px;'>";
            $output .= "<div class='table-responsive panel-collapse collapse in'>";
            $output .= "<tbody><tr><td>Code</td><td>Definition</td></tr>";

            foreach ($codeOptions as $option) {
                // Split each option into Code and Definition using "=" as the delimiter
                $varCodes = explode("=", $option);

                // Ensure both Code and Definition parts exist
                $code = isset($varCodes[0]) ? trim($varCodes[0]) : '';
                $definition = isset($varCodes[1]) ? trim($varCodes[1]) : 'Missing Definition';

                // Render the table row for the Code and Definition
                $output .= "<tr>";
                $output .= "<td style='text-align: center;'>" . htmlspecialchars($code, ENT_QUOTES) . "</td>";
                $output .= "<td>" . htmlspecialchars($definition, ENT_QUOTES) . "</td>";
                $output .= "</tr>";
            }

            $output .= "</tbody></table></div></div></div>";
        }
        // Render in div format
        else {
            $output .= "<div style='padding-left:15px'>";
            foreach ($codeOptions as $option) {
                $output .= htmlspecialchars($option, ENT_QUOTES) . "<br/>";
            }
            $output .= "</div>";
        }

        // Return the rendered HTML
        echo filter_tags($output);
    }

    public static function renderCodeFileDialog($codeFile, $name, $module)
    {
        if (empty($codeFile)) {
            return;
        }

        $dialogName = htmlspecialchars($codeFile, ENT_QUOTES) . '_' . $name;
        echo '<a onclick="$(\'#' . $dialogName . '\').dialog(\'open\').scrollTop(0);" style="cursor: pointer">See Code List</a>';
        echo '<div id="' . $dialogName . '" title="Codes ' . $name . '" class="dialog" style="display:none;">';
        echo '<table border="1" class="code_modal_table">';

        $csv = parseCSVtoArray($module, $codeFile);

        if (empty($csv)) {
            echo '<div style="text-align: center;color:red;">No Codes found for file: ' . htmlspecialchars($codeFile, ENT_QUOTES) . '</div>';
        } else {
            foreach ($csv as $header => $content) {
                echo '<tr>';
                foreach ($content as $col => $value) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'HTML-ENTITIES'); // Handle encoding
                    echo '<td class="code_modal_td">' . htmlspecialchars($value, ENT_QUOTES) . '</td>';
                }
                echo '</tr>';
            }
        }

        echo '</table></div>';
    }

    public static function renderOntologyLink($codeOntology)
    {
        if (!empty($codeOntology)) {
            $ontologyUrl = "https://bioportal.bioontology.org/ontologies/" . htmlspecialchars($codeOntology, ENT_QUOTES);
            echo "<a href='{$ontologyUrl}' target='_blank'>See Ontology Link</a><br/>";
        }
    }

    function renderCodeFile($codeFile, $name, $module)
    {
        if (empty($codeFile)) {
            echo '<div style="text-align: center;color:red;">No code file provided.</div>';
            return;
        }

        $dialogName = htmlspecialchars($codeFile, ENT_QUOTES) . '_' . $name;

        // Render the "See Code List" link
        echo '<a onclick="$(\'#' . $dialogName . '\').dialog(\'open\').scrollTop(0);" style="cursor: pointer">See Code List</a>';

        // Start rendering the modal dialog
        echo '<div id="' . $dialogName . '" title="Codes ' . htmlspecialchars($name, ENT_QUOTES) . '" class="dialog" style="display:none;">';
        echo '<table border="1" class="code_modal_table">';

        // Parse the CSV file to retrieve its contents
        $csv = parseCSVtoArray($module, $codeFile);

        // Handle cases where the CSV is empty or fails to parse
        if (empty($csv)) {
            echo '<div style="text-align: center;color:red;">No Codes found for file: ' . htmlspecialchars($codeFile, ENT_QUOTES) . '</div>';
        } else {
            // Render the table header
            echo '<thead><tr>';
            foreach (array_keys($csv[0]) as $header) { // Assuming first row contains headers
                echo '<th>' . htmlspecialchars($header, ENT_QUOTES) . '</th>';
            }
            echo '</tr></thead>';

            // Render the table rows
            echo '<tbody>';
            foreach ($csv as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    // Convert to UTF-8 to avoid weird characters and escape HTML
                    $cell = mb_convert_encoding($cell, 'UTF-8', 'HTML-ENTITIES');
                    echo '<td>' . htmlspecialchars($cell, ENT_QUOTES) . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody>';
        }

        // Close the table and modal
        echo '</table></div>';
    }
}
?>

