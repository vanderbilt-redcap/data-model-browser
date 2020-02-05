<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use Exception;
use REDCap;


class DataModelBrowserExternalModule extends \ExternalModules\AbstractExternalModule{

    public function __construct(){
        parent::__construct();
    }

    function cronbigdata(){

    }

    function createProjectAndImportDataDictionary($value_constant)
    {
        $project_id = $this->framework->createProject(ucfirst(strtolower($value_constant)), 0);
        $path = $this->module->getModulePath()."csv\\".$value_constant."_data_dictionary.csv";
        $this->framework->importDataDictionary($project_id,$path);

        return $project_id;
    }

    function addProjectToList($project_id, $eventId, $record, $fieldName, $value){
        $this->query("INSERT INTO redcap_data (project_id, event_id, record, field_name, value) VALUES (?, ?, ?, ?, ?)",
            [$project_id, $eventId, $record, $fieldName, $value]);
    }
}