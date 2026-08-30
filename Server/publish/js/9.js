var temp_zakaz_statuses={};
var temp_zakaz_detail_statuses={};

function get_price_types(type){
  var defer=$.Deferred();
 if(type==4) type=2;
 if(type==3) type=1;
 var send=[];
 send['price_type']=type;
 api_query_array("/api/index.php",send,"get_price_types").then(function(data){
    var datalen=data.price_types.length;
    var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>% ";
    if (type==2) table+="наценки";
    if (type==1) table+="скидки";
    table += "</th><th>Описание</th><th>Округление цен</th><th>Дата создания</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
      table += "<tr><td><div id='edit_price_type_"+data.price_types[i].id+"'></div>"+(i+1)+"</td>\
      <td>" + data.price_types[i].proc + "</td>\
      <td>"+data.price_types[i].descr+"</td>\
      <td>"+((data.price_types[i].type==1 || data.price_types[i].type==2)?data.price_types[i].round_for:"")+"</td>\
      <td>"+data.price_types[i].create_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
      table += "<td><form id='delete_price_type_"+data.price_types[i].id+"'>";
      table+="<input type=\"hidden\" name=\"price_type_id\" value=\""+data.price_types[i].id+"\"><input type=\"hidden\" name=\"type\" value=\""+data.price_types[i].type+"\"></form><div class='btn-group' style='display: flex;'>";
      table += "<a onclick=\"edit_price_type("+data.price_types[i].id+","+data.price_types[i].type+");\" title='Редактировать'><img src='/new_images/edit.svg' style='width:20px;'></a>";
      table += " <a title='Удалить' ";
      table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить этот документ?\',function(result){ if(result) api_query('/api/index.php','delete_price_type_"+data.price_types[i].id+"','delete_price_type').then(function(data){if(data.status=='ok') get_price_types("+type+")});});\"><img src='/new_images/garbage.svg' style='width:20px;'></a>"
      table += "</div></td>";
      table += "</tr>";
    }
    table+="</tbody></table>";
    $("#dict_price_types_"+type).html(table);
    defer.resolve(data);
 });
 return defer.promise();
}

function clear_search_order_text(input_id){
  $('#'+input_id).val('');
  //runTextFilterOrd();
        }

function get_currency_kurs(){
 api_query("/api/index.php","currency_kurs_form","get_currency_kurs").then(function(data){
    var datalen=data.currency_kurs.length;
    var table="<table class=\"table table-hover\"><thead><tr><th>Код валюты</th><th>Наименование Валюты</th><th>Курс </th><th>Дата</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
	table += "<tr><td>" + data.currency_kurs[i].CharCode + "</td><td>"+data.currency_kurs[i].Name+"</td><td>"+data.currency_kurs[i].Value/data.currency_kurs[i].Nominal+"</td><td>"+data.currency_kurs[i].date+"</td>";
	table += "<td><form id='delete_currency_kurs_"+data.currency_kurs[i].NumCode+"'><input type=\"hidden\" name=\"price_type_id\" value=\""+data.currency_kurs[i].NumCode+"\"></form><div class='btn-group' style='display: flex;'>";
	//table += "<button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\" onclick=\"edit_price_type('delete_price_type_"+data.currency_kurs[i].NumCode+"');\" title='Редактировать'></button>";
	//table += "<button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" title='Удалить' ";
	//table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить этот документ?\',function(result){ if(result) api_query('/api/index.php','delete_price_type_"+data.currency_kurs[i].NumCode+"','delete_price_type').then(function(data){if(data.status=='ok') location.reload()});});\"></button>"
	table += "</div></td>";
	table += "</tr>";
    }
    table+="</tbody></table>";
    $("#dict_currency_kurs").html(table);
 });
}


function save_price_type(form,close_div,button,in_module=''){
  var type=$('#'+form+' [name=type]').val();
  api_query("/api/index.php",form,"save_price_type").done(function(data){
    if (data.status=="ok"){
      $('#'+close_div).html('');
      if(parseInt(data.price_type_id)>0){
        get_price_types(type).then(function(data1){
          if(in_module=='sklad' && typeof(button)=="undefined"){
            $("#price_types_sklad").append('<div id="edit_price_type_'+data.price_type_id+in_module+'"></div>');
            var options='<option value="0">Не выбран</option>';
            var pt_len=data1.price_types.length;
            for(var i=0; i<pt_len; i++){
                options+='<option value="'+data1.price_types[i].id+'" markup="'+data1.price_types[i].proc+'"';
                if(parseInt(data1.price_types[i].id)==parseInt(data.price_type_id)) options+=' selected="selected"';
                options+='>'+data1.price_types[i].descr+'</option>';
            }
            $("select#price_type").html(options);
          }
          if((parseInt(type)==4 || parseInt(type)==3) && typeof(button)=="undefined") edit_price_type(data.price_type_id,type,in_module);
        });
      }
    }
  });
}

function print_edit_price_type_1(data,type,in_module=''){
	var data_html='<form id="edit_price_type_form_'+data.price_type.id+'" onsubmit="event.preventDefault();">\
	<div class="form-group row">\
	<label for="proc" class="col-sm-5 col-form-label text-nowrap">Введите';
	if(type==2) data_html+=' наценку';
	if(type==1) data_html+=' скидку';
	data_html+=' в %</label>\
	<div class="col-xs-7">\
	<input type="hidden" name="price_type_id" value="'+data.price_type.id+'">\
	<input type="hidden" name="type" value="'+type+'">\
        <input type="text" class="form-control search_str" name="proc" id="proc" value="'+data.price_type.proc+'" placeholder="Введите % ';
	if(type==2) data_html+='наценки';
	if(type==1) data_html+='скидки';
	data_html+='"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="proc" id="proc_label" onclick="clear_search_order_text(\'proc\');"></label></div>\
	</div>\
	<div class="form-group row">\
	<label for="descr" class="col-sm-5 col-form-label text-nowrap">Наименование</label>\
	<div class="col-sm-7">\
	 <input type="text" class="form-control search_str" name="descr" id="descr" value="'+data.price_type.descr+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="descr" id="descr_label" onclick="clear_search_order_text(\'descr\');"></label>\
	<div id="dealer_list_new"></div>\
	</div>\
	</div>\
  <div class="form-group row">\
	<label for="round_for" class="col-sm-5 col-form-label text-nowrap">Округлить до</label>\
	<div class="col-sm-7">\
  <select name="round_for" id="round_for" class="form-control">';
  if(parseInt(data.price_type.round_for)==1) data_html+='<option value="1" selected>1</option>';
  else data_html+='<option value="1">1</option>';
  if(parseInt(data.price_type.round_for)==5) data_html+='<option value="5" selected>5</option>';
  else data_html+='<option value="5">5</option>';
  if(parseInt(data.price_type.round_for)==10) data_html+='<option value="10" selected>10</option>';
  else data_html+='<option value="10">10</option>';
  if(parseInt(data.price_type.round_for)==50) data_html+='<option value="50" selected>50</option>';
  else data_html+='<option value="50">50</option>';
  if(parseInt(data.price_type.round_for)==100) data_html+='<option value="100" selected>100</option>';
  else data_html+='<option value="100">100</option>';
  data_html+='</select>';
	data_html+='<div id="dealer_list_new"></div>\
	</div>\
	</div>\
	</form>\
	<button type="button" class="btn btn-primary" onclick="save_price_type(\'edit_price_type_form_'+data.price_type.id+in_module+'\',\'edit_price_type_'+data.price_type.id+in_module+'\',\'save\',\''+in_module+'\');">Сохранить</button>\
	<button type="button" class="btn btn-secondary pull-right" onclick="$(\'#edit_price_type_'+data.price_type.id+in_module+'\').html(\'\');">Закрыть</button>\
	';
  $("[id^=edit_price_type_]").html('');
	create_window("edit_price_type_div_"+data.price_type.id+in_module,"Изменение типа прайса","edit_price_type_"+data.price_type.id+in_module,data_html);
}

function print_edit_price_type_3(data,type,in_module=''){
	var data_html='<form id="edit_price_type_form_'+data.price_type.id+'">';
	data_html+='<div class="form-group row"><div class="col-sm-5"><b>Тип:</b></div><div class="col-sm-7"> Дифференцированная';
	if(type==4) data_html+=' наценка';
	if(type==3) data_html+=' скидка';
	data_html+='</div></div>';
	data_html+='<div class="form-group row">\
	<label for="descr" class="col-sm-5 col-form-label text-nowrap">Наименование</label>\
	<div class="col-sm-7">\
	 <input type="text" class="form-control search_str" name="descr" id="descr" value="'+data.price_type.descr+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="descr" id="descr_label" onclick="clear_search_order_text(\'descr\');"></label>\
	</div>';
  if(type==3){
    data_html+='<label for="descr" class="col-sm-5 col-form-label text-nowrap">Применить к суммарному обороту</label>\
    <div class="col-sm-7">\
    <input type="checkbox" name="use_sum_trade" id="use_sum_trade_'+data.price_type.id+'" '+(data.price_type.use_sum_trade==1?"checked":"")+'>\
    </div>';
  }
	data_html+='</div>\
	<input type="hidden" name="price_type_id" value="'+data.price_type.id+'">\
	<input type="hidden" name="type" value="'+type+'">\
	</form>';
	data_html+='<hr><button type="button" class="btn btn-primary" onclick="edit_price_type_differencial_values('+type+','+data.price_type.id+');">Добавить</button>';
	data_html+='<div id="new_diff_val"></div>';
	data_html+='<div id="differencial_values"></div>';
	data_html+='\
	<hr>\
	<button type="button" class="btn btn-primary" onclick="save_price_type(\'edit_price_type_form_'+data.price_type.id+in_module+'\',\'edit_price_type_'+data.price_type.id+in_module+'\',\'save\',\''+in_module+'\');">Сохранить</button>\
	<button type="button" class="btn btn-secondary pull-right" onclick="$(\'#edit_price_type_'+data.price_type.id+in_module+'\').html(\'\');">Закрыть</button>\
	';
  $("[id^=edit_price_type_]").html('');
	create_window("edit_price_type_div_"+data.price_type.id+in_module,"Изменение типа прайса","edit_price_type_"+data.price_type.id+in_module,data_html);
	get_price_type_differencial_values(data.price_type.id,type);
}

function get_price_type_differencial_values(price_type_id,type=0) {
    var send=new Array();
    $("#differencial_values").html('<img src="/images/30.gif">');
    send['price_type_id']=price_type_id;
    //send['price_type_type']=type;
    api_query_array("/api/index.php",send,"get_price_type_differencial_values").then(function(data){
      var table='<table class="table"><thead><tr><th>Цена (от)</th><th>Цена (до)</th><th>';
      if(type==3) table+='Скидка в %';
      if(type==4) table+='Наценка в %';
      table+='</th><th>Описание</th><th>Округ.</th><th></th></tr></thead><tbody>';
      var len=data.price_type_differencial_values.length;
      for (var i=0; i<len; i++){
          table+='<tr>';
          table+='<td><div id="edit_diff_val_'+data.price_type_differencial_values[i].id+'"></div>'+data.price_type_differencial_values[i].min_sum+'</td>\
          <td>'+data.price_type_differencial_values[i].max_sum+'</td>\
          <td>'+data.price_type_differencial_values[i].value+'</td>\
          <td>'+data.price_type_differencial_values[i].descr+'</td>\
          <td>'+data.price_type_differencial_values[i].round_for+'</td>';
          table+='<td nowrap>\
          <a onclick="edit_price_type_differencial_values('+type+','+price_type_id+','+data.price_type_differencial_values[i].id+');">\
          <img src="/new_images/edit.svg" style="width:20px;">\
          </a>';
          table+='<a onclick="delete_price_type_differencial_value('+data.price_type_differencial_values[i].id+','+price_type_id+','+type+');">\
          <img src="/new_images/garbage.svg" style="width:20px;">\
          </button></td>';
          table+='</tr>';
      }
      table+="</tbody></table>";
      $("#differencial_values").html(table);
    });
}

function delete_price_type_differencial_value(id,price_type_id,type){
    bootbox.confirm('Вы точно хотите удалить этот документ?',function(result){
	if(result) {
	    send=new Array();
	    send['price_type_differencial_value_id']=id;
	    api_query_array('/api/index.php',send,'delete_price_type_differencial_value').then(function(data){
		if(data.status=='ok')
		    get_price_type_differencial_values(price_type_id,type);
	    });
	}
    });
}

function edit_price_type_differencial_values(type,price_type_id,id=0){
    var send=new Array();
    send['id']=id;
    api_query_array("/api/index.php",send,"get_price_type_differencial_value").then(function(data){
      if(data.price_type_differencial_value.length>0) var values=data.price_type_differencial_value[0];
      else {
          var values=new Object();
          values.min_sum=0;values.max_sum=0;values.value=0;values.round_for=1;values.descr="";
      }
      var table='<div id="price_type_differencial_values"><div class="form-group row">\
      <div class="col-sm-4"><label for="min_sum" class="col-form-label text-nowrap">Цена (от)</label></div>\
      <div class="col-sm-8">\
      <input type="text" class="form-control search_str" onclick="this.select();" name="min_sum" id="min_sum" value="'+values.min_sum+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="min_sum" id="min_sum_label" onclick="clear_search_order_text(\'min_sum\');"></label>\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4"><label for="max_sum" class="col-form-label text-nowrap">Цена (до)</label></div>\
      <div class="col-sm-8">\
      <input type="text" class="form-control search_str" onclick="this.select();" name="max_sum" id="max_sum" value="'+values.max_sum+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="max_sum" id="max_sum_label" onclick="clear_search_order_text(\'max_sum\');"></label>\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4"><label for="value" class="col-form-label text-nowrap">наценка/скидка<br> в %</label></div>\
      <div class="col-sm-8">\
      <input type="text" class="form-control search_str" onclick="this.select();" name="value" id="value" value="'+values.value+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="value" id="value_label" onclick="clear_search_order_text(\'value\');"></label>\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4"><label for="round_for" class="col-form-label text-nowrap">Округлить до</label></div>\
      <div class="col-sm-8">\
      <select name="round_for" id="round_for" class="form-control">';
      if(parseInt(values.round_for)==1) table+='<option value="1" selected>1</option>';
      else table+='<option value="1">1</option>';
      if(parseInt(values.round_for)==5) table+='<option value="5" selected>5</option>';
      else table+='<option value="5">5</option>';
      if(parseInt(values.round_for)==10) table+='<option value="10" selected>10</option>';
      else table+='<option value="10">10</option>';
      if(parseInt(values.round_for)==50) table+='<option value="50" selected>50</option>';
      else table+='<option value="50">50</option>';
      if(parseInt(values.round_for)==100) table+='<option value="100" selected>100</option>';
      else table+='<option value="100">100</option>';
      table+='</select>';
      table+='</div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4"><label for="descr" class="col-form-label text-nowrap">Описание</label></div>\
      <div class="col-sm-8">\
      <input type="text" class="form-control search_str" onclick="this.select();" name="descr" id="descr" value="'+values.descr+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="descr" id="descr_label" onclick="clear_search_order_text(\'descr\');"></label>\
      </div>\
      </div>';
      table+='<input type="hidden" name="id" id="id" value="'+id+'"><input type="hidden" name="price_type_id" id="price_type_id" value="'+price_type_id+'">';
      table+='</div>\
      <button type="button" class="btn btn-primary" onclick="save_price_type_differencial_value(\'price_type_differencial_values\','+type+');">Сохранить</button>';
      if(id==0) table+='<button type="button" class="btn btn-secondary pull-right" onclick="$(\'#new_diff_val\').html(\'\');">Закрыть</button>';
      else table+='<button type="button" class="btn btn-secondary pull-right" onclick="$(\'#edit_diff_val_'+id+'\').html(\'\');">Закрыть</button>';
      if(id==0) create_window("new_diff_val_div","Создание новой записи","new_diff_val",table);
      else create_window("edit_diff_val_div_"+id,"Редактирование записи","edit_diff_val_"+id,table);
    });
}

function edit_price_type(price_type_id,type,in_module){
  var send=[];
  send['price_type_id']=price_type_id;
  send['type']=type;
  api_query_array("/api/index.php",send,"get_price_type").done(function(data){
    if (data.status=="ok"){
      if(type==1 || type==2) print_edit_price_type_1(data,type,in_module);
      if(type==3 || type==4) print_edit_price_type_3(data,type,in_module);
    }
    });
}

function save_price_type_differencial_value(form,type=0){
  var send=[];
  send['min_sum']=$("#"+form+" input[name=min_sum]").val();
  send['max_sum']=$("#"+form+" input[name=max_sum]").val();
  send['value']=$("#"+form+" input[name=value]").val();
  send['round_for']=$("#"+form+" select[name=round_for]").val();
  send['descr']=$("#"+form+" input[name=descr]").val();
  send['id']=$("#"+form+" input[name=id]").val();
  send['price_type_id']=$("#"+form+" input[name=price_type_id]").val();
    api_query_array("/api/index.php",send,"save_price_type_differencial_value").then(function(data){
      if(data.status=="ok"){
          if($("#"+form+" input[name=id]").val()==0) {
            get_price_type_differencial_values($("#"+form+" input[name=price_type_id]").val(),type);
            $("#new_diff_val").html('');
              }
              else {
            get_price_type_differencial_values($("#"+form+" input[name=price_type_id]").val(),type);
            $("#edit_diff_val_"+$("#"+form+" input[name=price_type_id]").val()).html('');
          }
      }
    });
}

function add_new_price_type(type,in_module=''){

    var data_html='<form id="new_price_type_form_'+type+in_module+'" onsubmit="event.preventDefault();">';
    if(type==1 || type==2){
      data_html+='<div class="form-group row">\
      <label for="proc" class="col-sm-5 col-form-label text-nowrap">Введите';
      switch(type){
        case 1: data_html+=' скидку'; break;
        case 2: data_html+=' наценку'; break;
        case 3: data_html+=' скидку'; break;
        case 4: data_html+=' наценку'; break;
      }
      data_html+=' в %</label>\
      <div class="col-xs-7">\
      <input type="text" class="form-control" name="proc" id="proc" value="" placeholder="Введите % ';
      switch(type){
        case 1: data_html+=' скидки'; break;
        case 2: data_html+=' наценки'; break;
        case 3: data_html+=' скидки'; break;
        case 4: data_html+=' наценки'; break;
      }
      data_html+='"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="proc" id="proc_label" onclick="clear_search_order_text(\'proc\');"></label>\
      </div>\
      </div>';
    }
    data_html+='<div class="form-group row">\
    <label for="descr" class="col-sm-5 col-form-label text-nowrap">Наименование</label>\
    <div class="col-sm-7">\
     <input type="hidden" name="type" id="new_price_type_type" value="'+type+'">\
     <input type="text" class="form-control search_str" name="descr" id="descr" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="descr" id="descr_label" onclick="clear_search_order_text(\'descr\');"></label>\
    </div>';
    if(type==3)
      data_html+='<label for="descr" class="col-sm-5 col-form-label text-nowrap">Применить к суммарному обороту</label>\
      <div class="col-sm-7">\
      <input type="checkbox" name="use_sum_trade" id="use_sum_trade_0">\
      </div>';
      data_html+='</div>\
    </form>\
    <button class="btn btn-primary" type="button" onclick="save_price_type(\'new_price_type_form_'+type+in_module+'\',\'new_price_type_'+type+in_module+'\',undefined,\''+in_module+'\');">Сохранить</button>\
    <button class="btn btn-secondary pull-right" type="button" onclick="$(\'#new_price_type_'+type+in_module+'\').html(\'\');">Закрыть</button>\
    ';
    if (type==2) var header="Добавление новой наценки";
    if (type==1) var header="Добавление новой скидки";
    if (type==4) var header="Добавление новой дифференцированной наценки";
    if (type==3) var header="Добавление новой дифференцированной скидки";
    create_window("add_new_price_type_"+type+in_module,header,"new_price_type_"+type+in_module,data_html)
}

function change_new_detail_brand(brand_id,detail_id,brand_name,detail_name){
    $("#add_new_detail_form input[name=brand_id]").val(brand_id);
    $("#add_new_detail_form input[name=detail_id]").val(detail_id);
    $("#add_new_detail_form input[name=brand]").val(brand_name);
    $("#add_new_detail_form input[name=name]").val(detail_name);
    $("#brand_helper").hide();
}

function get_brands(){
    $('#brand_helper_content').html("Загружаю брэнды...");
    $('#brand_helper').show();
    $('#brand_helper').draggable();
    $.ajax({
	url: "/test.php?article="+$('#add_new_detail_form input[name=article]').val(),
	type: "GET"
    }).done(function(data){
	if (data!=null){
	    var datalen=data.length;
	    var brands_html="";
	    var table="<table class='table table-hover'><tr><th>Артикул</th><th>Брэнд</th><th>Наименование</th></tr>";
	    for (var i=0; i<datalen; i++){
		table+="<tr style='cursor:pointer' onclick='change_new_detail_brand("+data[i].brand_id+","+data[i].detail_id+",\""+data[i].brand_name.replace(/'/g,'').replace(/"/g,'')+"\",\""+data[i].name.replace(/'/g,'').replace(/"/g,'').replace(/"/g,'')+"\");'><td>"+data[i].article+"</td><td>"+data[i].brand_name+"</td><td>"+data[i].name+"</td></tr>";
	    }
	    table+="</table>";
	    $('#brand_helper_content').html(table);
	}
	else {
	    $('#brand_helper_content').html("деталь не найдена в базе данных");
	}
	//$('#brand_helper').draggable();
	//$('#brand_helper').toggle();
    });
    //return false;
}

function fix_price_select_brands(){
    //clear_search_form("search_form");
    api_query("/api/index.php","add_new_fix_price_detail_form","get_brands").then(function(data){
    	var brands=new Array();
    	var table="<table class='table table-hover'><thead><th>артикул</th><th>наименование</th><th>брэнд</th></thead><tbody>";
    	var brands_count=data.brands.length;
    	$.each(data.brands, function(key,val){
    	    table += '<tr onclick="fix_price_set_brand('+val.brand_id+','+val.detail_id+',\''+val.brand+'\',\''+val.name+'\'); $(\'#fix_price_select_brands\').html(\'\');"><td>'+val.article+'</td><td>'+val.name+'</td><td><b>'+val.brand+'</b></td></tr>';
    	});
    	table += '</tbody></table>';
    	if(brands_count>0){
    	    if(brands_count>1)
    		    create_window_gray("fix_price_select_brands_div","Выберите брэнд",'fix_price_select_brands',table);
    	    else {
        		$("#add_new_fix_price_detail_form [id=brand_id]").val(data.brands[0].brand_id);
        		$("#add_new_fix_price_detail_form [id=brand]").val(data.brands[0].brand);
        		$("#add_new_fix_price_detail_form [id=detail_id]").val(data.brands[0].detail_id);
            $("#add_new_fix_price_detail_form [id=name]").val(data.brands[0].name);
        		//search();
    	    }
    	}
    	else {
    	    $("#add_new_fix_price_detail_form [id=brand_id]").val('');
    	    $("#add_new_fix_price_detail_form [id=brand]").val('');
    	    $("#add_new_fix_price_detail_form [id=detail_id]").val('');
    	    //search();
    	}
    });

}

function fix_price_set_brand(brand_id,detail_id,brand,name){
    if(parseInt(brand_id)>0 && parseInt(detail_id)>0){
      	$("#add_new_fix_price_detail_form [id=brand_id]").val(brand_id);
      	$("#add_new_fix_price_detail_form [id=brand]").val(brand);
      	$("#add_new_fix_price_detail_form [id=detail_id]").val(detail_id);
        $("#add_new_fix_price_detail_form [id=name]").val(name);
    }
}

function save_new_detail_to_base(document_id){
    api_query("/api/index.php","add_new_detail_form","save_document_detail").then(function(data){
	     get_document_details("document_form_"+document_id);
    });
}

function save_new_fix_price_detail_to_base(){
    api_query("/api/index.php","add_new_fix_price_detail_form","save_fix_price_detail").then(function(data){
      if(typeof(data.status)!="undefined" && data.status=="ok")
       get_fix_price_details1("fix_price_form");
      
    });
}

function save_fix_price_detail_to_base(){
    api_query("/api/index.php","edit_fix_price_detail_form","save_fix_price_detail").then(function(data){
      if(typeof(data.status)!="undefined" && data.status=="ok")
	      get_fix_price_details1("fix_price_form");
    });
}

