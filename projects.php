<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once (__DIR__ . '/vendor/autoload.php');
include_once(__DIR__ . "/functions.php");
include_once(__DIR__ . "/classes/ProjectData.php");

ProjectData::getEnvironment();

#Mapper Project
$project_id_main = ($project_id != '')?$project_id:$_GET['pid'];
#Get Projects ID's
$pidsArray = ProjectData::getPIDsArray($project_id_main);

$RecordSetSettings = \REDCap::getData($pidsArray['SETTINGS'], 'array', null);
$settings = ProjectData::getProjectInfoArray($RecordSetSettings)[0];

if(APP_PATH_WEBROOT[0] == '/'){
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);
