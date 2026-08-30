<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/'</script>";
else {
    $content='
    <input type="hidden" id="module_id" value="13">
    <ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#supps">Подбор поставщиков</a></li>
    <!--li><a data-toggle="tab" href="#levam">Levam</a></li -->
    </ul>';
    //if($modules_rights['modules']['m5']['rights']['client_payments']['show']==1)
    $content.='
    <div class="tab-content">
    <div id="supps" class="tab-pane fade in active" style="padding-top:5px;">
        <iframe src="https://supps.sort1.ru/" style="width:100%;height:91vh;" id="supps_iframe"></iframe>
    </div>
    <!-- div id="levam" class="tab-pane" style="padding-top:5px;">
        <iframe src="/levam.php" style="width:100%;height:91vh;"></iframe>
    </div -->
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