function edit_detail(detail_form){
  api_query("/api/index.php",detail_form,"get_document_detail").done(function(data1){
    var data=data1.document_details[0];
    //$('#add_new_detail_div').toggle();
    //$('#add_new_detail_header').html("Добавление детали:");
    var new_detail_content="<form id='add_new_detail_form'><table class='table'>";
    new_detail_content+="<tr><td>артикул: </td>";
    new_detail_content+="<td><input type='text' name='article' class='form-control search_str' id='article' value='"+data.article+"' onchange='get_brands();'><label style='position: absolute; top: 3.8em; right: 1.2em;' for='article' id='article_label' onclick='clear_search_order_text(\'article\');'></label><input type='hidden' name='detail_id' value='"+data.detail_id+"'><input type='hidden' name='sklad_id' value='"+data.sklad_id+"'><input type='hidden' name='document_id' value='"+data.document_id+"'></td></tr>";
    new_detail_content+="<tr><td>брэнд: </td><td><input type='text' class='form-control search_str' id='brand' name='brand' value='"+data.brand+"'><label style='position: absolute; top: 0.8em; right: 1.2em;' for='brand' id='brand_label' onclick='clear_search_order_text(\'brand\');'></label><input type='hidden' name='brand_id' value='"+data.brand_id+"'>";
    new_detail_content+="<div id='brand_helper' style='position: absolute; display:none; border: 1px solid #337ab7'>";
    new_detail_content+=" <div id='brand_helper_header' style='padding: 5px; background-color: #2e6da4; color: #fff;'>Выберите брэнд детали &nbsp";
    new_detail_content+="  <button class='close pull-right' onclick='$(\"#brand_helper\").hide(); return false;'><span>&times;</span></button>";
    new_detail_content+=" </div>";
    new_detail_content+=" <div id='brand_helper_content' style='background-color: #eee; padding: 10px;'>";
    new_detail_content+=" </div>";
    new_detail_content+="</div></td></tr>";
    new_detail_content+="<tr><td>наименование: </td><td><input type='text' class='form-control search_str' id='name' name='name' value='"+data.name+"'><label style='position: absolute; top: 0.8em; right: 1.2em;' for='name' id='name_label' onclick='clear_search_order_text(\'name\');'></label></td></tr>";
    new_detail_content+="<tr><td>цена: </td><td><input type='text'  class='form-control search_str' id='price' name='price' value='"+data.price+"'><label style='position: absolute; top: 0.8em; right: 1.2em;' for='price' id='price_label' onclick='clear_search_order_text(\'price\');'></label></td></tr>";
    new_detail_content+="<tr><td>кол-во: </td><td><input type='text' class='form-control search_str' id='count' name='count' value='"+data.count+"'><label style='position: absolute; top: 0.8em; right: 1.2em;' for='count' id='count_label' onclick='clear_search_order_text(\'count\');'></label></td></tr>";
    new_detail_content+="<tr><td>срок доставки: </td><td><input type='text' class='form-control search_str' id='time' name='time' value='"+data.time+"'><label style='position: absolute; top: 0.8em; right: 1.2em;' for='time' id='time_label' onclick='clear_search_order_text(\'time\');'></label></td></tr>";
    //new_detail_content+="<tr><td>Документ: </td><td><input type='text' name='detail_flow_id' value='"+data.detail_flow_id+"'></td></tr>";
    new_detail_content+="<tr><td></td><td><button type='button' class='btn btn-secondary' id='save_new_detail' onclick='save_new_detail_to_base("+data.document_id+");'>Сохранить</button></td></tr>";
    new_detail_content+="</table></form>";
    create_window("add_new_detail","Изменение данных детали: ","add_new_detail_div",new_detail_content);
//    $('#new_detail_header_content').html("Изменение данных детали: ");
//    $('#add_new_detail_content').html(new_detail_content);
//    $('#add_new_detail_div').draggable();
 });
}

function add_new_document_detail(document_id,sklad_id){
    //$('#add_new_detail_div').toggle();
    //$('#add_new_detail_header').html("Добавление детали:");
    var new_detail_content="<form id='add_new_detail_form'><table class='table'>";
    new_detail_content+="<tr><td>артикул: </td>";
    new_detail_content+="<td><input type='text' name='article' value='' class='form-control search_str' id='article' onchange='get_brands();'><label style='position: absolute; top: 3.8em; right: 1.2em;' for='article' id='article_label' onclick='clear_search_order_text(\'article\');'></label><input type='hidden' name='document_id' value='"+document_id+"'><input type='hidden' name='detail_id' value=''><input type='hidden' name='sklad_id' value='"+sklad_id+"'></td></tr>";
    new_detail_content+="<tr><td>брэнд: </td><td><input type='text' id='brand' class='form-control search_str' name='brand' value=''><label style='position: absolute; top: 0.8em; right: 1.2em;' for='brand' id='brand_label' onclick='clear_search_order_text(\'brand\');'></label><input type='hidden' name='brand_id' value=''>";
    new_detail_content+="<div id='brand_helper' style='position: absolute; display:none; border: 1px solid #337ab7'>";
    new_detail_content+=" <div id='brand_helper_header' style='padding: 5px; background-color: #2e6da4; color: #fff;'>Выберите брэнд детали &nbsp";
    new_detail_content+="  <button class='close pull-right' onclick='$(\"#brand_helper\").hide(); return false;'><span>&times;</span></button>";
    new_detail_content+=" </div>";
    new_detail_content+=" <div id='brand_helper_content' style='background-color: #eee; padding: 10px;'>";
    new_detail_content+=" </div>";
    new_detail_content+="</div></td></tr>";
    new_detail_content+="<tr><td>наименование: </td><td><input type='text' class='form-control search_str' id='name' name='name' value=''><label style='position: absolute; top: 0.8em; right: 1.2em;' for='name' id='name_label' onclick='clear_search_order_text(\'name\');'></label></td></tr>";
    new_detail_content+="<tr><td>цена: </td><td><input type='text' class='form-control search_str' id='price' name='price' value=''><label style='position: absolute; top: 0.8em; right: 1.2em;' for='price' id='price_label' onclick='clear_search_order_text(\'price\');'></label></td></tr>";
    new_detail_content+="<tr><td>кол-во: </td><td><input type='text'class='form-control search_str' id='count' name='count' value=''><label style='position: absolute; top: 0.8em; right: 1.2em;' for='count' id='count_label' onclick='clear_search_order_text(\'count\');'></label></td></tr>";
    new_detail_content+="<tr><td>срок доставки: </td><td><input type='text' class='form-control search_str' id='time' name='time' value=''><label style='position: absolute; top: 0.8em; right: 1.2em;' for='time' id='time_label' onclick='clear_search_order_text(\'time\');'></label></td></tr>";
    //new_detail_content+="<tr><td>Документ: </td><td><input type='text' name='detail_flow_id' value=''></td></tr>";
    new_detail_content+="<tr><td></td><td><button type='button' class='btn btn-secondary' id='save_new_detail' onclick='save_new_detail_to_base("+document_id+");'>Сохранить</button></td></tr>";
    new_detail_content+="</table></form>";
    create_window("add_new_detail","Добавление детали: ","add_new_detail_div",new_detail_content);
//    $('#new_detail_header_content').html("Добавление детали: ");
//    $('#add_new_detail_content').html(new_detail_content);
//    $('#add_new_detail_div').draggable();
}

function get_fix_price_details(fix_price_form){
 api_query("/api/index.php",fix_price_form,"get_fix_price_details").then(function(data){
    var datalen=data.fix_price_details.length;
    var table="";
    //table += "<div class='row' style='padding:5px;'><div class='col-xs-4'><button class=\"btn btn-primary btn-sm\" onclick=\"add_new_detail("+$('#'+sklad_form+' [name=sklad_id]').val()+")\">Добавить деталь</button></div>";
    //table += "<div class='col-xs-4 pull-right'><div class='input-group input-group-sm'><input type='text' class='form-control'><span class='input-group-btn'><button class='btn btn-default' type='button'>Поиск</button></span></div></div>";
    //table += "</div><div id='add_new_sklad_detail'></div>";
    table += "<table class=\"table table-hover\"><thead><tr><th>№</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Цена покупки</th><th>Кол-во</th><th>Срок доставки</th><th>Цена продажи</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
    	table += "<tr><td><div id='edit_fix_price_detail_"+data.fix_price_details[i].detail_id+"'></div>"+(i+1)+"</td><td>" + data.fix_price_details[i].article + "</td><td>"+data.fix_price_details[i].brand+"</td><td>"+data.fix_price_details[i].name+"</td><td>"+data.fix_price_details[i].fix_price+"</td><td>"+data.fix_price_details[i].create_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td><td>"+data.fix_price_details[i].update_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
    	table += "<td><form id='delete_detail_"+data.fix_price_details[i].detail_id+"'><input type=\"hidden\" name=\"detail_id\" value=\""+data.fix_price_details[i].detail_id+"\"></form>";
    	table += "<div class='btn-group' style='display: flex;'><button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\"  onclick=\"edit_fix_price_detail('delete_detail_"+data.fix_price_details[i].detail_id+"');\"></button>";
    	table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
    	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить фиксированную цену детали?\',function(result){ if(result) api_query('/api/index.php','delete_detail_"+data.fix_price_details[i].detail_id+"','delete_fix_price_detail').then(function(data){if(data.status=='ok') location.reload()});});\"></button>";
    	table += "</div>";
    	table += "</td>";
    	table += "</tr>";
    }
    table += "</tbody></table>";
    $("#fix_price_details_list").html(table);
 });
}


function get_fix_price_details1(fix_price_form){
 api_query("/api/index.php",fix_price_form,"get_fix_price_details").then(function(data){
    var datalen=data.fix_price_details.length;
    var table="<div class='row' style='padding:5px;'><div class='col-xs-3'><button class=\"btn btn-primary btn-sm\" onclick=\"add_new_fix_price_detail("+$('#'+fix_price_form+' [name=detail_id]').val()+")\">Добавить деталь</button></div>";
    /*table += '<span class="btn btn-success fileinput-button btn-sm">\
        <span>Загрузить файл</span>\
	<form id="fileupload">\
	<input type="hidden" name="sklad_id" value="'+data.sklad_id+'">\
	<input type="hidden" name="action" value="upload_file">\
        <input id="fileupload1" type="file" name="files[]" multiple>\
	</form>\
    </span>'; */
    table += "<div class='col-xs-4 pull-right'><div class='input-group input-group-sm'>";
    table += "<span id='fix_price_search'><input type='text' class='form-control input-sm search_str' id='searchFixPrice' name='search'";
    if (data.hasOwnProperty('search')) table+="value='"+data.search+"'";
    else table+="value=''";
    table += " onchange='$(\"#"+fix_price_form+" [name=search]\").val($(\"#fix_price_search [name=search]\").val());'><label style='position: absolute; top: 0.65em;' for='searchFixPrice' id='searchFixPrice_label' onclick='clear_search_order_text(\"searchFixPrice\");'></label></span>";
    table += "<span class='input-group-btn'><button class='btn btn-default btn-sm' type='button' onclick='$(\"#"+fix_price_form+" [name=page]\").val(1);get_fix_price_details1(\""+fix_price_form+"\")'>Поиск</button></span></div></div>";
    table += "</div><div id='add_new_fix_price_detail'></div><div id='select_sklad_cols_"+data.sklad_id+"'></div>";
    table += '<div id="progress" class="progress col-sm-9"  style="display:none;">\
        <div class="progress-bar progress-bar-success"></div>\
    </div>';
    table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
    var x=0,y=0,xx=0,yy=0;
    if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
    else var selected_page=1;
    for (var i=1; i<=data.fix_price_pages; i++){
	if(i>(selected_page+6) && i<(data.fix_price_pages-1)){
	    x=1;
	}
	else x=0;
	if (i<(selected_page-6) && i!=1){
	    y=1;
	}
	else y=0;
	if (x==1) {
		if (xx==0){
		    table += '<li';
		    table += '><a href="#" onclick="$(\'#'+fix_price_form+' input[name=page]\').val(\''+i+'\');';
		    if($('#fix_price_search [name=search]').val()!="") table += '$(\'#'+fix_price_form+' input[name=search]\').val(\''+$('#fix_price_search [name=search]').val()+'\');';
		    table += 'get_fix_price_details1(\''+fix_price_form+'\')">...</a></li>';
		}
		if (x==1) xx++;
	}
	else {
	    if (y==1) {
		if (yy==0){
		    table += '<li';
		    table += '><a href="#" onclick="$(\'#'+fix_price_form+' input[name=page]\').val(\''+i+'\');';
		    if($('#fix_price_search [name=search]').val()!="") table += '$(\'#'+fix_price_form+' input[name=search]\').val(\''+$('#fix_price_search [name=search]').val()+'\');';
		    table += 'get_fix_price_details1(\''+fix_price_form+'\')">...</a></li>';
		}
		if (y==1) yy++;
	    }
	    else {
		table += '<li';
		if(selected_page==i) table+= " class='active'";
		table += '><a href="#" onclick="$(\'#'+fix_price_form+' input[name=page]\').val(\''+i+'\');';
		if($('#fix_price_search [name=search]').val()!="") table += '$(\'#'+fix_price_form+' input[name=search]\').val(\''+$('#fix_price_search [name=search]').val()+'\');';
		table += 'get_fix_price_details1(\''+fix_price_form+'\')">'+i+'</a></li>';
	    }
	}
    }
    table += '</ul></div>';
    table += "<table class=\"table table-hover\"><thead><tr><th>№</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Цена</th><th>Мин. наценка</span></th><th>Создано</th><th>Обновлено</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
	table += "<tr><td><div id='fix_price_detail_"+data.fix_price_details[i].detail_id+"'></div>"+(i+1)+"</td><td>" + data.fix_price_details[i].article + "</td>";
	table += "<td>"+data.fix_price_details[i].brand+"</td><td>"+data.fix_price_details[i].name+"</td><td>"+data.fix_price_details[i].fix_price+" руб.</td>";
	table += "<td>"+data.fix_price_details[i].minimum_markup+"%</td><td>"+data.fix_price_details[i].create_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td><td>"+data.fix_price_details[i].update_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
	table += "<td><form id='delete_detail_"+data.fix_price_details[i].detail_id+"'><input type=\"hidden\" name=\"detail_id\" value=\""+data.fix_price_details[i].detail_id+"\"></form>";
	table += "<div class='btn-group' style='display: flex;'><a onclick=\"edit_fix_price_detail("+data.fix_price_details[i].detail_id+");\"><img src='/new_images/edit.svg' style='width:20px;'></a>";
	table += " <a ";
	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить фиксированную цену детали?\',function(result){ if(result) api_query('/api/index.php','delete_detail_"+data.fix_price_details[i].detail_id+"','delete_fix_price_detail').then(function(data){if(data.status=='ok') get_fix_price_details1('fix_price_form')});});\"><img src='/new_images/garbage.svg' style='width:20px;'></a>";
	table += "</div>";
	table += "</td>";
	table += "</tr>";
    }
    table += "</tbody></table>";
/*    table+="\
    <script>\
	file_uploader();\
    </script>"; */
    $("#fix_price_details_list").html(table);
 });
}
//$( function() {
//    $( ".draggable" ).draggable();
//  } );


function edit_fix_price_detail(detail_id){
  var send=new Array();
  send['detail_id']=detail_id;
  api_query_array("/api/index.php",send,"get_fix_price_detail").done(function(data1){
    var fp_detail=data1.fix_price_details[0];
    var new_detail_content="<form id='edit_fix_price_detail_form'><table class='table'>";
    new_detail_content+="<tr><td>артикул: </td>";
    new_detail_content+="<td><input type='text' class='form-control search_str' name='article'  value='"+fp_detail.article+"' id='article' disabled><label style='position: absolute; top: 3.8em; right: 1.2em;' for='article' id='article_label' onclick='clear_search_order_text(\"article\");'></label><input type='hidden' name='detail_id' value='"+fp_detail.detail_id+"' id='detail_id'></td></tr>";
    new_detail_content+="<tr><td>брэнд: </td><td><input type='text' class='form-control search_str' name='brand' value='"+fp_detail.brand+"' id='brand' disabled><label style='position: absolute; top: 7.5em; right: 1.2em;' for='brand' id='brand_label' onclick='clear_search_order_text(\"brand\");'></label><input type='hidden' name='brand_id' value='"+fp_detail.brand_id+"' id='brand_id'>";
    new_detail_content+="<div id='select_brands' style=''>";
    new_detail_content+=" </div>";
    new_detail_content+="</div></td></tr>";
    new_detail_content+="<tr><td>наименование: </td><td><input type='text' class='form-control' name='name' value='"+fp_detail.name+"' id='name' disabled><label style='position: absolute; top: 11.2em; right: 1.2em;' for='name' id='name_label' onclick='clear_search_order_text(\"name\");'></label></td></tr>";
    new_detail_content+="<tr><td>цена: <span class='glyphicon glyphicon-question-sign' title='Цена в приоритете при одновременном задании Цены и Мин. наценки. Возможно задать цену ниже себестоимости, например, для распродажи неликвида'></span></td><td><input type='text' class='form-control' name='fix_price' value='"+fp_detail.fix_price+"'></td></tr>";
    new_detail_content+="<tr><td>мин наценка: <span class='glyphicon glyphicon-question-sign' title='Действует только при незаполненном окне Цена. Наценка может быть и отрицательной.'></span></td><td><input type='text' class='form-control' name='minimum_markup' value='"+fp_detail.minimum_markup+"'></td></tr>";
    new_detail_content+="<tr><td></td><td><button type='button' class='btn btn-secondary' class='form-control' id='save_new_detail' onclick='save_fix_price_detail_to_base();'>Сохранить</button></td></tr>";
    new_detail_content+="</table></form>";
    create_window("fix_price_detail_"+detail_id+"_div","Добавление детали: ","fix_price_detail_"+detail_id,new_detail_content);
 });
}

function add_new_fix_price_detail(){
    $('#add_new_detail_div').toggle();
    //$('#add_new_detail_header').html("Добавление детали:");
    var new_detail_content="<form id='add_new_fix_price_detail_form'><table class='table'>";
    new_detail_content+="<tr><td>артикул: </td>";
    new_detail_content+="<td><input type='text' class='form-control search_str' name='article' id='article' value='' onchange='fix_price_select_brands();' id='article'><label style='position: absolute; top: 3.8em; right: 1.2em;' for='article' id='article_label' onclick='clear_search_order_text(\"article\");'></label><input type='hidden' name='detail_id' value='' id='detail_id'></td></tr>";
    new_detail_content+="<tr><td>брэнд: </td><td><input type='text' class='form-control search_str' name='brand' value='' id='brand'><label style='position: absolute; top: 7.5em; right: 1.2em;' for='brand' id='brand_label' onclick='clear_search_order_text(\"brand\");'></label><input type='hidden' name='brand_id' value='' id='brand_id'>";
    new_detail_content+="<div id='fix_price_select_brands' style=''>";
    //new_detail_content+=" <div id='brand_helper_header' style='padding: 5px; background-color: #2e6da4; color: #fff;'>Выберите брэнд детали &nbsp";
    //new_detail_content+="  <button class='close pull-right' onclick='$(\"#brand_helper\").hide(); return false;'><span>&times;</span></button>";
    //new_detail_content+=" </div>";
    //new_detail_content+=" <div id='brand_helper_content' style='background-color: #eee; padding: 10px;'>";
    new_detail_content+=" </div>";
    new_detail_content+="</div></td></tr>";
    new_detail_content+="<tr><td>наименование: </td><td><input type='text' class='form-control' name='name' value='' id='name'><label style='position: absolute; top: 11.2em; right: 1.2em;' for='name' id='name_label' onclick='clear_search_order_text(\"name\");'></label></td></tr>";
    new_detail_content+="<tr><td>цена: <span class='glyphicon glyphicon-question-sign' title='Цена в приоритете при одновременном задании Цены и Мин. наценки. Возможно задать цену ниже себестоимости, например, для распродажи неликвида'></span></td><td><input type='text' class='form-control' name='fix_price' value=''><label style='position: absolute; top: 14.9em; right: 1.2em;' for='fix_price' id='fix_price_label' onclick='clear_search_order_text(\"fix_price\");'></label></td></tr>";
    new_detail_content+="<tr><td>мин наценка: <span class='glyphicon glyphicon-question-sign' title='Действует только при незаполненном окне Цена. Наценка может быть и отрицательной.'></span></td><td><input type='text' class='form-control' name='minimum_markup' value=''><label style='position: absolute; top: 18.6em; right: 1.2em;' for='minimum_markup' id='minimum_markup_label' onclick='clear_search_order_text(\"minimum_markup\");'></label></td></tr>";
    //new_detail_content+="<tr><td>срок доставки: </td><td><input type='text' class='form-control' name='time' value=''></td></tr>";
    //new_detail_content+="<tr><td>Наценка частная: </td><td><input type='text' class='form-control' name='detail_markup' value=''></td></tr>";
    new_detail_content+="<tr><td></td><td><button type='button' class='btn btn-secondary' class='form-control' id='save_new_detail' onclick='save_new_fix_price_detail_to_base();'>Сохранить</button></td></tr>";
    new_detail_content+="</table></form>";
    create_window("add_new_fix_price_detail_div","Добавление детали: ","add_new_fix_price_detail",new_detail_content);
}

var topology=new Array();
topology["topology_id"]=0;
topology['name']="";
topology["levels"]=new Array();
var topologys=new Array();

function add_new_sklad_topology(topology_id){
  var levels=0;
  if(typeof(topology_id)=="undefined" || topology_id==0){
    topology["topology_id"]=0;
    topology['name']="";
    topology["levels"]=new Array();
  }
  var table='<div class="form-group row col-sm-12">\
      <div class="col-sm-12">\
	    <label for="topology_name" class="col-sm-4 col-form-label">Наименование топологии</label>\
	    <div class="col-sm-8">\
		    <input class="form-control search_str" type="text" id="topology_name" name="topology_name" placeholder="Наименование" onchange="change_topology_attr(\'name\')" value="'+topology['name']+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="topology_name" id="topology_name_label" onclick="clear_search_order_text(\'topology_name\');"></label>\
	    </div>\
	    </div>\
      </div>\
      <div class="">\
	  <table id="new_topology_levels" class="table"><thead><tr><th colspan=4>Уровни топологии склада ';
	  table+='<a class="pull-right" onclick="create_new_topology_level();" title="добавить новый уровень в начало">+</a>';
	  table+='</th></tr></thead><tbody id="new_topology_tbody">\
	  </tbody><tbody>\
	  <tr><td><button class="btn btn-success" onclick="save_topology();">Сохранить</button></td>\
	  <td></td>\
	  <td colspan="2"><button class="btn btn-default pull-right" onclick="close_topology();">Отмена</button></td>\
	  </tbody></table>\
	</div>';
  create_window("new_sklad_topology_div","Создать топологию","new_sklad_topology",table);
  print_topology_levels();
}

function save_topology(){
	var send_topology={};
	send_topology=Object.assign({},topology);
	topology['levels'].forEach(function(item,index){
		send_topology.levels[index]=Object.assign({},topology['levels'][index]);
	});
	//console.log(send_topology);
	api_query_obj("/api/index.php",send_topology,"save_topology").then(function(data){
    if(data.status=="ok")
      $("#new_sklad_topology").html('');
      get_sklad_topologys();
	});
}

function close_topology(){
	$("#new_sklad_topology").html('');
}

function change_topology_attr(name){
	topology[name]=$("#topology_name").val();
}

function create_new_topology_level(id){
  if(typeof(id)!="undefined"){
	topology['levels'].splice(id+1,0,new Array());
	tlen=id+1;
  }
  else {
	//var tlen=topology['levels'].length;
	topology['levels'].unshift(new Array());
  }
  /*var tr='<tr id="tr_'+tlen+'"><td>Наименование: <input id="tlevel_'+tlen+'_name" onchange="change_level('+tlen+',\'name\')"></td>';
  tr+='<td>Тип: \
    <select class="" type="text" id="tlevel_'+tlen+'_type" onchange="change_level('+tlen+',\'type\')">\
    <option value="1">Числовой</option>\
    <option value="2">Алфавитный (лат.)</option>\
    <option value="3">Алфавитный (рус.)</option>\
    </select></td>';
  tr+='<td>Разделитель: <input type="text" id="tlevel_'+tlen+'_delimeter" onchange="change_level('+tlen+',\'delimeter\')"></td>';
  tr+='<td><a onclick="delete_level(\''+tlen+'\')">x</a> <a onclick="create_new_topology_level('+tlen+')">+</a></td></tr>';
  $("#new_topology_tbody").append(tr);
  */
  print_topology_levels();
}

function change_level(id,name){
  topology['levels'][id][name]=$("#tlevel_"+id+"_"+name).val();
}

function delete_level(id){
  $("#tr_"+id).remove();
  topology['levels'].splice(id,1);
  print_topology_levels();
}

