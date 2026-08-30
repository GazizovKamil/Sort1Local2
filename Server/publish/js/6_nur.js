var sklads=[];
var price_exports=[];

function get_sklads(){
 api_query("/api/index.php","some_form","get_sklads").then(function(data){
    var datalen=data.sklads.length;
    sklads=data.sklads;
    var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование</th><th>Адрес</th><th>Описание</th><th>Город</th><th>Позиций</th><th>Кол-во</th><th>Сумма закуп.</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
        table += "<tr><td>"+(i+1)+"<div id='stock_balances_"+data.sklads[i].id+"'></div>\
        <div id='edit_sklad_"+data.sklads[i].id+"'></div>\
        <div id='sklad_details_"+data.sklads[i].id+"'></div></td>\
        <td>" + data.sklads[i].name + "</td><td>"+data.sklads[i].address+"</td><td>"+data.sklads[i].descr+"</td><td>"+data.sklads[i].city_name+"</td><td>";
        if(parseFloat(data.sklads[i].sklad_positions)>0) table+=data.sklads[i].sklad_positions;
        else table+="0";
        table+="</td><td>";
        if(parseFloat(data.sklads[i].sklad_pos_count)) table+=data.sklads[i].sklad_pos_count;
        else table+="0";
        table+="</td>";
        table+='<td><a onclick="get_stock_balances('+data.sklads[i].id+')" title="Посмотреть историю склада">'+formatNumber(data.sklads[i].sklad_sum===null?0:parseFloat(data.sklads[i].sklad_sum).toFixed(0))+'</a></td>';
        table += "<td><form id='delete_sklad_"+data.sklads[i].id+"'><input type=\"hidden\" name=\"sklad_id\" value=\""+data.sklads[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
        table += "<a onclick=\"edit_sklad(\'delete_sklad_"+data.sklads[i].id+"\');\" title='Редактировать склад'><img src='/new_images/edit.svg' class='menuimg'></a>";
        table += " <a onclick=\"get_sklad_details1('sklad_form_"+data.sklads[i].id+"')\" title='Просмотреть детали'><img src='/new_images/file.svg' class='menuimg'></a>";
        table += "<form id='sklad_form_"+data.sklads[i].id+"' style='display:none'>\
        <input type='hidden' name='action' value='get_sklad_details'>\
        <input type='hidden' name='sklad_id' value='"+data.sklads[i].id+"'>\
        <input type='hidden' name='page' value='1'>\
        <input type='hidden' name='search' value=''>\
        <input type='hidden' name='search_brand' value=''>\
        <input type='hidden' name='search_location' value=''>\
        <input type='hidden' name='show_zero_sale_price' value='false'>\
        <input type='hidden' name='show_zero' value='false'>\
        <input type='hidden' name='show_only_zero' value='false'></form>";
        table += " <a title='Удалить склад' ";
        table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить ваш склад?\',function(result){ if(result) api_query('/api/index.php','delete_sklad_"+data.sklads[i].id+"','delete_sklad').then(function(data){if(data.status=='ok') get_sklads();});});\"><img src='/new_images/garbage.svg' class='menuimg'></a>"
        table += "</div></td>";
        table += "</tr>";
    }
    table+= "</tbody></table>";
    $("#sklads_list").html(table);
 });
}

function get_stock_balances(sklad_id){
  var send=[];
  send['sklad_id']=sklad_id;
  send['date_from']=$("#stock_balances_date_from").val();
  send['date_to']=$("#stock_balances_date_to").val();
  api_query_array("/api/index.php",send,"get_stock_balances").then(function(data){
    var table='\
    <div class="input-group input-group-sm pull-right">\
      <span id="stock_balances_date_from_label" class="input-group-addon">с: </span>\
      <input type="date" name="stock_balances_date_from" id="stock_balances_date_from" class="form-control" value="'+data.date_from+'">\
      <span id="stock_balances_date_to_label" class="input-group-addon">по: </span>\
      <input type="date" name="stock_balances_date_to" id="stock_balances_date_to" class="form-control" value="'+data.date_to+'">\
      <div class="input-group-btn">\
        <button type="button" class="btn btn-primary btn-sm" onclick="get_stock_balances('+sklad_id+');">Поиск</button>\
      </div>\
    </div>';
    var sb=data.stock_balances;
    table+='<table class="table table-hover"><thead><th>Дата</th><th>Позиций</th><th>Кол-во</th><th>Сумма</th><th>Сумма прод.</th></thead><tbody>';
    for (var i in sb){
      table+='<tr><td>'+sb[i].date+'</td><td>'+formatNumber(sb[i].position_count)+'</td><td>'+formatNumber(sb[i].details_count)+'</td><td><b>'+formatNumber(sb[i].sum)+'</b></td><td><b>'+formatNumber(sb[i].sale_sum)+'</b></td></tr>';
    }
    table+='</tbody></table>';
    create_window_centered_blue("stock_balances_"+sklad_id+"_div","История склада","stock_balances_"+sklad_id,table);
  });
}

function get_sklad_details(sklad_form){
 api_query("/api/index.php",sklad_form,"get_sklad_details").then(function(data){
    var datalen=data.sklad_details.length;
    var table="";
    //table += "<div class='row' style='padding:5px;'><div class='col-xs-4'><button class=\"btn btn-primary btn-sm\" onclick=\"add_new_detail("+$('#'+sklad_form+' [name=sklad_id]').val()+")\">Добавить деталь</button></div>";
    //table += "<div class='col-xs-4 pull-right'><div class='input-group input-group-sm'><input type='text' class='form-control'><span class='input-group-btn'><button class='btn btn-default' type='button'>Поиск</button></span></div></div>";
    //table += "</div><div id='add_new_sklad_detail'></div>";
    table += "<table class=\"table table-hover\"><thead><tr><th>№</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Цена покупки</th><th>Кол-во</th><th>Срок доставки</th><th>Цена продажи</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
	table += "<tr><td><div id='edit_sklad_detail_"+data.sklad_details[i].detail_id+"'></div>"+(i+1)+"</td><td>" + data.sklad_details[i].article + "</td><td>"+data.sklad_details[i].brand+"</td><td>"+data.sklad_details[i].name+"</td><td>"+data.sklad_details[i].price+"</td><td>"+data.sklad_details[i].count+"</td><td>"+data.sklad_details[i].time+"</td>";
	if (data.sklad_details[i].detail_markup>0) {
	    var price_markup=parseFloat(data.sklad_details[i].price)+parseFloat(data.sklad_details[i].price)/100*parseFloat(data.sklad_details[i].detail_markup);
	    table+="<td>"+price_markup.toFixed(2)+"</td>";
	}
	else {
	    var price_markup=parseFloat(data.sklad_details[i].price)+parseFloat(data.sklad_details[i].price)/100*parseFloat(data.sklad_details[i].default_markup);
	    table+="<td>"+price_markup.toFixed(2)+"</td>";
	}
	table += "<td><form id='delete_sklad_detail_"+data.sklad_details[i].detail_id+"'><input type=\"hidden\" name=\"detail_id\" value=\""+data.sklad_details[i].detail_id+"\"><input type=\"hidden\" name=\"sklad_id\" value=\""+data.sklad_details[i].sklad_id+"\"></form>";
	//table += "<div class='btn-group' style='display: flex;'><button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\"  onclick=\"edit_detail('delete_detail_"+data.sklad_details[i].detail_id+"');\"></button>";
	//table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
	//table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь со склада?\',function(result){ if(result) api_query('/api/index.php','delete_detail_"+data.sklad_details[i].detail_id+"','delete_sklad_detail').then(function(data){if(data.status=='ok') location.reload()});});\"></button>";
	//table += "</div>";
	table += "</td>";
	table += "</tr>";
    }
    table += "</tbody></table>";
    create_window("sklad_details_div_"+data.sklad_id,"Детали на складе "+data.sklad_name,"sklad_details_"+data.sklad_id,table)
 });
}

function b64toBlob(b64Data, contentType='', sliceSize=512) {
    const byteCharacters = atob(b64Data);
    const byteArrays = [];
  
    for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
      const slice = byteCharacters.slice(offset, offset + sliceSize);
  
      const byteNumbers = new Array(slice.length);
      for (let i = 0; i < slice.length; i++) {
        byteNumbers[i] = slice.charCodeAt(i);
      }
  
      const byteArray = new Uint8Array(byteNumbers);
      byteArrays.push(byteArray);
    }
  
    const blob = new Blob(byteArrays, {type: contentType});
    return blob;
  }

function get_sklad_csv(sklad_id){
    var send=new Array();
    send['add_markup']=$("#add_markup_"+sklad_id).prop("checked");
    send['use_reserv']=$("#use_reserv_"+sklad_id).prop("checked");
    send['get_zero_count']=$("#get_zero_count_"+sklad_id).prop("checked");
    send['get_only_zero_count']=$("#get_only_zero_count_"+sklad_id).prop("checked");
    send['sklad_id']=sklad_id;
    api_query_array("/api/index.php",send,"get_sklad_csv").then(function(data){
      //alert(data.export_file);

      var blob = b64toBlob(data.file); //new Blob([data.file]);
      var link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = "export.csv";
      link.click();
    });
  }

  function get_sklad_xls(sklad_id){
    var send=new Array();
    send['add_markup']=$("#add_markup_"+sklad_id).prop("checked");
    send['use_reserv']=$("#use_reserv_"+sklad_id).prop("checked");
    send['get_zero_count']=$("#get_zero_count_"+sklad_id).prop("checked");
    send['get_only_zero_count']=$("#get_only_zero_count_"+sklad_id).prop("checked");
    send['sklad_id']=sklad_id;
    api_query_array("/api/index.php",send,"get_sklad_xls").then(function(data){
      //alert(data.export_file);

      var blob = b64toBlob(data.file); //new Blob([data.file]);
      var link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = "export.xlsx";
      link.click();
    });
  }

  function get_price_list_csv(price_list_id){
    var send=new Array();
    send['add_markup']=$("#add_markup_price_"+price_list_id).prop("checked");
    //send['use_reserv']=$("#use_reserv_"+price_list_id).prop("checked");
    send['price_list_id']=price_list_id;
    api_query_array("/api/index.php",send,"get_price_list_csv").then(function(data){
      //alert(data.export_file);

      var blob = b64toBlob(data.file); //new Blob([data.file]);
      var link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = "export.csv";
      link.click();
    });
  }

  function get_price_list_xls(price_list_id){
    var send=new Array();
    send['add_markup']=$("#add_markup_price_"+price_list_id).prop("checked");
    //send['use_reserv']=$("#use_reserv_"+sklad_id).prop("checked");
    send['price_list_id']=price_list_id;
    api_query_array("/api/index.php",send,"get_price_list_xls").then(function(data){
      //alert(data.export_file);

      var blob = b64toBlob(data.file); //new Blob([data.file]);
      var link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = "export.xlsx";
      link.click();
    });
  }

function clear_unused_details(sklad_form,sklad_id){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Удаляю мусор со склада...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var send=[];
  send['sklad_id']=sklad_id;
  api_query_array("/api/index.php",send,"clear_unused_details").then(function(data){
    if(data.status=="ok"){
      $.unblockUI();
      get_sklad_details1(sklad_form);
    }
  });
}

function get_sklad_details1(sklad_form){
    $.blockUI({ css: { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .5, 
        color: '#fff'
        },
        message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
      });
    var sklad_id=$("#"+sklad_form+" input[name=sklad_id]").val();
 api_query("/api/index.php",sklad_form,"get_sklad_details").then(function(data){
    var datalen=data.sklad_details.length;
    var table="<div class='row' style='padding:5px; min-width:1000px;'><div class='col-xs-3'>"
    table+='<table><tbody><tr><td nowrap><a onclick="get_sklad_xls('+data.sklad_id+');"><img src="/new_images/excel_32.png" style="width: 30px;"></a>';
    table+='<a onclick="get_sklad_csv('+data.sklad_id+');"><img src="/new_images/csv_128.png" style="width: 30px;"></a></td>';
    table+='<td style="padding-left:5px;" nowrap><span><input type="checkbox" id="add_markup_'+data.sklad_id+'" name="add_markup_'+data.sklad_id+'"> выгружать с учетом наценок <br>';
    table+='<input type="checkbox" id="use_reserv_'+data.sklad_id+'" name="use_reserv_'+data.sklad_id+'"> не учитывать резерв<br>\
    <input type="checkbox" id="get_zero_count_'+data.sklad_id+'" name="get_zero_count_'+data.sklad_id+'"> Выгружать 0 остатки</span><br>\
    <input type="checkbox" id="get_only_zero_count_'+data.sklad_id+'" name="get_only_zero_count_'+data.sklad_id+'"> Выгружать только 0 остатки</span></td></tr></tbody></table>';
    //table+="<button class=\"btn btn-primary btn-sm\" onclick=\"add_new_sklad_detail("+$('#'+sklad_form+' [name=sklad_id]').val()+")\">Добавить деталь</button>";
    table+="</div>";
    /*table += '<span class="btn btn-success fileinput-button btn-sm">\
        <span>Загрузить файл</span>\
	<form id="fileupload">\
	<input type="hidden" name="sklad_id" value="'+data.sklad_id+'">\
	<input type="hidden" name="action" value="upload_file">\
        <input id="fileupload1" type="file" name="files[]" multiple>\
	</form>\
    </span>'; */
    table += "<div class='col-xs-3'><center><button class='btn btn-sm btn-danger' onclick='clear_unused_details(\""+sklad_form+"\","+data.sklad_id+");' title='Удаление ошибочных оприходований и оприходований, по которым удалены приходные документы'>Удалить мусор</button>\
    <button class='btn btn-sm btn-primary' onclick='get_sklad_doubles("+data.sklad_id+");' title='Показать задвоенные артикулы'>Задвоения</button>\
    </center><div id='sklad_doubles_"+data.sklad_id+"'></div></div>";
    table += "<div class='col-xs-6 pull-right'><div class='pull-right'><div class='input-group input-group-sm'>";
    //table+="<input type='checkbox' name='show_zero' class='form-control input-sm'>";
    table += "<span class='input-group-addon' id='sklad_search_zero_sale_price_"+data.sklad_id+"'>\
      <input type='checkbox' aria-label='Checkbox for following text input' name='show_zero_sale_price' onchange='$(\"#"+sklad_form+" [name=show_zero_sale_price]\").val($(\"#sklad_search_zero_sale_price_"+data.sklad_id+" [name=show_zero_sale_price]\").prop(\"checked\"));' ";
    if (data.hasOwnProperty('show_zero_sale_price') && data.show_zero_sale_price==1) table += " checked='checked'";
    table+="> Без частной наценки\
    </span>";
    table += "<span class='input-group-addon' id='sklad_search_zero_"+data.sklad_id+"'>\
      <input type='checkbox' aria-label='Checkbox for following text input' name='show_zero' onchange='$(\"#"+sklad_form+" [name=show_zero]\").val($(\"#sklad_search_zero_"+data.sklad_id+" [name=show_zero]\").prop(\"checked\"));' ";
    if (data.hasOwnProperty('show_zero') && data.show_zero==1) table += " checked='checked'";
    table+="> Показать 0 остатки\
    </span>";
    table += "<span class='input-group-addon' id='sklad_search_only_zero_"+data.sklad_id+"'>\
      <input type='checkbox' aria-label='Checkbox for following text input' name='show_only_zero' onchange='$(\"#"+sklad_form+" [name=show_only_zero]\").val($(\"#sklad_search_only_zero_"+data.sklad_id+" [name=show_only_zero]\").prop(\"checked\"));' ";
    if (data.hasOwnProperty('show_only_zero') && data.show_only_zero==1) table += " checked='checked'";
    table+="> только 0 остатки\
    </span></div>";
    table+="<div class='input-group input-group-sm'><span id='sklad_search_"+data.sklad_id+"' class='input-group-addon' style='padding: 0px 0px;'>\
    <input type='text' class='form-control input-sm' name='search' title='Артикул, наименование' placeholder='Артикул, наименование' style='width:160px;'";
    if (data.hasOwnProperty('search')) table+=" value='"+data.search+"'";
    else table+=" value=''";
    table += " onchange='$(\"#"+sklad_form+" [name=search]\").val($(\"#sklad_search_"+data.sklad_id+" [name=search]\").val());get_sklad_details1(\""+sklad_form+"\");'></span>";
    table+="<span id='sklad_search_brand_"+data.sklad_id+"' class='input-group-addon' style='padding: 0px 0px;'><input type='text' class='form-control input-sm' name='search_brand' style='width:110px;' placeholder='бренд'";
    if (data.hasOwnProperty('search_brand')) table+=" value='"+data.search_brand+"'";
    else table+=" value=''";
    table += " onchange='$(\"#"+sklad_form+" [name=search_brand]\").val(this.value);get_sklad_details1(\""+sklad_form+"\");'></span>";
    table+="<span id='sklad_search_location_"+data.sklad_id+"' class='input-group-addon' style='padding: 0px 0px;'><input type='text' class='form-control input-sm' name='search_location' style='width:154px;' placeholder='местоположение'";
    if (data.hasOwnProperty('search_location')) table+=" value='"+data.search_location+"'";
    else table+=" value=''";
    table += " onchange='$(\"#"+sklad_form+" [name=search_location]\").val(this.value);get_sklad_details1(\""+sklad_form+"\");'></span>";
    table += "</div>";
    table += "<span class='input-group-btn' style='padding: 2px;'><button class='btn btn-default btn-sm' type='button' onclick='$(\"#"+sklad_form+" [name=page]\").val(1);get_sklad_details1(\""+sklad_form+"\")'>Поиск</button></span>";

    table += "</div>";
    
    table += "</div></div>\
        <div id='add_new_sklad_detail'></div><div id='select_sklad_cols_"+data.sklad_id+"'></div>";
    table += '<div id="progress" class="progress col-sm-9"  style="display:none;">\
        <div class="progress-bar progress-bar-success"></div>\
    </div>';
    table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
    var x=0,y=0,xx=0,yy=0;
    if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
    else var selected_page=1;
    for (var i=1; i<=data.sklad_pages; i++){
	if(i>(selected_page+6) && i<(data.sklad_pages-1)){
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
		    table += '><a href="#" onclick="$(\'#'+sklad_form+' input[name=page]\').val(\''+i+'\');';
		    if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
		    table += 'get_sklad_details1(\''+sklad_form+'\')">...</a></li>';
		}
		if (x==1) xx++;
	}
	else {
	    if (y==1) {
		if (yy==0){
		    table += '<li';
		    table += '><a href="#" onclick="$(\'#'+sklad_form+' input[name=page]\').val(\''+i+'\');';
		    if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
		    table += 'get_sklad_details1(\''+sklad_form+'\')">...</a></li>';
		}
		if (y==1) yy++;
	    }
	    else {
		table += '<li';
		if(selected_page==i) table+= " class='active'";
		table += '><a href="#" onclick="$(\'#'+sklad_form+' input[name=page]\').val(\''+i+'\');';
		if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
		table += 'get_sklad_details1(\''+sklad_form+'\')">'+i+'</a></li>';
	    }
	}
    }
    table += '</ul></div>';
    table += "<table class=\"table table-hover\"><thead><tr><th></th><th>№</th><th>Мой код</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Цена</th><th>Цена част.</th><th>Кол-во</th><th>Мин. кол-во</th><th>Резервировано</th><th>Срок</th><th>Место</th><th>Акциз</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
        table += "<tr>";
        if(parseInt(data.sklad_details[i].invent_blocked)===1){
          table+='<td><img src="/images/warning-red.png" width="16px" title="Внимание: Товар заблокирован в инвентаризации!!!"></td>';
        }
        else {
          table+='<td></td>';
        }
        table+="<td><div id='edit_sklad_detail_"+data.sklad_details[i].detail_id+"'></div>"+((selected_page-1)*20+(i+1))+"</td><td>"+data.sklad_details[i].my_code+"</td>\
        <td>\
            <a onclick='show_detail_documents("+data.sklad_details[i].detail_id+","+data.sklad_id+",\"sklad\")' title='Посмотреть движение товара'>"+data.sklad_details[i].article+"</a>\
            <div id='show_sklad_detail_documents_"+data.sklad_details[i].detail_id+"'></div>\
        </td>";
        table += "<td>"+data.sklad_details[i].brand+"</td><td>"+data.sklad_details[i].name+"</td><td align='right' nowrap>"+formatNumber(data.sklad_details[i].price)+"</td>\
        <td align='right' nowrap>\
        <input type='text' id='sklad_detail_markup_price_"+data.sklad_details[i].detail_id+"' value='"+data.sklad_details[i].detail_markup_price+"' class='' size='4' onchange='save_sklad_detail_markup_price("+sklad_id+","+data.sklad_details[i].detail_id+",this.value);'></td>";
        table += "<td>"+data.sklad_details[i].count+"</td><td>"+data.sklad_details[i].min_count_must_have+"</td><td>"+data.sklad_details[i].reserved_count+"</td><td>"+data.sklad_details[i].time+"</td>";
        table += '<td nowrap>';
        if(typeof(data.detail_locations[data.sklad_details[i].detail_id])!="undefined")
        for(let j=0; j<data.detail_locations[data.sklad_details[i].detail_id].length; j++){
            table += data.detail_locations[data.sklad_details[i].detail_id][j].location+'<br>';
        }
        table += '</td>';
        table += '<td><input type="checkbox" '+(data.sklad_details[i].is_excise=="1"?"checked":"")+' disabled></td>';
        table += "<td><form id='delete_sklad_detail_"+data.sklad_details[i].detail_id+"'><input type=\"hidden\" name=\"detail_id\" value=\""+data.sklad_details[i].detail_id+"\"><input type=\"hidden\" name=\"sklad_id\" value=\""+data.sklad_details[i].sklad_id+"\"></form>";
        table += "<div class='btn-group' style='display: flex;'>\
            <a onclick=\"edit_sklad_detail('delete_sklad_detail_"+data.sklad_details[i].detail_id+"');\"><img src='/new_images/edit.svg' class='menuimg'></a>\
            <a onclick=\"delete_sklad_detail("+data.sklad_details[i].detail_id+","+data.sklad_id+");\"><img src='/new_images/garbage.svg' class='menuimg'></a>";
        //table += " &nbsp<a onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь со склада?\',function(result){ if(result) api_query('/api/index.php','delete_sklad_detail_"+data.sklad_details[i].detail_id+"','delete_sklad_detail').then(function(data){if(data.status=='ok') location.reload()});});\">\
        //    <img src='/new_images/garbage.svg' width='20px'></a>";
        table += "</div>";
        table += "</td>";
        table += "</tr>";
    }
    table += "</tbody></table>";
    if(datalen==0)
        table+='У вас на складе нет деталей. Детали на склад заводятся через создание приходных документов. <br> \
        Перейдите во вкладку Документы - Покупки, и создайте приходный документ с указанием склада. <br>\
        Далее добавьте в этот документ детали.<br>\
        <a onclick="load_module(10);">Перейти</a> ';
/*    table+="\
    <script>\
	file_uploader();\
    </script>"; */
    create_window_centered_blue("sklad_details_div_"+data.sklad_id,"Детали на складе "+data.sklad_name,"sklad_details_"+data.sklad_id,table);
    $.unblockUI();
 });
}

function delete_sklad_detail(detail_id,sklad_id){
  var send=[];
  send['sklad_id']=sklad_id;
  send['detail_id']=detail_id;
  api_query_array("/api/index.php",send,"delete_sklad_detail").then(function(data){
    if(data.status="ok"){
      get_sklad_details1("sklad_form_"+sklad_id);
    }
  });
}

function save_sklad_detail_markup_price(sklad_id,detail_id,markup_price){
  var send=[];
  send['sklad_id']=sklad_id;
  send['detail_id']=detail_id;
  send['detail_markup_price']=markup_price;
  api_query_array("/api/index.php",send,"save_sklad_detail_markup_price").then(function(data){

  });
}

function clear_search_order_text(input_id){
    $('#'+input_id).val('');
    //runTextFilterOrd();
          }

function change_default_markup(){
    var def_markup=$("#price_type option[value="+$("#price_type").val()+"]").attr('markup');
    $("#default_markup").val(def_markup);
}

