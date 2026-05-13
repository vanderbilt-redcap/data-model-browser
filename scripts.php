<?php

namespace Vanderbilt\DataModelBrowserExternalModule;

echo $module->loadREDCapJS(); ?>

<script src="<?= $module->getUrl('js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= $module->getUrl('js/dataTables.select.min.js') ?>"></script>
<script src="<?= $module->getUrl('js/dataTables.buttons.min.js') ?>"></script>

<script type="text/javascript" src="<?= $module->getUrl('js/functions.js') ?>"></script>
<script type="text/javascript" src="<?= $module->getUrl('js/jquery-ui.min.js') ?>"></script>
<script type="text/javascript" src="<?= $module->getUrl('js/jquery.tablesorter.min.js') ?>"></script>

<link type='text/css' href='<?= $module->getUrl('js/fonts-awesome/css/font-awesome.min.css') ?>' rel='stylesheet'
      media='screen'/>
<link rel="stylesheet" type="text/css" href="<?= $module->getUrl('css/bootstrap.min.css') ?>">
<link rel="stylesheet" type="text/css" href="<?= $module->getUrl('css/style.css') ?>">
<link type='text/css' href='<?= $module->getUrl('css/tabs-steps-menu.css') ?>' rel='stylesheet' media='screen'/>
<link type='text/css' href='<?= $module->getUrl('css/sortable-theme-bootstrap.css') ?>' rel='stylesheet'
      media='screen'/>
<link type='text/css' href='<?= $module->getUrl('css/jquery-ui.min.css') ?>' rel='stylesheet' media='screen'/>

<script>
    var startDDProjects_url = <?=json_encode($module->getUrl('startDDProjects.php'))?>;
    var downloadPDF_AJAX_url = <?=json_encode($module->getUrl('options/downloadPDF_AJAX.php'))?>;
    var pid = <?=json_encode($pid)?>;
</script>