function print_topology_levels(){
	var tr="";
	for(var tlen=0; tlen<topology['levels'].length; tlen++){
		tr+='<tr id="tr_'+tlen+'"><td>Наименование: <input onclick="this.select();" id="tlevel_'+tlen+'_name" onchange="change_level('+tlen+',\'name\')" value="';
		if(typeof(topology['levels'][tlen]['name'])!="undefined") tr+=topology['levels'][tlen]['name'];
		tr+='"></td>';
  		tr+='<td>Тип: \
    	<select class="" type="text" id="tlevel_'+tlen+'_type" onchange="change_level('+tlen+',\'type\')">\
		<option value="1"';
		if(typeof(topology['levels'][tlen]['type'])!="undefined" && topology['levels'][tlen]['type']==1) tr+=' selected="selected"';
		tr+='>Числовой</option>\
		<option value="2"';
		if(typeof(topology['levels'][tlen]['type'])!="undefined" && topology['levels'][tlen]['type']==2) tr+=' selected="selected"';
		tr+='>Алфавитный (лат.)</option>\
		<option value="3"';
		if(typeof(topology['levels'][tlen]['type'])!="undefined" && topology['levels'][tlen]['type']==3) tr+=' selected="selected"';
		tr+='>Алфавитный (рус.)</option>\
		</select></td>';
		tr+='<td>Начало: <input type="text" onclick="this.select();" size="3" id="tlevel_'+tlen+'_first" onchange="change_level('+tlen+',\'first\')" value="';
		if(typeof(topology['levels'][tlen]['first'])!="undefined") tr+=topology['levels'][tlen]['first'];
		tr+='"></td>';
		tr+='<td>Длина: <input type="text" onclick="this.select();" size="3" id="tlevel_'+tlen+'_len" onchange="change_level('+tlen+',\'len\')" value="';
		if(typeof(topology['levels'][tlen]['len'])!="undefined") tr+=topology['levels'][tlen]['len'];
		tr+='"></td>';
		tr+='<td>Разделитель: <input type="text" onclick="this.select();" id="tlevel_'+tlen+'_delimiter" onchange="change_level('+tlen+',\'delimiter\')" value="';
		if(typeof(topology['levels'][tlen]['delimiter'])!="undefined") tr+=topology['levels'][tlen]['delimiter'];
		tr+='"></td>';
		tr+='<td><a onclick="delete_level(\''+tlen+'\')" style="color:red;" title="удалить уровень">x</a> <a onclick="create_new_topology_level('+tlen+')" title="добавить после">+</a></td></tr>';
	}
  $("#new_topology_tbody").html(tr);
}

function get_sklad_topologys(){
  var send=[];
  var table='<table class="table table-hover"><thead><tr><th>Наименование</th><th>Уровни</th><th>Пример</th><th></th></tr></thead><tbody>';
  api_query_array("/api/index.php",send,"get_sklad_topologys").then(function(data){
    topologys=data.topologys;
    data.topologys.forEach(function(item,index){
      var lev_table="";
      var lev_prim_table="";
      if(typeof(item.topology_levels)!="undefined") {var lev_len=item.topology_levels.length;}
      else {var lev_len=0;}
      if(lev_len>0)
        item.topology_levels.forEach(function(lev_item,lev_ind){
          if(lev_ind<(lev_len-1)) {
            switch(lev_item.type){
              case "2": lev_prim_table+=" L "+lev_item.delimiter; break;
              case "3": lev_prim_table+=" Л "+lev_item.delimiter; break;
              default: lev_prim_table+=" 1 "+lev_item.delimiter;
            }
            lev_table+=" "+lev_item.name+" "+lev_item.delimiter;
          }
          else {
            switch(lev_item.type){
              case "2": lev_prim_table+=" L"; break;
              case "3": lev_prim_table+=" Л"; break;
              default: lev_prim_table+=" 1";
            }
            lev_table+=" "+lev_item.name;
          }
        });
      table+='<tr><td>'+item.name+'</td><td>'+lev_table+'</td><td>'+lev_prim_table+'</td><td><a onclick="get_sklad_topology('+item.id+')"><img src="/new_images/edit.svg" width="20px"></a></td></tr>';
    });
    table+="</tbody></table>";
    $("#sklad_topology_list").html(table);
  });

}

function get_sklad_topology(topology_id){
  var send=new Array();
  send['topology_id']=topology_id;
  api_query_array("/api/index.php",send,"get_sklad_topology").then(function(data){
    topology['name']=data.topology_name;
    topology['topology_id']=data.topology_id;
    topology['levels']=data.topology_levels;
    add_new_sklad_topology(data.topology_id);
  });
}

function edit_online_profile(profile_id){
  if(profile_id==0){
      print_online_profile(0,"");
  }
  else {
    if(profile_id>0){
      var send=new Array();
      send['profile_id']=profile_id;
      api_query_array("/api/index.php",send,"get_online_profile").then(function(data){
        if(data.status=="ok"){
          print_online_profile(data.profile_id,data.profile_name);
        }
      });
    }
  }
}

function delete_online_profile(profile_id){
  if(profile_id>0){
    var send=new Array();
    send['profile_id']=profile_id;
    api_query_array("/api/index.php",send,"delete_online_profile").then(function(data){
      if(data.status=="ok"){
        get_online_profiles();
      }
    });
  }
}

function print_online_profile(i,n){
  var table='<form id="online_profile"><table class="table"><thead><tr><th></th><th></th></tr></thead><tbody>';
  table+='<tr><td>Наименование</td><td><input type="text" name="name" id="nameProfile" class="form-control search_str" value="'+n+'"><label style="position: absolute; top: 5.1em; right: 1.2em;" for="nameProfile" id="nameProfile_label" onclick="clear_search_order_text(\'nameProfile\');"></label><input type="hidden" name="profile_id" value="'+i+'"></td></tr>';
  //table+='<tr><td>Использовать при поиске в магазине</td><td><input type="checkbox" name="use_in_shop" ></td></tr>';
  //table+='<tr><td>Использовать при поиске в Интернет магазине</td><td><input type="checkbox" name="use_in_internet_shop"></td></tr>';
  table+='<tr><td colspan="2"><button type="button" class="btn btn-success" onclick="save_profile();">Сохранить</button>\
  <button type="button" class="btn btn-default pull-right" onclick="close_window(\'new_online_profile\')">Отмена</button></td></tr>';
  table+='</tbody></table></form>';
  if(parseInt(i)>0) {
    var header="Редактирование профиля онлайн поиска";
  }
  else {
    var header="Создание профиля онлайн поиска";
  }
  create_window("new_online_profile_div",header,"new_online_profile",table);
}

function save_profile(){
  api_query("/api/index.php","online_profile","save_online_profile").then(function(data){
    if(data.status=="ok"){
      close_window('new_online_profile');
      get_online_profiles();
    }
  });
}

function get_online_profiles(){ 
  api_query("/api/index.php","some_form","get_online_profiles").then(function(data){
    var table='Выбранный профиль:<br><table class="table"><thead><tr></tr></thead><tbody>';
    var len=data.profiles.length;
    var active_my_profile_id=0;
    var active_internetshop_profile_id=0;
    var active_shop_profile_id=0;
      table+='<tr><td style="width: 20%;"><select class="form-control select-sm" name="selected_profile" id="selected_profile" onchange="get_profile_plugins(0,1)">';
      var all_selected=0;
      for(var i=0; i<len; i++){
        var selected=0;
        table+='<option value="'+data.profiles[i].id+'"';
        if(data.selected_profile_id==data.profiles[i].id){
          table+=' selected="selected">'+data.profiles[i].name+' - Мой профиль';
          active_my_profile_id=data.profiles[i].id;
          selected=1;
        }
        if(typeof(data.selected_profiles[1])!="undefined"){
          if(data.selected_profiles[1].profile_id==data.profiles[i].id){
            if(selected==0){
              table+=' >'+data.profiles[i].name+' - Интернет магазин';
            }
            else table+=' ,Интернет магазин';
            selected=1;
            active_internetshop_profile_id=data.profiles[i].id;
          }
          //else{
           // table+='>'+data.profiles[i].name+' ';
          //}
        }
        if(typeof(data.selected_profiles[2])!="undefined"){
          if(data.selected_profiles[2].profile_id==data.profiles[i].id){
            if(selected==0)
              table+=' >'+data.profiles[i].name+' - Розничный магазин';
            else  
              table+=' ,Розничный магазин';
            selected=1;
            active_shop_profile_id=data.profiles[i].id;
          }
        }
        if(!selected){
          table+='>'+data.profiles[i].name;
        }
        table+='</option>';
      }
      table+='</select></td><td align="left" style="vertical-align:middle" nowrap>';
      table+='<a onclick="edit_online_profile($(\'#plugins_form input[name=profile_id]\').val())" title="Редактировать профиль"><img src="/new_images/edit.svg" width="20px"></a>';
      table+=' <span id="delete_profile_button"></span>';
      table+='<input type="hidden" id="active_my_profile_id" value="'+active_my_profile_id+'"> <span id="active_my_profile"> активный профиль</span>';
      table+='<input type="hidden" id="active_internetshop_profile_id" value="'+active_internetshop_profile_id+'"> | <span id="active_internetshop_profile"> профиль интернет магазина</span>';
      table+='<input type="hidden" id="active_shop_profile_id" value="'+active_shop_profile_id+'"> | <span id="active_shop_profile"> профиль розничного магазина</span>';
      //table+=' <a onclick="get_profile_plugins('+data.profiles[i].id+',1)" title="Список поставщиков"><img src="/new_images/file.svg" width="20px"></a></td></tr>';
      table+='</td></tr>';
      //table+='<tr><td colspan="2" id="sort1_activation_list" style="display:none;"></td></tr>';
      table+='<tr><td colspan="2" id="profile_list"><br>\
      <form id="plugins_form" onsubmit="get_profile_plugins(0); return false;">\
      <input type="hidden" name="profile_id" value="">\
      <span class="pull-left">\
      <select name="type" class="form-control select-sm" onchange="get_profile_plugins(0);">\
        <option value="2">Легковые</option>\
        <option value="1">Грузовые</option>\
        <option value="0" selected="selected">Все</option>\
      </select>\
      </span>\
      <div class="input-group input-group-sm pull-right">\
          <input required type="text" class="form-control input-sm search_str" name="search" id="search_plugins_text" value="" onchange="get_profile_plugins(0);">\
          <label for="search_plugins_text" id="clear_search_plugins" onclick="clear_search_text_profile_plugins(\'search_plugins_text\');"></label>\
      	<div class="input-group-btn">\
      	    <button class="btn btn-default btn-sm" type="button" onclick="get_profile_plugins(0);">Поиск</button>\
      	</div>\
      </div>\
      </form>\
      <div id="sort1_list"></div>';
      table+='</td></tr>';
    
    table+='</tbody></table><script>get_profile_plugins(0);</script>';
    $("#online_profile_list").html(table);
  });
}

function get_company_profiles(){
  api_query("/api/index.php","some_form","get_company_online_profiles").then(function(data){
    if(data.status=="ok"){
      var len=data.profile_types.length;
      var table='<table class="table table-hover"><thead></thead><tbody>';
      data.profile_types.forEach(function(item){
        table+='<tr><td><input type="hidden" name="profile_type" value="'+item.id+'">'+item.name+'</td>';
        table+='<td><select name="'+item.id+'_profile_id" id="'+item.id+'_profile_id" class="form-control">';
        table+='<option value="0">Не выбрана</option>';
        data.config_profiles.forEach(function(pitem){
          if(typeof(data.company_profiles[item.id])!="undefined" && data.company_profiles[item.id].profile_id==pitem.id)
            table+='<option value="'+pitem.id+'" selected="selected">'+pitem.name+'</option>';
          else {
            table+='<option value="'+pitem.id+'">'+pitem.name+'</option>';
          }
        });
        table+='</select></td>';
        table+='<td><a onclick="save_company_online_profile('+item.id+')">сохранить</a></td>';
        //table+='<td><a onclick="delete_company_online_profile('+item.id+')">удалить</a></td>';
        table+="</tr>";
      });
      table+='</tbody></table>';
      $("#online_profiles_list").html(table);
    }
  });
}

function save_company_online_profile(profile_type,profile_id){
  var send=new Array();
  send['profile_type']=profile_type;
  if(typeof(profile_id)=="undefined")
    send['profile_id']=$("#"+profile_type+"_profile_id").val();
  else {
    send['profile_id']=profile_id;
  }
  api_query_array("/api/index.php",send,"save_company_online_profile").then(function(data){
    if(typeof(profile_id)=="undefined"){
      get_company_profiles();
      get_deliverers_list();
    }
    else {
      get_online_profiles();
    }
    
  });
}

function add_company_site(){
  var table='';
  table+='<form id="new_company_site_form" onsubmit="event.preventDefault(); save_company_site();">\
  Укажите имя вашего сайта (hostname), например my.site.name\
  <div class="input-group input-group-sm">\
        <input required type="text" class="form-control input-sm" name="site_name" id="site_name" value="">\
      <div class="input-group-btn">\
          <button class="btn btn-default btn-sm" type="button" onclick="save_company_site();">Сохранить</button>\
      </div>\
    </div>\
  </form>';
  create_window_centered_blue("new_company_site_div","Введите адрес вашего сайта, привязанного к данной организации","new_company_site",table);
}

function save_company_site(){
  api_query("/api/index.php","new_company_site_form","add_company_site").then(function(data){
    if(data.status=="ok"){
      $("#new_company_site").html('');
      get_company_sites();
    }
  });
}
///////
function add_marketplace_config(){
  api_query("/api/index.php","some_form","get_marketplaces").then(function(data){
    var marketplace_config = data.marketplace_config;

    var table='<div class="row">\
    <div class="col-sm-5" style="padding-top:7px;"><b>Маркетплейс: </b></div>\
    <div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="marketplaces" id="marketplaces" onchange="print_marketplace_config();">';

    for(var i=0; i<marketplace_config; i++){
      table+='<option value="'+i+'">'+marketplace_config[i].name+'</option>';
    }

    table+='</select></div></div>';
    table+='<div id="ofd_operator_config"></div>';//print_ofd_config();

    create_window_centered_blue("new_marketplace_config_div","Добавление новой кассы","new_marketplace_config",table);
    // setTimeout(print_marketplace_config(),10);
  })
}

function add_ofd_kassa(){
  var ofdoplen=ofds.length;
  var table='<div class="row">\
  <div class="col-sm-5" style="padding-top:7px;"><b>Тип подключения: </b></div>\
  <div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="ofd_operator" id="ofd_operator" onchange="print_ofd_config();">';
  for(var i=0; i<ofdoplen; i++){
    table+='<option value="'+i+'">'+ofds[i].name+'</option>';
  }
  table+='</select></div></div>';
  table+='<div id="ofd_operator_config"></div>';//print_ofd_config();
  create_window_centered_blue("new_ofd_kassa_div","Добавление новой кассы","new_ofd_kassa",table);
  setTimeout(print_ofd_config(),10);
}

function edit_ofd_kassa(ofd_kassa_index){
  var ofdoplen=ofds.length;
  var table='<div class="row"><div class="col-sm-5" style="padding-top:7px;"><b>Тип подключения: </b></div><div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="ofd_operator" id="ofd_operator" onchange="print_ofd_config();">';
  for(var i=0; i<ofdoplen; i++){
    table+='<option value="'+i+'"';
    if(ofds[i].id==ofd_kassas[ofd_kassa_index].ofd_operator_id) table+=' selected="selected"';
    table+='>'+ofds[i].name+'</option>';
  }
  table+='</select></div></div>';
  table+='<div id="ofd_operator_config"></div>';//print_ofd_config();
  create_window_centered_blue("new_ofd_kassa_div","Редактирование кассы","new_ofd_kassa",table);
  setTimeout(print_ofd_kassa_config(ofd_kassa_index),10);
}

function print_ofd_config(){
  var ofd_id=$("#ofd_operator").val();
  if(typeof(ofd_id)=="undefined") return 0;
  var table='<table class="table"><thead><th colspan="2">Конфигурация кассы</th></thead><tbody>';
  table+='<tr><td>Зарегистрирована в налоговой</td><td><input type="checkbox" id="registered_in_tax" name="registered_in_tax"';
  table+='></td></tr>';
  table+='<tr><td>Наименование</td><td><input type="hidden" name="ofd_kassa_id" id="ofd_kassa_id" value="0"><input class="form-control" type="text" name="ofd_config_name" id="ofd_config_name"></td></tr>';
  table+='<tr><td>Выберите склад:</td><td><select id="ofd_sklad_id" class="form-control">';
  var skladslen=sklads.length;
  for(j=0;j<skladslen;j++){
    table+='<option value="'+sklads[j].id+'">'+sklads[j].name+'</option>';
  }
  table+='</select></td></tr>';
  table+='<tr><td>Выберите кассира:</td><td><select id="ofd_user_id" class="form-control">';
  table+='<option value="0"';
    table+=' selected="selected"';
    table+='>Общая касса</option>';
  for(j in users){
    table+='<option value="'+users[j].id+'"';
    //if(users[j].id==ofd_kassas[kassa_index].user_id) table+=' selected="selected"';
    table+='>'+users[j].lastname+' '+users[j].name+'</option>';
  }
  table+='</select></td></tr>';
  var ofd_config_len=ofds[ofd_id].ofd_config.length;
  for (var i=0; i<ofd_config_len; i++){
    var conf=ofds[ofd_id].ofd_config[i];
    table+='<tr><td>'+conf.descr;
    if(typeof(conf.title)!="undefined") table+=' <a title="'+conf.title+'">?</a> ';
    table+=':</td><td>';
    switch(conf.type){
      case "boolean": 
        table+='<input type="checkbox" name="ofd_'+conf.name+'" id="ofd_'+conf.name+'"';
        if(conf.value) table+=' checked="checked"';
        table+='>';
        break;
      case "text": 
        table+='<input class="form-control" type="text" name="ofd_'+conf.name+'" id="ofd_'+conf.name+'" value="'+conf.value+'">';
        break;
      case "select_from": 
        table+='<input class="form-control" type="text" name="ofd_'+conf.name+'" id="ofd_'+conf.name+'" readonly value="'+conf.value+'" onclick="select_ofd_env('+ofds[ofd_id].id+',\''+conf.name+'\')"><div id="ofd_env_select_'+conf.name+'"></div>';
        break;
    }
    table+='</td></tr>';
  }
  table+='<tr><td><button class="btn btn-primary" onclick="save_ofd_kassa();">Сохранить</button></td><td><button class="btn btn-default pull-right" onclick="close_window(\'new_ofd_kassa_div\');">Отменить</button></td></tr>';
  table+='</tbody></table>';
  $("#ofd_operator_config").html(table);
  place_to_center("new_ofd_kassa_div");
}

function select_ofd_env(ofd_id,name){
  switch(ofd_id){
    case 3: // AQSI
      var send=[];
      send['ofd_id']=ofd_id;
      send['conf_name']=name;
      send['ofd_x_client_key']=$("#ofd_x_client_key").val();
      api_query_array("/api/index.php",send,"get_ofd_env").then(function(data){
        if(data.status=="ok"){
          switch(name){
            case "device": 
              var table='<table class="table table-hover">\
              <thead><tr><th>imei</th><th>Серийный номер</th></tr></thead>\
              <tbody>';
              if(typeof(data.res_str.devices)!="undefined")
                for(var i in data.res_str.devices){
                  table+='<tr onclick="set_ofd_env(\''+name+'\',\''+data.res_str.devices[i].id+'\')"><td>'+data.res_str.devices[i].imei+'</td><td>'+data.res_str.devices[i].deviceSn+'</td></tr>';
                }
              table+='</tbody></table>';
              create_window("ofd_env_select_"+name+"_div","Выберите кассу","ofd_env_select_"+name,table);
              break;
            case "cashier": 
              var table='<table class="table table-hover">\
              <thead><tr><th>ИНН</th><th>ФИО</th></tr></thead>\
              <tbody>';
              if(typeof(data.res_str)!="undefined")
                for(var i in data.res_str){
                  table+='<tr onclick="set_ofd_env(\''+name+'\',\''+data.res_str[i].id+'\')"><td>'+data.res_str[i].inn+'</td><td>'+data.res_str[i].name+'</td></tr>';
                }
              table+='</tbody></table>';
              create_window("ofd_env_select_"+name+"_div","Выберите кассира","ofd_env_select_"+name,table);
              break;
            case "shop": 
              var table='<table class="table table-hover">\
              <thead><tr><th>Наименование</th><th>Тип</th></tr></thead>\
              <tbody>';
              if(typeof(data.res_str)!="undefined")
                for(var i in data.res_str){
                  table+='<tr onclick="set_ofd_env(\''+name+'\',\''+data.res_str[i].id+'\')"><td>'+data.res_str[i].name+'</td><td>'+data.res_str[i].type.name+'</td></tr>';
                }
              table+='</tbody></table>';
              create_window("ofd_env_select_"+name+"_div","Выберите магазин","ofd_env_select_"+name,table);
              break;
          }
        }
      });
      break;
  }
}

function set_ofd_env(name,val){
  $("#ofd_"+name).val(val);
  $("#ofd_env_select_"+name).html('');
}

function print_ofd_kassa_config(kassa_index){
  var ofd_id=$("#ofd_operator").val();
  if(typeof(ofd_id)=="undefined") return 0;
  var table='<table class="table"><thead><th colspan="2">Конфигурация кассы</th></thead><tbody>';
  table+='<tr><td>Зарегистрирована в налоговой</td><td><input type="checkbox" id="registered_in_tax" name="registered_in_tax"';
  if(parseInt(ofd_kassas[kassa_index].registered_in_tax)===1) table+=' checked="checked"';
  table+='></td></tr>';
  table+='<tr><td>Наименование</td><td><input type="hidden" name="ofd_kassa_id" id="ofd_kassa_id" value="'+ofd_kassas[kassa_index].id+'">\
  <input class="form-control" type="text" name="ofd_config_name" id="ofd_config_name" value="'+ofd_kassas[kassa_index].config_name+'"></td></tr>';
  if(parseInt(ofd_id)==1) table+='<tr><td>ip-адрес ккм сервера <br>(https://192.168.0.2:5893)</td><td><input type="text" class="form-control" name="kassa_ip_port" id="kassa_ip_port" value="'+ofd_kassas[kassa_index].kassa_ip_port+'"></td></tr>';
  table+='<tr><td>Выберите склад:</td><td><select id="ofd_sklad_id" class="form-control">';
  var skladslen=sklads.length;
  for(j=0;j<skladslen;j++){
    table+='<option value="'+sklads[j].id+'"';
    if(sklads[j].id==ofd_kassas[kassa_index].sklad_id) table+=' selected="selected"';
    table+='>'+sklads[j].name+'</option>';
  }
  table+='</select></td></tr>';
  table+='<tr><td>Выберите кассира:</td><td><select id="ofd_user_id" class="form-control">';
  table+='<option value="0"';
    if("0"==ofd_kassas[kassa_index].user_id) table+=' selected="selected"';
    table+='>Общая касса</option>';
  for(j in users){
    table+='<option value="'+users[j].id+'"';
    if(users[j].id==ofd_kassas[kassa_index].user_id) table+=' selected="selected"';
    table+='>'+users[j].lastname+' '+users[j].name+'</option>';
  }
  table+='</select></td></tr>';
  var ofd_config_len=ofds[ofd_id].ofd_config.length;
  for (var i=0; i<ofd_config_len; i++){
    var conf=ofds[ofd_id].ofd_config[i];
    table+='<tr><td>'+conf.descr;
    if(typeof(conf.title)!="undefined") table+=' <a title="'+conf.title+'">?</a> ';
    table+=':</td><td>';
    switch(conf.type){
      case "boolean":
        table+='<input type="checkbox" name="ofd_'+conf.name+'" id="ofd_'+conf.name+'"';
        if(ofd_kassas[kassa_index].kassa_config[conf.name]) table+=' checked="checked"';
        table+='>';
        break;
      case "int":
      case "text":
        if(ofd_kassas[kassa_index].kassa_config[conf.name]=="")
          table+='<input class="form-control" type="text" name="ofd_'+conf.name+'" id="ofd_'+conf.name+'" value="'+conf.value+'">';
        else{
          if(typeof(ofd_kassas[kassa_index].kassa_config[conf.name])!="undefined")
            table+='<input class="form-control" type="text" name="ofd_'+conf.name+'" id="ofd_'+conf.name+'" value="'+ofd_kassas[kassa_index].kassa_config[conf.name]+'">';
          else 
            table+='<input class="form-control" type="text" name="ofd_'+conf.name+'" id="ofd_'+conf.name+'" value="">';
        }
        break;
      case "select_from": 
          table+='<input class="form-control" type="text" name="ofd_'+conf.name+'" id="ofd_'+conf.name+'" readonly onclick="select_ofd_env('+ofds[ofd_id].id+',\''+conf.name+'\')" value="'+ofd_kassas[kassa_index].kassa_config[conf.name]+'">\
          <div id="ofd_env_select_'+conf.name+'"></div>';
          break;
    }
    table+='</td></tr>';
  }
  table+='<tr><td><button class="btn btn-primary" onclick="save_ofd_kassa();">Сохранить</button></td><td><button class="btn btn-default pull-right" onclick="close_window(\'new_ofd_kassa_div\');">Отменить</button></td></tr>';
  table+='</tbody></table>';
  $("#ofd_operator_config").html(table);
  place_to_center("new_ofd_kassa_div");
}

