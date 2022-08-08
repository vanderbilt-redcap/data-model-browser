<div class="col-md-12 container-fluid wiki_container">
    <div class="col-md-12 wiki wiki_text wiki_text_size" style="padding-top: 0;">
        <div style="display: inline-block;float: left;">
            <div id="load_message" class="alert alert-info fade in" style="display:none">
                <span class="fa fa-spin fa-spinner"></span> <span>Please wait while the file is generated. It may take a few minutes.</span>
            </div>
        </div>
        <div style="display: inline-block;float: right;">
            <form method="POST" action="<?=$module->getUrl('options/downloadPDF_AJAX.php?NOAUTH');?>" id='downloadPDF2' style="padding-right: 10px">
                <a onclick="$('#downloadPDF2').submit();" class="btn btn-default btn-md"><i class="fa fa-arrow-down"></i> Codes CSV</a>
            </form>
        </div>
        <div style="display: inline-block;float: right;padding-right: 10px">
            <a href="<?=\Vanderbilt\DataModelBrowserExternalModule\printFile($module,$settings['des_pdf'],'url');?>" class="btn btn-default btn-md"><i class="fa fa-arrow-down"></i> Data Model</a>
        </div>
        <?php
        #Only users that are admins or have Design rights
        $designRights = "0";
        if(defined('USERID')) {
            $q = $module->query("SELECT design FROM redcap_user_rights WHERE project_id=? AND username =?", [$project_id, USERID]);

            while ($row = $q->fetch_assoc()) {
                $designRights = $row['design'];
            }
        }
        if($isAdmin || $designRights == "1"){
        ?>
        <div style="display: inline-block;float: right;padding-right: 10px">
            <a href="<?=$module->getUrl($page."&pid=".$_GET['pid']."&option=json&NOAUTH");?>" class="btn btn-default btn-md"><i class="fa fa-file-code-o"></i> JSON</a>
        </div>
        <?php } ?>
    </div>
</div>
<script>
    jQuery(document).ready(function($){
        $('#downloadPDF0,#downloadPDF1,#downloadPDF2').submit(function () {
            $('#load_message').show();
            //After 5 seconds hide message
            setTimeout(function(){ $('#load_message').hide(); }, 5000);
        });
    });
</script>