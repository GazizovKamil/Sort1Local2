function save_client(type){
    var postdata = $("#new_client_form").serialize();
    if (type=="update") var url="/modules/update_company.php?";
    else var url="/modules/save_company.php?";
    $.get(url + postdata,
	{
	}
    )
    .done(function(data){ 
	if (data.status!="error") {
	    $("#exampleModalCenter").modal("toggle");
	    $("#new_client_form").each(function(){
		this.reset();
	    });
	}
	else
	    alert(data.msg);
	location.reload();
	//$(\'#exampleModalCenter\').modal(\'hide\');
	//document.getElementById(\'mod_link_2\').click();
    });
}

var companys_for_select={};

function fill_form(type){
    var send=new Array();
    if (type=="inn") send['inn']=$("#inn").val();
    if (type=="name") send['org_name']=$("#company_name").val();
    /* $.get("/modules/get_org_info.php",
    	{
    	    inn: inn,
    	    org_name: org_name
    	} */
    api_query_array("/api/index.php",send,"get_company_data_from_api").done(function(data){
      	var obj_len=Object.keys(data).length;
      	var dirname="";
      	if (obj_len > 0){
      	    var count=data.suggestions.length;
            companys_for_select=data;
      	}
      	else {
      	    var count=0;
      	}
      	if (count==0){
      	    alert("К сожалению пользователь не найден, заполните пожалуйста поля вручную");
      	}
      	if (count==1){
          $("#kpp").val(data.suggestions[0].data.kpp);
          $("#inn").val(data.suggestions[0].data.inn);
          $("#ogrn").val(data.suggestions[0].data.ogrn);
		  $("#okpo").val(data.suggestions[0].data.okpo);
		  $("#okved").val(data.suggestions[0].data.okved);
          if (!jQuery.isEmptyObject(data.suggestions[0].data.address.value))
            $("#address").val(data.suggestions[0].data.address.value);
          else
            $("#address").val(data.suggestions[0].data.address.data.source);
          $("#company_name").val(data.suggestions[0].value);
		  if(typeof(data.suggestions[0].data.management)!="undefined" && data.suggestions[0].data.management!==null){
			$("#ruk").val(data.suggestions[0].data.management.name);
			$("#rukdol").val(data.suggestions[0].data.management.post);
		  }
      	}
      	if (count>1){
            select_company_from_list(data);
      	}
    });
}

function select_company_from_list(data){
  var table='<table class="table table-hover"><thead><tr><th>Наименование организации</th><th>ИНН/КПП</th><th>Руководитель</th></tr></thead><tbody>'
  $.each(data.suggestions,function(i, item){
    if(typeof(item.data.management) == 'undefined' || item.data.management === null) dirname="";
    else dirname=item.data.management.name;
    if (item.data.state.status=="ACTIVE") table+='<tr onclick="set_company_data('+i+');"><td>'+item.value+'</td><td>'+item.data.inn+'/'+item.data.kpp+'</td><td>'+dirname+'</td></tr>';
  });
  table+='</tbody></table>';
  create_window_centered_blue("company_list_for_select_div","Выберите компанию для автоматического заполнения данных","company_list_for_select",table);
}

function set_company_data(id){
  if (id>=0){
    if(typeof(companys_for_select.suggestions[id].data.kpp)!="undefined") $("#kpp").val(companys_for_select.suggestions[id].data.kpp);
	$("#inn").val(companys_for_select.suggestions[id].data.inn);
	if(typeof(companys_for_select.suggestions[id].data.ogrn)!="undefined") $("#ogrn").val(companys_for_select.suggestions[id].data.ogrn);
	if(typeof(companys_for_select.suggestions[id].data.okpo)!="undefined") $("#okpo").val(companys_for_select.suggestions[id].data.okpo);
	if(typeof(companys_for_select.suggestions[id].data.okved)!="undefined") $("#okved").val(companys_for_select.suggestions[id].data.okved);
	if (!jQuery.isEmptyObject(companys_for_select.suggestions[id].data.address.value))
		$("#address").val(companys_for_select.suggestions[id].data.address.value);
	else
		$("#address").val(companys_for_select.suggestions[id].data.address.data.source);
	$("#company_name").val(companys_for_select.suggestions[id].value);
	if(typeof(companys_for_select.suggestions[id].data.management)!="undefined" && companys_for_select.suggestions[id].data.management!==null){
		$("#ruk").val(companys_for_select.suggestions[id].data.management.name);
		$("#rukdol").val(companys_for_select.suggestions[id].data.management.post);
	}
	if(companys_for_select.suggestions[id].data.branch_type=="BRANCH"){
		$("#company_type").val(4);
		$("#main_orgs_list").parent().css("display","block");
	}
    $("#company_list_for_select").html('');
  }
}


