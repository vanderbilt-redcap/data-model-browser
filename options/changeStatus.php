<?php
require_once __DIR__ ."/../projects.php";
session_start();
$RecordSetSettings = \REDCap::getData($pidsArray['SETTINGS'], 'array');
$settings = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSettings,$pidsArray['SETTINGS'])[0];

$value = $_POST['value'];
$status = $_POST['status'];
$_SESSION[$status.'_'.$settings['des_wkname']] = $value;

echo json_encode("");

?>