var ofds=[];
var ofd_kassas=[];

function get_ofd_settings(){
  api_query("/api/index.php","some_form","get_ofds").then(function(data){
    ofds=data.ofds;
  });
}

function get_ofd_kassas(){
  api_query("/api/index.php","some_form","get_ofd_kassas").then(function(data){
    if(data.status=="ok"){
      ofd_kassas=data.kassas;
      users=data.users;
      var table='<table class="table table-hover"><thead><tr><th>Наименование</th><th>Тип подключения</th><th>Магазин</th><th>Кассир</th><th>Смена открыта</th><th></th></tr></thead><tbody>';
      if(typeof(data.kassas)!=undefined){
        var kassaslen=data.kassas.length;
        for(var i=0;i<kassaslen;i++){
          table+='<tr><td>'+data.kassas[i].config_name+'</td><td>'+data.kassas[i].ofd_operator_name+'</td><td>'+data.kassas[i].sklad_name+'</td><td>'+(data.kassas[i].user_id>0?(users[data.kassas[i].user_id].lastname+' '+users[data.kassas[i].user_id].name):"Общая касса")+'</td>';
          if(data.kassas[i].ofd_operator_id=="2"){
            if(parseInt(data.kassas[i].open_shift)===1) table+='<td><img src="/images/ok.svg" style="width:10px"></td>';
            else table+='<td><button onclick="OpenShiftGlobal('+i+');">открыть смену</button></td>';
          }
          else {
            table+='<td></td>';
          }
          table+='<td>';
          table+='<div class="btn-group" style="display: flex;">';
          table+='<a onclick="edit_ofd_kassa('+i+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a> &nbsp;&nbsp;';
          table+='<a title="Удалить кассу" onclick="bootbox.confirm(\'Вы точно хотите удалить кассу?\',function(result){ if(result) delete_ofd_kassa('+data.kassas[i].id+'); } );"><img src="/new_images/garbage.svg" style="width:20px;"></a>';
          if(data.kassas[i].ofd_operator_id=="2") 
            table+='&nbsp;&nbsp;<a onclick="show_kassa_menu('+i+')"><img src="/new_images/menu.svg" style="width:20px;"></a><div id="kassa_menu_'+data.kassas[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div>';
          table+='</div></td>';
          table+='</tr>';
        }
      }
      table+='</tbody></table>';
      $("#ofd_kassas_list").html(table);
    }
  });
}

function show_kassa_menu(index){
  var menu='';
  menu+='<div style="border: solid black 1px; width: 240px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
  menu+='<button type="button" class="close pull-right" onclick="$(\'#kassa_menu_'+ofd_kassas[index].id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
  menu+='<table class="table table-hover">';
  if(parseInt(ofd_kassas[index].registered_in_tax)===0) menu+='<tr><td><a onclick="KkmRegOfd('+index+')">Регистрация кассы</a></td></tr>';
  if(parseInt(ofd_kassas[index].open_shift)===0) menu+='<tr><td><a onclick="OpenShiftGlobal('+index+')">Открыть смену</a></td></tr>';
  menu+='<tr><td><a onclick="CloseShiftGlobal('+index+')">Закрыть смену</a></td></tr>';
  menu+='<tr><td><a onclick="GetDataKKT('+index+')">Состояние кассы</a></td></tr>';
  menu+='</table></div>';
  $("#kassa_menu_"+ofd_kassas[index].id).html(menu);
}

function OpenShiftGlobal(index){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Открываем смену...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query("/api/index.php","some_form","get_user_data").then(function(data){
    var config=ofd_kassas[index];
    if(parseInt(config.registered_in_tax)===0){
      bootbox.alert("Касса не зарегистрирована в налоговой, зарегистрируйте кассу чтобы фискализировать чеки");
      $.unblockUI(); 
      return 0;
    }
    if(parseInt(config.open_shift)===0) {
      if(config.kassa_config.CashierName=="" || config.kassa_config.CashierVATIN=="" || data.user[0].inn!=''){ //если у пользователя указан инн то его ставим кассиром или если не указан кассир по умолчанию в настройках кассы
        config.kassa_config.CashierName=data.user[0].lastname+' '+data.user[0].name;
        if(data.user[0].inn=="") {
          if(config.kassa_config.CashierVATIN==''){
            $.unblockUI(); 
            bootbox.alert("У вас не указан ИНН, укажите в настройках пользователя ИНН");
            return 0;
          }
        }
        else {
          config.kassa_config.CashierVATIN=data.user[0].inn;
        }
      }
      OpenShift(index);
    }
    else {
      bootbox.alert("Смена уже открыта");
      $.unblockUI(); 
      return 0;
    }

  });
  
}

function CloseShiftGlobal(index){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Закрываем смену...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query("/api/index.php","some_form","get_user_data").then(function(data){
    var config=ofd_kassas[index];
    if(parseInt(config.registered_in_tax)===0){
      $.unblockUI();
      bootbox.alert("Касса не зарегистрирована в налоговой, зарегистрируйте кассу чтобы фискализировать чеки");
      return 0;
    }
    if(parseInt(config.open_shift)===1) {
      if(config.kassa_config.CashierName=="" || config.kassa_config.CashierVATIN=="" || data.user[0].inn!=''){ //если у пользователя указан инн то его ставим кассиром или если не указан кассир по умолчанию в настройках кассы
        config.kassa_config.CashierName=data.user[0].lastname+' '+data.user[0].name;
        if(data.user[0].inn=="") {
          if(config.kassa_config.CashierVATIN==''){
            $.unblockUI();
            bootbox.alert("У вас не указан ИНН, укажите в настройках пользователя ИНН");
            return 0;
          }
        }
        else {
          config.kassa_config.CashierVATIN=data.user[0].inn;
        }
        config.kassa_config.CashierVATIN=data.user[0].inn;
      }
      CloseShift(index);
    }
    else {
      $.unblockUI();
      CloseShift(index);
      //bootbox.alert("Смена уже открыта");
      //return 0;
    }

  });
  
}

function save_openshift_data(index,Result){
  if(Result.Status==0){
    var send=[];
    send['ofd_kassa_id']=ofd_kassas[index].id;
    send['open_shift']=1;
    api_query_array("/api/index.php",send,"save_shift_data").then(function(data){
      $.unblockUI(); 
      bootbox.alert("Смена успешно открыта");
      $("#kassa_menu_"+ofd_kassas[index].id).html('');
      get_ofd_kassas();
    });
  }
  else {
    $.unblockUI(); 
    bootbox.alert("Ошибка:<br><pre>"+JSON.stringify(Result, null, 4)+"</pre>");
  }
}

function save_closeshift_data(index,Result){
  if(Result.Status==0){
    var send=[];
    send['ofd_kassa_id']=ofd_kassas[index].id;
    send['open_shift']=0;
    if(ofd_kassas[index].kassa_config.PayByProcessing) {
      Settlement(ofd_kassas[index].kassa_config.NumDeviceByProcessing);
    }
    api_query_array("/api/index.php",send,"save_shift_data").then(function(data){
      $.unblockUI(); 
      bootbox.alert("Смена успешно закрыта");
      $("#kassa_menu_"+ofd_kassas[index].id).html('');
      get_ofd_kassas();
    });
  }
  else {
    $.unblockUI(); 
    bootbox.alert("Ошибка:<br><pre>"+JSON.stringify(Result, null, 4)+"</pre>");
  }
}

function save_ofd_kassa(){
  var send={};
  send['ofd_id']=ofds[$("#ofd_operator").val()].id;
  send['ofd_kassa_id']=$("#ofd_kassa_id").val();
  send['config_name']=$("#ofd_config_name").val();
  send['sklad_id']=$("#ofd_sklad_id").val();
  send['user_id']=$("#ofd_user_id").val();
  if($("#registered_in_tax").prop('checked')) send['registered_in_tax']=1;
  else send['registered_in_tax']=0;
  send['kassa_ip_port']=$("#kassa_ip_port").val();
  send['ofd_config']={};
  var ofd_config_len=ofds[$("#ofd_operator").val()].ofd_config.length;
  for (var i=0; i<ofd_config_len; i++){
    var conf=ofds[$("#ofd_operator").val()].ofd_config[i];
    if(conf.type=="boolean") {
      if($('#ofd_'+conf.name).prop('checked')) send['ofd_config'][conf.name]=true;
      else send['ofd_config'][conf.name]=false;
    }
    else send['ofd_config'][conf.name]=$("#ofd_"+conf.name).val();
  }
  api_query_obj("/api/index.php",send,"save_ofd_kassa").then(function(data){
    if(data.status=="ok"){
      $("#new_ofd_kassa").html('');
      get_ofd_kassas();
    }
  });
}

function delete_ofd_kassa(kassa_id){
  var send=[];
  send['kassa_id']=kassa_id;
  api_query_array("/api/index.php",send,"delete_ofd_kassa").then(function(data){
    if(data.status=="ok") get_ofd_kassas();
  })
}

function get_roles_for_settings(){
  api_query("/api/index.php","some_form","get_roles").then(function(data){
    var len=data.roles.length;
    var select='<option value="">Не выбрана</option>';
    for(var i=0;i<len;i++){
      select+='<option value="'+data.roles[i].id+'">'+data.roles[i].name_rus+'</option>';
    }
    $('#selected_role_for_settings').html(select);
  });
}

function get_role_for_settings(){
  var send=new Array();
  send['role_id']=$("#selected_role_for_settings").val();
  api_query_array("/api/index.php",send,"get_role").then(function(data){
    //var len=data.roles.modules_rights.modules.length;
    var table='<h3>Настройки роли "'+data.roles.name_rus+'" </h3><form id="role_setting_'+data.roles.id+'"><input type="hidden" name="role_id" value="'+data.roles.id+'">';
    table+='<table class="table table-hover"><thead><tr><th>Наименование</th><th>Показать</th><th>Чтение</th><th>Запись</th><th>Удаление</th></tr></thead><tbody>';
    $.each(data.roles.modules_rights.modules,function(i,e){
      table+='<tr><td><b>'+e.name+'</b></td><td colspan="4"><span><input name="'+e.name+'[show]" type="checkbox"';
      if(e.show) table+=' checked="checked"';
      table+='></span></td></tr>';
      $.each(e.rights,function(index,element){
        table+='<tr><td style="padding-left:25px;">'+element.descr+'</td><td><span><input name="'+e.name+'['+index+'][show]" type="checkbox"';
        if(parseInt(element.show)) table+=' checked="checked"';
        table+='></span></td>';
        table+='<td><span><input  name="'+e.name+'['+index+'][read]" type="checkbox"';
        if(parseInt(element.read)) table+=' checked="checked"';
        table+='></span></td>';
        table+='<td><span><input name="'+e.name+'['+index+'][write]" type="checkbox"';
        if(parseInt(element.write)) table+=' checked="checked"';
        table+='></span></td>';
        table+='<td><span><input name="'+e.name+'['+index+'][delete]" type="checkbox"';
        if(parseInt(element.delete)) table+=' checked="checked"';
        table+='></span></td>';
        table+='</tr>';
      });
    });
    table+="</tbody></table></form>";
    table+='<button type="button" onclick="save_role_settings('+data.roles.id+')" class="btn btn-primary btn-sm">Сохранить</button>';
    $('#roles_settings_list').html(table);
  });
}

function save_role_settings(role_id){
  api_query("/api/index.php","role_setting_"+role_id,"save_role").then(function(data){
    if(data.status=="ok") bootbox.alert("Роль сохранена успешно");
  });
}

get_ofd_settings();
get_sklads();

function add_service_job(to_div="new_service_job"){
  $("#service_job_form_0").remove();
  var table="<div id='service_job_form_0'><table class='table'>";
    table+="<tr><td>Наименование работ: </td>";
    table+="<td><input type='text' class='form-control search_str' name='name' id='service_job_name' value=''><label style='position: absolute; top: 4.3em; right: 1.2em;' for='service_job_name' id='service_job_name_label' onclick='clear_search_order_text(\"service_job_name\");'></label></td></tr>";
    table+="<tr><td>Цена: </td><td><input type='text' class='form-control search_str' name='price' value='' id='service_job_price'><label style='position: absolute; top: 8.3em; right: 1.2em;' for='service_job_price' id='service_job_price_label' onclick='clear_search_order_text(\"service_job_price\");'></label>";
    table+="</td></tr>";
    table+="<tr><td>Код работ: </td><td><input type='text' class='form-control search_str' name='job_code' value='' id='service_job_job_code'><label style='position: absolute; top: 11.9em; right: 1.2em;' for='service_job_job_code' id='service_job_job_code_label' onclick='clear_search_order_text(\"service_job_job_code\");'></label></td></tr>";
    table+="<tr><td>Штрих-код: <span class='glyphicon glyphicon-question-sign' title='Можно задать штрих-код для быстрого добавления работ в заказ'></span></td><td><input type='text' class='form-control search_str' name='shtrih_code' value='' id='service_job_shtrih_code'><label style='position: absolute; top: 15.7em; right: 1.2em;' for='service_job_shtrih_code' id='service_job_shtrih_code_label' onclick='clear_search_order_text(\"service_job_shtrih_code\");'></label></td></tr>";
    table+="<tr><td>Работник: <span class='glyphicon glyphicon-question-sign' title='Работник по умолчанию, выполняющий данную работу'></span></td><td>";
    table+='<input type="hidden" name="default_employee" value="0" id="service_job_default_employee">';
    table+="<input type='text' onclick='select_default_employee(0)' class='form-control search_str' name='default_employee_name' value='Не назначен' id='service_job_default_employee_name'>\
    <label style='position: absolute; top: 19.5em; right: 1.2em;' for='service_job_default_employee_name' id='service_job_default_employee_name_label' onclick='clear_search_order_text(\"service_job_default_employee_name\");'></label>\
    <div id='select_service_employee_0' style='min-width:300px;'></div></td></tr>";    
    table+='<tr><td>Применять только в текущем сервисе</td><td><input type="checkbox" id="only_in_this_service" name="only_in_this_service"></td></tr>';
    table+="<tr><td><button type='button' class='btn btn-primary' class='form-control' id='save_service_job' onclick='f_save_service_job(0,\""+to_div+"\");'>Сохранить</button></td><td><button type='button' class='btn btn-default pull-right' class='form-control' id='cancel_service_job' onclick='$(\"#new_service_job\").html(\"\");'>Отменить</button></td></tr>";
    table+="</table></div>";
    create_window(to_div+"_div","Добавление новой сервисной работы",to_div,table);
}

function f_save_service_job(id,to_div="new_service_job"){
  var send=[];
  send['service_job_id']=$("input[name=service_job_id]").val();
  send['name']=$("#service_job_name").val();
  send['price']=$("#service_job_price").val();
  send['job_code']=$("#service_job_job_code").val();
  send['shtrih_code']=$("#service_job_shtrih_code").val();
  send['default_employee']=$("#service_job_default_employee").val();
  api_query_array("/api/index.php",send,"save_service_job").then(function(data){
    if(data.status=="ok"){
      $("#"+to_div).html('');
      if(to_div=="new_zakaz_job"){
        if(typeof($("[id^=zakaz_job_form] input[name=zakaz_id]").val())!="undefined"){
          //get_zakaz_job_list($("[id^=zakaz_job_form] input[name=zakaz_id]").val());
          $("#zakaz_job_list").html('');
          set_zakaz_job($("[id^=zakaz_job_form] input[name=zakaz_id]").val(),data.service_job_id,send['name'],send['price']);
        }
      }
      else{
        get_service_jobs();
      }
    }
  });
}

function get_service_jobs(){
  api_query("/api/index.php","search_service_jobs_form","get_service_jobs").then(function(data){
    var len=data.service_jobs.length;
    var table='<table class="table table-hover"><thead><tr><th>№</th><th>Наименование работ</th><th>Цена</th><th>код работ</th><th>штрих-код</th><th>работник</th><th>действия</th></tr></thead><tbody>';
    for(var i=0; i<len; i++){
      table+='<tr><td>'+(i+1)+'</td><td>'+data.service_jobs[i].name+'</td><td>'+data.service_jobs[i].price+'</td><td>'+data.service_jobs[i].job_code+'</td>';
      table+='<td>'+data.service_jobs[i].shtrih_code+'</td><td>';
      if(parseInt(data.service_jobs[i].default_employee)>0) table+=data.service_jobs[i].employee_lastname+' '+data.service_jobs[i].employee_name;
      else table+='не назначен';
      table+='</td>';
      table+='<td><div class="btn-group" style="display: flex;">\
       <a onclick="edit_service_job('+data.service_jobs[i].id+');" title="Редактировать работу">\<img src="/new_images/edit.svg" class="menuimg"></a>\
       <a title="Удалить работу" onclick="delete_service_job('+data.service_jobs[i].id+');"><img src="/new_images/garbage.svg" class="menuimg"></a>\
       </div></td>';
      table+='</tr>';
    }
    table+='</tbody></table>';
    $("#service_jobs_list").html(table);
  });
}

function delete_service_job(id){
  bootbox.confirm('Вы точно хотите удалить работу?',function(result){ 
    if(result) {
      var send=new Array();
      send['service_job_id']=id;
      api_query_array('/api/index.php',send,'delete_service_job').then(function(data){
        if(data.status=='ok') 
          get_service_jobs();
      });
    }
    });
}

function edit_service_job(id){
  var send=new Array();
  send['service_job_id']=id;
  api_query_array("/api/index.php",send,"get_service_job").then(function(data){
    var sj=data.service_job;
    var se=data.service_employees;
    var table="<form id='service_job_form_"+sj.id+"'><table class='table'>";
    table+="<input type='hidden' name='service_job_id' value='"+sj.id+"'>";
    table+="<tr><td>Наименование работ: </td>";
    table+="<td><input type='text' class='form-control search_str' name='name' id='service_job_name' value='"+sj.name+"'><label style='position: absolute; top: 4.3em; right: 1.2em;' for='service_job_name' id='service_job_name_label' onclick='clear_search_order_text(\"service_job_name\");'></label></td></tr>";
    table+="<tr><td>Цена: </td><td><input type='text' class='form-control search_str' name='price' value='"+sj.price+"' id='service_job_price'><label style='position: absolute; top: 8.3em; right: 1.2em;' for='service_job_price' id='service_job_price_label' onclick='clear_search_order_text(\"service_job_price\");'></label>";
    table+="</td></tr>";
    table+="<tr><td>Код работ: </td><td><input type='text' class='form-control search_str' name='job_code' value='"+sj.job_code+"' id='service_job_job_code'><label style='position: absolute; top: 11.9em; right: 1.2em;' for='service_job_job_code' id='service_job_job_code_label' onclick='clear_search_order_text(\"service_job_job_code\");'></label></td></tr>";
    table+="<tr><td>Штрих-код: <span class='glyphicon glyphicon-question-sign' title='Можно задать штрих-код для быстрого добавления работ в заказ'></span></td><td><input type='text' class='form-control search_str' name='shtrih_code' value='"+sj.shtrih_code+"' id='service_job_shtrih_code'><label style='position: absolute; top: 15.7em; right: 1.2em;' for='service_job_shtrih_code' id='service_job_shtrih_code_label' onclick='clear_search_order_text(\"service_job_shtrih_code\");'></label></td></tr>";
    table+="<tr><td>Работник: <span class='glyphicon glyphicon-question-sign' title='Работник по умолчанию, выполняющий данную работу'></span></td><td>";
    table+='<input type="hidden" name="default_employee" value="'+sj.default_employee+'">';
    if(typeof(se[sj.default_employee])!="undefined")
      table+="<input type='text' onclick='select_default_employee("+sj.id+")' class='form-control search_str' name='default_employee_name' value='"+se[sj.default_employee].lastname+" "+se[sj.default_employee].name+" "+se[sj.default_employee].surname+"'><label style='position: absolute; top: 19.5em; right: 1.2em;' for='minimum_markup' id='minimum_markup_label' onclick='clear_search_order_text(\"minimum_markup\");'></label><div id='select_service_employee_"+sj.id+"' style='min-width:300px;'></div></td></tr>";
    else
      table+="<input type='text' onclick='select_default_employee("+sj.id+")' class='form-control search_str' name='default_employee_name' value='Не назначен'><label style='position: absolute; top: 19.5em; right: 1.2em;' for='minimum_markup' id='minimum_markup_label' onclick='clear_search_order_text(\"minimum_markup\");'></label><div id='select_service_employee_"+sj.id+"' style='min-width:300px;'></div></td></tr>";
    table+='<tr><td>Применять только в текущем сервисе</td><td><input type="checkbox" id="only_in_this_service" name="only_in_this_service"';
    if(parseInt(sj.service_id)==parseInt($("#my_service").val()) && parseInt($("#my_service").val())>0) table+=' checked="checked"';
    table+='></td></tr>';
    table+="<tr><td><button type='button' class='btn btn-primary' class='form-control' id='save_service_job' onclick='f_save_service_job("+sj.id+");'>Сохранить</button></td><td><button type='button' class='btn btn-default pull-right' class='form-control' id='cancel_service_job' onclick='$(\"#new_service_job\").html(\"\");'>Отменить</button></td></tr>";
    table+="</table></form>";
    create_window_centered_blue("new_service_job_div","Редактирование сервисных работ","new_service_job",table);
  });
}

function select_default_employee(job_id){
  api_query("/api/index.php","some_form","get_service_employees").then(function(data){
    var len=data.service_employees.length;
    var table='<table class="table table-hover"><tbody>';
    table+='<tr><td><a onclick="set_default_employee('+job_id+',0,\'Не назначен\')">Не назначен</a></td></tr>';
    for (var i=0; i<len; i++){
      var se=data.service_employees[i];
      table+='<tr><td><a onclick="set_default_employee('+job_id+','+se.id+',\''+se.lastname+' '+se.name+' '+se.surname+'\')">'+se.lastname+' '+se.name+' '+se.surname+'</a></td></tr>';
    }
    table+='</tbody></table>';
    create_window("select_service_employee_"+job_id+"_div","Выберите работника","select_service_employee_"+job_id,table);
  });
}

function set_default_employee(job_id,empl_id,empl_name){
  $("#service_job_form_"+job_id+" input[name=default_employee]").val(empl_id);
  $("#service_job_form_"+job_id+" input[name=default_employee_name]").val(empl_name);
  $("#select_service_employee_"+job_id).html('');
}

function add_service_employee(){
  var table="<form id='service_employee_form_0'><table class='table'>";
    table+="<tr><td>Фамилия: </td>";
    table+="<td><input type='text' class='form-control search_str' name='lastname' id='service_employee_lastname' value=''></td></tr>";
    table+="<tr><td>Имя: </td><td><input type='text' class='form-control search_str' name='name' value='' id='service_employee_name'>";
    table+="</td></tr>";
    table+="<tr><td>Отчество: </td><td><input type='text' class='form-control search_str' name='surname' value='' id='service_employee_surname'></td></tr>";
    table+="<tr><td>Профессия: <span class='glyphicon glyphicon-question-sign'></span></td><td><input type='text' class='form-control search_str' name='profession' value='' id='service_employee_profession'></td></tr>";
    table+="<tr><td>Телефон: </td><td><input type='text' class='form-control search_str' name='service_employee_phone' value='' id='service_employee_phone'></td></tr>";
    table+="<tr><td>Примечание: </td><td><textarea class='form-control' name='service_employee_descr' value='' id='service_employee_descr'></textarea></td></tr>";
    table+="<tr><td></td><td><button type='button' class='btn btn-secondary' class='form-control' id='save_service_employee' onclick='f_save_service_employee(0);'>Сохранить</button></td></tr>";
    table+="</table></form>";
    create_window("new_service_employee_div","Добавление нового сервисного работника","new_service_employee",table);
}

function f_save_service_employee(id){
  api_query("/api/index.php","service_employee_form_"+id,"save_service_employee").then(function(data){
    if(data.status=="ok"){
      $("#new_service_employee").html('');
      get_service_employees();
    }
  });
}

function get_service_employees(){
  api_query("/api/index.php","search_service_employees_form","get_service_employees").then(function(data){
    var len=data.service_employees.length;
    var table='<table class="table table-hover"><thead><tr><th>№</th><th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Профессия</th><th>Тел</th><th>действия</th></tr></thead><tbody>';
    for(var i=0; i<len; i++){
      table+='<tr><td>'+(i+1)+'</td><td>'+data.service_employees[i].lastname+'</td><td>'+data.service_employees[i].name+'</td><td>'+data.service_employees[i].surname+'</td>';
      table+='<td>'+data.service_employees[i].profession+'</td><td>'+data.service_employees[i].phone+'</td>';
      table+='<td><div class="btn-group" style="display: flex;">\
       <a onclick="edit_service_employee('+data.service_employees[i].id+');" title="Редактировать работника">\<img src="/new_images/edit.svg" class="menuimg"></a>\
       <a title="Удалить работника" onclick="delete_service_employee('+data.service_employees[i].id+');"><img src="/new_images/garbage.svg" class="menuimg"></a>\
       </div></td>';
      table+='</tr>';
    }
    table+='</tbody></table>';
    $("#service_employees_list").html(table);
  });
}

function delete_service_employee(id){
  bootbox.confirm('Вы точно хотите удалить работника?',function(result){ 
    if(result) {
      var send=new Array();
      send['service_employee_id']=id;
      api_query_array('/api/index.php',send,'delete_service_employee').then(function(data){
        if(data.status=='ok') 
          get_service_employees();
      });
    }
    });
}

function edit_service_employee(id){
  var send=new Array();
  send['service_employee_id']=id;
  api_query_array("/api/index.php",send,"get_service_employee").then(function(data){
    var se=data.service_employee;
    var table="<form id='service_employee_form_"+se.id+"'><table class='table'>";
    table+="<input type='hidden' name='service_employee_id' value='"+se.id+"'>";
    table+="<tr><td>Фамилия: </td>";
    table+="<td><input type='text' class='form-control search_str' name='lastname' id='service_employee_lastname' value='"+se.lastname+"'><label style='position: absolute; top: 4.2em; right: 1.2em;' for='service_employee_lastname' id='service_employee_lastname_label' onclick='clear_search_order_text(\"service_employee_lastname\");'></label></td></tr>";
    table+="<tr><td>Имя: </td><td><input type='text' class='form-control search_str' name='name' value='"+se.name+"' id='service_employee_name'><label style='position: absolute; top: 8.5em; right: 1.2em;' for='service_employee_name' id='service_employee_name_label' onclick='clear_search_order_text(\"service_employee_name\");'></label>";
    table+="</td></tr>";
    table+="<tr><td>Отчество: </td><td><input type='text' class='form-control search_str' name='surname' value='"+se.surname+"' id='service_employee_surname'><label style='position: absolute; top: 12.8em; right: 1.2em;' for='service_employee_surname' id='service_employee_surname_label' onclick='clear_search_order_text(\"service_employee_surname\");'></label></td></tr>";
    table+="<tr><td>Профессия: <span class='glyphicon glyphicon-question-sign'></span></td><td><input type='text' class='form-control search_str' name='profession' value='"+se.profession+"' id='service_employee_profession'><label style='position: absolute; top: 16.9em; right: 1.2em;' for='service_employee_profession' id='service_employee_profession_label' onclick='clear_search_order_text(\"service_employee_profession\");'></label></td></tr>";
    table+="<tr><td>Телефон: </td><td><input type='text' class='form-control search_str' name='service_employee_phone' value='"+se.phone+"' id='service_employee_phone'></td></tr>";
    table+="<tr><td>Примечание: </td><td><textarea class='form-control' name='service_employee_descr' value='"+se.descr+"' id='service_employee_descr'></textarea></td></tr>";
    table+="<tr><td></td><td><button type='button' class='btn btn-secondary' class='form-control' id='save_service_employee' onclick='f_save_service_employee("+se.id+");'>Сохранить</button></td></tr>";
    table+="</table></form>";
    create_window("new_service_employee_div","Редактирование сервисного работника","new_service_employee",table);
  });
}

function f_save_service_workplace(id){
  api_query("/api/index.php","service_workplace_form_"+id,"save_service_workplace").then(function(data){
    if(data.status=="ok"){
      $("#new_service_workplace").html('');
      get_service_workplaces();
    }
  });
}

function get_service_workplaces(){
  if(parseInt($("#my_service").val())==0){
    bootbox.alert("Не выбран автосервис");
    return;
  }
  api_query("/api/index.php","search_service_workplaces_form","get_service_workplaces").then(function(data){
    var len=data.service_workplaces.length;
    var table='<table class="table table-hover"><thead><tr><th>№</th><th>Наименование рабочего места</th><th>штрих-код</th><th>Описание</th><th>действия</th></tr></thead><tbody>';
    for(var i=0; i<len; i++){
      table+='<tr><td>'+(i+1)+'</td><td>'+data.service_workplaces[i].name+'</td>';
      table+='<td>'+data.service_workplaces[i].shtrih_code+'</td>';
      table+='<td>'+data.service_workplaces[i].descr+'</td>';
      table+='<td><div class="btn-group" style="display: flex;">\
       <a onclick="edit_service_workplace('+data.service_workplaces[i].id+');" title="Редактировать рабочее место">\<img src="/new_images/edit.svg" class="menuimg"></a>\
       <a title="Удалить работу" onclick="delete_service_workplace('+data.service_workplaces[i].id+');"><img src="/new_images/garbage.svg" class="menuimg"></a>\
       </div></td>';
      table+='</tr>';
    }
    table+='</tbody></table>';
    $("#service_workplaces_list").html(table);
  });
}

function delete_service_workplace(id){
  bootbox.confirm('Вы точно хотите удалить рабочее место?',function(result){ 
    if(result) {
      var send=new Array();
      send['service_workplace_id']=id;
      api_query_array('/api/index.php',send,'delete_service_workplace').then(function(data){
        if(data.status=='ok') 
          get_service_workplaces();
      });
    }
    });
}

function add_service_workplace(){
  if(parseInt($("#my_service").val())==0){
    bootbox.alert("Не выбран автосервис");
    return;
  }
  var table="<form id='service_workplace_form_0'><table class='table'>";
    table+="<tr><td>Наименование рабочего места: </td>";
    table+="<td><input type='text' class='form-control search_str' name='name' id='service_workplace_name' value=''><label style='position: absolute; top: 4.2em; right: 1.2em;' for='service_workplace_name' id='service_workplace_name_label' onclick='clear_search_order_text(\"service_workplace_name\");'></label></td></tr>";
    table+="<tr><td>Штрих-код: <span class='glyphicon glyphicon-question-sign' title='Можно задать штрих-код для быстрого добавления рабочего места в заказ'></span></td><td><input type='text' class='form-control search_str' name='shtrih_code' value='' id='service_workplace_shtrih_code'><label style='position: absolute; top: 16.9em; right: 1.2em;' for='service_workplace_shtrih_code' id='service_workplace_shtrih_code_label' onclick='clear_search_order_text(\"service_workplace_shtrih_code\");'></label></td></tr>";
    table+="<tr><td>Описание: <span class='glyphicon glyphicon-question-sign' title='Добавьте расширенное описание рабочего места'></span></td><td><input type='text' class='form-control search_str' name='descr' value=''><label style='position: absolute; top: 21.0em; right: 1.2em;' for='workplace_descr' id='workplace_descr_label' onclick='clear_search_order_text(\"workplace_descr\");'></label></td></tr>";
    table+="<tr><td></td><td><button type='button' class='btn btn-secondary' class='form-control' id='save_service_workplace' onclick='f_save_service_workplace(0);'>Сохранить</button></td></tr>";
    table+="</table></form>";
    create_window("new_service_workplace_div","Добавление рабочего места","new_service_workplace",table);
}

function edit_service_workplace(id){
  var send=new Array();
  send['service_workplace_id']=id;
  api_query_array("/api/index.php",send,"get_service_workplace").then(function(data){
    var sj=data.service_workplace;
    var table="<form id='service_workplace_form_"+sj.id+"'><table class='table'>";
    table+="<input type='hidden' name='service_workplace_id' value='"+sj.id+"'>";
    table+="<tr><td>Наименование рабочего места: </td>";
    table+="<td><input type='text' class='form-control search_str' name='name' id='service_workplace_name' value='"+sj.name+"'><label style='position: absolute; top: 4.2em; right: 1.2em;' for='service_workplace_name' id='service_workplace_name_label' onclick='clear_search_order_text(\"service_workplace_name\");'></label></td></tr>";
    table+="<tr><td>Штрих-код: <span class='glyphicon glyphicon-question-sign' title='Можно задать штрих-код для быстрого добавления рабочего места в заказ'></span></td><td><input type='text' class='form-control search_str' name='shtrih_code' value='"+sj.shtrih_code+"' id='service_workplace_shtrih_code'><label style='position: absolute; top: 16.9em; right: 1.2em;' for='service_workplace_shtrih_code' id='service_workplace_shtrih_code_label' onclick='clear_search_order_text(\"service_workplace_shtrih_code\");'></label></td></tr>";
    table+="<tr><td>Описание: <span class='glyphicon glyphicon-question-sign' title='Полное описание рабочего места'></span></td><td><input type='text' class='form-control search_str' name='descr' value='"+sj.descr+"' id='service_workplace_descr'><label style='position: absolute; top: 16.9em; right: 1.2em;' for='service_workplace_descr' id='service_workplace_descr_label' onclick='clear_search_order_text(\"service_workplace_descr\");'></label></td></tr>";
    table+="<tr><td></td><td><button type='button' class='btn btn-secondary' class='form-control' id='save_service_workplace' onclick='f_save_service_workplace("+sj.id+");'>Сохранить</button></td></tr>";
    table+="</table></form>";
    create_window("new_service_workplace_div","Редактирование рабочего места","new_service_workplace",table);
  });
}

function edit_cross(local_cross_id,type="pref",oem_detail_id=0,oem_article="",oem_brand="",cross_name=""){ // types=["pref","doc"]
  var table='';
  if(local_cross_id>0){
    var send=[];
    send['local_cross_id']=local_cross_id;
    send['local_cross_type']=$("#select_local_cross_type").val();
    api_query_array("/api/index.php",send,"get_local_cross").then(function(data){
      var local_cross=data.local_crosses[0];
      //local_cross['oem_article']=date.oem['article'];
      //local_cross['oem_brand']=date.oem['brand'];
      local_cross['local_cross_id']=local_cross_id;
      table+=print_cross_edit(local_cross,type);
      create_window("new_my_cross_"+type+"_div","Изменение кросса","new_my_cross_"+type,table);
    });
    
  }
  else {
    var local_cross={
      "local_cross_id":0,
      "oem_detail_id":oem_detail_id,
      "cross_detail_id":0,
      "oem_article":oem_article,
      "oem_brand":oem_brand,
      "cross_article":"",
      "cross_brand":"",
      "cross_name":cross_name
    }
    table+=print_cross_edit(local_cross,type);
    create_window("new_my_cross_"+type+"_div","Изменение кросса","new_my_cross_"+type,table);
  }
  
}

function print_cross_edit(cross,type){
  var table='<form id="cross_edit_form_'+type+'">';
  table+='<input type="hidden" name="local_cross_id" value="'+cross['local_cross_id']+'">';
  table+='<input type="hidden" name="local_cross_type" value="'+$('#select_local_cross_type').val()+'">';
  table+='<input type="hidden" name="oem_detail_id" value="'+cross['oem_detail_id']+'">';
  table+='<input type="hidden" name="cross_detail_id" value="'+cross['cross_detail_id']+'">';
  //table+='<div class="row"><div class="col-sm-12"><table class="table"><thead></thead><tbody>';
  
  table+='<div class="row" style="padding:2px;"><div class="col-sm-4">Артикул OEM</div><div class="col-sm-8"><input type="text" name="oem_article" onchange="get_brands_my_cross_oem(\''+type+'\');" value="'+cross['oem_article']+'" class="form-control"><div id="oem_articles_list"></div></div></div>';
  table+='<div class="row" style="padding:2px;"><div class="col-sm-4">Бренд OEM</div><div class="col-sm-8"><input type="text" name="oem_brand" value="'+cross['oem_brand']+'" class="form-control"></div></div>';
  table+='<div class="row" style="padding:2px;"><div class="col-sm-4">Артикул кросса</div><div class="col-sm-8"><input type="text" name="cross_article" onchange="get_brands_my_cross_cross(\''+type+'\');"  value="'+cross['cross_article']+'" class="form-control"><div id="cross_articles_list_'+type+'"></div></div></div>';
  table+='<div class="row" style="padding:2px;"><div class="col-sm-4">Бренд кросса</div><div class="col-sm-8"><input type="text" name="cross_brand" value="'+cross['cross_brand']+'" class="form-control"></div></div>';
  table+='<div class="row" style="padding:2px;"><div class="col-sm-4">Наименование кросса</div><div class="col-sm-8"><input type="text" name="cross_name" value="'+cross['cross_name']+'" class="form-control"></div></div>';
  table+='</form>';
  table+='<div class="row" style="padding:5px;"><div class="col-sm-4"><button class="btn btn-primary btn-sm" type="button" onclick="save_cross(\''+type+'\');">Сохранить</button></div>\
  <div class="col-sm-4"><button class="btn btn-success btn-sm" type="button" onclick="save_cross_and_add(\''+type+'\');">Сохранить и добавить</button></div>\
  <div class="col-sm-4"><button class="btn btn-default pull-right btn-sm" type="button" onclick="$(\'#new_my_cross_'+type+'\').html(\'\');">Отмена</button></div></div>';
  return table;
}

function get_brands_my_cross_oem(type){
  var send=[];
  send['article']=$("#cross_edit_form_"+type+" input[name=oem_article]").val();
  api_query_array("/api/index.php",send,"get_brands_online").then(function(data){
    if(data.status=="ok"){
      var brands=new Array();
    	var table="<table class='table table-hover'><thead><th>артикул</th><th>наименование</th><th>брэнд</th></thead><tbody>";
      //table += '<tr style="cursor:pointer" onclick="set_brand_my_cross(0,0,\'\','+tab+'); $(\'#select_brands_'+tab+'\').html(\'\'); search('+tab+');"><td></td><td></td><td><b>Все бренды</b></td></tr>';
      var brands_count=data.brands.length;
    	$.each(data.brands, function(key,val){
    	    table+='<tr style="cursor:pointer" onclick="set_brand_my_cross('+val.brand_id+','+val.detail_id+',\''+val.brand+'\',\''+val.name+'\',\''+type+'\');"><td>'+val.article+'</td><td>'+val.name+'</td><td><b>'+val.brand+'</b></td></tr>';
    	});
      if(brands_count>0){
          create_window_gray("oem_articles_list_div_"+type,"Выберите брэнд",'oem_articles_list_'+type,table);
      }
      else {
        $("#cross_edit_form_"+type+" input[name=oem_detail_id]").val(0);
      }
    }
  });
}

function get_brands_my_cross_cross(type){
  var send=[];
  send['article']=$("#cross_edit_form_"+type+" input[name=cross_article]").val();
  api_query_array("/api/index.php",send,"get_brands_online").then(function(data){
    if(data.status=="ok"){
      var brands=new Array();
    	var table="<table class='table table-hover'><thead><th>артикул</th><th>наименование</th><th>брэнд</th></thead><tbody>";
      //table += '<tr style="cursor:pointer" onclick="set_brand_my_cross(0,0,\'\','+tab+'); $(\'#select_brands_'+tab+'\').html(\'\'); search('+tab+');"><td></td><td></td><td><b>Все бренды</b></td></tr>';
      var brands_count=data.brands.length;
    	$.each(data.brands, function(key,val){
    	    table+='<tr style="cursor:pointer" onclick="set_brand_my_cross1('+val.brand_id+','+val.detail_id+',\''+val.brand+'\',\''+val.name+'\',\''+type+'\');"><td>'+val.article+'</td><td>'+val.name+'</td><td><b>'+val.brand+'</b></td></tr>';
    	});
      if(brands_count>0){
          create_window_gray("cross_articles_list_div_"+type,"Выберите брэнд",'cross_articles_list_'+type,table);
      }
      else {
        $("#cross_edit_form_"+type+" input[name=cross_detail_id]").val(0);
      }
    }
  });
}

function set_brand_my_cross(brand_id,detail_id,brand,name,type){
  if(parseInt(brand_id)>0 && parseInt(detail_id)!=0){
      $("#cross_edit_form_"+type+" input[name=oem_brand]").val(brand);
      $("#cross_edit_form_"+type+" input[name=oem_detail_id]").val(detail_id);
      $("#cross_edit_form_"+type+" input[name=cross_name]").val(name);
      $("#oem_articles_list_"+type).html('');
  }
}

function set_brand_my_cross1(brand_id,detail_id,brand,name,type){
  if(parseInt(brand_id)>0 && parseInt(detail_id)!=0){
      $("#cross_edit_form_"+type+" input[name=cross_brand]").val(brand);
      $("#cross_edit_form_"+type+" input[name=cross_detail_id]").val(detail_id);
      $("#cross_edit_form_"+type+" input[name=cross_name]").val(name);
      $("#cross_articles_list_"+type).html('');
  }
}

function save_cross(type){
  api_query("/api/index.php","cross_edit_form_"+type,"save_local_cross").then(function(data){
    if(data.status=="ok"){
      $('#new_my_cross_'+type).html('');
      get_my_crosses("my_crosses_form");
    }
  });
}

function save_cross_and_add(type){
  var oem_art=$("#cross_edit_form_"+type+" input[name=oem_article]").val();
  var oem_brand=$("#cross_edit_form_"+type+" input[name=oem_brand]").val();
  api_query("/api/index.php","cross_edit_form_"+type,"save_local_cross").then(function(data){
    if(data.status=="ok"){
      $('#new_my_cross_'+type).html('');
      get_my_crosses("my_crosses_form");
      edit_cross(0,type="pref",0,oem_art,oem_brand,"");
    }
  });
}

function save_cross_direct(oem_art,oem_brand,cross_art,cross_brand,cross_name, local_cross_type="white"){
  var send=[];
  send['oem_article']=oem_art;
  send['oem_brand']=oem_brand;
  send['cross_article']=cross_art;
  send['cross_brand']=cross_brand;
  send['cross_name']=cross_name;
  send['local_cross_type']=local_cross_type;
  api_query_array("/api/index.php",send,"save_local_cross").then(function(data){
    if(data.status=="ok"){
      //$('#new_my_cross').html('');
      //get_my_crosses("my_crosses_form");
    }
  });
}

function get_my_crosses(local_cross_form){
  var type=$("#select_local_cross_type").val();
  if(typeof(type)=="undefined") type="white";
  $("#local_cross_type").val(type);
  api_query("/api/index.php",local_cross_form,"get_local_crosses").then(function(data){
     if(parseInt(data.local_crosses_pages)<parseInt($("#"+local_cross_form+" [name=page]").val())){
         $("#"+local_cross_form+" [name=page]").val(1);
         get_my_crosses(local_cross_form);
         return 0;
     }
     var datalen=data.local_crosses.length;
     var table='<div class="row" style="padding:5px;"><div class="col-xs-3">\
     <select id="select_local_cross_type" name="local_cross_type" class="form-control" onchange="get_my_crosses(\''+local_cross_form+'\');">\
     <option value="white" '+(type=="white"?' selected':'')+'>Белый список</option>\
     <option value="black" '+(type=="black"?' selected':'')+'>Черный список</option>\
     </select></div>\
     <div class="col-xs-2"><button class="btn btn-primary btn-sm" onclick="edit_cross(0,\'pref\')\">Добавить кросс</button></div><div id="new_my_cross">\
     </div>';
     table += '<div class="col-xs-2"><span class="btn btn-success fileinput-button btn-sm">\
         <span>Загрузить файл</span>\
         <input id="excel_reader_load_cross" onchange="excel_reader_cross_obj.handleFileSelect(event,\'cross\')" onclick="$(\'#excel_reader_load_cross\').val(\'\');" class="btn btn-sm btn-primary excel_reader_load" data-filename-placement="inside" type="file" title="Открыть файл">\
     </span><div id="excel_reader_result_list_cross"></div></div>';
     table+='<div class="col-xs-5 pull-right">\
     <form id="my_crosses_form" onsubmit="event.preventDefault();" class="pull-right">\
     <input type="hidden" name="page" value="1">\
     <input type="hidden" name="local_cross_type" id="local_cross_type" value="'+type+'">';
     table += "<div class='input-group input-group-sm'>";
     
     table += "<span id='local_cross_search'><input type='text' class='form-control input-sm' name='search'";
     if (data.hasOwnProperty('search')) table+="value='"+data.search+"'";
     else table+="value=''";
     table += " onchange='get_my_crosses(\""+local_cross_form+"\")'></span>";
     table += "<span class='input-group-btn'><button class='btn btn-default btn-sm' type='button' onclick='$(\"#"+local_cross_form+" [name=page]\").val(1);get_my_crosses(\""+local_cross_form+"\")'>Поиск</button></span></div></div>";
     table += "</form></div>";//<div id='new_my_cross'></div><div id='select_price_cols_"+data.price_list_id+"'></div>";
     table += '<div id="progress" class="progress col-sm-9"  style="display:none;">\
         <div class="progress-bar progress-bar-success"></div>\
     </div>';
     table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
     var x=0,y=0,xx=0,yy=0;
     if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
     else var selected_page=1;
     for (var i=1; i<=data.local_crosses_pages; i++){
   if(i>(selected_page+6) && i<(data.local_crosses_pages-1)){
       x=1;
   }
   else x=0;
   if (i<(selected_page-6) && i!=1){
       y=1;
   }
   else y=0;
   if (x==1) {
     if (xx==0){
         table += '<li';
         table += '><a onclick="$(\'#'+local_cross_form+' input[name=page]\').val(\''+i+'\');';
         //if($('#local_cross_search [name=search]').val()!="") table += '$(\'#'+local_cross_form+' input[name=search]\').val(\''+$('#price_list_search_'+data.price_list_id+' [name=search]').val()+'\');';
         table += 'get_my_crosses(\''+local_cross_form+'\')">...</a></li>';
     }
     if (x==1) xx++;
   }
   else {
       if (y==1) {
     if (yy==0){
         table += '<li';
         table += '><a onclick="$(\'#'+local_cross_form+' input[name=page]\').val(\''+i+'\');';
         //if($('#price_list_search_'+data.price_list_id+' [name=search]').val()!="") table += '$(\'#'+price_list_form+' input[name=search]\').val(\''+$('#price_list_search_'+data.price_list_id+' [name=search]').val()+'\');';
         table += 'get_my_crosses(\''+local_cross_form+'\')">...</a></li>';
     }
     if (y==1) yy++;
       }
       else {
     table += '<li';
     if(selected_page==i) table+= " class='active'";
     table += '><a onclick="$(\'#'+local_cross_form+' input[name=page]\').val(\''+i+'\');';
     //if($('#price_list_search_'+data.price_list_id+' [name=search]').val()!="") table += '$(\'#'+price_list_form+' input[name=search]\').val(\''+$('#price_list_search_'+data.price_list_id+' [name=search]').val()+'\');';
     table += 'get_my_crosses(\''+local_cross_form+'\')">'+i+'</a></li>';
       }
   }
     }
     table += '</ul></div>';
     table += "<table class=\"table table-hover\"><thead><tr><th>№</th><th>OEM Артикул</th><th>OEM Брэнд</th><th>Cross Article</th><th>Cross Brand</th><th>Cross Name</th><th></th></tr></thead><tbody>";
     for (var i=0; i<datalen; i++){
      table += "<tr><td><div id='edit_cross_"+data.local_crosses[i].oem_detail_id+"'></div>"+(i+1)+"</td><td>" + data.local_crosses[i].oem_article + "</td>";
      table += "<td>"+data.local_crosses[i].oem_brand+"</td><td>"+data.local_crosses[i].cross_article+"</td><td>"+data.local_crosses[i].cross_brand+"</td>";
      table += "<td>"+data.local_crosses[i].cross_name+"</td>";
      table += "<td><form id='delete_local_cross_"+data.local_crosses[i].id+"'><input type=\"hidden\" name=\"local_cross_id\" value=\""+data.local_crosses[i].id+"\"></form>";
      table += "<div class='btn-group' style='display: flex;'><button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\"  onclick=\"edit_cross("+data.local_crosses[i].id+",'pref');\"></button>";
      table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
      table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить кросс?\',function(result){ if(result) delete_cross("+data.local_crosses[i].id+");});\"></button>";
      table += "</div></td>";
      table += "</tr>";
     }
     table += "</tbody></table>";
     table+="\
     <script>\
   file_uploader(1);\
     </script>";
     $("#my_crosses_list").html(table);
  });
 }

 function load_crosses_to_base(api,tab){ 
  var send=[];
  send['local_crosses']=api;
  api_query_array("/api/index.php",send,"save_local_crosses").then(function(data){
    
  });
 }

 function delete_cross(local_cross_id){
   var send=[];
   var type=$("#select_local_cross_type").val();
    if(typeof(type)=="undefined") type="white";
    $("#local_cross_type").val(type);
   send['local_cross_id']=local_cross_id;
   send['local_cross_type']=type;
   api_query_array("/api/index.php",send,"delete_local_cross").then(function(data){
    if(data.status="ok"){
      get_my_crosses("my_crosses_form");
    }
   });
 }

 function get_detail_groups(group_id=0,glubina=0,force=0){
   if($("#det_group_znak_"+group_id).html()=="-" && !force){
     $("#detail_groups_list_"+group_id).html('');
     $("#det_group_znak_"+group_id).html('+');
     return;
   }
  var send=[];
  send['in_group']=group_id;
   api_query_array("/api/index.php",send,"get_detail_groups").then(function(data){
    if(data.status=="ok"){
      var len=data.detail_groups.length;
      glubina++;
      var table='<table class="detail_group_table" border="0" style="font-size: 16px;"><thead><tr><th></th><th></th></tr></thead><tbody>';
      if(glubina>1) table+='<tr><td><button class="btn btn-xs btn-primary" onclick="edit_detail_group('+group_id+',0);">добавить</button></td><td> </td></tr>';
      if(glubina==1){
        table+="<tr><td><div id='detail_group_details_from_sklad_0'></div>Неопределенные группы для товаров со склада</td><td><a onclick=\"get_detail_group_details_from_sklad_binding()\" title='Просмотреть список'><img src='/new_images/file.svg' style='width: 20px;'></a></td></tr>";
        table+="<tr><td><div id='detail_group_details_from_price_list_0'></div>Неопределенные группы для товаров с прайс листов</td><td><a onclick=\"get_detail_group_details_from_price_list_binding()\" title='Просмотреть список'><img src='/new_images/file.svg' style='width: 20px;'></a></td></tr>";
      }
      for (let i = 0; i < len; i++) {
        table += '<tr id="detail_group_row_' + data.detail_groups[i].id + '" ondblclick="get_detail_groups(' + data.detail_groups[i].id + ',' + (glubina) + ');">\
          <td>\
            <div id="detail_group_details_' + data.detail_groups[i].id + '"></div>\
            <div id="detail_group_details_from_sklad_' + data.detail_groups[i].id + '"></div>\
            <a onclick="get_detail_groups(' + data.detail_groups[i].id + ',' + (glubina) + ');" id="det_group_znak_' + data.detail_groups[i].id + '">+</a> ' + data.detail_groups[i].group_name + ' - ' + data.detail_groups[i].markup + '%\
            <div id="detail_groups_list_' + data.detail_groups[i].id + '" style="padding-left:' + ((glubina + 1) * 10) + 'px;"></div>\
          </td>\
          <td style="vertical-align: top;"> \
            <a onclick="move_group_up(' + data.detail_groups[i].id + ');">▲</a>\
            <a onclick="move_group_down(' + data.detail_groups[i].id + ');">▼</a>\
            <a style="color:red" onclick="edit_detail_group(' + group_id + ',' + data.detail_groups[i].id + ',\'' + data.detail_groups[i].group_name + '\',' + data.detail_groups[i].markup + ');"><img src="/new_images/edit.svg" style="width: 20px;"></a>';
        table += " <a onclick=\"get_detail_group_details(" + data.detail_groups[i].id + "," + data.detail_groups[i].status + ")\" title='Просмотреть список'><img src='/new_images/file.svg' style='width: 20px;'></a>";
        table += '  <a style="color:red" onclick="delete_detail_group(' + data.detail_groups[i].id + ');"><img src="/new_images/garbage.svg" style="width: 20px;"></a>\
          </td>\
        </tr>';
      }
      
      table+='</tbody></table>';
      document.getElementById("detail_groups_list_"+group_id).innerHTML=table;
      $("#det_group_znak_"+group_id).html('-');
    }
    else {
      document.getElementById("detail_groups_list_"+group_id).innerHTML='Группы еще не заведены';
    }
   });
 }

 function get_detail_group_details_from_sklad_binding(){
  if ($('#detail_group_details_from_sklad_0').children().length === 0) {
    selectedDetailsGroup = [];
  }

  var send=new Array();
  send['search_article']=$("#detail_group_search_article_from_sklad_new").val();
  //send['search_ean']=$("#detail_group_search_ean").val();
  send['search_name']=$("#detail_group_search_name_from_sklad_new").val();
  send['show_zero']=$("#detail_group_show_zero_from_sklad_new").prop("checked");
  send['selected_page']=$("#detail_group_selected_page_from_sklad_new").val();
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Пожалуйста подождите...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  }); 
  api_query_array("/api/index.php",send,"get_detail_group_details_from_sklad_binding").then(function(data){
    if(data.status=="ok"){
      var table='<div class="row">\
      <div class="col-sm-3">';
      //table+='<button type="button" class="btn btn-sm btn-primary" onclcik="get_detail_group_details_from_sklad('+detail_group_id+','+detail_group_status+')">Добавить деталь</button>';
      table+='</div>\
      <div class="col-sm-9">\
      <div class="input-group input-group-sm pull-right">\
        <div id="sel_undefined_detail_groups_list_0"></div><span class="input-group-addon" style = "cursor: pointer; background: #337ab7; color: white" onclick="select_detail_groups_undefined_details(0,0,0,\'sklad\')">Привязать</span>\
        <span class="input-group-addon"><input type="checkbox" name="show_zero" id="detail_group_show_zero_from_sklad"';
        if(typeof(data.show_zero)!="undefined" && data.show_zero) table+=' checked';
        table+='>Показать 0 остатки</span>\
        <label for="detail_group_search_article" class="input-group-addon">Артикул:</label>\
        <input class="form-control" type="text" name="search_article" id="detail_group_search_article_from_sklad_new" onchange="get_detail_group_details_from_sklad_binding();" value="';
        if(typeof(send['search_article'])!="undefined") table+=send['search_article'];
        table+='">\
        <label for="detail_group_search_name" class="input-group-addon">Наименование:</label>\
        <input class="form-control" type="text" name="search_name" id="detail_group_search_name_from_sklad_new" onchange="get_detail_group_details_from_sklad_binding();" value="';
        if(typeof(send['search_name'])!="undefined") table+=send['search_name'];
        table+='">\
        <input type="hidden" name="detail_group_selected_page" id="detail_group_selected_page_from_sklad_new" ';
        if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
        else var selected_page=1;
        table+=' value="'+selected_page+'"';
        table+='>\
      </div>\
      </div>\
      </div>';

      table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
      var x=0,y=0,xx=0,yy=0;
      
      for (var i=1; i<=data.detail_group_pages; i++){
        if(i>(selected_page+6) && i<(data.detail_group_pages-1)){
            x=1;
        }
        else x=0;
        if (i<(selected_page-6) && i!=1){
            y=1;
        }
        else y=0;
        if (x==1) {
          if (xx==0){
              table += '<li';
              table += '><a href="#" onclick="$(\'#detail_group_selected_page_from_sklad_new\').val(\''+i+'\');';
              //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_detail_group_details_from_sklad_binding()">...</a></li>';
          }
          if (x==1) xx++;
        }
        else {
            if (y==1) {
          if (yy==0){
              table += '<li';
              table += '><a href="#" onclick="$(\'#detail_group_selected_page_from_sklad_new\').val(\''+i+'\');';
              //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_detail_group_details_from_sklad_binding()">...</a></li>';
          }
          if (y==1) yy++;
            }
            else {
          table += '<li';
          if(selected_page==i) table+= " class='active'";
          table += '><a href="#" onclick="$(\'#detail_group_selected_page_from_sklad_new\').val(\''+i+'\');';
          //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
          table += 'get_detail_group_details_from_sklad_binding()">'+i+'</a></li>';
            }
        }
      }
      table += '</ul></div>';

      table+='<div style="height: 73vh; overflow:auto;">\
      <table class="table table-hover">\
      <thead>\
      <tr><th><input type="checkbox" id="detail_group_checkbox_all_from_sklad_new" onclick="check_all_detail_group_details_from_sklad_binding();"></th><th>№</th><th>артикул</th><th>наименование</th><th>бренд</th></tr>\
      </thead><tbody>';
      Object.keys(data.detail_group_details).forEach(function(key, index) {
        var detail = data.detail_group_details[key];
        var detailId = detail.detail_id;
        table += '<tr><td>';
        table += '<input type="checkbox" id="detail_group_checkbox_from_sklad_' + detailId + '_new" onchange="handle_checkbox_change_add_detail_groups(' + detailId + ')"';
        if (selectedDetailsGroup.includes(parseInt(detailId))) {
            table += ' checked';
        }
        table += '></td>';
        table += '<td>' + (index + 1) + '</td><td>' + detail.article + '</td><td>' + detail.name + '</td><td>' + detail.brand + '</td></tr>';
      });
      table+='</tbody></table><div>';
      $.unblockUI();
      create_window_centered_blue("detail_group_details_from_sklad_0_div_new","Детали со склада","detail_group_details_from_sklad_0",table);
    }
  }, function(data){
    $.unblockUI();
  })
} 