function show_company_data(company_id){
    api_query("/api/index.php","delete_company_"+company_id,"get_company").done( function(data){
	var data_html='<form id="new_client_form">\
	<div class="form-group row">\
	        <label for="company_type" class="col-sm-3 col-form-label">ОКОПФ</label>\
	        <div class="col-sm-9">\
    		<select class="form-control" id="company_type" placeholder="" name="okopf">';
	comp_types_len=data.company_types.length;
	for(var i=0; i<comp_types_len; i++){
	    if (data.company_types[i].id == data.type || ( i==0 && data.type==0 )) data_html+="<option value=\""+data.company_types[i].id+"\" selected=\"selected\">"+data.company_types[i].type+"</option>";
	    else data_html+="<option value=\""+data.company_types[i].id+"\">"+data.company_types[i].type+"</option>";
	}
	data_html+='      </select>\
    <input type="hidden" name="company_id" value="'+data.id+'">\
    </div>\
    </div>';
    data_html+='<div class="form-group row">\
	        <label for="company_btype" class="col-sm-3 col-form-label">Тип компании</label>\
	        <div class="col-sm-9">\
    		<select class="form-control" id="company_btype" placeholder="" name="btype">';
	comp_btypes_len=data.company_btypes.length;
	for(var i=0; i<comp_btypes_len; i++){
	    if (data.company_btypes[i].id == data.btype || (data.btype==0 && i==0)) data_html+="<option value=\""+data.company_btypes[i].id+"\" selected=\"selected\">"+data.company_btypes[i].descr+"</option>";
	    else data_html+="<option value=\""+data.company_btypes[i].id+"\">"+data.company_btypes[i].descr+"</option>";
	}
    data_html+='      </select>\
    </div></div>';
    data_html+='<div class="form-group row">\
	        <label for="company_tax_type" class="col-sm-3 col-form-label">Система налогообложения</label>\
	        <div class="col-sm-9">\
    		<select class="form-control" id="company_tax_type" placeholder="" name="tax_type">';
	comp_tax_types_len=data.tax_types.length;
	for(var i=0; i<comp_tax_types_len; i++){
	    if (data.tax_types[i].id == data.tax_type || (data.tax_type==0 && i==0)) data_html+="<option value=\""+data.tax_types[i].id+"\" selected=\"selected\">"+data.tax_types[i].name+" "+data.tax_types[i].tax_rate+" %</option>";
	    else data_html+="<option value=\""+data.tax_types[i].id+"\">"+data.tax_types[i].name+" "+data.tax_types[i].tax_rate+" %</option>";
	}
    data_html+='      </select>\
    </div></div>';
	data_html+='<div class="form-group row">\
    <label for="main_org_name" class="col-sm-3 col-form-label">Головная организация</label>\
    <div class="col-sm-9">\
		<input type="hidden" name="main_org_id" id="main_org_id" value="'+data.main_org_id+'">\
      <input type="text" class="form-control search_str" id="main_org_name" placeholder="Наименование головной организации" name="main_org_name" value="'+str_to_val(data.main_org_name)+'" onchange="change_main_org();">\
    </div>\
    <div id="main_orgs_list">\
    </div>\
    </div>';
    data_html+='<div class="form-group row">\
    <label for="company_name" class="col-sm-3 col-form-label">Наименование организации</label>\
    <div class="col-sm-9">\
      <input type="text" class="form-control search_str" id="company_name" placeholder="Наименование организации" name="company_name" value=\''+str_to_val(data.name)+'\'><label style="position: absolute; top: 0em; right: 1.2em;" for="company_name" id="company_name_label" onclick="clear_search_order_text(\'company_name\');"></label>\
    </div>\
    <div id="companys_list">\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="company_inn" class="col-sm-3 col-form-label">ИНН</label>\
    <div class="col-sm-9 pull-right">\
      <input type="text" class="form-control search_str" id="inn" placeholder="ИНН" name="inn" value="'+data.inn+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="inn" id="inn_label" onclick="clear_search_order_text(\'inn\');"></label>\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="inputPassword3" class="col-sm-3 col-form-label">КПП</label>\
    <div class="col-sm-9 pull-right">\
      <input type="text" class="form-control search_str" id="kpp" placeholder="КПП" name="kpp" value="'+data.kpp+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="kpp" id="kpp_label" onclick="clear_search_order_text(\'kpp\');"></label>\
    </div>\
    </div>\
    <a href="#" onclick="$(\'#advanced_company\').toggle()">Дополнительно</a>\
    <div id="advanced_company" style="display:none">\
	<div class="form-group row">\
	    <label for="inputAddress" class="col-sm-3 col-form-label">Юридический адрес</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control search_str" id="address" placeholder="Юридический адрес" name="address" value="'+str_to_val(data.address)+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="address" id="address_label" onclick="clear_search_order_text(\'address\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputogrn" class="col-sm-3 col-form-label">ОГРН</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control search_str" id="ogrn" placeholder="ОГРН" name="ogrn" value="'+data.ogrn+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="ogrn" id="ogrn_label" onclick="clear_search_order_text(\'ogrn\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputrs" class="col-sm-3 col-form-label">Расчетный счет</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control search_str" id="rs" placeholder="Расчетный счет" name="rs" value="'+data.rs+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="rs" id="rs_label" onclick="clear_search_order_text(\'rs\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputbank" class="col-sm-3 col-form-label">Банк</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control search_str" id="bank" placeholder="Банк" name="bank" value="'+str_to_val(data.bank)+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="bank" id="bank_label" onclick="clear_search_order_text(\'bank\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputks" class="col-sm-3 col-form-label">Кор. счет</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control search_str" id="ks" placeholder="Кор. счет" name="ks" value="'+data.ks+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="ks" id="ks_label" onclick="clear_search_order_text(\'ks\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputbik" class="col-sm-3 col-form-label">БИК</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control search_str" id="bik" placeholder="БИК" name="bik" value="'+data.bik+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="bik" id="bik_label" onclick="clear_search_order_text(\'bik\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputruk" class="col-sm-3 col-form-label">Руководитель</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control search_str" id="ruk" placeholder="Руководитель" name="ruk" value="'+data.ruk+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="ruk" id="ruk_label" onclick="clear_search_order_text(\'ruk\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputrukdol" class="col-sm-3 col-form-label">Должность руководителя</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control search_str" id="rukdol" placeholder="Должность руководителя" name="rukdol" value="'+data.rukdol+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="rukdol" id="rukdol_label" onclick="clear_search_order_text(\'rukdol\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="tel" class="col-sm-3 col-form-label">Телефон</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control search_str" id="mphone" placeholder="Телефон" name="mphone" value="'+data.mphone+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="mphone" id="mphone_label" onclick="clear_search_order_text(\'mphone\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="email" class="col-sm-3 col-form-label">E-mail</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control search_str" id="email" placeholder="E-mail" name="email" value="'+data.email+'"><label style="position: absolute; top: 0em; right: 1.2em;" for="email" id="email_label" onclick="clear_search_order_text(\'email\');"></label>\
	    </div>\
	</div>\
    </div>\
    </form>';
    $('#client_content').html(data_html);
    });
}

var companys=new Array();

function show_company_data1(company_id,type){
    $('div [id^=client_data_]').html('');
    var send=new Array();
    send['company_id']=company_id;
    api_query_array("/api/index.php",send,"get_company").done( function(data){
    	companys[company_id]=data;
      if(company_id>0) {
    	var data_html='<div id="company_list_for_select"></div>\
        <div class="form-group row">\
    	   <label for="company_name" class="col-sm-4 col-form-label">Наименование организации:</label>\
    	   <div class="col-sm-8">\
        	    '+data.name+'\
    	   </div>\
        </div>\
        <div class="form-group row">\
    	   <label for="company_inn" class="col-sm-4 col-form-label">ИНН:</label>\
    	    <div class="col-sm-8">\
        	    '+data.inn+'\
    	    </div>\
        </div>\
        <div class="form-group row">\
    	<label for="inputPassword3" class="col-sm-4 col-form-label">КПП:</label>\
    	<div class="col-sm-8">\
    	    '+data.kpp+'\
    	</div>\
        </div>\
        <div class="form-group row">\
    	<label for="company_balance" class="col-sm-4 col-form-label">Баланс:</label>\
    	<div class="col-sm-8">\
        	    '+parseFloat(data.company_balance).toFixed(2)+' руб.\
    	</div>\
        </div>\
      <div class="form-group row">\
    	<label for="company_rezerv" class="col-sm-4 col-form-label">Зарезервировано под заказы:</label>\
    	<div class="col-sm-8">\
        	    '+parseFloat(data.company_rezerv).toFixed(2)+' руб.\
    	</div>\
        </div>\
		<div class="form-group row">\
    	<label for="sum_trade" class="col-sm-4 col-form-label">Суммарный оборот:</label>\
    	<div class="col-sm-8">\
        	    '+parseFloat(data.sum_trade).toFixed(2)+' руб.\
    	</div>\
        </div>\
		<div class="form-group row">\
    	<label for="company_cashback" class="col-sm-4 col-form-label">Бонусы:</label>\
    	<div class="col-sm-8">';
    	if(parseInt(my_roles.id)<3){
			data_html+='<input style="width:80px;" value="'+parseFloat(data.company_cashback).toFixed(2)+'" type="text" onchange="change_company_bosus('+company_id+',this.value,'+type+')"> руб.';
		}
		else {
			data_html+=parseFloat(data.company_cashback).toFixed(2)+'руб.';
		}
		data_html+=' \
    	</div>\
        </div>';
      }
      else var data_html='';
        data_html+='<div id="company_list_for_select"></div><ul class="nav nav-tabs">\
        	<li class="active"><a data-toggle="tab" href="#company_main" onclick="show_company_main_data('+company_id+','+type+');">Основная информация</a></li>\
        	<li><a data-toggle="tab" href="#company_delivery_addresses" onclick="show_company_delivery_addresses('+company_id+');">Адреса доставки</a></li>\
        	<li><a data-toggle="tab" href="#company_rekvizits"  onclick="show_company_rekvizits('+company_id+');">Счета организации</a></li>\
        	<li><a data-toggle="tab" href="#company_dogovors"  onclick="show_company_dogovors('+company_id+',\''+str_to_val(data.name)+'\');">Договоры</a></li>\
          <li><a data-toggle="tab" href="#company_cars"  onclick="show_company_cars('+company_id+');">Автомобили</a></li>\
		  <li><a data-toggle="tab" href="#company_zakaz_details"  onclick="show_company_zakaz_details('+company_id+');">Заказы</a></li>\
		  <li><a data-toggle="tab" href="#company_akt_sverki"  onclick="get_akt_sverki('+company_id+');">Акт сверки</a></li>\
        </ul>\
        <div class="tab-content">\
        	<div id="company_main" class="tab-pane fade in active" style="padding: 7px; background-color: #fff; width:960px;">\
        	</div>\
        	<div id="company_delivery_addresses" class="tab-pane fade in"  style="padding: 7px; background-color: #fff; width:960px;">\
        	</div>\
        	<div id="company_rekvizits" class="tab-pane fade in" style="padding: 7px; background-color: #fff; width:960px;">\
        	</div>\
        	<div id="company_dogovors" class="tab-pane fade in" style="padding: 7px; background-color: #fff; width:960px;">\
        	</div>\
          <div id="company_cars" class="tab-pane fade in" style="padding: 7px; background-color: #fff; width:960px;">\
        	</div>\
			<div id="company_zakaz_details" class="tab-pane fade in" style="padding: 7px; background-color: #fff; max-width:1260px; max-height: 500px; overflow-y: auto; overflow-x: auto;">\
        	</div>\
			<div id="company_akt_sverki" class="tab-pane fade in" style="padding: 7px; background-color: #fff; width:960px; max-height: 500px; overflow-y: auto; overflow-x: clip;">\
        	</div>\
        </div>\
        <script>show_company_main_data('+company_id+','+type+')</script>\
    	';
      var divname="client";
      switch(type){
        case 1: divname="client"; break;
        case 2: divname="dealer"; break;
        case 3: divname="my_company"; break;
        case 5: divname="logistic"; break;
		case 6: divname="zakaz"; break;
		case 7: divname="price"; break;
		case 8: divname="site_company"; break;
		case 9: divname="online_profile"; break;
      }
      $("[id^="+divname+"_data_]").html('');
      $("[id^=my_company_data]").html('');
      create_window_centered_blue(divname+"_data_"+company_id+"_div","Данные клиента",divname+"_data_"+company_id,data_html);
	  show_company_main_data(company_id,type);
	  place_to_center(divname+"_data_"+company_id+"_div");
    });
}

function change_company_bosus(company_id,val,type){
	var send=[];
	send['company_id']=company_id;
	send['bonus']=val;
	api_query_array("/api/index.php",send,"change_company_bonus").then(function(data){
		if(data.status=="ok"){
			show_company_data(company_id,type);
		}
	})
}

function new_delivery_address(id){
    var table='';
    table+='<h4>Введите новый адрес доставки:</h4>\
	<div class="form-group row col-sm-12">\
	    <label for="delivery_address" class="col-sm-3 col-form-label">Адрес:</label>\
	    <div class="col-sm-9">\
    	    <input type="text" class="form-control search_str" id="new_delivery_address_address" placeholder="Адрес доставки" name="new_delivery_address_address" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="new_delivery_address_address" id="new_delivery_address_address_label" onclick="clear_search_order_text(\'new_delivery_address_address\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="delivery_address_days" class="col-sm-3 col-form-label">Дни недели:</label>\
	    <div class="col-sm-9">\
    	    <input type="text" class="form-control search_str" id="new_delivery_address_days" placeholder="Дни недели когда можно доставить" name="new_delivery_address_days" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="new_delivery_address_days" id="new_delivery_address_days_label" onclick="clear_search_order_text(\'new_delivery_address_days\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="delivery_address_time_start" class="col-sm-3 col-form-label">Время с:</label>\
	    <div class="col-sm-9">\
    	    <input type="text" class="form-control search_str" id="new_delivery_address_time_start" placeholder="Время с какого можно осуществлять доставку" name="new_delivery_address_time_start" value="" onchange=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="new_delivery_address_time_start" id="new_delivery_address_time_start_label" onclick="clear_search_order_text(\'new_delivery_address_time_start\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="delivery_address_time_stop" class="col-sm-3 col-form-label">Время по:</label>\
	    <div class="col-sm-9">\
    	    <input type="text" class="form-control search_str" id="new_delivery_address_time_stop" placeholder="Время по которое можно осуществлять доставку" name="new_delivery_address_time_stop" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="new_delivery_address_time_stop" id="new_delivery_address_time_stop_label" onclick="clear_search_order_text(\'new_delivery_address_time_stop\');"></label>\
	    </div>\
	</div>\
	<button class="btn btn-primary" onclick="save_delivery_address('+id+',-1);">Сохранить</button>\
    ';
    create_window("new_delivery_address_div","Новый адрес доставки","new_delivery_address",table);
}

function edit_delivery_address(company_id,address_id){
    var table='';
    table+='<h4>Изменените адрес доставки:</h4>\
	<div class="form-group row col-sm-12">\
	    <label for="delivery_address" class="col-sm-3 col-form-label">Адрес:</label>\
	    <div class="col-sm-9">\
    	    <input type="text" class="form-control search_str" id="new_delivery_address_address" placeholder="Адрес доставки" name="new_delivery_address_address" value="'+companys[company_id].delivery_addresses[address_id].delivery_address+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="new_delivery_address_address" id="new_delivery_address_address_label" onclick="clear_search_order_text(\'new_delivery_address_address\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="delivery_address_days" class="col-sm-3 col-form-label">Дни недели:</label>\
	    <div class="col-sm-9">\
    	    <input type="text" class="form-control search_str" id="new_delivery_address_days" placeholder="Дни недели когда можно доставить" name="new_delivery_address_days" value="'+companys[company_id].delivery_addresses[address_id].delivery_days+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="new_delivery_address_days" id="new_delivery_address_days_label" onclick="clear_search_order_text(\'new_delivery_address_days\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="delivery_address_time_start" class="col-sm-3 col-form-label">Время с:</label>\
	    <div class="col-sm-9">\
    	    <input type="text" class="form-control search_str" id="new_delivery_address_time_start" placeholder="Время с какого можно осуществлять доставку" name="new_delivery_address_time_start" value="'+companys[company_id].delivery_addresses[address_id].delivery_time_start+'" onchange=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="new_delivery_address_time_start" id="new_delivery_address_time_start_label" onclick="clear_search_order_text(\'new_delivery_address_time_start\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="delivery_address_time_stop" class="col-sm-3 col-form-label">Время по:</label>\
	    <div class="col-sm-9">\
    	    <input type="text" class="form-control search_str" id="new_delivery_address_time_stop" placeholder="Время по которое можно осуществлять доставку" name="new_delivery_address_time_stop" value="'+companys[company_id].delivery_addresses[address_id].delivery_time_stop+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="new_delivery_address_time_stop" id="new_delivery_address_time_stop_label" onclick="clear_search_order_text(\'new_delivery_address_time_stop\');"></label>\
	    </div>\
	</div>\
	<button class="btn btn-primary" onclick="save_delivery_address('+company_id+','+address_id+');">Сохранить</button>\
    ';
    create_window("delivery_address_"+company_id+"_"+address_id+"_div","Редактирование адреса доставки","delivery_address_"+company_id+"_"+address_id,table);
}

function save_delivery_address(company_id,address_id){
    var send=new Array();
    send['delivery_address']=$('#new_delivery_address_address').val();
    send['delivery_days']=$('#new_delivery_address_days').val();
    send['delivery_time_start']=$('#new_delivery_address_time_start').val();
    send['delivery_time_stop']=$('#new_delivery_address_time_stop').val();
    if(address_id>=0 && parseInt(companys[company_id].delivery_addresses[address_id].id)>0) send['id']=parseInt(companys[company_id].delivery_addresses[address_id].id);
    else send['id']=0;
    send['company_id']=company_id;
    api_query_array("/api/index.php",send,"save_delivery_address").then(function(data){
	if(data.status=="ok") {
	    $("#new_delivery_address").html('');
	    if(parseInt(data.id)>0){
		if(send['id']!=parseInt(data.id)){
		    send['id']=parseInt(data.id);
		    var da_len=companys[company_id].delivery_addresses.length;
		    companys[company_id].delivery_addresses[da_len]=send;
		}
		else {
		    companys[company_id].delivery_addresses[address_id]=send;
		}
	    }
	    show_company_delivery_addresses(company_id);
	}
    });
}

function new_company_rekvizit(id){
    var table='<form id="company_rekvizit_form_'+id+'_0">';
    table+='<h4>Введите новый расчетный счет:</h4>\
	<div class="form-group row col-sm-12">\
	    <label for="rs" class="col-sm-5 col-form-label">Расчетный счет:</label>\
	    <div class="col-sm-7">\
    	    <input type="text" class="form-control search_str" id="rs" name="rs" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="rs" id="rs_label" onclick="clear_search_order_text(\'rs\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="ks" class="col-sm-5 col-form-label">Корреспондентский счет:</label>\
	    <div class="col-sm-7">\
    	    <input type="text" class="form-control search_str" id="ks" name="ks" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="ks" id="ks_label" onclick="clear_search_order_text(\'ks\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="bik" class="col-sm-5 col-form-label">БИК:</label>\
	    <div class="col-sm-7">\
    	    <input type="text" class="form-control search_str" id="bik" name="bik" value="" onchange="fill_bank('+id+',0)"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="bik" id="bik_label" onclick="clear_search_order_text(\'bik\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="bank" class="col-sm-5 col-form-label">Банк:</label>\
	    <div class="col-sm-7">\
    	    <input type="text" class="form-control search_str" id="bank" name="bank" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="bank" id="bank_label" onclick="clear_search_order_text(\'bank\');"></label>\
	    </div>\
	</div>\
	</form>\
	<button class="btn btn-primary" onclick="save_company_rekvizit('+id+',-1);">Сохранить</button>\
    ';
    create_window("new_rekvizit_div","Новый расчетный счет","new_rekvizit",table);
}

function clear_search_order_text(input_id){
	$('#'+input_id).val('');
	//runTextFilterOrd();
}

function fill_bank(company_id,rekvizit_id){
	var send=[];
	send['bik']=$("#company_rekvizit_form_"+company_id+"_"+rekvizit_id+" input[name=bik]").val();
	api_query_array("/api/index.php",send,"get_bank_data_from_api").then(function(data){
		if(typeof(data.suggestions)!="undefined" && data.suggestions.length>0){
			$("#company_rekvizit_form_"+company_id+"_"+rekvizit_id+" input[name=bank]").val(data.suggestions[0].data.name.payment+' '+data.suggestions[0].data.payment_city);
			$("#company_rekvizit_form_"+company_id+"_"+rekvizit_id+" input[name=ks]").val(data.suggestions[0].data.correspondent_account);
		}
	});
}

function edit_company_rekvizit(company_id,rekvizit_id){
    var table='';
    table+='<form id="company_rekvizit_form_'+company_id+'_'+rekvizit_id+'">\
    	<div class="form-group row col-sm-12">\
    	    <label for="rs" class="col-sm-5 col-form-label">Расчетный счет:</label>\
    	    <div class="col-sm-7">\
        	    <input type="text" class="form-control search_str" id="rs" name="rs" value="'+companys[company_id].rekvizits[rekvizit_id].rs+'"><label style="position: absolute; top: 0.7em; right: 1.2em;" for="rs" id="rs_label" onclick="clear_search_order_text(\'rs\');"></label>\
    	    </div>\
    	</div>\
    	<div class="form-group row col-sm-12">\
    	    <label for="ks" class="col-sm-5 col-form-label">Корреспондентский счет:</label>\
    	    <div class="col-sm-7">\
        	    <input type="text" class="form-control search_str" id="ks" name="ks" value="'+companys[company_id].rekvizits[rekvizit_id].ks+'"><label style="position: absolute; top: 0.7em; right: 1.2em;" for="rs" id="ks_label" onclick="clear_search_order_text(\'ks\');"></label>\
    	    </div>\
    	</div>\
    	<div class="form-group row col-sm-12">\
    	    <label for="bik" class="col-sm-5 col-form-label">БИК:</label>\
    	    <div class="col-sm-7">\
        	    <input type="text" class="form-control search_str" id="bik" name="bik" value="'+companys[company_id].rekvizits[rekvizit_id].bik+'" onchange="fill_bank('+company_id+','+rekvizit_id+')"><label style="position: absolute; top: 0.7em; right: 1.2em;" for="bik" id="bik_label" onclick="clear_search_order_text(\'bik\');"></label>\
    	    </div>\
    	</div>\
    	<div class="form-group row col-sm-12">\
    	    <label for="bank" class="col-sm-5 col-form-label">Банк:</label>\
    	    <div class="col-sm-7">\
        	    <input type="text" class="form-control search_str" id="bank" placeholder="Наименование банка" name="bank" value="'+str_to_val(companys[company_id].rekvizits[rekvizit_id].bank)+'"><label style="position: absolute; top: 0.7em; right: 1.2em;" for="bank" id="bank_label" onclick="clear_search_order_text(\'bank\');"></label>\
    	    </div>\
    	</div>\
    	</form>\
      <button class="btn btn-primary" onclick="save_company_rekvizit('+company_id+','+rekvizit_id+');">Сохранить</button>\
    ';
    create_window("rekvizit_"+company_id+"_"+rekvizit_id+"_div","Редактирование расчетного счета","rekvizit_"+company_id+"_"+rekvizit_id,table);
}

function delete_company_rekvizit(company_id,rekvizit_id){
    var send=new Array();
    send['id']=rekvizit_id;
    api_query_array("/api/index.php",send,"delete_company_rekvizit").then(function(data){
	if(data.status=="ok") show_company_rekvizits(company_id);
    });
}

function save_company_rekvizit(company_id,rekvizit_id){
    var send=new Array();
    send['rs']=$('#rs').val();
    send['ks']=$('#ks').val();
    send['bik']=$('#bik').val();
    send['bank']=$('#bank').val();
    if(rekvizit_id>=0 && parseInt(companys[company_id].rekvizits[rekvizit_id].id)>0) send['id']=parseInt(companys[company_id].rekvizits[rekvizit_id].id);
    else send['id']=0;
    send['company_id']=company_id;
    api_query_array("/api/index.php",send,"save_company_rekvizit").then(function(data){
	if(data.status=="ok") {
	    $("#new_rekvizit").html('');
	    if(parseInt(data.id)>0){
		if(send['id']!=parseInt(data.id)){
		    send['id']=parseInt(data.id);
		    var da_len=companys[company_id].rekvizits.length;
		    companys[company_id].rekvizits[da_len]=send;
		}
		else {
		    companys[company_id].rekvizits[rekvizit_id]=send;
		}
	    }
	    show_company_rekvizits(company_id);
	}
    });
}

function save_company(id,btype=0){
    api_query("/api/index.php","new_client_form","save_company").then(function(data){
      if(btype>0 && id==0){
        if(btype==2 || btype==4 || btype==7 || btype==9) {
          get_dealers();
          $("[id^=dealer_data_]").html('');
		  if(btype==7){
			if(data.status=="ok"){
				$("#price_list_form_"+id+" #company_id").val(data.company_id);
				$("#price_list_form_"+id+" #price_company_name").val(data.company_name);
			}
		  }
		  if(btype==9){
			if(data.status=="ok"){
				$("#deliverer_company_id").val(data.company_id);
				$("#deliverer_company_name").val(data.company_name);
			}
		  }
        }
        else {
			if(btype==3){
				get_my_companies();
			}
			else
          		get_clients();
          $("[id^=client_data_]").html('');
        }
        if(parseInt(data.company_id)>0) show_company_data1(parseInt(data.company_id),btype);
	  }
	  else {
		if(btype==2 || btype==4 || btype==7) {
			get_dealers();
			$("[id^=dealer_data_]").html('');
		  }
		  else {
			if(btype==3){
				get_my_companies();
			}
			else
				get_clients();
			$("[id^=client_data_]").html('');
		  }
		//show_company_data1(id,btype);
	  }
    });
}

function show_company_delivery_addresses(id){
    var deliv_addr=companys[id].delivery_addresses;
    var table='';
    table+='<br><button class="btn btn-primary" onclick="new_delivery_address('+id+');">Добавить</button><div id="new_delivery_address"></div>';
    table+='<table class="table"><thead><tr><th>Адрес</th><th>Рабочие дни</th><th>Время с</th><th>Время по</th><th></th></tr></thead>';
	if(typeof(deliv_addr)!="undefined"){
		for (var i=0; i<deliv_addr.length; i++){
		table += '<tr><td><div id="delivery_address_'+id+'_'+i+'"></div>'+deliv_addr[i].delivery_address+'</td><td>'+deliv_addr[i].delivery_days+'</td><td>'+deliv_addr[i].delivery_time_start+'</td><td>'+deliv_addr[i].delivery_time_stop+'</td><td><button class="btn btn-primary btn-xs glyphicon glyphicon-pencil" onclick="edit_delivery_address('+id+','+i+');"></button></td></tr>';
		}
	}
    table += '</tbody></table>';
    $('#company_delivery_addresses').html(table);
}

function show_company_rekvizits1(id){
    var table='<br><button class="btn btn-primary" onclick="new_company_rekvizit('+id+');">Добавить</button><div id="new_rekvizit"></div>';
    table+='<table class="table"><thead><tr><th>Р/С</th><th>К/С</th><th>БИК</th><th>БАНК</th><th></th></tr></thead><tbody>';
    var rekv=companys[id].rekvizits;
    for (var i=0; i<rekv.length; i++){
	table += '<tr><td><div id="rekvizit_'+id+'_'+i+'"></div>'+rekv[i].rs+'</td><td>'+rekv[i].ks+'</td><td>'+rekv[i].bik+'</td><td>'+rekv[i].bank+'</td>';
	table += '<td>';
	table += '<button class="btn btn-primary btn-xs glyphicon glyphicon-pencil" onclick="edit_company_rekvizit('+id+','+i+');"></button>';
	table += '<button class="btn btn-primary btn-xs glyphicon glyphicon-trash" onclick="delete_company_rekvizit('+id+','+i+');"></button>';
	table += '</td></tr>';
    }
    table += '</tbody></table>';
    $('#company_rekvizits').html(table);
}

function show_company_rekvizits(id){
    var table='<br><button class="btn btn-primary" onclick="new_company_rekvizit('+id+');">Добавить</button><div id="new_rekvizit"></div>';
    table+='<table class="table"><thead><tr><th>Р/С</th><th>К/С</th><th>БИК</th><th>БАНК</th><th></th></tr></thead><tbody>';
    //var rekv=companys[id].rekvizits;
    var send=new Array();
    send['company_id']=id;
    api_query_array("/api/index.php",send,"get_company_rekvizits").then(function(data){
	for (var i=0; i<data.rekvizits.length; i++){
	    table += '<tr><td><div id="rekvizit_'+id+'_'+i+'"></div>'+data.rekvizits[i].rs+'</td><td>'+data.rekvizits[i].ks+'</td><td>'+data.rekvizits[i].bik+'</td><td>'+data.rekvizits[i].bank+'</td>';
	    table += '<td nowrap>';
	    table += '<button class="btn btn-primary btn-xs glyphicon glyphicon-pencil" onclick="edit_company_rekvizit('+id+','+i+');"></button>';
	    table += ' <button class="btn btn-primary btn-xs glyphicon glyphicon-trash" onclick="bootbox.confirm(\'Вы точно хотите удалить реквизиты?\',function(result){ if(result)delete_company_rekvizit('+id+','+data.rekvizits[i].id+');})"></button>';
	    table += '</td></tr>';
	}
	table += '</tbody></table>';
	$('#company_rekvizits').html(table);
    });
}

function change_main_org(){
	if($("#inn").val()==""){
		bootbox.alert("Введите инн организации");
		return;
	}
	var send=[];
	send['inn']=$("#inn").val();
	api_query_array("/api/index.php",send,"get_main_org").then(function(data){
		var table='<table class="table table-hover"><thead><th>инн</th><th>кпп</th><th>наименование</th></thead><tbody>';
		for (var i in data.main_orgs){
			table+='<tr onclick="set_main_org('+data.main_orgs[i].id+',\''+str_to_val(data.main_orgs[i].name)+'\');"><td>'+data.main_orgs[i].inn+'</td><td>'+data.main_orgs[i].kpp+'</td><td>'+data.main_orgs[i].name+'</td></tr>';
		}
		table+='</tbody></table>';
		create_window("main_orgs_list_div","Выберите головную организацию","main_orgs_list",table);
	});
}

function set_main_org(id,name){
	$("#main_org_id").val(id);
	$("#main_org_name").val(name);
	$("#main_orgs_list").html('');
}

function print_main_data_form(id,change_on_form=1,type=1){
  if(id>0) data=companys[id];
  else data=Object;
  if(change_on_form) type=$("#company_type").val();
  if(type==3){
    var data_html='<div class="form-group row col-sm-12">\
    <label for="company_name" class="col-sm-4 col-form-label">ФИО</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" id="company_name" placeholder="ФИО" name="company_name" value="'+str_to_val(data.name!="Object"?data.name:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_name" id="company_name_label" onclick="clear_search_order_text(\'company_name\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="company_inn" class="col-sm-4 col-form-label">ИНН</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" onclick="this.select();" id="inn" placeholder="ИНН" name="inn" value="'+(typeof(data.inn)!="undefined"?data.inn:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="inn" id="inn_label" onclick="clear_search_order_text(\'inn\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="inputPassword3" class="col-sm-4 col-form-label">КПП</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" id="kpp" onclick="this.select();" placeholder="КПП" name="kpp" value="'+(typeof(data.kpp)!="undefined"?data.kpp:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="kpp" id="kpp_label" onclick="clear_search_order_text(\'kpp\');"></label>\
    </div>\
    </div>\
	<div class="form-group row col-sm-12">\
	    <label for="inputAddress" class="col-sm-4 col-form-label">Адрес</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="address" placeholder="Адрес" name="address" value="'+(typeof(data.address)!="undefined"?data.address:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="address" id="address_label" onclick="clear_search_order_text(\'address\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="tel" class="col-sm-4 col-form-label">Телефон</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="mphone" placeholder="Телефон" name="mphone" value="'+(typeof(data.mphone)!="undefined"?data.mphone:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="mphone" id="mphone_label" onclick="clear_search_order_text(\'mphone\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="email" class="col-sm-4 col-form-label">E-mail</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="email" placeholder="E-mail" name="email" value="'+(typeof(data.email)!="undefined"?data.email:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="email" id="email_label" onclick="clear_search_order_text(\'email\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="birthday" class="col-sm-4 col-form-label">Дата рождения</label>\
	    <div class="col-sm-8">\
    	    <input type="date" class="form-control" id="client_birthday" name="birthday" value="'+(typeof(data.birthday)!="undefined"?data.birthday:"")+'">\
	    </div>\
	</div>';
  }
  else {
    var data_html='<div class="form-group row col-sm-12">\
    <label for="company_name" class="col-sm-4 col-form-label">Наименование организации</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" id="company_name" placeholder="Наименование организации" name="company_name" value="'+str_to_val(data.name!="Object"?data.name:"")+'" onchange="fill_form(\'name\')"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_name" id="company_name_label" onclick="clear_search_order_text(\'company_name\');"></label>\
    </div>\
    <div id="companys_list">\
    </div>\
    </div>\
	<div class="form-group row col-sm-12">\
    <label for="company_short_name" class="col-sm-4 col-form-label">Кр. наимен. организации</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" id="company_short_name" placeholder="Краткое наименование организации" name="company_short_name" value="'+str_to_val(data.short_name!="Object"?data.short_name:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_short_name" id="company_short_name_label" onclick="clear_search_order_text(\'company_short_name\');"></label>\
    </div>\
    <div id="companys_list">\
    </div></div>';
	data_html+='\
    <div class="form-group row col-sm-12">\
    <label for="company_inn" class="col-sm-4 col-form-label">ИНН</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" id="inn" onclick="this.select();" placeholder="ИНН" name="inn" value="'+(typeof(data.inn)!="undefined"?data.inn:"")+'" onchange="fill_form(\'inn\')"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="inn" id="inn_label" onclick="clear_search_order_text(\'inn\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="inputPassword3" class="col-sm-4 col-form-label">КПП</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" onclick="this.select();" id="kpp" placeholder="КПП" name="kpp" value="'+(typeof(data.kpp)!="undefined"?data.kpp:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="kpp" id="kpp_label" onclick="clear_search_order_text(\'kpp\');"></label>\
    </div>\
    </div>';
	data_html+='<div class="form-group row col-sm-12"';
	if(type!=4) {
		data_html+=' style="display:none;">';
	}
	else {
		data_html+='>';
	}
	data_html+='<label for="main_org_name" class="col-sm-4 col-form-label">Головная организация</label>\
    <div class="col-sm-8">\
		<input type="hidden" name="main_org_id" id="main_org_id" value="'+(typeof(data.main_org_id)=="undefined"?"0":data.main_org_id)+'">\
      <input type="text" class="form-control search_str" id="main_org_name" placeholder="Выберите головную организацию" name="main_org_name" value="'+str_to_val(data.main_org_name)+'" onclick="change_main_org();" readonly>\
    </div>\
    <div id="main_orgs_list">\
    </div>\
    </div>';
	data_html+='<div class="form-group row col-sm-12">\
	    <label for="inputAddress" class="col-sm-4 col-form-label">Юридический адрес</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();"  id="address" placeholder="Юридический адрес" name="address" value="'+(typeof(data.address)!="undefined"?data.address:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="address" id="address_label" onclick="clear_search_order_text(\'address\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="inputogrn" class="col-sm-4 col-form-label">ОГРН</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="ogrn" placeholder="ОГРН" name="ogrn" value="'+(typeof(data.ogrn)!="undefined"?data.ogrn:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="ogrn" id="ogrn_label" onclick="clear_search_order_text(\'ogrn\');"></label>\
	    </div>\
	</div>\
  <div class="form-group row col-sm-12">\
	    <label for="inputokpo" class="col-sm-4 col-form-label">ОКПО</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="okpo" placeholder="ОКПО" name="okpo" value="'+(typeof(data.okpo)!="undefined"?data.okpo:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="okpo" id="okpo_label" onclick="clear_search_order_text(\'okpo\');"></label>\
	    </div>\
	</div>\
  <div class="form-group row col-sm-12">\
	    <label for="inputokved" class="col-sm-4 col-form-label">ОКВЭД</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();"  id="okved" placeholder="ОКВЕД" name="okved" value="'+(typeof(data.okved)!="undefined"?data.okved:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="okved" id="okved_label" onclick="clear_search_order_text(\'okved\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="inputruk" class="col-sm-4 col-form-label">Руководитель</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="ruk" placeholder="Руководитель" name="ruk" value="'+(typeof(data.ruk)!="undefined"?data.ruk:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="ruk" id="ruk_label" onclick="clear_search_order_text(\'ruk\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="inputrukdol" class="col-sm-4 col-form-label">Должность руководителя</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="rukdol" placeholder="Должность руководителя" name="rukdol" value="'+(typeof(data.rukdol)!="undefined"?data.rukdol:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="rukdol" id="rukdol_label" onclick="clear_search_order_text(\'rukdol\');"></label>\
	    </div>\
	</div>\
  <div class="form-group row col-sm-12">\
	    <label for="inputbuh" class="col-sm-4 col-form-label">Бухгалтер</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="buh" placeholder="Бухгалтер" name="buh" value="'+(typeof(data.buh)!="undefined"?data.buh:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="buh" id="buh_label" onclick="clear_search_order_text(\'buh\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="inputbuhdol" class="col-sm-4 col-form-label">Должность бухгалтера</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="buhdol" placeholder="Должность бухгалтера" name="buhdol" value="'+(typeof(data.buhdol)!="undefined"?data.buhdol:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="buhdol" id="buhdol_label" onclick="clear_search_order_text(\'buhdol\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="tel" class="col-sm-4 col-form-label">Телефон</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="mphone" placeholder="Телефон" name="mphone" value="'+(typeof(data.mphone)!="undefined"?data.mphone:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="mphone" id="mphone_label" onclick="clear_search_order_text(\'mphone\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="email" class="col-sm-4 col-form-label">E-mail</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="email" placeholder="E-mail" name="email" value="'+(typeof(data.email)!="undefined"?data.email:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="email" id="email_label" onclick="clear_search_order_text(\'email\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="ipreg_num" class="col-sm-4 col-form-label">Уведомление о пост. на учет в качестве ИП</label>\
	    <div class="col-sm-8">\
    	    <input type="text" class="form-control search_str" onclick="this.select();" id="ipreg_num" placeholder="Уведомление о пост. на учет в качестве ИП" name="ipreg_num" value="'+(typeof(data.ipreg_num)!="undefined"?data.ipreg_num:"")+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="ipreg_num" id="ipreg_num_label" onclick="clear_search_order_text(\'ipreg_num\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="ipreg_date" class="col-sm-4 col-form-label">Дата уведомления</label>\
	    <div class="col-sm-8">\
    	    <input type="date" class="form-control" id="ipreg_date" placeholder="Дата уведомление о пост. на учет в качестве ИП" name="ipreg_date" value="'+(typeof(data.ipreg_date)!="undefined"?data.ipreg_date:"")+'">\
	    </div>\
	</div>';
  }
  data_html+='<div class="form-group row col-sm-12">\
  <label for="company_descr" class="col-sm-4 col-form-label">Примечание</label>\
  <div class="col-sm-8">\
	  <input type="text" class="form-control" id="company_descr" placeholder="Примечание" name="descr" value="'+(typeof(data.descr)!="undefined"?data.descr:"")+'">\
  </div>\
</div>\
<div class="form-group row col-sm-12">\
  <label for="show_company_descr" class="col-sm-4 col-form-label">Показывать примечание при оформлении заказа</label>\
  <div class="col-sm-8">\
	  <input type="checkbox" id="show_company_descr" name="show_descr"';
	  if(data.show_descr=="1") data_html+=' checked="checked"';
		data_html+='>\
  </div>\
</div>\
<div class="form-group row col-sm-12">\
  <label for="company_timezone" class="col-sm-4 col-form-label">Временная зона</label>\
  <div class="col-sm-8">\
	<select id="company_timezone" name="company_timezone" class="form-control">';
	 for(var t in timezones){
		data_html+='<option value="'+timezones[t].identifier+'"';
	  	if(data.timezone!="" && data.timezone==timezones[t].identifier) {
			data_html+=' selected';
		}
		if(my_timezone=="" && data.timezone=="" && timezones[t].identifier=="Europe/Moscow"){
			data_html+=' selected';
		}
		if(my_timezone!="" && data.timezone=="" && my_timezone==timezones[t].identifier) {
			data_html+=' selected';
		}
		data_html+='>'+timezones[t].identifier+' '+timezones[t].code+'</option>';
	 }
		data_html+='\
  	</select>\
  </div>\
</div>';

  if(change_on_form){
    $("#company_datas").html(data_html);
  }
  else
    return data_html;
}

function show_company_main_data(id,btype=0){	
	/*switch(parseInt(btype)){
		case 7: btype=2; break;
	}*/
	var data=companys[id];
	var data_html='<form id="new_client_form">\
	<div id="company_types"><div class="form-group row col-sm-12">\
			<label for="company_type" class="col-sm-4 col-form-label">ОКОПФ</label>\
			<div class="col-sm-8">\
			<select class="form-control" id="company_type" placeholder="" name="okopf" onchange="print_main_data_form('+id+',1)">';
	comp_types_len=data.company_types.length;
	if(btype>0 && (typeof(data.btype)=="undefined" || parseInt(data.btype)<1)) {
		if(btype==7 || btype==9) data.btype=2;	
		else data.btype=btype;
	}
	for(var i=0; i<comp_types_len; i++){
		if (data.company_types[i].id == data.type || ( i==0 && data.type==0 )) data_html+="<option value=\""+data.company_types[i].id+"\" selected=\"selected\">"+data.company_types[i].type+"</option>";
		else data_html+="<option value=\""+data.company_types[i].id+"\">"+data.company_types[i].type+"</option>";
	}
	data_html+='      </select>\
	<input type="hidden" name="company_id" value="'+data.id+'">\
	</div>\
	</div>';
	if(data.type==4){
		data_html+='<div class="form-group row col-sm-12">\
			<label for="company_btype" class="col-sm-4 col-form-label">В УПД указать покупателем</label>\
			<div class="col-sm-8">\
			<input type="checkbox" class="" id="buyer_in_upd" placeholder="" name="buyer_in_upd"';
			if(data.buyer_in_upd==1){
				data_html+=' checked';
			}
	
			data_html+='></div></div>';
	}
	data_html+='<div class="form-group row col-sm-12">\
			<label for="company_btype" class="col-sm-4 col-form-label">Тип компании</label>\
			<div class="col-sm-8">\
			<select class="form-control" id="company_btype" placeholder="" name="btype">';
	comp_btypes_len=data.company_btypes.length;
	for(var i=0; i<comp_btypes_len; i++){
		if (data.company_btypes[i].id == data.btype || (data.btype==0 && i==0)) data_html+="<option value=\""+data.company_btypes[i].id+"\" selected=\"selected\">"+data.company_btypes[i].descr+"</option>";
		else data_html+="<option value=\""+data.company_btypes[i].id+"\">"+data.company_btypes[i].descr+"</option>";
	}
	data_html+='      </select>\
	</div></div>';
	data_html+='<div class="form-group row col-sm-12">\
			<label for="company_tax_type" class="col-sm-4 col-form-label">Система налогообложения</label>\
			<div class="col-sm-8">\
			<select class="form-control" id="company_tax_type" placeholder="" name="tax_type">';
	comp_tax_types_len=data.tax_types.length;
	for(var i=0; i<comp_tax_types_len; i++){
		if (data.tax_types[i].id == data.tax_type || (data.tax_type==0 && i==0)) data_html+="<option value=\""+data.tax_types[i].id+"\" selected=\"selected\">"+data.tax_types[i].name+" "+data.tax_types[i].tax_rate+" %</option>";
		else data_html+="<option value=\""+data.tax_types[i].id+"\">"+data.tax_types[i].name+" "+data.tax_types[i].tax_rate+" %</option>";
	}
	data_html+='      </select>\
	</div></div></div>';
	data_html+='<div id="company_datas">';
	data_html+=print_main_data_form(id,0,data.type);
	data_html+='</div>\
	</form>\
	<button class="btn btn-primary" onclick="save_company('+id+','+btype+');" type="button">Сохранить</button>\
	';
	$('#company_main').html(data_html);
}

function get_clients(){ 
 api_query("/api/index.php","clients_client_search","get_clients").then(function(data){
	if(typeof(data.search_client_name_rus)!="undefined" && data.search_clients_client_name!=data.search_client_name_rus){
		$("#search_clients_client_name").val(data.search_client_name_rus);
	}
    var datalen=data.clients.length;
	var table = '<div style="height: 50px;"><ul class="pagination pagination-sm">';
    var x=0,y=0,xx=0,yy=0;
    if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
    else var selected_page=1;
    for (var i=1; i<=data.clients_pages; i++){
		if(i>(selected_page+6) && i<(data.clients_pages-1)){
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
				table += '><a href="#" onclick="$(\'#clients_client_search input[name=page]\').val(\''+i+'\');';
				if($('#clients_client_search [name=search_clients_client_name]').val()!="") table += '$(\'#clients_client_search [name=search_clients_client_name]\').val(\''+data.search_clients_client_name+'\');';
				table += 'get_clients()">...</a></li>';
			}
			if (x==1) xx++;
		}
		else {
			if (y==1) {
			if (yy==0){
				table += '<li';
				table += '><a href="#" onclick="$(\'#clients_client_search input[name=page]\').val(\''+i+'\');';
				if($('#clients_client_search [name=search_clients_client_name]').val()!="") table += '$(\'#clients_client_search [name=search_clients_client_name]\').val(\''+data.search_clients_client_name+'\');';
				table += 'get_clients()">...</a></li>';
			}
			if (y==1) yy++;
			}
			else {
			table += '<li';
			if(selected_page==i) table+= " class='active'";
			table += '><a href="#" onclick="$(\'#clients_client_search input[name=page]\').val(\''+i+'\');';
			if($('#clients_client_search [name=search_clients_client_name]').val()!="") table += '$(\'#clients_client_search [name=search_clients_client_name]\').val(\''+data.search_clients_client_name+'\');';
			table += 'get_clients()">'+i+'</a></li>';
			}
		}
    }
    table += '</ul></div>';
    table+="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование</th><th>Кр. наим.</th><th>Тел.</th><th>ИНН / КПП</th><th>Адрес</th><th>Баланс</th><th>Резерв</th><th>Сум.оборот</th><th>Тип</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
		table += '<tr ondblclick="show_company_data1('+data.clients[i].id+',1);"';
		if(data.clients[i].deleted=="1") table+=' style="background:pink;"';
		table+="><td><div id='client_data_"+data.clients[i].id+"'></div><div id='client_users_"+data.clients[i].id+"'></div>"+(i+1)+"</td><td>" + data.clients[i].name + "</td><td>" + data.clients[i].short_name + "</td>";
		if(data.clients[i].mphone===null) table+="<td></td>";
		else table+="<td>"+data.clients[i].mphone+"</td>";
		table+="<td>"+data.clients[i].inn+" / "+data.clients[i].kpp+"</td><td>"+data.clients[i].address+"</td><td>";
		if(data.clients[i].company_balance===null) table+="0.00";
		else table+=number_format(parseFloat(data.clients[i].company_balance).toFixed(2),2,"."," ");;
		table+="</td><td>";
		if(data.clients[i].company_rezerv===null) table+="0.00";
		else table+=number_format(parseFloat(data.clients[i].company_rezerv).toFixed(2),2,"."," ");;
		table+="</td><td>";
		if(data.clients[i].sum_trade===null || typeof(data.clients[i].sum_trade)=="undefined") table+="0.00";
		else table+=number_format(parseFloat(data.clients[i].sum_trade).toFixed(2),2,"."," ");
		table+="</td><td>"+data.btypes[data.clients[i].btype]+"</td>";
		table += "<td style='width: 85px;'><form id='delete_company_"+data.clients[i].id+"'><input type=\"hidden\" name=\"company_id\" value=\""+data.clients[i].id+"\"></form>";
		//table += "<button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\" data-toggle=\"modal\" data-target=\"#exampleModalCenter\" onclick=\"show_company_data("+data.clients[i].id+");\"></button>";
		/* table += " <button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\" onclick=\"show_company_data1("+data.clients[i].id+",1);\"></button>";
		table += " <button class=\"glyphicon glyphicon-user btn btn-primary btn-xs\" onclick=\"show_company_users("+data.clients[i].id+",1);\"></button>";
		table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
		table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить вашего клиента?\',function(result){ if(result) api_query('/api/index.php','delete_company_"+data.clients[i].id+"','delete_company').then(function(data){if(data.status=='ok') location.reload()});});\"></button>";
		*/
		table+='<div class="btn-group pull-right" style="display: flex;">';
		if(data.clients[i].deleted=="1") 
			table+="<a onclick=\"bootbox.confirm('Вы точно хотите восстановить вашего клиента?',function(result){ if(result) api_query('/api/index.php','delete_company_"+data.clients[i].id+"','restore_company').then(function(data){if(data.status=='ok') get_clients();});});\" title=\"Восстановить\"><img src=\"/new_images/restore.png\" class=\"menuimg\"></a>";
		table+='<a onclick="show_company_data1('+data.clients[i].id+',1);" title="Редактировать"><img src="/new_images/edit.svg" class="menuimg"></a>';
		table += '<a onclick="show_company_users('+data.clients[i].id+',1);" title="Показать пользователей"><img src="/new_images/user.svg" class="menuimg"></a>';
		table += "<a onclick=\"bootbox.confirm('Вы точно хотите удалить вашего клиента?',function(result){ if(result) api_query('/api/index.php','delete_company_"+data.clients[i].id+"','delete_company').then(function(data){if(data.status=='ok') get_clients();});});\"><img src=\"/new_images/garbage.svg\" class=\"menuimg\"></a>";
		table += "<div></td></tr>";
    }
    table += "</tbody></table>";
    $("#clients_list").html(table);
 });
}

function get_site_users(){ 
	api_query("/api/index.php","site_users_search","get_site_users").then(function(data){
	   var datalen=data.users.length;
	   var table = '<div style="height: 50px;"><ul class="pagination pagination-sm">';
	   var x=0,y=0,xx=0,yy=0;
	   if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
	   else var selected_page=1;
	   for (var j=1; i<=data.site_users_pages; j++){
		   if(j>(selected_page+6) && j<(data.clients_pages-1)){
			   x=1;
		   }
		   else x=0;
		   if (j<(selected_page-6) && j!=1){
			   y=1;
		   }
		   else y=0;
		   if (x==1) {
			   if (xx==0){
				   table += '<li';
				   table += '><a href="#" onclick="$(\'#site_users_search input[name=page]\').val(\''+j+'\');';
				   if($('#site_users_search [name=search_site_users_name]').val()!="") table += '$(\'#site_users_search [name=search_site_users_name]\').val(\''+data.search_site_users_name+'\');';
				   table += 'get_clients()">...</a></li>';
			   }
			   if (x==1) xx++;
		   }
		   else {
			   if (y==1) {
			   if (yy==0){
				   table += '<li';
				   table += '><a href="#" onclick="$(\'#site_users_search input[name=page]\').val(\''+j+'\');';
				   if($('#site_users_search [name=search_site_users_name]').val()!="") table += '$(\'#site_users_search [name=search_site_users_name]\').val(\''+data.search_site_users_name+'\');';
				   table += 'get_clients()">...</a></li>';
			   }
			   if (y==1) yy++;
			   }
			   else {
			   table += '<li';
			   if(selected_page==i) table+= " class='active'";
			   table += '><a href="#" onclick="$(\'#site_users_search input[name=page]\').val(\''+j+'\');';
			   if($('#site_users_search [name=search_site_users_name]').val()!="") table += '$(\'#site_users_search [name=search_site_users_name]\').val(\''+data.search_site_users_name+'\');';
			   table += 'get_clients()">'+j+'</a></li>';
			   }
		   }
	   }
	   table += '</ul></div>';
	   table+="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Тел.</th><th>E-mail</th><th></th></tr></thead><tbody>";
	   for (var i=0; i<datalen; i++){
		   table += "<tr";
		   if(data.users[i].deleted=="1") table+=' style="background:pink;"';
		   table+="><td><div id='site_company_data_"+data.users[i].company_id+"'></div>"+(i+1)+"</td><td>" + data.users[i].lastname + "</td><td>" + data.users[i].name + "</td><td>" + data.users[i].middlename + "</td>";
		   if(data.users[i].phone===null) table+="<td></td>";
		   else table+="<td>"+data.users[i].mphone+"</td>";
		   if(data.users[i].email===null) table+="<td></td>";
		   else table+="<td>"+data.users[i].email+"</td>";
		   table += "<td style='width: 85px;'><form id='delete_site_user_"+data.users[i].id+"'><input type=\"hidden\" name=\"site_user_id\" value=\""+data.users[i].id+"\"></form>";
		   //table += "<button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\" data-toggle=\"modal\" data-target=\"#exampleModalCenter\" onclick=\"show_company_data("+data.clients[i].id+");\"></button>";
		   /* table += " <button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\" onclick=\"show_company_data1("+data.clients[i].id+",1);\"></button>";
		   table += " <button class=\"glyphicon glyphicon-user btn btn-primary btn-xs\" onclick=\"show_company_users("+data.clients[i].id+",1);\"></button>";
		   table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
		   table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить вашего клиента?\',function(result){ if(result) api_query('/api/index.php','delete_company_"+data.clients[i].id+"','delete_company').then(function(data){if(data.status=='ok') location.reload()});});\"></button>";
		   */
		   table+='<div class="btn-group pull-right" style="display: flex;">';
		   if(data.users[i].deleted=="1") 
			   table+="<a onclick=\"bootbox.confirm('Вы точно хотите восстановить вашего клиента?',function(result){ if(result) api_query('/api/index.php','delete_site_user_"+data.users[i].id+"','restore_site_user').then(function(data){if(data.status=='ok') get_site_users();});});\" title=\"Восстановить\"><img src=\"/new_images/restore.png\" class=\"menuimg\"></a>";
		   table+='<a onclick="show_company_data1('+data.users[i].company_id+',8);" title="Редактировать"><img src="/new_images/edit.svg" class="menuimg"></a>';
		   table += "<a onclick=\"bootbox.confirm('Вы точно хотите удалить вашего клиента?',function(result){ if(result) api_query('/api/index.php','delete_company_"+data.users[i].id+"','delete_company').then(function(data){if(data.status=='ok') get_clients();});});\"><img src=\"/new_images/garbage.svg\" class=\"menuimg\"></a>";
		   table += "</div></td></tr>";
	   }
	   table += "</tbody></table>";
	   $("#site_users_list").html(table);
	});
   }

function get_dealers(){
 api_query("/api/index.php","clients_dealer_search","get_dealers").then(function(data){
	if(typeof(data.search_dealer_name_rus)!="undefined" && data.search_clients_dealer_name!=data.search_dealer_name_rus){
		$("#search_clients_dealer_name").val(data.search_dealer_name_rus);
	}
    var datalen=data.dealers.length;
    var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование</th><th>Кр. наим.</th><th>ИНН / КПП</th><th>Адрес</th><th>Баланс</th><th>Тип</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
	table += "<tr ondblclick=\"show_company_data1("+data.dealers[i].id+",2);\"><td><div id='dealer_data_"+data.dealers[i].id+"'></div><div id='dealer_users_"+data.dealers[i].id+"'></div>"+(i+1)+"</td><td>" + data.dealers[i].name + "</td><td>" + data.dealers[i].short_name + "</td><td>"+data.dealers[i].inn+" / "+data.dealers[i].kpp+"</td><td>"+data.dealers[i].address+"</td><td>";
	if(data.dealers[i].company_balance!==null) table+=parseFloat(data.dealers[i].company_balance).toFixed(2);
	else table+='0.00';
	table+="</td><td>"+data.btypes[data.dealers[i].btype]+"</td>";
	table += "<td style='width: 85px;'><form id='delete_company_"+data.dealers[i].id+"'><input type=\"hidden\" name=\"company_id\" value=\""+data.dealers[i].id+"\"></form>";
	//table += "<button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\" data-toggle=\"modal\" data-target=\"#exampleModalCenter\" onclick=\"show_company_data("+data.clients[i].id+");\"></button>";
	table += " <a onclick=\"show_company_data1("+data.dealers[i].id+",2);\"><img src=\"/new_images/edit.svg\" style=\"width:20px;\"></a>";
	table += " <a onclick=\"show_company_users("+data.dealers[i].id+",2);\"><img src=\"/new_images/user.svg\" style=\"width:20px;\"></a>";
	table += " <a \" ";
	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить вашего клиента?\',function(result){ if(result) api_query('/api/index.php','delete_company_"+data.dealers[i].id+"','delete_company').then(function(data){if(data.status=='ok') get_dealers()});});\"><img src=\"/new_images/garbage.svg\" style=\"width:20px;\"></a>";
	table += "</td></tr>";
    }
    table += "</tbody></table>";
    $("#dealers_list").html(table);
 });
}

function get_logistic_companys(){
 api_query("/api/index.php","some_form","get_logistic_companys").then(function(data){
    var datalen=data.logistic_companys.length;
    var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование</th><th>ИНН / КПП</th><th>Адрес</th><th>Тип</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
	table += "<tr><td><div id='logistic_data_"+data.logistic_companys[i].id+"'></div><div id='logistic_users_"+data.logistic_companys[i].id+"'></div>"+(i+1)+"</td><td>" + data.logistic_companys[i].name + "</td><td>"+data.logistic_companys[i].inn+" / "+data.logistic_companys[i].kpp+"</td><td>"+data.logistic_companys[i].address+"</td><td>"+data.btypes[data.logistic_companys[i].btype]+"</td>";
	table += "<td><form id='delete_company_"+data.logistic_companys[i].id+"'><input type=\"hidden\" name=\"company_id\" value=\""+data.logistic_companys[i].id+"\"></form>";
	//table += "<button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\" data-toggle=\"modal\" data-target=\"#exampleModalCenter\" onclick=\"show_company_data("+data.clients[i].id+");\"></button>";
	table += " <a onclick=\"show_company_data1("+data.logistic_companys[i].id+",5);\"><img src=\"/new_images/edit.svg\" style=\"width:20px;\"></a>";
	table += " <a onclick=\"show_company_users("+data.logistic_companys[i].id+",5);\"><img src=\"/new_images/user.svg\" style=\"width:20px;\"></a>";
	table += " <a \" ";
	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить вашего клиента?\',function(result){ if(result) api_query('/api/index.php','delete_company_"+data.logistic_companys[i].id+"','delete_company').then(function(data){if(data.status=='ok') location.reload()});});\"><img src=\"/new_images/garbage.svg\" style=\"width:20px;\"></a>";
	table += "</td></tr>";
    }
    table += "</tbody></table>";
    $("#logistics_list").html(table);
 });
}

var users=new Array();
var roles=new Array();
api_query("/api/index.php","some_form","get_roles").then(function(data){
    if(data.status=="ok") {
	var roles_len=data.roles.length;
	for(var i=0; i<roles_len; i++){
	    roles[i]=data.roles[i];
	}
    }
});

function show_company_users(id,type){
    var id_arr=new Array();
    $('div [id^=client_users_]').html('');
    var table="<button class='btn btn-primary' onclick='add_company_user("+id+")'>Добавить</button><div id='new_company_user'></div>";
    table += "<table class='table table-hover'><thead><tr><th> Логин </th><th> Имя </th><th> Фамилия </th><th></th></tr></thead><tbody>";
    id_arr['company_id']=id;
    api_query_array("/api/index.php",id_arr,"get_company_users").then(function(data){
	users[id]=new Array();
	users[id]=data.users;
	var userslen=data.users.length;
	for (var i=0; i<userslen; i++){
	    table += "<tr><td><div id='edit_user_"+i+"'></div>"+data.users[i].username+"</td><td>"+data.users[i].name+"</td><td>"+data.users[i].lastname+"</td><td><button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\" onclick=\"edit_company_user("+id+","+i+");\"></button> <button class=\"glyphicon glyphicon-lock btn btn-primary btn-xs\" onclick=\"change_password_company_user("+id+","+i+");\"></button></td></tr>";
	}
	table+="</tbody></table>";
	switch(type) {
		case 1: var divname="client"; break;
		case 2: var divname="dealer"; break;
		case 3: var divname="my_company"; break;
		case 5: var divname="logistic"; break;
	}
	create_window_centered_blue(divname+"_users_div_"+id,"Список пользователей",divname+"_users_"+id,table);
    });
}

function change_password_company_user(company_id,user_index){
  var user_id=users[company_id][user_index].id;
  var table='<div id="password_change_'+user_id+'">\
 <form id="user_password_data_'+user_id+'">\
    Введите пароль для пользователя:\
    <input type="password" id="pswrd" class="form-control search_str" name="password"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="pswrd" id="pswrd_label" onclick="clear_search_order_text(\'pswrd\');"></label><br>\
    Повторите пароль:\
    <input type="password" id="pswrd1" class="form-control search_str" name="password1"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="pswrd1" id="pswrd1_label" onclick="clear_search_order_text(\'pswrd1\');"></label><br>\
    <input type="hidden" name="user_id" value="'+user_id+'">\
    <input type="hidden" name="company_id" value="'+company_id+'">\
 </form>\
 <hr>\
    <button type="button" class="btn btn-primary" onclick="change_user_password(\'user_password_data_'+user_id+'\','+user_index+')">\
	Изменить\
    </button>\
        <button type="button" class="btn btn-secondary pull-right" onclick="close_window(\'edit_user_'+user_index+'\');">Закрыть</button>\
    </div>';
    create_window("edit_user_"+user_index+"_div","Измените пароль пользователя","edit_user_"+user_index,table);
}

function change_user_password(form_id,user_index){
  var pass1=$("#"+form_id+" [name=password]").val();
  var pass2=$("#"+form_id+" [name=password1]").val();
  if(pass1=="" || pass2=="") {
    bootbox.alert("Пароли пустые!");
    return 0;
  }
  if(pass1===pass2) {
    var send=new Array();
    send['company_id']=$("#"+form_id+" [name=company_id]").val();
    send['user_id']=$("#"+form_id+" [name=user_id]").val();
    send['password']=pass1;
    api_query_array("/api/index.php",send,"change_user_password").then(function(data){
      if(data.status=="ok"){
        $("#edit_user_"+user_index).html('');
        bootbox.alert("Пароль успешно изменен!");
      }
    });
  }
  else {
    bootbox.alert("Пароли не совпадают!");
    return 0;
  }

}

function add_company_user(company_id){
    var users_len=users[company_id].length;
    users[company_id][users_len]=new Array();
    var user_id=users_len;
    var table='<script src="/vendor/BootsptrapFormHelpers/js/bootstrap-formhelpers-phone.js"></script>\
<ul class="nav nav-tabs">\
  <li class="active"><a data-toggle="tab" href="#user_contacts">Контактная информация</a></li>\
  <li><a data-toggle="tab" href="#user_right">Права доступа</a></li>\
  <li><a data-toggle="tab" href="#user_api">API</a></li>\
</ul>\
<form id="company_user_data_'+company_id+'">\
<input type="hidden" name="new_user" value="1">\
<div class="tab-content" style="width:450px;">\
  <div id="user_contacts" class="tab-pane fade in active">\
	<table class="table" width="450px">\
	    <tr><td>Фамилия<br><input type="text" id="contactSur" class="form-control search_str" name="lastname" value="" onchange="change_user_data('+company_id+','+user_id+',\'lastname\');"><label style="position: absolute; top: 7.4em; left: 18em;" for="contactSur" id="contactSur_label" onclick="clear_search_order_text(\'contactSur\');"></label></td>\
	    <td>Имя<br><input type="text" class="form-control search_str" id="contactName" name="name" value="" onchange="change_user_data('+company_id+','+user_id+',\'name\');"><label style="position: absolute; top: 7.4em; right: 1.3em;" for="contactName" id="contactName_label" onclick="clear_search_order_text(\'contactName\');"></label></td></tr>\
	    <tr><td>Имя пользователя<br><input type="text" id="contactUserName" class="form-control search_str" name="username" value="" onchange="change_user_data('+company_id+','+user_id+',\'username\');"><label style="position: absolute; top: 12.4em; left: 18em;" for="contactUserName" id="contactUserName_label" onclick="clear_search_order_text(\'contactUserName\');"></label></td>\
	    <td>E-mail<br><input type="text" class="form-control search_str" id="contactEmail" name="email"  value="" onchange="change_user_data('+company_id+','+user_id+',\'email\');"><label style="position: absolute; top: 12.7em; right: 1.3em;" for="contactEmail" id="contactEmail_label" onclick="clear_search_order_text(\'contactEmail\');"></label></td></tr>\
	    <tr><td>Телефон<br><input type="text" class="form-control bfh-phone search_str" id="contactPhone" data-format="+d (ddd) ddd-dd-dd" name="phone" value="" onchange="change_user_data('+company_id+','+user_id+',\'phone\');"><label style="position: absolute; top: 17.4em; left: 18em;" for="contactPhone" id="contactPhone_label" onclick="clear_search_order_text(\'contactPhone\');"></label></td>\
	    <td>Мобильный Телефон<br><input type="text" class="form-control bfh-phone search_str" id="contactMphone" data-format="+d (ddd) ddd-dd-dd" name="mphone" value="" onchange="change_user_data('+company_id+','+user_id+',\'mphone\');"><label style="position: absolute; top: 18em; right: 1.3em;" for="contactMphone" id="contactMphone_label" onclick="clear_search_order_text(\'contactMphone\');"></label></td></tr>\
	</table>\
	<hr>Привязка к компаниям:\
	<table class="table">';
    table += '</table>';
    table += '</div>';
    table += '<div id="user_right" class="tab-pane fade">';
    table+='<h3>Уровень доступа</h3>\
	<p>Выберите уровень доступа для пользователя</p>\
	<select name="roles" class="form-control">';
    var roles_len=roles.length;
    for(var i=0; i<roles_len; i++){
	    table+='<option value="'+roles[i]['id']+'"';
	    if (parseInt(roles[i]['id'])==10) table+=' selected="selected"';
		table+='>'+roles[i]['name_rus']+'</option>';
    }
    table += '</select>';
    table += '</div></div></form>';
    table+='<hr>\
    <button type="button" class="btn btn-primary" onclick="save_user_data('+company_id+','+user_id+');">\
	Сохранить </button>\
        <button type="button" class="btn btn-secondary pull-right" onclick="location.reload();">Закрыть</button>';
    create_window("edit_user_div_"+user_id,"Добавление пользователя","new_company_user",table);
}

function edit_company_user(company_id,user_id){
    var table='<script src="/vendor/BootsptrapFormHelpers/js/bootstrap-formhelpers-phone.js"></script>\
<ul class="nav nav-tabs">\
  <li class="active"><a data-toggle="tab" href="#user_contacts">Контактная информация</a></li>\
  <li><a data-toggle="tab" href="#user_right">Права доступа</a></li>\
</ul>\
<form id="company_user_data_'+company_id+'">\
<div class="tab-content" style="width:450px;">\
  <div id="user_contacts" class="tab-pane fade in active">\
	<table class="table" width="450px">\
	    <tr><td>Фамилия<br><input type="text" id="contactSur" class="form-control search_str" name="lastname" value="'+users[company_id][user_id]['lastname']+'" onchange="change_user_data('+company_id+','+user_id+',\'lastname\');"><label style="position: absolute; top: 7.7em; left: 18.2em;" for="contactSur" id="contactSur_label" onclick="clear_search_order_text(\'contactSur\');"></label></td>\
	    <td>Имя<br><input type="text" id="contactName" class="form-control search_str" name="name" value="'+users[company_id][user_id]['name']+'" onchange="change_user_data('+company_id+','+user_id+',\'name\');"><label style="position: absolute; top: 7.7em; right: 1.3em;" for="contactName" id="contactName_label" onclick="clear_search_order_text(\'contactName\');"></label></td></tr>\
	    <tr><td>Имя пользователя<br><input type="text" id="contactUserName" class="form-control search_str" name="username" value="'+users[company_id][user_id]['username']+'" onchange="change_user_data('+company_id+','+user_id+',\'username\');"><label style="position: absolute; top: 13.8em; left: 18.2em;" for="contactUserName" id="contactUserName_label" onclick="clear_search_order_text(\'contactUserName\');"></label></td>\
	    <td>E-mail<br><input type="text" class="form-control search_str" name="email" id="contactEmail"  value="'+users[company_id][user_id]['email']+'" onchange="change_user_data('+company_id+','+user_id+',\'email\');"><label style="position: absolute; top: 13.4em; right: 1.3em;" for="contactEmail" id="contactEmail_label" onclick="clear_search_order_text(\'contactEmail\');"></label></td></tr>\
	    <tr><td>Телефон<br><input type="text" class="form-control bfh-phone search_str" id="contactPhone" data-format="+d (ddd) ddd-dd-dd" name="phone" value="'+users[company_id][user_id]['phone']+'" onchange="change_user_data('+company_id+','+user_id+',\'phone\');"><label style="position: absolute; top: 19.2em; left: 18.3em;" for="contactPhone" id="contactPhone_label" onclick="clear_search_order_text(\'contactPhone\');"></label></td>\
	    <td>Мобильный Телефон<br><input type="text" id="contactMphone" class="form-control bfh-phone search_str" data-format="+d (ddd) ddd-dd-dd" name="mphone" value="'+users[company_id][user_id]['mphone']+'" onchange="change_user_data('+company_id+','+user_id+',\'mphone\');"><label style="position: absolute; top: 19.2em; right: 1.3em;" for="contactMphone" id="contactMphone_label" onclick="clear_search_order_text(\'contactMphone\');"></label></td></tr>\
	</table>\
	<input type="hidden" name="edit_user" value="'+users[company_id][user_id]['id']+'">\
	<input type="hidden" name="user_id" value="'+users[company_id][user_id]['id']+'">\
	<hr>Привязка к компаниям:\
	<table class="table">';
    table += '</table>';
    table += '</div>';
    table += '<div id="user_right" class="tab-pane fade">';
    table+='<h3>Уровень доступа</h3>\
	<p>Выберите уровень доступа для пользователя</p>\
	<select name="roles" class="form-control" onchange="change_user_data('+company_id+','+user_id+',\'roles\');">';
    var roles_len=roles.length;
    for(var i=0; i<roles_len; i++){
	    table+='<option value="'+roles[i]['id']+'"';
	    if (parseInt(roles[i]['id'])==users[company_id][user_id]['roles']) table+=' selected="selected"';
		table+='>'+roles[i]['name_rus']+'</option>';
    }
    table += '</select>';
    table += '</div></div></form>';
    table+='<hr>\
    <button type="button" class="btn btn-primary" onclick="save_user_data('+company_id+','+user_id+');">\
	Сохранить </button>\
        <button type="button" class="btn btn-secondary pull-right" onclick="$(\'#edit_user_'+user_id+'\').html(\'\');">Закрыть</button>';
    create_window("edit_user_div_"+user_id,"Список пользователей","edit_user_"+user_id,table);
}

function change_user_data(company_id,user_id,field_name){
    users[company_id][user_id][field_name]=$('#company_user_data_'+company_id+' [name='+field_name+']').val();
}

function save_user_data(company_id,user_id){
    var user_data=users[company_id][user_id];
    user_data['user_id']=user_data['id'];
    user_data['companys']=new Array();
    user_data['companys'][0]=company_id;
    api_query_array('/api/index.php',user_data,'save_user_data').then(function(data){
	if(data.status=="ok") {
	    $('#edit_user_'+user_id).html('');
	    show_company_users(company_id);
	}
    });

}

function show_company_cars(company_id){
 var send=new Array();
 var table='<br><button class="btn btn-primary" onclick="edit_company_car('+company_id+');">Добавить</button><div id="new_company_car"></div>';
 table+="<table class=\"table table-hover\"><thead><tr><th>Марка</th><th>Модель</th><th>Год выпуска</th><th>Гос. номер</th><th>VIN</th><th>Двигатель №</th><th></th></tr></thead><tbody>";
 send['company_id']=company_id;
 api_query_array("/api/index.php",send,"get_company_cars").then(function(data){
    var datalen=0;
    if(typeof(data.company_cars)!="undefined") datalen=data.company_cars.length;
    for (var i=0; i<datalen; i++){
    	table += "<tr><td>";
      table += "<div id='edit_company_car_"+data.company_cars[i].id+"'></div>";
      table += data.company_cars[i].auto_maker_name+"</td><td>" + data.company_cars[i].auto_model + "</td><td>"+data.company_cars[i].made_year+"</td>\
	  	<td><a onclick='open_catalog_by_plate(\""+data.company_cars[i].auto_gov_num+"\")'>"+data.company_cars[i].auto_gov_num+"</a></td>";
    	table += "<td><a onclick='open_catalog_by_vin(\""+data.company_cars[i].vin+"\")'>"+data.company_cars[i].vin+"</a> <a onclick='navigator.clipboard.writeText(\""+data.company_cars[i].vin+"\");'><img src='/new_images/copy.png' style='width:18px;' title='скопировать в буфер обмена'></a></td>\
		<td>"+data.company_cars[i].engine_num+"</td>";
    	table += "<td><form id='delete_car_"+data.company_cars[i].id+"'><input type=\"hidden\" name=\"company_car_id\" value=\""+data.company_cars[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
    	table += "<a onclick=\"edit_company_car("+company_id+","+data.company_cars[i].id+");\" title='Редактировать'><img src='/new_images/edit.svg' style='width:20px;'></a>";
    	table += " <a title='Удалить' ";
    	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить этот автомобиль?\',function(result){ if(result) api_query('/api/index.php','delete_car_"+data.company_cars[i].id+"','delete_company_car').then(function(data){if(data.status=='ok') show_company_cars("+company_id+")});});\"><img src='/new_images/garbage.svg' style='width:20px;'></a>";
    	table += "</div></td></tr>";
    }
    table+="</tbody></table>";
    $("#company_cars").html(table);
 });
}

function open_catalog_by_vin(vin){
	$('#laximo').removeAttr("vin");
	load_module(5).then(function(){
		var laximo_iframe=document.getElementById('laximo_iframe');
		laximo_iframe.src="/laximo/index.php?task=catalogs";
		laximo_iframe.onload = function(){
			if(typeof($('#laximo').attr("vin"))=="undefined"){
				$(this).contents().find('input[name=identString]').val(vin);
				$(this).contents().find('#vinFrameSubmit').click();
				
			}
			$('#laximo').attr("vin",vin);
		}
		//$('#laximo_iframe').contents().find('input[name=identString]').val(vin);
		//$('#laximo_iframe').contents().find('#vinFrameSubmit').click();
	})
}

function open_catalog_by_plate(plate){
	$('#laximo').removeAttr("plate");
	load_module(5).then(function(){
		var laximo_iframe=document.getElementById('laximo_iframe');
		laximo_iframe.src="/laximo/index.php?task=catalogs";
		
		laximo_iframe.onload = function(){
			if(typeof($('#laximo').attr("plate"))=="undefined"){
				$(this).contents().find('input[name=plate]').val(plate);
				$(this).contents().find('#plateSubmit').click();
				
			}
			$('#laximo').attr("plate",plate);
		}
		//$('#laximo_iframe').contents().find('input[name=plate]').val(plate);
		//$('#laximo_iframe').contents().find('#plateSubmit').click();
	})
}

function show_company_zakaz_details(company_id){
	var send=new Array();
	var table='';
	table += "<table class=\"table table-hover zakaz-details\">\
    <thead><tr><th></th><th>№</th><th>№ заказа</th><th>Дата</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th>";
    table+="<th>Цена</th><th>в заказе</th><th>выд</th><th>отк</th><th>возвр</th><th>Сумма</th><th>Срок доставки</th><th>Статус</th><th>Тип поставщика</th><th>Поставщик</th><th>Комментарий</th><th></th><th></th></tr></thead><tbody>";
	send['company_id']=company_id;
	api_query_array("/api/index.php",send,"get_client_zakaz_details").then(function(data){
		var datalen=data.zakaz_details.length;
		var zakaz_status=10000;
    	var checked_details=0;
 		var zakaz_sum=0;
		for (var i=0; i<datalen; i++){
			if(isNaN(parseInt($("#my_sklad").val())) || parseInt(data.zakaz_details[i].delivery_type_id)===parseInt($("#my_sklad").val()) || isNaN(parseInt(data.zakaz_details[i].delivery_type_id)) || parseInt(data.zakaz_details[i].delivery_type_id)===0){
			  zakaz_details[data.zakaz_details[i].id]=data.zakaz_details[i];
			  zakaz_details[data.zakaz_details[i].id]['document_id']=[];
			  for(let l in data.linked_documents[data.zakaz_details[i].id]){
				zakaz_details[data.zakaz_details[i].id]['document_id'].push(data.linked_documents[data.zakaz_details[i].id][l]);
			  }
			  zakaz_details[data.zakaz_details[i].id]['document_details_id']=[];
			  for(let l in data.linked_document_details[data.zakaz_details[i].id]){
				zakaz_details[data.zakaz_details[i].id]['document_details_id'].push(data.linked_document_details[data.zakaz_details[i].id][l]);
			  }
			  table += '<tr';
			  if((data.zakaz_details[i].status>=100 && data.zakaz_details[i].status<=199) ||  data.zakaz_details[i].status==14){
				table += ' class="deleted"';
			  }
	  
			  if(data.zakaz_details[i].status==35 ){
				table += ' class="submitted"';
			  }
			  table+=' ondblclick="show_detail_menu('+data.zakaz_details[i].id+')">';
			  table+='<td></td>';
			  
			  table += "<td><div id='edit_zakaz_detail_"+data.zakaz_details[i].detail_id+"'></div>"+(i+1)+"</td>";
			  table+="<td>"+data.zakaz_details[i].zakaz_id+"</td>";
			  table+="<td>"+convertTZ(data.zakaz_details[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
			  table+="<td><a onclick='reorder_detail(\"" + data.zakaz_details[i].article + "\"," + data.zakaz_details[i].detail_id + ",\"" + data.zakaz_details[i].brand + "\"," + data.zakaz_details[i].brand_id + ",0,0,0,0,0);' title='проценить товар'>" + data.zakaz_details[i].article + "</a></td>";
			  table += "<td>"+data.zakaz_details[i].brand+"</td><td>"+data.zakaz_details[i].name+"</td>";
			  if($('#show_zakaz_'+data.zakaz_details[i].zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")){
				table+='<td>'+data.zakaz_details[i].dealer_price+'</td>';
			  }
			  table += "<td class='centered'>"+data.zakaz_details[i].price+"</td>";
			  table += "<td class='centered'>"+data.zakaz_details[i].count+"</td>";
			  table += "<td>"+data.zakaz_details[i].supplied_count+"</td>";
			  table += "<td>"+data.zakaz_details[i].rejected_count+"</td>";
			  table += "<td>"+data.zakaz_details[i].returned_count+"</td>";
			  table += "<td id='zakaz_detail_sum_"+data.zakaz_details[i].id+"'>"+(data.zakaz_details[i].price*data.zakaz_details[i].count).toFixed(2)+"</td>";
			  table += "<td>"+data.zakaz_details[i].time+"</td>";
			  table += "<td>"+zakaz_detail_statuses_ind[data.zakaz_details[i].status];
			  table +="</td>";
				switch(data.zakaz_details[i].deliverer_type){
					case "1":table += "<td>Склад</td>"; break;
					case "2":table += "<td>Price</td>"; break;
					case "3":table += "<td>Онлайн</td>"; break;
				}
				table += '<td>';
			  if(parseInt(data.zakaz_details[i].deliverer_type)==3){
				//table+='<a href="https://'+data.zakaz_details[i].deliverer_id+'-cart.sort1.pro/';
				//if(data.zakaz_details[i].deliverer_id==2) table+='https://shate-m.ru/personal/cart';
				//if(data.zakaz_details[i].deliverer_id==48 || data.zakaz_details[i].deliverer_id==311) table+='https://www.part-kom.ru/cart/';
				//if(data.zakaz_details[i].deliverer_id==5) table+='http://adeo-pro.ru/n_basket.php';
				//if(data.zakaz_details[i].deliverer_id==11) table+='https://berg.ru/cart';
				//table+='" target="_blank" title="Перейти в корзину поставщика">';
			  }
			  if(typeof(data.deliverers[data.zakaz_details[i].deliverer_type])!="undefined") 
				table += data.deliverers[data.zakaz_details[i].deliverer_type][data.zakaz_details[i].deliverer_id];
			  if(parseInt(data.zakaz_details[i].deliverer_type)==3){
				//table+='</a>';
			  }
			  table += '</td>';
				table += "<td>"+data.zakaz_details[i].comment+"</td>";
				table += "<td><form id='delete_detail_"+data.zakaz_details[i].id+"'><input type=\"hidden\" name=\"id\" value=\""+data.zakaz_details[i].id+"\"><input type=\"hidden\" name=\"detail_id\" value=\""+data.zakaz_details[i].detail_id+"\"><input type=\"hidden\" name=\"zakaz_id\" value=\""+data.zakaz_details[i].zakaz_id+"\"></form>";
			  table += '<div class="btn-group" style="display: flex;">';
			  table += '<!--button class="glyphicon glyphicon-list btn btn-primary btn-xs" onclick="show_detail_menu('+data.zakaz_details[i].id+')"></button-->';
			  table += '<div id="zakaz_detail_menu_'+data.zakaz_details[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div>';
				if(data.zakaz_details[i].status<100 || data.zakaz_details[i].status>199 ){
				zakaz_sum+=(data.zakaz_details[i].price*data.zakaz_details[i].count);
				//table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
				  //table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь с заказа?\',function(result){ if(result) api_query('/api/index.php','delete_detail_"+data.zakaz_details[i].id+"','delete_zakaz_detail_by_manager').then(function(data){if(data.status=='ok') get_zakaz_details1('zakaz_form_"+data.zakaz_details[i].zakaz_id+"',1);});});\"></button>";
			  }
			  table += "</div>";
				table += "</td>";
			  table+='<td><span id="'+data.zakaz_details[i].md5+'"></span></td>';
			  table += "</tr>";
			  if(zakaz_status>parseInt(data.zakaz_details[i].status)) zakaz_status=parseInt(data.zakaz_details[i].status);
			}
		  }
		  table+='<tr><td colspan="20" align="right">';
			table+='</td></tr>';
		  table += "</tbody></table>";
	   $("#company_zakaz_details").html(table);
	});
   }

function edit_company_car(company_id,car_id){
  if(typeof(car_id)=="undefined"){
    print_company_car_form(company_id,0);
  }
  else {
    var send=new Array();
    send['company_car_id']=car_id;
    api_query_array("/api/index.php",send,"get_company_car").then(function(data){
      print_company_car_form(company_id,car_id,data);
    });
  }
}

function get_car_by_vin(){
	var send=[];
	send['vin']=$("#company_car_form input#vin").val();
	if(send['vin']=='') return;
	if(typeof(send['vin'])=="string" && send['vin'].length<17) return;
	api_query_array("/api/index.php",send,"get_car_by_vin").then(function(data){
		if(data.status=="ok" && typeof(data.car)!="undefined"){
			if(parseInt(data.car.auto_maker_id)>0)
				$("#auto_maker_select select[name=auto_maker_id]").val(data.car.auto_maker_id);
			$("#company_car_form input[name=auto_model]").val(data.car.auto_model);
			$("#company_car_form input[name=made_date]").val(data.car.made_date);
			$("#company_car_form input[name=made_year]").val(data.car.made_year);
			$("#company_car_form input[name=engine_num]").val(data.car.engine_num);
		}
	})
}

function get_car_by_plate(){
	var send=[];
	send['plateNumber']=$("#company_car_form input#auto_gov_num").val();
	api_query_array("/api/index.php",send,"get_car_by_plate").then(function(data){
		if(data.status=="ok" && typeof(data.car)!="undefined"){
			if(parseInt(data.car.auto_maker_id)>0)
				$("#auto_maker_select select[name=auto_maker_id]").val(data.car.auto_maker_id);
			if($("#company_car_form input[name=auto_model]").val()=="") $("#company_car_form input[name=auto_model]").val(data.car.auto_model);
			if($("#company_car_form input[name=made_date]").val()=="") $("#company_car_form input[name=made_date]").val(data.car.made_date);
			if($("#company_car_form input[name=made_year]").val()=="") $("#company_car_form input[name=made_year]").val(data.car.made_year);
			if($("#company_car_form input[name=engine_num]").val()=="") $("#company_car_form input[name=engine_num]").val(data.car.engine_num);
			if($("#company_car_form input[name=vin]").val()=="") $("#company_car_form input[name=vin]").val((typeof(data.car.vin)!="undefined" && data.car.vin!==null)?data.car.vin[0]:"");
		}
	})
}

function print_company_car_form(company_id,car_id,data){
  var isdata=1;
  if(typeof(data)=="undefined") isdata=0;
  var table='<form id="company_car_form">';
  table+='<div class="form-group row">\
   <label for="auto_maker" class="col-sm-5">Марка автомобиля:</label>\
   <div class="col-sm-7 pull-right">\
    <div id="auto_maker_select">';
    var car={};
    if(!isdata) {
      car.auto_maker_id=0;
    }
    else {
      car=data.company_car;
    }
    print_select_auto_makers(car);
      //  <input type="text" class="form-control" value="'+car.auto_maker_name+'" name="auto_maker_name">\
      //  <input type="hidden" name="auto_maker_id" value="'+car.auto_maker_id+'">\
  table+=' </div>\
  </div>\
  </div>\
  <div class="form-group row">\
   <label for="auto_model" class="col-sm-5">Модель автомобиля:</label>\
   <div class="col-sm-7 pull-right">\
		<input type="hidden" name="auto_motor_id" value="'+(isdata?data.company_car.auto_model_id:0)+'">\
        <input type="text" class="form-control search_str" id="auto_model" value="'; if(isdata) table+=data.company_car.auto_model; table+='" name="auto_model" onclick="get_auto_models();"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_model" id="auto_model_label" onclick="clear_search_order_text(\'auto_model\');"></label>\
   </div>\
  </div>\
  <div id="auto_model_select" style="z-index:11;"></div>\
  <div class="form-group row">\
   <label for="vin" class="col-sm-5">VIN:</label>\
   <div class="col-sm-7 pull-right">\
        <input type="text" class="form-control search_str" id="vin" value="'; if(isdata) table+=data.company_car.vin; table+='" name="vin" onchange="get_car_by_vin()"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="vin" id="vin_label" onclick="clear_search_order_text(\'vin\');"></label>\
   </div>\
  </div>\
  <div class="form-group row">\
   <label for="auto_gov_num" class="col-sm-5 col-form-label">Гос. номер авто:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="auto_gov_num" value="'; if(isdata) table+=data.company_car.auto_gov_num; table+='" name="auto_gov_num" onchange="get_car_by_plate()"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_gov_num" id="auto_gov_num_label" onclick="clear_search_order_text(\'auto_gov_num\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
   <label for="auto_doc_num" class="col-sm-5 col-form-label">Номер свид. о рег.:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="auto_doc_num" value="'; if(isdata) table+=data.company_car.auto_doc_num; table+='" name="auto_doc_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_doc_num" id="auto_doc_num_label" onclick="clear_search_order_text(\'auto_doc_num\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="engine_num" class="col-sm-5 col-form-label">Номер двиг.:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="engine_num" value="'; if(isdata) table+=data.company_car.engine_num; table+='" name="engine_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="engine_num" id="engine_num_label" onclick="clear_search_order_text(\'engine_num\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="made_year" class="col-sm-5 col-form-label">Дата выпуска:</label>\
    <div class="col-sm-7">\
        <input type="date" class="form-control search_str" id="made_date" value="'; if(isdata) table+=data.company_car.made_date; table+='" name="made_date">\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="made_year" class="col-sm-5 col-form-label">Год выпуска:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="made_year" value="'; if(isdata) table+=data.company_car.made_year; table+='" name="made_year"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="made_year" id="made_year_label" onclick="clear_search_order_text(\'made_year\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="probeg" class="col-sm-5 col-form-label">Пробег:</label>\
    <div class="col-sm-7">\
            <input type="text" class="form-control search_str" id="probeg" value="'; if(isdata) table+=data.company_car.probeg; table+='" name="probeg"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="probeg" id="probeg_label" onclick="clear_search_order_text(\'probeg\');"></label>\
    </div>\
  </div>\
  <input type="hidden" name="company_id" value="'+company_id+'">\
  <input type="hidden" name="company_car_id" value="'+car_id+'">\
  </form>\
  <button class="btn btn-primary" onclick="save_company_car();">Сохранить</button>\
  ';
  if(!isdata) create_window("new_company_car_div","Добавление автомобиля клиента","new_company_car",table);
  else create_window("edit_company_car_"+car_id+"_div","Редактирование автомобиля клиента","edit_company_car_"+car_id,table);
}

function save_company_car(){
  api_query("/api/index.php","company_car_form","save_company_car").then(function(data){
    if(data.status=="ok"){
      var company_id=$("#company_car_form input[name=company_id]").val();
      show_company_cars(company_id);
      $("div[id^=edit_company_car]").html('');
      $("div[id^=new_company_car]").html('');
    }
  })
}

function enable_api(user_id){
	var send=new Array();
	send['user_id']=user_id;
	api_query_array("/api/index.php",send,"enable_api").then(function(data){
		if(data.status=="ok"){
			$("#api_status").html('API доступ включен, ключ доступа: '+data.api_key+' <a onclick="disable_api('+user_id+');">выключить</a>');
		}
	});
}
function disable_api(user_id){
	var send=new Array();
	send['user_id']=user_id;
	api_query_array("/api/index.php",send,"disable_api").then(function(data){
		if(data.status=="ok"){
			$("#api_status").html('API доступ выключен <a onclick="disable_api('+user_id+');">включить</a>');
		}
	});
}

function load_clients_to_base(api,tab){
	$.blockUI({ css: { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .5, 
        color: '#fff'
        },
        message: 'Загружаю клиентов...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
      });
	var send=[];
	send['clients']=api;
	api_query_array("/api/index.php",send,"save_clients").then(function(data){
		$.unblockUI();
		get_clients();
	});
   }

function print_akt_sverki(company_id){
	var a=document.createElement('a');
	a.href='/akt_sverki.php?date_from='+$("#akt_sverki_date_from").val()+'&date_to='+$("#akt_sverki_date_to").val()+'&company_id='+company_id;
	a.target="_blank";
	a.click();
}

function get_akt_sverki(company_id){
	var send=[];
	send['company_id']=company_id;
	send['date_from']=$("#akt_sverki_date_from").val();
	send['date_to']=$("#akt_sverki_date_to").val();
	$.blockUI({ css: { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .5, 
        color: '#fff'
        },
        message: 'Загружаю акт сверки...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
      });
	api_query_array("/api/index.php",send,"get_akt_sverki").then(function(data){
		$.unblockUI();
		if(data.status=="ok"){
			var table="";
			table+='\
			<div class="input-group input-group-sm pull-right">\
				<span class="input-group-addon" id="akt_sverki_print_label" title="Распечатать акт сверки"><a onclick="print_akt_sverki('+company_id+');"><img src="/new_images/printer.svg" style="height:16px"></a></span>\
				<span class="input-group-addon" id="akt_sverki_print_label" title="Скачать акт сверки xlsx"><a class="pull-right" onclick="get_akt_sverki_xls('+company_id+');"><img src="/new_images/excel_32.png" style="width: 16px;"></a></span>\
				<span class="input-group-addon" id="akt_sverki_date_from_label">с:</span>\
				<input type="date" id="akt_sverki_date_from" class="form-control" aria-describedby="akt_sverki_date_from_label" value="'+data.date_from+'">\
				<span class="input-group-addon" id="akt_sverki_date_to_label">по:</span>\
				<input type="date" id="akt_sverki_date_to" class="form-control" aria-describedby="akt_sverki_date_to_label" value="'+data.date_to+'">\
				<div class="input-group-btn"><button class="btn btn-primary btn-md" onclick="get_akt_sverki('+company_id+');">Поиск</button></div>\
		  	</div>';
			table+='<table class="table table-hover">';
			table+='<thead><tr><th>№</th><th>Дата</th><th>Наименование операции, документа</th><th>№ Заказа</th><th>Дебет</th><th>Кредит</th></tr></thead>';
			table+='<tbody>';
			table+='<tr><td colspan="4"><b>Сальдо начальное</b></td><td><b>'+(parseFloat(data.start_saldo)<0?parseFloat(-data.start_saldo).toFixed(2):"")+'</b></td><td><b>'+(parseFloat(data.start_saldo)>0?parseFloat(data.start_saldo).toFixed(2):"")+'</b></td></tr>';
			var len=data.items.length,ksum=0,dsum=0;
			for(var i=0; i<len; i++){
				table+='<tr><td>'+(i+1)+'</td><td>'+((data.items[i].type==1)?convertTZ(data.items[i].data.document_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"):convertTZ(data.items[i].data.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"))+'</td><td>';
				if(data.items[i].type==1) {
					switch(data.items[i].data.type_id){
						case "1":	table+='Поступление ('+data.items[i].data.id+' от '+((data.items[i].type==1)?convertTZ(data.items[i].data.document_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"):convertTZ(data.items[i].data.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"))+')'; break;
						case "2":	table+='Продажа ('+data.items[i].data.id+' от '+((data.items[i].type==1)?convertTZ(data.items[i].data.document_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"):convertTZ(data.items[i].data.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"))+')'; break;
						case "6":	table+='Возврат клиента ('+data.items[i].data.id+' от '+((data.items[i].type==1)?convertTZ(data.items[i].data.document_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"):convertTZ(data.items[i].data.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"))+')'; break;
						case "7":	table+='Возврат поставщику ('+data.items[i].data.id+' от '+((data.items[i].type==1)?convertTZ(data.items[i].data.document_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"):convertTZ(data.items[i].data.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"))+')'; break;
					}
				}
				if(data.items[i].type==2) {
					switch(data.items[i].data.payment_direction){
						case "1": table+='Оплата клиента ('+data.items[i].data.id+' от '+convertTZ(data.items[i].data.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+')'; break;
						case "2": table+='Оплата поставщику ('+data.items[i].data.id+' от '+convertTZ(data.items[i].data.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+')'; break;
						case "3": table+='Возврат оплаты клиенту ('+data.items[i].data.id+' от '+convertTZ(data.items[i].data.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+')'; break;
						case "4": table+='Возврат оплаты клиенту ('+data.items[i].data.id+' от '+convertTZ(data.items[i].data.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+')'; break;
						case "5": table+='Возврат оплаты клиенту ('+data.items[i].data.id+' от '+convertTZ(data.items[i].data.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+')'; break;
					}
				}
				table+='</td><td>'+data.items[i].data.zakaz_id+'</td><td>';
				if(data.items[i].type==1) {
					
					//if(data.items[i].data.type_id=="6"){
					//	if(typeof(data.document_sums[data.items[i].data.id])!="undefined"){
							//table+=parseFloat(data.document_sums[data.items[i].data.id].document_summ).toFixed(2);
							//ksum+=parseFloat(data.document_sums[data.items[i].data.id].document_summ);
					//	}
					//}
					if(data.items[i].data.type_id=="2"){
						if(typeof(data.document_sums[data.items[i].data.id])!="undefined"){
							table+=parseFloat(data.document_sums[data.items[i].data.id].document_summ).toFixed(2);
							dsum+=parseFloat(data.document_sums[data.items[i].data.id].document_summ);
						}
					}
					if(data.items[i].data.type_id=="7"){
						if(typeof(data.document_sums[data.items[i].data.id])!="undefined"){
							table+=parseFloat(data.document_sums[data.items[i].data.id].document_dealer_summ).toFixed(2);
							dsum+=parseFloat(data.document_sums[data.items[i].data.id].document_dealer_summ);
						}
					}
				}
				if(data.items[i].type==2 && (data.items[i].data.payment_direction=="3" || data.items[i].data.payment_direction=="4" || data.items[i].data.payment_direction=="5"  || data.items[i].data.payment_direction=="2")) {
					table+=data.items[i].data.summ;
					dsum+=parseFloat(data.items[i].data.summ);
				}
				table+='</td><td>';
				if(data.items[i].type==2 && data.items[i].data.payment_direction=="1") {
					table+=parseFloat(data.items[i].data.summ).toFixed(2);
					ksum+=parseFloat(data.items[i].data.summ);
				}
				if(data.items[i].type==1) {
					if(data.items[i].data.type_id==1 || data.items[i].data.type_id==6){
						if(typeof(data.document_sums[data.items[i].data.id])!="undefined"){
							table+=parseFloat(data.document_sums[data.items[i].data.id].document_summ).toFixed(2);
							ksum+=parseFloat(data.document_sums[data.items[i].data.id].document_summ);
						}
					}
				}
				table+='</td></tr>';
			}
			table+='<tr><td colspan="4"><b>Обороты за период</b></td><td><b>'+dsum.toFixed(2)+'</b></td><td><b>'+ksum.toFixed(2)+'</b></td></tr>';
			table+='<tr><td colspan="4"><b>Сальдо конечное</b></td>\
			<td><b>'+((dsum-ksum-parseFloat(data.start_saldo))>0?(dsum-ksum-parseFloat(data.start_saldo)).toFixed(2):"")+'</b></td>\
			<td><b>'+((dsum-ksum-parseFloat(data.start_saldo))<0?-(dsum-ksum-parseFloat(data.start_saldo)).toFixed(2):"")+'</b></tr>';
			table+='</tr><td colspan="6">';
			if(new Date(data.date_to+" 23:59:59")>=new Date()) table+='<button type="button" onclick="fix_company_balance('+company_id+','+(-(dsum-ksum-parseFloat(data.start_saldo))).toFixed(2)+');">Исправить баланс</button>';
			table+='</td></tr>';
			table+='</tbody></table>';
			document.getElementById("company_akt_sverki").innerHTML=table;
		}
	});
}

function fix_company_balance(company_id,balance){
	var send=[];
	send['company_id']=company_id;
	send['balance']=balance;
	api_query_array("/api/index.php",send,"update_company_balance").then(function(data){

	});
}

function get_clients_xls(){
	$.blockUI({ css: { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .5, 
        color: '#fff'
        },
        message: 'Загружаю клиентов...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
      });
	api_query("/api/index.php","some_form","get_clients_xls").then(function(data){
	//api_query_array("/api/index.php",send,"get_documents_xls").then(function(data){
	  //alert(data.export_file);
	  $.unblockUI();
	  var blob = b64toBlob(data.file); //new Blob([data.file]);
	  var link = document.createElement('a');
	  link.href = window.URL.createObjectURL(blob);
	  link.download = "clients.xlsx";
	  link.click();
	});
  }

function get_akt_sverki_xls(company_id){
var send=new Array();
send['date_from']=$("#akt_sverki_date_from").val();
send['date_to']=$("#akt_sverki_date_to").val();
send['company_id']=company_id;
api_query_array("/api/index.php",send,"get_akt_sverki_xls").then(function(data){
//api_query_array("/api/index.php",send,"get_documents_xls").then(function(data){
	//alert(data.export_file);
	var blob = b64toBlob(data.file); //new Blob([data.file]);
	var link = document.createElement('a');
	link.href = window.URL.createObjectURL(blob);
	link.download = "upd.xlsx";
	link.click();
});
}