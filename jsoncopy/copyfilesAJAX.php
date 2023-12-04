<?php
namespace Vanderbilt\DataModelBrowserExternalModule;
include_once(__DIR__ . "/../classes/JsonPDF.php");
require_once __DIR__ ."/../projects.php";

$option = $_REQUEST['option'];
$project_id = (int)$_REQUEST['pid'];

$result = array();
$jsonArray  = array();
if($option == "3"){

    #Harmonist 0A: Data Model
    $json0a =  JsonPDF::createProject0AJSON($module, $project_id);
    $json0a_version = JsonPDF::returnJSONCopyVersion('0a',$pidsArray['JSONCOPY']);
    #Harmonist 0B: Code Lists
    $json0b =  JsonPDF::createProject0BJSON($module, $project_id);
    $json0b_version = JsonPDF::returnJSONCopyVersion('0b',$pidsArray['JSONCOPY']);
    #Harmonist 0C: Data Model Metadata
    $json0c =  JsonPDF::createProject0CJSON($module, $project_id);
    $json0c_version = JsonPDF::returnJSONCopyVersion('0b',$pidsArray['JSONCOPY']);

    $message = "<strong>Success!</strong><br/>"
        ."<strong>Data Model</strong> has been updated to V".$json0a_version['lastversion']."<br/>"
        ."<strong>Code Lists</strong> has been updated to V".$json0b_version['lastversion']."<br/>"
        ."<strong>Data Model Metadata</strong> has been updated to V".$json0c_version['lastversion'];


}else{
    if($option == "1"){
        #Harmonist 0A: Data Model
        $json0a =  JsonPDF::createProject0AJSON($module, $project_id);
        $json0a_version = JsonPDF::returnJSONCopyVersion('0a',$pidsArray['JSONCOPY']);
        $message = "<strong>Success!</strong><br/>"
            ."<strong>Data Model</strong> has been updated to V".$json0a_version['lastversion'];

    }else if($option == "2"){
        #Harmonist 0B: Code Lists
        $json0b =  JsonPDF::createProject0BJSON($module, $project_id);
        $json0b_version = JsonPDF::returnJSONCopyVersion('0b',$pidsArray['JSONCOPY']);
        $message = "<strong>Success!</strong><br/>"
            ."<strong>Code Lists</strong> has been updated to V".$json0b_version['lastversion'];
    }else if($option == "4"){
        #Harmonist 0C: Data Model Metadata
        $json0c =  JsonPDF::createProject0CJSON($module, $project_id);
        $json0c_version = JsonPDF::returnJSONCopyVersion('0c',$pidsArray['JSONCOPY']);
        $message = "<strong>Success!</strong><br/>"
            ."<strong>Data Model Metadata</strong> has been updated to V".$json0c_version['lastversion'];
    }
}
$result = "<script>$('#succMsgContainer').show();"
    ."$('#succMsgContainer').html('".$message."');"
    ."</script>";
echo json_encode(array(
        'html' => $result
));
?>