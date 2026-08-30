var loaded_payments_in;
var loaded_payments_out;
var paymentes,payment_types;
var delivery_paymentes,delivery_payment_types;

function get_payments(){
    var send=new Array();
    $("body").css("cursor","progress");
    $("li a").css("cursor","progress");
    if($("#payments_in_filter_date_from").val()!="") send['from_date']=$("#payments_in_filter_date_from").val();
	  if($("#payments_in_filter_date_to").val()!="") send['to_date']=$("#payments_in_filter_date_to").val();
	  if($("#payments_in_filter_client").val()!="") send['client']=$("#payments_in_filter_client").val();
    api_query_array("/api/index.php",send,"get_payments").then(function(data){
    	var len=data.payments.length;
      paymentes=data.payments;
      payment_types=data.payment_types;
    	var table='';
      var sum=0.0;
    /*	table+='<table class="table table-hover">';
    	table+='<thead><tr><th>№ плат. пор.</th><th>Назначение платежа</th><th>тип платежа</th><th>клиент</th><th>№ заказа</th><th>сумма</th><th>Дата платежа</th><th>ИНН плательщика</th><th>Кассир</th><th></th></tr></thead>';
    	for(var i=0; i<len; i++){
    	    table+='<tr><td><div id="payment_'+data.payments[i].id+'"></div>'+data.payments[i].payment_num+'</td><td>'+data.payments[i].payment_target+'</td><td>'+data.payment_types[data.payments[i].payment_type]+'</td><td>'+data.payments[i].company_name+'</td><td>'+data.payments[i].zakaz_id+'</td><td style="text-align: right;">'+parseFloat(data.payments[i].summ).toFixed(2)+'</td><td>'+data.payments[i].create_date+'</td><td>'+data.payments[i].from_inn+'</td>';
    	    table+='<td>';
			if(data.payments[i].lastname!==null) table+=data.payments[i].lastname;
			if(data.payments[i].name!==null) table+=data.payments[i].name;
			if(data.payments[i].middlename!==null) table+=data.payments[i].middlename;
			table+='</td>';
			table+='<td>';
    	    table+='<a onclick="edit_payment('+data.payments[i].id+');"><img src="/new_images/edit.svg" style="width:20px;"></a>';
    	    table+='</td></tr>';
          sum+=parseFloat(data.payments[i].summ);
    	}
      	table+='<tr><td colspan="4"></td><td>Итого</td><td>'+sum.toFixed(2)+'</td><td colspan="4"></td></tr>';
    	table+='</table>';
    	$("#payments_list").html(table);*/
		  print_paymentes();
      $("body").css("cursor","default");
      $("li a").css("cursor","pointer");
    });

}