function edit_sklad(sklad_form){
  api_query("/api/index.php",sklad_form,"get_sklad").done(function(data1){
    if (data1.status=="ok") var data=data1.sklad;
    if (data1.status=="err") { bootbox.alert("Невозможно редактировать"); return 0;}
    var data_html='\
	<form id="edit_sklad_form_'+data.id+'" class="col-sm-12">\
	    <input type="hidden" name="sklad_id" value="'+data.id+'">\
	    <input type="hidden" name="company_id" value="'+data.company_id+'">\
	<div class="form-group row">\
	    <label for="sklad_name" class="col-sm-4 col-form-label">Наименование склада</label>\
	    <div class="col-sm-8">\
		<input type="text" class="form-control search_str" id="name" onclick="this.select();" placeholder="Наименование склада" name="name" value=\''+data.name.replace("'","\"")+'\'><label style="position: absolute; top: 0.8em; right: 1.2em;" for="name" id="name_label" onclick="clear_search_order_text(\'name\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="sklad_address" class="col-sm-4 col-form-label">Адрес склада</label>\
	    <div class="col-sm-8 pull-right">\
		<input type="text" class="form-control search_str" id="address" onclick="this.select();" placeholder="Адрес склада" name="address" value="'+data.address+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="address" id="address_label" onclick="clear_search_order_text(\'address\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="sklad_descr" class="col-sm-4 col-form-label">Описание</label>\
	    <div class="col-sm-8 pull-right">\
		<input type="text" class="form-control search_str" id="descr" onclick="this.select();" placeholder="Описание" name="descr" value="'+data.descr+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="descr" id="descr_label" onclick="clear_search_order_text(\'descr\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="price_type" class="col-sm-4 col-form-label">Тип цен</label>\
	    <div class="col-sm-8 pull-right">\
		<select class="form-control" id="price_type" name="price_type" onchange="change_default_markup();">\
		<option value="0">Не выбран</option>';
    var pt_len=data1.price_types.length;
    var show_default_markup=1;
    for(var i=0; i<pt_len; i++){
    	data_html+='<option value="'+data1.price_types[i].id+'" markup="'+data1.price_types[i].proc+'"';
    	if(data.price_type==data1.price_types[i].id) {
    	    data_html+=' selected="selected" ';
    	    if(parseInt(data1.price_types[i].type)>2) show_default_markup=0;
    	}
    	data_html+='>'+data1.price_types[i].descr+'</option>';
    }
    data_html+='    </select>\
	    </div>\
	</div>\
  <div class="form-group row">\
	    <label for="topology_id" class="col-sm-4 col-form-label">Топология</label>\
	    <div class="col-sm-8 pull-right">\
		<select class="form-control" id="topology_id" name="topology_id">\
		<option value="0">Не выбран</option>';
    var top_len=data1.topologys.length;
    //var show_default_markup=1;
    for(var i=0; i<top_len; i++){
    	data_html+='<option value="'+data1.topologys[i].id+'"';
    	if(data.topology_id==data1.topologys[i].id) {
    	    data_html+=' selected="selected" ';
    	    if(typeof(data1.price_types[i])!="undefined" && parseInt(data1.price_types[i].type)>2) show_default_markup=0;
    	}
    	data_html+='>'+data1.topologys[i].name+'</option>';
    }
    data_html+='    </select>\
	    </div>\
	</div>';
	//if(show_default_markup){
	    data_html+='<div class="form-group row">\
	    <label for="default_markup" class="col-sm-4 col-form-label">Наценка по умолчанию в %</label>\
	    <div class="col-sm-8 pull-right">\
		<input type="text" class="form-control search_str" onclick="this.select();" id="default_markup" placeholder="Наценка по умолчанию в %" name="default_markup" value="'+data.default_markup+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="default_markup" id="default_markup_label" onclick="clear_search_order_text(\'default_markup\');"></label>\
	    </div>\
	    </div>';
	//}
    data_html+='<div class="form-group row">\
	    <label for="sklad_phone" class="col-sm-4 col-form-label">Телефон</label>\
	    <div class="col-sm-8 pull-right">\
		<input type="text" class="form-control search_str" onclick="this.select();" id="sklad_phone" placeholder="Телефон склада/магазина" name="sklad_phone" value="'+data.phone+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="sklad_phone" id="sklad_phone_label" onclick="clear_search_order_text(\'sklad_phone\');"></label>\
	    </div>\
	    </div>';
    data_html+='<div class="form-group row">\
	    <label for="sklad_phone" class="col-sm-4 col-form-label">Время работы склада</label>\
	    <div class="col-sm-8 pull-right">\
		<input type="text" class="form-control search_str" onclick="this.select();" id="sklad_work_time" placeholder="Пн-Пт: c .. до .. Сб-Вс: c .. до .." name="sklad_work_time" value="'+data.work_time+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="sklad_phone" id="sklad_phone_label" onclick="clear_search_order_text(\'sklad_phone\');"></label>\
	    </div>\
	    </div>';
    data_html+='<div class="form-group row">\
	    <label for="sklad_phone" class="col-sm-4 col-form-label">Координаты склада (широта,долгота)</label>\
	    <div class="col-sm-8 pull-right">\
		<input type="text" class="form-control search_str" onclick="this.select();" id="sklad_coordinate" placeholder="0,0" name="sklad_coordinate" value="'+data.coordinate+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="sklad_phone" id="sklad_phone_label" onclick="clear_search_order_text(\'sklad_phone\');"></label>\
	    </div>\
	    </div>';
	data_html+='<div class="form-group row">\
	    <label for="sklad_city" class="col-sm-4 col-form-label">Город</label>\
	    <div class="col-sm-8 pull-right">\
		<input type="text" class="form-control search_str" id="city_name" placeholder="Город. Начните набирать..." name="city_name" value="'+data.city_name+'" onkeyup="get_city();"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="city_name" id="city_name_label" onclick="clear_search_order_text(\'city_name\');"></label>\
		<input type="hidden" name="city_id" id="city_id" value="'+data.city_id+'">\
		<div id="city_div"></div>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="punkt_vydachi" class="col-sm-4 col-form-label">Является пунктом выдачи</label>\
	    <div class="col-sm-8">\
		<input type="checkbox" class="search_str" id="punkt_vydachi" name="punkt_vydachi"';
	    if(data.punkt_vydachi==1) data_html+=' checked="checked"';
        data_html+=' onchange="check_sklad_checkboxes();">    </div>\
	</div>\
  <div class="form-group row">\
	    <label for="fullfilment" class="col-sm-4 col-form-label">Является FULLFILMENT складом</label>\
	    <div class="col-sm-8">\
		<input type="checkbox" class="search_str" id="fullfilment" name="fullfilment"';
	    if(data.fullfilment==1) data_html+=' checked="checked"';
        data_html+='>    </div>\
    </div>\
  <div class="form-group row">\
	    <label for="sklad_use_in_search" class="col-sm-4 col-form-label">Участвует в поиске</label>\
	    <div class="col-sm-8">\
		<input type="checkbox" class="search_str" id="sklad_use_in_search" name="sklad_use_in_search"';
	    if(data.sklad_use_in_search==1) data_html+=' checked="checked"';
        data_html+='  onchange="check_sklad_checkboxes();">    </div>\
	</div>\
  <div class="form-group row">\
	    <label for="sklad_use_in_jetparts" class="col-sm-4 col-form-label">Участвует в поиске jetparts</label>\
	    <div class="col-sm-8">\
		<input type="checkbox" class="search_str" id="sklad_use_in_jetparts" name="sklad_use_in_jetparts"';
	    if(data.sklad_use_in_jetparts==1) data_html+=' checked="checked"';
        data_html+='  onchange="check_sklad_checkboxes();">    </div>\
	</div>\
	</form>\
	';
    data_html+='<button class="btn btn-primary" onclick="save_sklad(\'edit_sklad_form_'+data.id+'\');">Сохранить</button>\
		<button class="btn btn-secondary pull-right" onclick="$(\'#edit_sklad_'+data.id+'\').html(\'\');">Закрыть</button>\
    ';
    $("[id^=edit_sklad]").html('');
    create_window_centered_blue("edit_sklad_div_"+data.id,"Изменение склада","edit_sklad_"+data.id,data_html)
    });
}

function add_new_sklad(){
    var send=new Array();
    send['price_type']=2;
    api_query_array("/api/index.php",send,"get_price_types").then(function(data1){
        api_query("/api/index.php","some_form","get_sklad_topologys").then(function(data){
            var data_html='\
            <form id="edit_sklad_form_0" class="col-sm-12">\
                <input type="hidden" name="sklad_id" value="0">\
                <input type="hidden" name="company_id" value="">\
            <div class="form-group row">\
                <label for="sklad_name" class="col-sm-4 col-form-label">Наименование склада</label>\
                <div class="col-sm-8">\
                <input type="text" onclick="this.select();" class="form-control search_str" id="name" placeholder="Наименование склада" name="name" value=\'\'><label style="position: absolute; top: 0.8em; right: 1.2em;" for="name" id="name_label" onclick="clear_search_order_text(\'name\');"></label>\
                </div>\
            </div>\
            <div class="form-group row">\
                <label for="sklad_address" class="col-sm-4 col-form-label">Адрес склада</label>\
                <div class="col-sm-8 pull-right">\
                <input type="text" onclick="this.select();" class="form-control search_str" id="address" placeholder="Адрес склада" name="address" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="address" id="address_label" onclick="clear_search_order_text(\'address\');"></label>\
                </div>\
            </div>\
            <div class="form-group row">\
                <label for="sklad_descr" class="col-sm-4 col-form-label">Описание</label>\
                <div class="col-sm-8 pull-right">\
                <input type="text" onclick="this.select();" class="form-control search_str" id="descr" placeholder="Описание" name="descr" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="descr" id="descr_label" onclick="clear_search_order_text(\'descr\');"></label>\
                </div>\
            </div>\
            <div class="form-group row">\
            <label for="price_type" class="col-sm-4 col-form-label">Тип цен</label>\
            <div class="col-sm-8 pull-right">\
            <select class="form-control" id="price_type" name="price_type" onchange="change_default_markup();">\
            <option value="0">Не выбран</option>';
            var pt_len=data1.price_types.length;
            var show_default_markup=1;
            for(var i=0; i<pt_len; i++){
                data_html+='<option value="'+data1.price_types[i].id+'" markup="'+data1.price_types[i].proc+'"';
                /*if(data.price_type==data1.price_types[i].id) {
                    data_html+=' selected="selected" ';
                    if(parseInt(data1.price_types[i].type)>2) show_default_markup=0;
                }*/
                data_html+='>'+data1.price_types[i].descr+'</option>';
            }
            data_html+='    </select>\
                </div>\
            </div>\
            <div class="form-group row">\
            <label for="topology_id" class="col-sm-4 col-form-label">Топология</label>\
            <div class="col-sm-8 pull-right">\
            <select class="form-control" id="topology_id" name="topology_id">\
            <option value="0">Не выбран</option>';
            var top_len=data.topologys.length;
            //var show_default_markup=1;
            for(var i=0; i<top_len; i++){
                data_html+='<option value="'+data.topologys[i].id+'"';
                data_html+='>'+data.topologys[i].name+'</option>';
            }
            data_html+='    </select>\
                </div>\
            </div>';
            data_html+='<div class="form-group row">\
                <label for="default_markup" class="col-sm-4 col-form-label">Наценка по умолчанию в %</label>\
                <div class="col-sm-8 pull-right">\
                <input type="text" onclick="this.select();" class="form-control search_str" id="default_markup" placeholder="Наценка по умолчанию в %" name="default_markup" value="0"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="default_markup" id="default_markup_label" onclick="clear_search_order_text(\'default_markup\');"></label>\
                </div>\
            </div>';
            data_html+='<div class="form-group row">\
              <label for="sklad_phone" class="col-sm-4 col-form-label">Телефон</label>\
              <div class="col-sm-8 pull-right">\
            <input type="text" class="form-control search_str" onclick="this.select();" id="sklad_phone" placeholder="Телефон склада/магазина" name="sklad_phone"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="sklad_phone" id="sklad_phone_label" onclick="clear_search_order_text(\'sklad_phone\');"></label>\
              </div>\
              </div>';
            data_html+='<div class="form-group row">\
              <label for="sklad_phone" class="col-sm-4 col-form-label">Время работы склада</label>\
              <div class="col-sm-8 pull-right">\
            <input type="text" class="form-control search_str" onclick="this.select();" id="sklad_work_time" placeholder="Пн-Пт: c .. до .. Сб-Вс: c .. до .." name="sklad_work_time"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="sklad_phone" id="sklad_phone_label" onclick="clear_search_order_text(\'sklad_phone\');"></label>\
              </div>\
              </div>';
            data_html+='<div class="form-group row">\
              <label for="sklad_phone" class="col-sm-4 col-form-label">Координаты склада (широта,долгота)</label>\
              <div class="col-sm-8 pull-right">\
            <input type="text" class="form-control search_str" onclick="this.select();" id="sklad_coordinate" placeholder="0,0" name="sklad_coordinate"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="sklad_phone" id="sklad_phone_label" onclick="clear_search_order_text(\'sklad_phone\');"></label>\
              </div>\
              </div>\
            <div class="form-group row">\
                <label for="sklad_city" class="col-sm-4 col-form-label">Город</label>\
                <div class="col-sm-8 pull-right">\
                <input type="text" onclick="this.select();" class="form-control search_str" id="city_name" placeholder="Город. Начните набирать..." name="city_name" value="" onkeyup="get_city();"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="city_name" id="city_name_label" onclick="clear_search_order_text(\'city_name\');"></label>\
                <input type="hidden" name="city_id" id="city_id" value="">\
                <div id="city_div"></div>\
                </div>\
            </div>\
            <div class="form-group row">\
                <label for="punkt_vydachi" class="col-sm-4 col-form-label">Является пунктом выдачи</label>\
                <div class="col-sm-8">\
                <input type="checkbox" class="" id="punkt_vydachi" name="punkt_vydachi" onchange="check_sklad_checkboxes();">\
                </div>\
            </div>\
            <div class="form-group row">\
                <label for="fullfilment" class="col-sm-4 col-form-label">Является FULLFILMENT складом</label>\
                <div class="col-sm-8">\
                <input type="checkbox" class="" id="fullfilment" name="fullfilment"';
            //if(data.fullfilment==1) data_html+=' checked="checked"';
            data_html+='>    </div>\
            </div>\
            <div class="form-group row">\
                <label for="sklad_use_in_search" class="col-sm-4 col-form-label">Участвует в поиске</label>\
                <div class="col-sm-8">\
                <input type="checkbox" class="" id="sklad_use_in_search" name="sklad_use_in_search" onchange="check_sklad_checkboxes();">\
                </div>\
            </div>\
            <div class="form-group row">\
                <label for="sklad_use_in_jetparts" class="col-sm-4 col-form-label">Участвует в поиске jetparts</label>\
                <div class="col-sm-8">\
                <input type="checkbox" class="" id="sklad_use_in_jetparts" name="sklad_use_in_jetparts" onchange="check_sklad_checkboxes();">\
                </div>\
            </div>\
            </form>\
            ';

            data_html+='<button class="btn btn-primary" onclick="save_sklad(\'edit_sklad_form_0\');">Сохранить</button>\
                <button class="btn btn-secondary pull-right" onclick="$(\'#edit_sklad_0\').html(\'\');">Закрыть</button>\
            ';
            $("[id^=edit_sklad]").html('');
            create_window("edit_sklad_div_0","Добавление нового склада","edit_sklad_0",data_html);
        });
    });
}

function check_sklad_checkboxes(){
    if(!$("#punkt_vydachi").prop("checked")){
      if($("#sklad_use_in_search").prop("checked")){
        bootbox.alert("Нельзя искать на складе с которого невозможно выдать");
        $("#sklad_use_in_search").prop("checked",false);
      }
      if($("#sklad_use_in_jetparts").prop("checked")) {
        bootbox.alert("Нельзя искать на складе с которого невозможно выдать");
        $("#sklad_use_in_jetparts").prop("checked",false);
      }
    }
    else{
      if($("#sklad_use_in_jetparts").prop("checked")) {
        api_query("/api/index.php","some_form","check_included_jetparts").then(function(data){
          if(data.status=="err"){
            $("#sklad_use_in_jetparts").prop("checked",false);
          }
        });
      }
    }
}

function save_sklad(sklad_form){
    api_query('/api/index.php',sklad_form,'save_sklad').done(function(data){
	if(data.status=="ok"){
	    $('#edit_sklad_'+$("#"+sklad_form+' input[name=sklad_id]').val()).html('');
	    get_sklads();
	}
    });

}

function change_new_detail_brand(brand_id,detail_id,brand_name,detail_name){
    $("#add_new_detail_form input[name=brand_id]").val(brand_id);
    $("#add_new_detail_form input[name=detail_id]").val(detail_id);
    $("#add_new_detail_form input[name=brand]").val(brand_name);
    $("#add_new_detail_form input[name=name]").val(detail_name);
    $("#brand_helper").hide();
}

/* function get_brands(){
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
	    var table="<table class='table table-hover'><tr><th>Артикул</th><th>Брэнд</th><th>Наименование</th></tr><tbody>";
	    for (var i=0; i<datalen; i++){
		table+="<tr style='cursor:pointer' onclick='change_new_detail_brand("+data[i].brand_id+","+data[i].detail_id+",\""+data[i].brand_name+"\",\""+data[i].name+"\");'><td>"+data[i].article+"</td><td>"+data[i].brand_name+"</td><td>"+data[i].name+"</td></tr>";
	    }
	    table+="</tbody></table>";
	    $('#brand_helper_content').html(table);
	}
	else {
	    $('#brand_helper_content').html("деталь не найдена в базе данных");
	}
	//$('#brand_helper').draggable();
	//$('#brand_helper').toggle();
    });
    //return false;
} */

function save_new_sklad_detail_to_base(sklad_id){
    api_query("/api/index.php","add_new_detail_form","save_sklad_detail").then(function(data){
	     get_sklad_details1("sklad_form_"+sklad_id);
    });
}


function save_new_price_list_detail_to_base(price_list_id){
    api_query("/api/index.php","add_new_detail_form","save_price_list_detail").then(function(data){
	get_price_list_details("price_list_form_"+price_list_id);
    });
}

function change_detail_markup(){
    $("#detail_markup").val(Math.round(($("#detail_markup_price").val()/$("#price").val()-1)*100));
}

function change_detail_markup_price(){
    $("#detail_markup_price").val(($("#price").val()*(1+$("#detail_markup").val()/100)).toFixed(2));
}

function edit_sklad_detail(detail_form){
  api_query("/api/index.php",detail_form,"get_sklad_detail").done(function(data1){
    var data=data1.sklad_details[0];
    $('#add_new_detail_div').toggle();
    //$('#add_new_detail_header').html("Добавление детали:");
    var new_detail_content="<div style='width:850px'><form id='add_new_detail_form'><table class='table'>";
    new_detail_content+="<tr><td>артикул: </td>";
    new_detail_content+="<td><input type='text' name='article' class='form-control search_str' id='addArticle' onclick='this.select();' value='"+data.article+"' onchange='get_brands();' readonly><label style='position: absolute; top: 3.5em; right: 1.2em;' for='addArticle' id='addArticle_label' onclick='clear_search_order_text(\"addArticle\");'></label><input type='hidden' name='detail_id' value='"+data.detail_id+"'><input type='hidden' name='sklad_id' value='"+data.sklad_id+"'></td></tr>";
    new_detail_content+="<tr><td>брэнд: </td><td><input type='text' class='form-control search_str' id='addBrand' onclick='this.select();' name='brand' value='"+data.brand+"' readonly><label style='position: absolute; top: 7.2em; right: 1.2em;' for='addBrand' id='addBrand_label' onclick='clear_search_order_text(\"addBrand\");'></label><input type='hidden' name='brand_id' value='"+data.brand_id+"'>";
    new_detail_content+="</td></tr>";
    new_detail_content+="<tr><td>наименование: </td><td><input type='text' class='form-control search_str' id='addName' onclick='this.select();' name='name' value='"+data.name+"'><label style='position: absolute; top: 11.9em; right: 1.2em;' for='addName' id='addName_label' onclick='clear_search_order_text(\"addName\");'></label></td></tr>";
    new_detail_content+="<tr><td>цена: </td><td><input type='text' class='form-control search_str' onclick='this.select();' name='price' id='price' value='"+data.price+"'><label style='position: absolute; top: 15.6em; right: 1.2em;' for='price' id='price_label' onclick='clear_search_order_text(\"price\");'></label></td></tr>";
    new_detail_content+="<tr><td>кол-во: </td><td><input type='text' class='form-control search_str' id='addCount' onclick='this.select();' name='count' value='"+data.count+"' readonly></td></tr>";
    new_detail_content+="<tr><td>зарезервировано под заказы: </td><td><input type='text' class='form-control search_str' id='reservedCount' onclick='this.select();' name='reserved_count' value='"+data.reserved_count+"'></td></tr>";
    new_detail_content+="<tr><td>Минимальный остаток: </td><td><input type='text' id='min_count_must_have' class='form-control search_str' onclick='this.select();' name='min_count_must_have' value='"+data.min_count_must_have+"'><label style='position: absolute; top: 26.8em; right: 1.2em;' for='min_count_must_have' id='min_count_must_have_label' onclick='clear_search_order_text(\"min_count_must_have\");'></label></td></tr>";
    new_detail_content+="<tr><td>срок доставки: </td><td><input type='text' name='time' id='addTime' class='form-control search_str' onclick='this.select();' value='"+data.time+"'><label style='position: absolute; top: 30.5em; right: 1.2em;' for='addTime' id='addTime_label' onclick='clear_search_order_text(\"addTime\");'></label></td></tr>";
    new_detail_content+="<tr><td>Наценка общая: </td><td><input type='text' class='form-control search_str' id='default_markup' onclick='this.select();' name='default_markup' value='"+data.default_markup+"' readonly></td></tr>";
    new_detail_content+="<tr><td>Наценка частная(приоритетнее): </td><td><input type='text' id='detail_markup' class='form-control search_str' onclick='this.select();' name='detail_markup' id='detail_markup' value='"+data.detail_markup+"' onchange='change_detail_markup_price();'><label style='position: absolute; top: 38.0em; right: 1.2em;' for='detail_markup' id='detail_markup_label' onclick='clear_search_order_text(\"detail_markup\");'></label></td></tr>";
    new_detail_content+="<tr><td>Цена частная в руб.: </td><td><input type='text' id='detail_markup_price' class='form-control search_str' onclick='this.select();' name='detail_markup_price' id='detail_markup_price' value='"+parseFloat(data.detail_markup_price).toFixed(2)+"' onchange='change_detail_markup();'><label style='position: absolute; top: 41.8em; right: 1.2em;' for='detail_markup_price' id='detail_markup_price_label' onclick='clear_search_order_text(\"detail_markup_price\");'></label></td></tr>";
    new_detail_content+="<tr><td>акцизный товар: </td><td><input type='checkbox' class='' id='is_excise' name='is_excise'";
    if(data.is_excise=="1") new_detail_content+=" checked='checked'";
    new_detail_content+="></td></tr>";
    new_detail_content+="<tr><td>Блокирован инвентаризацией: </td><td><input type='checkbox' class='' id='invent_blocked' name='invent_blocked'";
    if(data.invent_blocked=="1") new_detail_content+=" checked='checked'";
    new_detail_content+="></td></tr>";
    new_detail_content+="<tr><td>размеры детали: </td><td><input type='text' class='form-control input-sm' id='detail_size' name='detail_size' value='"+data.detail_size+"'></td></tr>";
    new_detail_content+="<tr><td>EAN13: </td><td>\
    <div class='input-group input-group-sm col-sm-12'>\
    <span>\
      <input type='text' id='detail_ean13' class='form-control search_str input-sm' onclick='this.select();' name='detail_ean13' id='detail_ean13' value='"+data.ean13+"'>\
      <label style='position: absolute; top: 36.5em; right: 1.3em;' for='detail_ean13' id='detail_ean13_label' onclick='clear_search_order_text(\"detail_ean13\");'></label>\
    </span>\
    <span class='input-group-btn'>\
    <button class='btn btn-default btn-sm' type='button' onclick='get_ean13_of_detail("+data.detail_id+",\"sklad\",\"ean13\");' title='Получить код'>...</button>\
    </span></div></td></tr>";
    new_detail_content+="<tr><td>Мой код: </td><td><input type='text' id='detail_my_code' class='form-control search_str' onclick='this.select();' name='detail_my_code' id='detail_my_code' value='"+data.my_code+"'><label style='position: absolute; top: 51.8em; right: 1.2em;' for='detail_my_code' id='detail_my_code_label' onclick='clear_search_order_text(\"detail_my_code\");'></label></td></tr>";
    new_detail_content+="<tr><td>Товарная группа: </td>";
    /*<td><div id='sel_sklad_detail_groups_list_0'></div><input type='text' id='sklad_detail_group_name' class='form-control search_str' onclick='select_detail_groups(0,0,0,\"sklad\");' name='detail_group_name' value='"+data.detail_group_name+"'><label style='position: absolute; top: 55.5em; right: 1.2em;' for='detail_group_name' id='detail_group_name_label' onclick='clear_search_order_text(\"detail_group_name\");clear_search_order_text(\"detail_group_id\");'></label>\
    <input type='hidden' name='detail_group_id' id='sklad_detail_group_id' value='"+data.detail_group_id+"'></td></tr>";*/
    new_detail_content+="<td><div id='sel_sklad_detail_groups_list_0'></div>";
    new_detail_content+="<a onclick='select_detail_groups(0,0,0,\"sklad\","+data.detail_id+", "+data.sklad_id+");' class='pull-right'> + </a>";
    if(typeof(data1.details_groups[data.detail_id])!="undefined" && data1.details_groups[data.detail_id].length>0){
        new_detail_content+="<table class='table'><thead><tr><th>Наименование</th><th></th></tr></thead><tbody>";
        for(var i of data1.details_groups[data.detail_id]){
            new_detail_content+='<tr><td>'+i.group_name+' </td>';
            new_detail_content+='<td>';
            //new_detail_content+='<a onclick="edit_sklad_detail_detail_group();"><img src="/new_images/edit.svg" width="15px;"></a>';
            new_detail_content+='<a onclick="bootbox.confirm(\'Вы точно хотите удалить запись?\',function(result){ if(result) delete_detail_group_detail('+i.detail_group_id+','+i.detail_id+'); });"><img src="/new_images/garbage.svg" width="15px;"></a></td>';
            new_detail_content+='</tr>';
        }
        new_detail_content+="</tbody></table>";
    }
    new_detail_content+="<tr><td>Местоположение: </td>";
    new_detail_content+="<td>";
    new_detail_content+="<a onclick='set_sklad_detail_location("+data1.sklad_id+","+data.detail_id+","+data1.sklad_topology+",\""+detail_form+"\")' class='pull-right'> + </a>";
    if(data1.detail_locations.length>0){
        new_detail_content+="<table class='table'><thead><tr><th>Место</th><th>кол-во</th><th></th></tr></thead><tbody>";
        for(var i=0; i<data1.detail_locations.length; i++){
            new_detail_content+='<tr><td>'+data1.detail_locations[i].location+' </td>';
            new_detail_content+='<td> '+data1.detail_locations[i].count+'</td>';
            new_detail_content+='<td>';
            new_detail_content+='<a onclick="edit_detail_location('+data1.sklad_id+','+data.detail_id+','+data1.sklad_topology+',\''+data1.detail_locations[i].location+'\',\''+detail_form+'\','+data1.detail_locations[i].id+');"><img src="/new_images/edit.svg" width="15px;"></a>';
            new_detail_content+='<a onclick="bootbox.confirm(\'Вы точно хотите удалить запись?\',function(result){ if(result) delete_detail_location('+data1.sklad_id+','+data.detail_id+',\''+data1.detail_locations[i].location+'\',\''+detail_form+'\'); });"><img src="/new_images/garbage.svg" width="15px;"></a></td>';
            new_detail_content+='</tr>';
        }
        new_detail_content+="</tbody></table>";
    }

    new_detail_content+="</td></tr>";
    new_detail_content+="<tr><td></td><td><button type='button' class='btn btn-secondary' id='save_new_detail' onclick='save_new_sklad_detail_to_base("+data.sklad_id+");'>Сохранить</button></td></tr>";
    new_detail_content+="</table></form><div id='detail_topology_select' style='position: absolute; top: 150px; left: 100px;'></div></div>";
    //$('#new_detail_header_content').html("Изменение данных детали: ");
    //$('#add_new_detail_content').html(new_detail_content);
    //$('#add_new_detail_div').draggable();
    $("[id^=edit_sklad_detail_]").html('');
    create_window_centered_blue("edit_sklad_detail_div_"+data.detail_id,"Изменение данных детали: ","edit_sklad_detail_"+data.detail_id,new_detail_content);
 });
}

