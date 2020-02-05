<?php

if (is_file('/app001/victrcore/lib/Victr/Env.php'))
    include_once('/app001/victrcore/lib/Victr/Env.php');

if(class_exists("Victr_Env")) {
    $envConf = Victr_Env::getEnvConf();

    if ($envConf[Victr_Env::ENV_CURRENT] === Victr_Env::ENV_PROD) {
        define("ENVIRONMENT", "PROD");
    }
    elseif ($envConf[Victr_Env::ENV_CURRENT] === Victr_Env::ENV_DEV) {
        define("ENVIRONMENT", "TEST");
    }
}
else {
    define("ENVIRONMENT", "DEV");
}

if(defined(ENVIRONMENT."_DES_PROJECTS")) {
    define("DES_PROJECTS", constant(ENVIRONMENT."_DES_PROJECTS"));
}

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

$settings = \REDCap::getData(array('project_id'=>DES_SETTINGS),'array')[1][$module->framework->getEventId(DES_SETTINGS)];