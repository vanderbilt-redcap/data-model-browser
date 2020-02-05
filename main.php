<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="<?=printFile($module,$settings['des_favicon'],'url')?>">

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
    <?php
    if($_REQUEST['option'] !== 'search' && $_REQUEST['option'] !== 'variableInfo'  ) {
        include('downloadButtons.php');
    }
    ?>
</head>
<body>
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
<br/>
</body>
</html>