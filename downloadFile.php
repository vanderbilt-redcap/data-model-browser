<?php
namespace Vanderbilt\DataModelBrowserExternalModule;
require_once "projects.php";

$filename = htmlspecialchars($_REQUEST['file'], ENT_QUOTES);
$sname = htmlspecialchars($_REQUEST['sname'], ENT_QUOTES);

header('Content-type: application/pdf');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
@readfile($module->framework->getSafePath($sname, EDOC_PATH));
?>