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
  <!-- li class="active"><a data-toggle="tab" href="#dogovor" onclick="get_dogovors();">Договоры</a></li -->';
  if($modules_rights['modules']['m10']['rights']['document_plus']['show']==1) $content.='  <li class="active"><a data-toggle="tab" href="#prihod" onclick="get_documents(\'+\');" id="prihod_link">Поступления</a></li>';
  if($modules_rights['modules']['m10']['rights']['document_minus']['show']==1) $content.='  <li><a data-toggle="tab" href="#rashod" onclick="get_documents(\'-\');" id="rashod_link">Реализация</a></li>';
  if($modules_rights['modules']['m10']['rights']['document_plus']['show']==1) $content.='  <li><a data-toggle="tab" href="#return_to_dealer" onclick="get_documents(\'rtd\');" id="rtd_link">Возвраты поставщикам</a></li>';
  if($modules_rights['modules']['m10']['rights']['document_minus']['show']==1) $content.='  <li><a data-toggle="tab" href="#return_from_client" onclick="get_documents(\'rfc\');" id="rfc_link">Возвраты клиентов</a></li>';
  if($modules_rights['modules']['m10']['rights']['document_invent']['show']==1) $content.='  <li><a data-toggle="tab" href="#invent" onclick="get_invents(\'-\');" id="invent_link">Инвентаризация</a></li>';
  if($modules_rights['modules']['m10']['rights']['document_export']['show']==1) $content.='  <li><a data-toggle="tab" href="#export_1c" onclick="get_documents_for_export(\'-\');" id="export_1c_link">Экспорт в 1С</a></li>';

$content.='  </ul>