function get_detail_group_details_from_price_list_binding(){
  if ($('#detail_group_details_from_price_list_0').children().length === 0) {
    selectedDetailsGroup = [];
  }

  var send=new Array();
  send['search_article']=$("#detail_group_search_article_from_price_list_new").val();
  //send['search_ean']=$("#detail_group_search_ean").val();
  send['search_name']=$("#detail_group_search_name_from_price_list_new").val();
  send['show_zero']=$("#detail_group_show_zero_from_price_list_new").prop("checked");
  send['selected_page']=$("#detail_group_selected_page_from_price_list_new").val();
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Пожалуйста подождите...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  }); 
  api_query_array("/api/index.php",send,"get_detail_group_details_from_price_list_binding").then(function(data){
    if(data.status=="ok"){
      var table='<div class="row">\
      <div class="col-sm-3">';
      //table+='<button type="button" class="btn btn-sm btn-primary" onclcik="get_detail_group_details_from_sklad('+detail_group_id+','+detail_group_status+')">Добавить деталь</button>';
      table+='</div>\
      <div class="col-sm-9">\
      <div class="input-group input-group-sm pull-right">\
        <div id="sel_undefined_detail_groups_list_0"></div><span class="input-group-addon" style = "cursor: pointer; background: #337ab7; color: white" onclick="select_detail_groups_undefined_details(0,0,0,\'price_list\')">Привязать</span>\
        <span class="input-group-addon"><input type="checkbox" name="show_zero" id="detail_group_show_zero_from_price_list"';
        if(typeof(data.show_zero)!="undefined" && data.show_zero) table+=' checked';
        table+='>Показать 0 остатки</span>\
        <label for="detail_group_search_article" class="input-group-addon">Артикул:</label>\
        <input class="form-control" type="text" name="search_article" id="detail_group_search_article_from_price_list_new" onchange="get_detail_group_details_from_price_list_binding();" value="';
        if(typeof(send['search_article'])!="undefined") table+=send['search_article'];
        table+='">\
        <label for="detail_group_search_name" class="input-group-addon">Наименование:</label>\
        <input class="form-control" type="text" name="search_name" id="detail_group_search_name_from_price_list_new" onchange="get_detail_group_details_from_price_list_binding();" value="';
        if(typeof(send['search_name'])!="undefined") table+=send['search_name'];
        table+='">\
        <input type="hidden" name="detail_group_selected_page" id="detail_group_selected_page_from_price_list_new" ';
        if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
        else var selected_page=1;
        table+=' value="'+selected_page+'"';
        table+='>\
      </div>\
      </div>\
      </div>';

      table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
      var x=0,y=0,xx=0,yy=0;
      
      for (var i=1; i<=data.detail_group_pages; i++){
        if(i>(selected_page+6) && i<(data.detail_group_pages-1)){
            x=1;
        }
        else x=0;
        if (i<(selected_page-6) && i!=1){
            y=1;
        }
        else y=0;
        if (x==1) {
          if (xx==0){
              table += '<li';
              table += '><a href="#" onclick="$(\'#detail_group_selected_page_from_price_list_new\').val(\''+i+'\');';
              //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_detail_group_details_from_price_list_binding()">...</a></li>';
          }
          if (x==1) xx++;
        }
        else {
            if (y==1) {
          if (yy==0){
              table += '<li';
              table += '><a href="#" onclick="$(\'#detail_group_selected_page_from_price_list_new\').val(\''+i+'\');';
              //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_detail_group_details_from_price_list_binding()">...</a></li>';
          }
          if (y==1) yy++;
            }
            else {
          table += '<li';
          if(selected_page==i) table+= " class='active'";
          table += '><a href="#" onclick="$(\'#detail_group_selected_page_from_price_list_new\').val(\''+i+'\');';
          //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
          table += 'get_detail_group_details_from_price_list_binding()">'+i+'</a></li>';
            }
        }
      }
      table += '</ul></div>';

      table+='<div style="height: 73vh; overflow:auto;">\
      <table class="table table-hover">\
      <thead>\
      <tr><th><input type="checkbox" id="detail_group_checkbox_all_from_price_list_new" onclick="check_all_detail_group_details_from_price_list_binding();"></th><th>№</th><th>артикул</th><th>наименование</th><th>бренд</th></tr>\
      </thead><tbody>';
      Object.keys(data.detail_group_details).forEach(function(key, index) {
        var detail = data.detail_group_details[key];
        var detailId = detail.detail_id;
        table += '<tr><td>';
        table += '<input type="checkbox" id="detail_group_checkbox_from_price_list_' + detailId + '_new" onchange="handle_checkbox_change_add_detail_groups(' + detailId + ',\'price_list\')"';
        if (selectedDetailsGroup.includes(parseInt(detailId))) {
            table += ' checked';
        }
        table += '></td>';
        table += '<td>' + (index + 1) + '</td><td>' + detail.article + '</td><td>' + detail.name + '</td><td>' + detail.brand + '</td></tr>';
      });
      table+='</tbody></table><div>';
      $.unblockUI();
      create_window_centered_blue("detail_group_details_from_price_list_0_div_new","Детали с прайс листов","detail_group_details_from_price_list_0",table);
    }
  }, function(data){
    $.unblockUI();
  })
} 

