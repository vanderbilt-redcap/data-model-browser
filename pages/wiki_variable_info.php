<?php
namespace Vanderbilt\DataModelBrowserExternalModule;

#We get the Tables and Variables information
$tid = $_REQUEST['tid'];
$vid = $_REQUEST['vid'];

$RecordSetDataModel = \REDCap::getData($pidsArray['DATAMODEL'], 'array', array('record_id' => $tid));
$dataTable = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetDataModel,$pidsArray['DATAMODEL']);
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
                                $recordSetCodeList = \REDCap::getData([
                                                                          'project_id' => $pidsArray['CODELIST'],
                                                                          'return_format' => 'array',
                                                                          'records' => $data['code_list_ref'][$vid],
                                                                          'filterType' => "RECORD"
                                                                      ]);
                                $codeFormatData = ProjectData::getProjectInfoArrayRepeatingInstruments($recordSetCodeList,$pidsArray['CODELIST'])[0];

                                // Normalize code format fields (handle both array and non-array cases for old and new REDCap data formats)
                                $codeFormat = is_array($codeFormatData['code_format']) ? $codeFormatData['code_format'][1] : $codeFormatData['code_format'];
                                $codeList = is_array($codeFormatData['code_list']) ? $codeFormatData['code_list'][1] : $codeFormatData['code_list'];
                                $codeFile = is_array($codeFormatData['code_file']) ? $codeFormatData['code_file'][1] : $codeFormatData['code_file'];
                                $codelistUpdateD= is_array($codeFormatData['codelist_update_d']) ? $codeFormatData['codelist_update_d'][1] : $codeFormatData['codelist_update_d'];

                                switch ($codeFormat) {
                                    case '1': // Code format 1: List of codes separated by pipe character
                                        echo "<span><i>(coded)</i></span><br/><br/>";

                                        ProjectData::renderCodeOptions($codeList, $data['code_text'][$vid], 'table');

                                        break;

                                    case '3': // Code format 3: CSV file upload with codes in column 1 and labels in column
                                        echo $dataFormat." <span><i>(coded)</i></span>";
                                        if ($codeFile != "") {
                                            $codeTable = "true";
                                        }
                                        break;

                                    default: // Other formats
                                        echo filter_tags($dataFormat) . " <span><i>(coded)</i></span>";
                                        break;
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
                            <?php if(!empty($codelistUpdateD)) {
                                ?><span style="display:block;"><i>Last code list update: <?=htmlspecialchars($codelistUpdateD,ENT_QUOTES)?></i></span><?php
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
                                    <?php if (!empty($codeTable)): ?>
                                        <table class="table table-bordered table-hover code_modal_table">
                                            <?php
                                            $csv = parseCSVtoArray($module, $codeFile);

                                            // Check if CSV is empty
                                            if (empty($csv)) {
                                                echo '<tr><td colspan="100" style="text-align: center; color: red;">No Codes found for file: ' . htmlspecialchars($codeFile, ENT_QUOTES) . '</td></tr>';
                                            } else {
                                                // Loop through CSV data
                                                foreach ($csv as $header => $content) {
                                                    echo '<tr>';
                                                    $isHeaderRow = ($header === 0); // Check if it's the header row
                                                    $counter = 1;

                                                    foreach ($content as $col => $value) {
                                                        // Escape and encode value
                                                        $value = mb_convert_encoding($value, 'UTF-8', 'HTML-ENTITIES');
                                                        $style = ($counter % 2 === 0) ? '' : 'text-align: center'; // Apply style for odd columns

                                                        // Render table cells
                                                        if ($isHeaderRow) {
                                                            echo '<td>' . htmlspecialchars($col, ENT_QUOTES) . '</td>';
                                                        } else {
                                                            echo '<td style="' . $style . '">' . htmlspecialchars($value, ENT_QUOTES) . '</td>';
                                                        }
                                                        $counter++;
                                                    }

                                                    echo '</tr>';
                                                }
                                            }
                                            ?>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        <?php } ?>
    </div>
</div>