function edit_detail_location(sklad_id,detail_id,topology_id,location,detail_form,location_id,document_details_id,znak){
  var send=new Array();
  send['sklad_id']=sklad_id;
  send['detail_id']=detail_id;
  send['location']=location;
  if(typeof(document_details_id)!="undefined"){
    var table='<form id="document_detail_location">';
  }
  else {
    var table='<form id="detail_location">';
  }
  table+='<input type="hidden" name="sklad_id" value="'+sklad_id+'">';
  table+='<input type="hidden" name="detail_id" value="'+detail_id+'">';
  table+='<input type="hidden" name="topology_id" value="'+topology_id+'">';
  table+='<input type="hidden" name="location_id" value="'+location_id+'">';
  if(typeof(document_details_id)!="undefined")
        table+='<input type="hidden" name="document_details_id" value="'+document_details_id+'">';
  table+='<input type="hidden" name="subaction" value="edit">';
  table+='<table class="table"><thead><tr><th></th><th></th></tr></thead><tbody>';

  api_query_array("/api/index.php",send,"get_sklad_detail_location").then(function(data){
      var level_location=data.sklad_detail_location.location;
      data.topology_levels.forEach(function(item,index){
          table+='<tr><td>'+item.name+'</td><td>';
          table+='<select name="'+item.name+'">';
          if(typeof(level_location)!="undefined"){
            var level_location_splitted=explode(item.delimiter,level_location,2);
            level_location=level_location_splitted[1];
          }
          else { // для случая когда размерность бывшая меньше новой размерности
            var level_location_splitted=new Array();
            level_location_splitted[0]="";
          }
          switch(item.type){
              case "1":
                  for (var i=item.first;i<parseInt(item.first)+parseInt(item.len);i++){
                      if(level_location_splitted[0]==i)
                        table+='<option value="'+i+'" selected="selected">'+i+'</option>';
                      else
                        table+='<option value="'+i+'">'+i+'</option>';
                  }
                  break;
              case "2":
                  var options=range(item.first,item.len);
                  //alert(options);
                  options.forEach(function(item){
                      if(level_location_splitted[0]==item)
                        table+='<option value="'+item+'" selected="selected">'+item+'</option>';
                      else
                        table+='<option value="'+item+'">'+item+'</option>';
                  });
                  break;
              case "3":
                  var options=range(item.first,item.len);
                  //alert(options);
                  options.forEach(function(item){
                    if(level_location_splitted[0]==item)
                      table+='<option value="'+item+'" selected="selected">'+item+'</option>';
                    else
                      table+='<option value="'+item+'">'+item+'</option>';
                  });
                  break;
          }
          table+='</select></td></tr>';
      });
      table+='<tr><td>Кол-во:</td><td><input name="count" value="'+data.sklad_detail_location.count+'" ></td></tr>';
      
      if(typeof(document_details_id)!="undefined"){
        table+='<tr><td><button type="button" class="btn btn-success" onclick="save_sklad_detail_location(\'document_detail_location\',\''+detail_form+'\',\''+znak+'\');">Сохранить</button></td>';
        table+='<td><button type="button" class="btn btn-default pull-right" onclick="close_window(\'document_detail_topology_select\');">Отмена</button></td></tr>';
        
      }
      else {
        table+='<tr><td><button type="button" class="btn btn-success" onclick="save_sklad_detail_location(\'detail_location\',\''+detail_form+'\');">Сохранить</button></td>';
        table+='<td><button type="button" class="btn btn-default pull-right" onclick="close_window(\'detail_topology_select\');">Отмена</button></td></tr>';

      }
      table+='</tbody></table></form>';
      if(typeof(document_details_id)!="undefined")
        create_window("detail_topology_select_div","Выберите местоположение детали на складе","document_detail_topology_select",table);
      else
        create_window("detail_topology_select_div","Выберите местоположение детали на складе","detail_topology_select",table);
  });
}

function delete_detail_location(sklad_id,detail_id,location,detail_form){
  var send=new Array();
  send['sklad_id']=sklad_id;
  send['detail_id']=detail_id;
  send['location']=location;
  api_query_array("/api/index.php",send,"delete_sklad_detail_location").then(function(data){
    if(data.status=="ok"){
      edit_sklad_detail(detail_form);
    }
  });
}

function set_sklad_detail_location(sklad_id,detail_id,topology_id,detail_form,document_details_id,znak){
    var send=new Array();
    send['sklad_id']=sklad_id;
    send['detail_id']=detail_id;
    send['topology_id']=topology_id;
    if(typeof(document_details_id)!="undefined"){
        var table='<form id="document_detail_location">';
    }
    else {
        var table='<form id="detail_location">';
    }
    table+='<input type="hidden" name="sklad_id" value="'+sklad_id+'">';
    table+='<input type="hidden" name="detail_id" value="'+detail_id+'">';
    table+='<input type="hidden" name="topology_id" value="'+topology_id+'">';
    if(typeof(document_details_id)!="undefined")
        table+='<input type="hidden" name="document_details_id" value="'+document_details_id+'">';
    table+='<input type="hidden" name="subaction" value="add">';
    table+='<table class="table"><thead><tr><th></th><th></th></tr></thead><tbody>';
    api_query_array("/api/index.php",send,"get_sklad_topology").then(function(data){
        if(typeof(data.topology_levels)=="undefined" || data.topology_levels.length==0) {
            bootbox.alert("к складу не привязана топология, не могу добавить расположение деталей");
            return 0;
        }
        data.topology_levels.forEach(function(item,index){
            table+='<tr><td>'+item.name+'</td><td>';
            table+='<select name="'+item.name+'">';
            switch(item.type){
                case "1":
                    for (var i=item.first;i<parseInt(item.first)+parseInt(item.len);i++){
                        table+='<option value="'+i+'">'+i+'</option>';
                    }
                    break;
                case "2":
                    var options=range(item.first,item.len);
                    //alert(options);
                    options.forEach(function(item){
                        table+='<option value="'+item+'">'+item+'</option>';
                    });
                    break;
                case "3":
                    var options=range(item.first,item.len);
                    //alert(options);
                    options.forEach(function(item){
                        table+='<option value="'+item+'">'+item+'</option>';
                    });
                    break;
            }
            table+='</select></td></tr>';
        });
        table+='<tr><td>Кол-во:</td><td><input name="count" value=""></td></tr>';
        if(typeof(document_details_id)!="undefined"){
            table+='<tr><td><button type="button" class="btn btn-success" onclick="save_sklad_detail_location(\'document_detail_location\',\''+detail_form+'\',\''+znak+'\');">Сохранить</button></td>';
            table+='<td><button type="button" class="btn btn-default pull-right" onclick="close_window(\'document_detail_topology_select\');">Отмена</button></td></tr>';
            
        }
        else {
            table+='<tr><td><button type="button" class="btn btn-success" onclick="save_sklad_detail_location(\'detail_location\',\''+detail_form+'\');">Сохранить</button></td>';
            table+='<td><button type="button" class="btn btn-default pull-right" onclick="close_window(\'detail_topology_select\');">Отмена</button></td></tr>';

        }   
        table+='</tbody></table></form>';
        if(typeof(document_details_id)!="undefined")
            create_window("document_detail_topology_select_div","Выберите местоположение детали на складе","document_detail_topology_select",table);
        else
            create_window("detail_topology_select_div","Выберите местоположение детали на складе","detail_topology_select",table);
    });
}

function save_sklad_detail_location(form_id,detail_form,znak){
    api_query("/api/index.php",form_id,"save_sklad_detail_location").then(function(data){
      if(data.status=="ok"){
        if(typeof(znak)=="undefined"){
            close_window('detail_topology_select');
            edit_sklad_detail(detail_form);
        }
        else {
            close_window('document_detail_topology_select');
            edit_document_detail(detail_form,znak);
        }
      }
    });
    return false;
}

function edit_price_list_detail(detail_form){
  api_query("/api/index.php",detail_form,"get_price_list_detail").done(function(data1){
    var data=data1.price_list_details[0];
    $('#add_new_price_list_detail').html('');
    $('[id^=edit_price_list_detail').html('');
    //$('#add_new_detail_header').html("Добавление детали:");
    var new_detail_content="<form id='add_new_detail_form'><table class='table'>";
    new_detail_content+="<tr><td>артикул: </td>";
    new_detail_content+="<td><input type='text' onclick='this.select();' id='addArticle' class='form-control search_str' name='article' value='"+data.article+"' onchange='get_brands2();'><input type='hidden' name='detail_id' value='"+data.detail_id+"'><input type='hidden' name='price_list_id' value='"+data.price_list_id+"'></td></tr>";
    new_detail_content+="<tr><td>брэнд: </td><td><input type='text' id='addBrand' onclick='this.select();' class='form-control search_str' name='brand' value='"+data.brand+"'><input type='hidden' name='brand_id' value='"+data.brand_id+"'>";
    new_detail_content+="<div id='brand_helper' style='position: absolute; display:none; border: 1px solid #337ab7'>";
    new_detail_content+=" <div id='brand_helper_header' style='padding: 5px; background-color: #2e6da4; color: #fff;'>Выберите брэнд детали &nbsp";
    new_detail_content+="  <button class='close pull-right' onclick='$(\"#brand_helper\").hide(); return false;'><span>&times;</span></button>";
    new_detail_content+=" </div>";
    new_detail_content+=" <div id='brand_helper_content' style='background-color: #eee; padding: 10px;'>";
    new_detail_content+=" </div>";
    new_detail_content+="</div></td></tr>";
    new_detail_content+="<tr><td>наименование: </td><td><input type='text' id='addName' onclick='this.select();' class='form-control search_str' name='name' value='"+data.name+"'></td></tr>";
    new_detail_content+="<tr><td>цена: </td><td><input type='text' onclick='this.select();' class='form-control search_str' name='price' id='price' value='"+data.price+"'></td></tr>";
    new_detail_content+="<tr><td>кол-во: </td><td><input type='text' id='addCount' onclick='this.select();' class='form-control search_str' name='count' value='"+data.count+"'></td></tr>";
    new_detail_content+="<tr><td>срок доставки: </td><td><input type='text' id='addTime' onclick='this.select();' class='form-control search_str' name='time' value='"+data.time+"'></td></tr>";
    new_detail_content+="<tr><td>Наценка общая: </td><td><input type='text' id='addNaz' onclick='this.select();' class='form-control search_str' name='default_markup' value='"+data.default_markup+"' readonly></td></tr>";
    new_detail_content+="<tr><td>Наценка частная(приоритетнее): </td><td><input type='text' onclick='this.select();' class='form-control search_str' name='detail_markup' id='detail_markup' value='"+data.detail_markup+"' onchange='change_detail_markup_price();'></td></tr>";
    new_detail_content+="<tr><td>Наценка частная в руб.: </td><td><input type='text' onclick='this.select();' class='form-control search_str' name='detail_markup_price' id='detail_markup_price' value='"+parseFloat(data.detail_markup_price).toFixed(2)+"' onchange='change_detail_markup();'></td></tr>";
    new_detail_content+="<td>Товарная группа: </td><td><div id='sel_price_list_detail_groups_list_0'></div><input type='text' id='price_list_detail_group_name' class='form-control search_str' onclick='select_detail_groups(0,0,0,\"price_list\", "+data.sklad_id+");' name='detail_group_name' value='"+data.detail_group_name+"'><label style='position: absolute; top: 55.5em; right: 1.2em;' for='detail_group_name' id='detail_group_name_label' onclick='clear_search_order_text(\"detail_group_name\");clear_search_order_text(\"detail_group_id\");'></label>\
    <input type='hidden' name='detail_group_id' id='price_list_detail_group_id' value='"+data.detail_group_id+"'></td>";
    new_detail_content+="<tr><td>примечание: </td><td><input type='text' id='price_list_detail_descr' onclick='this.select();' class='form-control search_str' name='descr' value='"+data.descr+"'></td></tr>";
    new_detail_content+="<tr><td></td><td><button type='button' class='btn btn-secondary' id='save_new_detail' onclick='save_new_price_list_detail_to_base("+data.price_list_id+");'>Сохранить</button></td></tr>";
    new_detail_content+="</table></form>";
    //$('#new_detail_header_content').html("Изменение данных детали: ");
    //$('#add_new_detail_content').html(new_detail_content);
    //$('#add_new_detail_div').draggable();
    create_window("edit_price_list_detail_div_0","Изменение данных детали: ","edit_price_list_detail_0",new_detail_content);
    //$("#edit_price_list_detail_0").css("position","absolute");
    let win_width=parseInt($("#price_list_details_div_"+data.price_list_id).width());
    $("#edit_price_list_detail_div_0").css("left",(win_width/2-100)+"px");
 });
}

function change_new_detail_brand2(brand_id,detail_id,brand_name,detail_name,article){
    if(typeof(article)!="undefined" && article.length>2) $("#add_new_detail_form input[name=article]").val(article);
    $("#add_new_detail_form input[name=brand_id]").val(brand_id);
    $("#add_new_detail_form input[name=detail_id]").val(detail_id);
    $("#add_new_detail_form input[name=brand]").val(brand_name);
    $("#add_new_detail_form input[name=name]").val(detail_name);
    $("#brand_helper").html('');
    $("#save_new_detail").removeAttr('disabled');
}

function get_brands2(){
    $("#save_new_detail").attr('disabled',"disabled");
    var send=new Array();
    send['article']=$('#add_new_detail_form input[name=article]').val();
    api_query_array("/api/index.php",send,"get_brands_online").then(function(data){
      if (data!=null){
          var datalen=data.brands.length;
        if(datalen==1){
          change_new_detail_brand2(data.brands[0].brand_id,data.brands[0].detail_id,data.brands[0].brand,typeof(data.brands[0].name)=="string"?data.brands[0].name.replace("'","").replace('"',''):"");
        }
        else {
            var table="<table class='table table-hover'><tr><th>Артикул</th><th>Брэнд</th><th>Наименование</th></tr>";
            for (var i=0; i<datalen; i++){
                  table+="<tr style='cursor:pointer' onclick='change_new_detail_brand2("+data.brands[i].brand_id+","+data.brands[i].detail_id+",\""+data.brands[i].brand+"\",\""+(typeof(data.brands[0].name)=="string"?data.brands[0].name.replace("'","").replace('"',''):"")+"\");'><td>"+data.brands[i].article+"</td><td>"+data.brands[i].brand+"</td><td>"+data.brands[i].name+"</td></tr>";
            }
            table+="</table>";
            if(datalen>1)
            create_window('brand_helper_div',"Выберите брэнд детали","brand_helper",table);
          else
            $("#save_new_detail").removeAttr('disabled');
        }

      }
      else {
          var table="деталь не найдена в базе данных";
        $("#save_new_detail").removeAttr('disabled');
      }
  });
}

function add_new_detail(sklad_id){
    $('#add_new_detail_div').toggle();
    //$('#add_new_detail_header').html("Добавление детали:");
    var new_detail_content="<form id='add_new_detail_form'><table class='table'>";
    new_detail_content+="<tr><td>артикул: </td>";
    new_detail_content+="<td><input type='text' class='form-control search_str' id='addArticle' name='article' value='' onchange='get_brands2();'><label style='position: absolute; top: 3.9em; right: 1.2em;' for='addArticle' id='addArticle_label' onclick='clear_search_order_text(\'addArticle\');'></label><input type='hidden' name='detail_id' value=''><input type='hidden' name='sklad_id' value='"+sklad_id+"'></td></tr>";
    new_detail_content+="<tr><td>брэнд: </td><td><input type='text' id='addBrand' class='form-control search_str' name='brand' value=''><label style='position: absolute; top: 6.8em; right: 1.2em;' for='addBrand' id='addBrand_label' onclick='clear_search_order_text(\'addBrand\');'></label><input type='hidden' name='brand_id' value=''>";
    new_detail_content+="<div id='brand_helper' style='position: absolute; display:none; border: 1px solid #337ab7'>";
    new_detail_content+=" <div id='brand_helper_header' style='padding: 5px; background-color: #2e6da4; color: #fff;'>Выберите брэнд детали &nbsp";
    new_detail_content+="  <button class='close pull-right' onclick='$(\"#brand_helper\").hide(); return false;'><span>&times;</span></button>";
    new_detail_content+=" </div>";
    new_detail_content+=" <div id='brand_helper_content' style='background-color: #eee; padding: 10px;'>";
    new_detail_content+=" </div>";
    new_detail_content+="</div></td></tr>";
    new_detail_content+="<tr><td>наименование: </td><td><input type='text' id='addName' class='form-control search_str' name='name' value=''><label style='position: absolute; top: 10.8em; right: 1.2em;' for='addName' id='addName_label' onclick='clear_search_order_text(\'addName\');'></label></td></tr>";
    new_detail_content+="<tr><td>цена: </td><td><input type='text' class='form-control search_str' id='price' name='price' value=''><label style='position: absolute; top: 14.8em; right: 1.2em;' for='price' id='price_label' onclick='clear_search_order_text(\'price\');'></label></td></tr>";
    new_detail_content+="<tr><td>кол-во: </td><td><input type='text' class='form-control search_str' id='addCount' name='count' value=''><label style='position: absolute; top: 18.8em; right: 1.2em;' for='addCount' id='addCount_label' onclick='clear_search_order_text(\'addCount\');'></label></td></tr>";
    new_detail_content+="<tr><td>срок доставки: </td><td><input type='text' class='form-control search_str' id='addTime' name='time' value=''><label style='position: absolute; top: 22.8em; right: 1.2em;' for='addTime' id='addTime_label' onclick='clear_search_order_text(\'addTime\');'></label></td></tr>";
    new_detail_content+="<tr><td>Наценка частная: </td><td><input type='text' class='form-control search_str' id='detail_markup' name='detail_markup' value=''><label style='position: absolute; top: 26.8em; right: 1.2em;' for='detail_markup' id='detail_markup_label' onclick='clear_search_order_text(\'detail_markup\');'></label></td></tr>";
    new_detail_content+="<tr><td></td><td><button type='button' class='btn btn-secondary' class='form-control' id='save_new_detail' onclick='save_new_sklad_detail_to_base("+sklad_id+");'>Сохранить</button></td></tr>";
    new_detail_content+="</table></form>";
    create_window("add_new_sklad_detail_div","Добавление детали: ","add_new_sklad_detail",new_detail_content);
}

function add_new_price_list_detail(price_list_id){
    $('#add_new_price_list_detail').html('');
    $('[id^=edit_price_list_detail').html('');
    $('#add_new_price_list_detail_div').toggle();
    //$('#add_new_detail_header').html("Добавление детали:");
    var new_detail_content="<form id='add_new_detail_form'><table class='table'>";
    new_detail_content+="<tr><td>артикул: </td>";
    new_detail_content+="<td><input type='text' class='form-control search_str' id='addArticle' name='article' value='' onchange='get_brands2();'><label style='position: absolute; top: 4.3em; right: 1.2em;' for='addArticle' id='addArticle_label' onclick='clear_search_order_text(\'addArticle\');'></label><input type='hidden' name='detail_id' value=''><input type='hidden' name='price_list_id' value='"+price_list_id+"'></td></tr>";
    new_detail_content+="<tr><td>брэнд: </td><td><input type='text' id='addBrand' class='form-control search_str' name='brand' value=''><label style='position: absolute; top: 8.6em; right: 1.2em;' for='addBrand' id='addBrand_label' onclick='clear_search_order_text(\'addBrand\');'></label><input type='hidden' name='brand_id' value=''>";
    new_detail_content+="<div id='brand_helper' >";
    //new_detail_content+=" <div id='brand_helper_header' style='padding: 5px; background-color: #2e6da4; color: #fff;'>Выберите брэнд детали &nbsp";
    //new_detail_content+="  <button class='close pull-right' onclick='$(\"#brand_helper\").hide(); return false;'><span>&times;</span></button>";
    //new_detail_content+=" </div>";
    //new_detail_content+=" <div id='brand_helper_content' style='background-color: #eee; padding: 10px;'>";
    //new_detail_content+=" </div>";
    new_detail_content+="</div></td></tr>";
    new_detail_content+="<tr><td>наименование: </td><td><input type='text' id='addName' class='form-control search_str' name='name' value=''><label style='position: absolute; top: 12.7em; right: 1.2em;' for='addName' id='addName_label' onclick='clear_search_order_text(\'addName\');'></label></td></tr>";
    new_detail_content+="<tr><td>цена: </td><td><input type='text' class='form-control search_str' id='price' name='price' value=''><label style='position: absolute; top: 16.9em; right: 1.2em;' for='price' id='price_label' onclick='clear_search_order_text(\'price\');'></label></td></tr>";
    new_detail_content+="<tr><td>кол-во: </td><td><input type='text' class='form-control search_str' id='addCount' name='count' value=''><label style='position: absolute; top: 21.2em; right: 1.2em;' for='addCount' id='addCount_label' onclick='clear_search_order_text(\'addCount\');'></label></td></tr>";
    new_detail_content+="<tr><td>срок доставки: </td><td><input type='text' class='form-control search_str' id='addTime' name='time' value=''><label style='position: absolute; top: 25.5em; right: 1.2em;' for='addTime' id='addTime_label' onclick='clear_search_order_text(\'addTime\');'></label></td></tr>";
    new_detail_content+="<tr><td>Наценка частная: </td><td><input type='text' class='form-control search_str' id='detail_markup' name='detail_markup' value=''><label style='position: absolute; top: 29.8em; right: 1.2em;' for='detail_markup' id='detail_markup_label' onclick='clear_search_order_text(\'detail_markup\');'></label></td></tr>";
    new_detail_content+="<tr><td></td><td><button type='button' class='btn btn-secondary' id='save_new_detail' onclick='save_new_price_list_detail_to_base("+price_list_id+");'>Сохранить</button></td></tr>";
    new_detail_content+="</table></form>";
    create_window("add_new_price_list_detail_div","Добавление детали: ","add_new_price_list_detail",new_detail_content);
}