function get_return_payments(){
    var send=new Array();
    if($("#payments_return_filter_date_from").val()!="") send['from_date']=$("#payments_return_filter_date_from").val();
	if($("#payments_return_filter_date_to").val()!="") send['to_date']=$("#payments_return_filter_date_to").val();
	if($("#payments_return_filter_client").val()!="") send['client']=$("#payments_return_filter_client").val();
    api_query_array("/api/index.php",send,"get_return_payments").then(function(data){
    	var len=data.payments.length;
    	var table='';
      var sum=0.0;
    	table+='<table class="table table-hover">';
    	table+='<thead><tr><th>№</th><th>Дата платежа</th><th>№ плат. пор.</th><th>Назначение платежа</th><th>тип платежа</th><th>клиент</th><th>№ заказа</th><th>сумма</th><th>ИНН плательщика</th><th>Фиск.</th><th></th></tr></thead>';
    	for(var i=0; i<len; i++){
    	    table+='<tr><td>'+data.payments[i].id+'</td><td>'+convertTZ(data.payments[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td><div id="payment_'+data.payments[i].id+'"></div>'+data.payments[i].payment_num+'</td><td>'+data.payments[i].payment_target+'</td><td>'+data.payment_types[data.payments[i].payment_type]+'</td><td>'+data.payments[i].company_name+'</td><td>'+data.payments[i].zakaz_id+'</td><td style="text-align: right;">'+parseFloat(data.payments[i].summ).toFixed(2)+'</td><td>'+data.payments[i].from_inn+'</td>';
          if(data.payments[i].dont_fiscalize=="0"){
            if(parseInt(data.payments[i].fiscalized)==0 && parseInt(data.payments[i].fiscalized_excise)==0) table+='<td><button class="btn btn-xs btn-default" onclick="fiscalize_payment('+data.payments[i].id+',\'return\')">Фиск.</button></td>';
            else table+='<td style="text-align: center;"><img src="/images/ok.svg" width="25px;"></td>';
          }
          else {
            table+='<td></td>';
          }
          table+='<td nowrap>';
    	    table+='<a onclick="edit_payment('+data.payments[i].id+');"><img src="/new_images/edit.svg" class="menuimg"></a>\
          <a onclick="delete_payment('+data.payments[i].id+',\'r\');"><img src="/new_images/garbage.svg" class="menuimg"></a>';
    	    table+='</td></tr>';
          sum+=parseFloat(data.payments[i].summ);
    	}
      table+='<tr><td colspan="6"></td><td><b>Итого</b></td><td><b>'+sum.toFixed(2)+'</b></td><td colspan="3"></td></tr>';
    	table+='</table>';
    	$("#return_payments_list").html(table);
    });

}
 
function get_delivery_payments(){
    var send=new Array();
    if($("#payments_out_filter_date_from").val()!="") send['from_date']=$("#payments_out_filter_date_from").val();
	if($("#payments_out_filter_date_to").val()!="") send['to_date']=$("#payments_out_filter_date_to").val();
	if($("#payments_out_filter_client").val()!="") send['client']=$("#payments_out_filter_client").val();
    api_query_array("/api/index.php",send,"get_delivery_payments").then(function(data){
    	var len=data.payments.length;
      delivery_paymentes=data.payments;
      delivery_payment_types=data.payment_types;
    	var table='';
      var sum=0.0;
    	/*table+='<table class="table table-hover">';
    	table+='<thead><tr><th>№</th><th>Дата платежа</th><th>№ плат. пор.</th><th>Назначение платежа</th><th>тип платежа</th><th>Поставщик</th><th>№ заказа</th><th>сумма</th><th>ИНН поставщика</th><th>КПП поставщика</th><th></th></tr></thead>';
    	for(var i=0; i<len; i++){
    	    table+='<tr><td>'+data.payments[i].id+'</td>\
          <td>'+convertTZ(data.payments[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>\
          <td><div id="payment_'+data.payments[i].id+'"></div>'+data.payments[i].payment_num+'</td>\
          <td>'+data.payments[i].payment_target+'</td>\
          <td>'+data.payment_types[data.payments[i].payment_type]+'</td>\
          <td>'+data.payments[i].company_name+' '+data.payments[i].company_inn+'/'+data.payments[i].company_kpp+'</td>\
          <td>'+data.payments[i].zakaz_id+'</td>\
          <td style="text-align: right;">'+parseFloat(data.payments[i].summ).toFixed(2)+'</td>\
          <td>'+data.payments[i].to_inn+'</td>\
          <td>'+data.payments[i].to_kpp+'</td>';
    	    table+='<td nowrap>';
    	    table+='<a onclick="edit_payment('+data.payments[i].id+');"><img src="/new_images/edit.svg" class="menuimg"></a>';
          table+=' <a onclick="delete_payment('+data.payments[i].id+',\'d\');"><img src="/new_images/garbage.svg" class="menuimg"></a>';
    	    table+='</td></tr>';
          sum+=parseFloat(data.payments[i].summ);
    	}
      table+='<tr><td colspan="7"><b>Итого</b></td><td><b>'+sum.toFixed(2)+'</b></td><td colspan="3"></td></tr>';
    	table+='</table>';
    	$("#delivery_payments_list").html(table);*/
      print_delivery_paymentes();
    });

}

function get_payment_clients(payment_id=0){
	var search_payment_client=$("#payment_form_"+payment_id+" input#company_name").val();
	if(typeof(search_payment_client)=="undefined") search_payment_client="";
	var send=new Array();
	send['search_clients_client_name']=search_payment_client;
	api_query_array("/api/index.php",send,"get_clients").then(function(data){
		var datalen=data.clients.length;
		var table="";
		//table+="<div><input placeholder='фильтр по клиенту' type='text' class='form-control' id='search_payment_client' value='"+search_payment_client+"' onchange='get_payment_clients("+payment_id+")'></div>";
		table+="<div style='max-height:400px; overflow: auto;'><table class='table table-hover'><thead><tr><th>Наименование</th><th>ИНН</th></tr></thead><tbody>";
		for (var i=0; i<datalen; i++){
		table += '<tr onclick="change_payment_client('+data.clients[i].id+',\''+data.clients[i].name.replace(/\"/g,"")+'\','+data.clients[i].inn+','+data.clients[i].kpp+','+payment_id+');"><td>'+data.clients[i].name+'</td><td>'+data.clients[i].inn+'/'+data.clients[i].kpp+'</td></tr>';
		}
		table += "</tbody></table></div>";
		create_window("payment_"+payment_id+"_clients_div","Выберите клиента","payment_"+payment_id+"_clients",table);
		//$("#payment_"+payment_id+"_clients_div").css("top","180px");
		//$("#payment_"+payment_id+"_clients_div").css("left","150px");
	});
}

function get_return_payment_clients(payment_id=0){
	var search_payment_client=$("#return_payment_form_"+payment_id+" input#return_company_name").val();
	if(typeof(search_payment_client)=="undefined") search_payment_client="";
	var send=new Array();
	send['search_clients_client_name']=search_payment_client;
	api_query_array("/api/index.php",send,"get_clients").then(function(data){
	   var datalen=data.clients.length;
	   var table="<div style='max-height:400px; overflow: auto;'><table class='table table-hover'>";
	   for (var i=0; i<datalen; i++){
	   table += '<tr onclick="change_return_payment_client('+data.clients[i].id+',\''+data.clients[i].name.replace(/\"/g,"")+'\','+data.clients[i].inn+','+data.clients[i].kpp+','+payment_id+');"><td>'+data.clients[i].name+'</td><td>'+data.clients[i].inn+'/'+data.clients[i].kpp+'</td></tr>';
	   }
	   table += "</table></div>";
	   create_window("return_payment_"+payment_id+"_clients_div","Выберите клиента","return_payment_"+payment_id+"_clients",table);
	   //$("#return_payment_"+payment_id+"_clients_div").css("top","180px");
	   //$("#return_payment_"+payment_id+"_clients_div").css("left","100px");
	});
   }

function get_payment_dealers(payment_id=0){
	var search_payment_dealer=$("#dealer_payment_form_"+payment_id+" input#company_name").val();
	if(typeof(search_payment_dealer)=="undefined") search_payment_dealer="";
	var send=new Array();
	send['search_clients_dealer_name']=search_payment_dealer;
	api_query_array("/api/index.php",send,"get_dealers").then(function(data){
	   var datalen=data.dealers.length;
	   var table="<div style='max-height:400px; overflow: auto;'><table class='table table-hover'>";
	   for (var i=0; i<datalen; i++){
	   table += '<tr onclick="change_payment_dealer('+data.dealers[i].id+',\''+data.dealers[i].name.replace(/\"/g,"")+'\','+data.dealers[i].inn+','+data.dealers[i].kpp+','+payment_id+');"><td>'+data.dealers[i].name+'</td><td>'+data.dealers[i].inn+'/'+data.dealers[i].kpp+'</td></tr>';
	   }
	   table += "</table></div>";
	   create_window("payment_"+payment_id+"_dealers_div","Выберите поставщика","payment_"+payment_id+"_dealers",table);
	   //$("#payment_"+payment_id+"_dealers_div").css("top","195px");
	   //$("#payment_"+payment_id+"_dealers_div").css("left","135px");
	});
   }

function get_payment_company_rekvizits(payment_id){
	if(typeof(payment_id)=="undefined") payment_id=0;
	var send=new Array();
	var payment_type="";
	send['company_id']=$("#payment_form_"+payment_id+" [id=company_id]").val();
	if(typeof(send['company_id'])=="undefined" || send['company_id']=="") {
		send['company_id']=$("#dealer_payment_form_"+payment_id+" [id=company_id]").val();
		payment_type="dealer_";
	}
	api_query_array("/api/index.php",send,"get_company_rekvizits").then(function(data){
		var datalen=data.rekvizits.length;
		var table='<table class="table table-hover">';
		table+='<thead><th>Р/C</th><th>К/С</th><th>Банк</th></thead><tbody>';
		if(datalen==1) {
			change_payment_company_rekvizit(data.rekvizits[0].id,data.rekvizits[0].rs,payment_id,payment_type);
			return 1;
		}
		else {
			for (var i=0; i<datalen; i++){
				table += '<tr onclick="change_payment_company_rekvizit('+data.rekvizits[i].id+',\''+data.rekvizits[i].bank+' '+data.rekvizits[i].rs+'\','+payment_id+',\''+payment_type+'\');"><td>'+data.rekvizits[i].rs+'</td><td>'+data.rekvizits[i].ks+'</td><td>'+data.rekvizits[i].bank+'</td></tr>';
			}
		}
		table += "</tbody></table>";
		create_window("payment_"+payment_id+"_company_rekvizits_div","Выберите расчетный счет","payment_"+payment_id+"_company_rekvizits",table);
	});
}

function change_payment_client(id,name,from_inn,from_kpp,payment_id=0){
    $("#payment_form_"+payment_id+" [id=company_id]").val(id);
    $("#payment_form_"+payment_id+" [id=company_name]").val(name);
    $("#payment_form_"+payment_id+" [id=from_inn]").val(from_inn);
    $("#payment_form_"+payment_id+" [id=from_kpp]").val(from_kpp);
    $("#payment_"+payment_id+"_clients").html('');
}

function change_return_payment_client(id,name,from_inn,from_kpp,payment_id=0){
    $("#return_payment_form_"+payment_id+" [id=return_company_id]").val(id);
    $("#return_payment_form_"+payment_id+" [id=return_company_name]").val(name);
    //$("#payment_form_"+payment_id+" [id=from_inn]").val(from_inn);
    //$("#payment_form_"+payment_id+" [id=from_kpp]").val(from_kpp);
    $("#return_payment_"+payment_id+"_clients").html('');
}

function change_payment_dealer(id,name,from_inn,from_kpp,payment_id=0){
    $("#dealer_payment_form_"+payment_id+" [id=company_id]").val(id);
    $("#dealer_payment_form_"+payment_id+" [id=company_name]").val(name);
    $("#dealer_payment_form_"+payment_id+" [id=from_inn]").val(from_inn);
    $("#dealer_payment_form_"+payment_id+" [id=from_kpp]").val(from_kpp);
    $("#dealer_payment_form_"+payment_id+" div#payment_"+payment_id+"_dealers").html('');
}

function change_payment_company_rekvizit(id,name,payment_id,payment_type){
    if(name==""){
      send=new Array();
      send['id']=id;
      api_query_array("/api/index.php",send,"get_company_rekvizit").then(function(data){
        $("#"+payment_type+"payment_form_"+payment_id+" [id=company_rekvizit_id]").val(id);
        $("#"+payment_type+"payment_form_"+payment_id+" [id=company_rekvizit_name]").val(data.rekvizits.bank+" "+data.rekvizits.rs);
        $("#payment_"+payment_id+"_company_rekvizits").html('');
      });
    }
    else {
      $("#"+payment_type+"payment_form_"+payment_id+" [id=company_rekvizit_id]").val(id);
      $("#"+payment_type+"payment_form_"+payment_id+" [id=company_rekvizit_name]").val(name);
      $("#payment_"+payment_id+"_company_rekvizits").html('');
    }
}

function get_payment_zakazes(payment_id=0){
    if($("#payment_form_"+payment_id+" [id=company_id]").val()=="") {
	bootbox.alert("Сначала выберите клиента");
    }
    else {
    	send=new Array();
    	send['company_id']=$("#payment_form_"+payment_id+" [id=company_id]").val();
    	api_query_array("/api/index.php",send,"get_zakazes").then(function(data){
    	    var datalen=data.zakazs.length;
    	    var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Дата заказа</th><th>Покупатель</th><th>Статус</th><th>Адрес доставки</th><th>Позиций в заказе</th><th>Сумма заказа</th><th>Коммент.</th><th></th></tr></thead><tbody>";
    	    for (var i=0; i<datalen; i++){
				if(parseInt(data.zakazs[i].oplachen)==0 && parseInt(data.zakazs[i].status)<70){
					table += "<tr onclick='change_payment_zakaz("+data.zakazs[i].id+","+payment_id+","+data.zakazs[i].zakaz_sum+");'><td>"+data.zakazs[i].id+"</td>";
					table += "<td>"+convertTZ(data.zakazs[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
					table += "<td>" + data.zakazs[i].company_name + "</td><td>"+data.zakazs[i].status+"</td><td>"+data.zakazs[i].delivery_address+"</td><td>"+data.zakazs[i].pozition_count+"</td><td>"+data.zakazs[i].zakaz_sum+"</td>";
					table += "<td>"+data.zakazs[i].comment+"</td>";
					table += "</tr>";
				}
    	    }
    	    table+= "</tbody></table>";
    	    create_window("payment_"+payment_id+"_zakazs_div","Выберите заказ клиента","payment_"+payment_id+"_zakazs",table);
    	});
    }
}

function change_payment_zakaz(id,payment_id=0,zakaz_sum){
    $("#payment_form_"+payment_id+" [id=zakaz_id]").val(id);
    $("#payment_form_"+payment_id+" [id=zakaz_name]").val(id);
    $("#payment_form_"+payment_id+" [id=summ]").val(zakaz_sum);
    $("#payment_"+payment_id+"_zakazs").html('');
}

function get_return_payment_zakazes(payment_id=0){
    if($("#return_payment_form_"+payment_id+" [id=return_company_id]").val()=="") {
	bootbox.alert("Сначала выберите клиента");
    }
    else {
    	send=new Array();
    	send['company_id']=$("#return_payment_form_"+payment_id+" [id=return_company_id]").val();
    	api_query_array("/api/index.php",send,"get_zakazes").then(function(data){
    	    var datalen=data.zakazs.length;
    	    var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Дата заказа</th><th>Покупатель</th><th>Статус</th><th>Адрес доставки</th><th>Позиций в заказе</th><th>Сумма заказа</th><th>Коммент.</th><th></th></tr></thead><tbody>";
    	    for (var i=0; i<datalen; i++){
				if(parseInt(data.zakazs[i].oplachen)==1 && parseInt(data.zakazs[i].status)==70){
					table += "<tr onclick='change_return_payment_zakaz("+data.zakazs[i].id+","+payment_id+","+data.zakazs[i].zakaz_sum+");'><td>"+data.zakazs[i].id+"</td>";
					table += "<td>"+convertTZ(data.zakazs[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
					table += "<td>" + data.zakazs[i].company_name + "</td><td>"+data.zakazs[i].status+"</td><td>"+data.zakazs[i].delivery_address+"</td><td>"+data.zakazs[i].pozition_count+"</td><td>"+data.zakazs[i].zakaz_sum+"</td>";
					table += "<td>"+data.zakazs[i].comment+"</td>";
					table += "</tr>";
				}
    	    }
    	    table+= "</tbody></table>";
    	    create_window("return_payment_"+payment_id+"_zakazs_div","Выберите заказ клиента","return_payment_"+payment_id+"_zakazs",table);
    	});
    }
}

function change_return_payment_zakaz(id,payment_id=0,zakaz_sum){
    $("#return_payment_form_"+payment_id+" [id=return_zakaz_id]").val(id);
    $("#return_payment_form_"+payment_id+" [id=return_zakaz_name]").val(id);
    $("#return_payment_form_"+payment_id+" [id=return_summ]").val(zakaz_sum);
    $("#return_payment_"+payment_id+"_zakazs").html('');
}

function clear_search_order_text(input_id){
	$('#'+input_id).val('');
	//runTextFilterOrd();
		  }

function new_payment(payment_direction=1){
    var table='';
    var date = new Date();

    var day = date.getDate(),
        month = date.getMonth() + 1,
        year = date.getFullYear(),
        hour = date.getHours(),
        min  = date.getMinutes(),
        sec = date.getSeconds();

    month = (month < 10 ? "0" : "") + month;
    day = (day < 10 ? "0" : "") + day;
    hour = (hour < 10 ? "0" : "") + hour;
    min = (min < 10 ? "0" : "") + min;
    sec = (sec < 10 ? "0" : "") + sec;

    var today = year + "-" + month + "-" + day + "T" + hour + ":" + min + ":" + sec; 
    table+='\
	<form id="payment_form_0">\
	<div class="form-group row">\
	    <label for="payment_type" class="col-sm-3 col-form-label">тип платежного поручения</label>\
	    <div class="col-sm-9">\
		<select class="form-control" type="text" id="payment_type" name="payment_type">\
			<option value="1">Наличными в офисе</option>\
			<option value="2">Оплата банковской картой VISA,MASTER,MIR</option>\
		    <option value="3">Наличными курьеру при доставке</option>\
		    <option value="4">Безналичная оплата</option>\
        <option value="6">Оплата по QR коду (СБП)</option>\
        <option value="7">Оплата картой, перевод</option>\
		</select>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="payment_num" class="col-sm-3 col-form-label">№ платежного поручения</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="payment_num" name="payment_num" placeholder="Введите номер платежного поручения"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="payment_num" id="payment_num_label" onclick="clear_search_order_text(\'payment_num\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="client_id" class="col-sm-3 col-form-label">Клиент</label>\
	    <div class="col-sm-9">\
		<input type="hidden" name="company_id" id="company_id"><input type="hidden" name="payment_direction" value="'+payment_direction+'">\
		<input class="form-control search_str" type="text" id="company_name" name="company_name" placeholder="Выберите клиента" onkeyup="get_payment_clients();" onclick="this.value=\'\'; get_payment_clients();" autocomplete="off"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_name" id="company_name_label" onclick="clear_search_order_text(\'company_name\');"></label>\
	    <div id="payment_0_clients"></div>\
		</div>\
	</div>\
	<div class="form-group row">\
	    <label for="zakaz_id" class="col-sm-3 col-form-label">№ заказа</label>\
	    <div class="col-sm-9">\
		    <input type="hidden" name="zakaz_id" id="zakaz_id"></input>\
		    <input class="form-control search_str" type="text" id="zakaz_name" name="zakaz_name" placeholder="Выберите номер заказа" onclick="get_payment_zakazes();"></input>\
        <label style="position: absolute; top: 0.8em; right: 1.2em;" for="zakaz_name" id="zakaz_name_label" onclick="clear_search_order_text(\'zakaz_name\');"></label>\
	    </div>\
	</div>\
  <div class="form-group row">\
	    <label for="zakaz_id" class="col-sm-3 col-form-label">Дата платежа</label>\
	    <div class="col-sm-9">\
		    <input class="form-control" type="datetime-local" id="new_payment_create_date" name="create_date" value='+today+'>\
	    </div>\
	</div>\
  <div id="payment_0_zakazs"></div>\
	<div class="form-group row">\
	    <label for="company_rekvizit_id" class="col-sm-3 col-form-label">Расчетный счет</label>\
	    <div class="col-sm-9">\
		<div id="payment_0_company_rekvizits"></div>\
		<input type="hidden" name="company_rekvizit_id" id="company_rekvizit_id">\
		<input class="form-control search_str" type="text" id="company_rekvizit_name" name="company_rekvizit_name" placeholder="Выберите расчетный счет" onclick="get_payment_company_rekvizits();">\
    <label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_rekvizit_name" id="company_rekvizit_name_label" onclick="clear_search_order_text(\'company_rekvizit_name\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="payment_target" class="col-sm-3 col-form-label">Назначение платежа</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="payment_target" name="payment_target" placeholder="Назначение платежа"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="payment_target" id="payment_target_label" onclick="clear_search_order_text(\'payment_target\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="summ" class="col-sm-3 col-form-label">Сумма</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="summ" name="summ" placeholder="Сумма платежа"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="summ" id="summ_label" onclick="clear_search_order_text(\'summ\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="from_inn" class="col-sm-3 col-form-label">ИНН плательщика</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="from_inn" name="from_inn" placeholder=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="from_inn" id="from_inn_label" onclick="clear_search_order_text(\'from_inn\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="from_kpp" class="col-sm-3 col-form-label">КПП плательщика</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="from_kpp" name="from_kpp" placeholder=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="from_kpp" id="from_kpp_label" onclick="clear_search_order_text(\'from_kpp\');"></label>\
	    </div>\
	</div>';
  table+='<div class="form-group row">\
	    <label for="is_advance" class="col-sm-3 col-form-label">Аванс</label>\
	    <div class="col-sm-9">\
		  <input class="" type="checkbox" id="is_advance" name="is_advance">\
	    </div>\
	</div>';
  table+='<div class="form-group row">\
    <label for="dont_fiscalize" class="col-sm-3 col-form-label">Не фискализировать</label>\
    <div class="col-sm-9">\
    <input class="" type="checkbox" id="dont_fiscalize" name="dont_fiscalize">\
    </div>\
  </div>';
  table+='<div class="form-group row">\
    <label for="dont_fiscalize" class="col-sm-3 col-form-label">Не печатать чек</label>\
    <div class="col-sm-9">\
    <input class="" type="checkbox" id="dont_print_check" name="dont_print_check">\
    </div>\
  </div>';
	table+='</form>';
    table+='<button class="btn btn-primary" onclick="save_payment(\'payment_form_0\');">Сохранить</button>\
		<button class="btn btn-secondary pull-right" onclick="$(\'#payment_0\').html(\'\');">Закрыть</button>\
    ';
    create_window_centered_blue("payment_0_div","Создание платежа","payment_0",table);
}

function new_return_payment(){
    var table='';
    table+='\
	<form id="return_payment_form_0">\
	<div class="form-group row">\
	    <label for="return_payment_type" class="col-sm-3 col-form-label">тип возврата</label>\
	    <div class="col-sm-9">\
		<select class="form-control" type="text" id="return_payment_type" name="payment_type">\
			<option value="1">Наличными в офисе</option>\
      <option value="2">Оплата банковской картой VISA,MASTER,MIR</option>\
      <option value="7">Картой, перевод</option>\
		</select>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="client_id" class="col-sm-3 col-form-label">Клиент</label>\
	    <div class="col-sm-9">\
		<input type="hidden" name="company_id" id="return_company_id"><input type="hidden" name="payment_direction" value="3">\
		<input class="form-control" type="text" id="return_company_name" name="company_name" placeholder="Выберите клиента" onkeyup="get_return_payment_clients();" onclick="get_return_payment_clients();" autocomplete="off">\
	    <div id="return_payment_0_clients"></div>\
		</div>\
	</div>';
/*	<div class="form-group row">\
	    <label for="return_zakaz_id" class="col-sm-3 col-form-label">№ заказа</label>\
	    <div class="col-sm-9">\
		<div id="return_payment_0_zakazs"></div>\
		<input type="hidden" name="zakaz_id" id="return_zakaz_id">\
		<input class="form-control search_str" type="text" id="return_zakaz_name" name="zakaz_name" placeholder="Выберите номер заказа" onclick="get_return_payment_zakazes();"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="return_zakaz_name" id="return_zakaz_name_label" onclick="clear_search_order_text(\'return_zakaz_name\');"></label>\
	    </div>\
	</div>\ */
	table+='<div class="form-group row">\
	    <label for="return_payment_target" class="col-sm-3 col-form-label">Назначение платежа</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="return_payment_target" name="payment_target" placeholder="Назначение платежа"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="return_payment_target" id="return_payment_target_label" onclick="clear_search_order_text(\'return_payment_target\');"></label>\
	    </div>\
	</div>\
  <div class="form-group row">\
	    <label for="return_zakaz_id" class="col-sm-3 col-form-label">№ заказа</label>\
	    <div class="col-sm-9">\
		    <input class="form-control search_str" type="text" id="return_zakaz_id" name="zakaz_id" placeholder="№ заказа"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="return_zakaz_id" id="return_zakaz_id_label" onclick="clear_search_order_text(\'return_zakaz_id\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="return_summ" class="col-sm-3 col-form-label">Сумма</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="return_summ" name="summ" placeholder="Сумма платежа"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="return_summ" id="return_summ_label" onclick="clear_search_order_text(\'return_summ\');"></label>\
	    </div>\
	</div>\
  <div class="form-group row">\
	    <label for="is_advance" class="col-sm-3 col-form-label">Аванс</label>\
	    <div class="col-sm-9">\
		  <input class="" type="checkbox" id="is_advance" name="is_advance">\
	    </div>\
	</div>\
	</form>\
    ';
    table+='<button class="btn btn-primary" onclick="save_payment(\'return_payment_form_0\',0,\'return\');">Сохранить</button>\
		<button class="btn btn-secondary pull-right" onclick="$(\'#return_payment_0\').html(\'\');">Закрыть</button>\
    ';
    create_window_centered_blue("return_payment_0_div","Создание возрата денежных средств","return_payment_0",table);
}

function select_cashdesk(payment_direction){
  if($("#"+payment_direction+"_payment_form_0 input#is_payment_cashdesk").prop('checked')){
    api_query("/api/index.php","some_form","get_cash_desks").then(function(data){
      var html='<select name="payment_cashdesk_id" class="form-control">';
      for(var i in data.cash_desks){
        html+='<option value="'+data.cash_desks[i].id+'">'+data.cash_desks[i].name+'</option>';
      }
      html+='</select>';
      $("#select_payment_cashdesk").html(html);
    });
  }
  else {
    $("#select_payment_cashdesk").html('');
  }
}

function change_dealer_payment_cashdesk_select(){
  if($("#dealer_payment_form_0 select#payment_type").val()=="1" || $("#dealer_payment_form_0 select#payment_type").val()=="3"){
    $("#payment_cashdesk_checkbox").css("display","block");
  }
  else {
    $("#payment_cashdesk_checkbox").css("display","none");
  }
}

function new_payment_to_dealer(){
  var date = new Date();

    var day = date.getDate(),
        month = date.getMonth() + 1,
        year = date.getFullYear(),
        hour = date.getHours(),
        min  = date.getMinutes(),
        sec = date.getSeconds();

    month = (month < 10 ? "0" : "") + month;
    day = (day < 10 ? "0" : "") + day;
    hour = (hour < 10 ? "0" : "") + hour;
    min = (min < 10 ? "0" : "") + min;
    sec = (sec < 10 ? "0" : "") + sec;

    var today = year + "-" + month + "-" + day + "T" + hour + ":" + min + ":" + sec; 
    var table='';
    table+='\
	<form id="dealer_payment_form_0">\
	<div class="form-group row">\
	    <label for="payment_type" class="col-sm-3 col-form-label">тип платежного поручения</label>\
	    <div class="col-sm-9">\
		<select class="form-control" type="text" id="payment_type" name="payment_type" onchange="change_dealer_payment_cashdesk_select();">\
		    <option value="1">Наличными в офисе</option>\
		    <option value="3">Наличными курьеру при доставке</option>\
		    <option value="4">Безналичная оплата</option>\
        <option value="6">Оплата по QR коду (СБП)</option>\
        <option value="7">Картой, перевод</option>\
		</select>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="payment_num" class="col-sm-3 col-form-label">№ платежного поручения</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="payment_num" name="payment_num" placeholder="Введите номер платежного поручения"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="payment_num" id="payment_num_label" onclick="clear_search_order_text(\'payment_num\');"></label>\
	    </div>\
	</div>\
  <div class="form-group row" id="payment_cashdesk_checkbox">\
	    <label for="payment_cashdesk" class="col-sm-3 col-form-label">Платеж из кассы</label>\
	    <div class="col-sm-9">\
		    <input type="checkbox" id="is_payment_cashdesk" name="is_payment_cashdesk" title="Если выбрана, то деньги снимаются из кассы наличных" onchange="select_cashdesk(\'dealer\')">\
        <div id="select_payment_cashdesk"></div>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="client_id" class="col-sm-3 col-form-label">Поставщик</label>\
	    <div class="col-sm-9">\
		<input type="hidden" name="company_id" id="company_id"><input type="hidden" name="payment_direction" value="2">\
		<input class="form-control search_str" type="text" id="company_name" name="company_name" placeholder="Выберите поставщика" onkeyup="get_payment_dealers();" onclick="get_payment_dealers();" autocomplete="off"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_name" id="company_name_label" onclick="clear_search_order_text(\'company_name\');"></label>\
		<div id="payment_0_dealers"></div>\
		</div>\
	</div>\
  <div class="form-group row">\
	    <label for="new_payment_create_date" class="col-sm-3 col-form-label">Дата платежа</label>\
	    <div class="col-sm-9">\
		    <input class="form-control" type="datetime-local" id="new_payment_create_date" name="create_date" value='+today+'>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="company_rekvizit_id" class="col-sm-3 col-form-label">Расчетный счет</label>\
	    <div class="col-sm-9">\
		<div id="payment_0_company_rekvizits"></div>\
		<input type="hidden" name="company_rekvizit_id" id="company_rekvizit_id">\
		<input class="form-control search_str" type="text" id="company_rekvizit_name" name="company_rekvizit_name" placeholder="Выберите расчетный счет" onclick="get_payment_company_rekvizits();"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_rekvizit_name" id="company_rekvizit_name_label" onclick="clear_search_order_text(\'company_rekvizit_name\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="summ" class="col-sm-3 col-form-label">Сумма</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="summ" name="summ" placeholder="Сумма платежа"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="rs" id="summ_label" onclick="clear_search_order_text(\'summ\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="from_inn" class="col-sm-3 col-form-label">ИНН плательщика</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="from_inn" name="from_inn" placeholder=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="from_inn" id="from_inn_label" onclick="clear_search_order_text(\'from_inn\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="from_kpp" class="col-sm-3 col-form-label">КПП плательщика</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" type="text" id="from_kpp" name="from_kpp" placeholder=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="from_kpp" id="from_kpp_label" onclick="clear_search_order_text(\'from_kpp\');"></label>\
	    </div>\
	</div>\
	</form>\
    ';
    table+='<button class="btn btn-primary" onclick="save_payment(\'dealer_payment_form_0\',0,\'dealer\');">Сохранить</button>\
		<button class="btn btn-secondary pull-right" onclick="$(\'#dealer_payment_0\').html(\'\');">Закрыть</button>\
    ';
    create_window_centered_blue("dealer_payment_0_div","Создание платежа","dealer_payment_0",table);
}

function edit_payment(id){
    send=new Array();
    send['payment_id']=id;
    api_query_array("/api/index.php",send,"get_payment").then(function(data){
    var table='';
    var tabname='';
    switch(data.payment.payment_direction){
      case "1": tabname='client'; break;
      case "2": tabname='dealer'; break;
      case "3":
      case "4":
      case "5": tabname='return'; break;
    }
    if(data.payment.payment_direction==2) table+='<form id="dealer_payment_form_'+data.payment.id+'">';
    else table+='<form id="payment_form_'+data.payment.id+'">';
	table+='<input type="hidden" name="payment_id" value="'+data.payment.id+'">\
  <input type="hidden" name="payment_direction" value="'+data.payment.payment_direction+'">\
	<div class="form-group row">\
	    <label for="payment_type" class="col-sm-3 col-form-label">тип платежного поручения</label>\
	    <div class="col-sm-9">\
		<select class="form-control" type="text" id="payment_type" name="payment_type">\
			<option value="1"';
			if(data.payment.payment_type=="1") table+=' selected="selected"';
			table+='>Наличными в офисе</option>\
      <option value="2"'
      if(data.payment.payment_type=="2") table+=' selected="selected"';
      table+='>Оплата банковской картой VISA,MASTER,MIR</option>\
			<option value="3"';
			if(data.payment.payment_type=="3") table+=' selected="selected"';
			table+='>Наличными курьеру при доставке</option>\
			<option value="4"';
			if(data.payment.payment_type=="4") table+=' selected="selected"';
			table+='>Безналичная оплата</option>\
      <option value="6"';
			if(data.payment.payment_type=="6") table+=' selected="selected"';
			table+='>Оплата по QR коду (СБП)</option>\
      <option value="7"';
			if(data.payment.payment_type=="7") table+=' selected="selected"';
			table+='>Оплата банковской картой, перевод</option>\
		</select>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="payment_num" class="col-sm-3 col-form-label">№ платежного поручения</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" onclick="this.select();" type="text" id="payment_num" name="payment_num" value="'+data.payment.payment_num+'">	<label style="position: absolute; top: 0.8em; right: 1.2em;" for="payment_num" id="payment_num_label" onclick="clear_search_order_text(\'payment_num\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="client_id" class="col-sm-3 col-form-label">Клиент</label>\
	    <div class="col-sm-9">\
		<input type="hidden" name="company_id" id="company_id" value="'+data.payment.company_id+'">\
		<input class="form-control search_str" type="text" id="company_name" name="company_name" value="'+data.payment.company_name.replace(/\"/g,"")+'" ';
    if(data.payment.payment_direction==2) table+='onkeyup="get_payment_dealers('+id+');" onclick="get_payment_dealers('+id+');"';
    else table+='onkeyup="get_payment_clients('+id+');" onclick="get_payment_clients('+id+');"';
    table+='>';
	  if(data.payment.payment_direction==2) table+='<div id="payment_'+id+'_dealers"></div>';
    else table+='<div id="payment_'+id+'_clients"></div>';
		table+='</div>\
	</div>\
	<div class="form-group row">\
	    <label for="zakaz_id" class="col-sm-3 col-form-label">№ заказа</label>\
	    <div class="col-sm-9">\
		<div id="payment_'+id+'_zakazs"></div>\
		<input class="form-control search_str" type="text" id="zakaz_id" name="zakaz_id" placeholder="Выберите номер заказа"  onclick="this.select();" value="'+data.payment.zakaz_id+'">	<label style="position: absolute; top: 0.8em; right: 1.2em;" for="zakaz_name" id="zakaz_name_label" onclick="clear_search_order_text(\'zakaz_name\');"></label>\
	    </div>\
	</div>\
  <div class="form-group row">\
	    <label for="payment_create_date" class="col-sm-3 col-form-label">Дата платежа</label>\
	    <div class="col-sm-9">\
		    <input class="form-control" type="datetime-local" id="payment_create_date" name="create_date" value='+data.payment.create_date.replace(" ","T")+'>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="company_rekvizit_id" class="col-sm-3 col-form-label">Расчетный счет</label>\
	    <div class="col-sm-9">\
		<div id="payment_'+id+'_company_rekvizits"></div>\
		<input type="hidden" name="company_rekvizit_id" id="company_rekvizit_id" value="'+data.payment.company_rekvizit_id+'">\
		<input class="form-control search_str" type="text" id="company_rekvizit_name" name="company_rekvizit_name" placeholder="Выберите расчетный счет" onclick="get_payment_company_rekvizits('+id+');" >	<label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_rekvizit_name" id="company_rekvizit_name_label" onclick="clear_search_order_text(\'company_rekvizit_name\');"></label>\
	    </div>\
	</div>\
  <div class="form-group row">\
	    <label for="payment_target" class="col-sm-3 col-form-label">Назначение платежа</label>\
	    <div class="col-sm-9">\
		    <input class="form-control search_str" onclick="this.select();" type="text" id="payment_target" name="payment_target" value="'+data.payment.payment_target+'">	<label style="position: absolute; top: 0.8em; right: 1.2em;" for="payment_target" id="payment_target_label" onclick="clear_search_order_text(\'payment_target\');"></label>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="summ" class="col-sm-3 col-form-label">Сумма</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" onclick="this.select();" type="text" id="summ" name="summ" value="'+data.payment.summ+'">	<label style="position: absolute; top: 0.8em; right: 1.2em;" for="summ" id="summ_label" onclick="clear_search_order_text(\'summ\');"></label>\
	    </div>\
	</div>\
  <div class="form-group row">\
	    <label for="from_cashback_balance" class="col-sm-3 col-form-label">Оплачено бонусами</label>\
	    <div class="col-sm-9">\
		<input class="form-control search_str" onclick="this.select();" type="text" id="from_cashback_balance" name="from_cashback_balance" value="'+data.payment.from_cashback_balance+'" disabled>	\
	    </div>\
	</div>\
	<div class="form-group row">';
	    if(data.payment.payment_direction=="1") table+='<label for="from_inn" class="col-sm-3 col-form-label">ИНН плательщика</label>';
      else if(data.payment.payment_direction=="2") table+='<label for="to_inn" class="col-sm-3 col-form-label">ИНН поставщика</label>';
	    table+='<div class="col-sm-9">';
		if(data.payment.payment_direction=="1") table+='<input class="form-control search_str" onclick="this.select();" type="text" id="from_inn" name="from_inn" value="'+data.payment.from_inn+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="rs" id="rs_label" onclick="clear_search_order_text(\'from_inn\');"></label>';
    else if(data.payment.payment_direction=="2") table+='<input class="form-control search_str" onclick="this.select();" type="text" id="to_inn" name="to_inn" value="'+data.payment.to_inn+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="rs" id="rs_label" onclick="clear_search_order_text(\'to_inn\');"></label>';
	  table+='  </div>\
	</div>\
	<div class="form-group row">';
	    if(data.payment.payment_direction=="1") table+='<label for="from_kpp" class="col-sm-3 col-form-label">КПП плательщика</label>';
      else if(data.payment.payment_direction=="2") table+='<label for="to_kpp" class="col-sm-3 col-form-label">КПП поставщика</label>';
	    table+='<div class="col-sm-9">';
		  if(data.payment.payment_direction=="1") table+='<input class="form-control search_str" onclick="this.select();" type="text" id="from_kpp" name="from_kpp" value="'+data.payment.from_kpp+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="rs" id="rs_label" onclick="clear_search_order_text(\'from_kpp\');"></label>';
      else if(data.payment.payment_direction=="2") table+='<input class="form-control search_str" onclick="this.select();" type="text" id="to_kpp" name="to_kpp" value="'+data.payment.to_kpp+'"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="rs" id="rs_label" onclick="clear_search_order_text(\'to_kpp\');"></label>';
	   table+=' </div>\
	</div>';
  if(data.payment.payment_direction=="2" && parseInt(data.payment.from_cashdesk_id)>0){
    table+='<div class="form-group row">\
        <label for="from_cashdesk_id" class="col-sm-3 col-form-label">Оплачено из кассы</label>\
        <div class="col-sm-9">\
        <input class="form-control" type="text" id="payment_cashdesk_id" name="payment_cashdesk_id" value="'+data.cashdesk.name+'" disabled>\
        </div>\
    </div>';
  }
  table+='<div class="form-group row">\
	    <label for="is_advance" class="col-sm-3 col-form-label">Аванс</label>\
	    <div class="col-sm-9">\
		  <input class="" type="checkbox" id="is_advance" name="is_advance" '+(data.payment.is_advance=="1"?"checked":"")+'>\
	    </div>\
	</div>';
  table+='<div class="form-group row">\
    <label for="dont_fiscalize" class="col-sm-3 col-form-label">Не фискализировать</label>\
    <div class="col-sm-9">\
    <input class="" type="checkbox" id="dont_fiscalize" name="dont_fiscalize" '+(data.payment.dont_fiscalize=="1"?"checked":"")+'>\
    </div>\
  </div>';
	table+='</form>\
    ';
    if(data.payment.payment_direction==2) table+='<button class="btn btn-primary" onclick="save_payment(\'dealer_payment_form_'+data.payment.id+'\','+id+',\''+tabname+'\');">Сохранить</button>';
    else table+='<button class="btn btn-primary" onclick="save_payment(\'payment_form_'+data.payment.id+'\','+id+',\''+tabname+'\');">Сохранить</button>';
		table+='<button class="btn btn-secondary pull-right" onclick="$(\'#payment_'+id+'\').html(\'\');">Закрыть</button>\
    ';
    create_window_centered_blue("payment_"+id+"_div","Изменение платежа","payment_"+id,table);
	if(data.payment.company_rekvizit_id>0)
		change_payment_company_rekvizit(data.payment.company_rekvizit_id,'',id);
    });
}

function save_payment(payment_form,payment_id=0,payment_direction){
	if(typeof(payment_direction)=="undefined") payment_direction_in_id="";
	else payment_direction_in_id=payment_direction+'_';
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Сохраняем платеж...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  if(typeof($("#dont_print_check").prop("checked"))!="undefined" && $("#dont_print_check").prop("checked")){
    var notPrintCheck=true;
  }
  if(parseInt($("#"+payment_form+" #payment_type").val())===2 && parseInt($("#"+payment_form+" [name=payment_direction]").val())===1){
    api_query("/api/index.php","some_form","get_active_kassas").then(function(data1){
      $.unblockUI();
      if(data1.kassas.length>0){
        var klen=data1.kassas.length;
        var numDevice=0;
        PayByProcessing=false;
        for(var i=0; i<klen; i++){
          if(data1.kassas[i].sklad_id==$("#my_sklad").val()){
            numDevice=data1.kassas[i].kassa_config.NumDeviceByProcessing;
            PayByProcessing=data1.kassas[i].kassa_config.PayByProcessing;
            break;
          }
        }
        if(numDevice!=0 && PayByProcessing){
          var payData={};
          payData.NumDevice=numDevice;
          payData.Summ=$("#"+payment_form+" #summ").val();
          payData.ReceiptNumber="";//zakaz_id;
          send=[];
          send['zakaz_id']=""; 
          send['company_id']=$("#"+payment_form+" #company_id").val();
          if(send['company_id']=="" || parseInt(send['company_id'])<=0) {
            bootbox.alert("Выберите сначала клиента, не понятно кто платит");
            return 0;
          }
          send['payment_target']="Авансовый платеж";
          send['payment_direction']=1;
          send['summ']=$("#"+payment_form+" #summ").val();
          send['payment_type']=$("#"+payment_form+" #payment_type").val();
          PayByPaymentCard(payData,send);
        }
        else {
          if(klen==0 || !PayByProcessing){
            api_query('/api/index.php',payment_form,'save_payment').done(function(data){
              if(data.status=="ok"){
                $('#'+payment_direction+'payment_'+payment_id).html('');
                $('#edit_payment_'+$(payment_form+'[name=payment_id]').val()).html('');
                if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
                  if(typeof(notPrintCheck)!="undefined"){
                    switch(notPrintCheck){
                      case false: data.excise_check_data.kassa_config.NotPrint=0; break;
                      case true: data.excise_check_data.kassa_config.NotPrint=1; break;
                    }
                  }
                  var check_res=RegisterCheck(data.excise_check_data);
                }
                if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
                  if(typeof(notPrintCheck)!="undefined"){
                    switch(notPrintCheck){
                      case false: data.check_data.kassa_config.NotPrint=0; break;
                      case true: data.check_data.kassa_config.NotPrint=1; break;
                    }
                  }
                  var check_res=RegisterCheck(data.check_data);
                }
                if(payment_direction=="") { 
                  if(parseInt($("#"+payment_form+" [name=payment_direction]").val())===1)
                    get_payments();
                  else {
                    if(parseInt($("#"+payment_form+" [name=payment_direction]").val())===2){
                      $("#dealer_payment_0").html('');
                      get_delivery_payments();
                    }
                  }
                }
                else 
                  if(payment_direction=="return")
                        get_return_payments();
              }
            });
          }
          else {
            bootbox.alert("Вы не можете принимать оплату, откройте смену, или обратитесь к кассиру вашего магазина");
          }
        }
      }
      else { // нет активных касс, просто принимаем оплату в ручном режиме
        api_query('/api/index.php',payment_form,'save_payment').done(function(data){
          if(data.status=="ok"){
              $('#'+payment_direction+'payment_'+payment_id).html('');
          $('#edit_payment_'+$(payment_form+'[name=payment_id]').val()).html('');
          if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
            if(typeof(notPrintCheck)!="undefined"){
              switch(notPrintCheck){
                case false: data.excise_check_data.kassa_config.NotPrint=0; break;
                case true: data.excise_check_data.kassa_config.NotPrint=1; break;
              }
            }
            var check_res=RegisterCheck(data.excise_check_data);
          }
          if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
            if(typeof(notPrintCheck)!="undefined"){
              switch(notPrintCheck){
                case false: data.check_data.kassa_config.NotPrint=0; break;
                case true: data.check_data.kassa_config.NotPrint=1; break;
              }
            }
            var check_res=RegisterCheck(data.check_data);
          }
          if(payment_direction=="") { 
            if(parseInt($("#"+payment_form+" [name=payment_direction]").val())===1)
              get_payments();
            else {
              if(parseInt($("#"+payment_form+" [name=payment_direction]").val())===2){
                $("#dealer_payment_0").html('');
                get_delivery_payments();
              }
            }
          }
          else 
            if(payment_direction=="return")
                  get_return_payments();
          }
        });
      }
      
    });
  }
  else {
    api_query('/api/index.php',payment_form,'save_payment').done(function(data){
      $.unblockUI();
    	if(data.status=="ok"){
    	    $('#'+payment_direction_in_id+'payment_'+payment_id).html('');
			$('#edit_payment_'+$(payment_form+'[name=payment_id]').val()).html('');
			if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
        if(typeof(notPrintCheck)!="undefined"){
          switch(notPrintCheck){
            case false: data.excise_check_data.kassa_config.NotPrint=0; break;
            case true: data.excise_check_data.kassa_config.NotPrint=1; break;
          }
        }
        var check_res=RegisterCheck(data.excise_check_data);
      }
      if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
        if(typeof(notPrintCheck)!="undefined"){
          switch(notPrintCheck){
            case false: data.check_data.kassa_config.NotPrint=0; break;
            case true: data.check_data.kassa_config.NotPrint=1; break;
          }
        }
        var check_res=RegisterCheck(data.check_data);
      }
			if(payment_direction=="") { 
        if(parseInt($("#"+payment_form+" [name=payment_direction]").val())===1)
          get_payments();
        else {
          if(parseInt($("#"+payment_form+" [name=payment_direction]").val())===2){
            $("#dealer_payment_0").html('');
            get_delivery_payments();
          }
        }
      }
			else 
				switch(payment_direction){
    	    case "return": get_return_payments(); break;
          case "client": get_payments(); break;
          case "dealer": get_delivery_payments(); break;
        }
    	}
    });
  }
}

function fiscalize_payment(payment_id,payment_direction="",notPrintCheck,Correction=0){
    var send=[];
    send['payment_id']=payment_id;
    send['correction']=Correction;
    $.blockUI({ css: { 
      border: 'none', 
      padding: '15px', 
      backgroundColor: '#000', 
      '-webkit-border-radius': '10px', 
      '-moz-border-radius': '10px', 
      opacity: .5, 
      color: '#fff'
      },
      message: 'Сохраняем...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
    });
    api_query_array('/api/index.php',send,'fiscalize_payment').done(function(data){
      
    	if(data.status=="ok"){
        if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
          if(typeof(notPrintCheck)!="undefined"){
            switch(notPrintCheck){
              case false: data.excise_check_data.kassa_config.NotPrint=0; break;
              case true: data.excise_check_data.kassa_config.NotPrint=1; break;
            }
          }
          if(Correction==1){
            switch(data.excise_check_data.TypeCheck){
              case 0: data.excise_check_data.TypeCheck=2; break;
              case 1: data.excise_check_data.TypeCheck=3; break;
              case 10: data.excise_check_data.TypeCheck=12; break;
              case 11: data.excise_check_data.TypeCheck=13; break;
            }
            data.excise_check_data.CorrectionBaseDate=data.payment_date.replace(" ","T");
            data.excise_check_data.CorrectionBaseNumber="PAS-"+payment_id;
            data.excise_check_data.CorrectionBaseName="Акт коррекции";
          }
          var check_res=RegisterCheck(data.excise_check_data);
        }
        if(typeof(data.check_data)!="undefined" && data.check_data!==null && (data.check_data.details.length>0 || data.check_data.jobs.length>0) ){
          if(typeof(notPrintCheck)!="undefined"){
            switch(notPrintCheck){
              case false: data.check_data.kassa_config.NotPrint=0; break;
              case true: data.check_data.kassa_config.NotPrint=1; break;
            }
          }
          if(Correction==1){
            switch(data.check_data.TypeCheck){
              case 0: data.check_data.TypeCheck=2; break;
              case 1: data.check_data.TypeCheck=3; break;
              case 10: data.check_data.TypeCheck=12; break;
              case 11: data.check_data.TypeCheck=13; break;
              
            }
            data.check_data.CorrectionBaseDate=data.payment_date.replace(" ","T");
            data.check_data.CorrectionBaseNumber="PAS-"+payment_id;
            data.check_data.CorrectionBaseName="Акт коррекции";
          }
          var check_res=RegisterCheck(data.check_data);
        }
        if(payment_direction=="") 
          get_payments();
        else 
          if(payment_direction=="return")
                get_return_payments();
      }
      $.unblockUI();
    });
}

function check_payment_type(){
  if($("#select_payment_type_from_zakaz").val()==1){
    $("#client_paid_sum_tr").css("display","table-row");
    $("#calculated_change_tr").css("display","table-row");
  }
  else {
    $("#client_paid_sum_tr").css("display","none");
    $("#calculated_change_tr").css("display","none");
  }
}

function calculate_change(){
  var paid_sum=parseFloat($("#select_client_paid_sum_from_zakaz").val());
  var payment_sum=parseFloat($("#select_payment_summ_from_zakaz").val());
  $("#calculated_change").html("<b>"+(paid_sum-payment_sum)+"</b>");
}

function select_payment_type_from_zakaz(company_id,zakaz_id,summ){	
	api_query("/api/index.php","some_form","get_payment_types").then(function(data){
		var send=new Array();
		send['company_id']=company_id;
		api_query_array("/api/index.php",send,"get_company").then(function(data1){
      var send_zakaz=[];
      send_zakaz['zakaz_id']=zakaz_id;
      api_query_array("/api/index.php",send_zakaz,"get_zakaz").then(function(zakaz_data){
        var table='<table class="table"><tbody>';
        table+='<tr><td>Тип оплаты:</td><td><select class="form-control input-sm" id="select_payment_type_from_zakaz" onchange="check_payment_type()">';
        table+='<option value="0">не выбран</option>';
        var len=data.payment_types.length;
        for(var i=0; i<len;i++){
          //if(data.payment_types[i].id!="6")
            table+='<option value="'+data.payment_types[i].id+'"';
            if(zakaz_data.zakaz.payment_type==data.payment_types[i].id) table+=' selected';
            table+='>'+data.payment_types[i].name+'</option>';
        }
        table+='</select></td></tr>';
        table+='<tr><td>Сумма оплаты:</td><td><input type="text" name="select_payment_summ_from_zakaz" id="select_payment_summ_from_zakaz" class="form-control input-sm" value="'+summ+'"></td></tr>';
        table+='<tr '+(zakaz_data.zakaz.payment_type==1?style="display:none":"")+' id="client_paid_sum_tr"><td>Внесено клиентом <br>(для расчета сдачи):</td><td><input type="text" name="client_paid_sum" id="select_client_paid_sum_from_zakaz" class="form-control input-sm" value="" onchange="calculate_change();"></td></tr>';

        table+='<tr '+(zakaz_data.zakaz.payment_type==1?style="display:none":"")+' id="calculated_change_tr"><td>Сдача:</td><td><span id="calculated_change"></span></td></tr>';
        table+='<tr><td>На балансе денег:</td><td>'+data1.company_balance+'</td></tr>';
        table+='<tr><td>На балансе бонусов:</td><td>'+data1.company_cashback+'</td></tr>';
        table+='<tr><td>Сумма заказа:</td><td>'+summ+'</td></tr>';
        table+='<tr><td>Оплачено бонусами:</td><td>'+zakaz_data.zakaz.zakaz_cashback_discount+'</td></tr>';
        table+='<tr><td>Аванс:</td><td><input type="checkbox" name="is_advance_payment_from_zakaz" id="is_advance_payment_from_zakaz"></td></tr>';
        table+='<tr><td>Не фискализировать:</td><td><input type="checkbox" name="dont_fiscalize" id="dont_fiscalize_from_zakaz"></td></tr>';
        table+='<tr><td>Не печатать чек:</td><td><input type="checkbox" name="dont_print_check" id="dont_print_check_from_zakaz"></td></tr>';
        table+='<tr><td><button type="button" class="btn btn-sm btn-success" onclick="save_payment_from_zakaz('+company_id+','+zakaz_id+',\''+summ+'\');">Оплатить</button></td>';
        table+='<td><button type="button" class="btn btn-sm btn-default pull-right" onclick="$(\'#select_payment_type_'+zakaz_id+'\').html(\'\');">Отменить</button></td></tr>';
        table+='</tbody></table>';
        create_window_centered_blue("select_payment_type_"+zakaz_id+"_div","Выберите тип оплаты","select_payment_type_"+zakaz_id,table);
      });
		});
	});
}

function save_payment_from_zakaz(company_id,zakaz_id,summ){
	var send=new Array();
	send['zakaz_id']=zakaz_id;
	send['company_id']=company_id;
	send['payment_target']="Оплата заказа №"+zakaz_id;
	send['payment_direction']=1;
  send['is_advance']=$("#is_advance_payment_from_zakaz").prop('checked');
  send['dont_fiscalize']=$("#dont_fiscalize_from_zakaz").prop('checked');
	send['summ']=$("#select_payment_summ_from_zakaz").val();
	send['payment_type']=$("#select_payment_type_from_zakaz").val();
  for(let i in zakazes){
    if(parseInt(zakazes[i].id)==zakaz_id){
      var zakaz_data=zakazes[i];
      break;
    }
  }
  var notPrintCheck=false;
  if(typeof($("#dont_print_check_from_zakaz").prop("checked"))!="undefined" && $("#dont_print_check_from_zakaz").prop("checked")){
    notPrintCheck=true;
  }
	if(parseInt(send['payment_type'])===0){
		bootbox.alert("Выберите тип платежа");
	}
	else {
    $.blockUI({ css: { 
      border: 'none', 
      padding: '15px', 
      backgroundColor: '#000', 
      '-webkit-border-radius': '10px', 
      '-moz-border-radius': '10px', 
      opacity: .5, 
      color: '#fff'
      },
      message: 'Производим оплату заказа...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
    });
    switch(parseInt(send['payment_type'])){
      case 2: //оплата картой через терминал
        api_query("/api/index.php","some_form","get_active_kassas").then(function(data1){
          $.unblockUI();
          if(data1.kassas.length>0){ //&& send['no_fiscalize']==false){
            if(typeof(zakaz_data)!="undefined"){
              if((parseFloat(zakaz_data.pay_sum)+parseFloat(send['summ']))<parseFloat(zakaz_data.zakaz_sum) && send['is_advance']==false){
                if(parseInt(send['payment_type'])==2){
                  // при быстрой продаже мы уже приняли оплату ее надо записать как аванс.
                  send['is_advance']=1;
                }
                else {
                  bootbox.alert("<font color='red'>Ошибка!!! </font>Платеж не является авансовым и сумма платежа меньше стоимости заказа");
                  return;
                }
              }
            }
            var klen=data1.kassas.length;
            var numDevice=0;
            var PayByProcessing=false;
            for(var i=0; i<klen; i++){
              if(data1.kassas[i].sklad_id==$("#my_sklad").val() && typeof(data1.kassas[i].kassa_config.NumDeviceByProcessing)!="undefined" && typeof(data1.kassas[i].kassa_config.PayByProcessing)!="undefined"){
                  numDevice=data1.kassas[i].kassa_config.NumDeviceByProcessing;
                  PayByProcessing=data1.kassas[i].kassa_config.PayByProcessing;
                  break;
              }
            }
            if(numDevice!=0 && PayByProcessing){
              var payData={};
              payData.NumDevice=numDevice;
              payData.Summ=send['summ'];
              payData.ReceiptNumber=zakaz_id;
              PayByPaymentCard(payData,send);
            }
            else {
              if(klen==0 || !PayByProcessing){
                api_query_array('/api/index.php',send,'save_payment').done(function(data){
                  if(data.status=="ok"){
                    //$('#payment_'+payment_id).html('');
                    //$('#edit_payment_'+$(payment_form+'[name=payment_id]').val()).html('');
                    if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
                      if(typeof(notPrintCheck)!="undefined"){
                        switch(notPrintCheck){
                          case false: data.excise_check_data.kassa_config.NotPrint=0; break;
                          case true: data.excise_check_data.kassa_config.NotPrint=1; break;
                        }
                      }
                      var check_res=RegisterCheck(data.excise_check_data);
                    }
                    if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
                      if(typeof(notPrintCheck)!="undefined"){
                        switch(notPrintCheck){
                          case false: data.check_data.kassa_config.NotPrint=0; break;
                          case true: data.check_data.kassa_config.NotPrint=1; break;
                        }
                      }
                      var check_res=RegisterCheck(data.check_data);
                    }
                    get_payments();
                    get_zakazes().then(function(data){
                      get_zakaz_details1('zakaz_form_'+zakaz_id);
                    })
                  }
                });
              }
              else {
                bootbox.alert("Вы не можете принимать оплату, откройте смену, или обратитесь к кассиру вашего магазина");
              }
            }
          }
          else { // нет активных касс, просто принимаем оплату в ручном режиме
            api_query_array('/api/index.php',send,'save_payment').done(function(data){
              $.unblockUI();
              if(data.status=="ok"){
                //$('#payment_'+payment_id).html('');
                //$('#edit_payment_'+$(payment_form+'[name=payment_id]').val()).html('');
                if(typeof(data.check_data)!="undefined" && data.check_data!=null){
                  //var check_res=RegisterCheck(data.check_data);
                }
                get_payments();
                get_zakazes().then(function(data){
                  get_zakaz_details1('zakaz_form_'+zakaz_id);
                })
              }
            });
          }
          
        })
        
        break;
      default: 
        api_query_array('/api/index.php',send,'save_payment').done(function(data){
          $.unblockUI();
          if(data.status=="ok"){
            //$('#payment_'+payment_id).html('');
            //$('#edit_payment_'+$(payment_form+'[name=payment_id]').val()).html('');
            if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
              if(typeof(notPrintCheck)!="undefined"){
                switch(notPrintCheck){
                  case false: data.excise_check_data.kassa_config.NotPrint=0; break;
                  case true: data.excise_check_data.kassa_config.NotPrint=1; break;
                }
              }
              var check_res=RegisterCheck(data.excise_check_data);
            }
            if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
              if(typeof(notPrintCheck)!="undefined"){
                switch(notPrintCheck){
                  case false: data.check_data.kassa_config.NotPrint=0; break;
                  case true: data.check_data.kassa_config.NotPrint=1; break;
                }
              }
              var check_res=RegisterCheck(data.check_data);
            }
            get_payments();
            get_zakazes().then(function(data){
              get_zakaz_details1('zakaz_form_'+zakaz_id);
            })
          }
        });
    }
	}
}

