function get_tax_types(){
 api_query("/api/index.php","some","get_tax_types").then(function(data){
    var datalen=data.tax_types.length;
    var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование ";
    table += "</th><th>Налоговая ставка</th><th>НДС</th><th>Дата создания</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
    	table += "<tr><td><div id='edit_tax_type_"+data.tax_types[i].id+"'></div>"+(i+1)+"</td><td>" + data.tax_types[i].name + "</td><td>"+data.tax_types[i].tax_rate+"</td>";
      if(data.tax_types[i].is_nds=="1"){
        table += '<td>ДА</td>';
      }
      else {
        table += '<td>НЕТ</td>';
      }
      table += "<td>"+data.tax_types[i].create_date+"</td>";
    	table += "<td><form id='delete_tax_type_"+data.tax_types[i].id+"'><input type=\"hidden\" name=\"tax_type_id\" value=\""+data.tax_types[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
    	table += "<a onclick=\"edit_tax_type('delete_tax_type_"+data.tax_types[i].id+"');\" title='Редактировать'><img src='/new_images/edit.svg' style='width:20px;'></a>";
    	table += "<a title='Удалить' ";
    	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить этот документ?\',function(result){ if(result) api_query('/api/index.php','delete_tax_type_"+data.tax_types[i].id+"','delete_tax_type').then(function(data){if(data.status=='ok') location.reload()});});\"><img src='/new_images/garbage.svg' style='width:20px;'></a>"
    	table += "</div></td>";
    	table += "</tr>";
    }
    table+="</tbody></table>";
    $("#dict_tax_types").html(table);
 });
}

function save_tax_type(form,close_div){
    var type=$('#'+form+' [name=type]').val();
    api_query("/api/index.php",form,"save_tax_type").done(function(data){
	if (data.status=="ok"){
	    $('#'+close_div).html('');
	    get_tax_types();
	}
    });
}

function edit_tax_type(tax_type_form){
  api_query("/api/index.php",tax_type_form,"get_tax_type").done(function(data){
    if (data.status=="ok"){
	var data_html='<form id="edit_tax_type_form_'+data.tax_type.id+'">\
	<div class="form-group row col-sm-12">\
	<label for="name" class="col-sm-4 col-form-label text-nowrap">Введите';
	data_html+=' наименование</label>\
	<div class="col-sm-8">\
	<input type="hidden" name="tax_type_id" value="'+data.tax_type.id+'">\
        <input type="text" class="form-control search_str" name="name" id="name" value="'+data.tax_type.name+'" placeholder="Введите наименование ';
	data_html+='"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="name" id="name_label" onclick="clear_search_order_text(\'name\');"></label></div>\
	</div>\
	<div class="form-group row col-sm-12">\
	<label for="tax_rate" class="col-sm-4 col-form-label text-nowrap">Налоговая ставка</label>\
	<div class="col-sm-8">\
	 <input type="text" class="form-control search_str" name="tax_rate" id="tax_rate" value="'+data.tax_type.tax_rate+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="tax_rate" id="tax_rate_label" onclick="clear_search_order_text(\'tax_rate\');"></label>\
	</div>\
	</div>\
	<div class="form-group row col-sm-12">\
	<label for="is_add" class="col-sm-4 col-form-label text-nowrap">Является НДС</label>\
	<div class="col-sm-1">\
	 <input type="checkbox" class="form-check-input form-control" name="is_add" id="is_add" ';
	 if(data.tax_type.is_nds==1) data_html+=' checked="checked"';
	data_html+='>\
	</div>\
	</div>\
	</form>\
	<button class="btn btn-primary" onclick="save_tax_type(\'edit_tax_type_form_'+data.tax_type.id+'\',\'edit_tax_type_'+data.tax_type.id+'\');">Сохранить</button>\
	<button class="btn btn-secondary pull-right" onclick="$(\'#edit_tax_type_'+data.tax_type.id+'\').html(\'\');">Закрыть</button>\
	';
	create_window("edit_tax_type_div_"+data.tax_type.id,"Изменение налоговой ставки","edit_tax_type_"+data.tax_type.id,data_html);
    }
    });
}

function add_new_tax_type(){
    var data_html='<form id="new_tax_type_form">\
    <div class="form-group row col-sm-12">\
    <label for="name" class="col-sm-4 col-form-label text-nowrap">Введите';
    data_html+=' наименование</label>\
    <div class="col-sm-8">\
     <input type="text" class="form-control search_str" name="name" id="name" value="" placeholder="Введите наименование';
    data_html+='"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="name" id="name_label" onclick="clear_search_order_text(\'name\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="descr" class="col-sm-4 col-form-label text-nowrap">налоговая ставка</label>\
    <div class="col-sm-8">\
     <input type="text" class="form-control search_str" name="tax_rate" id="tax_rate" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="tax_rate" id="tax_rate_label" onclick="clear_search_order_text(\'tax_rate\');"></label>\
    </div>\
    </div>\
	<div class="form-group row col-sm-12">\
	<label for="is_nds" class="col-sm-4 col-form-label text-nowrap">Является НДС</label>\
	<div class="col-sm-1">\
	 <input type="checkbox" class="form-check-input form-control" name="is_nds" id="is_nds" ';
	data_html+='>\
	</div>\
	</div>\
    </form>\
    <button class="btn btn-primary" onclick="save_tax_type(\'new_tax_type_form\');">Сохранить</button>\
    <button class="btn btn-secondary pull-right" onclick="$(\'#new_tax_type\').html(\'\');">Закрыть</button>\
    ';
    var header="Добавление новой налоговой ставки";
    create_window("add_new_tax_type",header,"new_tax_type",data_html)
}
