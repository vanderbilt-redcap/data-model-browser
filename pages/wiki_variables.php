<?php
$deprecated = empty($_REQUEST['deprecated_'.$settings['des_wkname']]) ? $_SESSION['draft_'.$settings['des_wkname']] : $_REQUEST['deprecated_'.$settings['des_wkname']];
$draft = empty($_REQUEST['draft']) ? $_SESSION['draft_'.$settings['des_wkname']] : $_REQUEST['draft_'.$settings['des_wkname']];
$tid = empty($_REQUEST['tid']) ? "" : $_REQUEST['tid'];
$vid = empty($_REQUEST['vid']) ? "" : $_REQUEST['vid'];

if(!empty($_POST['deprecated_'.$settings['des_wkname']])){
    $_SESSION['deprecated_'.$settings['des_wkname']] = $_POST['deprecated_'.$settings['des_wkname']];
}

if(!empty($_POST['draft_'.$settings['des_wkname']])){
    $_SESSION['draft_'.$settings['des_wkname']] = $_POST['draft_'.$settings['des_wkname']];
}

if(empty($draft)){
    $draft = 'false';
}
if(empty($deprecated)){
    $deprecated = 'false';
}

#We get the Tables and Variables information
$dataTable = getTablesInfo($module,DES_DATAMODEL,$tid);

