<?php
session_start();
//print_r($_SESSION);
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
    include "../include/db.inc.php";
    include "../include/db_safe.inc.php";
    $fields="";
	$db = new SafeMySQL(['mysqli' => $mysqli]);
    if(!empty($_GET['sklad_id'])) $sklad_id=(int)$_GET['sklad_id'];
    if (isset($sklad_id)) {
	$sql="select * from sklad where id=?i and company_id in (select company_id from user_companys where user_id=?i and main_company_id=0)";
	$sklad_data=$db->getRow($sql,$sklad_id,(int)$_SESSION['user_id']);
    }
    $content='
<form id="new_sklad_form">
    <input type="hidden" name="sklad_id" value="'.$sklad_data['id'].'">
    <input type="hidden" name="company_id" value="'.$sklad_data['company_id'].'">
  <div class="form-group row">
    <label for="sklad_name" class="col-sm-3 col-form-label">Наименование склада</label>
    <div class="col-sm-9">
      <input type="text" class="form-control" id="name" placeholder="Наименование склада" name="name" value=\''.str_replace("'","\"",$sklad_data['name']).'\'>
    </div>
  </div>
  <div class="form-group row">
    <label for="sklad_address" class="col-sm-3 col-form-label">Адрес склада</label>
    <div class="col-sm-9 pull-right">
      <input type="text" class="form-control" id="address" placeholder="Адрес склада" name="address" value="'.$sklad_data['address'].'">
    </div>
  </div>
  <div class="form-group row">
    <label for="sklad_descr" class="col-sm-3 col-form-label">Описание</label>
    <div class="col-sm-9 pull-right">
      <input type="text" class="form-control" id="descr" placeholder="Описание" name="descr" value="'.$sklad_data['descr'].'">
    </div>
  </div>
</form>

';
    if(isset($sklad_id))
	$ret=array(
	    "status"=>"ok",
	    "content" => $content
	);
    else
	$ret=array(
	    "status"=>"err",
	    "content" => ""
	);
//    header("Content-type: application/json");
//    echo json_encode($ret);
    echo $content;
}

?>