function bank_orders_uploader(){
    'use strict';
    var url = '/api/index.php';
    $('#bank_client_upload').fileupload({
        url: url,
        dataType: 'json',
        done: function (e, data) {
          loaded_payments_in=data.result.result.payments_in;
          loaded_payments_out=data.result.result.payments_out;
          var bank_orders=build_bank_orders("client");
          create_window('select_bank_orders_div','Выберите строки для загрузки','select_bank_orders',bank_orders);
        },
        progressall: function (e, data) {
           $('#progress').show();
           var progress = parseInt(data.loaded / data.total * 100, 10);
           $('#progress .progress-bar').css(
                'width',
                progress + '%'
           );
           if (progress>99) $('#progress').hide();
        }
    }).prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled');
}

function bank_orders_delivery_uploader(){
    'use strict';
    var url = '/api/index.php';
    $('#bank_delivery_upload').fileupload({
        url: url,
        dataType: 'json',
        done: function (e, data) {
          loaded_payments_in=data.result.result.payments_in;
          loaded_payments_out=data.result.result.payments_out;
          var bank_orders=build_bank_orders("delivery");
          create_window('select_delivery_bank_orders_div','Выберите строки для загрузки','select_delivery_bank_orders',bank_orders);
        },
        progressall: function (e, data) {
           $('#progress').show();
           var progress = parseInt(data.loaded / data.total * 100, 10);
           $('#progress .progress-bar').css(
                'width',
                progress + '%'
           );
           if (progress>99) $('#progress').hide();
        }
    }).prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled');
}

