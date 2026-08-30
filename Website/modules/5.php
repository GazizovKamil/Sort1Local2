<?php

include "../include/db.inc.php";
include "../include/db_safe.inc.php";
$db = new SafeMySQL(['mysqli' => $mysqli]);

if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/'</script>";
else {
    $isOn = $db->getOne("select laximo_on from laximo_companys where company_id = i?", $_SESSION['company_id']);
    if($isOn){
        $content='
        <input type="hidden" id="module_id" value="5">
        <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#laximo" onclick="document.getElementById(\'laximo_iframe\').src=\'/laximo/index.php\';">Laximo</a></li>
        <!--li><a data-toggle="tab" href="#levam">Levam</a></li -->
        <li><a data-toggle="tab" href="#vinpin">VINPIN</a></li>
        </ul>';
    }
        

    //if($modules_rights['modules']['m5']['rights']['client_payments']['show']==1)
    $content.='
    <div class="tab-content">
    <div id="laximo" class="tab-pane fade in active" style="padding-top:5px;">
        <iframe src="/laximo/index.php?task=catalogs" style="width:100%;height:91vh;" id="laximo_iframe"></iframe>
    </div>
    <!-- div id="levam" class="tab-pane" style="padding-top:5px;">
        <iframe src="/levam.php" style="width:100%;height:91vh;"></iframe>
    </div -->
    <div id="vinpin" class="tab-pane" style="padding-top:5px;">
        <iframe src="https://vinpin.sort1.pro/" style="width:100%;height:91vh;"></iframe>
    </div>
    </div>';

    $ret_arr=array(
    "content" => $content
    );
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest"){
        echo json_encode($ret_arr);
    }
    else {
        echo $content;
    }
}
?>
