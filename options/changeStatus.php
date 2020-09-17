<?php
require_once __DIR__ ."/../projects.php";
session_start();
$settings = \REDCap::getData(array('project_id'=>DES_SETTINGS),'array')[1][$module->framework->getEventId(DES_SETTINGS)];

$value = $_POST['value'];
$status = $_POST['status'];
$_SESSION[$status.'_'.$settings['des_wkname']] = $value;

echo json_encode("");

?>