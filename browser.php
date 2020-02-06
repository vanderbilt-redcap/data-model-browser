<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
?>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script type="text/javascript" src="<?=$module->getUrl('js/jquery-3.3.1.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/bootstrap.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/functions.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/jquery-ui.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/jquery.tablesorter.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/jquery.dataTables.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/dataTables.select.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/dataTables.buttons.min.js')?>"></script>

    <link type='text/css' href='<?=$module->getUrl('js/fonts-awesome/css/font-awesome.min.css')?>' rel='stylesheet' media='screen' />
    <link rel="stylesheet" type="text/css" href="<?=$module->getUrl('css/bootstrap.min.css')?>">
    <link rel="stylesheet" type="text/css" href="<?=$module->getUrl('css/style.css')?>">
    <link type='text/css' href="<?=$module->getUrl('css/font-awesome.min.css')?>" rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/tabs-steps-menu.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/sortable-theme-bootstrap.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/tabs-steps-menu.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/jquery-ui.min.css')?>' rel='stylesheet' media='screen' />

    <script>
        var startDDProjects_url = <?=json_encode($module->getUrl('startDDProjects.php'))?>;
        var pid = <?=json_encode($_GET['pid'])?>;
    </script>

    <?php if(array_key_exists('message',$_REQUEST) && $_REQUEST['message']=='S'){?>
        <div class="container" style="margin-top: 60px">
            <div class="alert alert-success col-md-12">
               Data Dictionary and projects successfully installed. To see the Project Ids go to the <a href="<?=APP_PATH_WEBROOT?>DataEntry/record_status_dashboard.php?pid=<?=$_REQUEST['pid']?>" target="_blank">Record Dashboard</a>.
            </div>
        </div>
    <?php } ?>

<?php
#User rights
$UserRights = \REDCap::getUserRights(USERID)[USERID];
$isAdmin = false;
if($UserRights['user_rights'] == '1'){
    $isAdmin = true;
}

//$module->createPdf();



$dd_array = \REDCap::getDataDictionary('array');
if(count($dd_array) == 1 && $isAdmin && !array_key_exists('project_constant',$dd_array) && !array_key_exists('project_id',$dd_array)){
    echo '  <div class="container" style="margin-top: 60px">  
                <div class="alert alert-warning col-md-12">
                    <div class="col-md-10"><span class="pull-left">
                        The data dictionary for <strong>'.\REDCap::getProjectTitle().'</strong> is empty.
                        <br/>Click the button to create the data dictionary and all related projects.</span>
                    </div>
                    <div class="col-md-2"><a href="#" onclick="startDDProjects()" class="btn btn-primary pull-right">Create Projects & Data Dictionary</a></div>
                </div>
            </div>';
}else{
    include_once("projects.php");
    $settings = \REDCap::getData(array('project_id'=>DES_SETTINGS),'array')[1][$module->framework->getEventId(DES_SETTINGS)];

    include_once("functions.php");
    include_once("main.php");
}
?>