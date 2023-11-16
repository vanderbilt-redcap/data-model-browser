<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$project_id = $_REQUEST['pid'];
$des_projectname = $module->getProjectSetting('des-projectname');

#MAPPER PARENT PROJECT
$newProjectTitle = $des_projectname.": Parent Project";
$path = $module->framework->getModulePath()."csv/PID_data_dictionary.csv";
$module->framework->importDataDictionary($project_id,$path);
$custom_record_label = "[project_constant]: [project_id]";
$module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label,$project_id]);
$module->query("UPDATE redcap_projects SET app_title = ? WHERE project_id = ?",[$newProjectTitle,$project_id]);

$projects_array = ProjectData::getProjectsContantsArray();
$projects_titles_array = ProjectData::getProjectsTitlesArray();
$custom_record_label_array = array(0=>'', 1=>"[table_name]",2=>"[list_name]",3=>'',4=>"version [version]: [type]");
$projects_array_repeatable = array(
    0=>array(0=>array('status'=>0,'instrument'=>'display_file','params'=>'[upload_name')),
    1=>array(0=>array('status'=>1,'instrument'=>'variable_metadata','params'=>'[variable_name]')),
    2=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    3=>array(0=>array('status'=>0,'instrument'=>'','params'=>'')),
    4=>array(0=>array('status'=>1,'instrument'=>'display_file','params'=>'[upload_date], [upload_name]'))
);
$userPermission = $module->getProjectSetting('user-permission',$project_id);

$record = 1;
foreach ($projects_array as $index=>$name){
    $project_title = $des_projectname.": ".$projects_titles_array[$index];
    $project_id_new = $module->createProjectAndImportDataDictionary($name,$project_title);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'record_id', $record);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_id', $project_id_new);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_constant', $name);
    $module->addProjectToList($project_id, $module->framework->getEventId($project_id), $record, 'project_info_complete', 2);
    if($custom_record_label_array[$index] != ''){
        $module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label_array[$index],$project_id_new]);
    }

    if($name == 'SETTINGS'){
        $qtype = $module->query("SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id =?",[$project_id_new]);
        $rowtype = $qtype->fetch_assoc();
        $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'record_id', 1);
        $module->addProjectToList($project_id_new, $rowtype['event_id'], 1, 'des_update_d', date("Y-m-d H:i:s"));
        \Records::addRecordToRecordListCache($project_id_new, $record,1);


    }

    #Add Repeatable projects
    foreach($projects_array_repeatable[$index] as $repeat_event){
        if($repeat_event['status'] == 1){
            $q = $module->query("SELECT b.event_id FROM  redcap_events_arms a LEFT JOIN redcap_events_metadata b ON(a.arm_id = b.arm_id) where a.project_id = ?",[$project_id_new]);
            while ($row = $q->fetch_assoc()) {
                $event_id = $row['event_id'];
                $module->query("INSERT INTO redcap_events_repeat (event_id, form_name, custom_repeat_form_label) VALUES (?, ?, ?)",[$event_id,$repeat_event['instrument'],$repeat_event['params']]);
            }
        }
    }

    #ADD USER PERMISSIONS
    $fields_rights = "project_id, username, design, user_rights, data_export_tool, reports, graphical, data_logging, data_entry";
    $instrument_names = \REDCap::getInstrumentNames(null,$project_id_new);
    $data_entry = "[".implode(',1][',array_keys($instrument_names)).",1]";
    foreach ($userPermission as $user){
        if($user != null) {
            $module->query("INSERT INTO redcap_user_rights (".$fields_rights.") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", [$project_id_new,$user, 1, 1, 1, 1, 1, 1, $data_entry]);
        }
    }

    \Records::addRecordToRecordListCache($project_id, $record,1);
    $record++;
}

#We must clear the project cache so our updates are pulled from the DB.
$module->clearProjectCache();
#Upload SQL fields to projects
#Get Projects ID's
$pidsArray = ProjectData::getPIDsArray($project_id);

$projects_array_sql = array(
    $pidsArray['DATAMODEL']=>array(
        'variable_replacedby' => array(
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '1',
            'label' => ""
        ),
        'code_list_ref' => array(
            'query' => "select record, value from ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['CODELIST'])." where project_id = ".$pidsArray['CODELIST']." and field_name = 'list_name' order by value asc",
            'autocomplete' => '0',
            'label' => ""
        )
    ),
    $pidsArray['DATAMODELMETADATA']=>array(
        'index_tablename' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    '  ' ) as value  FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'patient_id_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'default_group_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'group_tablename' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    '  ' ) as value  FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'birthdate_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'death_date_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'age_date_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'enrol_date_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_table' =>  array (
            'query' => "SELECT a.record,   CONCAT(   max(if(a.field_name = 'table_name', a.value, NULL)),    '  ' ) as value  FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." a  WHERE a.project_id=".$pidsArray['DATAMODEL']."  GROUP BY a.record  ORDER BY value",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_var' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_date' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        ),
        'height_units' =>  array (
            'query' => "SELECT CONCAT(a.record, ':', b.instance), CONCAT(a.value, ':', b.value) FROM (SELECT record,value FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'table_name') a JOIN (SELECT record, value, IFNULL(instance,1) as instance FROM ".\Vanderbilt\DataModelBrowserExternalModule\getDataTable($pidsArray['DATAMODEL'])." WHERE project_id=".$pidsArray['DATAMODEL']." AND field_name = 'variable_name') b  ON b.record=a.record ORDER BY a.value, b.instance",
            'autocomplete' => '0',
            'label' => ""
        )
    )
);

foreach ($projects_array_sql as $projectid=>$projects){
    foreach ($projects as $varid=>$options){
        foreach ($options as $optionid=>$value){
            if($optionid == 'query') {
                $module->query("UPDATE redcap_metadata SET element_enum = ? WHERE project_id = ? AND field_name=?",[$value,$projectid,$varid]);
            }
            if($optionid == 'autocomplete' && $value == '1'){
                $module->query("UPDATE redcap_metadata SET element_validation_type= ? WHERE project_id = ? AND field_name=?",["autocomplete",$projectid,$varid]);
            }
            if($optionid == 'label' && $value != "") {
                $module->query("UPDATE redcap_metadata SET element_label= ? WHERE project_id = ? AND field_name=?", [$value, $projectid, $varid]);
            }
        }
    }
}

echo json_encode(array(
        'status' =>'success'
    )
);
?>