?>
<br/>
<br/>
<div class="wiki_main">
    <div class='row'>
            <?PHP foreach( $dataTable as $data ){
                   if(!empty($data['record_id'])) {?>
                    <div class="col-md-12">
                        <span class="wiki_title"><?PHP echo $data['table_name'];?></span>
                        <?php
                        if (array_key_exists('table_status', $data)) {
                            if ($data['table_status'] == "2") {
                                ?><span class="wiki_deprecated_draft_message"><span class="fa fa-exclamation-circle wiki_deprecated"></span> <em>Deprecated</em></span><?php
                            }
                            if ($data['table_status'] == "0") {
                                ?><span class="wiki_deprecated_draft_message"><span class="fa fa-clock-o wiki_draft"></span> <em>Draft</em></span><?php
                            }
                        }
                        ?>
                    </div>
                    <div class="col-md-12 wiki_text wiki_text_size">
                        <span style="display:block;"><?PHP echo mb_convert_encoding($data['table_definition'],'UTF-8','HTML-ENTITIES'); ?></span>
                        <span style="display:block;"><i><?PHP echo $data['text_top']; ?></i></span>
                    </div>

                    <div class="col-md-12">
                        <span class="wiki_title_small">Table status</span>
                    </div>
                    <div class="col-md-12 wiki_text wiki_text_size">
                        <?php
                            if (array_key_exists('table_status', $data)) {
                                if ($data['table_status'] == "0") {
                                    $date_d = "";
                                    if(array_key_exists('table_added_d', $data) && !empty($data['table_added_d'])){
                                        $date_d = "(created ".$data['table_added_d'].")";
                                    }
                                    ?><span style="display:block;">Draft <?=$date_d?></span><?php
                                }
                                if ($data['table_status'] == "1") {
                                    $date_d = "";
                                    if(array_key_exists('table_added_d', $data) && !empty($data['table_added_d'])){
                                        $date_d = "(as of ".$data['table_added_d'].")";
                                    }
                                    ?><span style="display:block;">Active <?=$date_d?></span><?php
                                }
                                if ($data['table_status'] == "2") {
                                    $date_d = "";
                                    if(array_key_exists('table_deprecated_d', $data) && !empty($data['table_deprecated_d'])){
                                        $date_d = "(as of ".$data['table_deprecated_d'].")";
                                    }
                                    ?><span style="display:block;">Deprecated <?=$date_d?></span><?php

                                }
                            }else if (empty($data['table_status'])) {
                                ?><span style="display:block;">Status Unknown</span><?php
                            }
                        ?>
                    </div>

                    <div class="col-md-12" style="padding-bottom: 10px">
                        <?php include('/../options/options.php'); ?>
                    </div>
                    <div class="col-md-12">
                        <div class="panel panel-default" >
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    Variables
                                </h3>
                            </div>
                            <div id="collapse3" class="table-responsive panel-collapse collapse in" aria-expanded="true">
                                <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                                    <?php
                                    echo '<thead>'.'
                                    <tr>'.'
                                        <th>Field Name</th>'.'
                                        <th>Format</th>'.'
                                        <th>Description</th>'.'
                                    </tr>'.'
                                    </thead>';

                                    foreach ($data['variable_order'] as $id => $value) {
                                        $variable_display = "";
                                        $variable_text = "";
                                        $deprecated_text = "";
                                        $variable_class = "";
                                        if (array_key_exists('variable_status', $data) && array_key_exists($id, $data['variable_status'])) {
                                            if($data['variable_status'][$id] == "0"){//DRAFT
                                                if($draft == 'false') {//DEPRECATED
                                                    $variable_display = "display:none";
                                                }
                                                $variable_text = "<span><em class='fa fa-clock-o wiki_draft'></em> <em>Draft</em></span><br/>";
                                                $variable_class = "draft";
                                            }else if($data['variable_status'][$id] == "2"){
                                                if($deprecated == 'false') {//DEPRECATED
                                                    $variable_display = "display:none";
                                                }
                                                $variable_text = "<span><em class='fa fa-exclamation-circle wiki_deprecated'></em> <em>Deprecated</em></span><br/>";
                                                $variable_class = "deprecated";

                                                if($data['variable_replacedby'][$id] != ""){
                                                    $variable_replacedby = explode("|",$data['variable_replacedby'][$id]);
                                                    $table = getProjectInfoArray(DES_DATAMODEL,array('record_id' => $variable_replacedby[0]),"simple");
                                                    $table_name = $table['table_name'];
                                                    $var_name = $table['variable_name'][$variable_replacedby[1]];

                                                    $deprecated_text .= "<div><em>This variable was deprecated on ".date("d M Y",strtotime($data['variable_deprecated_d'][$id]))." and replaced with ".$table_name." | ".$var_name.".</em></div>";
                                                }else if($data['variable_replacedby'][$id] == "" && $data['variable_deprecated_d'][$id] != ""){
                                                    $deprecated_text .= "<div><em>This variable was deprecated on ".date("d M Y",strtotime($data['variable_deprecated_d'][$id])).".</em></div>";
                                                }else if($data['variable_replacedby'][$id] == "" && $data['variable_deprecated_d'][$id] == ""){
                                                    $deprecated_text .= "<div><em>This variable was deprecated.</div>";
                                                }
                                                $deprecated_text .= "<div><em>".$data['variable_deprecatedinfo'][$id]."</em></div>";
                                            }
                                        }

                                        $required_class = '';
                                        $required_text = '';
                                        if($data['variable_required'][$id][1] == '1'){
                                            $required_class = 'required_des';
                                            $required_text="<div style='color:red'><em>*Required</em></div>";
                                        }

                                        $record_var_aux = empty($id) ? '1' : $id;
                                        $record_var = $id;
                                        $name = $data['variable_name'][$id];
                                        $url = $module->getUrl("browser.php?pid=".$_GET['pid'].'&tid='. $tid . '&vid=' . $record_var . '&option=variableInfo');
                                        echo '<tr class="'.$required_class." ".$variable_class.'" style="' . $variable_display . '"" id="'.$record_var_aux.'_row">' .
                                            '<td style="width:130px">' .
                                            '<a href="'.$url.'">' . $name . '</a>' .
                                            '</td>' .
                                            '<td style="width:350px">';

                                        $dataFormat = $dataTable['data_format_label'][$data['data_format'][$id]];
                                        if ($data['has_codes'][$id] != '1') {
                                            echo $dataFormat;
                                        } else if ($data['has_codes'][$id] == '1') {
                                            if(!empty($data['code_list_ref'][$id])){
                                                $codeformat = getProjectInfoArray(DES_CODELIST,array('record_id' => $data['code_list_ref'][$id]),'simple')[0];

                                                if ($codeformat['code_format'] == '1') {
                                                    $codeOptions = empty($codeformat['code_list']) ? $data['code_text'][$id] : explode(" | ", $codeformat['code_list']);
                                                    if (!empty($codeOptions[0])) {
                                                        $dataFormat .= "<div style='padding-left:15px'>";
                                                    }
                                                    foreach ($codeOptions as $option) {
                                                        $dataFormat .= $option . "<br/>";
                                                    }
                                                    if (!empty($codeOptions[0])) {
                                                        $dataFormat .= "</div>";
                                                    }
                                                    echo $dataFormat;

                                                } else if ($codeformat['code_format'] == '3') {
                                                    echo $dataFormat.'<br/>';

                                                    if (array_key_exists('code_file', $codeformat) && $codeformat['code_file'] != "") {
                                                        echo '<a href="#codesModal'.$codeformat['code_file'].'_'.$name.'" id="btnViewCodes" type="button" class="btn_code_modal open-codesModal" data-toggle="modal" data-target="#codesModal'.$codeformat['code_file'].'_'.$name.'">See Code List</a>';

                                                        #modal window with the updates
                                                        echo '<div class="modal fade" id="codesModal'.$codeformat['code_file'].'_'.$name.'" role="dialog" aria-labelledby="Codes">' .
                                                            '<div class="modal-dialog" role="document">' .
                                                            '<div class="modal-content">' .
                                                            '<div class="modal-header">' .
                                                            '<button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' .
                                                            '<h4 class="modal-title">Codes</h4>'.
                                                            '</div>'.
                                                            '<div class="modal-body">'.
                                                            '<div class="row" style="padding:30px;">'.
                                                            '<div class="panel panel-default">'.
                                                            '<div class="panel-heading">'.$name.'</div>'.
                                                            '<div class="table-responsive panel-collapse collapse in">'.
                                                            '<table border="1" class="code_modal_table">';
                                                        $csv = parseCSVtoArray($codeformat['code_file']);
                                                        if(empty($csv)){
                                                            echo '<div style="text-align: center;color:red;">No Codes found for file:'.$codeformat['code_file'].'</div>';
                                                        }
                                                        foreach ($csv as $header=>$content){
                                                            if($header == 0){
                                                                echo '<tr>';
                                                            }else{
                                                                echo '<tr>';
                                                            }
                                                            foreach ($content as $col=>$value) {
                                                                //Convert to UTF-8 to avoid weird characters
                                                                $value = mb_convert_encoding($value,'UTF-8','HTML-ENTITIES');
                                                                if($header == 0){
                                                                    echo '<td class="code_modal_td">'.$col.'</td>';
                                                                }else{
                                                                    echo '<td class="code_modal_td">'.$value.'</td>';
                                                                }
                                                            }
                                                            echo '</tr>';
                                                        }
                                                        echo '</table></div></div>'.
                                                            '</div>'.
                                                            '</div>'.
                                                            '<div class="modal-footer">' .
                                                            '<button type="button" class="btn btn-default" id="btnCloseCodesModal" data-dismiss="modal">CLOSE</button>' .
                                                            '</div>'.
                                                            '</div></div></div>';
                                                    }
                                                } else if ($codeformat['code_format'] == '4') {
                                                    echo "<a href='https://bioportal.bioontology.org/ontologies/".$codeformat['code_ontology']."' target='_blank'>See Ontology Link</a><br/>";
                                                }
                                            } else {
                                                echo $dataFormat;
                                            }
                                        }
                                        echo '</td><td id="'.$record_var_aux.'_description"><div style="padding-bottom: 8px;padding-top: 8px">'.$required_text;
                                        echo "<div>".$variable_text.mb_convert_encoding($data['description'][$id], 'UTF-8','HTML-ENTITIES')."</div>";
                                        if (!empty($data['description_extra'][$id])) {
                                            echo "<div><i>" . $data['description_extra'][$id] . "</i></div>";
                                        }
                                        if (!empty($data['code_text'][$id])) {
                                            echo "<div><i>" . $data['code_text'][$id] . "</i></div>";
                                        }
                                        echo $deprecated_text;
                                        echo '</div></td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </table>
                            </div>
                        </div>
                    </div>
                <?PHP }

            }?>
    </div>
</div>