function handle_checkbox_change_add_detail_groups(detailId,from='sklad') {
  var checkbox = document.getElementById('detail_group_checkbox_from_'+from+'_' + detailId+ '_new');
  if (checkbox.checked) {
    selectedDetailsGroup.push(detailId);
  } else {
      var index = selectedDetailsGroup.indexOf(detailId);
      if (index > -1) {
        selectedDetailsGroup.splice(index, 1);
      }
  }
}

function set_detail_group_undefined_detail(group_id,from="sklad") {
  let send = [];
  send['group_id'] = group_id;
  send['details'] = selectedDetailsGroup;
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Пожалуйста подождите...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query_array("/api/index.php", send, "set_undefined_details_group").then(function (data) {
    selectedDetailsGroup = [];
    $.unblockUI();
    if (data.status == "ok") {
      if(from=="sklad") get_detail_group_details_from_sklad_binding();
      if(from=="price_list") get_detail_group_details_from_price_list_binding();
      //$("#detail_group_details_from_sklad_0").html('');
    } else {
      console.error("Не удалось переместить категорию вверх");
    }
  });
}

function select_detail_groups_undefined_details(group_id = 0, glubina = 0, force = 0, from = "sklad") {
  if ($("#new_sel_det_group_znak_" + group_id).html() == "-" && !force) {
    $("#new_sel_undefined_detail_groups_list_" + group_id).html('');
    $("#new_sel_det_group_znak_" + group_id).html('+');
    return;
  }
  var send = [];
  send['in_group'] = group_id;
  api_query_array("/api/index.php", send, "get_detail_groups").then(function (data) {
    if (data.status == "ok") {
      var len = data.detail_groups.length;
      glubina++;
      var table = '<table class="detail_group_table" border="0" style="font-size: 16px;"><thead><tr><th></th><th></th></tr></thead><tbody>';
      
      for (let i = 0; i < len; i++) {
        var detailGroup = data.detail_groups[i];

        // Проверка has_in_group
        var hasInGroup = detailGroup.has_in_group == "1" ? 
          '<a onclick="select_detail_groups_undefined_details(' + detailGroup.id + ',' + (glubina) + ',0,\'' + from + '\');" id="new_sel_det_group_znak_' + detailGroup.id + '">+</a>' 
          : '';

        table += '<tr>\
          <td>' + hasInGroup + '<a onclick="bootbox.confirm(\'Вы точно хотите привязать товары к ' + detailGroup.group_name + '?\',function(result){ if(result) set_detail_group_undefined_detail(' + detailGroup.id + ',\'' + from + '\');})">' + detailGroup.group_name + '</a></td>\
          <td></td></tr>';

        table += '<tr><td id="new_sel_undefined_detail_groups_list_' + detailGroup.id + '" style="padding-left:' + ((glubina + 1) * 10) + 'px;"></td><td></td></tr>';
      }
      table += '</tbody></table>';

      if (glubina == 1) {
        create_window("new_sel_undefined_detail_groups_list_" + group_id + "_div", "выберите группу", "sel_undefined_detail_groups_list_" + group_id, table);
      } else {
        document.getElementById("new_sel_undefined_detail_groups_list_" + group_id).innerHTML = table;
      }
      $("#new_sel_det_group_znak_" + group_id).html('-');
    } else {
      document.getElementById("sel_undefined_detail_groups_list_" + group_id).innerHTML = 'Группы еще не заведены';
    }
  });
}

function swapRows(row1, row2) {
  let temp = row1.outerHTML;
  row1.outerHTML = row2.outerHTML;
  row2.outerHTML = temp;
}

function move_group_up(group_id) {
  let row = document.getElementById('detail_group_row_' + group_id);

  if (row.previousElementSibling) {
    let send = [];
    send['group_id'] = group_id;
    send['direction'] = 'up';

    api_query_array("/api/index.php", send, "move_detail_group").then(function (data) {
      if (data.status == "ok") {
        swapRows(row, row.previousElementSibling);
      } else {
        console.error("Не удалось переместить категорию вверх");
      }
    });
  }
}

function move_group_down(group_id) {
  let row = document.getElementById('detail_group_row_' + group_id);

  if (row.nextElementSibling) {
    let send = [];
    send['group_id'] = group_id;
    send['direction'] = 'down';

    api_query_array("/api/index.php", send, "move_detail_group").then(function (data) {
      if (data.status == "ok") {
        swapRows(row, row.nextElementSibling);
      } else {
        console.error("Не удалось переместить категорию вниз");
      }
    });
  }
}

 function edit_detail_group(in_group_id=0,group_id=0,group_name='',group_markup=0){
  var table='<form id="edit_detail_group_form">';
  table+='<div class="row"><input type="hidden" name="in_group_id" value="'+in_group_id+'"><input type="hidden" name="group_id" value="'+group_id+'">\
    <div class="col-sm-6">Наименование группы</div>\
    <div class="col-sm-6"><input type="text" name="group_name" class="form-control input-sm" value="'+group_name+'"></div>\
  </div>\
  <div class="row">\
    <div class="col-sm-6">Наценка в %</div>\
    <div class="col-sm-6"><input type="text" name="markup" class="form-control input-sm" value="'+group_markup+'"></div>\
  </div>';
  table+='</form>';
  table+='<div class="row" style="padding: 10px">\
    <div class="col-sm-6"><button class="btn btn-primary btn-xs" onclick="save_detail_group();">Сохранить</button></div>\
    <div class="col-sm-6"><button class="btn btn-default btn-xs pull-right" onclick="close_window(\'new_detail_group\');">Отмена</button></div>\
  </div>';
  create_window_centered_blue("new_detail_group_div","добавление/редактирование группы","new_detail_group",table);
 }

 function add_detail_group_library(){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Пожалуйста подождите...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  }); 
  api_query("/api/index.php","some_form","add_detail_group_library").then(function(data){
    if(data.status=="ok"){
      get_detail_groups();
      $.unblockUI();
    }
    $.unblockUI();
  });
 }

 function save_detail_group(){
   api_query("/api/index.php","edit_detail_group_form","save_detail_group").then(function(data){
    if(data.status=="ok"){
      close_window("new_detail_group");
      get_detail_groups();
    }
   });
 }

 function delete_detail_group(group_id){
   var send=[];
   send['group_id']=group_id;
   api_query_array("/api/index.php",send,"delete_detail_group").then(function(data){
    if(data.status=="ok"){
      get_detail_groups();
    }
   });
 }

function select_detail_groups(group_id=0,glubina=0,force=0,inl="sklad",detail_id=0,sklad_id){
  if($("#sel_"+inl+"_det_group_znak_"+group_id).html()=="-" && !force){
    $("#sel_"+inl+"_detail_groups_list_"+group_id).html('');
    $("#sel_"+inl+"_det_group_znak_"+group_id).html('+');
    return;
  }
 var send=[];
 send['in_group']=group_id;
  api_query_array("/api/index.php",send,"get_detail_groups").then(function(data){
   if(data.status=="ok"){
     var len=data.detail_groups.length;
     glubina++;
     var table='<table class="detail_group_table" border="0" style="font-size: 16px;"><thead><tr><th></th><th></th></tr></thead><tbody>';
     //if(glubina>1) table+='<tr><td><button class="btn btn-xs btn-primary" onclick="edit_detail_group('+group_id+',0);">добавить</button></td><td> </td></tr>';
     for(let i=0; i<len; i++){
       table+='<tr>\
       <td><a onclick="select_detail_groups('+data.detail_groups[i].id+','+(glubina)+','+force+',\''+inl+'\',\''+detail_id+'\','+sklad_id+');" id="sel_'+inl+'_det_group_znak_'+data.detail_groups[i].id+'">+</a>\
        <a onclick="set_detail_group('+data.detail_groups[i].id+',\''+data.detail_groups[i].group_name+'\',\''+inl+'\',\''+detail_id+'\','+sklad_id+');">'+data.detail_groups[i].group_name+'</a> </td>\
       <td> \
       </td></tr>';
       table+='<tr><td id="sel_'+inl+'_detail_groups_list_'+data.detail_groups[i].id+'" style="padding-left:'+((glubina+1)*10)+'px;"></td><td> </td></tr>';
     }
     table+='</tbody></table>';
     if (glubina==1)
      create_window("sel_"+inl+"_detail_groups_list_"+group_id+"_div","выберите группу","sel_"+inl+"_detail_groups_list_"+group_id,table);
     else 
      document.getElementById("sel_"+inl+"_detail_groups_list_"+group_id).innerHTML=table;
      $("#sel_"+inl+"_det_group_znak_"+group_id).html('-');
   }
   else {
     document.getElementById("sel_"+inl+"_detail_groups_list_"+group_id).innerHTML='Группы еще не заведены';
   }
  });
}

function set_detail_group(group_id,group_name,inl="sklad",detail_id=0, sklad_id){
  if(inl=="plan_report"){
    var send=[];
    send['month']=$("#plan_report_month").val();
    send['sklad_id']=$("#plan_report_sklad_id").val();
    send['detail_group_id']=group_id;
    send['value']=0;
    api_query_array("/api/index.php",send,"save_plan_report_reestr").then(function(){
      get_plan_report_reestr();
    })
  }
  else {
    var send=[];
    send['detail_group_detail_id']=detail_id;
    send['detail_group_id']=group_id;
    send['group_name']=group_name;
    send['sklad_id']=sklad_id;

    api_query_array("/api/index.php",send,"add_detail_group_detail_to_start").then(function(){
      edit_sklad_detail('delete_sklad_detail_'+detail_id);
    })
  }
  close_window("sel_"+inl+"_detail_groups_list_0");
}

function delete_detail_group_detail(group_id,detail_id=0){
    var send=[];
    send['detail_group_detail_id']=detail_id;
    send['detail_group_id']=group_id;
    api_query_array("/api/index.php",send,"delete_detail_group_detail").then(function(){
      edit_sklad_detail('delete_sklad_detail_'+detail_id);
    })
  
  //close_window("sel_"+inl+"_detail_groups_list_0");
}

