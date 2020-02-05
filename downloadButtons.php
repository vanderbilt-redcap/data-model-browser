<div class="col-md-12 container-fluid wiki_container">
    <div class="col-md-12 wiki wiki_text wiki_text_size" style="padding-top: 0;">
        <div style="display: inline-block;float: left;">
            <div id="load_message" class="alert alert-info fade in" style="display:none">
                <span class="fa fa-spin fa-spinner"></span> <span>Please wait while the file is generated. It may take a few minutes.</span>
            </div>
        </div>
        <div style="display: inline-block;float: right;">
            <form method="POST" action="options/downloadPDF_AJAX.php?option=2" id='downloadPDF2' style="padding-right: 10px">
                <a onclick="$('#downloadPDF2').submit();" class="btn btn-default btn-md"><i class="fa fa-arrow-down"></i> Codes CSV</a>
            </form>
        </div>
        <div style="display: inline-block;float: right;">
            <form method="POST" action="options/downloadPDF_AJAX.php?option=0" id='downloadPDF0' style="padding-right: 10px">
                <a href="<?=getFileLink($settings['des_pdf'], $secret_key,$secret_iv);?>" class="btn btn-default btn-md"><i class="fa fa-arrow-down"></i> DES</a>
            </form>
        </div>
    </div>
</div>
<script>
    jQuery(document).ready(function($){
        $('#downloadPDF0,#downloadPDF1,#downloadPDF2').submit(function () {
            var value_deprecated = $('#deprecated_info').hasClass('wiki_deprecated_btn');
            var value_draft = $('#draft_info').hasClass('wiki_draft_btn');

            $('#'+$(this).attr('id')).append("<input type=hidden name=deprecated value="+value_deprecated+">");
            $('#'+$(this).attr('id')).append("<input type=hidden name=draft value="+value_draft+">");
            $('#load_message').show();
            //After 25 seconds hide message
            setTimeout(function(){ $('#load_message').hide(); }, 25000);
        });
    });
</script>