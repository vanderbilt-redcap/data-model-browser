<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

$style = '';
if(array_key_exists('page',$_REQUEST)){
    $style = 'padding-bottom:10px;';
}
?>
    <div class="nav-padding-left">
        <div class="nav nav-pills" style="margin-top: 100px;<?=$style?>">
            <?PHP
            $active = "";
            $path = "";
            if( !array_key_exists('option', $_REQUEST)) {
                $active = "class='wiki_active'";
            }
            ?>
            <?php if( array_key_exists('option', $_REQUEST)){
               echo '<a href="'.$module->getUrl($page."&pid=".$_GET['pid']).'" '.$active.' style="padding-top: 40px;">Home</a>';
            }
            if( array_key_exists('option', $_REQUEST) && ($_REQUEST['option'] === 'variables' || $_REQUEST['option'] === 'variableInfo')){
                $tid = (int)$_REQUEST['tid'];
                $vid = isset($_REQUEST['vid']) ? (int)$_REQUEST['vid']:"";
                $dataTable = getTablesInfo($module,$pidsArray['DATAMODEL'],$tid);
                $active = "";
                foreach( $dataTable as $data ) {
                    if (!empty($data['record_id'])) {
                        #rearrange the array to start at 1 to match the variables
                        $data['variable_name'] = array_combine(range(1, count($data['variable_name'])), $data['variable_name']);
                        if ($_REQUEST['option'] === 'variables' || $_REQUEST['option'] === 'variableInfo') {
                            if($_REQUEST['option'] === 'variables') {
                                $active = "class='wiki_active'";
                            }
                            $url = $module->getUrl($page).'&NOAUTH&tid=' . $data['record_id'] . '&option=variables';
                            ?>
                            <span> > </span>
                            <a href="<?=$url?>" <?=$active?>><?= htmlentities($data['table_name'],ENT_QUOTES) ?></a>
                        <?php }
                        if ($_REQUEST['option'] === 'variableInfo') {
                            $active = "class='wiki_active'";
                            $url = $module->getUrl($page."&pid=".$_GET['pid']."&tid=".$tid ."&vid=". $vid ."&option=variableInfo");
                            ?>
                            <span> > </span>
                           <a href="<?=$url?>" <?=$active?>><?= htmlentities($data['variable_name'][$vid],ENT_QUOTES) ?></a>
                        <?php }
                    }
                }
            }else if($_REQUEST['option'] == 'search'){
                echo "<span> > </span><a href=".$page."'&pid=".$pidsArray['DATAMODEL']."&option=search' class='wiki_active'>Variable search</a>";
            }?>

        </div>
    </div>