function get_detail_group_details(detail_group_id,detail_group_status){
  var send=new Array();
  send['group_id']=detail_group_id;
  send['search_article']=$("#detail_group_search_article").val();
  //send['search_ean']=$("#detail_group_search_ean").val();
  send['search_name']=$("#detail_group_search_name").val();
  send['show_zero']=$("#detail_group_show_zero").prop("checked");
  send['selected_page']=$("#detail_group_selected_page").val();
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Пожалуйста подождите...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  }); 
  api_query_array("/api/index.php",send,"get_detail_group_details").then(function(data){
    if(data.status=="ok"){
      var table='<div class="row">\
      <div class="col-sm-3">';
      table+='<button type="button" class="btn btn-sm btn-primary" onclick="get_detail_group_details_from_sklad('+detail_group_id+','+detail_group_status+')">Добавить деталь</button>';
      table+='</div>\
      <div class="col-sm-9">\
      <div class="input-group input-group-sm pull-right">\
        <label for="detail_group_search_article" class="input-group-addon">Артикул:</label>\
        <input class="form-control" type="text" name="search_article" id="detail_group_search_article" onchange="get_detail_group_details('+detail_group_id+','+detail_group_status+');" value="';
        if(data.search_article!==null && typeof(data.search_article)!="undefined") table+=data.search_article;
        table+='">\
        <label for="detail_group_search_name" class="input-group-addon">Наименование:</label>\
        <input class="form-control" type="text" name="search_name" id="detail_group_search_name" onchange="get_detail_group_details('+detail_group_id+','+detail_group_status+');" value="';
        if(data.search_name!==null && typeof(data.search_name)!="undefined") table+=data.search_name;
        table+='">\
        <input type="hidden" name="detail_group_selected_page" id="detail_group_selected_page" ';
        if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
        else var selected_page=1;
        table+=' value="'+selected_page+'"';
        table+='>\
        <div class="input-group-btn">\
        <button type="button" class="btn btn-sm btn-default form-control" onclick="get_detail_group_details('+detail_group_id+','+detail_group_status+');">Поиск</button>\
        </div>\
      </div>\
      </div>\
      </div>';

      table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
      var x=0,y=0,xx=0,yy=0;
      
      for (var i=1; i<=data.detail_group_pages; i++){
        if(i>(selected_page+6) && i<(data.detail_group_pages-1)){
            x=1;
        }
        else x=0;
        if (i<(selected_page-6) && i!=1){
            y=1;
        }
        else y=0;
        if (x==1) {
          if (xx==0){
              table += '<li';
              table += '><a href="#" onclick="$(\'#detail_group_selected_page\').val(\''+i+'\');';
              //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_detail_group_details('+detail_group_id+',\''+detail_group_status+'\')">...</a></li>';
          }
          if (x==1) xx++;
        }
        else {
            if (y==1) {
          if (yy==0){
              table += '<li';
              table += '><a href="#" onclick="$(\'#detail_group_selected_page\').val(\''+i+'\');';
              //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_detail_group_details('+detail_group_id+',\''+detail_group_status+'\')">...</a></li>';
          }
          if (y==1) yy++;
            }
            else {
          table += '<li';
          if(selected_page==i) table+= " class='active'";
          table += '><a href="#" onclick="$(\'#detail_group_selected_page\').val(\''+i+'\');';
          //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
          table += 'get_detail_group_details('+detail_group_id+',\''+detail_group_status+'\')">'+i+'</a></li>';
            }
        }
      }
      table += '</ul></div>';

      table+='<div style="height: 73vh; overflow:auto;">\
      <table class="table table-hover">\
      <thead>\
      <tr><th><input type="checkbox" id="detail_group_checkbox_all" onclick="check_all_detail_group_details();"></th><th>№</th><th>артикул</th><th>наименование</th><th>бренд</th></tr>\
      </thead><tbody>';
      var len=data.detail_group_details.length;
      for (var i=0; i<len; i++){
        table+='<tr><td>';
        table+='<input type="checkbox" id="detail_group_checkbox_'+data.detail_group_details[i].id+'" onchange="delete_detail_group_detail('+detail_group_id+','+data.detail_group_details[i].detail_id+')" checked>';
        table+='</td>';
        table+='<td>'+(i+1)+'</td><td>'+data.detail_group_details[i].article+'</td><td>'+data.detail_group_details[i].name+'</td><td>'+data.detail_group_details[i].brand+'</td>\
        </tr>';
      }
      table+='</tbody></table><div>';
      $.unblockUI();
      create_window_centered_blue("detail_group_details_"+detail_group_id+"_div","Детали товарной группы","detail_group_details_"+detail_group_id,table);
    }
  }, function(data){
    $.unblockUI();
  })
}

var selectedDetailsGroup = [];

function get_detail_group_details_from_sklad(detail_group_id,detail_group_status){
  var send=new Array();
  send['group_id']=detail_group_id;
  send['search_article']=$("#detail_group_search_article_from_sklad").val();
  //send['search_ean']=$("#detail_group_search_ean").val();
  send['search_name']=$("#detail_group_search_name_from_sklad").val();
  send['show_zero']=$("#detail_group_show_zero_from_sklad").prop("checked");
  send['selected_page']=$("#detail_group_selected_page_from_sklad").val();
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Пожалуйста подождите...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  }); 
  api_query_array("/api/index.php",send,"get_detail_group_details_from_sklad").then(function(data){
    if(data.status=="ok"){
      var table='<div class="row">\
      <div class="col-sm-3">';
      //table+='<button type="button" class="btn btn-sm btn-primary" onclcik="get_detail_group_details_from_sklad('+detail_group_id+','+detail_group_status+')">Добавить деталь</button>';
      table+='</div>\
      <div class="col-sm-9">\
      <div class="input-group input-group-sm pull-right">\
        <span class="input-group-addon"><input type="checkbox" name="show_zero" id="detail_group_show_zero_from_sklad"';
        if(typeof(data.show_zero)!="undefined" && data.show_zero) table+=' checked';
        table+='>Показать 0 остатки</span>\
        <label for="detail_group_search_article" class="input-group-addon">Артикул:</label>\
        <input class="form-control" type="text" name="search_article" id="detail_group_search_article_from_sklad" onchange="get_detail_group_details_from_sklad('+detail_group_id+','+detail_group_status+');" value="';
        if(typeof(send['search_article'])!="undefined") table+=send['search_article'];
        table+='">\
        <label for="detail_group_search_name" class="input-group-addon">Наименование:</label>\
        <input class="form-control" type="text" name="search_name" id="detail_group_search_name_from_sklad" onchange="get_detail_group_details_from_sklad('+detail_group_id+','+detail_group_status+');" value="';
        if(typeof(send['search_name'])!="undefined") table+=send['search_name'];
        table+='">\
        <input type="hidden" name="detail_group_selected_page" id="detail_group_selected_page_from_sklad" ';
        if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
        else var selected_page=1;
        table+=' value="'+selected_page+'"';
        table+='>\
      </div>\
      </div>\
      </div>';

      table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
      var x=0,y=0,xx=0,yy=0;
      
      for (var i=1; i<=data.detail_group_pages; i++){
        if(i>(selected_page+6) && i<(data.detail_group_pages-1)){
            x=1;
        }
        else x=0;
        if (i<(selected_page-6) && i!=1){
            y=1;
        }
        else y=0;
        if (x==1) {
          if (xx==0){
              table += '<li';
              table += '><a href="#" onclick="$(\'#detail_group_selected_page_from_sklad\').val(\''+i+'\');';
              //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_detail_group_details_from_sklad('+detail_group_id+',\''+detail_group_status+'\')">...</a></li>';
          }
          if (x==1) xx++;
        }
        else {
            if (y==1) {
          if (yy==0){
              table += '<li';
              table += '><a href="#" onclick="$(\'#detail_group_selected_page_from_sklad\').val(\''+i+'\');';
              //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_detail_group_details_from_sklad('+detail_group_id+',\''+detail_group_status+'\')">...</a></li>';
          }
          if (y==1) yy++;
            }
            else {
          table += '<li';
          if(selected_page==i) table+= " class='active'";
          table += '><a href="#" onclick="$(\'#detail_group_selected_page_from_sklad\').val(\''+i+'\');';
          //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
          table += 'get_detail_group_details_from_sklad('+detail_group_id+',\''+detail_group_status+'\')">'+i+'</a></li>';
            }
        }
      }
      table += '</ul></div>';

      table+='<div style="height: 73vh; overflow:auto;">\
      <table class="table table-hover">\
      <thead>\
      <tr><th><input type="checkbox" id="detail_group_checkbox_all_from_sklad" onclick="check_all_detail_group_details_from_sklad();"></th><th>№</th><th>артикул</th><th>наименование</th><th>бренд</th></tr>\
      </thead><tbody>';
      var len=data.detail_group_details.length;

      for (var i=0; i<len; i++){
        table+='<tr><td>';
        table+='<input type="checkbox" id="detail_group_checkbox_from_sklad_'+data.detail_group_details[i].detail_id+'" onchange="add_to_detail_group_start('+data.detail_group_details[i].detail_id+','+detail_group_id+')" ';
        if(data.detail_group_details[i].checked) table+=' checked';
        table+='>';
        table+='</td>';
        table+='<td>'+(i+1)+'</td><td>'+data.detail_group_details[i].article+'</td><td>'+data.detail_group_details[i].name+'</td><td>'+data.detail_group_details[i].brand+'</td>\
        </tr>';
      }
      table+='</tbody></table><div>';
      $.unblockUI();
      create_window_centered_blue("detail_group_details_from_sklad_"+detail_group_id+"_div","Детали со склада","detail_group_details_from_sklad_"+detail_group_id,table);
    }
  }, function(data){
    $.unblockUI();
  })
}

function add_to_detail_group_start(detail_group_detail_id,detail_group_id){
  var send=new Array();
  send['detail_group_detail_id']=detail_group_detail_id;
  send['detail_group_id']=detail_group_id;
  api_query_array("/api/index.php",send,"add_detail_group_detail_to_start").then(function(data){
    if(data.status=="err"){
      $("#checkbox_"+detail_group_detail_id).prop('checked', false);
    }
  });
}

function check_all_detail_group_details(){
  $("input[id^=detail_group_checkbox").each(function(index){
    if($("#detail_group_checkbox_all").prop("checked")){
      if(!$(this).prop("checked")) $(this).click();
    }
    else {
      if($(this).prop("checked")) $(this).click();
    }
  })
}

function check_all_detail_group_details_from_sklad(){
  $("input[id^=detail_group_checkbox_from_sklad_").each(function(index){
    if($("#detail_group_checkbox_all_from_sklad").prop("checked")){
      if(!$(this).prop("checked")) $(this).click();
    }
    else {
      if($(this).prop("checked")) $(this).click();
    }
  })
}

function check_all_detail_group_details_from_sklad_binding() {
  var checkboxes = document.querySelectorAll('[id^="detail_group_checkbox_from_sklad_"]');
  var selectAllCheckbox = document.getElementById('detail_group_checkbox_all_from_sklad_new');

  if (selectAllCheckbox.checked) {
      // Add all detailIds to the array and check all checkboxes
      checkboxes.forEach(function(checkbox) {
          var detailId = parseInt(checkbox.id.split('_')[5]);
          if (!selectedDetailsGroup.includes(detailId)) {
            selectedDetailsGroup.push(detailId);
          }
          checkbox.checked = true;
      });
  } else {
      // Remove all detailIds from the array and uncheck all checkboxes
      checkboxes.forEach(function(checkbox) {
        var detailId = parseInt(checkbox.id.split('_')[5]);
            var index = selectedDetailsGroup.indexOf(detailId);
            if (index !== -1) {
              selectedDetailsGroup.splice(index, 1);
            }
            checkbox.checked = false;
    });
  }
}

function check_all_detail_group_details_from_price_list_binding() {
  var checkboxes = document.querySelectorAll('[id^="detail_group_checkbox_from_price_list_"]');
  var selectAllCheckbox = document.getElementById('detail_group_checkbox_all_from_price_list_new');

  if (selectAllCheckbox.checked) {
      // Add all detailIds to the array and check all checkboxes
      checkboxes.forEach(function(checkbox) {
          var detailId = parseInt(checkbox.id.split('_')[6]);
          if (!selectedDetailsGroup.includes(detailId)) {
            selectedDetailsGroup.push(detailId);
          }
          checkbox.checked = true;
      });
  } else {
      // Remove all detailIds from the array and uncheck all checkboxes
      checkboxes.forEach(function(checkbox) {
        var detailId = parseInt(checkbox.id.split('_')[6]);
            var index = selectedDetailsGroup.indexOf(detailId);
            if (index !== -1) {
              selectedDetailsGroup.splice(index, 1);
            }
            checkbox.checked = false;
    });
  }
}

function save_zakaz_options(){
  var send=[];
  send['zakaz_commit']=$("#zakaz_options_form input[name=zakaz_commit]").prop("checked");
  send['zakaz_marketing_channel']=$("#zakaz_options_form input[name=zakaz_marketing_channel]").prop("checked");
  send['self_zakaz_sale_price']=$("#zakaz_options_form input[name=self_zakaz_sale_price]").prop("checked");
  api_query_array("/api/index.php",send,"save_zakaz_commit").then(function(data){
    if(data.status=="ok"){
      get_zakaz_options();
    }
  });
}

function get_zakaz_options(){
  api_query("/api/index.php","some_from","get_zakaz_commit").then(function(data){
    var table='<form id="zakaz_options_form">\
    <table class="table table-hover">\
          <tr>\
          <td>Не проверять наличие денежных средств при подтверждении заказа</td><td><input type="checkbox" name="zakaz_commit"';
          if(parseInt(data.zakaz_commit)==1) table+=' checked="checked"';
          table+='></td>\
          </tr>\
          <tr>\
          <td>Обязательное указание маркетингового канала поступления заказа</td><td><input type="checkbox" name="zakaz_marketing_channel"';
          if(parseInt(data.zakaz_marketing_channel)==1) table+=' checked="checked"';
          table+='></td>\
          </tr>\
          <tr>\
          <td>При оприходовании деталей из заказов на склад, устанавливать цену продажи из заказа</td><td><input type="checkbox" name="self_zakaz_sale_price"';
          if(parseInt(data.self_zakaz_sale_price)==1) table+=' checked="checked"';
          table+='></td>\
          </tr>\
    </table>\
    </form>\
    <button class="btn btn-primary btn-sm" onclick="save_zakaz_options()">Сохранить</button>';
    $("#zakaz_options_list").html(table);
  });
}

function save_document_options(){
  var send=[];
  send['document_set_price']=$("#document_options_form input[name=document_set_price]").prop("checked");
  send['document_set_category']=$("#document_options_form input[name=document_set_category]").prop("checked");
  send['document_details_round']=$("#document_options_form [name=document_details_round]").val();
  send['document_edit_deny_date']=$("#document_options_form [name=document_edit_deny_date]").val();
  send['document_detail_edit_deny']=$("#document_options_form [name=document_detail_edit_deny]").prop("checked");
  api_query_array("/api/index.php",send,"save_document_set_price").then(function(data){
    if(data.status=="ok"){
      get_document_options();
    }
  });
}

function get_document_options(){
  api_query("/api/index.php","some_from","get_document_set_price").then(function(data){
    document_details_round=parseInt(data.document_details_round);
    var table='<form id="document_options_form">\
    <table class="table table-hover">';
          table+='<tr>\
          <td>Устанавливать цену продажи детали приходного документа в цену продажи склада</td><td><input type="checkbox" name="document_set_price"';
          if(parseInt(data.document_set_price)==1) table+=' checked="checked"';
          table+='></td>\
          </tr>';
          table+='<tr>\
          <td>Обязательное указание категории товара</td><td><input type="checkbox" name="document_set_category"';
          if(parseInt(data.document_set_category)==1) table+=' checked="checked"';
          table+='></td>\
          </tr>';
          table+='<tr>\
          <td>округлить цену продажи :</td><td><select name="document_details_round" class="form-control">';
          //table+='<option value="0" '+(parseInt(data.document_details_round)==0?' selected="selected"':"")+'> не округлять </option>';
          table+='<option value="1" '+(parseInt(data.document_details_round)==1?' selected="selected"':"")+'> 1 </option>';
          table+='<option value="5" '+(parseInt(data.document_details_round)==5?' selected="selected"':"")+'> 5 </option>';
          table+='<option value="10" '+(parseInt(data.document_details_round)==10?' selected="selected"':"")+'> 10 </option>';
          table+='<option value="50" '+(parseInt(data.document_details_round)==50?' selected="selected"':"")+'> 50 </option>';
          table+='<option value="100" '+(parseInt(data.document_details_round)==100?' selected="selected"':"")+'> 100 </option>';
          table+='</select></td>\
          </tr>\
          <tr><td>Дата запрета редактирования документов: </td><td><input class="form-control" type="date" id="document_edit_deny_date" name="document_edit_deny_date" value="'+(data.document_edit_deny_date!==null?data.document_edit_deny_date:"")+'"></td></tr>\
          <tr><td>Запрет редактирования деталей в документе: </td><td><input class="" type="checkbox" id="document_detail_edit_deny" name="document_detail_edit_deny" '+(data.document_detail_edit_deny==1?" checked":"")+'></td></tr>\
        </table>\
    </form>\
    <button class="btn btn-primary btn-sm" onclick="save_document_options()">Сохранить</button>';
    $("#document_options_list").html(table);
  });
}

function get_marketing_channels(){
  api_query("/api/index.php","some_form","get_marketing_channels").then(function(data){
    var table='<table class="table"><thead><tr><th>дата создания</th><th>Наименование</th><th></th></tr></thead><tbody>';
    for (var i in data.marketing_channels){
      table+='<tr><td>'+data.marketing_channels[i].create_date+'</td><td>'+data.marketing_channels[i].name+'</td><td>\
      <a onclick="edit_marketing_channel('+data.marketing_channels[i].id+');"><img src="/new_images/edit.svg" class="menuimg"></a>\
      <a onclick="bootbox.confirm(\'Вы точно хотите удалить канал продаж?\',function(result){ if(result) delete_marketing_channel('+data.marketing_channels[i].id+');})"><img src="/new_images/garbage.svg" class="menuimg"></a>\
      </td></tr>';
    }
    table+='</tbody></table>';
    document.getElementById("marketing_channels_list").innerHTML=table;
  })
}

function edit_marketing_channel(id=0){
  if(id==0){
    data={
      id: 0,
      name: ""
    };
    marketing_channel_edit_form(data);
  }
  else {
    var send=[];
    send['marketing_channel_id']=id;
    api_query_array("/api/index.php",send,"get_marketing_channel").then(function(data){
      marketing_channel_edit_form(data.marketing_channel);
    })
  }
}

function marketing_channel_edit_form(data){
  var table='<form id="edit_marketing_channel_form"><table class="table"><tbody>\
  <tr><td>Наименование канала продаж</td><td><input type="text" name="name" value="'+data.name+'" class="form-control"></td></tr>\
  <tr><td><button type="button" class="btn btn-sm btn-primary" onclick="save_marketing_channel();">Сохранить</button></td>\
  <td><button type="button" class="btn btn-sm btn-default" onclick="$(\'#edit_marketing_channel\').html(\'\');">Отменить</button></td></tr>\
  </tbody></table></form>';
  create_window_centered_blue("edit_marketing_channel_div","Редактирование канала продаж","edit_marketing_channel",table);
}

function save_marketing_channel(){
  api_query("/api/index.php","edit_marketing_channel_form","save_marketing_channel").then(function(data){
    if(data.status=="ok"){
      get_marketing_channels();
      $('#edit_marketing_channel').html('');
    }
  })
}

function delete_marketing_channel(id){
  var send=[];
  send['marketing_channel_id']=id;
  api_query_array("/api/index.php",send,"delete_marketing_channel").then(function(data){
    get_marketing_channels();
  })
}

function get_zakaz_footers(){
  api_query("/api/index.php","some_form","get_zakaz_footers").then(function(data){
    var table='<table class="table"><thead><th>Дата</th><th>Описание</th><th>исп. по умолчанию</th><th></th></thead><tbody>';
    for(var i in data.ZakazFooters){
      table+='<tr><td>'+data.ZakazFooters[i].create_date+'</td><td>'+data.ZakazFooters[i].descr+'</td><td>';
      if(data.ZakazFooters[i].is_default=="1") table+='<img src="/images/ok.svg" width="25px;">';
      table+='</td><td>\
      <div class="btn-group" style="display: flex;">\
      <a onclick="edit_zakaz_footer('+data.ZakazFooters[i].id+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a>\
       <a title="Удалить" onclick="bootbox.confirm(\'Вы точно хотите удалить?\',\
       function(result){ \
         if(result) \
          delete_zakaz_footer('+data.ZakazFooters[i].id+');\
       })"><img src="/new_images/garbage.svg" style="width:20px;"></a></div>\
      </td></tr>';
    }
    table+='</tbody></table>';
    $("#zakaz_footers_list").html(table);
  });
}

function edit_zakaz_footer(id){
  if(id==0){
    data={
      "id":0,
      "descr":"",
      "zakaz_footer":"",
      "is_default":0
    };
    print_zakaz_footer(data);
  }
  else {
    var send=[];
    send['ZakazFooter_id']=id;
    api_query_array("/api/index.php",send,"get_zakaz_footer").then(function(data){
      print_zakaz_footer(data.ZakazFooter);
    });
  }
}

function print_zakaz_footer(data){
  var table='<form id="edit_zakaz_footer_form"><input type="hidden" name="ZakazFooter_id" value="'+data.id+'"><table class="table"><tbody>';
  table+='<tr><td>Описание</td><td><input type="text" name="descr" value="'+data.descr+'" class="form-control"></td></tr>';
  table+='<tr><td>Форма</td><td>\
  <a onclick="edit_zakaz_footer_html(\'zakaz_footer\')">\
  <img src="/new_images/edit.svg" style="width: 25px;">\
  </a>\
  <textarea name="zakaz_footer" id="zakaz_footer" class="form-control" style="display:none;">'+data.zakaz_footer+'</textarea></td></tr>';
  table+='<tr><td>Использовать по умолчанию</td><td><input type="checkbox" name="is_default"';
  if(data.is_default==1) table+=' checked';
  table+='></td></tr>';
  table+='<tr><td><button onclick="save_zakaz_footer();" class="btn btn-sm btn-primary" type="button">Сохранить</button></td><td></td></tr>';
  table+='</tbody></table></form>';
  create_window_centered_blue("edit_zakaz_footer_div","Редактирование пеатной формы заказа","edit_zakaz_footer",table);
}

function save_zakaz_footer(){
  api_query("/api/index.php","edit_zakaz_footer_form","save_zakaz_footer").then(function(data1){
    if(data1.status=="ok"){
      $("#edit_zakaz_footer").html('');
      get_zakaz_footers();
    }
  })
}

function delete_zakaz_footer(id){
  send=[];
  send['ZakazFooter_id']=id;
  api_query_array("/api/index.php",send,"delete_zakaz_footer").then(function(data1){
    if(data1.status=="ok"){
      get_zakaz_footers();
    }
  })
}

function edit_zakaz_footer_html(id){
  //$("#"+id+"_html").css("display","none");
  //$("#"+id).css("display","block");
  if(typeof($("#"+id).parent().find(".note-editor").html())!="undefined"){
    $('#'+id).summernote('destroy');
    $("#"+id).css("display","none");
  }
  else {
    $('#'+id).summernote({
      //placeholder: "Hello stand alone ui",
      tabsize: 2,
      //height: 120,
      //width: 650,
      toolbar: [
        ["style", ["style"]],
        ["font", ["bold", "underline", "clear"]],
        ["color", ["color"]],
        ["para", ["ul", "ol", "paragraph"]],
        ["table", ["table"]],
        ["insert", ["link", "picture", "video"]],
        ["view", ["fullscreen", "codeview", "help"]]
      ]
    });
  }
}

function get_zakaz_garants(){
  api_query("/api/index.php","some_form","get_zakaz_garants").then(function(data){
    var table='<table class="table"><thead><th>Дата</th><th>Описание</th><th>исп. по умолчанию</th><th></th></thead><tbody>';
    for(var i in data.ZakazGarants){
      table+='<tr><td>'+data.ZakazGarants[i].create_date+'</td><td>'+data.ZakazGarants[i].descr+'</td><td>';
      if(data.ZakazGarants[i].is_default=="1") table+='<img src="/images/ok.svg" width="25px;">';
      table+='</td><td>\
      <div class="btn-group" style="display: flex;">\
      <a onclick="edit_zakaz_garant('+data.ZakazGarants[i].id+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a>\
       <a title="Удалить" onclick="bootbox.confirm(\'Вы точно хотите удалить?\',\
       function(result){ \
         if(result) \
          delete_zakaz_garant('+data.ZakazGarants[i].id+');\
       })"><img src="/new_images/garbage.svg" style="width:20px;"></a></div>\
      </td></tr>';
    }
    table+='</tbody></table>';
    $("#zakaz_garants_list").html(table);
  });
}

function edit_zakaz_garant(id){
  if(id==0){
    data={
      "id":0,
      "descr":"",
      "zakaz_garant":"",
      "is_default":0
    };
    print_zakaz_garant(data);
  }
  else {
    var send=[];
    send['ZakazGarant_id']=id;
    api_query_array("/api/index.php",send,"get_zakaz_garant").then(function(data){
      print_zakaz_garant(data.ZakazGarant);
    });
  }
}

