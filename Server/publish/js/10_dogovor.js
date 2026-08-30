function get_dogovors(){
 api_query("/api/index.php","some_form","get_dogovors").then(function(data){
    var datalen=data.dogovors.length;
    var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Компания</th><th>Наценка</th><th>Тип договора</th><th>Дата создания</th><th>Действие договора</th><th></th></tr></thead></tbody>";
    var znak="-";
    for (var i=0; i<datalen; i++){
	table += "<tr><td>"+data.dogovors[i].num+"</td><td>" + data.dogovors[i].company_name + "</td><td>"+data.dogovors[i].proc+"</td><td>"+data.dogovors[i].dogovor_type_name+"</td>";
	table += "<td>"+data.dogovors[i].create_date+"</td><td>"+data.dogovors[i].stop_date+"</td>";
	table += "<td><form id='delete_dogovor_"+data.dogovors[i].id+"'><input type=\"hidden\" name=\"dogovor_id\" value=\""+data.dogovors[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
	table += "<a onclick=\"edit_dogovor('delete_dogovor_"+data.dogovors[i].id+"');\" title='Редактировать документ'><img src='/new_images/edit.svg' style='width:20px;'></a>";
	table += " <a title='Удалить document' ";
	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить этот договор?\',function(result){ if(result) api_query('/api/index.php','delete_dogovor_"+data.dogovors[i].id+"','delete_dogovor').then(function(data){if(data.status=='ok') location.reload()});});\"><img src='/new_images/garbage.svg' style='width:20px;'></a>"
	table += "</div></td>";
	table += "</tr>";
    }
    table+="</tbody></table>";
    $("#dogovor_list").html(table);
 });
}

function show_company_dogovors(company_id,company_name){
 var send=new Array();
 send['company_id']=company_id;
 api_query_array("/api/index.php",send,"get_company_dogovors").then(function(data){
    var datalen=data.dogovors.length;
    var table='<br><button class="btn btn-primary" onclick="add_new_dogovor('+company_id+',\''+company_name.replace(/"/g,"")+'\');">Добавить</button><div id="new_dogovor"></div>';
    table+="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Компания</th><th>Скидка</th><th>Кред. лимит</th><th>Дата создания</th><th>Действие договора</th><th></th></tr></thead><tbody>";
    var znak="-";
    for (var i=0; i<datalen; i++){
      if(data.dogovors[i].proc===null) data.dogovors[i].proc=0;
    	table += "<tr><td>"+data.dogovors[i].num+"</td><td>" + data.dogovors[i].company_name + "</td><td>"+(data.dogovors[i].price_type_descr!==null?data.dogovors[i].price_type_descr:"")+" "+data.dogovors[i].proc+" %</td><td>"+data.dogovors[i].credit_limit+"</td>";
    	table += "<td>"+data.dogovors[i].create_date+"</td><td>"+data.dogovors[i].start_date+'-'+data.dogovors[i].stop_date+"</td>";
    	table += "<td><form id='delete_dogovor_"+data.dogovors[i].id+"'><input type=\"hidden\" name=\"dogovor_id\" value=\""+data.dogovors[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
    	table += "<a onclick=\"edit_dogovor('delete_dogovor_"+data.dogovors[i].id+"');\" title='Редактировать документ'><img src='/new_images/edit.svg' style='width:20px;'></a>";
    	table += " <a title='Удалить document' ";
    	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить этот договор?\',function(result){ if(result) api_query('/api/index.php','delete_dogovor_"+data.dogovors[i].id+"','delete_dogovor').then(function(data){if(data.status=='ok') show_company_dogovors("+company_id+",'"+company_name.replaceAll("'","").replaceAll('"','')+"')});});\"><img src='/new_images/garbage.svg' style='width:20px;'></a>"
    	table += "</div></td>";
    	table += "</tr>";
    }
    table+="</tbody></table>";
    $("#company_dogovors").html(table);
 });

}

function select_sklad(){
    api_query("/api/index.php","some_form","get_sklads").then(function(data){
    var datalen=data.sklads.length;
    var table="";
    table+="<table class=\"table table-hover\"><tr><th>№</th><th>Наименование</th><th>Адрес</th><th>Описание</th><th></th></tr>";
    for (var i=0; i<datalen; i++){
	table += "<tr onclick=\"$('#sklad_id').val("+data.sklads[i].id+"); $('#sklad_name').val('"+data.sklads[i].name+"'); $(\'#select_sklad\').hide();\"><td>"+(i+1)+"</td><td>" + data.sklads[i].name + "</td><td>"+data.sklads[i].address+"</td><td>"+data.sklads[i].descr+"</td>";
	table += "</tr>";
    }
    create_window("select_sklad","Выберите склад","sklad_list_new",table)
 });
}

function select_tax_type(){
    api_query("/api/index.php","some_form","get_tax_types").then(function(data){
    var datalen=data.tax_types.length;
    var table="";
    table+="<table class=\"table table-hover\"><tr><th>№</th><th>Наименование</th><th>Адрес</th><th>ИНН/КПП</th><th></th></tr>";
    for (var i=0; i<datalen; i++){
	table += "<tr onclick=\"$('#company_id').val("+data.dealers[i].id+"); $('#company_name').val('"+data.dealers[i].name.replace(/\"/g,"")+"'); $(\'#select_dealer\').hide();\"><td>"+(i+1)+"</td>\
		<td>" + data.dealers[i].name + "</td><td>"+data.dealers[i].address+"</td><td>"+data.dealers[i].inn+"/"+data.dealers[i].kpp+"</td>";
	table += "</tr>";
    }
    create_window("select_dealer","Выберите поставщика","dealer_list_new",table);
 });
}

function select_dealer(){
    api_query("/api/index.php","some_form","get_dealers").then(function(data){
    var datalen=data.dealers.length;
    var table="";
    table+="<table class=\"table table-hover\"><tr><th>№</th><th>Наименование</th><th>Адрес</th><th>ИНН/КПП</th><th></th></tr>";
    for (var i=0; i<datalen; i++){
	table += "<tr onclick=\"$('#company_id').val("+data.dealers[i].id+"); $('#company_name').val('"+data.dealers[i].name.replace(/\"/g,"")+"'); $(\'#select_dealer\').hide();\"><td>"+(i+1)+"</td>\
		<td>" + data.dealers[i].name + "</td><td>"+data.dealers[i].address+"</td><td>"+data.dealers[i].inn+"/"+data.dealers[i].kpp+"</td>";
	table += "</tr>";
    }
    create_window("select_dealer","Выберите поставщика","dealer_list_new",table);
 });
}

function select_client(){
    api_query("/api/index.php","some_form","get_clients").then(function(data){
    var datalen=data.clients.length;
    var table="";
    table+="<table class=\"table table-hover\"><tr><th>№</th><th>Наименование</th><th>Адрес</th><th>ИНН/КПП</th><th>Тип</th><th></th></tr>";
    for (var i=0; i<datalen; i++){
	table += "<tr onclick=\"$('#price_type_type').val("+data.clients[i].btype+");$('#dogovor_type').val("+data.clients[i].btype+");$('#company_id').val("+data.clients[i].id+"); $('#company_name').val('"+data.clients[i].name.replace(/\"/g,"")+"'); $(\'#select_client\').hide();\"><td>"+(i+1)+"</td>\
		<td>" + data.clients[i].name + "</td><td>"+data.clients[i].address+"</td><td>"+data.clients[i].inn+"/"+data.clients[i].kpp+"</td>";
	if (data.clients[i].btype==1) {table+="<td>Покупатель</td>";}
	if (data.clients[i].btype==2) {table+="<td>Поставщик</td>";}
	table += "</tr>";
    }
    create_window("select_client","Выберите организацию","dealer_list_new",table);
 });
}

function select_price_type(type){
    if (type=="edit" && $('#price_type_type').val()=="") {
	bootbox.alert('Сначала выберите организацию');
	return 0;
    }
    api_query("/api/index.php","price_type_form","get_price_types").then(function(data){
    var datalen=data.price_types.length;
    var table="";
    table+="<table class=\"table table-hover\"><tr><th>№</th><th>Наименование</th></tr>";
    for (var i=0; i<datalen; i++){
	    table += "<tr onclick=\"$('#price_type_type').val("+data.price_types[i].type+");$('#price_type_id').val("+data.price_types[i].id+"); $('#price_type_name').val('"+data.price_types[i].descr.replace(/\"/g,"")+"'); $(\'#price_types_new\').html(\'\');\">\
		<td>" + data.price_types[i].id + "</td><td>"+data.price_types[i].descr+"</td>";
	    table += "</tr>";
    }
    create_window("select_price_type","Выберите скидку","price_types_new",table);
 });
}

function save_dogovor(){
    api_query("/api/index.php","new_dogovor_form","save_dogovor").done(function(data){
      	if (data.status=="ok"){
      	    //get_dogovors();
            var company_id=$("#new_dogovor_form [name=company_id]").val();
            var company_name=$("#new_dogovor_form [name=company_name]").val();
            $('#add_new_dogovor').html('');
            show_company_dogovors(company_id,company_name);
      	}
    });
}
function clear_search_order_text(input_id){
  $('#'+input_id).val('');
  //runTextFilterOrd();
        }

function edit_dogovor(dogovor_form){
  api_query("/api/index.php",dogovor_form,"get_dogovor").done(function(data){
    var data_html='<form id="price_type_form"></form>\
    <form id="new_dogovor_form">\
    <div class="form-group row col-sm-12">\
    <label for="num" class="col-sm-5 col-form-label text-nowrap">Номер договора</label>\
    <div class="col-sm-7">\
     <input type="hidden" name="dogovor_id" value="'+data.dogovor.id+'">\
     <input type="text" class="form-control search_str" id="num" onclick="this.select();" name="num" placeholder="Введите номер договора" value="'+data.dogovor.num+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="num" id="num_label" onclick="clear_search_order_text(\'num\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="company_name" class="col-sm-5 col-form-label text-nowrap">Выберите организацию</label>\
    <div class="col-sm-7">\
     <input type="hidden" name="company_id" id="company_id" value="'+data.dogovor.company_id+'">\
     <input type="text" class="form-control" name="company_name" id="company_name" onclick="select_client();" readonly placeholder="Нажмите чтобы выбрать" value="'+data.company_name+'">\
     <input type="hidden" name="dogovor_type" id="dogovor_type" value="'+data.dogovor.dogovor_type+'">\
    <div id="dealer_list_new"></div>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="start_date" class="col-sm-5 col-form-label text-nowrap">Дата с</label>\
    <div class="col-sm-7">\
     <input type="date" class="form-control" name="start_date" value="'+data.dogovor.start_date+'" placeholder="Введите дату начала действия договора">\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="stop_date" class="col-sm-5 col-form-label text-nowrap">Дата по</label>\
    <div class="col-sm-7">\
     <input type="date" class=" form-control" name="stop_date" value="'+data.dogovor.stop_date+'" placeholder="Введите дату окончания действия договора">\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Выберите скидку</label>\
    <div class="col-xs-7">\
     <input type="hidden" name="price_type_id" id="price_type_id" value="';
     data_html+=data.dogovor.price_type;
     data_html+='">\
     <input type="text" class="form-control" name="price_type_name" id="price_type_name" value="';
     if(data.dogovor.price_type_name!==false) data_html+=data.dogovor.price_type_name;
     else data_html+='без скидки';
     data_html+='" onclick="select_price_type();" readonly placeholder="Нажмите чтобы выбрать скидку">\
    <div id="price_types_new"></div>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
      <label for="is_cashback_by_default" class="col-sm-5 col-form-label text-nowrap">Накопительная система скидок по умолчанию</label>\
    <div class="col-sm-7">\
     <input type="checkbox" name="is_cashback" id="is_cashback_by_default" title="Когда выбрана, по умолчанию используется накопительная система" '+(data.dogovor.is_cashback_by_default==1?"checked":"")+'>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="payment_type" class="col-sm-5 col-form-label text-nowrap">Тип платежей</label>\
    <div class="col-sm-7">\
      <select class="form-control" id="payment_type" name="payment_type">\
        <option value="1"';
          if(data.dogovor.payment_type==1) data_html+=' selected="selected"';
          data_html+='>Безналичная оплата</option>\
        <option value="2"';
          if(data.dogovor.payment_type==2) data_html+=' selected="selected"';
        data_html+='>Наличная оплата</option>\
      </select>\
      <div id="doc_types_list_new"></div>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="dogovor_descr" class="col-sm-5 col-form-label text-nowrap">Комментарий к договору</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();" id="descr" placeholder="" name="descr" value="'+data.dogovor.descr+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="descr" id="descr_label" onclick="clear_search_order_text(\'descr\');"></label>\
    </div>\
    </div><div id="dogovor_details"></div>';
    data_html+='\
    <div class="form-group row col-sm-12">\
    <label for="credit_limit" class="col-sm-5 col-form-label text-nowrap">Кредитный лимит</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();" id="credit_limit" placeholder="" name="credit_limit" value="'+data.dogovor.credit_limit+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="credit_limit" id="credit_limit_label" onclick="clear_search_order_text(\'credit_limit\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="credit_limit_time" class="col-sm-5 col-form-label text-nowrap">Время действия кредитных счетов</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();" id="credit_limit_time" placeholder="" name="credit_limit_time" value="'+data.dogovor.credit_limit_time+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="credit_limit_time" id="credit_limit_time_label" onclick="clear_search_order_text(\'credit_limit_time\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="card_number" class="col-sm-5 col-form-label text-nowrap">№ скидочной карты</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();" id="card_number" placeholder="" name="card_number" value="'+data.dogovor.card_number+'">\
    </div>\
    </div>\
    ';
    data_html+='</form>\
    <button class="btn btn-primary" onclick="save_dogovor();">Сохранить</button>\
    <button class="btn btn-secondary pull-right" onclick="$(\'#new_dogovor\').html(\'\');">Закрыть</button>\
    ';
    create_window("add_new_dogovor","Изменение договора","new_dogovor",data_html)
    });
}

function add_new_dogovor(company_id=0,company_name=''){
  var send=[];
  send['company_id']=company_id
  api_query_array("/api/index.php",send,"get_company").done(function(data){
    var data_html='<form id="price_type_form">\
    </form>\
    <form id="new_dogovor_form">\
    <div class="form-group row">\
    <label for="num" class="col-sm-5 col-form-label text-nowrap">Выберите тип договора</label>\
    <div class="col-sm-7">\
	   <select name="dogovor_type" id="dogovor_type" class="form-control">\
      <option value="1">Покупатель</option>\
      <option value="2" '+((data.btype==2 || data.btype==4)?"selected":"")+'>Поставщик</option>\
     </select>\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="num" class="col-sm-5 col-form-label text-nowrap">Номер договора</label>\
    <div class="col-sm-7">\
     <input type="text" class="form-control search_str" id="num" name="num" value="" onclick="this.select();"  placeholder="Введите номер договора"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="num" id="num_label" onclick="clear_search_order_text(\'num\');"></label>\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="company_name" class="col-sm-5 col-form-label text-nowrap">Организация</label>\
    <div class="col-sm-7">\
     <input type="hidden" name="company_id" id="company_id" value="'+company_id+'">';
    if(company_id==0)
	   data_html+='<input type="text" class="form-control" name="company_name" id="company_name" onclick="select_client();" value="" readonly placeholder="Нажмите чтобы выбрать">';
    else
	   data_html+='<input type="text" class="form-control" name="company_name" id="company_name" value="'+company_name+'" readonly>';
    data_html+=' \
    <div id="dealer_list_new"></div>\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="start_date" class="col-sm-5 col-form-label text-nowrap">Дата с</label>\
    <div class="col-sm-7">\
     <input type="date" class="form-control" id="" name="start_date" value="'+getDateToString()+'" placeholder="Введите дату начала действия договора">\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="stop_date" class="col-sm-5 col-form-label text-nowrap">Дата по</label>\
    <div class="col-sm-7">\
     <input type="date" class="form-control" id="" name="stop_date" value="'+getDateToString([10])+'" placeholder="Введите дату окончания действия договора">\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Выберите скидку</label>\
    <div class="col-sm-7">\
     <input type="hidden" name="price_type_id" id="price_type_id" value="">\
     <input type="text" class="form-control" name="price_type_name" id="price_type_name" value="" onclick="select_price_type();" readonly placeholder="Нажмите чтобы выбрать скидку">\
    <div id="price_types_new"></div>\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="is_bonus" class="col-sm-5 col-form-label text-nowrap">Накопительная система <br>скидок по умолчанию</label>\
    <div class="col-sm-7">\
     <input type="checkbox" name="is_bonus" id="is_bonus_by_default" title="Когда выбрана, по умолчанию используется накопительная система скидок (бонусная система)">\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="payment_type" class="col-sm-5 col-form-label text-nowrap">Тип платежей</label>\
    <div class="col-sm-7">\
      <select class="form-control" id="payment_type" name="payment_type">\
        <option value="1">Безналичная оплата</option>\
        <option value="2">Наличная оплата</option>\
      </select>\
      <div id="doc_types_list_new"></div>\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="dogovor_descr" class="col-sm-5 col-form-label text-nowrap">Комментарий к договору</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();"  id="descr" placeholder="" name="descr" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="descr" id="descr_label" onclick="clear_search_order_text(\'descr\');"></label>\
    </div>\
    </div><div id="dogovor_details">\
    <div class="form-group row">\
    <label for="credit_limit" class="col-sm-5 col-form-label text-nowrap">Кредитный лимит</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();"  id="credit_limit" placeholder="" name="credit_limit" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="credit_limit" id="credit_limit_label" onclick="clear_search_order_text(\'credit_limit\');"></label>\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="credit_limit_time" class="col-sm-5 col-form-label text-nowrap">Время действия кредитных счетов</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();"  id="credit_limit_time" placeholder="" name="credit_limit_time" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="credit_limit_time" id="credit_limit_time_label" onclick="clear_search_order_text(\'credit_limit_time\');"></label>\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="card_number" class="col-sm-5 col-form-label text-nowrap">№ скидочной карты</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();" id="card_number" placeholder="" name="card_number" value="">\
    </div>\
    </div>\
    ';

    data_html+='</div></form>\
    <button class="btn btn-primary" onclick="save_dogovor();">Сохранить</button>\
    <button class="btn btn-secondary pull-right" onclick="$(\'#new_dogovor\').html(\'\');">Закрыть</button>\
    ';
    create_window("add_new_dogovor","Добавление нового договора","new_dogovor",data_html);
  })
}

function add_new_dogovor_in_zakaz(company_id=0,company_name='',zakaz_id=0){
  var send=[];
  send['company_id']=company_id
  api_query_array("/api/index.php",send,"get_company").done(function(data){
    var data_html='<form id="price_type_form">\
    </form>\
    <form id="new_dogovor_form_in_zakaz_'+zakaz_id+'">\
    <div class="form-group row">\
      <label for="num" class="col-sm-5 col-form-label text-nowrap">Выберите тип договора</label>\
      <div class="col-sm-7">\
      <select name="dogovor_type" id="dogovor_type" class="form-control">\
        <option value="1">Покупатель</option>\
        <option value="2" '+((data.btype==2 || data.btype==4)?"selected":"")+'>Поставщик</option>\
      </select>\
      </div>\
      </div>\
    <div style="max-width: 550px;">\
    <div class="form-group row col-sm-12">\
    <label for="num" class="col-sm-5 col-form-label text-nowrap">Номер договора</label>\
    <div class="col-sm-7">\
    <input type="text" class="form-control search_str" id="num" name="num" value="" onclick="this.select();"  placeholder="Введите номер договора"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="num" id="num_label" onclick="clear_search_order_text(\'num\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="company_name" class="col-sm-5 col-form-label text-nowrap">Выберите организацию</label>\
    <div class="col-sm-7">\
    <input type="hidden" name="zakaz_id" value="'+zakaz_id+'">\
    <input type="hidden" name="company_id" id="company_id" value="'+company_id+'">';
    if(company_id==0)
    data_html+='<input type="text" class="form-control" name="company_name" id="company_name" onclick="select_client();" value="" readonly placeholder="Нажмите чтобы выбрать">';
    else
    data_html+='<input type="text" class="form-control" name="company_name" id="company_name" value="'+company_name+'" readonly>';
    data_html+=' \
    <div id="dealer_list_new"></div>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="start_date" class="col-sm-5 col-form-label text-nowrap">Дата с</label>\
    <div class="col-sm-7">\
    <input type="date" class="form-control" id="" name="start_date" value="'+getDateToString()+'" placeholder="Введите дату начала действия договора">\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="stop_date" class="col-sm-5 col-form-label text-nowrap">Дата по</label>\
    <div class="col-sm-7">\
    <input type="date" class="form-control" id="" name="stop_date" value="'+getDateToString([10])+'" placeholder="Введите дату окончания действия договора">\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Выберите скидку</label>\
    <div class="col-sm-7">\
    <input type="hidden" name="price_type_id" id="price_type_id" value="">\
    <input type="text" class="form-control" name="price_type_name" id="price_type_name" value="" onclick="select_price_type();" readonly placeholder="Нажмите чтобы выбрать скидку">\
    <div id="price_types_new"></div>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="payment_type" class="col-sm-5 col-form-label text-nowrap">Тип платежей</label>\
    <div class="col-sm-7">\
  <input type="hidden" id="payment_type" name="payment_type" value="">\
      <select class="form-control" id="payment_type_name" name="payment_type_name">\
  <option value="1">Безналичная оплата</option>\
  <option value="2">Наличная оплата</option>\
      </select>\
      <div id="doc_types_list_new"></div>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="dogovor_descr" class="col-sm-5 col-form-label text-nowrap">Комментарий к договору</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();"  id="descr" placeholder="" name="descr" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="descr" id="descr_label" onclick="clear_search_order_text(\'descr\');"></label>\
    </div>\
    </div><div id="dogovor_details">\
    <div class="form-group row col-sm-12">\
    <label for="credit_limit" class="col-sm-5 col-form-label text-nowrap">Кредитный лимит</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();"  id="credit_limit" placeholder="" name="credit_limit" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="credit_limit" id="credit_limit_label" onclick="clear_search_order_text(\'credit_limit\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="credit_limit_time" class="col-sm-5 col-form-label text-nowrap">Время действия кредитных счетов</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" onclick="this.select();"  id="credit_limit_time" placeholder="" name="credit_limit_time" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="credit_limit_time" id="credit_limit_time_label" onclick="clear_search_order_text(\'credit_limit_time\');"></label>\
    </div>\
    </div>\
    ';

    data_html+='</div>\
    <div class="form-group row col-sm-12">\
    <button type="button" class="btn btn-primary" onclick="save_dogovor_in_zakaz('+zakaz_id+');">Сохранить</button>\
    <button type="button" class="btn btn-secondary pull-right" onclick="$(\'#new_dogovor_in_zakaz_'+zakaz_id+'\').html(\'\');">Закрыть</button>\
    </div>\
    </div></form>\
    ';
    create_window("add_new_dogovor_in_zakaz_"+zakaz_id,"Добавление нового договора","new_dogovor_in_zakaz_"+zakaz_id,data_html);
  })
}

function save_dogovor_in_zakaz(zakaz_id){
  api_query("/api/index.php","new_dogovor_form_in_zakaz_"+zakaz_id,"save_dogovor").done(function(data){
      if (data.status=="ok"){
          //get_dogovors();
          var company_id=$("#new_dogovor_form_in_zakaz_"+zakaz_id+" [name=company_id]").val();
          var company_name=$("#new_dogovor_form_in_zakaz_"+zakaz_id+" [name=company_name]").val();
          $('#add_new_dogovor_in_zakaz_'+zakaz_id).html('');
          bootbox.alert("Новый договор с кредитным лимитом добавлен");
          //show_company_dogovors(company_id,company_name);
      }
  });
}