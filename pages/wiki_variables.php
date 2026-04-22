<?php
namespace Vanderbilt\DataModelBrowserExternalModule;
use Vanderbilt\DataModelBrowserExternalModule\ProjectData;

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
$RecordSetDataModel = \REDCap::getData($pidsArray['DATAMODEL'], 'array', array('record_id' => $tid));
$dataTable = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetDataModel,$pidsArray['DATAMODEL']);
$dataformatChoices = $module->getChoiceLabels('data_format', $pidsArray['DATAMODEL']);
if(array_key_exists(0, $dataTable) && array_key_exists('variable_order', $dataTable[0]) && !empty($dataTable[0]['variable_order'])){
    asort($dataTable[0]['variable_order']);
}
?>
<script>
    $(document).ready(function() {
        // Initialize all dialogs with the class `.dialog`
        $(".dialog").dialog({
            autoOpen: false,          // Keep modal closed by default
            closeOnEscape: true,      // Allow closing with the Escape key
            width: 700,               // Set the modal width
            modal: true,              // Block interaction with the page
            draggable: false,         // Prevent dragging to keep it centered
            resizable: false,         // Prevent resizing to maintain consistent dimensions
            buttons: [
                {
                    text: "Close",
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            ],
            open: function(event, ui) {
                $("body").css("overflow", "hidden"); // Disable scrolling on the background
                $(".ui-dialog").css({
                    "top": "50%",                     // Center the modal vertically
                    "left": "50%",                    // Center the modal horizontally
                    "transform": "translate(-50%, -50%)", // Adjust for proper centering
                    "position": "fixed"              // Ensure it stays fixed in the viewport
                });

                // Scroll the dialog content to the top when it opens
                $(this).closest(".ui-dialog").scrollTop(0);

                // Add click listener to the overlay (background) to close the dialog
                $(".ui-widget-overlay").on("click", function() {
                    $(".dialog").dialog("close"); // Close the dialog
                });
            },
            close: function(event, ui) {
                $("body").css("overflow", "auto"); // Re-enable scrolling on the background

                // Remove the click listener from the overlay
                $(".ui-widget-overlay").off("click");
            }
        });
    });
</script>
<style>
    .dialog,
    .ui-dialog {
        display: none; /* Keep it hidden by default */
        position: fixed; /* Ensure it is positioned relative to the viewport */
        z-index: 1100; /* Set a high z-index to ensure it appears above other elements */
        top: 10%; /* Vertically center the modal */
        left: 50%; /* Horizontally center the modal */
        transform: translate(-50%, 0); /* Centering adjustment */
        background-color: white; /* Ensure modal has a background */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Add shadow for better visibility */
        padding: 20px; /* Add padding for content */
        border-radius: 8px; /* Optional: rounded corners */
        overflow-y: auto; /* Enable vertical scrolling for modal content */
        max-height: 80vh; /* Limit modal height to 80% of the viewport */
        width: 700px; /* Set modal width */
    }
</style>
<br/>
<br/>
<div class="wiki_main">
    <div class='row'>
            <?PHP foreach( $dataTable as $data ){
                   if(!empty($data['record_id']) && $data['table_status'] != "3") {?>
                    <div class="col-md-12">
                        <span class="wiki_title"><?PHP echo htmlspecialchars($data['table_name'],ENT_QUOTES);?></span>
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
                        <span style="display:block;"><i><?PHP echo filter_tags($data['text_top']); ?></i></span>
                        <span style="display:block;"><?PHP echo mb_convert_encoding($data['table_definition'],'UTF-8','HTML-ENTITIES'); ?></span>
                        <span style="display:block;"><i><?PHP echo filter_tags($data['text_bottom']); ?></i></span>
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
                                    ?><span style="display:block;">Draft <?=htmlspecialchars($date_d,ENT_QUOTES)?></span><?php
                                }
                                if ($data['table_status'] == "1") {
                                    $date_d = "";
                                    if(array_key_exists('table_added_d', $data) && !empty($data['table_added_d'])){
                                        $date_d = "(as of ".$data['table_added_d'].")";
                                    }
                                    ?><span style="display:block;">Active <?=htmlspecialchars($date_d,ENT_QUOTES)?></span><?php
                                }
                                if ($data['table_status'] == "2") {
                                    $date_d = "";
                                    if(array_key_exists('table_deprecated_d', $data) && !empty($data['table_deprecated_d'])){
                                        $date_d = "(as of ".$data['table_deprecated_d'].")";
                                    }
                                    ?><span style="display:block;">Deprecated <?=htmlspecialchars($date_d,ENT_QUOTES)?></span><?php

                                }
                            }else if (empty($data['table_status'])) {
                                ?><span style="display:block;">Status Unknown</span><?php
                            }
                        ?>
                    </div>

                    <div class="col-md-12" style="padding-bottom: 10px">
                        <?php include(__DIR__ .'/../options/options.php'); ?>
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
                                        if($data['variable_status'][$id] != "" && $data['variable_status'][$id] != 3) {
                                            if (array_key_exists('variable_status', $data) && array_key_exists($id, $data['variable_status'])) {
                                                if ($data['variable_status'][$id] == "0") {//DRAFT
                                                    if ($draft == 'false') {//DEPRECATED
                                                        $variable_display = "display:none";
                                                    }
                                                    $variable_text = "<span><em class='fa fa-clock-o wiki_draft'></em> <em>Draft</em></span><br/>";
                                                    $variable_class = "draft";
                                                } else if ($data['variable_status'][$id] == "2") {
                                                    if ($deprecated == 'false') {//DEPRECATED
                                                        $variable_display = "display:none";
                                                    }
                                                    $variable_text = "<span><em class='fa fa-exclamation-circle wiki_deprecated'></em> <em>Deprecated</em></span><br/>";
                                                    $variable_class = "deprecated";

                                                    if ($data['variable_replacedby'][$id] != "") {
                                                        $variable_replacedby = explode("|", $data['variable_replacedby'][$id]);
                                                        $RecordSetTable = \REDCap::getData($pidsArray['DATAMODEL'], 'array', array('record_id' => $variable_replacedby[0]));
                                                        $table = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetTable,$pidsArray['DATAMODEL'])[0];
                                                        $table_name = htmlspecialchars($table['table_name'],ENT_QUOTES);
                                                        $var_name = htmlspecialchars($table['variable_name'][$variable_replacedby[1]],ENT_QUOTES);

                                                        $deprecated_text .= "<div><em>This variable was deprecated on " . htmlspecialchars(date("d M Y", strtotime($data['variable_deprecated_d'][$id])),ENT_QUOTES) . " and replaced with " . $table_name . " | " . $var_name . ".</em></div>";
                                                    } else if ($data['variable_replacedby'][$id] == "" && $data['variable_deprecated_d'][$id] != "") {
                                                        $deprecated_text .= "<div><em>This variable was deprecated on " . htmlspecialchars(date("d M Y", strtotime($data['variable_deprecated_d'][$id])),ENT_QUOTES) . ".</em></div>";
                                                    } else if ($data['variable_replacedby'][$id] == "" && $data['variable_deprecated_d'][$id] == "") {
                                                        $deprecated_text .= "<div><em>This variable was deprecated.</div>";
                                                    }
                                                    $deprecated_text .= "<div><em>" . htmlspecialchars($data['variable_deprecatedinfo'][$id],ENT_QUOTES) . "</em></div>";
                                                }
                                            }

                                            $required_class = '';
                                            $required_text = '';
                                            if ($data['variable_required'][$id][1] == '1') {
                                                $required_class = 'required_des';
                                                $required_text = "<div style='color:red'><em>*Required</em></div>";
                                            }

                                            $record_var_aux = htmlspecialchars(empty($id) ? '1' : $id,ENT_QUOTES);
                                            $record_var = $id;
                                            $name = htmlspecialchars($data['variable_name'][$id],ENT_QUOTES);
                                            $url = $module->getUrl($page).'&NOAUTH&tid=' . $data['record_id'] . '&vid=' . $record_var . '&option=variableInfo';
                                            echo '<tr class="' . $required_class . " " . $variable_class . '" style="' . $variable_display . '"" id="' . $record_var_aux . '_row">' .
                                                '<td style="width:130px">' .
                                                '<a href="' . $url . '">' . $name . '</a>' .
                                                '</td>' .
                                                '<td style="width:350px">';

                                            $dataFormat = htmlspecialchars($dataformatChoices[$data['data_format'][$id]],ENT_QUOTES);
                                            if ($data['has_codes'][$id] != '1') {
                                                echo $dataFormat;
                                            } else if ($data['has_codes'][$id] == '1') {
                                                if (!empty($data['code_list_ref'][$id])) {
                                                    $RecordSetCodeList = \REDCap::getData([
                                                                                         'project_id' => $pidsArray['CODELIST'],
                                                                                         'return_format' => 'array',
                                                                                         'records' => $data['code_list_ref'][$id],
                                                                                         'filterType' => "RECORD"
                                                                                     ]);
                                                    $codeFormatData = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetCodeList,$pidsArray['CODELIST'])[0];

                                                    // Normalize code format fields (handle both array and non-array cases for old and new REDCap data formats)
                                                    $codeFormat = is_array($codeFormatData['code_format']) ? $codeFormatData['code_format'][1] : $codeFormatData['code_format'];
                                                    $codeList = is_array($codeFormatData['code_list']) ? $codeFormatData['code_list'][1] : $codeFormatData['code_list'];
                                                    $codeFile = is_array($codeFormatData['code_file']) ? $codeFormatData['code_file'][1] : $codeFormatData['code_file'];
                                                    $codeOntology = is_array($codeFormatData['code_ontology']) ? $codeFormatData['code_ontology'][1] : $codeFormatData['code_ontology'];

                                                    // Handle different code format types
                                                    switch ($codeFormat) {
                                                        case '1': // Code format 1: List of codes separated by pipe character
                                                            ProjectData::renderCodeOptions($codeList, $data['code_text'][$id]);
                                                            break;

                                                        case '3': // Code format 3: CSV file upload with codes in column 1 and labels in column
                                                            ProjectData::renderCodeFileDialog($codeFile, $name, $module);
                                                            break;

                                                        case '4': // Code format 4: Ontology (using BioPortal Ontology Service)
                                                            ProjectData::renderOntologyLink($codeOntology);
                                                            break;

                                                        default:
                                                            echo "Invalid code format.";
                                                            break;
                                                    }
                                                } else {
                                                    echo $dataFormat;
                                                }
                                            }
                                            if (!empty($data['code_text'][$id])) {
                                                echo "<div><i>" . htmlentities($data['code_text'][$id],ENT_QUOTES) . "</i></div>";
                                            }
                                            echo '</td><td id="' . $record_var_aux . '_description"><div style="padding-bottom: 8px;padding-top: 8px">' . filter_tags($required_text);
                                            echo "<div>" . $variable_text . mb_convert_encoding($data['description'][$id], 'UTF-8', 'HTML-ENTITIES') . "</div>";
                                            if (!empty($data['description_extra'][$id])) {
                                                echo "<div><i>" . htmlentities($data['description_extra'][$id],ENT_QUOTES) . "</i></div>";
                                            }
                                            echo $deprecated_text;
                                            echo '</div></td>';
                                            echo '</tr>';
                                        }
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