function get_price_lists(){
  var defer=$.Deferred();
 api_query("/api/index.php","some_form","get_price_lists").then(function(data){
    var datalen=data.price_lists.length;
    var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование</th><th>Поставщик</th><th>Город</th><th>наценка</th><th>Позиций</th><th>Кол-во</th><th>Дата создания</th><th>Обновлен</th><th>Статус</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
      table += "<tr><td><div id='price_list_details_"+data.price_lists[i].id+"'></div>"+(i+1)+"<div id='edit_price_list_"+data.price_lists[i].id+"'></div></td>\
      <td>" + data.price_lists[i].name + "</td>\
      <td>"+data.price_lists[i].company_name+"</td>\
      <td>"+data.price_lists[i].city_name+"</td>\
      <td>"+data.price_lists[i].default_markup+"</td>";
      //if(typeof(data.price_det_pos[data.price_lists[i].id])!="undefined"){
          table +="<td nowrap style='text-align: right'>"+formatNumber(parseInt(data.price_lists[i].positions))+"</td>";
          table +="<td nowrap style='text-align: right'>"+formatNumber(parseInt(data.price_lists[i].pos_count))+"</td>";
          //table +="<td nowrap>"+formatNumber(parse(data.price_det_pos[data.price_lists[i].id].pos_sum).toFixed(2))+"</td>";
      //}
      //else{
      //    table +="<td style='text-align: right'>0</td>";
      //    table +="<td style='text-align: right'>0</td>";
          //table +="<td>0</td>";
      //}
      table +="<td>"+convertTZ(data.price_lists[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>\
      <td>"+convertTZ(data.price_lists[i].update_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>\
      <td>"+(data.price_lists[i].status=="1"?"<span style='color:green'>Включен</span>":"<span style='color:red'>Выключен</span>")+"</td>";

      table += "<td><form id='delete_price_list_"+data.price_lists[i].id+"'><input type=\"hidden\" name=\"price_list_id\" value=\""+data.price_lists[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
      table += "<a onclick=\"edit_price_list(\'delete_price_list_"+data.price_lists[i].id+"\');\" title='Редактировать'><img src='/new_images/edit.svg' class='menuimg'></a>";
      table += " <a onclick=\"get_price_list_details('price_list_form_"+data.price_lists[i].id+"')\" title='Просмотреть детали'><img src='/new_images/file.svg' class='menuimg'></a>";
      table += "<form id='price_list_form_"+data.price_lists[i].id+"' style='display:none'><input type='hidden' name='action' value='get_price_list_details'>";
      table += "<input type='hidden' name='price_list_id' value='"+data.price_lists[i].id+"'><input type='hidden' name='page' value='1'><input type='hidden' name='search' value=''></form>";
      table += " <a title='Удалить прайс-лист' ";
      table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить ваш склад?\',function(result){ if(result) api_query('/api/index.php','delete_price_list_"+data.price_lists[i].id+"','delete_price_list').then(function(data){if(data.status=='ok') get_price_lists()});});\"><img src='/new_images/garbage.svg' class='menuimg'></a>"
      table += "</div></td>";
      table += "</tr>";
    }
    table+= "</tbody></table>";
    $("#price_list_list").html(table);
    defer.resolve(data);
 });
 return defer.promise();
}

function edit_price_list(price_list_form,target=""){
  api_query("/api/index.php",price_list_form,"get_price_list").done(function(data1){
    if (data1.status=="ok") var data=data1.price_list;
    else {
	bootbox.alert(data1.err);
	return 0;
    }
    var data_html='<form id="price_list_form_'+data.id+'" style="width:900px;">\
    <div class="form-group row">\
      <label for="sklad_name" class="col-sm-4 col-form-label text-nowrap">Наименование</label>\
      <div class="col-xs-8">\
        <input type="hidden" name="price_list_id" id="price_list_id" value="'+data.id+'">\
        <input type="text" class="form-control search_str" onclick="this.select();" name="name" id="price_list_name" value="'+data.name+'" placeholder="Введите наименование прайс-листа"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="price_list_name" id="price_list_name_label" onclick="clear_search_order_text(\'price_list_name\');"></label>\
        <div id="price_list_status_list"></div>\
      </div>\
    </div>\
    <div class="form-group row">\
      <label for="company_name" class="col-sm-4 col-form-label text-nowrap">Выберите поставщика</label>\
      <div class="col-sm-8">\
        <input type="hidden" name="company_id" id="company_id" value="'+data.company_id+'">\
        <input type="text" class="form-control" name="company_name" id="price_company_name" onclick="this.value=\'\'; $(\'#company_id_plus\').val(\'0\'); select_dealer_sklad();" value="'+data.company_name.replace(/\"/g,"")+'"\
        placeholder="Нажмите чтобы выбрать" onkeyup="select_dealer_sklad();">\
        <div id="dealer_list_new"></div>\
      </div>\
    </div>\
    <div class="form-group row">\
      <label for="currency" class="col-sm-4 col-form-label text-nowrap">Валюта</label>\
      <div class="col-sm-8">\
        <input type="hidden" id="currency" name="currency" id="currency" value="'+data.currency+'">\
        <input type="text" class="form-control" id="currency_name" name="currency_name" value="'+data.currency_name+'" readonly onclick="select_currency();" placeholder="Нажмите чтобы выбрать">\
        <div id="currency_list"></div>\
      </div>\
    </div>\
    <div class="form-group row">\
      <label for="timeplus" class="col-sm-4 col-form-label text-nowrap">Доставка +n дней</label>\
      <div class="col-sm-8">\
        <input type="text" class="form-control search_str" onclick="this.select();" id="timeplus" placeholder="" name="timeplus" value="'+data.timeplus+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="timeplus" id="timeplus_label" onclick="clear_search_order_text(\'timeplus\');"></label>\
      </div>\
    </div>\
	<div class="form-group row">\
	    <label for="price_type" class="col-sm-4 col-form-label">Тип цен</label>\
	    <div class="col-sm-8 pull-right">\
		<select class="form-control" id="price_type" name="price_type" onchange="change_default_markup();">\
		<option value="0">Не выбран</option>';
    var pt_len=data1.price_types.length;
    for(var i=0; i<pt_len; i++){
      data_html+='<option value="'+data1.price_types[i].id+'" markup="'+data1.price_types[i].proc+'"';
      if(data.price_type==data1.price_types[i].id) data_html+=' selected="selected" ';
      data_html+='>'+data1.price_types[i].descr+'</option>';
    }
    data_html+='    </select>\
	    </div>\
	</div>\
  <div class="form-group row">\
    <label for="default_brand" class="col-sm-4 col-form-label text-nowrap">Бренд по умолчанию</label>\
    <div class="col-sm-8">\
	    <input type="text" class="form-control search_str" onclick="this.select();" id="default_brand" name="default_brand" value="'+data.default_brand+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="default_brand" id="default_brand_label" onclick="clear_search_order_text(\'default_brand\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="default_markup" class="col-sm-4 col-form-label text-nowrap">Наценка по умолчанию</label>\
    <div class="col-sm-8">\
	    <input type="text" class="form-control search_str" onclick="this.select();" id="default_markup" name="default_markup" value="'+data.default_markup+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="default_markup" id="default_markup_label" onclick="clear_search_order_text(\'default_markup\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
	  <label for="price_list_city" class="col-sm-4 col-form-label">Город</label>\
	  <div class="col-sm-8 pull-right">\
		  <input type="text" class="form-control search_str" onclick="this.select();" id="city_name" placeholder="Город. Начните набирать..." name="city_name" value="'+data.city_name+'" onkeyup="get_city();"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="city_name" id="city_name_label" onclick="clear_search_order_text(\'city_name\');"></label>\
		  <input type="hidden" name="city_id" id="city_id" value="'+data.city_id+'">\
		  <div id="city_div"></div>\
	  </div>\
  </div>\
  <div id="price_get_type">\
    <div class="form-group row">\
      <label for="price_list_get_type" class="col-sm-4 col-form-label">Тип обновления</label>\
      <div class="col-sm-8">\
        <input type="radio" name="price_get_type" id="price_get_type_incoming_from" value="1" '+(data.price_get_type==1?"checked":"")+' onclick="change_price_get_type('+data.id+')">Присылают на почту<br>\
        <input type="radio" name="price_get_type" id="price_get_type_going_to" value="2" '+(data.price_get_type==2?"checked":"")+' onclick="change_price_get_type('+data.id+')">Забираем из почтового ящика<br>\
        <input type="radio" name="price_get_type" id="price_get_type_going_to" value="3" '+(data.price_get_type==3?"checked":"")+' onclick="change_price_get_type('+data.id+')">Скачиваем по ссылке<br>\
      </div>\
    </div>\
  </div>\
  <div id="price_list_incoming_from" '+((typeof(data.price_get_type)!="undefined" && data.price_get_type!=1)?'style="display:none"':"")+'>\
    <div class="form-group row">\
      <label for="price_list_update_email_to1" class="col-sm-4 col-form-label">E-mail на который отправлять обновления</label>\
      <div class="col-sm-8 pull-right">\
        <input type="text" class="form-control search_str" id="update_email_to1" value="price-'+$("#mycompany").val()+'@sort1.ru" disabled>\
      </div>\
    </div>\
    <div class="form-group row">\
      <label for="price_list_update_email_to" class="col-sm-4 col-form-label">E-mail с которого перенаправляются обновления(Ваш email на который приходят прайсы)</label>\
      <div class="col-sm-8 pull-right">\
        <input type="text" class="form-control search_str" id="update_email_to" name="update_email_to" value="'+data.update_email_to+'">\
      </div>\
    </div>\
  </div>\
  <div id="price_list_going_to" '+((typeof(data.price_get_type)!="undefined" && data.price_get_type!=2)?'style="display:none"':"")+'>\
    <div class="form-group row">\
      <label for="price_list_email_configs" class="col-sm-4 col-form-label" >Выберите ящик с которого забирать прайс-листы</label>\
      <div class="col-sm-8 pull-right">\
        <select id="price_list_email_configs" name="email_config_id" class="form-control">';
        data_html+='<option value="0">Не выбрано</option>';
          for(var i in data1.email_configs){
            data_html+='<option value="'+data1.email_configs[i].id+'" '+(parseInt(data.email_config_id)==parseInt(data1.email_configs[i].id)?"selected":"")+'>'+data1.email_configs[i].name+'</option>';
          }
        data_html+=' </select>\
      </div>\
    </div>\
  </div>\
  <div id="price_list_get_url" '+((typeof(data.price_get_type)!="undefined" && data.price_get_type!=3)?'style="display:none"':"")+'>\
    <div class="form-group row">\
      <label for="price_list_get_url" class="col-sm-4 col-form-label">URL с которого скачивать обновления</label>\
      <div class="col-sm-8 pull-right">\
        <input type="text" class="form-control search_str" name="get_url" id="price_list_get_url" value="'+data.get_url+'">\
      </div>\
    </div>\
  </div>\
  <div id="edit_price_list_cron" '+((typeof(data.price_get_type)!="undefined" && data.price_get_type!=3)?'style="display:none"':"")+'>\
  <div class="form-group row">\
    <label for="price_list_get_url" class="col-sm-4 col-form-label">Периодичность</label>\
    <div class="col-sm-8 pull-right">\
      <button class="btn btn-sm btn-primary" type="button" onclick="edit_price_list_cron('+data.id+')">Настроить</button>\
      <div id="edit_price_list_cron_'+data.id+'" style="position:relative; top:-320px; left: -100px"></div>\
    </div>\
  </div>\
  </div>\
  <div id="price_list_email_from_incoming" class="form-group row" '+((typeof(data.price_get_type)!="undefined" && data.price_get_type==3)?'style="display:none"':"")+'>\
    <label for="price_list_update_email_from" class="col-sm-4 col-form-label">E-mail с которого приходят обновления</label>\
    <div class="col-sm-8 pull-right">\
      <input type="text" class="form-control search_str" onclick="this.select();" id="update_email_from" placeholder="Укажите E-mail с которого приходят обновления" name="update_email_from" value="'+data.update_email_from+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="update_email_from" id="update_email_from_label" onclick="clear_search_order_text(\'update_email_from\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
	  <label for="price_list_update_email_subj" class="col-sm-4 col-form-label">Тема письма содержит</label>\
	  <div class="col-sm-8 pull-right">\
		  <input type="text" class="form-control search_str" onclick="this.select();" id="update_email_subj" placeholder="Укажите текст содержащийся в теме письма" name="update_email_subj" value="'+data.update_email_subj+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="update_email_subj" id="update_email_subj_label" onclick="clear_search_order_text(\'update_email_subj\');"></label>\
	  </div>\
  </div>\
  <div class="form-group row">\
	  <label for="price_list_filename_part" class="col-sm-4 col-form-label">Имя файла содержит</label>\
	  <div class="col-sm-8 pull-right">\
		  <input type="text" class="form-control search_str" onclick="this.select();" id="filename_part" placeholder="Укажите текст содержащийся в имени файла" name="filename_part" value="'+data.filename_part+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="filename_part" id="filename_part_label" onclick="clear_search_order_text(\'filename_part\');"></label>\
	  </div>\
  </div>\
    <div class="form-group row">\
	    <label for="price_list_file_delimiter" class="col-sm-4 col-form-label">Разделитель полей в файле (для .csv)</label>\
	    <div class="col-sm-8 pull-right">\
		<input type="text" class="form-control search_str" onclick="this.select();" id="file_delimiter" placeholder="Укажите разделитель полей в .csv" name="file_delimiter" value="'+data.file_delimiter+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="file_delimiter" id="file_delimiter" onclick="clear_search_order_text(\'file_delimiter\');"></label>\
	    </div>\
    </div>\
    <div class="form-group row">\
	    <label for="price_list_send_zakaz_to_email" class="col-sm-4 col-form-label">E-mail на который отправлять заказ</label>\
	    <div class="col-sm-8 pull-right">\
		    <input type="text" class="form-control search_str" id="price_list_send_zakaz_to_email" placeholder="Укажите email на который отправлять заказ" name="send_zakaz_to_email" value="'+data.send_zakaz_to_email+'">\
	    </div>\
    </div>\
    <div class="form-group row">\
    <label for="status" class="col-sm-4 col-form-label text-nowrap">Статус</label>\
    <div class="col-sm-8">\
      <input type="hidden" name="status" id="status" value="'+data.status+'">\
      <input type="text" class="form-control" id="status_name" placeholder="" name="status_name" value="'+data.status_name+'" readonly onclick="select_price_list_status('+data.id+');">\
      <div id="status_list"></div>\
    </div>\
    </div><div id="price_list_details">';

    data_html+='</div></form>\
    <button class="btn btn-primary" onclick="save_price_list(\'price_list_form_'+data.id+'\');">Сохранить</button>\
    <button class="btn btn-secondary pull-right" onclick="$(\'#edit_price_list_'+data.id+'\').html(\'\');">Закрыть</button>\
    ';
    $("[id^=edit_price_list]").html('');
    create_window_centered_blue("edit_price_list_"+(target!=""?target+"_":"")+"div_"+data.id,"Редактирование прайс-листа","edit_price_list_"+(target!=""?target+"_":"")+data.id,data_html)
    });
}

function change_price_get_type(price_id){
  var price_get_type=$("#price_list_form_"+price_id+" input[name=price_get_type]:checked").val();
  switch(parseInt(price_get_type)){
    case 1: 
      $("#price_list_incoming_from").css("display","block");
      $("#price_list_going_to").css("display","none");
      $("#price_list_get_url").css("display","none");
      $("#edit_price_list_cron").css("display","none");
      break;
    case 2: 
      $("#price_list_going_to").css("display","block");
      $("#price_list_incoming_from").css("display","none");
      $("#price_list_get_url").css("display","none");
      $("#edit_price_list_cron").css("display","none");
      break;
    case 3: 
      $("#price_list_going_to").css("display","none");
      $("#price_list_incoming_from").css("display","none");
      $("#price_list_get_url").css("display","block");
      $("#price_list_email_from_incoming").css("display","none");
      $("#edit_price_list_cron").css("display","block");
      break;
  }
}

function add_new_price_list(target="",zakaz_detail_id=0,zakaz_type=""){
    var send=new Array();
    send['price_type']=2;
    api_query_array("/api/index.php",send,"get_price_types").then(function(data){
        var data_html='<div id="price_data_0"></div><form id="price_list_form_0" class="col-sm-12">\
        <div class="form-group row">\
        <label for="sklad_name" class="col-sm-4 col-form-label text-nowrap">Наименование</label>\
        <div class="col-xs-8">\
        <input type="hidden" name="price_list_id" id="price_list_id" value="0">\
        <input type="text" class="form-control search_str" onclick="this.select();" name="name" id="price_list_name" value="" placeholder="Введите наименование прайс-листа">\
        <div id="price_list_status_list"></div>\
        </div>\
        </div>\
        <div class="form-group row">\
          <label for="company_name" class="col-sm-4 col-form-label text-nowrap">Выберите поставщика</label>\
          <div class="col-sm-8">\
            <div class="input-group input-group-sm" style="width: 100%">\
              <input type="hidden" name="company_id" id="company_id" value="">\
              <input type="text" class="form-control input-sm" name="company_name" id="price_company_name" onclick="select_dealer_sklad();" onkeyup="select_dealer_sklad();" value="" placeholder="Нажмите чтобы выбрать" style="width:100%" autocomplete="off">\
              <div class="input-group-btn"><button title="добавление нового поставщика" class="btn btn-sm btn-default" onclick="show_company_data1(0,7);" type="button">+</button></div>\
            </div>\
          </div>\
        </div>\
        <div class="form-group row">\
        <label for="currency" class="col-sm-4 col-form-label text-nowrap">Валюта</label>\
        <div class="col-sm-8">\
        <div id="dealer_list_new"></div>\
        <input type="hidden" id="currency" name="currency" id="currency" value="">\
        <input type="text" class="form-control" id="currency_name" name="currency_name" value="" readonly onclick="select_currency();" placeholder="Нажмите чтобы выбрать">\
        <div id="currency_list"></div>\
        </div>\
        </div>\
        <div class="form-group row">\
        <label for="timeplus" class="col-sm-4 col-form-label text-nowrap">Доставка +n дней</label>\
        <div class="col-sm-8">\
        <input type="text" onclick="this.select();" class="form-control search_str" id="timeplus" placeholder="" name="timeplus" value="">\
        </div>\
        </div>\
        <div class="form-group row">\
	    <label for="price_type" class="col-sm-4 col-form-label">Тип цен</label>\
	    <div class="col-sm-8 pull-right">\
		  <select class="form-control" id="price_type" name="price_type" onchange="change_default_markup();">\
		    <option value="0">Не выбран</option>';
        var pt_len=data.price_types.length;
        for(var i=0; i<pt_len; i++){
          data_html+='<option value="'+data.price_types[i].id+'" markup="'+data.price_types[i].proc+'"';
          data_html+='>'+data.price_types[i].descr+'</option>';
        }
        data_html+='    </select>\
            </div>\
        </div>\
        <div class="form-group row">\
            <label for="default_brand" class="col-sm-4 col-form-label text-nowrap">Бренд по умолчанию</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" onclick="this.select();" id="default_brand" name="default_brand" value="">\
            </div>\
        </div>\
        <div class="form-group row">\
            <label for="default_markup" class="col-sm-4 col-form-label text-nowrap">Наценка по умолчанию</label>\
            <div class="col-sm-8">\
            <input type="text" onclick="this.select();" class="form-control search_str" id="default_markup" placeholder="" name="default_markup" value="">\
            </div>\
        </div>\
        <div class="form-group row">\
            <label for="price_list_city" class="col-sm-4 col-form-label">Город</label>\
            <div class="col-sm-8 pull-right">\
            <input type="text" onclick="this.select();" class="form-control search_str" id="city_name" placeholder="Город. Начните набирать..." name="city_name" value="" onkeyup="get_city();">\
            <input type="hidden" name="city_id" id="city_id" value="0">\
            <div id="city_div"></div>\
            </div>\
        </div>\
        <div class="form-group row">\
	        <label for="price_list_file_delimiter" class="col-sm-4 col-form-label">Разделитель полей в файле (для .csv)</label>\
	      <div class="col-sm-8 pull-right">\
		      <input type="text" class="form-control search_str" onclick="this.select();" id="file_delimiter" placeholder="Укажите разделитель полей в .csv" name="file_delimiter" value=",">\
	      </div>\
        </div>\
        <div class="form-group row">\
        <label for="status" class="col-sm-4 col-form-label text-nowrap">Статус</label>\
        <div class="col-sm-8">\
        <input type="hidden" name="status" id="status" value="">\
        <input type="text" class="form-control" id="status_name" placeholder="" name="status_name" value="" readonly onclick="select_price_list_status();">\
        <div id="status_list"></div>\
        </div>\
        </div><div id="price_list_details">';
        data_html+='</div></form>\
        <button class="btn btn-primary" onclick="save_price_list(\'price_list_form_0\','+zakaz_detail_id+',\''+zakaz_type+'\');">Сохранить</button>\
        <button class="btn btn-secondary pull-right" onclick="$(\'#edit_price_list_0\').html(\'\');">Закрыть</button>\
        ';
        $("[id^=edit_price_list]").html('');
        create_window("edit_price_list_"+(target!=""?target+"_":"")+"div_0","Редактирование прайс-листа","edit_price_list_"+(target!=""?target+"_":"")+"0",data_html);
    });
}

function save_price_list(price_list_form,zakaz_detail_id=0,zakaz_type=""){
  api_query('/api/index.php',price_list_form,'save_price_list').done(function(data){
    if(data.status=="ok"){
        $('#edit_price_list_'+$('#'+price_list_form+' [name=price_list_id]').val()).html('');
        if(zakaz_detail_id>0){
          set_zakaz_detail_dealer(data.price_list_id,zakaz_detail_id,2,zakaz_type);
        }
        else {
          get_price_lists();
        }
    }
  });
}

function select_dealer_sklad(){
    var send=[];
    send['search_clients_dealer_name']=$("#price_company_name").val();
    api_query_array("/api/index.php",send,"get_dealers").then(function(data){
    var datalen=data.dealers.length;
    var table="";
    table+="<div style=\"width: 550px; height:300px; overflow:auto;\">\
    <table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование</th><th>Адрес</th><th>ИНН/КПП</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
	table += "<tr onclick=\"$('#company_id').val("+data.dealers[i].id+"); $('#price_company_name').val('"+data.dealers[i].name.replace(/\"/g,"")+"'); $(\'#select_dealer\').hide();\"><td>"+(i+1)+"</td>\
		<td>" + data.dealers[i].name + "</td><td>"+data.dealers[i].address+"</td><td>"+data.dealers[i].inn+"/"+data.dealers[i].kpp+"</td>";
	table += "</tr>";
    }
    table+="</tbody></table></div>";
    create_window("select_dealer","Выберите поставщика","dealer_list_new",table);
 });
}

function select_price_list_status(price_list_id){
    var table="<table class=\"table table-hover\"><thead><tr><th></th></tr></thead><tbody>";
    table += "<tr onclick=\"$('#status').val(1);$('#status_name').val('Активен');$('#status_list').html('');\"><td>Активен</td></tr>";
    table += "<tr onclick=\"$('#status').val(0);$('#status_name').val('Неактивен');$('#status_list').html('');\"><td>Неактивен</td></tr>";
    table += "</tbody></table>";
    create_window("select_status","Выберите статус","status_list",table);
}

function select_currency(){
    api_query("/api/index.php","some_form","get_currency_kurs").then(function(data){
    var datalen=data.currency_kurs.length;
    var table="";
    table+="<table class=\"table table-hover\"><thead><tr><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
	table += "<tr onclick=\"$('#currency').val("+data.currency_kurs[i].NumCode+"); $('#currency_name').val('"+data.currency_kurs[i].Name.replace(/\"/g,"")+"'); $(\'#select_currency\').hide();\"><td>"+data.currency_kurs[i].Name+"</td>";
	table += "</tr>";
    }
    table+="</tbody></table>";
    create_window("select_currency","Выберите валюту","currency_list",table);
 });
}

// "

function get_price_list_details(price_list_form){
 api_query("/api/index.php",price_list_form,"get_price_list_details").then(function(data){
    if(parseInt(data.price_list_pages)<parseInt($("#"+price_list_form+" [name=page]").val())){
        $("#"+price_list_form+" [name=page]").val(1);
        get_price_list_details(price_list_form);
        return 0;
    }
    $("[id^=price_list_details_]").html('');
    var datalen=data.price_list_details.length;
    var table="<div class='row' style='padding:5px;'><div class='col-xs-2'><button class=\"btn btn-primary btn-sm\" onclick=\"add_new_price_list_detail("+$('#'+price_list_form+' [name=price_list_id]').val()+")\">Добавить деталь</button></div>";
    if(parseInt(data.price_get_type)!=3) {
      table += '<div class="col-xs-2"><span class="btn btn-success fileinput-button btn-sm">\
        <span>Загрузить файл</span>\
        <form id="fileupload10">\
        <input type="hidden" name="price_list_id" value="'+data.price_list_id+'">\
        <input type="hidden" name="action" value="upload_file">\
        <input id="fileupload_10" type="file" name="files[]" multiple>\
        </form>\
        </span></div>';
    }
    else {
      table += '<div class="col-xs-2">\
        <span class="btn btn-success btn-sm" onclick="get_price_from_url_file_data('+data.price_list_id+')">\
        <span>Загрузить файл</span>\
        </span></div>';
    }
    table += "<div class='col-xs-4'><div class='input-group input-group-sm'>";
    table += "<span id='price_list_search_"+data.price_list_id+"'><input type='text' class='form-control input-sm' name='search'";
    if (data.hasOwnProperty('search')) table+="value='"+data.search+"'";
    else table+="value=''";
    table += " onchange='$(\"#"+price_list_form+" [name=search]\").val($(\"#price_list_search_"+data.price_list_id+" [name=search]\").val());'></span>";
    table += "<span class='input-group-btn'><button class='btn btn-default btn-sm' type='button' onclick='$(\"#"+price_list_form+" [name=page]\").val(1);get_price_list_details(\""+price_list_form+"\")'>Поиск</button></span></div></div>";
    table +='<div class="col-xs-4">\
    <a onclick="get_price_list_xls('+data.price_list_id+');" title="Выгрузить прайс-лист в формате xlsx"><img src="/new_images/excel_32.png" style="width: 30px;"></a>\
    <a onclick="get_price_list_csv('+data.price_list_id+');" title="Выгрузить прайс-лист в формате csv"><img src="/new_images/csv_128.png" style="width: 30px;"></a>\
    <input type="checkbox" id="add_markup_price_'+data.price_list_id+'" name="add_markup_price_'+data.price_list_id+'">Выгружать с учетом наценок\
    </div>';
    table += "</div><div id='add_new_price_list_detail'></div><div id='select_price_cols_"+data.price_list_id+"'></div>";
    table += '<div id="progress" class="progress col-sm-9"  style="display:none;">\
        <div class="progress-bar progress-bar-success"></div>\
    </div>';
    table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
    var x=0,y=0,xx=0,yy=0;
    if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
    else var selected_page=1;
    for (var i=1; i<=data.price_list_pages; i++){
	if(i>(selected_page+6) && i<(data.price_list_pages-1)){
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
		    table += '><a onclick="$(\'#'+price_list_form+' input[name=page]\').val(\''+i+'\');';
		    if($('#price_list_search_'+data.price_list_id+' [name=search]').val()!="") table += '$(\'#'+price_list_form+' input[name=search]\').val(\''+$('#price_list_search_'+data.price_list_id+' [name=search]').val()+'\');';
		    table += 'get_price_list_details(\''+price_list_form+'\')">...</a></li>';
		}
		if (x==1) xx++;
	}
	else {
	    if (y==1) {
		if (yy==0){
		    table += '<li';
		    table += '><a onclick="$(\'#'+price_list_form+' input[name=page]\').val(\''+i+'\');';
		    if($('#price_list_search_'+data.price_list_id+' [name=search]').val()!="") table += '$(\'#'+price_list_form+' input[name=search]\').val(\''+$('#price_list_search_'+data.price_list_id+' [name=search]').val()+'\');';
		    table += 'get_price_list_details(\''+price_list_form+'\')">...</a></li>';
		}
		if (y==1) yy++;
	    }
	    else {
		table += '<li';
		if(selected_page==i) table+= " class='active'";
		table += '><a onclick="$(\'#'+price_list_form+' input[name=page]\').val(\''+i+'\');';
		if($('#price_list_search_'+data.price_list_id+' [name=search]').val()!="") table += '$(\'#'+price_list_form+' input[name=search]\').val(\''+$('#price_list_search_'+data.price_list_id+' [name=search]').val()+'\');';
		table += 'get_price_list_details(\''+price_list_form+'\')">'+i+'</a></li>';
	    }
	}
    }
    table += '</ul></div><div id="edit_price_list_detail_0"></div>';
    table += "<table class=\"table table-hover\"><thead><tr><th>№</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Цена</th><th>Кол-во</th><th>Резервировано</th><th>Срок доставки</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
      table += "<tr><td>"+(i+1)+"</td><td>" + data.price_list_details[i].article + "</td>";
      table += "<td>"+data.price_list_details[i].brand+"</td><td>"+data.price_list_details[i].name+"</td><td>"+data.price_list_details[i].price+"</td>";
      table += "<td>"+data.price_list_details[i].count+"</td><td>"+data.price_list_details[i].reserved_count+"</td><td>"+data.price_list_details[i].time+"</td>";
      table += "<td><form id='delete_price_detail_"+data.price_list_details[i].detail_id+"'><input type=\"hidden\" name=\"detail_id\" value=\""+data.price_list_details[i].detail_id+"\"><input type=\"hidden\" name=\"price_list_id\" value=\""+data.price_list_details[i].price_list_id+"\"></form>";
      table += "<div class='btn-group' style='display: flex;'><img src='/new_images/edit.svg' class='menuimg' onclick=\"edit_price_list_detail('delete_price_detail_"+data.price_list_details[i].detail_id+"');\">";
      table += " <img src='/new_images/garbage.svg' class='menuimg' ";
      table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь с прайс листа?\',function(result){ if(result) api_query('/api/index.php','delete_price_detail_"+data.price_list_details[i].detail_id+"','delete_price_list_detail').then(function(data){if(data.status=='ok') get_price_list_details('"+price_list_form+"')});});\">";
      table += "</div></td>";
      table += "</tr>";
    }
    table += "</tbody></table>";
    //table+="<script>file_uploader();</script>";
    create_window_centered_blue("price_list_details_div_"+data.price_list_id,"Детали в прайс-листе "+data.price_list_name,"price_list_details_"+data.price_list_id,table);
    file_uploader(1);
 });
} 


var keyTimer;

function get_city(){
//    var city_name=$("#city_name").val();
    clearTimeout(keyTimer);
    keyTimer = setTimeout(runCityFilter, 1000);
}

function runCityFilter(){
    var city_name=$("#city_name").val();
    if (city_name!='' && city_name.length>1){
	var send=new Array();
	send['city_name']=city_name;
	api_query_array("/api/index.php",send,"get_city").then(function(data){
	    var table='<table class="table table-hover">';
	    var len=data.citys.length;
	    for(var i=0; i<len; i++){
		table+='<tr onclick="change_city('+data.citys[i].id+');"><td id="select_city_'+data.citys[i].id+'">'+data.citys[i].city+'</td></tr>';
	    }
	    table+='</table>';
	    create_simple_div("citys","city_div",table);
	    if(len==1) change_city(data.citys[0].id);
	    if(len>1) $("#city_div").show();
	    if(len==0) {
		table='<table class="table"><tr><td>Не найден город! проверьте правильность ввода</td></tr></table>';
		create_simple_div("citys","city_div",table);
		$("#city_div").show();
	    }
	});
    }
}

function change_city(id){
    var city_name=$("#select_city_"+id).html();
    $("#city_id").val(id);
    $("#city_name").val(city_name);
    $("#city_div").hide();
}

function show_detail_documents(detail_id,sklad_id=0,from){
    $.blockUI({ css: { 
      border: 'none', 
      padding: '15px', 
      backgroundColor: '#000', 
      '-webkit-border-radius': '10px', 
      '-moz-border-radius': '10px', 
      opacity: .5, 
      color: '#fff'
      },
      message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
    });
    var send=[];
    send['detail_id']=detail_id;
    send['date_from']=$("#show_det_doc_search input[name=date_from]").val();
    send['date_to']=$("#show_det_doc_search input[name=date_to]").val();
    if(sklad_id>0)  send['sklad_id']=sklad_id;
    else send['sklad_id']=$("#my_sklad").val();
    api_query_array("/api/index.php",send,"get_detail_documents").then(function(data){
      $.unblockUI();
        var table='<div style="min-width:780px;">\
        <form id="show_det_doc_search">\
            <div id="zakaz_client_header" class="row col-sm-12">\
            <div class="col-sm-12">\
                <div class="input-group input-group-sm pull-right">\
                    <span id="show_det_doc_date_from_label" class="input-group-addon">с: </span>\
                    <input type="date" name="date_from" id="show_det_doc_date_from" class="form-control" value="'+data.date_from+'">\
                    <span id="show_det_doc_date_to_label" class="input-group-addon">по: </span>\
                    <input type="date" name="date_to" id="show_det_doc_date_to" class="form-control" value="'+data.date_to+'">\
                    <div class="input-group-btn">\
                    <button type="button" class="btn btn-primary btn-sm" onclick="show_detail_documents('+detail_id+','+send['sklad_id']+',\''+from+'\');">Поиск</button>\
                </div>\
            </div>\
            </div>\
            <div class="col-sm-1">\
                \
            </div>\
            </div>\
        </form>\
        ';
        table+='<table class="table table-hover">';
        table+='<thead>\
        <tr><th>№</th><th>Дата</th><th>Контрагент</th><th>№ документа</th><th>тип</th><th colspan="2">Поступление</th><th colspan="2">Продажа</th></tr>\
        <tr><th colspan="5"></th><th>кол-во</th><th>цена</th><th>кол-во</th><th>цена</th></tr>\
        </thead>';
        table+='<tbody>';
        var len=data.document_details.length;
        var prih_sum=0,prod_sum=0;
        for(var i=0; i<len; i++){
            table+='<tr><td>'+(i+1)+'</td><td>'+convertTZ(data.documents[data.document_details[i].document_id].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>\
            <td>'+data.contragents[data.documents[data.document_details[i].document_id].company_id].name+'</td>\
            <td>'+data.document_details[i].document_id+'</td>';
            switch(parseInt(data.documents[data.document_details[i].document_id].type_id)){
                case 1: table+='<td>Поступление</td>'; break;
                case 2: table+='<td>Продажа</td>'; break;
                case 3: table+='<td>Инвентаризация (списание)</td>'; break;
                case 5: table+='<td>Инвентаризация (поступление)</td>'; break;
                case 6: table+='<td>Возврат покупателя</td>'; break;
                case 7: table+='<td>Возврат поставщику</td>';break;
                default: table+='<td></td>';
            }
            if(data.documents[data.document_details[i].document_id].type_id=="1" || data.documents[data.document_details[i].document_id].type_id=="6" || data.documents[data.document_details[i].document_id].type_id=="5"){
                table+='<td>'+data.document_details[i].count+'</td><td>'+data.document_details[i].price+'</td><td></td><td></td>';
                prih_sum+=parseInt(data.document_details[i].count);
            }
            if(data.documents[data.document_details[i].document_id].type_id=="2" || data.documents[data.document_details[i].document_id].type_id=="7" || data.documents[data.document_details[i].document_id].type_id=="3"){
                table+='<td></td><td></td><td>'+data.document_details[i].count+'</td><td>'+(data.documents[data.document_details[i].document_id].type_id=="7"?data.document_details[i].dealer_price:data.document_details[i].price)+'</td>';
                prod_sum+=parseInt(data.document_details[i].count);
            }
            table+='</tr>';
        }
        if(data.logistic_order_details.length>0){
          table+='<tr><td colspan="9"> Перемещения </td></tr>';
          for(var i in data.logistic_order_details){
            table+='<tr><td>'+(i+1)+'</td>\
            <td>'+convertTZ(data.logistic_orders[data.logistic_order_details[i].logistic_order_id].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>\
            <td>'+data.contragents[data.logistic_orders[data.logistic_order_details[i].logistic_order_id].from_company_id].name+'</td>\
            <td>'+data.logistic_order_details[i].logistic_order_id+'</td>';
            switch(parseInt(data.logistic_orders[data.logistic_order_details[i].logistic_order_id].logistic_order_type)){
                case 1: table+='<td>Внутреннее перемещение из заказа</td>'; break;
                case 2: table+='<td>Доставка</td>'; break;
                case 3: table+='<td>Внутреннее перемещение без заказа</td>'; break;
                default: table+='<td></td>';
            }
            if(data.logistic_orders[data.logistic_order_details[i].logistic_order_id].logistic_order_type=="1" || 
              data.logistic_orders[data.logistic_order_details[i].logistic_order_id].logistic_order_type=="6" || 
              data.logistic_orders[data.logistic_order_details[i].logistic_order_id].logistic_order_type=="5"){
                table+='<td>'+data.logistic_order_details[i].count+'</td><td>'+data.logistic_order_details[i].price+'</td><td></td><td></td>';
                prih_sum+=parseInt(data.logistic_order_details[i].count);
            }
            if(data.logistic_orders[data.logistic_order_details[i].logistic_order_id].logistic_order_type=="2" || 
              data.logistic_orders[data.logistic_order_details[i].logistic_order_id].logistic_order_type=="7" || 
              data.logistic_orders[data.logistic_order_details[i].logistic_order_id].logistic_order_type=="3"){
                table+='<td></td><td></td><td>'+data.logistic_order_details[i].count+'</td><td>'+(data.logistic_orders[data.logistic_order_details[i].logistic_order_id].logistic_order_type=="7"?data.logistic_order_details[i].dealer_price:data.logistic_order_details[i].price)+'</td>';
                prod_sum+=parseInt(data.logistic_order_details[i].count);
            }
            table+='</tr>';
          }
        }
        table+='<tr><td colspan="5"><b>Итого</b></td><td><b>'+prih_sum+'</b></td><td></td><td><b>'+prod_sum+'</b></td><td></td></tr>';
        table+='</tbody></table></div>';
        if(typeof(data.document_details[0])!="undefined")
            var header_text="Движение товара "+data.document_details[0].article+" "+data.document_details[0].brand+" "+data.document_details[0].name;
        else var header_text="Движение товара";
        if(typeof($("#show_"+from+"_detail_documents_"+detail_id+"_div").html())=="undefined" || $("#show_"+from+"_detail_documents_"+detail_id+"_div").html()==""){
            create_window("show_"+from+"_detail_documents_"+detail_id+"_div",header_text,"show_"+from+"_detail_documents_"+detail_id,table);
        }
        else {
            $("#show_"+from+"_detail_documents_"+detail_id+"_div_content").html(table);
            $("#show_"+from+"_detail_documents_"+detail_id+"_div_header").html(header_text);
        }
    });
}

function get_sklad_prices(){
    var znak_ch="inv";
    var send=new Array();
    send['search_sklad_price_date_from']=$("#search_sklad_price_date_from").val();
    send['search_sklad_price_date_to']=$("#search_sklad_price_date_to").val();
    send['search_sklad_price_sklad_name']=$("#search_sklad_price_sklad_name").val();
    api_query_array("/api/index.php",send,"get_sklad_prices").then(function(data){
      if(typeof(data.search_sklad_price_date_from)!="undefined") 
        $("#search_sklad_price_date_from").val(data.search_sklad_price_date_from);
      if(typeof(data.search_sklad_price_date_to)!="undefined") 
        $("#search_sklad_price_date_to").val(data.search_sklad_price_date_to);
      if(typeof(data.search_sklad_price_sklad_name)!="undefined") 
        $("#search_sklad_price_sklad_name").val(data.search_sklad_price_sklad_name);
      var datalen=data.sklad_prices.length;
      var table=""
      
      table+="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Склад</th><th>Описание</th><th>Тип</th><th>Дата создания</th>";
      table += "<th>Позиций</th><th>Кол-во</th><th>Сумма</th><th>статус</th><th></th></tr></thead></tbody>";
      var sklad_prices_sum=0,sklad_prices_sum_count=0,sklad_prices_sum_pos=0;
      for (var i=0; i<datalen; i++){
        if(data.sklad_prices[i].sklad_price_pos_sum!==null) sklad_prices_sum+=parseFloat(data.sklad_prices[i].sklad_price_pos_sum);
        if(data.sklad_prices[i].sklad_price_pos_count!==null) sklad_prices_sum_pos+=parseFloat(data.sklad_prices[i].sklad_price_pos_count);
        if(data.sklad_prices[i].sklad_price_positions!==null) sklad_prices_sum_count+=parseFloat(data.sklad_prices[i].sklad_price_positions);
          table += "<tr><td><div id='sklad_price_details_"+data.sklad_prices[i].id+"'></div>"+data.sklad_prices[i].id+"</td>";
        table += "<td>"+data.sklad_prices[i].sklad_name+"</td><td>"+data.sklad_prices[i].descr+"</td>";
        table += '<td>'+(data.sklad_prices[i].type=="1" ? "Частичная" : "Полная")+'</td>';
          table += "<td>"+convertTZ(data.sklad_prices[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td><td>";
        if(parseFloat(data.sklad_prices[i].sklad_price_positions)>0)
          table += data.sklad_prices[i].sklad_price_positions;
        else
          table+="0";
        table +="</td><td>";
        if(parseFloat(data.sklad_prices[i].sklad_price_pos_count)>0)
          table += data.sklad_prices[i].sklad_price_pos_count;
        else
          table += "0";
        table += '</td><td nowrap align="left">';
        if(parseFloat(data.sklad_prices[i].sklad_price_pos_sum).toFixed(2)>0)
          table += formatNumber(parseFloat(data.sklad_prices[i].sklad_price_pos_sum).toFixed(2));
        else table += "0";
        table += "</td>";
        switch(parseInt(data.sklad_prices[i].status)){
          case 1: table+='<td>Создан</td>'; break;
          case 20: table+='<td>Идет инвентаризация</td>'; break;
          case 30: table+='<td>Завершена</td>'; break;
        }
          table += "<td><form id='delete_sklad_price_"+data.sklad_prices[i].id+"'><input type=\"hidden\" name=\"sklad_price_id\" value=\""+data.sklad_prices[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
        //if(znak=="-") table += '<a onclick="show_document_print_menu('+data.documents[i].id+');" title="Печать"><img src="/new_images/printer.svg" class="menuimg"></a>';
        table += '<div id="sklad_price_print_menu_'+data.sklad_prices[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div>';
        table += "&nbsp;<a onclick=\"edit_sklad_price("+data.sklad_prices[i].id+");\" title='Редактировать документ'><img src='/new_images/edit.svg' class='menuimg'></a>";
          table += "<a onclick=\"get_sklad_price_details("+data.sklad_prices[i].id+","+data.sklad_prices[i].status+")\" title='Просмотреть список'><img src='/new_images/file.svg' class='menuimg'></a>";
          table += '<form id="sklad_price_form_'+data.sklad_prices[i].id+'" style="display:none"><input type="hidden" name="action" value="get_sklad_price_details">';
          table += "<input type='hidden' name='sklad_price_id' value='"+data.sklad_prices[i].id+"'><input type='hidden' name='sklad_id' value='"+data.sklad_prices[i].sklad_id+"'></form>";
          table += "<a title='Удалить document' ";
          table += "onclick=\"delete_sklad_price("+data.sklad_prices[i].id+")\"><img src='/new_images/garbage.svg' class='menuimg'></a>"
          table += "</div></td>";
          table += "</tr>";
      }
      table+='<tr style="font-weight:bold"><td colspan="5">Итого</td><td>'+sklad_prices_sum_count+'</td><td>'+sklad_prices_sum_pos+'</td><td>'+sklad_prices_sum.toFixed(2)+'</td><td colspan="2"></td></tr>';
      table+="</tbody></table>";
      $("#sklad_price_list").html(table);
    });
  }
  
  function delete_sklad_price(id){
    bootbox.confirm(
      'Вы точно хотите удалить этот документ?',
      function(result){ 
        if(result) {
          $.blockUI({ css: { 
            border: 'none', 
            padding: '15px', 
            backgroundColor: '#000', 
            '-webkit-border-radius': '10px', 
            '-moz-border-radius': '10px', 
            opacity: .5, 
            color: '#fff'
            },
            message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
          });
          api_query('/api/index.php','sklad_price_form_'+id,'delete_sklad_price').then(function(data){
            $.unblockUI();
            if(data.status=='ok') 
              get_sklad_prices();
          });
        }
      }
    );
  }
  
  function add_new_sklad_price(){
    var znakchar="sklad_price";
    var table='\
    <form id="new_sklad_price_form">\
      <div class="form-group row">\
        <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Описание</label>\
        <div class="col-sm-7">\
          <input type="hidden" name="is_new" value="1">\
          <input type="text" class="form-control" id="descr_'+znakchar+'" name="descr" value="">\
        </div>\
      </div>\
      <div class="form-group row">\
        <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Выберите склад</label>\
        <div class="col-sm-7">\
          <input type="hidden" name="sklad_id" id="sklad_id_'+znakchar+'" value="">\
          <input type="text" class="form-control" name="sklad_name" id="sklad_name_'+znakchar+'" value="" onclick="select_sklad(\''+znakchar+'\');" readonly placeholder="Нажмите чтобы выбрать">\
          <div id="sklad_list_new_'+znakchar+'"></div>\
        </div>\
      </div>\
      <div class="form-group row">\
          <label for="sklad_price_type" class="col-sm-5 col-form-label text-nowrap">Тип списка </label>\
          <div class="col-sm-7">\
            <select name="sklad_price_type" id="sklad_price_type_'+znakchar+'" class="form-control">\
            <option value="1"';
            //if(parseInt(data.sklad_price.type)==1) table+=' selected="selected"';
            table+='>Частичная</option>\
            <option value="2"';
            //if(parseInt(data.sklad_price.type)==2) table+=' selected="selected"';
            table+='>Полная</option>\
            </select>\
            <div id="sklad_list_new_'+znakchar+'"></div>\
          </div>\
        </div>\
    </form>\
    <div class="row">\
    <div class="col-sm-6"><button class="btn btn-primary" onclick="save_sklad_price();">Сохранить</button></div>\
    <div class="col-sm-6"><button class="btn btn-default pull-right" onclick="$(\'#edit_sklad_prices_0\').html(\'\');">Отменить</button></div>\
    </div>';
    create_window_centered_blue("new_sklad_price_div","Добавление нового списка цен","edit_sklad_prices_0",table);
  }
  
  function edit_sklad_price(sklad_price_id){
    var znakchar="inv";
    var send=new Array();
    send['sklad_price_id']=sklad_price_id;
    api_query_array("/api/index.php",send,"get_sklad_price").then(function(data){
      var table='\
      <form id="new_sklad_price_form">\
        <div class="form-group row">\
          <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Описание</label>\
          <div class="col-sm-7">\
            <input type="hidden" name="sklad_price_id" value="'+sklad_price_id+'">\
            <input type="hidden" name="is_new" value="0">\
            <input type="text" class="form-control" id="descr_'+znakchar+'" name="descr" value="'+data.sklad_price.descr+'">\
          </div>\
        </div>\
        <div class="form-group row">\
          <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Выберите склад</label>\
          <div class="col-sm-7">\
            <input type="hidden" name="sklad_id" id="sklad_id_'+znakchar+'" value="'+data.sklad_price.sklad_id+'">\
            <input type="text" class="form-control" name="sklad_name" id="sklad_name_'+znakchar+'" onclick="select_sklad(\''+znakchar+'\');" readonly value="'+data.sklad_price.sklad_name+'">\
            <div id="sklad_list_new_'+znakchar+'"></div>\
          </div>\
        </div>\
        <div class="form-group row">\
          <label for="sklad_price_type" class="col-sm-5 col-form-label text-nowrap">Тип списка<sup title="Тип печати ценников: (полная или частичная). ">⍰</sup></label>\
          <div class="col-sm-7">\
            <select name="sklad_price_type" id="sklad_price_type_'+znakchar+'" class="form-control">\
            <option value="1"';
            if(parseInt(data.sklad_price.type)==1) table+=' selected="selected"';
            table+='>Частичная</option>\
            <option value="2"';
            if(parseInt(data.sklad_price.type)==2) table+=' selected="selected"';
            table+='>Полная</option>\
            </select>\
            <div id="sklad_list_new_'+znakchar+'"></div>\
          </div>\
        </div>';
      
      //<b>Инвентаризационная комиссия:</b> <button type="button" class="pull-right btn btn-xs btn-primary" onclick="select_new_sklad_price_user('+sklad_price_id+');" title="добавить члена комиссии"> + </button><div id="select_sklad_price_user"></div>';
      /*var sklad_price_users_len=data.sklad_price_users.length;
      if(sklad_price_users_len>0) table+='<table class="table"><thead><th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Председатель</th></thead><tbody>';
      for(var i=0; i<sklad_price_users_len; i++){
        table+='<tr><td>'+data.sklad_price_users[i].lastname+'</td><td>'+data.sklad_price_users[i].name+'</td><td>'+data.sklad_price_users[i].middlename+'</td><td>';
        if(data.sklad_price_users[i].is_header=="1") {
          table+='<input type="radio" name="is_header" value="'+data.sklad_price_users[i].user_id+'" checked="checked">';
        }
        else { 
          table+='<input type="radio" name="is_header" value="'+data.sklad_price_users[i].user_id+'">';
        }
        table+='</td></tr>';
      }
      if(sklad_price_users_len>0) table+='</tbody></table>';
      */
      table+='</form>';
      table+='<br><div class="row">\
      <div class="col-sm-6"><button class="btn btn-primary" onclick="save_sklad_price();">Сохранить</button></div>\
      <div class="col-sm-6"><button class="btn btn-default pull-right" onclick="$(\'#new_sklad_price\').html(\'\');">Отменить</button></div>\
      </div>';
      create_window_centered_blue("new_sklad_price_div","Изменение документа инвентаризации","edit_sklad_prices_0",table);
    });
  }

  function add_to_sklad_price_start(sklad_price_detail_id){
    var send=new Array();
    send['sklad_price_detail_id']=sklad_price_detail_id;
    api_query_array("/api/index.php",send,"add_sklad_price_detail_to_start").then(function(data){
      if(data.status=="err"){
        $("#checkbox_"+sklad_price_detail_id).prop('checked', false);
      }
    });
  }
  
  function select_new_sklad_price_user(sklad_price_id){
    api_query("/api/index.php","some_form","get_my_company_users").then(function(data){
      var userslen=data.users.length;
      var table='<table class="table table-hover"><thead><td>Фамилия</td><td>Имя</td><td>Отчество</td></thead><tbody>';
      for (var i=0; i<userslen; i++){
        table+='<tr onclick="add_sklad_price_user('+sklad_price_id+','+data.users[i].id+');"><td>'+data.users[i].lastname+'</td><td>'+data.users[i].name+'</td><td>'+data.users[i].middlename+'</td></tr>';
      }
      table+='</tbody></table>';
      create_window("select_sklad_price_user_div","Добавление нового члена комиссии","select_sklad_price_user",table);
    });
  }
  
  function add_sklad_price_user(sklad_price_id,user_id){
    var send=new Array();
    send['user_id']=user_id;
    send['sklad_price_id']=sklad_price_id;
    api_query_array("/api/index.php",send,"add_sklad_price_user").then(function(data){
      if(data.status=="ok"){
        $("#select_sklad_price_user").html('');
        edit_sklad_price(sklad_price_id);
      }
    });
  
  } 

  function sklad_price_start(sklad_price_id){
    var send=new Array();
    send['sklad_price_id']=sklad_price_id;
    api_query_array("/api/index.php",send,"start_sklad_price").then(function(data){
      get_sklad_prices();
    });
  }
  
  function save_sklad_price(){
    api_query("/api/index.php","new_sklad_price_form","save_sklad_price").then(function(data){
      if(data.status=="ok"){
        $("#edit_sklad_prices_0").html("");
        get_sklad_prices();
      }
    });
  }
  
  function get_sklad_price_details(sklad_price_id,sklad_price_status){
    var send=new Array();
    send['sklad_price_id']=sklad_price_id;
    send['search_article']=$("#sklad_price_search_article").val();
    //send['search_ean']=$("#sklad_price_search_ean").val();
    send['search_name']=$("#sklad_price_search_name").val();
    send['show_zero']=$("#sklad_price_show_zero").prop("checked");
    send['selected_page']=$("#sklad_price_selected_page").val();
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
    api_query_array("/api/index.php",send,"get_sklad_price_details").then(function(data){
      if(data.status=="ok"){
        var table='<div class="row">\
        <div class="col-sm-3">';
        if(sklad_price_status==1) table+='<a href="/print_price_tag_for_detail.php?sklad_price_id='+sklad_price_id+'" target="_blank"><button class="btn btn-primary" type="button">Печать ценников</button></a>';
        table+='</div>\
        <div class="col-sm-9">\
        <div class="input-group input-group-sm pull-right">\
          <span class="input-group-addon"><input type="checkbox" name="show_zero" id="sklad_price_show_zero"';
          if(typeof(data.show_zero)!="undefined" && data.show_zero) table+=' checked';
          table+='>Показать 0 остатки</span>\
          <label for="sklad_price_search_article" class="input-group-addon">Артикул:</label>\
          <input class="form-control" type="text" name="search_article" id="sklad_price_search_article" onchange="get_sklad_price_details('+sklad_price_id+','+sklad_price_status+');" value="';
          if(data.search_article!==null && typeof(data.search_article)!="undefined") table+=data.search_article;
          table+='">\
          <label for="sklad_price_search_name" class="input-group-addon">Наименование:</label>\
          <input class="form-control" type="text" name="search_name" id="sklad_price_search_name" onchange="get_sklad_price_details('+sklad_price_id+','+sklad_price_status+');" value="';
          if(data.search_name!==null && typeof(data.search_name)!="undefined") table+=data.search_name;
          table+='">\
          <input type="hidden" name="sklad_price_selected_page" id="sklad_price_selected_page" ';
          if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
          else var selected_page=1;
          table+=' value="'+selected_page+'"';
          table+='>\
        </div>\
        </div>\
        </div>';
  
        table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
        var x=0,y=0,xx=0,yy=0;
        
        for (var i=1; i<=data.sklad_price_pages; i++){
          if(i>(selected_page+6) && i<(data.sklad_price_pages-1)){
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
                table += '><a href="#" onclick="$(\'#sklad_price_selected_page\').val(\''+i+'\');';
                //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
                table += 'get_sklad_price_details('+sklad_price_id+',\''+sklad_price_status+'\')">...</a></li>';
            }
            if (x==1) xx++;
          }
          else {
              if (y==1) {
            if (yy==0){
                table += '<li';
                table += '><a href="#" onclick="$(\'#sklad_price_selected_page\').val(\''+i+'\');';
                //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
                table += 'get_sklad_price_details('+sklad_price_id+',\''+sklad_price_status+'\')">...</a></li>';
            }
            if (y==1) yy++;
              }
              else {
            table += '<li';
            if(selected_page==i) table+= " class='active'";
            table += '><a href="#" onclick="$(\'#sklad_price_selected_page\').val(\''+i+'\');';
            //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
            table += 'get_sklad_price_details('+sklad_price_id+',\''+sklad_price_status+'\')">'+i+'</a></li>';
              }
          }
        }
        table += '</ul></div>';
  
        table+='<div style="height: 73vh; overflow:auto;">\
        <table class="table table-hover">\
        <thead>\
        <tr><th><input type="checkbox" id="sklad_price_checkbox_all" onclick="check_all_sklad_price_details();"></th><th>№</th><th>артикул</th><th>наименование</th><th>бренд</th><th>кол-во учет</th><th>цена закуп.</th><th>цена прод.</th></tr>\
        </thead><tbody>';
        var len=data.sklad_price_details.length;
        for (var i=0; i<len; i++){
          table+='<tr><td>';
          switch(parseInt(data.sklad_price_details[i].status)) {
            case 0: table+='<input type="checkbox" id="sklad_price_checkbox_'+data.sklad_price_details[i].id+'" onchange="add_to_sklad_price_start('+data.sklad_price_details[i].id+')">'; break;
            case 1: table+='<input type="checkbox" id="sklad_price_checkbox_'+data.sklad_price_details[i].id+'" onchange="add_to_sklad_price_start('+data.sklad_price_details[i].id+')" checked="checked">'; break;
            case 20: table+='<input type="checkbox" id="sklad_price_checkbox_'+data.sklad_price_details[i].id+'" onchange="add_to_sklad_price_start('+data.sklad_price_details[i].id+')" checked="checked">'; break;
            case 30: table+='<img src="/images/ok.svg" style="width:20px;">'; break;
          }
          table+='</td>';
          table+='<td>'+(i+1)+'</td><td>'+data.sklad_price_details[i].article+'</td><td>'+data.sklad_price_details[i].name+'</td><td>'+data.sklad_price_details[i].brand+'</td>\
          <td id="sklad_price_sklad_count_'+data.sklad_price_details[i].id+'">'+data.sklad_price_details[i].count_sklad+'</td>\
          <td id="sklad_price_price_'+data.sklad_price_details[i].id+'">'+data.sklad_price_details[i].price+'</td>\
          <td id="sklad_price_sale_price_'+data.sklad_price_details[i].id+'">\
          <input type="text" size="4" name="sklad_price_det_sale_price_'+data.sklad_price_details[i].id+'" id="sklad_price_det_sale_price_'+data.sklad_price_details[i].id+'"\
         value="'+data.sklad_price_details[i].sale_price+'" onchange="change_sklad_price_det_sale_price('+data.sklad_price_details[i].id+','+data.sklad_price_details[i].sklad_price_id+','+data.sklad_price_details[i].detail_id+',this.value,'+data.sklad_price_details[i].sale_price+')" onfocus="this.select()" ';
        table+='>\
          </td>\
          </tr>';
        }
        table+='</tbody></table><div>';
        $.unblockUI();
        create_window_centered_blue("sklad_price_details_"+sklad_price_id+"_div","Инвентаризационная опись","sklad_price_details_"+sklad_price_id,table);
      }
    }, function(data){
      $.unblockUI();
    })
  }

  function change_sklad_price_det_sale_price(sklad_price_detail_id,sklad_price_id,detail_id,price,oldprice){
    var send=[];
    send['sklad_price_detail_id']=sklad_price_detail_id;
    send['sale_price']=price;
    api_query_array("/api/index.php",send,"save_sklad_price_detail").then(function(data){
      if(data.status=="ok"){
        save_sklad_detail_markup_price($("#sklad_price_form_"+sklad_price_id+" input[name=sklad_id]").val(),detail_id,price);
      }
      else {
        $("#sklad_price_det_sale_price_"+sklad_price_detail_id).val(oldprice);
      }
    })
  }
  
  function check_all_sklad_price_details(){
    $("input[id^=sklad_price_checkbox").each(function(index){
      if($("#sklad_price_checkbox_all").prop("checked")){
        if(!$(this).prop("checked")) $(this).click();
      }
      else {
        if($(this).prop("checked")) $(this).click();
      }
    })
  }

  function init_price_export(id){
    var date=new Date();
    var date_from=new Date(new Date().setMonth(date.getMonth() - 3));
    price_exports[id]={
      name:"",
      filename:"",
      price_type_id:0,
      price_from: 0,
      price_to: 0,
      brands:[],
      export_from:[],
      format:"1",
      send_to_email:"",
      csv_delimiter:",",
      export_nelikvid:0,
      enable_export:0,
      periodically_send_to_email: 0,
      export_nelikvid_date_from: date_from.getFullYear()+"-"+((date_from.getMonth()+1)<10?"0"+(date_from.getMonth()+1):(date_from.getMonth()+1))+"-"+(date_from.getDate()<10?"0"+date_from.getDate():date_from.getDate()),
      export_nelikvid_date_to: date.getFullYear()+"-"+((date.getMonth()+1)<10?"0"+(date.getMonth()+1):(date.getMonth()+1))+"-"+(date.getDate()<10?"0"+date.getDate():date.getDate()),
      description:"",
      originality:0,
      address:""
    };
  }

  function get_price_export(id){
    var send=[];
      send['price_export_id']=id;
      api_query_array("/api/index.php",send,"get_price_export").then(function(data){
          price_exports[data.price_exports[0].id]={};
          price_exports[data.price_exports[0].id].id=data.price_exports[0].id;
          price_exports[data.price_exports[0].id].name=data.price_exports[0].name;
          price_exports[data.price_exports[0].id].send_to_email=data.price_exports[0].send_to_email;
          price_exports[data.price_exports[0].id].format=data.price_exports[0].format;
          price_exports[data.price_exports[0].id].filename=data.price_exports[0].filename;
          price_exports[data.price_exports[0].id].email_config_id=data.price_exports[0].email_config_id;
          price_exports[data.price_exports[0].id].send_from_my_email=data.price_exports[0].send_from_my_email;
          price_exports[data.price_exports[0].id].brands=[];
          price_exports[data.price_exports[0].id].export_from=[];
          price_exports[data.price_exports[0].id].price_type_id=data.price_exports[0].price_type_id;
          price_exports[data.price_exports[0].id].discount_price_type_id=data.price_exports[0].discount_price_type_id;
          price_exports[data.price_exports[0].id].csv_delimiter=data.price_exports[0].csv_delimiter;
          price_exports[data.price_exports[0].id].export_nelikvid=data.price_exports[0].export_nelikvid;
          price_exports[data.price_exports[0].id].enable_export=data.price_exports[0].enable_export;
          price_exports[data.price_exports[0].id].periodically_send_to_email=data.price_exports[0].periodically_send_to_email;
          price_exports[data.price_exports[0].id].export_nelikvid_date_from=data.price_exports[0].export_nelikvid_date_from;
          price_exports[data.price_exports[0].id].export_nelikvid_date_to=data.price_exports[0].export_nelikvid_date_to;
          price_exports[data.price_exports[0].id].show_price_name=data.price_exports[0].show_price_name;
          price_exports[data.price_exports[0].id].price_from=data.price_exports[0].price_from;
          price_exports[data.price_exports[0].id].price_to=data.price_exports[0].price_to;
          price_exports[data.price_exports[0].id].description=data.price_exports[0].description;
          price_exports[data.price_exports[0].id].originality=data.price_exports[0].originality;
          price_exports[data.price_exports[0].id].address=data.price_exports[0].address;
          if(data.price_exports[0].selected_cols===null || data.price_exports[0].selected_cols==''){
            price_exports[data.price_exports[0].id].selected_cols=[
              {name:'article',descr:'Артикул',selected:1},
              {name:'brand',descr:'Бренд',selected:1},
              {name:'name',descr:'Наименование',selected:1},
              {name:'count',descr:'Количество (остаток)',selected:1},
              {name:'sale_price',descr:'Цена продажи',selected:1},
              {name:'price_list_name',descr:'Наименование прайс-листа',selected:0},
              {name:'sklad_name',descr:'Наименование склада',selected:0},
              {name:'uuid',descr:'Наш идентификатор',selected:0}
            ]
          }
          else {
            price_exports[data.price_exports[0].id].selected_cols=JSON.parse(data.price_exports[0].selected_cols);
          }
          if(typeof(data.brands[data.price_exports[0].id])!="undefined")
          for(var x in data.brands[data.price_exports[0].id]){
            price_exports[data.price_exports[0].id].brands.push({brand_id:data.brands[data.price_exports[0].id][x].brand_id,brand_name:data.brands[data.price_exports[0].id][x].brand_name});
          }
          if(typeof(data.export_from[data.price_exports[0].id])!="undefined")
          for(var x in data.export_from[data.price_exports[0].id]){
            if(data.export_from[data.price_exports[0].id][x].export_from_type=="1") {
              price_exports[data.price_exports[0].id].export_from.push({
                export_from_type:data.export_from[data.price_exports[0].id][x].export_from_type,
                export_from_id:data.export_from[data.price_exports[0].id][x].export_from_id,
                export_from_name:(typeof(data.sklads[data.export_from[data.price_exports[0].id][x].export_from_id])!="undefined"?data.sklads[data.export_from[data.price_exports[0].id][x].export_from_id].name:"")
              });
            }
            else {
              if(typeof(data.price_lists[data.export_from[data.price_exports[0].id][x].export_from_id])!="undefined")
              price_exports[data.price_exports[0].id].export_from.push({export_from_type:data.export_from[data.price_exports[0].id][x].export_from_type,export_from_id:data.export_from[data.price_exports[0].id][x].export_from_id,export_from_name:data.price_lists[data.export_from[data.price_exports[0].id][x].export_from_id].name,export_from_status:data.price_lists[data.export_from[data.price_exports[0].id][x].export_from_id].status});
            }
          }
        //price_exports[id]=data;
        print_price_export_form(id);
      });
  }

  function edit_price_export(id=0,is_new=0){
    if(id==0){
      if(typeof(price_exports[id])=="undefined" || is_new){
        init_price_export(id);
      }
      print_price_export_form(id);
    }
    else {
      print_price_export_form(id);
    }
  }

  function toggle_export_nelikvid_dates(export_id){
    if($("#export_nelikvid").prop("checked")) {
      $("#export_nelikvid_dates").css("display","table-row");
      set_export_value(export_id,'export_nelikvid',1);
    }
    else {
      $("#export_nelikvid_dates").css("display","none");
      set_export_value(export_id,'export_nelikvid',0);
    }
  }

  function toggle_enable_export(export_id){
    if($("#enable_export").prop("checked")) {
      set_export_value(export_id,'enable_export',1);
    }
    else {
      set_export_value(export_id,'enable_export',0);
    }
  }

  function toggle_periodically_send_to_email(export_id){
    if($("#periodically_send_to_email").prop("checked")) {
      set_export_value(export_id,'periodically_send_to_email',1);
      document.getElementById("edit_price_export_cron_tr").style.display="table-row";
    }
    else {
      set_export_value(export_id,'periodically_send_to_email',0);
      document.getElementById("edit_price_export_cron_tr").style.display="none";
    }
  }

  function toggle_show_price_name(export_id){
    if($("#show_price_name").prop("checked")) {
      set_export_value(export_id,'show_price_name',1);
    }
    else {
      set_export_value(export_id,'show_price_name',0);
    }
  }

  function print_price_export_form(export_id){
    create_window_centered_blue("edit_price_export_0_div",(export_id>0?"Редактирование экспорта прайс листов":"Создание экспорта прайс листов"),"edit_price_export_0","Загружаю");
    var send=[];
    send['price_type']=2;
    api_query_array("/api/index.php",send,"get_price_types").then(function(data){
      var table='';
      table+='<table class="table"><tbody>';
      table+='<tr><td>Наименование</td><td><input type="text" name="name" id="price_export_name" class="form-control input-sm" onchange="set_export_value('+export_id+',\'name\',this.value);"';
      if(typeof(price_exports[export_id])!="undefined" && price_exports[export_id]['name']!=""){
        table+=' value="'+price_exports[export_id]['name']+'"';
      }
      table+='></td></tr>';
      table+='<tr><td>Имя файла выгрузки</td><td><input type="text" name="filename" id="price_export_filename" class="form-control input-sm" onchange="set_export_value('+export_id+',\'filename\',this.value);"';
      if(typeof(price_exports[export_id])!="undefined" && price_exports[export_id]['filename']!=""){
        table+=' value="'+price_exports[export_id]['filename']+'"';
      }
      table+='></td></tr>';
      table+='<tr>\
        <td>Наценка</td>\
        <td><select name="price_type_id" id="price_export_price_type_id" onchange="set_export_value('+export_id+',\'price_type_id\',this.value);" class="form-control">';
        table+='<option value="0">По умолчанию</option>';
      for(var i in data.price_types){
        table+='<option value="'+data.price_types[i].id+'"';
        if(parseInt(data.price_types[i].id)==parseInt(price_exports[export_id]['price_type_id'])) table+=' selected="selected"';
        table+='>'+data.price_types[i].descr+'</option>';
      }
      table+='</select></td></tr>';
      var send1=[];
      send1['price_type']=1;
      api_query_array("/api/index.php",send1,"get_price_types").then(function(data1){
        table+='<tr>\
          <td>Скидка</td>\
          <td><select name="discount_price_type_id" id="price_export_discount_price_type_id" onchange="set_export_value('+export_id+',\'discount_price_type_id\',this.value);" class="form-control">';
          table+='<option value="0">По умолчанию</option>';
        for(var i in data1.price_types){
          table+='<option value="'+data1.price_types[i].id+'"';
          if(parseInt(data1.price_types[i].id)==parseInt(price_exports[export_id]['discount_price_type_id'])) table+=' selected="selected"';
          table+='>'+data1.price_types[i].descr+'</option>';
        }
        table+='</select></td></tr>';
        table+='<tr><td>Включить если цена :</td>\
        <td>от:<input type="text" placeholder="мин цена для включения" name="price_from" onchange="set_export_value('+export_id+',\'price_from\',this.value);" value="'+(price_exports[export_id]['price_from']>0?price_exports[export_id]['price_from']:"")+'"> до:<input type="text" placeholder="макс цена для включения" name="price_to" onchange="set_export_value('+export_id+',\'price_to\',this.value);" value="'+(price_exports[export_id]['price_to']>0?price_exports[export_id]['price_to']:"")+'"></td></tr>';
        table+='<tr><td>Бренды</td><td><button class="btn btn-sm btn-primary pull-right" onclick="set_export_brand('+export_id+');">Добавить</button></td></tr>';
        if(typeof(price_exports[export_id])!="undefined" && price_exports[export_id]['brands'].length>0){
          table+='<tr><td></td><td><table class="table table-hover"><tbody>';
          for(var i in price_exports[export_id]['brands']){
            table+='<tr><td>'+price_exports[export_id]['brands'][i].brand_name+' <button type="button" class="close pull-right" onclick="del_export_brand('+export_id+','+i+')"><span>×</span></button></td></tr>';
          }
          table+='<tbody></table></td></tr>';
        } 
        table+='<tr><td>Откуда выгружать</td><td><button class="btn btn-sm btn-primary pull-right" onclick="set_export_from('+export_id+');">Добавить</button></td></tr>';
        if(typeof(price_exports[export_id])!="undefined" && price_exports[export_id]['export_from'].length>0){
          table+='<tr><td></td><td><table class="table table-hover"><tbody>';
          for(var i in price_exports[export_id]['export_from']){
            table+='<tr>\
            <td>'+price_exports[export_id]['export_from'][i].export_from_name+' </td>\
            <td>'+(price_exports[export_id]['export_from'][i].export_from_type==1?"Склад":"Прайс")+'</td>\
            <td>'+((typeof(price_exports[export_id]['export_from'][i].export_from_status)=="undefined" || price_exports[export_id]['export_from'][i].export_from_status==1)?"":"<font color='red'>выкл</font>")+'</td>\
            <td><button type="button" class="close pull-right" onclick="del_export_from('+price_exports[export_id]['export_from'][i].export_from_type+','+price_exports[export_id]['export_from'][i].export_from_id+','+export_id+')"><span>×</span></button></td>\
            </tr>';
          }
          table+='<tbody></table></td></tr>';
        }
        table+='<tr><td colspan=2>Поля выгрузки</td></tr>';
        table+='<tr><td></td><td>';
        for(var cl in price_exports[export_id].selected_cols){
          table+='<input type="checkbox"> '+price_exports[export_id].selected_cols[cl].descr+'<br>';
        }
        table+='</td></tr>';
        table+='<tr><td>Выгружать неликвид</td><td><input type="checkbox" name="export_nelikvid" id="export_nelikvid" ';
        if(price_exports[export_id].export_nelikvid=="1") table+=' checked';
        table+=' onchange="toggle_export_nelikvid_dates('+export_id+');"></td></tr>';
        table+='<tr id="export_nelikvid_dates"';
        if(typeof(price_exports[export_id].export_nelikvid)=="undefined" || price_exports[export_id].export_nelikvid=="0"){
          table += ' style="display:none;"';
        }
        table+='><td>Период неликвидности</td><td>\
        <div class="input-group input-group-sm pull-right">\
          <span id="export_nelikvid_date_from_label" class="input-group-addon">с: </span>\
          <input type="date" name="export_nelikvid_date_from" id="export_nelikvid_date_from" class="form-control" value="'+price_exports[export_id].export_nelikvid_date_from+'" onchange="set_export_value('+export_id+',\'export_nelikvid_date_from\',this.value);">\
          <span id="export_nelikvid_date_to_label" class="input-group-addon">по: </span>\
          <input type="date" name="export_nelikvid_date_to" id="export_nelikvid_date_to" class="form-control" value="'+price_exports[export_id].export_nelikvid_date_to+'" onchange="set_export_value('+export_id+',\'export_nelikvid_date_to\',this.value);">\
        </div></td></tr>';
        table+='<tr><td>Отправлять на почту</td><td>\
        <input type="text" class="form-control" id="send_to_email" name="send_to_email" value="'+price_exports[export_id].send_to_email+'" onchange="set_export_value('+export_id+',\'send_to_email\',this.value);">\
        </td></tr>';
        table+='<tr><td>Отправлять со своей почты</td><td>\
        <input type="checkbox" id="send_from_my_email" name="send_from_my_email" '+(price_exports[export_id].send_from_my_email==1?"checked":"")+' onchange="set_send_from_my_email('+export_id+',this.value);">\
        </td></tr>';
        table+='<tr id="price_export_email_config_id_tr" '+(price_exports[export_id].send_from_my_email==0?"style='display:none;'":"")+'><td>Выберите почту с которой отправлять</td><td>';
        
        api_query("/api/index.php","some_form","get_email_configs").then(function(data2){
          table+='<select id="price_export_email_config_id" name="email_config_id" class="form-control" onchange="set_price_export_email_config_id('+export_id+')" '+(data2.email_configs.length==0?'title="Необходимо перейти в Настройки -> почтовые ящики и добавить хотя бы 1 ящик"':'')+'>';
          table+='<option value="0">Не выбран</option>';
          for(var i in data2.email_configs){
            table+='<option value="'+data2.email_configs[i].id+'" '+(parseInt(price_exports[export_id].email_config_id)==parseInt(data2.email_configs[i].id)?"selected":"")+'>'+data2.email_configs[i].name+'</option>';
          }
          table+='</select></td></tr>';
          table+='<tr><td>Формат отправки</td><td>\
          <select class="form-control" id="price_export_format" name="format" onchange="set_export_value('+export_id+',\'format\',this.value);">';
          table+='<option value="1"'+(price_exports[export_id].format=="1"?" selected":"")+'>CSV</option>';
          table+='<option value="2"'+(price_exports[export_id].format=="2"?" selected":"")+'>XLSX</option>';
          table+='<option value="3"'+(price_exports[export_id].format=="3"?" selected":"")+'>Авито</option>';
          table+='<option value="4"'+(price_exports[export_id].format=="4"?" selected":"")+'>Яндекс YML</option>';
          table+='</select>\
          </td></tr>';
          //if(price_exports[export_id].format=="1"){
            table+='<tr '+(price_exports[export_id].format=="1"?'style="display: table-row"':'style="display: none"')+' id="tr_csv_delimiter"><td>Разделитель полей</td><td>\
            <input type="text" class="form-control" id="csv_delimiter" name="csv_delimiter" value="'+price_exports[export_id].csv_delimiter+'" onchange="set_export_value('+export_id+',\'csv_delimiter\',this.value);">\
            </td></tr>';
            table+='<tr '+(price_exports[export_id].format=="3"?'style="display: table-row"':'style="display: none"')+' id="tr_description"><td>Описание *</td><td>\
            <textarea class="form-control" id="description" name="description" onchange="set_export_value('+export_id+',\'description\',this.value);">'+price_exports[export_id].description+'</textarea>\
            </td></tr>';
            table+='<tr '+(price_exports[export_id].format=="3"?'style="display: table-row"':'style="display: none"')+' id="tr_address"><td>Адрес *</td><td>\
            <textarea class="form-control" id="price_export_address" name="address" onchange="set_export_value('+export_id+',\'address\',this.value);">'+price_exports[export_id].address+'</textarea>\
            </td></tr>';
          //}
          //console.log(price_exports[export_id].originality)
          table += '<tr ' + (price_exports[export_id].format == "3" ? 'style="display: table-row"' : 'style="display: none"') + ' id="tr_uniqueness" style="display: none;">\
            <td>Уникальность</td>\
            <td>\
              <input type="radio" id="min_price" name="uniqueness" value="unset" ' + (price_exports[export_id].originality == 0 ? 'checked' : '') + ' onchange="set_export_value(' + export_id + ', \'originality\', 0);">\
              <label for="min_price">Не установлена</label><br>\
              <input type="radio" id="min_price" name="uniqueness" value="min_price" ' + (price_exports[export_id].originality == 1 ? 'checked' : '') + ' onchange="set_export_value(' + export_id + ', \'originality\', 1);">\
              <label for="min_price">По минимальной цене</label><br>\
              <input type="radio" id="max_price" name="uniqueness" value="max_price" ' + (price_exports[export_id].originality == 2 ? 'checked' : '') + ' onchange="set_export_value(' + export_id + ', \'originality\', 2);">\
              <label for="max_price">По максимальной цене</label><br>\
              <input type="radio" id="max_quantity" name="uniqueness" value="max_quantity" ' + (price_exports[export_id].originality == 3 ? 'checked' : '') + ' onchange="set_export_value(' + export_id + ', \'originality\', 3);">\
              <label for="max_quantity">По максимальному количеству</label>\
            </td>\
          </tr>';
          table+='<tr><td>Разрешить загрузку</td><td><input type="checkbox" name="enable_export" id="enable_export" ';
          if(price_exports[export_id].enable_export=="1") table+=' checked';
          table+=' onchange="toggle_enable_export('+export_id+');"></td></tr>';
          table+='<tr><td>ссылка на загрузку</td><td>https://sort1.pro/get_price_export_file.php?export_id='+export_id+'</td></tr>';
          table+='<tr><td>Периодически отправлять на почту</td><td><input type="checkbox" name="periodically_send_to_email" id="periodically_send_to_email" ';
          if(price_exports[export_id].periodically_send_to_email=="1") table+=' checked';
          table+=' onchange="toggle_periodically_send_to_email('+export_id+');"></td></tr>';
          table+='<tr><td>Показать наименования источника</td><td><input type="checkbox" name="show_price_name" id="show_price_name" ';
          if(price_exports[export_id].show_price_name=="1") table+=' checked';
          table+=' onchange="toggle_show_price_name('+export_id+');"></td></tr>';
          table+='<tr id="edit_price_export_cron_tr" '+(price_exports[export_id].periodically_send_to_email=="0"?'style="display:none"':"")+'><td>Периодичность</td><td><button class="btn btn-sm btn-primary" onclick="edit_price_export_cron('+export_id+')">Настроить</button>\
            <div id="edit_price_export_cron" style="position:relative; top:-320px; left: -100px"></div></td></tr>';
          table+='<tr><td colspan="2"><button class="btn btn-sm btn-success" onclick="send_price_export_to_email('+export_id+')">Отправить</button></td>';
          table+='<tr><td><button class="btn btn-sm btn-primary" onclick="save_price_export('+export_id+')">Сохранить</button></td>\
          <td><button class="btn btn-sm pull-right" onclick="$(\'#edit_price_export_0\').html(\'\');">Отменить</button></td></tr>';
          create_window_centered_blue("edit_price_export_0_div",(export_id>0?"Редактирование экспорта прайс листов":"Создание экспорта прайс листов"),"edit_price_export_0",table);
        })
      })
    })
  }

  function set_send_from_my_email(export_id){
    if($("#send_from_my_email").prop("checked")) {
      price_exports[export_id].send_from_my_email=1;
      $("#price_export_email_config_id_tr").css("display","table-row");
    }
    else {
      price_exports[export_id].send_from_my_email=0;
      $("#price_export_email_config_id_tr").css("display","none");
    }
    //price_exports[export_id].
  }

  function set_price_export_email_config_id(export_id){
    price_exports[export_id].email_config_id=$("#price_export_email_config_id").val();
  }

  function edit_price_export_cron(export_id){
    var send=[];
    send['price_export_id']=export_id;
    api_query_array("/api/index.php",send,"get_price_export_cron").then(function(data){
      var table='<table class="table"><thead><tr><th>время</th><th>день</th><th>месяц</th><th>год</th></tr></thead><tbody>';
      table+='<tr>';
      table+='<td><select name="export_cron_hours" multiple size="10">';
      table+='<option value="*" '+(data.price_export_cron.hours.indexOf("*")!=-1?" selected":"")+'>Каждый час</option>';
      //table+='<option value="*/2" '+(data.price_export_cron.hours=="*/2"?" selected":"")+'>Каждые 2 часа</option>';
      //table+='<option value="*/3" '+(data.price_export_cron.hours=="*/3"?" selected":"")+'>Каждые 3 часа</option>';
      //table+='<option value="*/6" '+(data.price_export_cron.hours=="*/6"?" selected":"")+'>Каждые 6 часов</option>';
      for (var i=1; i<24; i++){
        //console.log(data.price_export_cron.hours);
        //console.log(data.price_export_cron.hours.indexOf(i.toString()));
        table+='<option value="'+i+'" '+(data.price_export_cron.hours.indexOf(i.toString())!=-1?" selected":"")+'>в '+i+':00</option>';
      }
      table+='</select></td>';
      table+='<td><select name="export_cron_days" multiple size="10">';
      table+='<option value="*" '+(data.price_export_cron.days.indexOf("*")!=-1?" selected":"")+'>Каждый день</option>';
      //table+='<option value="*/2" '+(data.price_export_cron.days=="*/2"?" selected":"")+'>Каждые 2 дня</option>';
      //table+='<option value="*/3" '+(data.price_export_cron.days=="*/3"?" selected":"")+'>Каждые 3 дня</option>';
      //table+='<option value="*/6" '+(data.price_export_cron.days=="*/6"?" selected":"")+'>Каждые 4 дня</option>';
      for (var i=1; i<32; i++){
        table+='<option value="'+i+'" '+(data.price_export_cron.days.indexOf(i.toString())!=-1?" selected":"")+'> '+i+' числа</option>';
      }
      table+='</select></td>';
      table+='<td><select name="export_cron_months" multiple size="10">';
      table+='<option value="*" '+(data.price_export_cron.months.indexOf("*")!=-1?" selected":"")+'>Каждый месяц</option>';
      for (var i=1; i<13; i++){
        table+='<option value="'+i+'" '+(data.price_export_cron.months.indexOf(i.toString())!=-1?" selected":"")+'> '+i+'</option>';
      }
      table+='</select></td>';
      table+'<td></td></tr>';
      table+='<tr><td><button class="btn btn-xs btn-primary" onclick="save_price_export_cron('+export_id+');">Сохранить</button></td><td></td>\
        <td><button class="btn btn-xs btn-default" onclick="document.getElementById(\'edit_price_export_cron\').innerHTML=\'\';">Отменить</button></td><td></td></tr>';
      table+='</tbody></table>';
      create_window("edit_price_export_cron_div","Редактирование времени отправки","edit_price_export_cron",table);
      //$("#edit_price_export_cron").css("top","-100px");
    })
  }

  function save_price_export_cron(export_id){
    var send=[];
    send['price_export_id']=export_id;
    send['hours']=$("select[name=export_cron_hours]").val();
    send['days']=$("select[name=export_cron_days]").val();
    send['months']=$("select[name=export_cron_months]").val();
    api_query_array("/api/index.php",send,"save_price_export_cron").then(function(data){
      document.getElementById("edit_price_export_cron").innerHTML="";
    })
  }

  function edit_price_list_cron(price_id){
    var send=[];
    send['price_list_id']=price_id;
    api_query_array("/api/index.php",send,"get_price_list_cron").then(function(data){
      var table='<table class="table"><thead><tr><th>время</th><th>день</th><th>месяц</th><th>год</th></tr></thead><tbody>';
      table+='<tr>';
      table+='<td><select name="price_list_cron_hours" multiple size="10">';
      table+='<option value="*" '+(data.price_list_cron.hours.indexOf("*")!=-1?" selected":"")+'>Каждый час</option>';
      //table+='<option value="*/2" '+(data.price_export_cron.hours=="*/2"?" selected":"")+'>Каждые 2 часа</option>';
      //table+='<option value="*/3" '+(data.price_export_cron.hours=="*/3"?" selected":"")+'>Каждые 3 часа</option>';
      //table+='<option value="*/6" '+(data.price_export_cron.hours=="*/6"?" selected":"")+'>Каждые 6 часов</option>';
      for (var i=1; i<24; i++){
        //console.log(data.price_export_cron.hours);
        //console.log(data.price_export_cron.hours.indexOf(i.toString()));
        table+='<option value="'+i+'" '+(data.price_list_cron.hours.indexOf(i.toString())!=-1?" selected":"")+'>в '+i+':00</option>';
      }
      table+='</select></td>';
      table+='<td><select name="price_list_cron_days" multiple size="10">';
      table+='<option value="*" '+(data.price_list_cron.days.indexOf("*")!=-1?" selected":"")+'>Каждый день</option>';
      //table+='<option value="*/2" '+(data.price_export_cron.days=="*/2"?" selected":"")+'>Каждые 2 дня</option>';
      //table+='<option value="*/3" '+(data.price_export_cron.days=="*/3"?" selected":"")+'>Каждые 3 дня</option>';
      //table+='<option value="*/6" '+(data.price_export_cron.days=="*/6"?" selected":"")+'>Каждые 4 дня</option>';
      for (var i=1; i<32; i++){
        table+='<option value="'+i+'" '+(data.price_list_cron.days.indexOf(i.toString())!=-1?" selected":"")+'> '+i+' числа</option>';
      }
      table+='</select></td>';
      table+='<td><select name="price_list_cron_months" multiple size="10">';
      table+='<option value="*" '+(data.price_list_cron.months.indexOf("*")!=-1?" selected":"")+'>Каждый месяц</option>';
      for (var i=1; i<13; i++){
        table+='<option value="'+i+'" '+(data.price_list_cron.months.indexOf(i.toString())!=-1?" selected":"")+'> '+i+'</option>';
      }
      table+='</select></td>';
      table+'<td></td></tr>';
      table+='<tr><td><button class="btn btn-xs btn-primary" onclick="save_price_list_cron('+price_id+');" type="button">Сохранить</button></td><td></td>\
        <td><button class="btn btn-xs btn-default" onclick="document.getElementById(\'edit_price_list_cron\').innerHTML=\'\';" type="button">Отменить</button></td><td></td></tr>';
      table+='</tbody></table>';
      create_window("edit_price_list_cron_"+price_id+"_div","Редактирование времени отправки","edit_price_list_cron_"+price_id,table);
      //$("#edit_price_export_cron").css("top","-100px");
    })
  }

  function save_price_list_cron(price_id){
    var send=[];
    send['price_list_id']=price_id;
    send['hours']=$("select[name=price_list_cron_hours]").val();
    send['days']=$("select[name=price_list_cron_days]").val();
    send['months']=$("select[name=price_list_cron_months]").val();
    api_query_array("/api/index.php",send,"save_price_list_cron").then(function(data){
      document.getElementById("edit_price_list_cron_"+price_id).innerHTML="";
    })
  }

  function send_price_export_to_email(id){
    $.blockUI({ css: { 
      border: 'none', 
      padding: '15px', 
      backgroundColor: '#000', 
      '-webkit-border-radius': '10px', 
      '-moz-border-radius': '10px', 
      opacity: .5, 
      color: '#fff'
      },
      message: 'Формирую файл...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
    }); 
    var send1=[];
    send1['price_export']=price_exports[id];
    api_query_array("/api/index.php",send1,"save_price_export").then(function(data1){
      var send=[];
      send['price_export_id']=id;
      api_query_array("/api/index.php",send,"send_export_file_to_email").then(function(data){
        if(data.status=="ok"){
          $.unblockUI();
          bootbox.alert("Файл успешно отправлен на указанный email");
        }
      });
    });
  }

  function set_export_from(export_id){
    var table='<table class="table table-hover"><thead>';
    create_window_centered_blue("export_from_select_div","Выберите откуда выгружать","export_from_select","Загружаю ....");
    api_query("/api/index.php","some_form","get_sklads").then(function(sklads){
      api_query("/api/index.php","some_form","get_price_lists").then(function(price_lists){
        var data=sklads;
        var datalen=data.sklads.length;
        //sklads=data.sklads;
        var table="<b>Склады</b>";
        table+="<table class=\"table table-hover\"><thead><tr><th></th><th>№</th><th>Наименование</th><th>Адрес</th><th>Описание</th><th>Город</th><th>Позиций</th><th>Кол-во</th></tr></thead><tbody>";
        for (var i=0; i<datalen; i++){
          table += "<tr>\
          <td><input type='checkbox'";
          for (var x in price_exports[export_id]['export_from']){
            if(price_exports[export_id]['export_from'][x].export_from_type==1 && price_exports[export_id]['export_from'][x].export_from_id==data.sklads[i].id){
              table+=" checked ";
              break;
            }
          }
          table+=" onchange='this.checked?add_export_from(1,"+data.sklads[i].id+",\""+data.sklads[i].name+"\","+export_id+"):del_export_from(1,"+data.sklads[i].id+","+export_id+")'></td>";
            table += "<td>"+(i+1)+"<div id='stock_balances_"+data.sklads[i].id+"'></div>\
            <div id='edit_sklad_"+data.sklads[i].id+"'></div>\
            <div id='sklad_details_"+data.sklads[i].id+"'></div></td>\
            <td>" + data.sklads[i].name + "</td><td>"+data.sklads[i].address+"</td><td>"+data.sklads[i].descr+"</td><td>"+data.sklads[i].city_name+"</td><td>";
            if(parseFloat(data.sklads[i].sklad_positions)>0) table+=data.sklads[i].sklad_positions;
            else table+="0";
            table+="</td><td>";
            if(parseFloat(data.sklads[i].sklad_pos_count)) table+=data.sklads[i].sklad_pos_count;
            else table+="0";
            table+="</td>";
            table += "</tr>";
        }
        table+= "</tbody></table>";

        data=price_lists;
        datalen=data.price_lists.length;
        table+="<b>Прайс листы</b>";
        table+="<table class=\"table table-hover\"><thead><tr><th></th><th>№</th><th>Наименование</th><th>Поставщик</th><th>Город</th><th>наценка</th><th>Позиций</th><th>Кол-во</th><th>Дата создания</th><th>Обновлен</th><th>Статус</th></tr></thead><tbody>";
        for (var i=0; i<datalen; i++){
          table += "<tr>\
          <td><input type='checkbox'";
          for (var x in price_exports[export_id]['export_from']){
            if(price_exports[export_id]['export_from'][x].export_from_type==2 && price_exports[export_id]['export_from'][x].export_from_id==data.price_lists[i].id){
              table+=" checked ";
              break;
            }
          }
          table+=" onchange='this.checked?add_export_from(2,"+data.price_lists[i].id+",\""+data.price_lists[i].name+"\","+export_id+"):del_export_from(2,"+data.price_lists[i].id+","+export_id+")'></td>\
          <td><div id='price_list_details_"+data.price_lists[i].id+"'></div>"+(i+1)+"<div id='edit_price_list_"+data.price_lists[i].id+"'></div></td>\
          <td>" + data.price_lists[i].name + "</td>\
          <td>"+data.price_lists[i].company_name+"</td>\
          <td>"+data.price_lists[i].city_name+"</td>\
          <td>"+data.price_lists[i].default_markup+"</td>";
          table +="<td nowrap style='text-align: right'>"+formatNumber(parseInt(data.price_lists[i].positions))+"</td>";
          table +="<td nowrap style='text-align: right'>"+formatNumber(parseInt(data.price_lists[i].pos_count))+"</td>";
          table +="<td>"+convertTZ(data.price_lists[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>\
          <td>"+convertTZ(data.price_lists[i].update_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>\
          <td>"+(data.price_lists[i].status=="1"?"<span style='color:green'>Включен</span>":"<span style='color:red'>Выключен</span>")+"</td>";
          table += "</tr>";
        }
        table+='<tr><td colspan="11"><center><button class="btn btn-sm btn-primary" onclick="$(\'#export_from_select\').html(\'\');">Закрыть</button></center></td></tr>';
        table+= "</tbody></table>";
        create_window_centered_blue("export_from_select_div","Выберите откуда выгружать","export_from_select",table);
      });
    });
    //table+='<div id="export_brands_select_list"></div>';
    //table+='<hr><div class="row"><div class="col-sm-6"><button class="btn btn-sm btn-primary">Сохранить</button></div>\
    //  <div class="col-sm-6"><button class="btn btn-sm pull-right">Отменить</button></div></div>';
    //create_window("export_brands_select_div","Добавление бренда","export_brands_select",table);
  }

  function set_export_brand(export_id){
    var table='<div class="row"><div class="col-sm-5">Введите бренд: </div>\
      <div class="col-sm-7"><input type="text" class="form-control input-sm" name="brand_name" onchange="select_export_brands(this.value,'+export_id+')"></div></div>';
    table+='<div id="export_brands_select_list"></div>';
    //table+='<hr><div class="row"><div class="col-sm-6"><button class="btn btn-sm btn-primary">Сохранить</button></div>\
    //  <div class="col-sm-6"><button class="btn btn-sm pull-right">Отменить</button></div></div>';
    create_window_centered_blue("export_brands_select_div","Добавление бренда","export_brands_select",table);
  }

  function select_export_brands(brand,export_id){
    var send=[];
    send['brand']=brand;
    api_query_array("/api/index.php",send,"search_brand_id").then(function(data){
      if(Object.keys(data.brands.brand_ids).length==1){
        for (var i in data.brands.brand_ids){
          add_export_brand(data.brands.brand_ids[i]['brand_id'],data.brands.brand_ids[i]['brand'],export_id);
        }
      }
      else {
        var table='<table class="table table-hover"><tbody>';
        for (var i in data.brands.brand_ids){
          table+='<tr onclick="add_export_brand('+data.brands.brand_ids[i]['brand_id']+',\''+data.brands.brand_ids[i]['brand']+'\','+export_id+')"><td>'+data.brands.brand_ids[i]['brand']+'</td><td>'+data.brands.brand_ids[i]['brand_id']+'</td></tr>';
        }
        table+='</tbody></table>';
        create_window('export_brands_select_list_div','Выберите бренд из списка','export_brands_select_list',table);
      }
    })
  }

  function set_export_value(export_id, key, value) {
    if (typeof(price_exports[export_id]) == "undefined") {
      init_price_export(export_id);
    }
    if (typeof(price_exports[export_id][key]) != "undefined") {
      price_exports[export_id][key] = value;
      if (key == "format") {
        if (value == "1") {
          $("#tr_csv_delimiter").css("display", "table-row");
          $("#tr_description").css("display", "none");
          $("#tr_address").css("display", "none");
          $("#tr_uniqueness").css("display", "none"); // Скрываем элементы уникальности
        } else if (value == "2") {
          $("#tr_csv_delimiter").css("display", "none");
          $("#tr_description").css("display", "none");
          $("#tr_address").css("display", "none");
          $("#tr_uniqueness").css("display", "none"); // Скрываем элементы уникальности
        } else if (value == "3") {
          $("#tr_csv_delimiter").css("display", "none");
          $("#tr_address").css("display", "table-row");
          $("#tr_description").css("display", "table-row");
          $("#tr_uniqueness").css("display", "table-row"); // Отображаем элементы уникальности
        }
      }
    }
  }

  function add_export_brand(brand_id,brand_name,export_id){
    if(typeof(price_exports[export_id])=="undefined") {
      init_price_export(export_id);
    }
    var is_exist=0;
    for(var i in price_exports[export_id].brands){
      if(parseInt(price_exports[export_id].brands[i].brand_id)==parseInt(brand_id)){
        is_exist=1;
        break;
      }
    }
    if(is_exist==0){
      price_exports[export_id].brands.push({brand_id:brand_id,brand_name:brand_name});
      $("#export_brands_select").html('');
      edit_price_export(export_id);
    }
    else {
      bootbox.alert("Этот бренд уже есть в списке (возможно алиас бренда)");
    }
  }

  function del_export_brand(export_id,i){
    price_exports[export_id]['brands'].splice(i,1);
    edit_price_export(export_id);
  }

  function add_export_from(type,id,name,export_id){
    price_exports[export_id].export_from.push({export_from_type:type,export_from_id:id,export_from_name:name});
    edit_price_export(export_id);
  }

  function del_export_from(type,id,export_id){
    for (var i in price_exports[export_id]['export_from']){
      if(price_exports[export_id]['export_from'][i].export_from_type==type && price_exports[export_id]['export_from'][i].export_from_id==id){
        price_exports[export_id]['export_from'].splice(i,1);
        edit_price_export(export_id);
        break;
      }
    }
  }

  function get_price_exports(){
    api_query("/api/index.php","some_from","get_price_exports").then(function(data){
      var table='<table class="table table-hover"><thead><th>№</th><th>Наименование</th><th>Имя файла выгрузки</th><th>бренды</th><th>откуда выгружаем</th></thead><tbody>';
      if(data.status=="ok" && data.price_exports.length>0){
        for(var i in data.price_exports){
          price_exports[data.price_exports[i].id]={};
          price_exports[data.price_exports[i].id].id=data.price_exports[i].id;
          table+='<tr><td>'+(parseInt(i)+1)+'</td><td>'+data.price_exports[i].name+'</td><td>'+data.price_exports[i].filename+'</td>';
          price_exports[data.price_exports[i].id].name=data.price_exports[i].name;
          price_exports[data.price_exports[i].id].filename=data.price_exports[i].filename;
          price_exports[data.price_exports[i].id].email_config_id=data.price_exports[i].email_config_id;
          price_exports[data.price_exports[i].id].send_from_my_email=data.price_exports[i].send_from_my_email;
          price_exports[data.price_exports[i].id].brands=[];
          price_exports[data.price_exports[i].id].export_from=[];
          price_exports[data.price_exports[i].id].price_type_id=data.price_exports[i].price_type_id;
          table+='<td><table class="table"><tbody>';
          if(typeof(data.brands[data.price_exports[i].id])!="undefined")
          for(var x in data.brands[data.price_exports[i].id]){
            table+='<tr><td>'+data.brands[data.price_exports[i].id][x].brand_name+'</td></tr>';
            price_exports[data.price_exports[i].id].brands.push({brand_id:data.brands[data.price_exports[i].id][x].brand_id,brand_name:data.brands[data.price_exports[i].id][x].brand_name});
          }
          table+='</tbody></table></td>';
          table+='<td><table class="table"><tbody>';
          if(typeof(data.export_from[data.price_exports[i].id])!="undefined")
          for(var x in data.export_from[data.price_exports[i].id]){
            table+='<tr>';
            table+=(typeof(data.sklads[data.export_from[data.price_exports[i].id][x].export_from_id])!="undefined" && data.export_from[data.price_exports[i].id][x].export_from_type=="1")?
              "<td>"+data.sklads[data.export_from[data.price_exports[i].id][x].export_from_id].name+"</td><td style='width:80px;'>Склад</td>":
                (typeof(data.price_lists[data.export_from[data.price_exports[i].id][x].export_from_id])!="undefined"?
                  "<td>"+data.price_lists[data.export_from[data.price_exports[i].id][x].export_from_id].name+"</td><td style='width:80px;'>Прайс</td>":"<td></td>");
            if(data.export_from[data.price_exports[i].id][x].export_from_type=="2"){
              if(typeof(data.price_lists[data.export_from[data.price_exports[i].id][x].export_from_id])!="undefined"){
                if(data.price_lists[data.export_from[data.price_exports[i].id][x].export_from_id].status==0){
                  table+="<td><font color='red'>Выкл</font></td>";
                }
                else table+="<td></td>";
              }
              else table+="<td></td>";
            }
            else table+="<td></td>";
            table+='</tr>';
            if(data.export_from[data.price_exports[i].id][x].export_from_type=="1") {
              price_exports[data.price_exports[i].id].export_from.push(
                {
                  export_from_type:data.export_from[data.price_exports[i].id][x].export_from_type,
                  export_from_id:data.export_from[data.price_exports[i].id][x].export_from_id,
                  export_from_name:(typeof(data.sklads[data.export_from[data.price_exports[i].id][x].export_from_id])!="undefined")?data.sklads[data.export_from[data.price_exports[i].id][x].export_from_id].name:""
                });
            }
            else {
              if(typeof(data.price_lists[data.export_from[data.price_exports[i].id][x].export_from_id])!="undefined")
              price_exports[data.price_exports[i].id].export_from.push({export_from_type:data.export_from[data.price_exports[i].id][x].export_from_type,export_from_id:data.export_from[data.price_exports[i].id][x].export_from_id,export_from_name:data.price_lists[data.export_from[data.price_exports[i].id][x].export_from_id].name,export_from_status:data.price_lists[data.export_from[data.price_exports[i].id][x].export_from_id].status});
            }
          }
          
          table+='</tbody></table>';
          table+='</td>';
          table+='<td nowrap>\
          <div class="btn-group" style="display: flex;">\
          &nbsp;<a onclick="get_price_export('+data.price_exports[i].id+',0);" title="Редактировать"><img src="/new_images/edit.svg" class="menuimg"></a>\
          <a title="Удалить" onclick="bootbox.confirm(\'Вы точно хотите удалить?\',function(result){ if(result) {delete_price_export('+data.price_exports[i].id+')} });"><img src="/new_images/garbage.svg" class="menuimg"></a>\
          <a onclick="get_export_file('+data.price_exports[i].id+',\'xlsx\');"><img src="/new_images/excel_32.png" class="menuimg"></a>\
          <a onclick="get_export_file('+data.price_exports[i].id+',\'csv\');"><img src="/new_images/csv_128.png" class="menuimg"></a>\
          <a onclick="get_export_file('+data.price_exports[i].id+',\'Авито\');"><img src="/new_images/avito_logo.png" style="width:60px" class="menuimg"></a>\
          <a onclick="get_export_file('+data.price_exports[i].id+',\'yandex\');"><img src="/new_images/ya.svg" style="width:32px" class="menuimg"></a>\
          <input type="checkbox" name="price_export_show_price_name_'+data.price_exports[i].id+'" id="price_export_show_price_name_'+data.price_exports[i].id+'" onchange="set_show_price_name('+data.price_exports[i].id+')" '+(data.price_exports[i].show_price_name==1?"checked":"")+'> Показать наименования поставщиков\
          </div></td>';
          table+='</tr>';
        }
      }
      table+='</tbody></table>';
      document.getElementById("price_export_list").innerHTML=table;
    })
  }

  function set_show_price_name(export_id){
    var send=[];
    send['price_export_id']=export_id;
    if($("#price_export_show_price_name_"+export_id).prop("checked")) send['show_price_name']=1;
    else send['show_price_name']=0;
    api_query_array("/api/index.php",send,"set_price_export_show_price_name").then(function(data){

    })
  }

  function save_price_export(export_id){
    var send=[];
    send['price_export']=price_exports[export_id];
    api_query_array("/api/index.php",send,"save_price_export").then(function(data){
      if(data.status=="ok"){
        $("#edit_price_export_0").html('');
        get_price_exports();
      }
    })
  }

  function delete_price_export(export_id){
    var send=[];
    send['price_export_id']=export_id;
    api_query_array("/api/index.php",send,"delete_price_export").then(function(data){
      if(data.status=="ok"){
        //$("#edit_price_export_0").html('');
        get_price_exports();
      }
    })
  }

  function get_export_file(export_id,file_type='csv'){
    var send=[];
    send['price_export_id']=export_id;
    send['file_type']=file_type;
    send['show_price_name']=$("#price_export_show_price_name_"+export_id).prop("checked");
    $.blockUI({ css: { 
      border: 'none', 
      padding: '15px', 
      backgroundColor: '#000', 
      '-webkit-border-radius': '10px', 
      '-moz-border-radius': '10px', 
      opacity: .5, 
      color: '#fff'
      },
      message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
    });
    api_query_array("/api/index.php",send,"get_export_file").then(function(data){
      if(data.status=="ok"){
        if(data.category){
          bootbox.alert({
            message: data.message,
            callback: function() {
                get_detail_without_category(data.price_export_id);
            }
          });
        }
        else if(data.avitoCategory){
          bootbox.alert({
            message: data.message,
            callback: function() {
              get_unlinked_avito_categorys(data.price_export_id);
            }
          });
        }
        else{
          if (file_type == 'Авито') {
            var blob = b64toBlob(data.file, { type: 'application/xml' });  
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = "export."+data.file_type;
            link.click();
            bootbox.alert({
              message: data.message,
            });
          } else {
            if (file_type == 'yandex') {
              var blob = b64toBlob(data.file, { type: 'application/xml' });  
              var link = document.createElement('a');
              link.href = window.URL.createObjectURL(blob);
              link.download = "export.xml";
              link.click();
              bootbox.alert({
                message: data.message,
              });
            } else {
            var blob = b64toBlob(data.file); //new Blob([data.file]);
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = "export."+file_type;
            link.click();
            }
          }
        }
      }
      $.unblockUI();
    });
  }

  function get_sklad_doubles(sklad_id){
    var send=[];
    send['sklad_id']=sklad_id;
    $.blockUI({ css: { 
      border: 'none', 
      padding: '15px', 
      backgroundColor: '#000', 
      '-webkit-border-radius': '10px', 
      '-moz-border-radius': '10px', 
      opacity: .5, 
      color: '#fff'
      },
      message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
    });
    api_query_array("/api/index.php",send,"get_sklad_doubles").then(function(data){
      if(data.status=="ok"){
        $.unblockUI();
        var table='<div style="height: 500px; width:600px; overflow: auto"><table class="table table-hover"><thead><th>№</th><th>Артикул</th><th>кол-во дублей</th><th>Наименование</th></thead><tbody>';
        var x=1;
        for(var i in data.doubles){
          table+='<tr><td>'+x+'</td><td>'+data.doubles[i].article+'</td><td>'+data.doubles[i].dubles+'</td><td>'+data.doubles[i].name+'</td></tr>';
          x++;
        }
        table+='</tbody></table></div>';
        create_window("sklad_doubles_"+sklad_id+"_div","Задвоения артикулов на складе","sklad_doubles_"+sklad_id,table);
      }
    });
  }

  var selected_details_group = [];

  function get_detail_without_category(price_export_id){
    // if ($('#edit_price_export_0_div').children().length === 0) {
    //   selected_details_group = [];
    // }

    var request_params = {};
    request_params['search_article'] = $("#price_export_search_article_input").val();
    request_params['search_name'] = $("#price_export_search_name_input").val();
    request_params['show_zero'] = $("#price_export_show_zero_checkbox").prop("checked");
    request_params['selected_page'] = $("#price_export_selected_page_input").val();
    request_params['price_export_id'] = price_export_id;

    $.blockUI({
      css: { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .5, 
        color: '#fff'
      },
      message: 'Идет загрузка, пожалуйста подождите... <a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
    });

    api_query_array("/api/index.php", request_params, "get_details_from_without_category").then(function(data){
      if (data.status == "ok") {
        if(data.detail_group_details.length < 0){
          get_export_file(data.price_export_id,'Авито');
        }

        var table = '<div class="row">';
        table += '<div class="col-sm-3"></div>';
        table += '<div class="col-sm-9">';
        table += '<div class="input-group input-group-sm pull-right">';
        table += '<div id="price_export_action_buttons_0"></div>';
        table += '<span class="input-group-addon" style="cursor: pointer; background: #337ab7; color: white" onclick="price_export_bind_details_to_groups(0, 0, 0, \'stock\')">Привязать</span>';
        table += '<span class="input-group-addon">';
        table += '<input type="checkbox" onchange="get_detail_without_category();" name="show_zero" id="price_export_show_zero_checkbox"';
        if (typeof(data.show_zero) != "undefined" && data.show_zero) table += ' checked';
        table += '>Показать 0 остатки</span>';
        table += '<label for="price_export_search_article_input" class="input-group-addon">Артикул:</label>';
        table += '<input class="form-control" type="text" name="search_article" id="price_export_search_article_input" onchange="get_detail_without_category();" value="';
        if (typeof(request_params['search_article']) != "undefined") table += request_params['search_article'];
        table += '">';
        table += '<label for="price_export_search_name_input" class="input-group-addon">Наименование:</label>';
        table += '<input class="form-control" type="text" name="search_name" id="price_export_search_name_input" onchange="get_detail_without_category();" value="';
        if (typeof(request_params['search_name']) != "undefined") table += request_params['search_name'];
        table += '">';
        table += '<input type="hidden" name="selected_page" id="price_export_selected_page_input" ';
        var selected_page = data.hasOwnProperty('selected_page') ? parseInt(data.selected_page) : 1;
        table += ' value="' + selected_page + '">';
        table += '</div></div></div>';

        table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
        var x = 0, y = 0, xx = 0, yy = 0;
        for (var i = 1; i <= data.detail_group_pages; i++) {
          if (i > (selected_page + 6) && i < (data.detail_group_pages - 1)) {
            x = 1;
          } else x = 0;
          if (i < (selected_page - 6) && i != 1) {
            y = 1;
          } else y = 0;
          if (x == 1) {
            if (xx == 0) {
              table += '<li><a href="#" onclick="$(\'#price_export_selected_page_input\').val(\'' + i + '\'); get_detail_without_category()">...</a></li>';
            }
            xx++;
          } else {
            if (y == 1) {
              if (yy == 0) {
                table += '<li><a href="#" onclick="$(\'#price_export_selected_page_input\').val(\'' + i + '\'); get_detail_without_category()">...</a></li>';
              }
              yy++;
            } else {
              table += '<li';
              if (selected_page == i) table += " class='active'";
              table += '><a href="#" onclick="$(\'#price_export_selected_page_input\').val(\'' + i + '\'); get_detail_without_category()">' + i + '</a></li>';
            }
          }
        }
        table += '</ul></div>';

        // Table
        table += '<div style="height: 73vh; overflow:auto;">\
        <table class="table table-hover">\
        <thead>\
        <tr><th><input type="checkbox" id="price_export_select_all_checkbox" onclick="price_export_check_all_details();"></th><th>#</th><th>Артикул</th><th>Наименование</th><th>Бренд</th></tr>\
        </thead><tbody>';
        Object.keys(data.detail_group_details).forEach(function(key, index) {
          var detail = data.detail_group_details[key];
          var detail_id = detail.detail_id;
          table += '<tr><td>';
          table += '<input type="checkbox" id="price_export_checkbox_' + detail_id + '" onchange="price_export_handle_checkbox_change(' + detail_id + ')"';
          if (selected_details_group.includes(parseInt(detail_id))) {
              table += ' checked';
          }
          table += '></td>';
          table += '<td>' + (index + 1) + '</td><td>' + detail.article + '</td><td>' + detail.name + '</td><td>' + detail.brand + '</td></tr>';
        });
        table += '</tbody></table><div>';

        $.unblockUI();
        create_window_centered_blue("edit_price_export_details_div", "Export Details", "edit_price_export_0", table);
      }
    }, function(data){
      $.unblockUI();
    });
}

function price_export_bind_details_to_groups(group_id = 0, glubina = 0, force = 0, from = "sklad") {
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
      // if(glubina>1) table+='<tr><td><button class="btn btn-xs btn-primary" onclick="edit_detail_group('+group_id+',0);">добавить</button></td><td> </td></tr>';
      for (let i = 0; i < len; i++) {
        var detailGroup = data.detail_groups[i];
        
        // Проверка has_in_group
        var hasInGroup = detailGroup.has_in_group == "1" ? 
          '<a onclick="select_detail_groups_undefined_details_export(' + detailGroup.id + ',' + (glubina) + ',0,\'' + from + '\');" id="new_sel_det_group_znak_' + detailGroup.id + '">+</a>' 
          : '';

        table += '<tr>\
          <td>' + hasInGroup + '<a onclick="bootbox.confirm(\'Вы точно хотите привязать товары к ' + detailGroup.group_name + '?\',function(result){ if(result) set_detail_group_undefined_detail_export(' + detailGroup.id + ',\'' + from + '\');})">' + detailGroup.group_name + '</a></td>\
          <td></td></tr>';
          
        table += '<tr><td id="new_sel_undefined_detail_groups_list_' + detailGroup.id + '" style="padding-left:' + ((glubina + 1) * 10) + 'px;"></td><td> </td></tr>';
      }
      table += '</tbody></table>';
      
      if (glubina == 1)
        create_window("new_sel_undefined_detail_groups_list_" + group_id + "_div", "выберите группу", "price_export_action_buttons_" + group_id, table);
      else
        document.getElementById("new_sel_undefined_detail_groups_list_" + group_id).innerHTML = table;

      $("#new_sel_det_group_znak_" + group_id).html('-');
    } else {
      document.getElementById("sel_undefined_detail_groups_list_" + group_id).innerHTML = 'Группы еще не заведены';
    }
  });
}

function set_detail_group_undefined_detail_export(group_id,from="sklad") {
  let send = [];
  send['group_id'] = group_id;
  send['details'] = selected_details_group;

  api_query_array("/api/index.php", send, "set_undefined_details_group").then(function (data) {
    selected_details_group = [];
    if (data.status == "ok") {
      get_detail_without_category();
      //$("#detail_group_details_from_sklad_0").html('');
    } else {
      console.error("Не удалось переместить категорию вверх");
    }
  });
}

function select_detail_groups_undefined_details_export(group_id = 0, glubina = 0, force = 0, from = "sklad") {
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
          '<a onclick="select_detail_groups_undefined_details_export(' + detailGroup.id + ',' + (glubina) + ',0,\'' + from + '\');" id="new_sel_det_group_znak_' + detailGroup.id + '">+</a>' 
          : '';

        table += '<tr>\
          <td>' + hasInGroup + '<a onclick="bootbox.confirm(\'Вы точно хотите привязать товары к ' + detailGroup.group_name + '?\',function(result){ if(result) set_detail_group_undefined_detail_export(' + detailGroup.id + ',\'' + from + '\');})">' + detailGroup.group_name + '</a></td>\
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

function price_export_handle_checkbox_change(detailId) {
  var checkbox = document.getElementById('price_export_checkbox_' + detailId);
  if (checkbox.checked) {
    selected_details_group.push(detailId);
  } else {
      var index = selected_details_group.indexOf(detailId);
      if (index > -1) {
        selected_details_group.splice(index, 1);
      }
  }
}

function price_export_check_all_details() {
  var checkboxes = document.querySelectorAll('[id^="price_export_checkbox_"]');
  var selectAllCheckbox = document.getElementById('price_export_select_all_checkbox');

  if (selectAllCheckbox.checked) {
      checkboxes.forEach(function(checkbox) {
          var detailId = parseInt(checkbox.id.split('_')[3]);
          if (!selected_details_group.includes(detailId)) {
            selected_details_group.push(detailId);
          }
          checkbox.checked = true;
      });
  } else {
      checkboxes.forEach(function(checkbox) {
        var detailId = parseInt(checkbox.id.split('_')[3]);
            var index = selected_details_group.indexOf(detailId);
            if (index !== -1) {
              selected_details_group.splice(index, 1);
            }
            checkbox.checked = false;
    });
  }
}

function get_unlinked_avito_categorys(price_export_id) {
  var send = [];
  send['price_export_id'] = price_export_id;
  api_query_array("/api/index.php", send, "get_unlinked_avito_categorys").then(function (data) {
    if (data.status == "ok") {
        if(data.avito_categories.length == 0){
          get_export_file(price_export_id,'Авито');
          $("#edit_price_export_0").html('');
          return;
        }
        var len = data.avito_categories.length;
        var table = '<table class="table table-bordered" style="font-size: 16px;">\
                        <thead>\
                            <tr>\
                                <th>Название категории</th>\
                                <th></th>\
                            </tr>\
                        </thead>\
                        <tbody>';

        for (let i = 0; i < len; i++) {
          let category = data.avito_categories[i];
          table += '<tr id="bind_row_' + category.category_id + '">\
                      <td>';
  
          table += category.group_name + '</td>\
                      <td>\
                          <button class="btn btn-xs btn-primary" onclick="showCategorySelection(' + category.category_id + ', ' + category.group_id + ', '+ price_export_id +');">\
                            Привязать\
                          </button>\
                          <div id="category_selection_'+category.category_id+'"></div>\
                      </td>\
                    </tr>';
        }

        table += '</tbody></table>';
        create_window_centered_blue("new_avito_category_div", "Привязка к категории", "edit_price_export_0", table);
    } else {
        // Обработка ошибки или другие действия при неудачном запросе
    }
  });
}

function showCategorySelection(category_id, group_id, price_export_id) {
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
              table += '<a onclick="get_child_category_avito_export(' + category.id + ', ' + 1 + ', ' + group_id + ', ' + price_export_id + ');" id="avito_category_znak_' + category.id + '">+</a> ';
            }
    
            table += category.name + '</td>\
                        <td>\
                          <button class="btn btn-xs btn-primary" onclick="binding_marketplace_export(0,' + category.id + ',' + group_id + ', '+ price_export_id +');">\
                            Привязать\
                          </button>\
                        </td>\
                      </tr>';
          }

          table += '</tbody></table>';
          create_window("category_selection_div_" + category_id, "Привязка к категории", "category_selection_" + category_id, table);
      } else {
          // Обработка ошибки или другие действия при неудачном запросе
      }
  });
}

function get_child_category_avito_export(parent_id, glubina, category_id, price_export_id) {
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
              table += '<a onclick="get_child_category_avito(' + category.id + ', ' + glubina + ', ' + category_id + ', ' + price_export_id + ');" id="avito_category_znak_' + category.id + '">+</a> ';
            }
    
            table += category.name + '\
                        </td>\
                        <td>\
                          <button class="btn btn-xs btn-primary" onclick="binding_marketplace_export(' + glubina + ',' + category.id + ', ' + category_id + ', '+ price_export_id +');">\
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

function binding_marketplace_export(glubina, avito_category_id, category_id, price_export_id) {
  var send = {
    'avito_category_id': avito_category_id,
    'category_id': category_id,
  };

  api_query_array("/api/index.php", send, "toggle_marketplace_binding").then(function (data) {
    if (data.status == "ok") {

      if (data.category) {
        $("#category_selection_" + category_id).html('');

        get_unlinked_avito_categorys(price_export_id);
      }

    } else {
      console.error('Произошла ошибка: ' + data.error);
    }
  });
}

function get_price_from_url_file_data(price_id){
  $.blockUI({
    css: { 
      border: 'none', 
      padding: '15px', 
      backgroundColor: '#000', 
      '-webkit-border-radius': '10px', 
      '-moz-border-radius': '10px', 
      opacity: .5, 
      color: '#fff'
    },
    message: 'Идет загрузка, пожалуйста подождите... <a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var send=[];
  send['base_id']=price_id;
  send['base_type']=2;
  send['selected_page']=0;
  api_query_array("/api/index.php",send,"get_uploaded_file_page").then(function(data){
    $.unblockUI();
    var sheets=build_excel_sheets(data,data.base_type);
    create_window('select_price_cols_div_'+data.base_id,'Сопоставление колонок','select_price_cols_'+data.base_id,sheets);
    if(!data.hasOwnProperty('selected_page')){
        $('#a_sheet_0').click();
    }
    else {
      $('#a_sheet_'+data.selected_page).click();
    }
  });
}
