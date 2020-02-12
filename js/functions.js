function startDDProjects(){
    $.ajax({
        url: startDDProjects_url,
        data: "&pid="+pid,
        type: 'POST',
        success: function(returnData) {
            var data = JSON.parse(returnData);
            if (data.status == 'success') {
                $('#create_spinner').removeClass('fa fa-spinner fa-spin');
                var url = window.location.href;
                if (url.substring(url.length-1) == "#")
                {
                    url = url.substring(0, url.length-1);
                }

                if(url.match(/(&message=)([A-Z]{1})/)){
                    url = url.replace( /(&message=)([A-Z]{1})/, "&message=S" );
                }else{
                    url = url + "&message=S";
                }
                window.location = url;
            }
        }
    });
}


/**
 * Function that changes the button appearance, loads the new value in session and shows/hides content
 * @param status, deprecated or draft
 * @param statvalue, true or false
 * @param option, if its loading option or button click
 */
function loadStatus(status,statvalue,option) {
    if(option == ''){
        if (statvalue != "" && statvalue != null && $('.'+status).length > 0){
            statvalue = "false";
            $('.'+status).filter(function() {
                if($(this).css("display") == "none"){
                    statvalue = "true";
                    $(this).show();
                    $("#"+status+"-icon").addClass("wiki_"+status);
                    $("#"+status+"_info").addClass("wiki_"+status+"_btn");
                    $("#"+status+"_info").removeClass("btn-default-reverse");
                } else{
                    $(this).hide();
                    $("#"+status+"-icon").removeClass("wiki_"+status);
                    $("#"+status+"_info").removeClass("wiki_"+status+"_btn");
                    $("#"+status+"_info").addClass("btn-default-reverse");
                }
            });
        }else if($('#'+status+'_info').hasClass('btn-default-reverse') && (statvalue == "" || statvalue == null)){
            statvalue = loadStatusButton(status,"true");
        } else{
            statvalue = loadStatusButton(status,"false");
        }

        $.ajax({
            type: "POST",
            url: changeStatus_url,
            data: "&status=" + status + "&value=" + statvalue
            ,
            error: function (xhr, status, error) {
                alert(xhr.responseText);
            },
            success: function (result) {
            }
        });
    }else if(option == "0"){
        if(statvalue == "" || statvalue == null){
            if($('#'+status+'_info').hasClass('btn-default-reverse')){
                statvalue = "false";
            }
        }else{
            if($('#'+status+'_info').hasClass('btn-default-reverse') && (statvalue == "" || statvalue == null)){
                statvalue = "false";
            }

            if($('#'+status+'_info').hasClass('btn-default-reverse') && ((option == "0" && statvalue == "true") || (option == "" && statvalue == "false"))){
                statvalue = loadStatusButton(status,"true");
            } else{
                statvalue = loadStatusButton(status,"false");

            }
        }
    }
}

function loadStatusButton(status, option){
    if(option == "true"){
        $("."+status).show();
        $("#"+status+"-icon").addClass("wiki_"+status);
        $("#"+status+"_info").addClass("wiki_"+status+"_btn");
        $("#"+status+"_info").removeClass("btn-default-reverse");
    }else{
        $("."+status).hide();
        $("#"+status+"-icon").removeClass("wiki_"+status);
        $("#"+status+"_info").removeClass("wiki_"+status+"_btn");
        $("#"+status+"_info").addClass("btn-default-reverse");
    }
    return option;
}

function downloadExcel(){
    $('#load_message').show();
    //After 5 seconds hide message
    setTimeout(function(){ $('#load_message').hide(); }, 5000);

    $.ajax({
        url: downloadPDF_AJAX_url,
        data: "&pid="+pid,
        type: 'POST',
        success: function(returnData) {
            $('#load_message').hide();
        }
    });
}