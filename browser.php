<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Vanderbilt\DataModelBrowserExternalModule\ProjectData;
$pid = (int)$_GET['pid'];
?>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php echo $module->loadREDCapJS(); ?>
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
    <link type='text/css' href='<?=$module->getUrl('css/tabs-steps-menu.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/sortable-theme-bootstrap.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/jquery-ui.min.css')?>' rel='stylesheet' media='screen' />

    <script>
        var startDDProjects_url = <?=json_encode($module->getUrl('startDDProjects.php'))?>;
        var downloadPDF_AJAX_url = <?=json_encode($module->getUrl('options/downloadPDF_AJAX.php'))?>;
        var pid = <?=json_encode($pid)?>;
    </script>

    <?php if(array_key_exists('message',$_REQUEST) && $_REQUEST['message']=='S'){?>
        <div class="container" style="margin-top: 80px">
            <div class="alert alert-success col-md-12">
               Data Dictionary and projects successfully installed. To see the Project Ids go to the <a href="<?=APP_PATH_WEBROOT?>DataEntry/record_status_dashboard.php?pid=<?=$pid?>" target="_blank">Record Dashboard</a>.
            </div>
        </div>
    <?php } ?>

<?php
$dd_array = \REDCap::getDataDictionary('array');
$data_array = \REDCap::getData($pid,'array');
$des_projectname = $module->getProjectSetting('des-projectname');
$des_privacy = $module->getProjectSetting('des-privacy');

#User rights
$isAdmin = false;
#if its a public noauth, we don't have the userid
if(defined('USERID')) {
    $UserRights = \REDCap::getUserRights(USERID)[USERID];

    if ($UserRights['user_rights'] == '1') {
        $isAdmin = true;
    }
}

if($des_projectname == "" || $des_privacy == ""){
    echo '  <div class="container" style="margin-top: 60px">  
                <div class="alert alert-warning col-md-12">
                 There are some missing module settings. To edit them go to the <a href="'.APP_PATH_WEBROOT_FULL.'external_modules/manager/project.php?pid='.$pid.'" target="_blank">External Modules - Project Module Manager</a>.    
                </div>
            </div>';
}else if(count($dd_array) == 1 && $isAdmin && !array_key_exists('project_constant',$dd_array) && !array_key_exists('project_id',$dd_array) || count($data_array) == 0){
    echo '  <div class="container" style="margin-top: 60px">  
                <div class="alert alert-warning col-md-12">
                    <div class="col-md-10"><span class="pull-left">
                        The data dictionary for <strong>'.\REDCap::getProjectTitle().'</strong> is empty.
                        <br/>Click the button to create the data dictionary and all related projects.</span>
                    </div>
                    <div class="col-md-2"><button id="installationbtn" onclick="startDDProjects();$(\'#save_continue_4_spinner\').addClass(\'fa fa-spinner fa-spin\');" class="btn btn-primary pull-right"><span id="save_continue_4_spinner"></span> Create Projects & Data Dictionary</button></div>
                </div>
            </div>';
}else{
    include_once("projects.php");
    $RecordSetSettings = \REDCap::getData($pidsArray['SETTINGS'], 'array');
    $settings = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSettings,$pidsArray['SETTINGS'])[0];

    include_once("functions.php");

    $has_permission = false;
    $page = "browser.php?NOAUTH";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">
        <link rel="icon" href="<?=printFile($module,$settings['des_favicon'],'url')?>">

        <title><?=htmlspecialchars($settings['des_doc_title'],ENT_QUOTES)?></title>

        <script type='text/javascript'>
            var app_path_webroot = '<?=APP_PATH_WEBROOT?>';
            var app_path_webroot_full = '<?=APP_PATH_WEBROOT?>';
            var app_path_images = '<?=APP_PATH_IMAGES?>';
        </script>

        <style>
            table thead .glyphicon{ color: blue; }
        </style>
        <?php include('header.php'); ?>
        <?php include('navbar.php'); ?>
    </head>
    <?php
    $has_permission = false;
    if($des_privacy == 'public' || $des_privacy == ""){
        $has_permission = true;
        include_once("main.php");
    }else{
        header('Location: '.$module->getUrl('main_private.php'));
    }

    if(!$has_permission){
        echo '<div class="container" style="margin-top: 60px"><div class="alert alert-warning" role="alert">You don\'t have permissions to access this Browser. Please contact an administrator.</div></div>';
        exit;
    }
    ?>
    <br/>
    <?php include('footer.php'); ?>
    </body>
    </html>
<?php
}
?>