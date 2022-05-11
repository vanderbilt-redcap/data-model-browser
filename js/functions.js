function startDDProjects(){
    $('#installationbtn').prop("disabled",true);
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
function loadStatus(url, status,statvalue,option) {
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
        }else if($('#'+status+'_info').hasClass('btn-default-reverse') && (statvalue == "" || statvalue == null || statvalue == "false")){
            statvalue = loadStatusButton(status,"true");
        } else{
            statvalue = loadStatusButton(status,"false");
        }

        $.ajax({
            type: "POST",
            url: url,
            data: "&status=" + status + "&value=" + statvalue
            ,
            error: function (xhr, status, error) {
                alert(xhr.responseText);
            },
            success: function (result) {
                console.log(result)
            }
        });
    }else if(option == "0"){
        if(statvalue == "" || statvalue == null){
            if($('#'+status+'_info').hasClass('btn-default-reverse')){
                statvalue = "false";
                $("."+status).hide();
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

/**
 * Function that loads the SOP table
 * @param data, data we send to the ajax
 * @param url, url of the ajax file
 * @param loadAJAX, where we load our content
 */
function loadAjax(data, url, loadAJAX){
    $('#errMsgContainer').hide();
    $('#succMsgContainer').hide();
    $('#warnMsgContainer').hide();
    if(data != '') {
        $.ajax({
            type: "POST",
            url: url,
            data:data
            ,
            error: function (xhr, status, error) {
                alert(xhr.responseText);
            },
            success: function (result) {
                jsonAjax = jQuery.parseJSON(result);

                if(jsonAjax.html != '' && jsonAjax.html != undefined) {
                    $("#" + loadAJAX).html(jsonAjax.html);
                }

                if(jsonAjax.number_updates != '' && jsonAjax.number_updates != undefined && jsonAjax.number_updates != "0"){
                    $('#succMsgContainer').show();
                    $('#succMsgContainer').html(' <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a> <strong>Success!</strong> '+jsonAjax.number_updates+' NEW Latest update/s were saved.');
                }

                if(jsonAjax.variablesInfo != '' && jsonAjax.variablesInfo != undefined){
                    var value = jsonAjax.variablesInfo;
                    $.each(jsonAjax.variablesInfo, function (i, object) {;
                        if(object.display == "none"){
                            $("#"+i+"_row").hide();
                        }else{
                            $("#"+i+"_row").show();
                        };
                    });
                }

                //If table sortable add function
                if(jsonAjax.sortable == "true"){
                    $("#"+loadAJAX+"_table").tablesorter();
                }

                //Error Messages (Successful, Warning and Error)
                if(jsonAjax.succmessage != '' && jsonAjax.succmessage != undefined ){
                    $('#succMsgContainer').show();
                    $('#succMsgContainer').html(jsonAjax.succmessage);
                }else if(jsonAjax.warnmessage != '' && jsonAjax.warnmessage != undefined ){
                    $('#warnMsgContainer').show();
                    $('#warnMsgContainer').html(jsonAjax.warnmessage);
                }else if(jsonAjax.errmessage != '' && jsonAjax.errmessage != undefined ){
                    $('#errMsgContainer').show();
                    $('#errMsgContainer').html(jsonAjax.errmessage);
                }

                $('.divModalLoading').hide();
            }
        });
    }
}