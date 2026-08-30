<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/account/login'</script>";
else {
$content='

<input type="hidden" id="module_id" value="6">
<ul class="nav nav-tabs">';
if($modules_rights['modules']['m6']['rights']['sklad']['show']==1) $content.='<li class="active"><a data-toggle="tab" href="#stock" onclick="get_sklads();">Мои Склады</a></li>';
if($modules_rights['modules']['m6']['rights']['price_list']['show']==1) $content.='<li><a data-toggle="tab" href="#price_lists" onclick="get_price_lists();">Прайсы Поставщиков</a></li>';
if($modules_rights['modules']['m6']['rights']['price_list']['show']==1) $content.='<li><a data-toggle="tab" href="#price_exports" onclick="get_price_exports();">Конструктор выгрузки</a></li>';
if($modules_rights['modules']['m6']['rights']['sklad']['show']==1) $content.='<li><a data-toggle="tab" href="#sklad_prices" onclick="get_sklad_prices();">Печать ценников</a></li>';
 $content.=' <!-- li><a data-toggle="tab" href="#sort_api" onclick="get_company_profiles();">Настройки онлайн поиска</a></li -->
</ul>

<div class="tab-content">';
if($modules_rights['modules']['m6']['rights']['sklad']['show']==1) $content.='
  <div id="stock" class="tab-pane fade in active" style="padding-top:5px;">
    <button type="button" class="btn btn-primary" onclick="add_new_sklad();">
	Добавить склад
    </button>
    <div id="edit_sklad_0"></div>
    <div id="sklads_list">
    </div>

 </div>';

if($modules_rights['modules']['m6']['rights']['price_list']['show']==1) $content.='
 <div id="price_lists" class="tab-pane fade" style="padding-top:5px;">
    <button type="button" class="btn btn-primary" onclick="add_new_price_list();">
	Добавить прайс-лист
    </button>
    <div id="edit_price_list_0"></div>
    <div id="price_list_list"></div>
 </div>';

 if($modules_rights['modules']['m6']['rights']['price_list']['show']==1) $content.='
 <div id="price_exports" class="tab-pane fade" style="padding-top:5px;">
    <button type="button" class="btn btn-primary" onclick="edit_price_export(0,1);">
	Создать конструктор
    </button>
    <div id="edit_price_export_0"></div>
    <div id="price_export_list"></div>
	<div id="export_brands_select"></div>
	<div id="export_from_select"></div>
 </div>';

 if($modules_rights['modules']['m6']['rights']['sklad']['show']==1) $content.='
 <div id="sklad_prices" class="tab-pane fade" style="padding-top:5px;">
    <button type="button" class="btn btn-primary" onclick="add_new_sklad_price();">
		Создать список ценников
    </button>
    <div id="edit_sklad_prices_0"></div>
    <div id="sklad_price_list"></div>
 </div>';

$content.=' <div id="sort_api" class="tab-pane fade">
    <h3>Профили поставщиков для поиска</h3>
      
    <div id="online_profiles_list"></div>
 </div>
</div>
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
	    <div class="modal-content">
    		<div class="modal-header">
    		    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        		<span aria-hidden="true">&times;</span>
    		    </button>
    		    <h5 class="modal-title" id="exampleModalLongTitle">Добавление склада</h5>

    		</div>
    		<div class="modal-body" id="sklad_content">
		</div>
		<div class="modal-footer">
    		    <button type="button" class="btn btn-primary" onclick="api_query(\'/api/index.php\',\'new_sklad_form\',\'save_sklad\');">Сохранить</button>
    		    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_modal">Закрыть</button>
		</div>
	    </div>
	</div>
    </div>
    <div id="sklad_details" style="display: none; position: absolute; border: 1px solid #337ab7; background-color: #2e6da4;">
    	<div style="background-color: #2e6da4; color: #fff; cursor: move; padding: 5px;">

		<button type="button" class="close pull-right" onclick="$(\'#sklad_details\').hide();"><span>&times;</span></button>
    		<div id="sklad_details_LongTitle"></div>
    	</div>
    	<div id="sklad_details_content" style="padding: 5px; background-color:#eee;">
	</div>
    </div>
<script>get_sklads(); $("#sklad_details").draggable();</script>
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
