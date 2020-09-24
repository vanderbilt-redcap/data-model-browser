<?php
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
                $tid = $_REQUEST['tid'];
                $vid = isset($_REQUEST['vid']) ? $_REQUEST['vid']:"";
                $path = "&tid=".$tid."&vid=".$vid;
                $dataTable = getTablesInfo($module,DES_DATAMODEL,$tid);
                $active = "";
                foreach( $dataTable as $data ) {
                    if (!empty($data['record_id'])) {
                        if ($_REQUEST['option'] === 'variables' || $_REQUEST['option'] === 'variableInfo') {
                            if($_REQUEST['option'] === 'variables') {
                                $active = "class='wiki_active'";
                            }
                            $url = $module->getUrl($page."&pid=".$_GET['pid']."&tid=".$data['record_id']."&option=variables");
                            ?>
                            <span> > </span>
                            <a href="<?=$url?>" <?=$active?>><?= $data['table_name'] ?></a>
                        <?php }

                        if ($_REQUEST['option'] === 'variableInfo') {
                            $active = "class='wiki_active'";
                            $url = $module->getUrl($page."&pid=".$_GET['pid']."&tid=".$tid ."&vid=". $vid ."&option=variableInfo");
                            ?>
                            <span> > </span>
                           <a href="<?=$url?>" <?=$active?>><?= $data['variable_name'][$vid] ?></a>
                        <?php }
                    }
                }
            }else if($_REQUEST['option'] == 'search'){
                echo "<span> > </span><a href=".$page."'&pid=".DES_DATAMODEL."&option=search' class='wiki_active'>Variable search</a>";
            }?>

        </div>
    </div>