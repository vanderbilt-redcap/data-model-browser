<?php
$path = $module->module->getModulePath()."csv\PID_data_dictionary.csv";
$module->framework->importDataDictionary($_GET['pid'],$path);
$custom_record_label = "[project_constant]: [project_id]";
$module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label,$_GET['pid']]);

$projects_array = array(0=>'DATAMODEL',1=>'CODELIST',2=>'SETTINGS',3=>'FILEREPO',4=>'JSONCOPY');
$custom_record_label_array = array(0=>"[table_name]",1=>"[list_name]",2=>'',3=>'',4=>"version [version]: [type]");

$record = 1;
foreach ($projects_array as $index=>$name){
    $project_id = $module->createProjectAndImportDataDictionary($name);
    $module->addProjectToList($_GET['pid'], $module->framework->getEventId($_GET['pid']), $record, 'record_id', $record);
    $module->addProjectToList($_GET['pid'], $module->framework->getEventId($_GET['pid']), $record, 'project_id', $project_id);
    $module->addProjectToList($_GET['pid'], $module->framework->getEventId($_GET['pid']), $record, 'project_constant', $name);
    $module->addProjectToList($_GET['pid'], $module->framework->getEventId($_GET['pid']), $record, 'project_info_complete', 2);
    if($custom_record_label_array[$index] != ''){
        $module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",[$custom_record_label_array[$index],$project_id]);
    }
    \Records::addRecordToRecordListCache($_GET['pid'], $record,1);
    $record++;
}

echo json_encode(array(
        'status' =>'success'
    )
);
?>
