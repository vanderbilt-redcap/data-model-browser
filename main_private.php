
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
    }else if(isUserExpiredOrSuspended($module,USERID, 'user_suspended_time') || isUserExpiredOrSuspended($module,USERID, 'user_expiration')) {
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
    }else if(isUserExpiredOrSuspended($module,USERID, 'user_suspended_time') || isUserExpiredOrSuspended($module,USERID, 'user_expiration')) {
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