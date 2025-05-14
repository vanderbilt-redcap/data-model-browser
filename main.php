<?php namespace Vanderbilt\DataModelBrowserExternalModule; ?>
<body>
<?php
if($_REQUEST['option'] !== 'search' && $_REQUEST['option'] !== 'variableInfo' && $_REQUEST['option'] !== 'json') {
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
        }else if( array_key_exists('option', $_REQUEST) && $_REQUEST['option'] === 'json' ) {
            include('jsoncopy/copyfiles.php');
        }
    ?>
</div>