function set_all_payment_checkbox(type){
  if(type=="client") { var pay_len=loaded_payments_in.length; var loaded=loaded_payments_in; }
  else { var pay_len=loaded_payments_out.length; var loaded=loaded_payments_out; }
  if($("#check_all_import_from_bank").prop("checked")){
    var val=1;
    $("input.check_box_import_from_bank").prop("checked",true);
  }
  else{ 
    var val=0;
    $("input.check_box_import_from_bank").prop("checked",false);
  }
  for(let i=0; i<pay_len; i++){
    loaded[i].import=val;
  }
}

function build_bank_orders(type){
  if((loaded_payments_in===null && type=="client") || (loaded_payments_out===null && type=="delivery")) {
    var table ="В данном файле нет ваших платежных поручений";
    return table;
  }
  else {
    var payin_len=0;
    if(typeof(loaded_payments_in)!="undefined" && loaded_payments_in!=null) payin_len=loaded_payments_in.length;
    var payout_len=0;
    if(typeof(loaded_payments_out)!="undefined" && loaded_payments_out!=null) payout_len=loaded_payments_out.length;
    var table = '<div id="show_bank_order_'+type+'"></div><table class="table table-hover">';
    table+='<thead>';
    if(type=="client")
      table+='<tr><td colspan="9"><button class="btn btn-success" onclick="import_payments(\'in\')">Загрузить</button></td><td colspan="5"><button class="btn btn-default" onclick="close_bank_orders(\''+type+'\');">Закрыть</button></td></tr>';
    else
      table+='<tr><td colspan="9"><button class="btn btn-success" onclick="import_payments(\'out\')">Загрузить</button></td><td colspan="5"><button class="btn btn-default" onclick="close_bank_orders(\''+type+'\');">Закрыть</button></td></tr>';
    table+='<tr><th><input type="checkbox" onchange="set_all_payment_checkbox(\''+type+'\');" id="check_all_import_from_bank"></th><th>№</th><th>Дата платежа</th><th>Номер пл.пор.</th><th>Плательщик</th><th>ПлательщикИНН</th><th>Получатель</th><th>ПолучательИНН</th><th>Назначение платежа</th><th>Сумма</th><th>Состояние</th></tr>';
    table+='</thead><tbody>';
    if(type=="client"){
      for(var i=0; i<payin_len; i++){
        table+='<tr ondblclick="show_payment_details('+i+',\''+type+'\')"><td><input type="checkbox" onchange="add_to_import(\'in\','+i+');" class="check_box_import_from_bank"></td><td>'+i+'</td><td>'+loaded_payments_in[i].Дата+'</td><td>'+loaded_payments_in[i].Номер+'</td><td>';
        if(typeof(loaded_payments_in[i].Плательщик)!="undefined") table+=loaded_payments_in[i].Плательщик;
        else table+=loaded_payments_in[i].Плательщик1;
        table+='</td><td>'+loaded_payments_in[i].ПлательщикИНН+'</td><td>';
        if(typeof(loaded_payments_in[i].Получатель)!="undefined") table+=loaded_payments_in[i].Получатель;
        else table+=loaded_payments_in[i].Получатель1;
        table+='</td><td>'+loaded_payments_in[i].ПолучательИНН+'</td><td>'+loaded_payments_in[i].НазначениеПлатежа+'</td><td>'+loaded_payments_in[i].Сумма+'</td>';
        table+='<td><div id="bank_order_status_'+type+'_'+i+'"></div></td></tr>';
      }
      table+='<tr><td colspan="10"><button class="btn btn-success" onclick="import_payments(\'in\')">Загрузить</button></td><td colspan="5"><button class="btn btn-default" onclick="close_bank_orders(\''+type+'\');">Закрыть</button></td></tr>';
    }
    if(type=="delivery"){
      for(var i=0; i<payout_len; i++){
        table+='<tr ondblclick="show_payment_details('+i+',\''+type+'\')"><td><input type="checkbox" onchange="add_to_import(\'out\','+i+');" class="check_box_import_from_bank"></td><td>'+i+'</td><td>'+loaded_payments_out[i].Дата+'</td><td>'+loaded_payments_out[i].Номер+'</td><td>';
        if(typeof(loaded_payments_out[i].Плательщик)!="undefined") table+=loaded_payments_out[i].Плательщик;
        else table+=loaded_payments_out[i].Плательщик1;
        table+='</td><td>'+loaded_payments_out[i].ПлательщикИНН+'</td><td>';
        if(typeof(loaded_payments_out[i].Получатель)!="undefined") table+=loaded_payments_out[i].Получатель;
        else table+=loaded_payments_out[i].Получатель1;
        table+='</td><td>'+loaded_payments_out[i].ПолучательИНН+'</td><td>'+loaded_payments_out[i].НазначениеПлатежа+'</td><td>'+loaded_payments_out[i].Сумма+'</td>';
        table+='<td><div id="bank_order_status_'+type+'_'+i+'"></div></td></tr>';
      }
      table+='<tr><td colspan="9"><button class="btn btn-success" onclick="import_payments(\'out\')">Загрузить</button></td><td colspan="5"><button class="btn btn-default" onclick="close_bank_orders(\''+type+'\');">Закрыть</button></td></tr>';
    }

    table+='</tbody></table>';
    return table;
  }
}