function print_zakaz_garant(data){
  var table='<form id="edit_zakaz_garant_form"><input type="hidden" name="ZakazGarant_id" value="'+data.id+'"><table class="table"><tbody>';
  table+='<tr><td>Описание</td><td><input type="text" name="descr" value="'+data.descr+'" class="form-control"></td></tr>';
  table+='<tr><td>Форма</td><td>\
  <a onclick="edit_zakaz_garant_html(\'zakaz_garant\')">\
  <img src="/new_images/edit.svg" style="width: 25px;">\
  </a>\
  <textarea name="zakaz_garant" id="zakaz_garant" class="form-control" style="display:none;">'+data.zakaz_garant+'</textarea></td></tr>';
  table+='<tr><td>Использовать по умолчанию</td><td><input type="checkbox" name="is_default"';
  if(data.is_default==1) table+=' checked';
  table+='></td></tr>';
  table+='<tr><td><button onclick="save_zakaz_garant();" class="btn btn-sm btn-primary" type="button">Сохранить</button></td><td></td></tr>';
  table+='</tbody></table></form>';
  create_window_centered_blue("edit_zakaz_garant_div","Редактирование пеатной формы заказа","edit_zakaz_garant",table);
}

function save_zakaz_garant(){
  api_query("/api/index.php","edit_zakaz_garant_form","save_zakaz_garant").then(function(data1){
    if(data1.status=="ok"){
      $("#edit_zakaz_garant").html('');
      get_zakaz_garants();
    }
  })
}

function delete_zakaz_garant(id){
  send=[];
  send['ZakazGarant_id']=id;
  api_query_array("/api/index.php",send,"delete_zakaz_garant").then(function(data1){
    if(data1.status=="ok"){
      get_zakaz_garants();
    }
  })
}

function edit_zakaz_garant_html(id){
  //$("#"+id+"_html").css("display","none");
  //$("#"+id).css("display","block");
  if(typeof($("#"+id).parent().find(".note-editor").html())!="undefined"){
    $('#'+id).summernote('destroy');
    $("#"+id).css("display","none");
  }
  else {
    $('#'+id).summernote({
      //placeholder: "Hello stand alone ui",
      tabsize: 2,
      //height: 120,
      //width: 650,
      toolbar: [
        ["style", ["style"]],
        ["font", ["bold", "underline", "clear"]],
        ["color", ["color"]],
        ["para", ["ul", "ol", "paragraph"]],
        ["table", ["table"]],
        ["insert", ["link", "picture", "video"]],
        ["view", ["fullscreen", "codeview", "help"]]
      ]
    });
  }
}

function get_settings_avito(group_id = 0, glubina = 0, force = 0) {
  var send = [];
  send['in_group'] = group_id;
  api_query_array("/api/index.php", send, "get_settings_avito").then(function (data) {
      if (data.status == "ok") {
          var len = data.avito_categorys.length;
          glubina++;
          var table = '<table class="table table-bordered table-hover" style="font-size: 16px;"><thead><tr><th>SORT1 категории</th><th>Авито категории</th><th></th></tr></thead><tbody>';
          if (glubina > 1) table += '<tr><td colspan="3"><button class="btn btn-xs btn-primary" onclick="edit_detail_group(' + group_id + ',0);">Добавить</button></td></tr>';
          for (let i = 0; i < len; i++) {
              table += '<tr id="detail_group_avito_row_' + data.avito_categorys[i].id + '">\
                  <td>' + 
                  (data.avito_categorys[i].has_in_group === "1" ? 
                    '<a onclick="get_child_settings_avito(' + data.avito_categorys[i].id + ',' + (glubina) + ');" id="avito_group_znak_' + data.avito_categorys[i].id + '">+</a> ' : 
                    '') + 
                  data.avito_categorys[i].name + '</td>\
                  <td style="vertical-align: top;">' + (data.avito_categorys[i].category_marketplaces_name !== null ? data.avito_categorys[i].category_marketplaces_name : '') + '</td>\
                  <td>';

              if (data.avito_categorys[i].view == "1") {
                  table += '<button class="btn btn-xs ' + (data.avito_categorys[i].category_marketplaces_name !== null ? 'btn-danger' : 'btn-primary') + '" onclick="toggle_marketplace_binding('+glubina+',' + data.avito_categorys[i].id + ', \'' + (data.avito_categorys[i].category_marketplaces_name == null ? 'bind' : 'unbind') + '\', \'' + data.avito_categorys[i].name + '\');">\
                      ' + (data.avito_categorys[i].category_marketplaces_name == null ? 'Привязать' : 'Отвязать') + '\
                      </button>';
              }

              table += '</td></tr>';
          }

          table += '</tbody></table>';
          document.getElementById("avito_category_config_list_" + group_id).innerHTML = table;
          if (data.avito_categorys.some(cat => cat.has_in_group === "1")) {
              $("#avito_group_znak_" + group_id).html('-');
          }
      } else {
          document.getElementById("avito_category_config_list_" + group_id).innerHTML = 'Нет категорий';
      }
  });
}
function get_child_settings_avito(parent_id, glubina, force = 0) {
  if ($("#avito_group_znak_" + parent_id).html() == "-" && !force) {
      $(".child_avito_" + parent_id).html('');
      $("#avito_group_znak_" + parent_id).html('+');
      return;
  }
  
  var send = [];
  send['in_group'] = parent_id;
  
  api_query_array("/api/index.php", send, "get_settings_avito").then(function (data) {
      if (data.status == "ok") {
          var len = data.avito_categorys.length;
          glubina++;
          var table = '';
          
          for (let i = 0; i < len; i++) {
              let rowHtml = '<tr id="detail_group_avito_row_' + data.avito_categorys[i].id + '" class="child_avito_' + parent_id + '">\
                  <td style="padding-left:' + (glubina * 10) + 'px;">';
              
              // Conditionally add the <a> tag based on has_in_group
              if (data.avito_categorys[i].has_in_group !== "0") {
                  rowHtml += '<a onclick="get_child_settings_avito(' + data.avito_categorys[i].id + ',' + (glubina) + ');" id="avito_group_znak_' + data.avito_categorys[i].id + '">+</a> ';
              }
              
              rowHtml += data.avito_categorys[i].name + '</td>\
                  <td style="vertical-align: top;">' + (data.avito_categorys[i].category_marketplaces_name !== null ? data.avito_categorys[i].category_marketplaces_name : '') + '</td>\
                  <td>\
                      <button class="btn btn-xs ' + (data.avito_categorys[i].category_marketplaces_name !== null ? 'btn-danger' : 'btn-primary') + '" onclick="toggle_marketplace_binding('+glubina+',' + data.avito_categorys[i].id + ', \'' + (data.avito_categorys[i].category_marketplaces_name == null ? 'bind' : 'unbind') + '\', \'' + data.avito_categorys[i].name + '\');">\
                      ' + (data.avito_categorys[i].category_marketplaces_name == null ? 'Привязать' : 'Отвязать') + '\
                      </button>\
                  </td>\
              </tr>';
              
              table += rowHtml;
          }
          
          if (table !== '') {
              $("#detail_group_avito_row_" + parent_id).after(table);
              $("#avito_group_znak_" + parent_id).html('-');
          } else {
              $("#avito_group_znak_" + parent_id).html(''); // Hide the sign if no child elements
          }
      } else {
          // Optionally handle the case when the status is not "ok"
      }
  });
}

function toggle_marketplace_binding(glubina, category_id, action, category_name) {
  if (action === 'bind') {
    bind_marketplace(glubina, category_id, category_name);
  } else if (action === 'unbind') {
    unbind_marketplace(glubina, category_id);
  }
}

function bind_marketplace(glubina, category_id, category_name) {
  if ($("#avito_category_znak_" + category_id).html() == "-" && !force) {
    $(".child_category_avito_" + category_id).html('');
    $("#avito_category_znak_" + category_id).html('+');
    return;
  }
  var send = [];
  send['in_group'] = 0;
  api_query_array("/api/index.php", send, "get_avito_categorys").then(function (data) {
      if (data.status == "ok") {
          var len = data.avito_categorys.length;
          var table = '<table class="table table-bordered" style="font-size: 16px;">\
                          <thead>\
                              <tr>\
                                  <th>Название категории</th>\
                                  <th></th>\
                              </tr>\
                          </thead>\
                          <tbody>';

          for (let i = 0; i < len; i++) {
            let category = data.avito_categorys[i];
            table += '<tr id="bind_row_' + category.id + '">\
                        <td>';
            
            // Условие на отображение тега <a>
            if (category.has_children == "1") {
              table += '<a onclick="get_child_category_avito(' + category.id + ', ' + glubina + ', ' + category_id + ');" id="avito_category_znak_' + category.id + '">+</a> ';
            }
    
            table += category.name + '</td>\
                        <td>\
                          <button class="btn btn-xs btn-primary" onclick="binding_marketplace(' + glubina + ',' + category.id + ', ' + category_id + ');">\
                            Привязать\
                          </button>\
                        </td>\
                      </tr>';
          }

          table += '</tbody></table>';
          create_window_centered_blue("new_avito_category_div", "Привязка к категории " + category_name, "new_avito_category", table);
      } else {
          // Обработка ошибки или другие действия при неудачном запросе
      }
  });
}

function get_child_category_avito(parent_id, glubina, category_id, force = 0) {
  if ($("#avito_category_znak_" + parent_id).html() == "-" && !force) {
      $(".child_category_avito_" + parent_id).html('');
      $("#avito_category_znak_" + parent_id).html('+');
      return;
  }
  var send = [];
  send['in_group'] = parent_id;
  api_query_array("/api/index.php", send, "get_avito_categorys").then(function (data) {
      if (data.status == "ok") {
          var len = data.avito_categorys.length;
          glubina++;
          var table = '';

          for (let i = 0; i < len; i++) {
            let category = data.avito_categorys[i];
            table += '<tr id="bind_row_' + category.id + '" class="child_category_avito_' + parent_id + '">\
                        <td style="padding-left:' + (glubina * 10) + 'px;">';
    
            // Условие на отображение тега <a>
            if (category.has_children == "1") {
              table += '<a onclick="get_child_category_avito(' + category.id + ', ' + glubina + ', ' + category_id + ');" id="avito_category_znak_' + category.id + '">+</a> ';
            }
    
            table += category.name + '\
                        </td>\
                        <td>\
                          <button class="btn btn-xs btn-primary" onclick="binding_marketplace(' + glubina + ',' + category.id + ', ' + category_id + ');">\
                            Привязать\
                          </button>\
                        </td>\
                      </tr>';
          }

          if (table !== '') {
              $("#bind_row_" + parent_id).after(table);
              $("#avito_category_znak_" + parent_id).html('-');
          }
      } else {
        var table = '<tr class="child_category_avito_' + parent_id + '">\
            td style="padding-left:' + (glubina * 10) + 'px;">\
                <button class="btn btn-xs btn-primary" onclick="edit_category_avito(' + parent_id + ')">Добавить категорию</button>\
            </td>\
        </tr>';
        $("#bind_row_" + parent_id).after(table);
        $("#avito_category_znak_" + parent_id).html('-');
      }
  });
}


function unbind_marketplace(glubina, category_id) {
  var send = {
    'category_id': category_id,
  };

  api_query_array("/api/index.php", send, "toggle_marketplace_unbinding").then(function (data) {
    if (data.status == "ok") {
      if (data.category) {
        
        var category = data.category;
        var newRowHtml = '<td style="padding-left:' + (glubina * 10) + 'px;">\
                          <a onclick="get_child_settings_avito(' + category.id + ',1);" id="avito_group_znak_' + category.id + '">+</a> ' + category.name + '</td>' +
                          '<td style="vertical-align: top;">' + (category.category_marketplaces_name ? category.category_marketplaces_name : '') + '</td>' +
                          '<td>';

        if (category.category_marketplaces_name) {
          newRowHtml += '<button class="btn btn-xs btn-danger" onclick="toggle_marketplace_binding('+glubina+',' + category.id + ', \'unbind\', \'' + category.name + '\');">Отвязать</button>';
        } else {
          newRowHtml += '<button class="btn btn-xs btn-primary" onclick="toggle_marketplace_binding('+glubina+',' + category.id + ', \'bind\', \'' + category.name + '\');">Привязать</button>';
        }

        newRowHtml += '</td>';

        // Find and replace the existing row with the new one
        var existingRow = $("#detail_group_avito_row_" + category_id);
        if (existingRow.length > 0) {
          existingRow.html(newRowHtml);
        } else {
          // If the existing row is not found, append a new one
          // $("#new_avito_category").append('<tr id="detail_group_row_' + category.id + '">' + newRowHtml + '</tr>');
        }
      }
    } else {
        console.error('Произошла ошибка: ' + data.error);
        // Другие действия при ошибке
    }
  });
}

function binding_marketplace(glubina, avito_category_id, category_id) {
  var send = {
    'avito_category_id': avito_category_id,
    'category_id': category_id,
  };

  api_query_array("/api/index.php", send, "toggle_marketplace_binding").then(function (data) {
    if (data.status == "ok") {

      if (data.category) {
        $("#new_avito_category").html('');

        var category = data.category;
        var newRowHtml = '<td style="padding-left:' + (glubina * 10) + 'px;">\
                          <a onclick="get_child_settings_avito(' + category.id + ',1);" id="avito_group_znak_' + category.id + '">+</a> ' + category.name + '</td>' +
                          '<td style="vertical-align: top;">' + (category.category_marketplaces_name ? category.category_marketplaces_name : '') + '</td>' +
                          '<td>';

        if (category.category_marketplaces_name) {
          newRowHtml += '<button class="btn btn-xs btn-danger" onclick="toggle_marketplace_binding('+glubina+',' + category.id + ', \'unbind\', \'' + category.name + '\');">Отвязать</button>';
        } else {
          newRowHtml += '<button class="btn btn-xs btn-primary" onclick="toggle_marketplace_binding('+glubina+',' + category.id + ', \'bind\', \'' + category.name + '\');">Привязать</button>';
        }

        newRowHtml += '</td>';

        var existingRow = $("#detail_group_avito_row_" + category_id);
        if (existingRow.length > 0) {
          existingRow.html(newRowHtml);
        }
      }

    } else {
      console.error('Произошла ошибка: ' + data.error);
    }
  });
}


function save_category_avito(parent_id,glubina) {
  api_query("/api/index.php", "edit_avito_category_form", "save_category_avito").then(function (data) {
      if (data.status == "ok") {
          $("#edit_avito_category").html('');

          if (data.category) {
            var existingRow = $("#bind_row_" + data.category.id);
            
            if (existingRow.length) {
                // Заменяем существующую строку новыми данными
                existingRow.replaceWith('<tr id="bind_row_' + data.category.id + '" class="child_category_avito_' + parent_id + '">\
                                            <td style="padding-left:' + (glubina * 10) + 'px;">\
                                                <a onclick="create_child_category_avito(' + data.category.id + ', '+glubina+');" id="avito_category_znak_' + data.category.id + '">+</a> ' + data.category.name + '\
                                                <a style="color:red" onclick="edit_category_avito(' + parent_id + ',\'' + data.category.name + '\',' + data.category.id + ','+glubina+');"><img src="/new_images/edit.svg" style="width: 20px;"></a>\
                                            </td>\
                                        </tr>');
            } else {
                if(glubina !== 0){
                  glubina++;
                  var newRow = '<tr id="bind_row_' + data.category.id + '" class="child_category_avito_' + parent_id + '">\
                                  <td style="padding-left:' + (glubina * 10) + 'px;">\
                                      <a onclick="create_child_category_avito(' + data.category.id + ', '+glubina+');" id="avito_category_znak_' + data.category.id + '">+</a> ' + data.category.name + '\
                                      <a style="color:red" onclick="edit_category_avito(' + parent_id + ',\'' + data.category.name + '\',' + data.category.id + ','+glubina+');"><img src="/new_images/edit.svg" style="width: 20px;"></a>\
                                  </td>\
                              </tr>';

                              $("#bind_row_" + parent_id).after(newRow);
                }
                else{
                  // Добавляем новую строку, если существующей нет
                  var newRow = '<tr id="bind_row_' + data.category.id + '">\
                                  <td>\
                                      <a onclick="create_child_category_avito(' + data.category.id + ', '+glubina+');" id="avito_category_znak_' + data.category.id + '">+</a> ' + data.category.name + '\
                                      <a style="color:red" onclick="edit_category_avito(' + parent_id + ',\'' + data.category.name + '\',' + data.category.id + ');"><img src="/new_images/edit.svg" style="width: 20px;"></a>\
                                  </td>\
                              </tr>';

                  $("#avito_table").append(newRow);
                }
            }
          }
      } else {
          // Обработка ошибки
      }
  });
}


function edit_category_avito(parent_id=0,group_name='',category_id=0, glubina){
  var table='<form id="edit_avito_category_form">';
  table+='<div class="row"><input type="hidden" name="parent_id" value="'+parent_id+'"><input type="hidden" name="category_id" value="'+category_id+'">\
    <div class="col-sm-6">Наименование группы</div>\
    <div class="col-sm-6"><input type="text" name="name" class="form-control input-sm" value="'+group_name+'"></div>\
  </div>';
  table+='</form>';
  table+='<div class="row" style="padding: 10px">\
    <div class="col-sm-6"><button class="btn btn-primary btn-xs" onclick="save_category_avito('+parent_id+','+glubina+');">Сохранить</button></div>\
    <div class="col-sm-6"><button class="btn btn-default btn-xs pull-right" onclick="close_window();">Отмена</button></div>\
  </div>';
  create_window_centered_blue("edit_avito_category_div","Добавление/редактирование категории","edit_avito_category",table);
}

function create_avito_category(category_id = 0) {
  if ($("#avito_category_znak_" + category_id).html() == "-" && !force) {
    $(".child_category_avito_" + category_id).html('');
    $("#avito_category_znak_" + category_id).html('+');
    return;
  }
  var send = [];
  send['in_group'] = 0;
  api_query_array("/api/index.php", send, "get_avito_categorys").then(function (data) {
      if (data.status == "ok") {
          var len = data.avito_categorys.length;
          var table = '<button class="btn btn-primary" onclick="edit_category_avito(0,\'\',0,0)">Добавить категорию</button><br><br>';
          table += '<table id="avito_table" class="table table-bordered" style="font-size: 16px;">\
                          <thead>\
                              <tr>\
                                  <th>Название категории</th>\
                              </tr>\
                          </thead>\
                          <tbody>';

          for (let i = 0; i < len; i++) {
              table += '<tr id="bind_row_' + data.avito_categorys[i].id + '">\
                          <td>\
                              <a onclick="create_child_category_avito(' + data.avito_categorys[i].id + ', 1);" id="avito_category_znak_' + data.avito_categorys[i].id + '">+</a> ' + data.avito_categorys[i].name + '\
                              <a style="color:red" onclick="edit_category_avito(0,\'' + data.avito_categorys[i].name + '\',' + data.avito_categorys[i].id + ');"><img src="/new_images/edit.svg" style="width: 20px;"></a>\
                          </td>\
                      </tr>';
          }

          table += '</tbody></table>';
          create_window_centered_blue("new_avito_category_div", "Категории авито", "new_avito_category", table);
      } else {
          // Обработка ошибки или другие действия при неудачном запросе
      }
  });
}

function create_child_category_avito(parent_id, glubina, force = 0) {
  if ($("#avito_category_znak_" + parent_id).html() == "-" && !force) {
      $(".child_category_avito_" + parent_id).html('');
      $("#avito_category_znak_" + parent_id).html('+');
      return;
  }
  var send = [];
  send['in_group'] = parent_id;
  api_query_array("/api/index.php", send, "get_avito_categorys").then(function (data) {
      if (data.status == "ok") {
          var len = data.avito_categorys.length;
          glubina++;
          var table = '<tr class="child_category_avito_' + parent_id + '">\
                            <td style="padding-left:' + (glubina * 10) + 'px;">\
                                <button class="btn btn-xs btn-primary" onclick="edit_category_avito(' + parent_id + ',\'\',0,'+glubina+')">Добавить категорию</button>\
                            </td>\
                        </tr>';

            for (let i = 0; i < len; i++) {
                table += '<tr id="bind_row_' + data.avito_categorys[i].id + '" class="child_category_avito_' + parent_id + '">\
                      <td style="padding-left:' + (glubina * 10) + 'px;">\
                        <a onclick="create_child_category_avito(' + data.avito_categorys[i].id + ', '+glubina+');" id="avito_category_znak_' + data.avito_categorys[i].id + '">+</a> ' + data.avito_categorys[i].name + '\
                        <a style="color:red" onclick="edit_category_avito(0,\'' + data.avito_categorys[i].name + '\',' + data.avito_categorys[i].id + ','+glubina+');"><img src="/new_images/edit.svg" style="width: 20px;"></a>\
                      </td>\
                </tr>';
            }

          if (table !== '') {
              $("#bind_row_" + parent_id).after(table);
              $("#avito_category_znak_" + parent_id).html('-');
          }
      } else {
        var table = '<tr class="child_category_avito_' + parent_id + '">\
            td style="padding-left:' + (glubina * 10) + 'px;">\
                <button class="btn btn-xs btn-primary" onclick="edit_category_avito(' + parent_id + ')">Добавить категорию</button>\
            </td>\
        </tr>';
        $("#bind_row_" + parent_id).after(table);
        $("#avito_category_znak_" + parent_id).html('-');
      }
  });
}

function redefine_zakaz_details_statuses(){
  api_query("/api/index.php","some_form","get_zakaz_detail_statuses").then(function(data){
    //zakaz_detail_statuses=data;
    temp_zakaz_detail_statuses=data;
    var table='<table class="table table-hover"><tbody>';
    for (var i in data){
        table+='<tr><td>'+data[i].descr+'</td><td><input type="color" value="'+data[i].color+'"  onchange="save_temp_zakaz_detail_status('+i+',this.value)"></td><td id="zakaz_detail_statuses_color_num_'+i+'">'+data[i].color+'</td></tr>';
    }
    table+='<tr><td><button class="btn btn-sm btn-primary" onclick="save_user_zakaz_detail_statuses();">Сохранить</button></td><td colspan="2"><button class="btn btn-sm btn-default" onclick="redefine_zakaz_details_statuses()">Сбросить</button></td></tr>';
    table+='</tbody></table>';
    create_window_centered_blue("user_zakaz_details_statuses_config_div","Цветовая схема деталей в заказе","user_zakaz_details_statuses_config",table);
  });
}

function redefine_zakaz_statuses(){
  api_query("/api/index.php","some_form","get_zakaz_statuses").then(function(data){
    //zakaz_detail_statuses=data;
    temp_zakaz_statuses=data;
    var table='<table class="table table-hover"><tbody>';
    for (var i in data){
        table+='<tr><td>'+data[i].descr+'</td><td><input type="color" value="'+data[i].color+'" onchange="save_temp_zakaz_status('+i+',this.value)"></td><td id="zakaz_statuses_color_num_'+i+'">'+data[i].color+'</td></tr>';
    }
    table+='<tr><td><button class="btn btn-sm btn-primary" onclick="save_user_zakaz_statuses();">Сохранить</button></td><td colspan="2"><button class="btn btn-sm btn-default" onclick="redefine_zakaz_statuses()">Сбросить</button></td></tr>';
    table+='</tbody></table>';
    create_window_centered_blue("user_zakaz_statuses_config_div","Цветовая схема заказов","user_zakaz_statuses_config",table);
  });
}

function save_temp_zakaz_status(id,color){
  if(typeof(temp_zakaz_statuses[id])=="undefined") temp_zakaz_statuses[id]={};
  temp_zakaz_statuses[id].color=color.toUpperCase();
  $("#zakaz_statuses_color_num_"+id).text(temp_zakaz_statuses[id].color)
}

function save_temp_zakaz_detail_status(id,color){
  if(typeof(temp_zakaz_detail_statuses[id])=="undefined") temp_zakaz_detail_statuses[id]={};
  temp_zakaz_detail_statuses[id].color=color.toUpperCase();
  $("#zakaz_detail_statuses_color_num_"+id).text(temp_zakaz_detail_statuses[id].color)
}

function save_user_zakaz_statuses(){
  var send=[];
  send['user_statuses']=temp_zakaz_statuses;
  api_query_array("/api/index.php",send,"save_user_zakaz_statuses").then(function(data){
    if(data.status=="ok"){
      $("#user_zakaz_statuses_config").html('');
      get_zakaz_statuses();
    }
  })
}

function save_user_zakaz_detail_statuses(){
  var send=[];
  send['user_statuses']=temp_zakaz_detail_statuses;
  api_query_array("/api/index.php",send,"save_user_zakaz_detail_statuses").then(function(data){
    if(data.status=="ok"){
      $("#user_zakaz_details_statuses_config").html('');
      get_zakaz_detail_statuses();
    }
  })
}