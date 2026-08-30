<?php
session_start();
//print_r($_SESSION);
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
    include "../include/db.inc.php";
    include "../include/db_safe.inc.php";
    $fields="";
	$db = new SafeMySQL(['mysqli' => $mysqli]);
    $content='

<form id="new_client_form">
  <div class="form-group row">
    <label for="company_type" class="col-sm-3 col-form-label">ОКОПФ</label>
    <div class="col-sm-9">
      <select class="form-control" id="company_type" placeholder="" name="okopf">';
	$comp_types=$db->getAll("select * from company_types");
	foreach( $comp_types as $compt_key => $compt_val){
	    $content.="<option value=\"".$compt_val['id']."\">".$compt_val['type']."</option>";
	}
$content.='      </select>
    </div>
  </div>
  <div class="form-group row">
    <label for="company_name" class="col-sm-3 col-form-label">Наименование организации</label>
    <div class="col-sm-9">
      <input type="text" class="form-control" id="company_name" placeholder="Наименование организации" name="company_name" onchange="fill_form(\'name\');">
    </div>
    <div id="companys_list">
    </div>
  </div>
  <div class="form-group row">
    <label for="company_inn" class="col-sm-3 col-form-label">ИНН</label>
    <div class="col-sm-9 pull-right">
      <input type="text" class="form-control" id="inn" placeholder="ИНН" name="inn" onchange="fill_form(\'inn\');">
    </div>
  </div>
  <div class="form-group row">
    <label for="inputPassword3" class="col-sm-3 col-form-label">КПП</label>
    <div class="col-sm-9 pull-right">
      <input type="text" class="form-control" id="kpp" placeholder="КПП" name="kpp">
    </div>
  </div>
<a href="#" onclick="$(\'#advanced_company\').toggle()">Дополнительно</a>
    <div id="advanced_company" style="display:none">
	<div class="form-group row">
	    <label for="inputAddress" class="col-sm-3 col-form-label">Юридический адрес</label>
	    <div class="col-sm-9 pull-right">
    	    <input type="text" class="form-control" id="address" placeholder="Юридический адрес" name="address">
	    </div>
	</div>
	<div class="form-group row">
	    <label for="inputogrn" class="col-sm-3 col-form-label">ОГРН</label>
	    <div class="col-sm-9 pull-right">
    	    <input type="text" class="form-control" id="ogrn" placeholder="ОГРН" name="ogrn">
	    </div>
	</div>
	<div class="form-group row">
	    <label for="inputrs" class="col-sm-3 col-form-label">Расчетный счет</label>
	    <div class="col-sm-9 pull-right">
    	    <input type="text" class="form-control" id="rs" placeholder="Расчетный счет" name="rs">
	    </div>
	</div>
	<div class="form-group row">
	    <label for="inputbank" class="col-sm-3 col-form-label">Банк</label>
	    <div class="col-sm-9 pull-right">
    	    <input type="text" class="form-control" id="bank" placeholder="Банк" name="bank">
	    </div>
	</div>
	<div class="form-group row">
	    <label for="inputks" class="col-sm-3 col-form-label">Кор. счет</label>
	    <div class="col-sm-9 pull-right">
    	    <input type="text" class="form-control" id="ks" placeholder="Кор. счет" name="ks">
	    </div>
	</div>
	<div class="form-group row">
	    <label for="inputbik" class="col-sm-3 col-form-label">БИК</label>
	    <div class="col-sm-9 pull-right">
    	    <input type="text" class="form-control" id="bik" placeholder="БИК" name="bik">
	    </div>
	</div>
	<div class="form-group row">
	    <label for="inputruk" class="col-sm-3 col-form-label">Руководитель</label>
	    <div class="col-sm-9 pull-right">
    	    <input type="text" class="form-control" id="ruk" placeholder="Руководитель" name="ruk">
	    </div>
	</div>
	<div class="form-group row">
	    <label for="inputrukdol" class="col-sm-3 col-form-label">Должность руководителя</label>
	    <div class="col-sm-9 pull-right">
    	    <input type="text" class="form-control" id="rukdol" placeholder="Должность руководителя" name="rukdol">
	    </div>
	</div>
    </div>';
if (isset($_GET['main_company'])) $content.='<input type=hidden name="main_company" value="1">';
$content.='</form>
';
    if(isset($company_id))
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