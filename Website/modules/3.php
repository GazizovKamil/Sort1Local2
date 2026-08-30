<?php
//use Sort1API\Components\DB;

if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	   echo "{\"error\":\"auth need\"}";
    else
	   echo "<script> location.href='/'</script>";
else {
$content='

<input type="hidden" id="module_id" value="3">
<ul class="nav nav-tabs">';
//echo print_r($_SESSION,true);
if((int)$_SESSION['roles']<10){
  if($modules_rights['modules']['m3']['rights']['client_zakazs']['show']==1) $content.='<li class="active"><a data-toggle="tab" href="#zakaz_client" onclick="get_zakazes(\'client\');" id="zakazes_client">Заказы клиентов</a></li>';
  if($modules_rights['modules']['m3']['rights']['client_zakazs']['show']==1) $content.='<li id="zakaz_to_sklad_li"><a data-toggle="tab" href="#zakaz_to_sklad" onclick="get_zakazes(\'to_sklad\');" id="zakazes_to_sklad">Заказы на склад</a></li>';
  $content.='<li><a data-toggle="tab" href="#market_zakaz" onclick="get_market_zakazes();">Заказы из маркетплейсов</a></li>';
  //$content.='<!-- li><a data-toggle="tab" href="#zakaz_price" onclick="get_zakazes_price();">Заказы из прайс-листов</a></li -->
  //<!-- li><a data-toggle="tab" href="#zakaz_online" onclick="get_zakazes_online();">Заказы поставщикам online</a></li -->
  if($modules_rights['modules']['m3']['rights']['orders']['show']==1) $content.='<li><a data-toggle="tab" href="#orders" onclick="get_deliverers_list();">Отслеживание заказов</a></li>';
  if($modules_rights['modules']['m3']['rights']['baskets']['show']==1) $content.='<li><a data-toggle="tab" href="#dealer_baskets" onclick="get_dealer_baskets();">Корзины поставщиков</a></li>';
}
else {
  $content.='<li class="active"><a data-toggle="tab" href="#zakaz_client" onclick="get_zakazes();">Ваши заказы</a></li>';
}

$content.='</ul>

<div class="tab-content">';

 
/*if((int)$_SESSION['roles']<10){
    $content.='<h3>Заказы клиентов</h3>';
}
else {
  $content.='<h3>Ваши заказы</h3>';
} */
//echo print_r($modules_rights,true);

if($modules_rights['modules']['m3']['rights']['client_zakazs']['show']==1){
  $content.=' <div id="zakaz_client" class="tab-pane fade in active" style="padding-top:5px;">';
  $content.='<div id="select_zakaz_details_dealer_price" style="z-index:5"></div>
  <div id="select_zakaz_details_dealer_online"></div>
  <div class="pull-right"><a onclick="toggle_client_zakazes(\'client\')"><img src="/new_images/off.png" id="zakazes_on_off_client" style="width: 30px;"></a> показать детали</div><br><br>
  <form id="zakaz_client_search">
    <div id="zakaz_client_header" class="row col-sm-12">
      <div class="col-sm-12">
        <div class="input-group input-group-sm pull-right">';
        //modules_rights.modules.m0.rights.show_zakaz_sale_price.show
        if($modules_rights['modules']['m0']['rights']['show_zakaz_sale_price']['show']==1 || empty($modules_rights['modules']['m0'])){
        $content.='<span class="input-group-addon">
        <input type="checkbox" aria-label="..." name="show_zakaz_dealer_price" id="show_zakaz_dealer_price"> закуп. цена
        </span>';
        }
          $content.='<span class="input-group-addon">
            <input type="checkbox" aria-label="..." name="show_archive" id="show_archive"> Показать архив
          </span>
          <span id="search_zakaz_client_name_label" class="input-group-addon">Клиент: </span>
          <input type="text" name="search_zakaz_client_name" id="search_zakaz_client_name" class="form-control" onchange="get_zakazes(\'client\');" title="VIN автомобиля или наименование клиента" placeholder="VIN или наименование">
          <span id="search_zakaz_article_client_label" class="input-group-addon">Артикул: </span>
          <input type="text" name="search_zakaz_article" id="search_zakaz_article_client" class="form-control" onchange="get_zakazes(\'client\');">
          <span id="search_zakaz_date_from_client_label" class="input-group-addon">с: </span>
          <input type="date" name="search_zakaz_date_from" id="search_zakaz_date_from_client" class="form-control">
          <span id="search_zakaz_date_to_client_label" class="input-group-addon">по: </span>
          <input type="date" name="search_zakaz_date_to" id="search_zakaz_date_to_client" class="form-control">
          <div class="input-group-btn">
          <button type="button" class="btn btn-primary btn-sm" onclick="search_in_zakazes(\'client\');">Поиск</button>
          </div>
        </div>
      </div>
      <div class="col-sm-1">
        
      </div>
    </div>
    </form> 
    <div id="zakaz_diagnostic_card"></div>
    <div id="zakaz_diagnostic_card_print" style="display:none"></div>
    <div id="zakaz_client_list">
    </div>
    <div id="zakaz_details_client_list">
    </div>
 </div>';
 $content.=' <div id="zakaz_to_sklad" class="tab-pane fade" style="padding-top:5px;">';
  $content.='
  <div class="pull-right"><a onclick="toggle_client_zakazes(\'to_sklad\')"><img src="/new_images/off.png" id="zakazes_on_off_to_sklad" style="width: 30px;"></a> показать детали</div><br><br>
  <form id="zakaz_to_sklad_search" onsubmit="return false">
    <div id="zakaz_to_sklad_header" class="row col-sm-12">
      <div class="col-sm-12">
        <div class="input-group input-group-sm pull-right">';
        //modules_rights.modules.m0.rights.show_zakaz_sale_price.show
        if($modules_rights['modules']['m0']['rights']['show_zakaz_sale_price']['show']==1 || empty($modules_rights['modules']['m0'])){
        $content.='<span class="input-group-addon">
        <input type="checkbox" aria-label="..." name="show_zakaz_dealer_price" id="show_zakaz_dealer_price_to_client"> закуп. цена
        </span>';
        }
          $content.='<span class="input-group-addon">
            <input type="checkbox" aria-label="..." name="show_archive" id="show_archive_to_sklad"> Показать архив
          </span>
          <span id="search_zakaz_article_to_sklad_label" class="input-group-addon">Артикул: </span>
          <input type="text" name="search_zakaz_article" id="search_zakaz_article_to_sklad" class="form-control" onchange="get_zakazes(\'to_sklad\');">
          <span id="search_zakaz_date_from_to_sklad_label" class="input-group-addon">с: </span>
          <input type="date" name="search_zakaz_date_from" id="search_zakaz_date_from_to_sklad" class="form-control">
          <span id="search_zakaz_date_to_to_sklad_label" class="input-group-addon">по: </span>
          <input type="date" name="search_zakaz_date_to" id="search_zakaz_date_to_to_sklad" class="form-control">
          <div class="input-group-btn">
          <button type="button" class="btn btn-primary btn-sm" onclick="search_in_zakazes(\'to_sklad\');">Поиск</button>
          </div>
        </div>
      </div>
      <div class="col-sm-1">
        
      </div>
    </div>
    </form> 
    <div id="zakaz_to_sklad_list">
    </div>
    <div id="zakaz_details_to_sklad_list">
    </div>
 </div>';
}

 $content.='<div id="zakaz_price" class="tab-pane fade">
    <h3>Заказы из прайс-листов</h3>
    <div id="zakaz_price_list"></div>
 </div>
 <div id="zakaz_online" class="tab-pane fade">
    <h3>Заказы online</h3>
    <div class="input-group input-group-sm pull-right">
	<span id="price_list_search_16">
	    <form id="zakazes_online_form" onsubmit="get_zakazes_online(); return false;"><input type="text" class="form-control input-sm" name="zakazes_online_search" value="" onchange="get_zakazes_online();"></form>
	</span>
	<span class="input-group-btn">
	    <button class="btn btn-default btn-sm" type="button" onclick="get_zakazes_online();">Поиск</button>
	</span>
    </div>
    <div id="zakaz_online_list"></div>
 </div>';

// if($modules_rights['modules']['m3']['rights']['client_zakazs']['show']==1){ // для мпакретплейса $modules_rights['modules']['m3']['rights']['client_market_zakazs']['show']
  $content.=' <div id="market_zakaz" class="tab-pane fade" style="padding-top:5px;">';
  $content.='
  <form id="market_zakaz_client_search">
    <div id="market_zakaz_client_header" class="row col-sm-12">
      <div class="col-sm-12">
        <button type="button" class="btn btn-primary btn-sm" id="btnAddMarketplaceConfig" onclick="get_market_orders();">
          Выгрузить заказы
        </button>
        <div class="input-group input-group-sm pull-right">';
        //modules_rights.modules.m0.rights.show_zakaz_sale_price.show
          $content.='
          <span id="marketplace_name_label" class="input-group-addon">Маркетплэйс: </span>
          <select class="form-control" style="width:200px;" name="search_marketplaces_configs_id" id="search_marketplaces_configs_id" onchange="get_market_zakazes();">';
          $marketplaces=$db->getAll("select mc.id,mc.marketplace_id,mc.config_name,m.name from marketplaces_configs mc left join marketing_channels m on (m.id=mc.marketing_channel_id) where mc.company_id =?i and mc.active=1",$_SESSION['main_company']);
          //$marketplaces = $db->getAll('select id,name from marketplaces where id in (?a)',array_column($client_marketplaces,"marketplace_id"));
          for ($i=0; $i < count((array)$marketplaces); $i++) { 
            $content.='<option value="'.$marketplaces[$i]['id'].'">'.$marketplaces[$i]['config_name'].'</option>';
          }
          $content.='</select>
          <span id="search_zakaz_client_name_label" class="input-group-addon">Клиент: </span>
          <input type="text" name="search_market_zakaz_client_name" id="search_market_zakaz_client_name" class="form-control" onchange="get_market_zakazes();">
          <span id="search_market_zakaz_article_label" class="input-group-addon">Артикул: </span>
          <input type="text" name="search_market_zakaz_article" id="search_market_zakaz_article" class="form-control" onchange="get_market_zakazes();">
          <span id="search_zakaz_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="search_market_zakaz_date_from" id="search_market_zakaz_date_from" class="form-control">
          <span id="search_zakaz_date_to_label" class="input-group-addon">по: </span>
          <input type="date" name="search_market_zakaz_date_to" id="search_market_zakaz_date_to" class="form-control">
          <div class="input-group-btn">
          <button type="button" class="btn btn-primary btn-sm" onclick="get_market_zakazes();">Поиск</button>
          </div>
        </div>
      </div>
      <div class="col-sm-1">
        
      </div>
    </div>
    </form> 
    <div id="market_zakaz_client_list"></div>
    <div id="chat_window"></div>
    <div id="market_zakaz_details_client_list">
    </div>
 </div>';
// }

if($modules_rights['modules']['m3']['rights']['orders']['show']==1){
 $content.='<div id="orders" class="tab-pane fade" style="padding-top:5px;">
    <form id="orders_search"  onsubmit="event.preventDefault();">
    <div id="orders_header" class="row col-sm-12">
      <div class="col-sm-11">
        
        <div class="input-group input-group-sm pull-right">
          <span id="search_order_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="search_order_date_from" id="search_order_date_from" class="form-control" value="'.date("Y-m-d",strtotime("3 days ago")).'">
          <span id="search_zakaz_date_to_label" class="input-group-addon">по: </span>
          <input type="date" name="search_order_date_to" id="search_order_date_to" class="form-control" value="'.date("Y-m-d").'">
          <div class="input-group-btn">
          <button type="button" class="btn btn-primary btn-sm" onclick="get_orders();">Поиск</button>
          </div>
        </div>
        <div class="input-group input-group-sm pull-right">
          
          <input required type="text" name="ordfilter_text" id="ordfilter_text" class="form-control search_str" placeholder="Быстрый отбор" onchange="get_ordfilter_text();">
          <label for="ordfilter_text" id="ordfilter_text_label" onclick="clear_search_order_text(\'ordfilter_text\');get_ordfilter_text();"></label>
        </div>
      </div>
      <div class="col-sm-1">
        
      </div>
    </div>
    </form> 
    <table style="width:100%"><tr><td valign="top" style="width:150px;" id="orders_list_parent">
    <div id="deliverers_list" style="font-size: 12px; width: 100%">
    </div>
    </td>
    <td style="border-left: 2px solid gray" valign="top">
    <div id="orders_list" style="font-size: 12px;"></div>
    </td></tr>
    </table>
 </div>';
}
if($modules_rights['modules']['m3']['rights']['baskets']['show']==1){
$content.='<div id="dealer_baskets" class="tab-pane fade" style="padding-top:5px;">
    <form id="dealer_baskets_search"  onsubmit="event.preventDefault();">
    <div id="dealer_baskets_header" class="row col-sm-12">
      <div class="col-sm-11">
        
        <div class="input-group input-group-sm pull-right">
          <span id="search_dealer_baskets_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="search_dealer_baskets_date_from" id="search_dealer_baskets_date_from" class="form-control" value="'.date("Y-m-d",strtotime("1 days ago")).'">
          <span id="search_dealer_baskets_date_to_label" class="input-group-addon">по: </span>
          <input type="date" name="search_dealer_baskets_date_to" id="search_dealer_baskets_date_to" class="form-control" value="'.date("Y-m-d").'">
          <div class="input-group-btn">
          <button type="button" class="btn btn-primary btn-sm" onclick="get_dealer_baskets();">Поиск</button>
          </div>
        </div>
        <div class="input-group input-group-sm pull-right">
          
          <input required type="text" name="dealbaskfilter_text" id="dealbaskfilter_text" class="form-control search_str" placeholder="Быстрый отбор" onkeyup="get_dealbaskfilter_text();">
          <label for="dealbaskfilter_text" id="dealbaskfilter_text_label" onclick="clear_search_order_text(\'dealbaskfilter_text\');"></label>
        </div>
      </div>
      <div class="col-sm-1">
        
      </div>
    </div>
    </form>
    <table style="width:100%">
    <tr>
      <td valign="top" style="max-width:5%">
        <div id="dealer_baskets_list" style="font-size: 12px; height: 87vh; overflow:auto;">
        </div>
      </td>
      <td style="border-left: 2px solid gray; width: 98%; height: 87vh" valign="top">
        <iframe id="dealer_basket" style="width:100%; height:100%;" onload="$.unblockUI();"></iframe>
      </td>
    </tr>
    </table>
  </div>';
}
$content.='</div>
<script>get_zakazfilter().then(function(dataz){get_zakazes("client");});</script>
';
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
