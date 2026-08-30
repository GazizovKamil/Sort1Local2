<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/'</script>";
else {
$content='

<input type="hidden" id="module_id" value="1">
<input type="hidden" id="max_search_tab_id" value="-1">
<ul class="nav nav-tabs" id="search_nav_tabs">
  <li class="active" id="search_nav_li_0"><a data-toggle="tab" href="#zapchasti_0"><span id="search_tab_name_0">Новый поиск</span>&nbsp<span id="search_tab_status_0"></span></a></li>
  <li class="" id="new_search_nav_button"><a onclick="create_new_search_tab();">+</a></li>
</ul>
<div class="tab-content" style="padding: 5px;" id="zapchasti">
  <div id="zapchasti_0" class="tab-pane fade in active">
        <div class="pull-right"><a onclick="tab_toggle_group_search(0)"><img src="/new_images/off.png" id="tab_group_on_off_0" style="width: 30px;"></a> Групповой поиск</div>
        <br><div class="row" id="group_search_header_0" style="display:none;">
          <div class="col-sm-3">
            Загрузите список для групповой проценки 
          </div>
          <div class="col-sm-2">
            <input id="excel_reader_load_0" onchange="excel_reader_obj.handleFileSelect(event,0)" class="btn btn-sm btn-primary excel_reader_load" data-filename-placement="inside" type="file" title="Загрузить файл (в формате *.xls/*.xlsx)">
          </div>
          <div class="col-sm-2" id="stop_group_search_0" style="display:none;"><button class="btn btn-danger" onclick="stop_group_search(0)">Остановить</button></div>
          <div class="col-sm-2" id="continue_group_search_0" style="display:none;"><button class="btn btn-success" onclick="continue_group_search(0)">Продолжить</button></div>
        </div>
        <div id="excel_reader_result_list_0" style="font-size:12px;"></div>
        <div id="search_header_0"> 
        <div id="plugins_started_0" style="max-width: 700px; height: 40px; overflow: auto; " class="pull-right"></div>
          <form id="search_form_0" onsubmit="select_brands(0); $(\'#search_str_0\').blur(); return false;">
          <br>
         <div class="row"> 
              <div class="col-lg-6">
                	<div class="input-group">
                		      <input required title="Введите код запчасти" type="text" name="article" id="search_str_0" class="form-control search_str" placeholder="Введите код запчасти" onchange="" autocomplete="off" ';
                          if(isset($_GET['article'])) $content.='value="'.$_GET['article'].'"';
                          $content.='><label for="search_str_0" id="search_str_label_0" onclick="clear_search_str(0);"></label>
                    		  <input type="hidden" name="brand" id="brand"';
                          if(isset($_GET['brand'])) $content.='value="'.$_GET['brand'].'"';
                          $content.='>
                		        <input type="hidden" name="brand_id" id="brand_id">
                      		<input type="hidden" name="detail_id" id="detail_id">
                      		<input type="hidden" name="request_id" id="request_id_0">

                          <div class="input-group-btn">
                              <button type="button" class="btn btn-default" onclick="get_search_history(0);" title="История поиска"><span class="glyphicon glyphicon-time"></span></button>
                              <button type="button" class="btn btn-default" onclick="select_brands(0);" title="Искать"><span class="glyphicon glyphicon-search"></span></button>
                              <button type="button" id="stop_search_0" class="btn btn-default" onclick="stop_search(0)" title="Остановить" style="display:none;"><span class="glyphicon glyphicon-remove-circle" style="color:red"></span></button>
                      	  </div>
                	 </div>
               </div>
               <div class="col-lg-6">
                  <div class="row">
                    <div class="col-lg-5" style="margin-top: 6px; padding-right: 0px;">';
                    $content.='<input type="checkbox" name="fast_sale" id="fast_sale_0" class=""> Быстрая продажа';
                    $content.='<input type="checkbox" name="show_price" id="show_price_0" class="" onchange="items_to_table(0);"> Показать закупочные цены';
                    
                   $content.=' <sup style="font-size: 120%; cursor: pointer; top: 0.6em; float: right" title="Допустим, ищете рычаг, а в списке есть болты и щётки. Внесите слово Рычаг. Уйдёт всё, что не имеет в названии Рычаг">&#9072;</sup> </div> 
                    <div class="col-lg-5">

                      <div class="input-group">

                        <input type="text" name="filter_text" id="filter_text_0" class="form-control search_str" placeholder="Убрать мусор" onkeyup="get_filter_text(0);">
                        <label for="filter_text_0" id="filter_text_label_0" onclick="clear_search_text(\'filter_text_0\',0);"></label>
                      </div>

                    </div>

                    <div class="col-lg-2">
                      <a onclick="clear_filter(0);" title="Очистить фильтры"><svg viewBox="0 0 24 24" style="width: 30px; margin-top: 4px;">
                        <path d="M3,2v2l6,8h6l6-8V2H3z M15,5.188L13.188,7L15,8.813L13.813,10L12,8.188L10.188,10L9,8.813L10.813,7L9,5.188L10.188,4 L12,5.813L13.813,4L15,5.188z M9,13v6l6,3v-9H9z"/ fill="gray">
                        </svg></a>
                      </div>

                  </div>
               </div>

         </div>
         </form>
        </div>
         <div id="search_history_list_0"></div>
      	<br>
      	<div id="select_brands_0"></div>
        <div id="search_status_0"></div>
        <table style="width: 100%"><tr>
        <td valign="top" style="border-right:1px solid gray"><div id="zapchasti_list_0" style="max-height:750px; overflow: auto;"></div></td>
        <td valign="top"><div id="zapchasti_content_0"></div></td>
        </tr></table>
  </div>
</div>
<script> remove_search_tab(0); 
get_search_opts().then(function(data1){
  create_new_search_tab(';
  if(isset($_GET['article'])) $content.='"'.$_GET['article'].'"';
  if(isset($_GET['article'])) $content.=',"'.$_GET['brand'].'"';
  $content.=');
},create_new_search_tab(';
if(isset($_GET['article'])) $content.='"'.$_GET['article'].'"';
if(isset($_GET['article'])) $content.=',"'.$_GET['brand'].'"';
$content.=')
);
get_search_fields();
</script>
';
if(isset($_GET['article']))
    $content.='<script>$("#search_form").submit();</script>';
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
