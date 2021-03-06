<div class="col-md-12 container-fluid wiki_container">
    <div class="col-md-12 wiki wiki_text wiki_text_size" style="padding-top: 0;">
        <div style="display: inline-block;float: left;">
            <div id="load_message" class="alert alert-info fade in" style="display:none">
                <span class="fa fa-spin fa-spinner"></span> <span>Please wait while the file is generated. It may take a few minutes.</span>
            </div>
        </div>
        <div style="display: inline-block;float: right;">
            <form method="POST" action="<?=$module->getUrl('options/downloadPDF_AJAX.php')."&pid=".$_GET['pid'];?>" id='downloadPDF2' style="padding-right: 10px">
                <a onclick="$('#downloadPDF2').submit();" class="btn btn-default btn-md"><i class="fa fa-arrow-down"></i> Codes CSV</a>
            </form>
        </div>
        <div style="display: inline-block;float: right;padding-right: 10px">
            <a href="<?=printFile($module,$settings['des_pdf'],'url');?>" class="btn btn-default btn-md"><i class="fa fa-arrow-down"></i> DES</a>
        </div>
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