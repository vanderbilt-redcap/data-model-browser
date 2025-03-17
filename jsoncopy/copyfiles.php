<?php
namespace Vanderbilt\DataModelBrowserExternalModule;
?>
<div class="alert alert-success fade in" style="display: none;" id="succMsgContainer"></div>
<br/>
<div class="col-md-12 wiki_text wiki_text_size" style="padding-top: 0;padding-bottom: 30px;">
    <div class="col-xs-12 col-md-12" style="padding-bottom:50px;">
        <span class="concepts-title">Regenerate Metadata for Data Model</span>
    </div>

    <div class="col-xs-12 col-md-12" style="padding-bottom:20px;">
        <span>Have you made changes to the data model, code lists, and metadata in REDCap?</span>
    </div>

    <div class="col-xs-12 col-md-12" style="padding-bottom:40px;">
            <span>Update the stored model files so that your changes will carry over to the Harmonist tools and data quality checks:</span>
    </div>

    <div class="col-xs-12 col-md-12" style="padding-bottom:20px;">
            <span><strong>Which part of the Data Model has been revised?</strong></span>
    </div>


    <div class="col-xs-12 col-md-12">
        <div style="padding-bottom: 10px">
            <input type="radio" value="1" name="option">
            <span style="font-style: italic;">Data Model (<a href="<?=APP_PATH_WEBROOT_ALL;?>DataEntry/record_home.php?pid=<?=$pidsArray['DATAMODEL'];?>" target="_blank">Harmonist 0A</a> project in REDCap)</span>
        </div>
        <div style="padding-bottom: 10px">
            <input type="radio" value="2" name="option">
            <span style="font-style: italic;">Code Lists (<a href="<?=APP_PATH_WEBROOT_ALL;?>DataEntry/record_home.php?pid=<?=$pidsArray['CODELIST'];?>" target="_blank">Harmonist 0B</a> project in REDCap)</span>
        </div>
        <div style="padding-bottom: 10px">
            <input type="radio" value="4" name="option">
            <span style="font-style: italic;">Data Model Metadata (<a href="<?=APP_PATH_WEBROOT_ALL;?>DataEntry/record_home.php?pid=<?=$pidsArray['DATAMODELMETADATA'];?>" target="_blank">Harmonist 0C</a> project in REDCap)</span>
        </div>
        <div style="padding-bottom: 10px">
            <input type="radio" value="3" name="option" checked>
            <span style="font-style: italic;">All</span>
        </div>
    </div>

    <div class="col-xs-12 col-md-12">
        <?php
        $url = $module->getUrl('jsoncopy/copyfilesAJAX.php');
        ?>
        <a href="#" onclick="loadAjax('option='+$('input[name=option]:checked').val(),'<?=$url?>', 'succMsgContainer')" class="btn btn-info pull-left" style='margin-top:8px;margin-right:8px;cursor: pointer' id="BtnFiles">Update</a>
    </div>

    <div class="col-xs-12 col-md-12" style="padding-top:30px;">
        <span><strong>Rationale:</strong> In order to save processing time when running Harmonist Data Toolkit quality checks on submitted datasets, the contents of the data model (Harmonist 0A REDCap project), code lists (Harmonist 0B REDCap project), and data model metadata (Harmonist 0C REDCap project) are pre-processed and saved in <a href="<?=APP_PATH_WEBROOT_ALL."DataEntry/record_home.php?".$pidsArray['JSONCOPY']?>" target="_blank">data model files (JSON format) in REDCap</a>.</span>
    </div>
</div>