function close_bank_orders(type){
  if(type=="client"){
	$("#select_bank_orders").html('');
	get_payments();
  }
  if(type=="delivery"){
	$("#select_delivery_bank_orders").html('');
	get_delivery_payments();
  }
}

function show_payment_details(id,type){
  var out='<table class="table table-hover">';
  if(type=="client") {
    arr=loaded_payments_in[id];
  }
  else if(type=="delivery")
    arr=loaded_payments_out[id];
    //var tds=loaded_payments_in[id].map((key,val)=>{return "<td>"+key+"</td><td>"+val+"</td>"});
  Object.keys(arr).forEach(function(i){
      if(arr[i]!="") out+="<tr><td>"+i+":</td><td>"+arr[i]+"</td></tr>";
  });
  out+="</table>";
  create_window_centered_blue('show_bank_order_'+type+'_div','Данные платежного поручения','show_bank_order_'+type,out);
}

function add_to_import(type,id){
  if(type=="in"){
    var changing_payment=loaded_payments_in[id];
  }
  if(type=="out"){
    var changing_payment=loaded_payments_out[id];
  }
  if(typeof(changing_payment.import)=="undefined"){
    changing_payment.import=1;
  }
  else {
    if(changing_payment.import==1) changing_payment.import=0;
    else changing_payment.import=1;
  }
}

function import_payments(type){
  var send=new Array();
  send['payments']=new Array();
  if(type=="in"){
    var array=loaded_payments_in;
    send['type']="in";
  }
  else {
    if (type=="out"){
      var array=loaded_payments_out;
      send['type']="out";
    }
  }
  var i=0,j=0;
  array.forEach(function(item){
    if(typeof(item.import)!="undefined" && item.import==1){
      send['payments'][i]=item;
      send['payments'][i]['return_index']=j;
      i++;
    }
    j++;
  });
  api_query_array("/api/index.php",send,"import_payments").then(function(data){
    j=0;i=0;
    if(type=="in") var div_type="client";
    if(type=="out") var div_type="delivery";
    array.forEach(function(item){
      if(typeof(item.import)!="undefined" && item.import==1){
        switch(data.payments_saved[j]){
          case 1:$("#bank_order_status_"+div_type+"_"+j).html('Успешно загружен');break;
          case 10:$("#bank_order_status_"+div_type+"_"+j).html('Был загружен ранее');break;
          default: $("#bank_order_status_"+div_type+"_"+j).html('<span style="color: red;">Ошибка загрузки</span>');
        }
        i++;
      }
      j++;
    });
  });
}

var keyTimerPayment;

function get_paymentfilter_text(){
//    var city_name=$("#city_name").val();
    clearTimeout(keyTimerPayment);
    keyTimerPayment = setTimeout(runTextFilterPayment, 1000);
}

function runTextFilterPayment(){
    if(typeof(paymentes)!="undefined" && paymentes.length>0){
      if(typeof(paymentfilter['filter_count'])=="undefined") paymentfilter['filter_count']=0;
      paymentfilter['filter_text']=$("#paymentfilter_text").val();
      if(paymentfilter['filter_text']!="") paymentfilter['filter_count']++;
      else paymentfilter['filter_count']--;
      print_paymentes();
      //}
    }
}

function clear_search_payment_text(input_id){
  $('#'+input_id).val('');
  runTextFilterPayment();
}

var paymentfilter=new Array();

