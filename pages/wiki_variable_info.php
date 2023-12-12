<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

#We get the Tables and Variables information
$tid = $_REQUEST['tid'];
$vid = $_REQUEST['vid'];

$RecordSetDataModel = \REDCap::getData($pidsArray['DATAMODEL'], 'array', array('record_id' => $tid));
$dataTable = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetDataModel);
$dataformatChoices = $module->getChoiceLabels('data_format', $pidsArray['DATAMODEL']);
?>

<br/>
<div class="container-fluid">
    <div class='row' style=''>
        <?PHP foreach( $dataTable as $data ){
            if(!empty($data['record_id']) && $data['variable_status'][$vid] != "" && $data['variable_status'][$vid] != 3) {
                ?>
                <div class="col-md-12">
                    <span class="wiki_title"><?PHP echo htmlspecialchars($data['variable_name'][$vid],ENT_QUOTES);?></span>
                </div>
                <div class="col-md-12 wiki_text wiki_text_size">
                    <span style="display:block;"><?PHP echo htmlspecialchars($data['description'][$vid],ENT_QUOTES); ?></span>
                    <?php if (!empty($data['description_extra'][$vid])) {
                        ?><span style="display:block;"><i><?PHP echo htmlspecialchars($data['description_extra'][$vid],ENT_QUOTES); ?></i></span><?php
                    }?>
                </div>

                <div class="col-md-12">
                    <span class="wiki_title_small">Format</span>
                    <div class="wiki_text_inside wiki_text_size">
                    <?php
                        $codeTable = "";
                        $dataFormat = $dataformatChoices[$data['data_format'][$vid]];
                        if ($data['has_codes'][$vid] == '0' || empty($data['has_codes'][$vid])) {
                            echo $dataFormat;
                            if (!empty($data['code_text'][$vid])) {
                                echo "<br/>".htmlspecialchars($data['code_text'][$vid],ENT_QUOTES);
                            }
                        } else if ($data['has_codes'][$vid] == '1') {
                            if(!empty($data['code_list_ref'][$vid])){
                                $RecordSetCodeList = \REDCap::getData($pidsArray['CODELIST'], 'array', array('record_id' => $data['code_list_ref'][$vid]));
                                $codeformat = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetCodeList)[0];
                                if ($codeformat['code_format'] == '1') {
                                    $dataFormat .= " <span><i>(coded)</i></span><br/><br/>";

                                    $codeOptions = empty($codeformat['code_list']) ? $data['code_text'][$vid] : explode(" | ", $codeformat['code_list']);

                                    if (!empty($codeOptions[0])) {
                                        $dataFormat .= "<div class='wiki'><div class='panel panel-default'><table border='1' class='table table-bordered table-hover' style='font-size: 13px;'><div class='table-responsive panel-collapse collapse in'>";
                                        $dataFormat .= "<tbody><tr><td class=''>Code</td>";
                                        $dataFormat .= "<td class=''>Definition</td></tr>";
                                    }
                                    foreach ($codeOptions as $option) {
                                        $var_codes = preg_split("/=.*?/", $option);
                                        if($var_codes[0] == ""){
                                            //split by = except if the first character is =
                                            $var_codes = preg_split("/([^=])(=)(.*?)/", $option);
                                        }
                                        $dataFormat .= "<tr><td style='text-align: center;'>".trim($var_codes[0])."</td><td>".trim($var_codes[1])."</td></tr>";
                                    }
                                    if (!empty($codeOptions[0])) {
                                        $dataFormat .= "</tbody></table></div></div></div>";
                                    }
                                    echo filter_tags($dataFormat);

                                } else if ($codeformat['code_format'] == '3') {
                                    echo $dataFormat." <span><i>(coded)</i></span>";
                                    if (array_key_exists('code_file', $codeformat) && $codeformat['code_file'] != "") {
                                        $codeTable = "true";
                                    }
                                } else {
                                    echo filter_tags($dataFormat)." <span><i>(coded)</i></span>";
                                }
                            }
                        }
                    ?>
                    </div>
                </div>

                <div class="col-md-12">
                    <span class="wiki_title_small">Variable status</span>
                    <div class="wiki_text_inside wiki_text_size">
                        <?php if($data['variable_required'][$vid][1] == '1'){
                            ?><span style='color:red'><em>*Required</em></span><?php
                        }
                        if (array_key_exists('variable_status', $data) && array_key_exists($vid, $data['variable_status'])) {
                            if ($data['variable_status'][$vid] == "0") {
                                $date_d = "";
                                if(array_key_exists('variable_added_d', $data) && !empty($data['variable_added_d'][$vid])){
                                    $date_d = "(".$data['variable_added_d'][$vid].")";
                                }
                                ?><span style="display:block;"><span class="fa fa-clock-o wiki_draft"></span> <em>Draft <?=htmlspecialchars($date_d,ENT_QUOTES)?></em></span><?php
                            }
                            if ($data['variable_status'][$vid] == "1") {
                                $date_d = "";
                                if(array_key_exists('variable_added_d', $data) && !empty($data['variable_added_d'][$vid])){
                                    $date_d = "(".$data['variable_added_d'][$vid].")";
                                }
                                ?><span style="display:block;"><span class="fa fa-check wiki_activevar"></span> <em>Active <?=htmlspecialchars($date_d,ENT_QUOTES)?></em></span><?php
                            }
                            if ($data['variable_status'][$vid] == "2") {
                                $date_d = "";
                                if(array_key_exists('variable_deprecated_d', $data) && !empty($data['variable_deprecated_d'][$vid])){
                                    $date_d = "(".$data['variable_deprecated_d'][$vid].")";
                                }
                                ?><span style="display:block;"><em class='fa fa-exclamation-circle wiki_deprecated'></em> <em>Deprecated <?=htmlspecialchars($date_d,ENT_QUOTES)?></em></span><?php

                            }
                        }else if (empty($data['variable_status'])) {
                            ?><span style="display:block;">Status Unknown</span><?php
                        }
                        ?>
                    </div>
                </div>

                <?php if(!empty($codeTable)){?>
                    <div class="col-md-12">
                        <span class="wiki_title_small">Code list</span>
                        <div class="wiki_text_inside wiki_text_size">
                            <span style="display:block;"><?PHP echo htmlspecialchars($data['variable_name'][$vid],ENT_QUOTES);?> codes ( <i class="fa fa-arrow-down"></i> <a href="<?= $module->getUrl('downloadFile.php?' . parseCSVtoLink($module,$codeformat['code_file']));?>" target="_blank">Download CSV</a> )</span>
                            <?php if(!empty($codeformat) && array_key_exists('codelist_update_d', $codeformat) && !empty($codeformat['codelist_update_d'])) {
                                ?><span style="display:block;"><i>Last code list update: <?=htmlspecialchars($codeformat['codelist_update_d'],ENT_QUOTES)?></i></span><?php
                            }else{
                                ?><span style="display:block;"><i>Unknown Date</i></span><?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="wiki wiki_text_inside" style="padding-top: 0;">
                            <div class="panel panel-default">
                                <div class="panel-heading"><?PHP echo htmlspecialchars($data['variable_name'][$vid],ENT_QUOTES);?></div>
                                <div class="table-responsive panel-collapse collapse in">
                                    <table class="table table-bordered table-hover code_modal_table">
                                        <?PHP if(!empty($codeTable)){ ?>
                                            <?PHP
                                            $csv = \Vanderbilt\DataModelBrowserExternalModule\parseCSVtoArray($module,$codeformat['code_file']);
                                            if(empty($csv)){
                                                ?><div style="text-align: center;color:red;">No Codes found for file: <?=$codeformat['code_file']?></div><?PHP
                                            }
                                            foreach ($csv as $header=>$content){
                                                ?><tr><?PHP
                                                $counter = 1;
                                                foreach ($content as $col=>$value) {
                                                    $style = "";
                                                    if($counter % 2){
                                                        $style = "text-align: center";
                                                    }
                                                    $value = mb_convert_encoding($value,'UTF-8','HTML-ENTITIES');
                                                    if($header == 0){
                                                        ?>
                                                        <td class=""><?=$col;?></td>
                                                        <?PHP
                                                    }else{
                                                        ?>
                                                        <td style="<?=$style?>"><?=$value;?></td>
                                                        <?PHP
                                                    }
                                                    $counter++;
                                                }
                                                ?></tr><?PHP
                                            }
                                            ?>
                                        <?PHP } ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        <?php } ?>
    </div>
</div>