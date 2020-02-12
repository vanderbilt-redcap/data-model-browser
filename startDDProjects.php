<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$project_id = $_REQUEST['pid'];
$path = $module->framework->getModulePath()."csv/PID_data_dictionary.csv";
$module->framework->importDataDictionary($project_id,$path);
$custom_record_label = "[project_constant]: [project_id]";
$module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label,$project_id]);

$projects_array = array(0=>'DATAMODEL',1=>'CODELIST',2=>'SETTINGS',3=>'FILEREPO',4=>'JSONCOPY');
$custom_record_label_array = array(0=>"[table_name]",1=>"[list_name]",2=>'',3=>'',4=>"version [version]: [type]");

$project_title = \REDCap::getProjectTitle();
$record = 1;
foreach ($projects_array as $index=>$name){
    $project_id_new = $module->createProjectAndImportDataDictionary($name,$project_title);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'record_id', $record);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_id', $project_id_new);
    $module->addProjectToList($project_id, $module->framework->getEventId($$project_id), $record, 'project_constant', $name);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_info_complete', 2);
    if($custom_record_label_array[$index] != ''){
        $module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label_array[$index],$project_id_new]);
    }

    if($name[$index] == 'DATAMODEL'){
        $q = $module->query("SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id = ?",[263]);
        while ($row = $q->fetch_assoc()) {
            $event_id = $row['event_id'];
            $module->query("INSERT INTO redcap_events_repeat (event_id, form_name, custom_repeat_form_label) VALUES (?, ?, ?)",[$event_id,"variable_metadata","[variable_name]"]);
        }
    }
    \Records::addRecordToRecordListCache($project_id, $record,1);
    $record++;
}

echo json_encode(array(
        'status' =>'success'
    )
);
?>
