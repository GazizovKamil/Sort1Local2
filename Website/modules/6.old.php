<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/account/login'</script>";
else {
$content='

<input type="hidden" id="module_id" value="6">
<ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#stock" onclick="get_sklads();">Мои Склады</a></li>
  <li><a data-toggle="tab" href="#api" onclick="get_price_lists();">Прайсы Поставщиков</a></li>
  <li><a data-toggle="tab" href="#sort_api" onclick="get_plugins();">Настройки онлайн поиска</a></li>
</ul>

<div class="tab-content">
  <div id="stock" class="tab-pane fade in active">
    <h3>Мои склады</h3>
    <button type="button" class="btn btn-primary" onclick="add_new_sklad();">
	Добавить склад
    </button>
    <div id="edit_sklad_0"></div>
    <div id="sklads_list">
    </div>

 </div>
 <div id="api" class="tab-pane fade">
    <h3>Прайсы Поставщиков</h3>
    <button type="button" class="btn btn-primary" onclick="add_new_price_list();">
	Добавить прайс-лист
    </button>
    <div id="edit_price_list_0"></div>
    <div id="price_list_list"></div>
 </div>
 <div id="sort_api" class="tab-pane fade">
    <h3>Поставщики</h3>
    <form id="plugins_form" onsubmit="get_plugins(); return false;">
    <span class="pull-left">
    <select name="type" class="form-control select-sm" onchange="get_plugins();">
      <option value="2">Легковые</option>
      <option value="1">Грузовые</option>
      <option value="0">Все</option>
    </select>
    </span>
    <div class="input-group input-group-sm pull-right">
        <input required type="text" class="form-control input-sm search_str" name="search" id="search_plugins_text" value="" onchange="get_plugins();">
        <label for="search_plugins_text" id="clear_search_plugins" onclick="clear_search_text_plugins(\'search_plugins_text\');"></label>
    	<div class="input-group-btn">
    	    <button class="btn btn-default btn-sm" type="button" onclick="get_plugins();">Поиск</button>
    	</div>
    </div>
    </form>
    <div id="sort1_list"></div>
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
