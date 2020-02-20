<?php

use Vanderbilt\Victrlib\Env;
# Define the environment: options include "DEV", "TEST" or "PROD"
if (is_file('/app001/www/redcap/plugins/victrlib/src/Env.php'))
    include_once('/app001/www/redcap/plugins/victrlib/src/Env.php');

if (class_exists("\\Vanderbilt\\Victrlib\\Env")) {

    if (Env::isProd()) {
        define("ENVIRONMENT", "PROD");
    } else if (Env::isStaging()) {
        define("ENVIRONMENT", "TEST");
    }else{
        define("ENVIRONMENT", "DEV");
    }
}
else {
    define("ENVIRONMENT", "DEV");
}

#Mapper Project
$project_id_main = ($project_id != '')?$project_id:$_GET['pid'];
define(ENVIRONMENT.'_DES_PROJECTS', $project_id_main);

if(defined(ENVIRONMENT."_DES_PROJECTS")) {
    define("DES_PROJECTS", constant(ENVIRONMENT."_DES_PROJECTS"));
}

if(APP_PATH_WEBROOT[0] == '/'){
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
define('APP_PATH_WEBROOT_ALL',APP_PATH_WEBROOT_FULL.$APP_PATH_WEBROOT_ALL);
define('APP_PATH_PLUGIN',APP_PATH_WEBROOT_FULL."plugins/".substr(__DIR__,strlen(dirname(__DIR__))+1));

# Define the projects stored in DES_PROJECTS
$projects = \REDCap::getData(array('project_id'=>DES_PROJECTS),'array');

$linkedProjects = array();
foreach ($projects as $event){
    foreach ($event as $project) {
        define(ENVIRONMENT . '_DES_' . $project['project_constant'], $project['project_id']);
        array_push($linkedProjects,"DES_".$project['project_constant']);
    }
}

# Define the environment for each project
foreach($linkedProjects as $projectTitle) {
    if(defined(ENVIRONMENT."_".$projectTitle)) {
        define($projectTitle, constant(ENVIRONMENT."_".$projectTitle));
    }
}
