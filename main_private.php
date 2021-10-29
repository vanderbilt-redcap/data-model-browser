<?php use Vanderbilt\DataModelBrowserExternalModule\ProjectData; ?>
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
<link type='text/css' href='<?=$module->getUrl('css/tabs-steps-menu.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/sortable-theme-bootstrap.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/jquery-ui.min.css')?>' rel='stylesheet' media='screen' />

<script>
    var startDDProjects_url = <?=json_encode($module->getUrl('startDDProjects.php'))?>;
    var downloadPDF_AJAX_url = <?=json_encode($module->getUrl('options/downloadPDF_AJAX.php'))?>;
    var pid = <?=json_encode($_GET['pid'])?>;
</script>
<?php
include_once("projects.php");
$RecordSetSettings = \REDCap::getData(DES_SETTINGS, 'array');
$settings = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSettings)[0];
include_once("functions.php");
$des_privacy = $module->getProjectSetting('des-privacy');
$has_permission = false;
$page = "main_private.php?";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="<?=\Vanderbilt\DataModelBrowserExternalModule\printFile($module,$settings['des_favicon'],'url')?>">

    <title><?=$settings['des_doc_title']?></title>

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
<body>
<?php
$des_project = $module->getProjectSetting('des-project');
$has_permission = false;
if($des_privacy == 'public'){
    $has_permission = true;
}else if($des_privacy == 'main'){
    if(!defined('USERID')){
        echo '<div class="container" style="margin-top: 60px"><div class="alert alert-warning" role="alert">Please log in REDCap to access this Browser.</div></div>';
        exit;
    }else if(\Vanderbilt\DataModelBrowserExternalModule\isUserExpiredOrSuspended($module,USERID, 'user_suspended_time') || \Vanderbilt\DataModelBrowserExternalModule\isUserExpiredOrSuspended($module,USERID, 'user_expiration')) {
        echo '<div class="container" style="margin-top: 60px"><div class="alert alert-warning" role="alert">This user is expired or suspended. Please contact an administrator.</div></div>';
        exit;
    }else{
        $result = $module->query("SELECT * FROM `redcap_user_rights` WHERE project_id=? AND username=?" ,[$_REQUEST['pid'],USERID]);
        if ($result->num_rows > 0) {
            $has_permission = true;
        }
    }

}else if($des_privacy == 'other') {
    if(!defined('USERID')){
        echo '<div class="container" style="margin-top: 60px"><div class="alert alert-warning" role="alert">Please log in REDCap to access this Browser.</div></div>';
        exit;
    }else if(count($des_project) == 0) {
        echo '<div class="container" style="margin-top: 60px"><div class="alert alert-warning" role="alert">Please select a project(s) to give permissions to.</div></div>';
        exit;
    }else if(\Vanderbilt\DataModelBrowserExternalModule\isUserExpiredOrSuspended($module,USERID, 'user_suspended_time') || \Vanderbilt\DataModelBrowserExternalModule\isUserExpiredOrSuspended($module,USERID, 'user_expiration')) {
        echo '<div class="container" style="margin-top: 60px"><div class="alert alert-warning" role="alert">This user is expired or suspended. Please contact an administrator.</div></div>';
        exit;
    }else{
        foreach ($des_project as $project) {
            $result = $module->query("SELECT * FROM `redcap_user_rights` WHERE project_id=? AND username=?" ,[$project,USERID]);
            if ($result->num_rows > 0) {
                $has_permission = true;
            }
        }
    }
}else{
    echo '<div class="container" style="margin-top: 60px"><div class="alert alert-warning" role="alert">This Browser has not yet been set up. Please go to the “<strong>External Modules</strong>” menu and configure the Data Model Browser.</div></div>';
    exit;
}

if(!$has_permission){
    echo '<div class="container" style="margin-top: 60px"><div class="alert alert-warning" role="alert">You don\'t have permissions to access this Browser. Please contact an administrator.</div></div>';
    exit;
}
if($has_permission){
    if($_REQUEST['option'] !== 'search' && $_REQUEST['option'] !== 'variableInfo'  ) {
        include('downloadButtons.php');
    }
?>
<div class="container-fluid wiki_container">
    <?PHP
    if( !array_key_exists('option', $_REQUEST) )
    {
        include('pages/wiki_tables.php');
    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'variables' )
    {
        include('pages/wiki_variables.php');
    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'variableInfo' )
    {
        include('pages/wiki_variable_info.php');
    }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'search' )
    {
        include('pages/wiki_variable_search.php');
    }
    ?>
</div>
<?php } ?>