<div class="tab-content">';
if($modules_rights['modules']['m10']['rights']['document_plus']['show']==1) $content.=' <div id="prihod" class="tab-pane fade in active" style="padding-top:5px;">
	<div id="new_documentplus"></div>
	<form id="document_client_search_plus">
	<input type="hidden" name="znak" value="+">
	<div class="pull-right">
		  		<input type="radio" name="date_type" value="create_date" checked>Дата создания
				<input type="radio" name="date_type" value="document_date">Дата документа
			</div>
	<div id="document_client_header" class="row col-sm-12">
		<div class="col-sm-2">
		<button type="button" class="btn btn-primary btn-sm" onclick="add_new_document(\'+\');">Добавить документ</button>
		</div>
		<div class="col-sm-1">
			<table>
			<tr><td nowrap>
			<a onclick="get_documents_xls(\'+\');"><img src="/new_images/xls1.svg" style="width: 30px;"></a>&nbsp
			<a onclick="get_documents_csv(\'+\');"><img src="/new_images/csv1.svg" style="width: 30px;"></a>
			</td></tr></table>
		</div>
      	<div class="col-sm-9">
		  	
        	<div class="input-group input-group-sm pull-right">
				<span id="search_document_show_deleted_plus_label" class="input-group-addon"><input type="checkbox" name="search_document_show_deleted" id="search_document_show_deleted_plus" onchange="$(\'.show_deleted_document_details_class\').val(1);get_documents(\'+\');"> Удаленные: </span>
				<span id="search_document_client_name_label" class="input-group-addon">Клиент: </span>
				<input type="text" name="search_document_client_name" id="search_document_client_name_plus" class="form-control" onkeyup="if(event.keyCode===13 || event.keyCode===27) {get_documents(\'+\');}">
				<span id="search_document_article_label" class="input-group-addon">Артикул: </span>
				<input type="text" name="search_document_article" id="search_document_article_plus" class="form-control" onkeyup="if(event.keyCode===13 || event.keyCode===27) {get_documents(\'+\');}">
				<span id="search_document_date_from_label" class="input-group-addon">с: </span>
				<input type="date" name="search_document_date_from" id="search_document_date_from_plus" class="form-control">
				<span id="search_document_date_to_label" class="input-group-addon">по: </span>
				<input type="date" name="search_document_date_to" id="search_document_date_to_plus" class="form-control">
				<div class="input-group-btn">
				<button type="button" class="btn btn-primary btn-sm" onclick="get_documents(\'+\');">Поиск</button>
				</div>
        	</div>
      	</div>
    </div>
    </form>
    <div id="prihod_list">
    </div>
 </div>';
 if($modules_rights['modules']['m10']['rights']['document_plus']['show']==1) $content.=' 
 <div id="return_to_dealer" class="tab-pane fade" style="padding-top:5px;">
 	<div id="new_documentrtd"></div>
	<form id="document_client_search_rtd">
	<input type="hidden" name="znak" value="rtd">
	<div class="pull-right">
		  		<input type="radio" name="date_type" value="create_date" checked>Дата создания
				<input type="radio" name="date_type" value="document_date">Дата документа
			</div>
	<div id="document_client_header" class="row col-sm-12">
		<div class="col-sm-2">
		</div>
		<div class="col-sm-1">
			<table>
			<tr><td nowrap>
			<a onclick="get_documents_xls(\'rtd\');"><img src="/new_images/xls1.svg" style="width: 30px;"></a>&nbsp
			<a onclick="get_documents_csv(\'rtd\');"><img src="/new_images/csv1.svg" style="width: 30px;"></a>
			</td></tr></table>
		</div>
      	<div class="col-sm-9">
		  	
        	<div class="input-group input-group-sm pull-right">
			<span id="search_document_show_deleted_rtd_label" class="input-group-addon"><input type="checkbox" name="search_document_show_deleted" id="search_document_show_deleted_rtd" onchange="$(\'.show_deleted_document_details_class\').val(1);get_documents(\'rtd\');"> Удаленные: </span>
				<span id="search_document_client_name_label" class="input-group-addon">Клиент: </span>
				<input type="text" name="search_document_client_name" id="search_document_client_name_rtd" class="form-control" onkeyup="if(event.keyCode===13 || event.keyCode===27) {get_documents(\'rtd\');}">
				<span id="search_document_article_label" class="input-group-addon">Артикул: </span>
				<input type="text" name="search_document_article" id="search_document_article_rtd" class="form-control" onkeyup="if(event.keyCode===13 || event.keyCode===27) {get_documents(\'rtd\');}">
				<span id="search_document_date_from_label" class="input-group-addon">с: </span>
				<input type="date" name="search_document_date_from" id="search_document_date_from_rtd" class="form-control">
				<span id="search_document_date_to_label" class="input-group-addon">по: </span>
				<input type="date" name="search_document_date_to" id="search_document_date_to_rtd" class="form-control">
				<div class="input-group-btn">
				<button type="button" class="btn btn-primary btn-sm" onclick="get_documents(\'rtd\');">Поиск</button>
				</div>
        	</div>
      	</div>
    </div>
    </form>
    <div id="rtd_list">
    </div>
 </div>';
 if($modules_rights['modules']['m10']['rights']['document_minus']['show']==1) $content.=' 
 <div id="return_from_client" class="tab-pane fade" style="padding-top:5px;">
 	<div id="new_documentrfc"></div>
	<form id="document_client_search_rfc">
	<input type="hidden" name="znak" value="rfc">
	<div class="pull-right">
		  		<input type="radio" name="date_type" value="create_date" checked>Дата создания
				<input type="radio" name="date_type" value="document_date">Дата документа
			</div>
	<div id="document_client_header" class="row col-sm-12">
		<div class="col-sm-2">
		</div>
		<div class="col-sm-1">
			<table>
			<tr><td nowrap>
			<a onclick="get_documents_xls(\'rfc\');"><img src="/new_images/xls1.svg" style="width: 30px;"></a>&nbsp
			<a onclick="get_documents_csv(\'rfc\');"><img src="/new_images/csv1.svg" style="width: 30px;"></a>
			</td></tr></table>
		</div>
      	<div class="col-sm-9">
		  	
        	<div class="input-group input-group-sm pull-right">
			<span id="search_document_show_deleted_rfc_label" class="input-group-addon"><input type="checkbox" name="search_document_show_deleted" id="search_document_show_deleted_rfc" onchange="$(\'.show_deleted_document_details_class\').val(1);get_documents(\'rfc\');"> Удаленные: </span>
				<span id="search_document_client_name_label" class="input-group-addon">Клиент: </span>
				<input type="text" name="search_document_client_name" id="search_document_client_name_rfc" class="form-control" onkeyup="if(event.keyCode===13 || event.keyCode===27) {get_documents(\'rfc\');}">
				<span id="search_document_article_label" class="input-group-addon">Артикул: </span>
				<input type="text" name="search_document_article" id="search_document_article_rfc" class="form-control" onkeyup="if(event.keyCode===13 || event.keyCode===27) {get_documents(\'rfc\');}">
				<span id="search_document_date_from_label" class="input-group-addon">с: </span>
				<input type="date" name="search_document_date_from" id="search_document_date_from_rfc" class="form-control">
				<span id="search_document_date_to_label" class="input-group-addon">по: </span>
				<input type="date" name="search_document_date_to" id="search_document_date_to_rfc" class="form-control">
				<div class="input-group-btn">
				<button type="button" class="btn btn-primary btn-sm" onclick="get_documents(\'rfc\');">Поиск</button>
				</div>
        	</div>
      	</div>
    </div>
    </form>
    <div id="rfc_list">
    </div>
 </div>';
 if($modules_rights['modules']['m10']['rights']['document_minus']['show']==1) $content.=' <div id="rashod" class="tab-pane fade" style="padding-top:5px;">
	<div id="new_documentminus"></div>
	<form id="document_client_search_minus">
	<input type="hidden" name="znak" value="-">
	<div class="pull-right">
		  		<input type="radio" name="date_type" value="create_date" checked>Дата создания
				<input type="radio" name="date_type" value="document_date">Дата документа
			</div>
	<div id="document_client_header" class="row col-sm-12">
		<div class="col-sm-2">
		<!-- button type="button" class="btn btn-primary btn-sm" onclick="add_new_document(\'-\');">Добавить документ</button -->
		</div>
		<div class="col-sm-1">
			<table>
			<tr><td nowrap>
			<a onclick="get_documents_xls(\'-\');"><img src="/new_images/xls1.svg" style="width: 30px;"></a>&nbsp
			<a onclick="get_documents_csv(\'-\');"><img src="/new_images/csv1.svg" style="width: 30px;"></a>
			</td></tr></table>
		</div>
      <div class="col-sm-9">
	  
        <div class="input-group input-group-sm pull-right">
		  <span id="search_document_show_deleted_minus_label" class="input-group-addon"><input type="checkbox" name="search_document_show_deleted" id="search_document_show_deleted_minus" onchange="$(\'.show_deleted_document_details_class\').val(1);get_documents(\'-\');"> Удаленные: </span>
          <span id="search_document_client_name_label" class="input-group-addon">Клиент: </span>
          <input type="text" name="search_document_client_name" id="search_document_client_name_minus" class="form-control" onkeyup="if(event.keyCode===13 || event.keyCode===27) {get_documents(\'-\');}">
          <span id="search_document_article_label" class="input-group-addon">Артикул: </span>
          <input type="text" name="search_document_article" id="search_document_article_minus" class="form-control" onkeyup="if(event.keyCode===13 || event.keyCode===27) {get_documents(\'-\');}">
          <span id="search_document_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="search_document_date_from" id="search_document_date_from_minus" class="form-control">
          <span id="search_document_date_to_label" class="input-group-addon">по: </span>
		  <input type="date" name="search_document_date_to" id="search_document_date_to_minus" class="form-control">
		  <div class="input-group-btn">
		  <button type="button" class="btn btn-primary btn-sm" onclick="get_documents(\'-\');">Поиск</button>
		  </div>
        </div>
      </div>
    </div>
    </form>
    <div id="rashod_list"></div>
 </div>';

 if($modules_rights['modules']['m10']['rights']['document_invent']['show']==1) $content.='<div id="invent" class="tab-pane fade" style="padding-top:5px;">
	<div id="new_invent"></div>
	<form id="invent_search">
	<input type="hidden" name="znak" value="inv">
	<div id="invent_header" class="row col-sm-12">
		<div class="col-sm-2">
		<button type="button" class="btn btn-primary btn-sm" onclick="add_new_invent();">Сформировать инвентаризационную опись</button>
		</div>
		<div class="col-sm-10">
			<div class="input-group input-group-sm pull-right">
				<span id="search_invent_sklad_name_label" class="input-group-addon">Склад: </span>
				<input type="text" name="search_invent_sklad_name" id="search_invent_sklad_name" class="form-control">
				<span id="search_invent_date_from_label" class="input-group-addon">с: </span>
				<input type="date" name="search_invent_date_from" id="search_invent_date_from" class="form-control">
				<span id="search_invent_date_to_label" class="input-group-addon">по: </span>
				<input type="date" name="search_invent_date_to" id="search_invent_date_to" class="form-control">
				<div class="input-group-btn">
				<button type="button" class="btn btn-primary btn-sm" onclick="get_invents();">Поиск</button>
				</div>
			</div>
		</div>
	</div>
	</form>
	<div id="invent_list">
    </div>
 </div>';

 if($modules_rights['modules']['m10']['rights']['document_export']['show']==1) $content.='<div id="export_1c" class="tab-pane fade" style="padding-top:5px;">
	<div id="new_export"></div>
	<form id="export_search">
	<input type="hidden" name="znak" value="exp">
	<div class="row">
		<div class="col-sm-8">
			<button type="button" class="btn btn-primary btn-sm" onclick="new_export();">Выгрузить данные</button>
		</div>
		<div class="col-sm-4">
			<span class="pull-right">
			<input type="radio" name="date_type" value="create_date" checked>Дата создания
			<input type="radio" name="date_type" value="document_date">Дата документа
			</span>
		</div>
	</div>
	<div id="invent_header" class="row">
		<div class="col-sm-12">
			
			<div class="input-group input-group-sm pull-right">
				<span id="search_export_orgtype_label" class="input-group-addon">Тип клиента: 
				<select name="search_export_orgtype" id="search_export_orgtype">
					<option value="0">Все</option>
					<option value="1">Юр.</option>
					<option value="3">Физ.</option>
				</select>
				</span>
				<span id="search_export_ORP_label" class="input-group-addon">Отч. о розн. продаж: 
				<input type="checkbox" name="search_export_ORP" id="search_export_ORP">
				</span>
				<span id="search_export_PKO_label" class="input-group-addon">ПКО: 
				<input type="checkbox" name="search_export_PKO" id="search_export_PKO">
				</span>
				<span id="search_export_PKO_label" class="input-group-addon">РКО: 
				<input type="checkbox" name="search_export_RKO" id="search_export_RKO">
				</span>
				<span id="search_export_realizaciya_label" class="input-group-addon">Реализ.: 
				<input type="checkbox" name="search_export_realizaciya" id="search_export_realizaciya">
				</span>
				<span id="search_export_realizaciya_label" class="input-group-addon">Поступл.: 
				<input type="checkbox" name="search_export_prihod" id="search_export_prihod">
				</span>
				<span id="search_export_return_client_label" class="input-group-addon">Возв. клиент.: 
				<input type="checkbox" name="search_export_return_client" id="search_export_return_client">
				</span>
				<span id="search_export_return_client_label" class="input-group-addon">Возв. постав.: 
				<input type="checkbox" name="search_export_return_postav" id="search_export_return_postav">
				</span>
				<span id="search_export_date_from_label" class="input-group-addon">с: </span>
				<input type="date" name="search_document_date_from" id="search_export_date_from" class="form-control">
				<span id="search_invent_date_to_label" class="input-group-addon">по: </span>
				<input type="date" name="search_document_date_to" id="search_export_date_to" class="form-control">
				<div class="input-group-btn">
				<button type="button" class="btn btn-primary btn-sm" onclick="get_documents_for_export(\'-\');">Поиск</button>
				</div>
			</div>
		</div>
	</div>
	</form>
	<div id="export_real_list">
	</div>
	<div id="export_prihod_list">
    </div>
 </div>';

$content.='</div>
    <div class="modal fade" id="document_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
	    <div class="modal-content">
    		<div class="modal-header">
    		    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        		<span aria-hidden="true">&times;</span>
    		    </button>
    		    <h5 class="modal-title" id="exampleModalLongTitle">Добавление документа</h5>

    		</div>
    		<div class="modal-body" id="document_content">
		</div>
		<div class="modal-footer">
    		    <button type="button" class="btn btn-primary" onclick="api_query(\'/api/index.php\',\'new_document_form\',\'save_document\');">Сохранить</button>
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
<script> get_documents("+");</script>
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