function print_paymentes(){
  var datalen=paymentes.length;
  var s_paymentes_i=0;
  var show_paymentes=new Array();
  //if(typeof(paymentfilter)=="undefined") filter=new Array();
  if(typeof(paymentfilter['filter_counter'])=="undefined"){
      paymentfilter['filter_counter']={};
      paymentfilter['filter_counter']['company_name']=0;
      paymentfilter['filter_counter']['status']=0;
      paymentfilter['filter_counter']['delivery_type_name']=0;
      paymentfilter['filter_counter']['user_lastname']=0;
  }
  if(typeof(paymentfilter['id'])=="undefined"){
    paymentfilter['id']=new Array();
    }
    if(typeof(paymentfilter['company_name'])=="undefined"){
        paymentfilter['company_name']=new Array();
    }
    if(typeof(paymentfilter['payment_type'])=="undefined"){
      paymentfilter['payment_type']=new Array();
    }
    if(typeof(paymentfilter['zakaz_id'])=="undefined"){
      paymentfilter['zakaz_id']=new Array();
    }
    if(typeof(paymentfilter['lastname'])=="undefined"){
      paymentfilter['lastname']=new Array();
    }

    for (i=0; i<datalen; i++){  
      if(typeof(paymentfilter['company_name'][paymentes[i]["company_name"]])=="undefined"){
        if(paymentes[i]["company_name"]==null) paymentes[i]["company_name"]="";
              paymentfilter['company_name'][paymentes[i]["company_name"]]=new Array();
              paymentfilter['company_name'][paymentes[i]["company_name"]]['check']=0;
              paymentfilter['company_name'][paymentes[i]["company_name"]]['print']=paymentes[i]["company_name"];
      }

      if(typeof(paymentfilter['id'][paymentes[i]["id"]])=="undefined"){
        if(paymentes[i]["id"]==null) paymentes[i]["id"]="";
              paymentfilter['id'][paymentes[i]["id"]]=new Array();
              paymentfilter['id'][paymentes[i]["id"]]['check']=0;
              paymentfilter['id'][paymentes[i]["id"]]['print']=paymentes[i]["id"];
      }
      
      if(typeof(paymentfilter['payment_type'][paymentes[i]["payment_type"]])=="undefined"){
        if(paymentes[i]["payment_type"]==null) paymentes[i]["payment_type"]="";
              paymentfilter['payment_type'][paymentes[i]["payment_type"]]=new Array();
              paymentfilter['payment_type'][paymentes[i]["payment_type"]]['check']=0;
              paymentfilter['payment_type'][paymentes[i]["payment_type"]]['print']=payment_types[paymentes[i]["payment_type"]];

      }
      
      if(typeof(paymentfilter['zakaz_id'][paymentes[i]["zakaz_id"]])=="undefined"){
        if(paymentes[i]["zakaz_id"]==null) paymentes[i]["zakaz_id"]=""; 
              paymentfilter['zakaz_id'][paymentes[i]["zakaz_id"]]=new Array();
              paymentfilter['zakaz_id'][paymentes[i]["zakaz_id"]]['check']=0;
              paymentfilter['zakaz_id'][paymentes[i]["zakaz_id"]]['print']=paymentes[i]["zakaz_id"];

      }
      if(typeof(paymentfilter['lastname'][paymentes[i]["lastname"]])=="undefined"){
        if(paymentes[i]["lastname"]==null) paymentes[i]["lastname"]="";
        paymentfilter['lastname'][paymentes[i]["lastname"]]=new Array();
        paymentfilter['lastname'][paymentes[i]["lastname"]]['check']=0;
        paymentfilter['lastname'][paymentes[i]["lastname"]]['print']=paymentes[i]["lastname"];
      }

      if(typeof(paymentfilter['filter_count'])!="undefined" && paymentfilter['filter_count']>0){
        if(paymentfilter_1(i)){
          show_paymentes[s_paymentes_i]=paymentes[i];
          show_paymentes[s_paymentes_i]['item_index']=i;
          s_paymentes_i++;
        }
      }
      else {
        show_paymentes[s_paymentes_i]=paymentes[i];
        show_paymentes[s_paymentes_i]['item_index']=i;
        s_paymentes_i++;
      }
    }
    var datalen=show_paymentes.length;
    var paymentes_sum=0,paymentes_sum_count=0;
    
    var table="<table class=\"table table-hover\"><thead><tr>";
    table+=make_paymentes_header('id','№');
	table+='<th>Дата платежа</th><th>№ плат. пор.</th>';
    table+='<th>Назначение платежа</th>';
	table+=make_paymentes_header('payment_type','Тип платежа');
    table+=make_paymentes_header('company_name','Покупатель');
    
    table+=make_paymentes_header('zakaz_id','№ заказа');
    table+='<th>Сумма</th><th>ИНН плательщика</th>';
    table+=make_paymentes_header('lastname','Кассир');
    table+='<th>Аванс</th>';
    table+='<th>Фиск.</th>';
    table+='<th></th></tr></thead><tbody>';
    //<th>Покупатель</th><th>Статус</th><th>Пункт выдачи</th><th>Адрес доставки</th><th>Позиций в заказе</th><th>Сумма заказа</th><th>Менеджер</th><th>Коммент.</th><th></th></tr></thead><tbody>";
    //table+='<table class="table table-hover">';
    //	table+='<thead><tr><th>№ плат. пор.</th><th>Назначение платежа</th><th>тип платежа</th><th>клиент</th><th>№ заказа</th><th>сумма</th><th>Дата платежа</th><th>ИНН плательщика</th><th>Кассир</th><th></th></tr></thead>';
    	for(var i=0; i<datalen; i++){
    	    table+='<tr ondblclick="edit_payment('+show_paymentes[i].id+');"><td>'+show_paymentes[i].id+'</td><td>'+convertTZ(show_paymentes[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td><div id="payment_'+show_paymentes[i].id+'"></div>'+show_paymentes[i].payment_num+'</td>';
			table+='<td>'+show_paymentes[i].payment_target+'</td><td>'+payment_types[show_paymentes[i].payment_type]+'</td>';
			table+='<td>'+show_paymentes[i].company_name+'</td><td><a onclick="open_zakaz('+show_paymentes[i].zakaz_id+');">'+show_paymentes[i].zakaz_id+'</a></td>';
			table+='<td style="text-align: right;">'+parseFloat(show_paymentes[i].summ).toFixed(2)+'</td>';
			table+='<td>'+show_paymentes[i].from_inn+'</td>';
    	    table+='<td>';
			if(show_paymentes[i].lastname!==null) table+=show_paymentes[i].lastname+" ";
			if(show_paymentes[i].name!==null) table+=show_paymentes[i].name+" ";
			if(show_paymentes[i].middlename!==null) table+=show_paymentes[i].middlename;
			table+='</td>';
      if(show_paymentes[i].is_advance=="1") table+='<td>Да</td>';
      else table+='<td></td>';
      table+='<td nowrap>';
      if(show_paymentes[i].dont_fiscalize=="0"){
        if(show_paymentes[i].is_divided=="1"){
          if(parseInt(show_paymentes[i].fiscalized)==0 && parseInt(show_paymentes[i].payment_type)!=4) table+='<button class="btn btn-xs btn-default" onclick="fiscalize_payment('+show_paymentes[i].id+')">Фиск.</button>';
          else {
            table+='<img src="/images/ok.svg" width="25px;">';
          }
          if(parseInt(show_paymentes[i].fiscalized_excise)==0 && parseInt(show_paymentes[i].payment_type)!=4) table+='<button class="btn btn-xs btn-default" onclick="fiscalize_payment('+show_paymentes[i].id+')">Фиск.Акциз</button>';
          else {
            table+='<img src="/images/ok.svg" width="25px;">';
          }
        }
        else {
          if(parseInt(show_paymentes[i].fiscalized)==0 && parseInt(show_paymentes[i].payment_type)!=4) table+='<button class="btn btn-xs btn-default" onclick="fiscalize_payment('+show_paymentes[i].id+')">Фиск.</button>';
          else {
            table+='<img src="/images/ok.svg" width="25px;">';
            table+='<a onclick="fiscalize_payment('+show_paymentes[i].id+',\'\',true,1);"><img src="/new_images/invoice.png" title="отправить чек коррекции" style="width: 25px;"></a>';
          }
        }
      }
      table+='</td>';
			table+='<td nowrap>';
    	    table+='<a onclick="edit_payment('+show_paymentes[i].id+');"><img src="/new_images/edit.svg" class="menuimg"></a>';
          table+=' <a onclick="bootbox.confirm(\'Вы точно хотите удалить ваш заказ?\',function(result){ if(result) delete_payment('+show_paymentes[i].id+',\'c\');})"><img src="/new_images/garbage.svg" class="menuimg"></a>';
          table+=' <a onclick="return_payment('+show_paymentes[i].id+',\'c\');" title="Вернуть платеж"><img src="/new_images/cash-refund.png" class="menuimg"></a>';
    	    table+='</td></tr>';
          	paymentes_sum+=parseFloat(show_paymentes[i].summ);
    	}
      	table+='<tr><td><b>Итого</b></td><td colspan="6"></td><td><b>'+paymentes_sum.toFixed(2)+'</b></td><td colspan="5"></td></tr>';
    	table+='</table>';
    	$("#payments_list").html(table);
    //return table;
}

function paymentfilter_1(i){
  if(typeof(paymentfilter['filter_count'])=="undefined" || paymentfilter['filter_count']==0) return 1;
  var item=paymentes[i];
  var flag=new Array();
  var ret=0,filter_text_ret=0;
  if(item["company_name"]==null) item["company_name"]="";
  if(item["delivery_type_name"]==null) item["delivery_type_name"]="";
  if(item["status"]==null) item["status"]="";
  if(item["company_name"].search(RegExp(paymentfilter['filter_text'],"i")) != -1 || item["status"].search(RegExp(paymentfilter['filter_text'],"i")) != -1 || item["delivery_type_name"].search(RegExp(paymentfilter['filter_text'],"i")) != -1 )
    filter_text_ret=1;
  if(paymentfilter['filter_text']=="") filter_text_ret=1;
  for(let field in paymentfilter){
    if(paymentfilter['filter_counter'][field]==0) continue;
    flag[field]=new Array();
    flag[field]['valid']=0;
    flag[field]['active_filter_count']=0;
    for(let key in paymentfilter[field]){
          if(paymentfilter[field][key]['check']>0){
              flag[field]['active_filter_count']++;
              if(key==item[field]) {
                  flag[field]['valid']++;
                  break;
              }
          }
    }
  }
  for(let field in flag){
    if(flag[field]['active_filter_count']>0){
      if(flag[field]['valid']>0){
        ret++;
      }
      else {
        ret=0;
        break;
      }
    }
    else {
      ret=1;
    }
  }
  return ret&&filter_text_ret;
}

function print_paymentfilter(field_name) {
  var table='<div><button class="btn btn-primary" onclick="print_paymentes();">Применить</button>';
  table+='<button class="btn btn-default pull-right" onclick="clear_paymentfilter_by_name(\''+field_name+'\');">Очистить</button></div>';
  table+='<div class="search_filter_div"><div class = "filter_header">';
  table+='';
  table+='</div><table class="table">';
  // filter[tab][field_name].forEach(function(val,key){
  //  table+='<tr><td><input type="checkbox"></td><td>'+key+'</td></tr>'
//  });
  sort_paymentfilter(field_name);
  for(var key in paymentfilter[field_name]) {
    if (key.length != 0)  {
      table+='<tr><td><input type="checkbox" onclick="set_paymentfilter(\''+field_name+'\',\''+btoa(toBinary(key))+'\');"';
      if (typeof(paymentfilter[field_name][key])== "number" && paymentfilter[field_name][key]==1)
        table+=' checked="checked"';
      if (paymentfilter[field_name][key]['check']) {
        table+=' checked="checked"';
      }
      if (typeof(paymentfilter[field_name][key])== "number" ) {
        table+='></td><td>'+key+'</td></tr>';
      }
      else {
        table+='></td><td>'+paymentfilter[field_name][key]['print']+'</td></tr>';
      }
    }
  }
  table += "</table></div>";
  create_window("paymentfilter_div_"+field_name,"Выберите элементы фильтра",'select_paymentfilter_'+field_name,table);
  //sort_filter(field_name,tab);
}

function clear_paymentfilter_by_name(field,print) {
  if(typeof(paymentfilter)!="undefined") {
    $("body").css("cursor", "progress");
    //if(filter[tab]['filter_text']=="")
    //  filter[tab]['filter_count']=0;
      if(typeof(paymentfilter['filter_counter'])=="undefined") paymentfilter['filter_counter']={};
      paymentfilter['filter_counter'][field]=0;
      if(field!="filter_counter"){
        Object.keys(paymentfilter[field]).forEach(function(filter_key){
          if(field=="count" || field=="time") {
            if(paymentfilter[field][filter_key]==1) {
              paymentfilter[field][filter_key]=0;
            }
          }
          else
            if(paymentfilter[field][filter_key]['check']==1) {
              paymentfilter[field][filter_key]['check']=0;
            }
        });
    }
    if(typeof(print)=="undefined" || print===1) print_paymentes();
    $("body").css("cursor", "default");
  }
}

function set_paymentfilter(field_name, key) {
  key=fromBinary(atob(key));
  if(typeof(paymentfilter['filter_count'])=="undefined") paymentfilter['filter_count']=0;
  if(typeof(paymentfilter['filter_counter'])=="undefined") paymentfilter['filter_counter']={};
  if(typeof(paymentfilter['filter_counter'][field_name])=="undefined") paymentfilter['filter_counter'][field_name]=0;
  if(typeof(paymentfilter[field_name][key])=="undefined") {
    if(field_name=="count" || field_name=="time") paymentfilter[field_name][key]=0;
    else paymentfilter[field_name][key]=new Array();
  }
  if(typeof(paymentfilter[field_name][key])=="number"){
    if (paymentfilter[field_name][key]){
      paymentfilter[field_name][key] = 0;
      paymentfilter['filter_counter'][field_name]--;
      paymentfilter['filter_count']--;
    }
    else {
      paymentfilter[field_name][key] = 1;
      paymentfilter['filter_counter'][field_name]++;
      paymentfilter['filter_count']++;

    }
  }
  else {
    if (paymentfilter[field_name][key]['check']){
      paymentfilter[field_name][key]['check'] = 0;
      paymentfilter['filter_count']--;
      paymentfilter['filter_counter'][field_name]--;
    }
    else {
      paymentfilter[field_name][key]['check'] = 1;
      paymentfilter['filter_count']++;
      paymentfilter['filter_counter'][field_name]++;
    }
  }
  //items_to_table(tab);
  //filter[tab][field_name][key] = (filter[tab][field_name][key] == 1)?0:1;
}

function sort_paymentfilter(field_name){
    var items=paymentfilter[field_name];
    paymentfilter[field_name]={};
    Object.keys(items).sort().forEach(function(key){
      paymentfilter[field_name][key]=items[key];
    });
  }

function make_paymentes_header(field,field_name){
  var table='';
  if(typeof(paymentfilter['filter_counter'])!="undefined" && paymentfilter['filter_counter'][field] > 0) table+='<th nowrap>';
  else table+='<th class="filter-css" nowrap>';
  if(typeof(paymentes["sort_field"])!="undefined" && paymentes["sort_field"]==field) {
    table+=""
    if(paymentes["sort_direction"]=="up") {
      table+="<span><a onclick='sort_paymentes_desc(\""+field+"\");'>"+field_name+" &#9650</a></span>";
      table+="\t";
      if (typeof(paymentfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_paymentfilter(\''+field+'\');">';
        table+='<svg class = "filt" viewBox="0 0 80 90" ';
        if(paymentfilter['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+=' focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div  id="select_paymentfilter_'+field+'"></div>';
        }
    }
    else {
      table+="<a onclick='sort_paymentes(\""+field+"\");'>"+field_name+" &#9660</a> ";
      table+="\t";
      if (typeof(paymentfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_paymentfilter(\''+field+'\');">';
        table+='<svg viewBox="0 0 80 90" ';
        if(paymentfilter['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+= 'focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div id="select_paymentfilter_'+field+'"></div>';
      }
    }
  }
  else {
    table+="<a class='clickable' onclick='sort_paymentes(\""+field+"\")'>"+field_name+"";
    table+="\t";
    if (typeof(paymentfilter[field]) != "undefined") {
      table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_paymentfilter(\''+field+'\');">';
      table+='<svg viewBox="0 0 80 90" ';
      if(typeof(paymentfilter['filter_counter']) != "undefined" && paymentfilter['filter_counter'][field] > 0 && typeof(paymentfilter['filter_counter'][field]) != "undefined") {
        table+='class="opacity-06"';
      }
      else {
        table+='class="opacity"';
      }
      table+='focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
      table+="</a>";
      table+='<div id="select_paymentfilter_'+field+'"></div>';
    }
  }

  table+="</th>";
  return table;
}

function sort_paymentes(s){
  //      items.sort();
  paymentes["sort_field"]=s;
  paymentes["sort_direction"]="up";
      //var items=all_items[tab];
      paymentes.sort(function(a, b) {
          if (s=="company_name") { if(a.company_name == b.company_name) return 0; else { if(a.company_name > b.company_name) return 1; else if(a.company_name < b.company_name) return -1; }}
          if (s=="payment_type") { if(a.payment_type == b.payment_type) return 0; else { if(a.payment_type > b.payment_type) return 1; else if(a.payment_type < b.payment_type) return -1; }}
          if (s=="zakaz_id") { if(a.zakaz_id == b.zakaz_id) return 0; else { if(a.zakaz_id > b.zakaz_id) return 1; else if(a.zakaz_id < b.zakaz_id) return -1; }}
          if (s=="lastname") { if(a.lastname == b.lastname) return 0; else { if(a.lastname > b.lastname) return 1; else if(a.lastname < b.lastname) return -1; }}
          if (s=="id") { return a.id-b.id; }
      });
      //alert(items.join('\n'));
      var table=print_paymentes();
      $("#paymentes_list").html(table);
  }

function sort_paymentes_desc(s){
  //      items.sort();
  paymentes["sort_field"]=s;
  paymentes["sort_direction"]="down";
      //var items=all_items[tab];
      paymentes.sort(function(a, b) {
          if (s=="company_name") { if(b.company_name == a.company_name) return 0; else { if(b.company_name > a.company_name) return 1; else if(b.company_name < a.company_name) return -1; }}
          if (s=="payment_type") { if(b.payment_type == a.payment_type) return 0; else { if(b.payment_type > a.payment_type) return 1; else if(b.payment_type < a.payment_type) return -1; }}
          if (s=="zakaz_id") { if(b.zakaz_id == a.zakaz_id) return 0; else { if(b.zakaz_id > a.zakaz_id) return 1; else if(b.zakaz_id < a.zakaz_id) return -1; }}
          if (s=="lastname") { if(b.lastname == a.lastname) return 0; else { if(b.lastname > a.lastname) return 1; else if(b.lastname < a.lastname) return -1; }}
          if (s=="id") { return b.id-a.id; }
      });
      //alert(items.join('\n'));
      var table=print_paymentes();
      $("#paymentes_list").html(table);
  }

  function make_paymentes_header(field,field_name){
    var table='';
    if(typeof(paymentfilter['filter_counter'])!="undefined" && paymentfilter['filter_counter'][field] > 0) table+='<th nowrap>';
    else table+='<th class="filter-css" nowrap>';
    if(typeof(paymentes["sort_field"])!="undefined" && paymentes["sort_field"]==field) {
      table+=""
      if(paymentes["sort_direction"]=="up") {
        table+="<span><a onclick='sort_paymentes_desc(\""+field+"\");'>"+field_name+" &#9650</a></span>";
        table+="\t";
        if (typeof(paymentfilter[field]) != "undefined") {
          table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_paymentfilter(\''+field+'\');">';
          table+='<svg class = "filt" viewBox="0 0 80 90" ';
          if(paymentfilter['filter_counter'][field] > 0) {
            table+='class="opacity-06"';
          }
          else {
            table+='class="opacity"';
          }
          table+=' focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
          table+="</a>";
          table+='<div  id="select_paymentfilter_'+field+'"></div>';
          }
      }
      else {
        table+="<a onclick='sort_paymentes(\""+field+"\");'>"+field_name+" &#9660</a> ";
        table+="\t";
        if (typeof(paymentfilter[field]) != "undefined") {
          table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_paymentfilter(\''+field+'\');">';
          table+='<svg viewBox="0 0 80 90" ';
          if(paymentfilter['filter_counter'][field] > 0) {
            table+='class="opacity-06"';
          }
          else {
            table+='class="opacity"';
          }
          table+= 'focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
          table+="</a>";
          table+='<div id="select_paymentfilter_'+field+'"></div>';
        }
      }
    }
    else {
      table+="<a class='clickable' onclick='sort_paymentes(\""+field+"\")'>"+field_name+"";
      table+="\t";
      if (typeof(paymentfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_paymentfilter(\''+field+'\');">';
        table+='<svg viewBox="0 0 80 90" ';
        if(typeof(paymentfilter['filter_counter']) != "undefined" && paymentfilter['filter_counter'][field] > 0 && typeof(paymentfilter['filter_counter'][field]) != "undefined") {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+='focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div id="select_paymentfilter_'+field+'"></div>';
      }
    }
  
    table+="</th>";
    return table;
  }

  function delete_payment(id,type){
    var send=[];
    send['payment_id']=id;
      api_query_array("/api/index.php",send,"get_payment").then(function(data){
        if(parseInt(data.payment.payment_type)==6){
          if(parseInt(data.payment.zakaz_id)>0){
            var send1=[]; send1['zakaz_id']=data.payment.zakaz_id;
            api_query_array("/api/index.php",send1,"cancel_sber_pay").then(function(data1){
              if(data1.status=="ok"){
                if(typeof(data1.sber_responce)!="undefined"){
                  delete_payment_from_base(send,type);
                }
                if(typeof(data1.tinkoff_response)!="undefined" && data1.tinkoff_response.Status=="REFUNDED"){
                  delete_payment_from_base(send,type);
                }
                else {
                  if(typeof(data1.tinkoff_response)!="undefined"){
                    bootbox.alert(data1.tinkoff_response.Message);
                  }
                }
              }
            });
          }
          else {
            delete_payment_from_base(send,type);
          }
        }
        else {
          delete_payment_from_base(send,type);
        }
      });
    
  }

  function delete_payment_from_base(send,type){
    api_query_array("/api/index.php",send,"delete_payment").then(function(data){
      if(data.status=="ok"){
        switch(type){
          case "c": get_payments(); break;
          case "d": get_delivery_payments(); break;
          case "r": get_return_payments(); break;
        }
      }
    });
  }

  function edit_cash_desk(id){
    var send=[];
    send['cash_desk_id']=id;
    var win_name='';
    api_query_array("/api/index.php",send,"get_cash_desk").then(function(data){
      if(id==0) {
        data.cash_desk={id:0,name:"",user_id:0,summ:0,user_name:""};
        win_name="Создание кассы наличных";
      }
      else {
        win_name="Редактирование кассы наличных";
      }
      var table='<form id="edit_cash_desk_form"><input type="hidden" name="cash_desk_id" value="'+data.cash_desk.id+'">\
        <table class="table"><tbody>';
      table+='<tr><td>Наименование кассы: </td><td><input type="text" name="name" id="cash_desk_name" value="'+data.cash_desk.name+'" class="form-control"></td></tr>';
      table+='<tr><td>Сотрудник: </td><td><select name="user_id" id="cash_desk_user_id" class="form-control">';
      table+='<option value="0"';
      if(data.cash_desk.user_id=="0") table+=' selected="selected"';
      table+='>Выберите сотрудника</option>';
      table+='<option value="-1"';
      if(data.cash_desk.user_id=="-1") table+=' selected="selected"';
      table+='>Все сотрудники (одна касса)</option>';
      for (let i in data.users){
        table+='<option value="'+data.users[i].id+'"';
        if(data.users[i].id==data.cash_desk.user_id) table+=' selected="selected"';
        table+='>'+data.users[i].lastname+' '+data.users[i].name+'</option>';
      }
      table+='</select></td></tr>';
      table+='<tr><td><button type="button" id="cash_desk_save_btn" onclick="save_cash_desk()" class="btn btn-primary btn-sm">Сохранить</button> </td>\
      <td><button type="button" id="cash_desk_cancel_btn" onclick="$(\'#cash_desk_0\').html(\'\')" class="btn btn-default btn-sm pull-right">Отмена</button></td></tr>';
      table+='</tbody></table></form>';
      create_window_centered_blue("cash_desk_0_div",win_name,"cash_desk_0",table);
    })
  }

  function get_cash_desks(){
    var win_name='';
    api_query("/api/index.php","some_form","get_cash_desks").then(function(data){
      var table='<div id="new_encashment"></div><div id="cash_desk_history"></div><div id="cash_desk_sverka"></div><table class="table table-hover"><thead><tr><th>Наименование</th><th>Кассир</th><th>Дата создания</th><th>Остаток</th><th></th></tr></thead><tbody>';
      for(let i in data.cash_desks){
        table+='<tr><td>'+data.cash_desks[i].name+'</td><td>';
        if(parseInt(data.cash_desks[i].user_id)>0) table+=data.users[data.cash_desks[i].user_id].lastname+' '+data.users[data.cash_desks[i].user_id].name;
        else table+='Все сотрудники (одна касса)';
        table+='</td>\
        <td>'+convertTZ(data.cash_desks[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td><a onclick="cash_desk_history('+data.cash_desks[i].id+')">'+data.cash_desks[i].summ+'</a></td>\
        <td><button class="btn btn-sm btn-success" onclick="new_encashment('+data.cash_desks[i].id+','+data.cash_desks[i].summ+');" title="Инкассация">Инкассация</button> \
        <a onclick="edit_cash_desk('+data.cash_desks[i].id+');" title="Редактировать кассу"><img src="/new_images/edit.svg" class="menuimg"></a>\
        <a onclick="bootbox.confirm(\'Вы точно хотите удалить инкассацию?\',function(result){ if(result) delete_cash_desk('+data.cash_desks[i].id+');})"><img src="/new_images/garbage.svg" class="menuimg"></a>\
        </td></tr>';
      }
      table+='</tbody></table>';
      $("#cash_desks_list").html(table);
      //create_window_centered_blue("cash_desk_o_div",win_name,"cash_desk_0",table);
    })
  }

  function cash_desk_history(id){
    var send=[];
    send['cash_desk_id']=id;
    send['date_from']=$("#cash_desk_filter_date_from").val();
    send['date_to']=$("#cash_desk_filter_date_to").val();
    api_query_array("/api/index.php",send,"get_cash_desk_history").then(function(data){
      if(data.status=="ok"){
        var table='<table class="table table-hover"><thead><tr><th>№</th><th>Дата</th><th>Сумма (остаток)</th></tr></thead><tbody>';
        for (var i of data.cash_desk_history){
          table+='<tr onclick="cash_desk_sverka('+i.cashdesk_id+',\''+i.date+'\')"><td>'+i.cashdesk_id+'</td><td>'+i.date+'</td><td style="text-align:right">'+i.summ+'</td></tr>';
        }
        table+='</tbody></table>';
        create_window_centered_blue("cash_desk_history_div","История кассы наличных","cash_desk_history",table);
      }
    })
  }

  function cash_desk_sverka(id,date){
    var send=[];
    send['cash_desk_id']=id;
    send['date']=date;
    api_query_array("/api/index.php",send,"get_cash_desk_sverka").then(function(data){
      if(data.status=="ok"){
        var table='<table class="table table-hover"><thead><tr><th>№</th><th>Дата</th><th>Наименование</th><th>Приход</th><th>Расход</th></tr></thead><tbody>';
        var summ_in=0,summ_out=0;
        table+='<tr><td colspan="4">Баланс кассы на начало дня</td><td><b>'+data.cashdesk_history.yesterday.summ+'</b></td></tr>';
        for (var i of data.payments){
          switch(i.payment_direction){
            case "1": table+='<tr><td>'+i.id+'</td><td>'+i.create_date+'</td><td>Оплата клиента</td><td style="text-align:right">'+i.summ+'</td><td></td></tr>'; summ_in+=parseFloat(i.summ); break;
            case "2": table+='<tr><td>'+i.id+'</td><td>'+i.create_date+'</td><td>Оплата поставщику</td><td></td><td style="text-align:right">'+i.summ+'</td></tr>'; summ_out+=parseFloat(i.summ); break;
            case "3": table+='<tr><td>'+i.id+'</td><td>'+i.create_date+'</td><td>Возврат клиенту</td><td></td><td style="text-align:right">'+i.summ+'</td></tr>'; summ_out+=parseFloat(i.summ); break;
          }
        }
        for (var i of data.PKOs){
            table+='<tr><td>'+i.id+'</td><td>'+i.create_date+'</td><td>Приходный кассовый ордер</td><td style="text-align:right">'+i.summ+'</td><td></td></tr>'; summ_in+=parseFloat(i.summ);
        }
        for (var r of data.RKOs){
          table+='<tr><td>'+r.id+'</td><td>'+r.create_date+'</td><td>Расходный кассовый ордер</td><td></td><td style="text-align:right">'+r.summ+'</td></tr>'; summ_out+=parseFloat(r.summ);
        }
        table+='<tr><td>Итого</td><td></td><td></td><td>'+summ_in+'</td><td>'+summ_out+'</td></tr>';
        var razn=(summ_in-summ_out);
        table+='<tr><td>Разница</td><td></td><td></td><td></td><td style="color:'+(razn<0?"red":"green")+'; text-align:right;">'+(summ_in-summ_out)+'</td></tr>';
        table+='<tr><td colspan="4">Баланс кассы на конец дня</td><td style="text-align:right"><b>'+data.cashdesk_history.today.summ+'</b></td></tr>';
        var klas='';
        if(Math.round(parseFloat(data.cashdesk_history.yesterday.summ)+razn)!=Math.round(parseFloat(data.cashdesk_history.today.summ))){
          klas="blink_me_red";
        }
        else klas='';
        table+='<tr><td colspan="4">Расчетный баланс кассы на конец дня</td><td style="text-align:right"><b class="'+klas+'">'+(parseFloat(data.cashdesk_history.yesterday.summ)+razn).toFixed(2)+'</b></td></tr>';
        if(Math.round(parseFloat(data.cashdesk_history.yesterday.summ)+razn)!=Math.round(parseFloat(data.cashdesk_history.today.summ))){
          table+='<tr><td colspan="5" style="text-align:center"><button type="button" class="btn btn-default">Исправить баланс кассы</button></td></tr>';
        }
        table+='</tbody></table>';
        create_window_centered_blue("cash_desk_sverka_div","Сверка кассы наличных на "+date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1"),"cash_desk_sverka",table);
        $("#cash_desk_sverka_div").css("left","40px");
      }
    })
  }

  function save_cash_desk(){
    api_query("/api/index.php","edit_cash_desk_form","save_cash_desk").then(function(data){
      if(data.status=="ok") {
        get_cash_desks();
        $("#cash_desk_0").html('');
      }
    })
  }

  function get_encashments(){
    var win_name='';
    var send=[];
    send['encashment_filter_date_from']=$("#encashment_filter_date_from").val();
    send['encashment_filter_date_to']=$("#encashment_filter_date_to").val();
    api_query_array("/api/index.php",send,"get_encashments").then(function(data){
      var table='<div id="edit_encashment"></div><table class="table table-hover"><thead><tr><th>Дата создания</th><th>Сумма</th><th>Деньги сдал</th><th>Принята сумма</th><th>Деньги принял</th><th>Расхождение</th><th></th></tr></thead><tbody>';
      var summ=0;
      for(let i in data.encashments){
        table+='<tr><td>'+convertTZ(data.encashments[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td>'+data.encashments[i].summ+'</td>\
        <td>'+data.users[data.encashments[i].user_id].lastname+' '+data.users[data.encashments[i].user_id].name+'</td><td>'+data.encashments[i].confirmed_summ+'</td>';
        var diff=data.encashments[i].confirmed_summ-data.encashments[i].summ;
        if(parseInt(data.encashments[i].confirmed_by)>0)
          table+='<td>'+data.users[data.encashments[i].confirmed_by].lastname+' '+data.users[data.encashments[i].confirmed_by].name+'</td><td>'+(diff<0?'<span style="color:red">'+diff+'</span>':'<span style="color:green">'+diff+'</span>')+'</td>';
        else table+='<td></td><td>0</td>';
        table+='<td><a onclick="edit_encashment('+data.encashments[i].id+');" title="Редактировать инкассацию"><img src="/new_images/edit.svg" class="menuimg"></a> \
        <a onclick="bootbox.confirm(\'Вы точно хотите удалить инкассацию?\',function(result){ if(result) delete_encashment('+data.encashments[i].id+');})" title="Удалить инкассацию"><img src="/new_images/garbage.svg" class="menuimg"></a></td></tr>';
        summ+=parseFloat(data.encashments[i].summ);
      }
      table+='<tr><td>Итого</td><td>'+summ+'</td><td colspan="5"></td></tr>';
      table+='</tbody></table>';
      $("#encashments_list").html(table);
      //create_window_centered_blue("cash_desk_o_div",win_name,"cash_desk_0",table);
    })
  }

  

  function new_encashment(cashdesk_id,cashdesk_summ){
    var table='<form id="new_encashment_form"><table class="table"><tbody>';
    table+='<tr><td>Сумма инкасации: </td><td><input type="text" name="summ" id="encashment_summ" class="form-control" onchange="change_encashment_sums();">\
    <input type="hidden" name="from_cashdesk" value="'+cashdesk_id+'"></td></tr>';
    table+='<tr><td>Остаток в кассе: </td><td><input type="text" name="remain" id="cashdesk_remain" readonly class="form-control" value="'+cashdesk_summ+'" >\
    <input type="hidden" name="cashdesk_summ" value="'+cashdesk_summ+'"></td></tr>';
    table+='<tr><td><button class="btn btn-sm btn-primary" type="button" onclick="save_encashment();">Сохранить</button></td><td></td></tr>';
    table+='</tbody></table></form>';
    create_window_centered_blue("new_encashment_div","Инкассация","new_encashment",table);
  }

  function save_encashment(encashment_id=0){
    var send=[];
    send['summ']=$("#new_encashment_form input[name=summ]").val();
    send['cashdesk_summ']=$("#new_encashment_form input[name=cashdesk_summ]").val();
    send['encashment_id']=encashment_id;
    send['from_cashdesk']=$("#new_encashment_form input[name=from_cashdesk]").val();
    send['remain']=$("#new_encashment_form input[name=remain]").val();
    send['confirmed_summ']=$("#new_encashment_form input[name=confirmed_summ]").val();
    if(encashment_id==0 && parseFloat(send['cashdesk_summ'])<parseFloat(send['summ'])){
      bootbox.alert("Нельзя отдать больше чем есть на кассе"); return;
    }
    api_query_array("/api/index.php",send,"save_encashment").then(function(data){
      if(data.status=="ok"){
        get_encashments();
        get_cash_desks();
        $("#new_encashment").html('');
      }
    })
  }

  function change_encashment_sums(){
    var encasment_summ=$("#new_encashment_form input[name=summ]").val();
    var cashdesk_summ=$("#new_encashment_form input[name=cashdesk_summ]").val();
    if(parseFloat(cashdesk_summ)<parseFloat(encasment_summ)){
      bootbox.alert("Нельзя отдать больше чем есть на кассе"); return;
    }
    $("#new_encashment_form input[name=remain]").val(cashdesk_summ-encasment_summ);
  }

  function edit_encashment(encashment_id){
    var send=[];
    send['encashment_id']=encashment_id;
    api_query_array("/api/index.php",send,"get_encashment").then(function(data){
      var table='<form id="new_encashment_form"><table class="table"><tbody>';
      table+='<tr><td>Сумма инкасации: </td><td>\
      <input type="text" name="summ" id="encashment_summ" class="form-control" value="'+data.encashment.summ+'" readonly>\
      <input type="hidden" name="encashment_id" value="'+encashment_id+'"><input type="hidden" name="from_cashdesk" value="'+data.encashment.from_cashdesk+'"></td></tr>';
      table+='<tr><td>Остаток в кассе: </td><td><input type="text" name="remain" id="cashdesk_remain" readonly class="form-control" value="'+data.encashment.remain+'" >\
      <input type="hidden" name="cashdesk_summ" value="'+data.encashment.remain+'"></td></tr>';
      table+='<tr><td>Подтвержденная сумма: </td><td>\
      <input type="text" name="confirmed_summ" id="confirmed_summ" class="form-control" value="'+data.encashment.confirmed_summ+'">\
      </td></tr>';
      table+='<tr><td><button class="btn btn-sm btn-primary" type="button" onclick="save_encashment('+encashment_id+');">Сохранить</button></td><td></td></tr>';
      table+='</tbody></table></form>';
      $("#edit_encashment").html('');
      create_window_centered_blue("edit_encashment_div","Инкассация","edit_encashment",table);
    })
  }

  function delete_encashment(encashment_id){
    var send=[];
    send['encashment_id']=encashment_id;
    api_query_array("/api/index.php",send,"delete_encashment").then(function(data){
      if(data.status=="ok"){
        get_encashments();
        get_cash_desks();
        //$("#new_encashment").html('');
      }
    })
  }

  function delete_cash_desk(cashdesk_id){
    var send=[];
    send['cashdesk_id']=cashdesk_id;
    api_query_array("/api/index.php",send,"delete_cash_desk").then(function(data){
      if(data.status=="ok"){
        get_cash_desks();
        //$("#new_encashment").html('');
      }
    })
  }

  function get_RKOs(){
    var win_name='';
    var send=[];
    send['date_from']=$("#RKO_filter_date_from").val();
    send['date_to']=$("#RKO_filter_date_to").val();
    api_query_array("/api/index.php",send,"get_RKOs").then(function(data){
      var sum=0;
      var table='<div id="edit_RKO"></div><table class="table table-hover"><thead>\
      <tr><th>Дата создания</th><th>Сумма</th><th>Деньги отпустил</th><th>Описание</th><th>Назначение платежа</th><th></th></tr></thead><tbody>';
      for(let i in data.RKOs){
        table+='<tr><td>'+convertTZ(data.RKOs[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td>'+data.RKOs[i].summ+'</td>\
        <td>'+data.users[data.RKOs[i].user_id].lastname+' '+data.users[data.RKOs[i].user_id].name+'</td><td>'+data.RKOs[i].descr+'</td>';
        table+='<td>'+data.RKOs[i].payment_reason+'</td><td></td>';
        table+='<td><a onclick="edit_RKO('+data.RKOs[i].id+');" title="Редактировать расходный кассовый ордер"><img src="/new_images/edit.svg" class="menuimg"></a> \
        <a onclick="bootbox.confirm(\'Вы точно хотите удалить расходно кассовый ордер?\',function(result){ if(result) delete_RKO('+data.RKOs[i].id+');})" title="Удалить РКО"><img src="/new_images/garbage.svg" class="menuimg"></a></td></tr>';
        sum+=parseFloat(data.RKOs[i].summ);
      }
      table+='<tr><td><b>Итого</b></td><td><b>'+sum+'</b></td><td colspan="4"></td></tr>';
      table+='</tbody></table>';
      $("#RKOs_list").html(table);
      $("#RKO_filter_date_to").val(data.date_to);
      $("#RKO_filter_date_from").val(data.date_from);
      //create_window_centered_blue("cash_desk_o_div",win_name,"cash_desk_0",table);
    })
  }

  function new_RKO(){
    api_query("/api/index.php","some_form","get_cash_desks").then(function(data){
      var table='<form id="new_RKO_form"><table class="table"><tbody>';
      table+='<tr><td>Выберите кассу</td><td>';
      table+='<select name="from_cashdesk" class="form-control">';
      for(var i in data.cash_desks){
        table+='<option value="'+data.cash_desks[i].id+'">'+data.cash_desks[i].name+'</option>';
      }
      if(data.cash_desks.length>0){
        cashdesk_id=data.cash_desks[0].id;
        cashdesk_summ=data.cash_desks[0].summ;
      }
      else {
        cashdesk_id=0;
        cashdesk_summ=0;
      }
      table+='</select></td></tr>';
      table+='<tr><td>Сумма расхода: </td><td>\
      <input type="text" name="summ" id="RKO_summ" class="form-control" value="0" onchange="change_RKO_sums();">\
      </td></tr>';
      table+='<tr><td>Остаток в кассе: </td><td><input type="text" name="remain" id="RKO_cashdesk_remain" readonly class="form-control" value="'+cashdesk_summ+'" >\
      <input type="hidden" name="cashdesk_summ" value="'+cashdesk_summ+'"></td></tr>';
      table+='<tr><td>Описание: </td><td>\
      <input type="text" name="descr" id="RKO_descr" class="form-control" value="">\
      </td></tr>\
      <tr><td>Назначение платежа: </td><td>\
      <input type="text" name="payment_reason" id="RKO_payment_reason" class="form-control" value="">\
      </td></tr>';
      table+='<tr><td><button class="btn btn-sm btn-primary" type="button" onclick="save_RKO();">Сохранить</button></td><td></td></tr>';
      table+='</tbody></table></form>';
      $("#edit_RKO").html('');
      create_window_centered_blue("edit_RKO_div","Расходный ордер","edit_RKO",table);
    });
  }

  function edit_RKO(RKO_id){
    var send=[];
    send['RKO_id']=RKO_id;
    api_query_array("/api/index.php",send,"get_RKO").then(function(data){
      var table='<form id="new_RKO_form"><table class="table"><tbody>';
      table+='<tr><td>Сумма расхода: </td><td>\
      <input type="text" name="summ" id="RKO_summ" class="form-control" value="'+data.RKO.summ+'"  onchange="change_RKO_sums('+data.RKO.summ+');">\
      <input type="hidden" name="RKO_id" value="'+RKO_id+'"><input type="hidden" name="from_cashdesk" value="'+data.RKO.from_cashdesk+'"></td></tr>';
      table+='<tr><td>Остаток в кассе: </td><td><input type="text" name="remain" id="RKO_cashdesk_remain" readonly class="form-control" value="'+data.RKO.remain+'" >\
      <input type="hidden" name="cashdesk_summ" value="'+data.RKO.remain+'"></td></tr>';
      table+='<tr><td>Описание: </td><td>\
      <input type="text" name="descr" id="RKO_descr" class="form-control" value="'+data.RKO.descr+'">\
      </td></tr><tr><td>Назначение платежа: </td><td>\
      <input type="text" name="payment_reason" id="RKO_payment_reason" class="form-control" value="'+data.RKO.payment_reason+'">\
      </td></tr>';
      table+='<tr><td><button class="btn btn-sm btn-primary" type="button" onclick="save_RKO('+RKO_id+');">Сохранить</button></td><td></td></tr>';
      table+='</tbody></table></form>';
      $("#edit_RKO").html('');
      create_window_centered_blue("edit_RKO_div","Расходный ордер","edit_RKO",table);
    })
  }

  function save_RKO(RKO_id=0){
    var send=[];
    send['summ']=$("#new_RKO_form input[name=summ]").val();
    send['cashdesk_summ']=$("#new_RKO_form input[name=cashdesk_summ]").val();
    send['RKO_id']=RKO_id;
    if(RKO_id==0)
      send['from_cashdesk']=$("#new_RKO_form select[name=from_cashdesk]").val();
    else 
      send['from_cashdesk']=$("#new_RKO_form input[name=from_cashdesk]").val();
    send['remain']=$("#new_RKO_form input[name=remain]").val();
    send['descr']=$("#new_RKO_form input[name=descr]").val();
    send['payment_reason']=$("#new_RKO_form input[name=payment_reason]").val();
    if(RKO_id==0 && parseFloat(send['cashdesk_summ'])<parseFloat(send['summ'])){
      bootbox.alert("Нельзя отдать больше чем есть на кассе"); return;
    }
    api_query_array("/api/index.php",send,"save_RKO").then(function(data){
      if(data.status=="ok"){
        get_RKOs();
        get_cash_desks();
        $("#edit_RKO").html('');
      }
    })
  }

  function change_RKO_sums(old_sum=0){
    var RKO_summ=$("#new_RKO_form input[name=summ]").val();
    var cashdesk_summ=$("#new_RKO_form input[name=cashdesk_summ]").val();
    if(cashdesk_summ<RKO_summ){
      bootbox.alert("Нельзя отдать больше чем есть на кассе"); return;
    }
    $("#new_RKO_form input[name=remain]").val(cashdesk_summ-(RKO_summ-old_sum));
  }

  function delete_RKO(RKO_id){
    var send=[];
    send['RKO_id']=RKO_id;
    api_query_array("/api/index.php",send,"delete_RKO").then(function(data){
      if(data.status=="ok"){
        get_RKOs();
        get_cash_desks();
        //$("#new_encashment").html('');
      }
    })
  }

  function get_PKOs(){
    var win_name='';
    var send=[];
    send['date_from']=$("#PKO_filter_date_from").val();
    send['date_to']=$("#PKO_filter_date_to").val();
    api_query_array("/api/index.php",send,"get_PKOs").then(function(data){
      var sum=0;
      var table='<div id="edit_PKO"></div><table class="table table-hover"><thead>\
      <tr><th>Дата создания</th><th>Сумма</th><th>Деньги положил</th><th>Описание</th><th>Назначение платежа</th><th></th></tr></thead><tbody>';
      for(let i in data.PKOs){
        table+='<tr><td>'+convertTZ(data.PKOs[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td>'+data.PKOs[i].summ+'</td>\
        <td>'+data.users[data.PKOs[i].user_id].lastname+' '+data.users[data.PKOs[i].user_id].name+'</td><td>'+data.PKOs[i].descr+'</td>';
        table+='<td>'+data.PKOs[i].payment_reason+'</td><td></td>';
        table+='<td><a onclick="edit_PKO('+data.PKOs[i].id+');" title="Редактировать приходный кассовый ордер"><img src="/new_images/edit.svg" class="menuimg"></a> \
        <a onclick="bootbox.confirm(\'Вы точно хотите удалить приходный кассовый ордер?\',function(result){ if(result) delete_PKO('+data.PKOs[i].id+');})" title="Удалить ПКО"><img src="/new_images/garbage.svg" class="menuimg"></a></td></tr>';
        sum+=parseFloat(data.PKOs[i].summ);
      }
      table+='<tr><td><b>Итого</b></td><td><b>'+sum+'</b></td><td colspan="4"></td></tr>';
      table+='</tbody></table>';
      $("#PKOs_list").html(table);
      $("#PKO_filter_date_to").val(data.date_to);
      $("#PKO_filter_date_from").val(data.date_from);
      //create_window_centered_blue("cash_desk_o_div",win_name,"cash_desk_0",table);
    })
  }

  function new_PKO(){
    api_query("/api/index.php","some_form","get_cash_desks").then(function(data){
      var table='<form id="new_PKO_form"><table class="table"><tbody>';
      table+='<tr><td>Выберите кассу</td><td>';
      table+='<select name="to_cashdesk" class="form-control">';
      for(var i in data.cash_desks){
        table+='<option value="'+data.cash_desks[i].id+'">'+data.cash_desks[i].name+'</option>';
      }
      if(data.cash_desks.length>0){
        cashdesk_id=data.cash_desks[0].id;
        cashdesk_summ=data.cash_desks[0].summ;
      }
      else {
        cashdesk_id=0;
        cashdesk_summ=0;
      }
      table+='</select></td></tr>';
      table+='<tr><td>Сумма прихода: </td><td>\
      <input type="text" name="summ" id="PKO_summ" class="form-control" value="0" onchange="change_PKO_sums();">\
      </td></tr>';
      table+='<tr><td>Остаток в кассе: </td><td><input type="text" name="remain" id="PKO_cashdesk_remain" readonly class="form-control" value="'+cashdesk_summ+'" >\
      <input type="hidden" name="cashdesk_summ" value="'+cashdesk_summ+'"></td></tr>';
      table+='<tr><td>Описание: </td><td>\
      <input type="text" name="descr" id="PKO_descr" class="form-control" value="">\
      </td></tr>\
      <tr><td>Назначение платежа: </td><td>\
      <input type="text" name="payment_reason" id="PKO_payment_reason" class="form-control" value="">\
      </td></tr>';
      table+='<tr><td><button class="btn btn-sm btn-primary" type="button" onclick="save_PKO();">Сохранить</button></td><td></td></tr>';
      table+='</tbody></table></form>';
      $("#edit_RKO").html('');
      create_window_centered_blue("edit_PKO_div","Расходный ордер","edit_PKO",table);
    });
  }

  function edit_PKO(PKO_id){
    var send=[];
    send['PKO_id']=PKO_id;
    api_query_array("/api/index.php",send,"get_PKO").then(function(data){
      var table='<form id="new_PKO_form"><table class="table"><tbody>';
      table+='<tr><td>Сумма прихода: </td><td>\
      <input type="text" name="summ" id="PKO_summ" class="form-control" value="'+data.PKO.summ+'"  onchange="change_PKO_sums('+data.PKO.summ+');">\
      <input type="hidden" name="PKO_id" value="'+PKO_id+'"><input type="hidden" name="to_cashdesk" value="'+data.PKO.to_cashdesk+'"></td></tr>';
      table+='<tr><td>Остаток в кассе: </td><td><input type="text" name="remain" id="PKO_cashdesk_remain" readonly class="form-control" value="'+data.PKO.remain+'" >\
      <input type="hidden" name="cashdesk_summ" value="'+data.PKO.remain+'"></td></tr>';
      table+='<tr><td>Описание: </td><td>\
      <input type="text" name="descr" id="PKO_descr" class="form-control" value="'+data.PKO.descr+'">\
      </td></tr><!-- tr><td>Назначение платежа: </td><td>\
      <input type="text" name="payment_reason" id="PKO_payment_reason" class="form-control" value="'+data.PKO.payment_reason+'">\
      </td></tr -->';
      table+='<tr><td><button class="btn btn-sm btn-primary" type="button" onclick="save_PKO('+PKO_id+');">Сохранить</button></td><td></td></tr>';
      table+='</tbody></table></form>';
      $("#edit_PKO").html('');
      create_window_centered_blue("edit_PKO_div","Расходный ордер","edit_PKO",table);
    })
  }

  function save_PKO(PKO_id=0){
    var send=[];
    send['summ']=$("#new_PKO_form input[name=summ]").val();
    send['cashdesk_summ']=$("#new_PKO_form input[name=cashdesk_summ]").val();
    send['PKO_id']=PKO_id;
    if(PKO_id==0)
      send['to_cashdesk']=$("#new_PKO_form select[name=to_cashdesk]").val();
    else 
      send['to_cashdesk']=$("#new_PKO_form input[name=to_cashdesk]").val();
    send['remain']=$("#new_PKO_form input[name=remain]").val();
    send['descr']=$("#new_PKO_form input[name=descr]").val();
    send['payment_reason']=$("#new_PKO_form input[name=payment_reason]").val();
    //if(RKO_id==0 && parseFloat(send['cashdesk_summ'])<parseFloat(send['summ'])){
    //  bootbox.alert("Нельзя отдать больше чем есть на кассе"); return;
    //}
    api_query_array("/api/index.php",send,"save_PKO").then(function(data){
      if(data.status=="ok"){
        get_PKOs();
        get_cash_desks();
        $("#edit_PKO").html('');
      }
    })
  }

  function change_PKO_sums(old_sum=0){
    var PKO_summ=$("#new_PKO_form input[name=summ]").val();
    var cashdesk_summ=$("#new_PKO_form input[name=cashdesk_summ]").val();
    $("#new_PKO_form input[name=remain]").val(parseFloat(cashdesk_summ)+(parseFloat(PKO_summ)-parseFloat(old_sum)));
  }

  function delete_PKO(PKO_id){
    var send=[];
    send['PKO_id']=PKO_id;
    api_query_array("/api/index.php",send,"delete_PKO").then(function(data){
      if(data.status=="ok"){
        get_PKOs();
        get_cash_desks();
        //$("#new_encashment").html('');
      }
    })
  }

  function return_payment(payment_id){
    bootbox.confirm("Хотите вернуть деньги клиенту?",function(result){
      if(result){
        var send=[];
        send['payment_id']=payment_id;
          api_query_array("/api/index.php",send,"get_payment").then(function(data){
            if(parseInt(data.payment.payment_type)==6){
              if(parseInt(data.payment.zakaz_id)>0){
                var send1=[]; send1['zakaz_id']=data.payment.zakaz_id;
                api_query_array("/api/index.php",send1,"cancel_sber_pay").then(function(data1){
                  if(data1.status=="ok"){
                    if(typeof(data1.sber_responce)!="undefined"){
                      return_payment_from_base(send);
                    }
                    if(typeof(data1.tinkoff_response)!="undefined" && data1.tinkoff_response.Status=="REFUNDED"){
                      return_payment_from_base(send);
                    }
                    else {
                      if(typeof(data1.tinkoff_response)!="undefined"){
                        bootbox.alert(data1.tinkoff_response.Message);
                      }
                    }
                    if(typeof(data1.ukassa_response)!="undefined" && data1.ukassa_response.status=="succeeded"){
                      return_payment_from_base(send);
                    }
                    else {
                      if(typeof(data1.ukassa_response)!="undefined" && data1.ukassa_response.type=="error"){
                        bootbox.alert(data1.ukassa_response.description);
                      }
                    }
                  }
                });
              }
              else {
                return_payment_from_base(send);
              }
            }
            else {
              if(parseInt(data.payment.payment_type)==2){
                if(parseFloat(data.payment.summ)>parseFloat(data.company_balance)){
                  bootbox.alert("На балансе клиента не хватает денег для возврата");
                  return;
                }
                $.blockUI({ css: { 
                  border: 'none', 
                  padding: '15px', 
                  backgroundColor: '#000', 
                  '-webkit-border-radius': '10px', 
                  '-moz-border-radius': '10px', 
                  opacity: .5, 
                  color: '#fff'
                  },
                  message: 'Производим возврат денег на карту...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
                });
                api_query("/api/index.php","some_form","get_active_kassas").then(function(data1){
                  if(data1.kassas.length>0){
                    var klen=data1.kassas.length;
                    var numDevice=0;
                    for(var i=0; i<klen; i++){
                      if(data1.kassas[i].sklad_id==$("#my_sklad").val()){
                        numDevice=data1.kassas[i].kassa_config.NumDeviceByProcessing;
                        break;
                      }
                    }
                    if(numDevice!=0){
                      var payData={};
                      payData.NumDevice=numDevice;
                      payData.Summ=parseFloat(data.payment.summ).toFixed(2); //send['summ'];
                      payData.UniversalID=data.payment.UniversalID;
                      if(typeof(data.payment.zakaz_id)!="undefined") payData.ReceiptNumber=data.payment.zakaz_id;
                      else payData.ReceiptNumber=0;
                      ReturnPaymentByPaymentCard(payData,send);
                    }
                    else {
                      $.unblockUI();
                      return_payment_from_base(send);
                    }
                  }
                  else {
                    $.unblockUI();
                    return_payment_from_base(send);
                  }
                });
              }
              else {
                return_payment_from_base(send);
              }
            }
          });
        }
    })
  }

  function return_payment_from_base(send){
    api_query_array("/api/index.php",send,"return_payment").then(function(data){
      if(data.status=="ok"){
        //$('#payment_'+payment_id).html('');
                //$('#edit_payment_'+$(payment_form+'[name=payment_id]').val()).html('');
                if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
                  var check_res=RegisterCheck(data.excise_check_data);
                }
                if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
                  var check_res=RegisterCheck(data.check_data);
                }
                get_payments();
      }
    })
  }

  function edit_planned_dealer_payment(id=0){
    var table='';
    if(id==0){
      table+='<form id="planned_dealer_payment_form_'+id+'">';
      table+='<div class="row"><div class="col-sm-6">Наименование планового платежа</div><div class="col-sm-6"><input type="text" class="form-control" name="descr"></div></div>';
      table+='<div class="row"><div class="col-sm-6">Сумма планового платежа</div><div class="col-sm-6"><input type="text" class="form-control" name="summ"></div></div>';
      table+='<div class="row"><div class="col-sm-6">Дата планового платежа</div><div class="col-sm-6"><input type="date" class="form-control" name="payment_date"></div></div>';
      table+='<div class="row"><div class="col-sm-6">Повторять</div><div class="col-sm-6"><input type="checkbox" name="repeatedly" id="planned_payment_repeatedly" onchange="check_planned_payment_repeat()"></div></div>';
      table+='<div class="row" ';
      table+=' style="display:none"'; 
      table+=' id="planned_payment_repeat_period_row"><div class="col-sm-6">Периодичность</div><div class="col-sm-6">\
      <select name="repeat_period" id="planned_payment_repeat_period" class="form-control">\
      <option value="1">Каждый день</option>\
      <option value="2" selected>Каждый месяц</option>\
      <option value="3">Каждый год</option>\
      </select>\
      </div></div>';
      table+='</form>';
      table+='<div class="row"><div class="col-sm-6"><button type="button" onclick="save_planned_dealer_payment()" class="btn btn-primary btn-sm">Сохранить</button></div>\
      <div class="col-sm-6"><button type="button" onclick="close_window(\'planned_dealer_payment_0\')" class="btn btn-default btn-sm pull-right">Отмена</button></div></div>';
      create_window_centered_blue("planned_dealer_payment_0_div","Добавление планового платежа","planned_dealer_payment_0",table);
    }
    else {
      var send=[];
      send['planned_dealer_payment_id']=id;
      api_query_array("/api/index.php",send,"get_planned_dealer_payment").then(function(data){
        let p=data.planned_dealer_payment;
        table+='<form id="planned_dealer_payment_form_'+id+'"><input type="hidden" name="planned_dealer_payment_id" value="'+id+'">';
        table+='<div class="row"><div class="col-sm-6">Наименование планового платежа</div><div class="col-sm-6"><input type="text" class="form-control" name="descr" value="'+p.descr+'"></div></div>';
        table+='<div class="row"><div class="col-sm-6">Сумма планового платежа</div><div class="col-sm-6"><input type="text" class="form-control" name="summ" value="'+p.summ+'"></div></div>';
        table+='<div class="row"><div class="col-sm-6">Дата планового платежа</div><div class="col-sm-6"><input type="date" class="form-control" name="payment_date"  value="'+(p.year+'-'+(p.month<10?"0"+p.month:p.month)+'-'+(p.day_of_month<10?"0"+p.day_of_month:p.day_of_month))+'"></div></div>';
        table+='<div class="row"><div class="col-sm-6">Повторять</div><div class="col-sm-6"><input type="checkbox" name="repeatedly" id="planned_payment_repeatedly" onchange="check_planned_payment_repeat('+id+')"';
        if(p.repeatedly==1) table+=' checked';
        table+='></div></div>';
        table+='<div class="row" ';
        if(p.repeatedly==0) table+=' style="display:none"'; 
        table+=' id="planned_payment_repeat_period_row"><div class="col-sm-6">Периодичность</div><div class="col-sm-6">\
        <select name="repeat_period" id="planned_payment_repeat_period" class="form-control">\
        <option value="1" '+(p.repeat_period==1?"selected":"")+'>Каждый день</option>\
        <option value="2" '+(p.repeat_period==2?"selected":"")+'>Каждый месяц</option>\
        <option value="3" '+(p.repeat_period==3?"selected":"")+'>Каждый год</option>\
        </select>\
        </div></div>';
        table+='</form>';
        table+='<div class="row"><div class="col-sm-6"><button type="button" onclick="save_planned_dealer_payment('+id+')" class="btn btn-primary btn-sm">Сохранить</button></div>\
        <div class="col-sm-6"><button type="button" onclick="close_window(\'planned_dealer_payment_0\')" class="btn btn-default btn-sm pull-right">Отмена</button></div></div>';
        create_window_centered_blue("planned_dealer_payment_0_div","Добавление планового платежа","planned_dealer_payment_0",table);
      });
    }
    
  }

  function check_planned_payment_repeat(id=0){
    if($("#planned_dealer_payment_form_"+id+" #planned_payment_repeatedly").prop("checked")){
      $("#planned_dealer_payment_form_"+id+" #planned_payment_repeat_period_row").css("display","block");
    }
    else {
      $("#planned_dealer_payment_form_"+id+" #planned_payment_repeat_period_row").css("display","none");
    }
  }

  function save_planned_dealer_payment(id=0){
    api_query("/api/index.php","planned_dealer_payment_form_"+id,"save_planned_dealer_payment").then(function(data){
      if(data.status=="ok"){
        $("#planned_dealer_payment_0").html('');
        get_planned_dealer_payments();
      }
    })
  }

  function get_planned_dealer_payments(){
    var send=[];
    send['date_from']=$("#planned_dealer_payment_filter_date_from").val();
    send['date_to']=$("#planned_dealer_payment_filter_date_to").val();
    api_query_array("/api/index.php",send,"get_planned_dealer_payments").then(function(data){
      table='';
      table+='<table class="table table-hover">';
      table+='<tr><th>№ пор.</th><th>№</th><th>Наименование</th><th>сумма</th><th>день</th><th>месяц</th><th>год</th><th>создан</th><th>изменен</th><th>менеджер</th><th></th></tr>';
      let c=0;
      for(let i of data.planned_dealer_payments){
        c++;
        table+='<tr><td>'+c+'</td><td>'+i.id+'</td><td>'+i.descr+'</td><td>'+i.summ+'</td><td>'+((i.repeatedly==1 && i.repeat_period==1)?"ежедневно":i.day_of_month)+'</td>\
        <td>'+((i.repeatedly==1 && i.repeat_period==2)?"ежемесячно":i.month)+'</td>\
        <td>'+((i.repeatedly==1 && i.repeat_period==3)?"ежегодно":i.year)+'</td>\
        <td>'+i.create_date+'</td>\
        <td>'+i.update_date+'</td>\
        <td>'+data.users[i.user_id].name+' '+data.users[i.user_id].lastname+'</td>\
        <td><a onclick="edit_planned_dealer_payment('+i.id+');"><img src="/new_images/edit.svg" class="menuimg"></a>\
        <a onclick="bootbox.confirm(\'Вы точно хотите удалить плановый платеж?\',function(result){ if(result) delete_planned_dealer_payment('+i.id+');})"><img src="/new_images/garbage.svg" class="menuimg"></a>\
        </td></tr>';
      }
      table+='</table>';
      document.getElementById("planned_dealer_payments_list").innerHTML=table;
    })
  }

  function delete_planned_dealer_payment(id){
    var send=[];
    send['planned_dealer_payment_id']=id;
    api_query_array("/api/index.php",send,"delete_planned_dealer_payment").then(function(data){
      if(data.status=="ok"){
        get_planned_dealer_payments();
      }
    })
  }