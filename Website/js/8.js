function edit_service_note(workplace_id,time_hour,time_minute,start_date){
    var send=[];
	if(typeof(start_date)=="undefined"){
    	send['date']=$("#today_date").text();
	}
	else send['date']=start_date;
    send['hour']=time_hour;
    send['minute']=time_minute;
    send['workplace_id']=workplace_id;
    api_query_array("/api/index.php",send,"get_service_note").then(function(data){
        if(typeof(data.service_note.id)!="undefined"){
            print_edit_service_note(data,send);
        }
        else {
            var service_note={id:0,workplace_id:workplace_id};
            print_edit_service_note({service_note: service_note, workplaces:data.workplaces, companys:[],cars:[],employees:[],statuses:data.statuses},send);
        }
    });
}

function get_zakazes_service_note(note_id){ 
	var defer=$.Deferred();
	//zakaz_detail_to_online=new Array();
	$("body").css("cursor","progress");
	$("li a").css("cursor","progress");
	var send=[];
	send['company_id']=$("#service_note_form_"+note_id+" input[name=company_id]").val();
	send['sklad_id']=$("#my_sklad").val();
	api_query_array("/api/index.php",send,"get_zakazes").then(function(data){
	  //if(typeof(data.search_zakaz_date_from)!="undefined") $("#search_zakaz_date_from").val(data.search_zakaz_date_from);
	  //if(typeof(data.search_zakaz_date_to)!="undefined") $("#search_zakaz_date_to").val(data.search_zakaz_date_to);
	  //if(typeof(data.search_zakaz_article)!="undefined") $("#search_zakaz_article").val(data.search_zakaz_article);
	  //if(typeof(data.search_zakaz_client_name)!="undefined") $("#search_zakaz_client_name").val(data.search_zakaz_client_name);
	  //zakazes=data.zakazs;
	  var datalen=data.zakazs.length;
	  var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Дата заказа</th><th>Покупатель</th><th>Статус</th><th>Адрес доставки</th><th>Позиций в заказе</th><th>Сумма заказа</th><th>Коммент.</th><th></th></tr></thead><tbody>";
	  for (var i=0; i<datalen; i++){
		  if(parseInt(data.zakazs[i].status)<70){
			  table += "<tr onclick='change_service_note_zakaz("+data.zakazs[i].id+","+note_id+");'><td>"+data.zakazs[i].id+"</td>";
			  table += "<td>"+convertTZ(data.zakazs[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
			  table += "<td>" + data.zakazs[i].company_name + "</td><td>"+data.zakazs[i].status+"</td><td>"+data.zakazs[i].delivery_address+"</td><td>"+data.zakazs[i].pozition_count+"</td><td>"+data.zakazs[i].zakaz_sum+"</td>";
			  table += "<td>"+data.zakazs[i].comment+"</td>";
			  table += "</tr>";
		  }
	  }
	  table+= "</tbody></table>";
	  create_window("service_note_zakazs_"+note_id+"div","Выберите заказ клиента","service_note_zakazs_"+note_id,table);

	  $("body").css("cursor","default");
	  $("li a").css("cursor","pointer");
	  defer.resolve(data);
	});
	return defer.promise();
}

function change_service_note_zakaz(zakaz_id,note_id){
    $("#service_note_form_"+note_id+" [id=zakaz_id]").val(zakaz_id);
    $("#service_note_form_"+note_id+" [id=zakaz_name]").val((zakaz_id));
    $("#service_note_zakazs_"+note_id).html('');
}

function print_edit_service_note(data,send){
	var service_note=data.service_note;
    if(service_note.id>0) var note_id=service_note.id;
    else note_id=0;
    var table='<form id="service_note_form_'+note_id+'" style="min-width:500px;">\
	<div class="form-group row">\
		<label for="note_car_name" class="col-sm-3 col-form-label">Рабочее место</label>\
	    <div class="col-sm-9">\
			<select name="workplace_id" id="workplace_id" class="form-control">';
			var workplkeys=Object.keys(data.workplaces);
		for(let key in workplkeys){
			let i=workplkeys[key];
			table+='<option value="'+data.workplaces[i].id+'"';
			if(typeof(service_note.workplace_id)!="undefined" && parseInt(service_note.workplace_id)==parseInt(data.workplaces[i].id)){
				table+=' selected="selected"';
			}
			table+='>'+data.workplaces[i].name+'</option>';
		}
	table+='</select></div>\
	</div>\
	<div class="form-group row">\
	    <label for="client_id" class="col-sm-3 col-form-label">Клиент</label>\
	    <div class="col-sm-9">\
		<input type="hidden" name="service_note_id" id="service_note_id"';
        if(typeof(service_note.id)!="undefined") table+=' value="'+service_note.id+'"';
		else table+=' value="'+send['workplace_id']+'"';
        table+='>';
		//table+='<input type="hidden" name="workplace_id" id="workplace_id"';
        //if(typeof(service_note.workplace_id)!="undefined") table+=' value="'+service_note.workplace_id+'"';
		//else table+=' value="'+send['workplace_id']+'"';
        //table+='>';
		table+='<input type="hidden" name="company_id" id="service_company_id"';
        if(typeof(service_note.company_id)!="undefined") table+=' value="'+service_note.company_id+'"';
        table+='>\
		<div class="input-group" style="width:100%"><input class="form-control" type="text" id="service_company_name" name="company_name" placeholder="Выберите клиента" onkeyup="get_note_clients('+note_id+');" onclick="this.value=\'\'; get_note_clients('+note_id+');" autocomplete="off"';
		if(typeof(data.companys[service_note.company_id])!="undefined") table+=' value="'+data.companys[service_note.company_id].name+'"';
		table+='>\
		<span class="input-group-btn"  style="width:10%">\
		<button class="btn btn-default" type="button" onclick="add_new_client_in_service_note('+(parseInt(note_id)>0?parseInt(note_id):0)+');">+</button>\
		</span>\
		</div>\
	    <div id="note_'+note_id+'_clients"></div>\
		</div>\
	</div>\
	<div id="fast_new_client_service" style="position:relative;"></div>\
    <div class="form-group row">\
	    <label for="note_car_name" class="col-sm-3 col-form-label">Автомобиль</label>\
	    <div class="col-sm-9">\
        <input type="hidden" id="note_car_id" name="note_car_id"';
        if(typeof(service_note.car_id)!="undefined") table+=' value="'+service_note.car_id+'"';
        table+='>\
		<input class="form-control" type="text" id="note_car_name" name="note_car_name" placeholder="Выберите автомобиль" onclick="get_note_cars('+note_id+')"';
		if(typeof(data.cars[service_note.car_id])!="undefined") table+=' value="'+data.cars[service_note.car_id].auto_maker_name+' '+data.cars[service_note.car_id].auto_model+' '+data.cars[service_note.car_id].auto_gov_num+'"';
		table+='  autocomplete="off">\
        <div id="note_'+note_id+'_cars"></div>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="mileage" class="col-sm-3 col-form-label">Пробег</label>\
	    <div class="col-sm-9">\
		<input class="form-control" type="text" id="mileage" name="mileage"';
        if(typeof(service_note.mileage)!="undefined") {
            table+=' value="'+service_note.mileage+'"';
        }
        table+='>\
	    </div>\
	</div>\
    <div class="form-group row">\
	    <label for="start_date" class="col-sm-3 col-form-label">Начало работ</label>\
	    <div class="col-sm-9">\
		<input class="form-control" type="datetime-local" id="start_date" name="start_date"';
        if(typeof(service_note.start_date)=="undefined") {
            var date_data=send['date'].split(".");
            table+=' value="'+date_data[2]+'-'+date_data[1]+'-'+date_data[0]+'T'+(send['hour']<10? "0"+send['hour']:send['hour'])+':'+(send['minute']<10? "0"+send['minute']:send['minute'])+'"';
        }
        else table+=' value="'+service_note.start_date.replace(/\s+/g,"T")+'"';
        table+='>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="stop_date" class="col-sm-3 col-form-label">Окончание работ</label>\
	    <div class="col-sm-9">\
		<input class="form-control" type="datetime-local" id="stop_date" name="stop_date"';
        if(typeof(service_note.stop_date)=="undefined") {
            var date_data=send['date'].split(".");
            table+=' value="'+date_data[2]+'-'+date_data[1]+'-'+date_data[0]+'T'+((send['hour']+1)<10? "0"+(send['hour']+1):(send['hour']+1))+':'+(send['minute']<10? "0"+send['minute']:send['minute'])+'"';
        }
        else table+=' value="'+service_note.stop_date.replace(/\s+/g,"T")+'"';
        table+='>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="zakaz_id" class="col-sm-3 col-form-label">№ заказа</label>\
	    <div class="col-sm-9">\
		<div id="service_note_zakazs_'+service_note.id+'"></div>\
		<input type="hidden" name="zakaz_id" id="zakaz_id" value="'+(typeof(service_note.zakaz_id)!="undefined"?service_note.zakaz_id:0)+'">\
		<div class="input-group">\
		<input class="form-control" type="text" id="zakaz_name" name="zakaz_name" onclick="get_zakazes_service_note('+service_note.id+');" value="'+((typeof(service_note.zakaz_id)!="undefined" && parseInt(service_note.zakaz_id)>0)?service_note.zakaz_id:"")+'" placeholder="Нажмите чтобы выбрать заказ">\
	    <span class="input-group-btn"  style="width:20%">\
		<button class="btn btn-default" type="button" onclick="edit_zakaz_in_service_note('+note_id+');" title="Перейти к заказу"><img src="/new_images/edit.svg" style="width:17px;"></button>\
		<button class="btn btn-default" type="button" onclick="add_zakaz_in_service_note('+note_id+');" title="Создать новый заказ">+</button>\
		</span>\
		</div>\
		</div>\
	</div>\
	<div class="form-group row">\
	    <label for="cause" class="col-sm-3 col-form-label">Причина обращения</label>\
	    <div class="col-sm-9">\
		<textarea class="form-control" id="cause" name="cause">';
        if(typeof(service_note.cause)!="undefined") {
            table+=service_note.cause;
        }
        table+='</textarea>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="problems" class="col-sm-3 col-form-label">Проблемы автомобиля</label>\
	    <div class="col-sm-9">\
		<textarea class="form-control" id="problems" name="problems">';
        if(typeof(service_note.problems)!="undefined") {
            table+=service_note.problems;
        }
        table+='</textarea>\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="recommendations" class="col-sm-3 col-form-label">Рекомендации</label>\
	    <div class="col-sm-9">\
		<textarea class="form-control" id="recommendations" name="recommendations">';
        if(typeof(service_note.recommendations)!="undefined") {
            table+=service_note.recommendations;
        }
        table+='</textarea>\
	    </div>\
	</div>';
    /*table+='<div class="form-group row">\
	    <label for="note_employee_name" class="col-sm-3 col-form-label">Исполнитель</label>\
	    <div class="col-sm-9">\
        <input type="hidden" class="form-control" type="text" id="note_employee_id" name="note_employee_id"';
		if(typeof(service_note.employee_id)!="undefined") {
            table+=' value="'+service_note.employee_id+'"';
        }
		table+='>\
		<input type="text" class="form-control" type="text" id="note_employee_name" name="note_employee_name" onkeyup="get_note_employees('+note_id+');" onclick="this.value=\'\'; get_note_employees('+note_id+');" autocomplete="off"';
		if(parseInt(service_note.employee_id)>0 && typeof(data.employees[service_note.employee_id])!="undefined") 
			table+=' value="'+data.employees[service_note.employee_id].lastname+' '+data.employees[service_note.employee_id].name+' '+data.employees[service_note.employee_id].surname+'"';
		table+='>\
	    <div id="note_employees_'+note_id+'"></div>\
		</div>\
	</div>';*/
	table+='\
	<div class="form-group row">\
	    <label for="note_status" class="col-sm-3 col-form-label">Статус</label>\
	    <div class="col-sm-9">\
        <select class="form-control" id="note_status" name="note_status">';
		if(typeof(data.statuses)!="undefined") {
			for(let i in data.statuses){
            	table+='<option value="'+data.statuses[i].id+'"'+(service_note.status==data.statuses[i].id?' selected="selected"':'')+'>';
				table+=data.statuses[i].descr;
				table+='</option>';
			}
        }
		table+='</select>\
		</div>\
	</div>\
	</form>\
    ';
    table+='<button class="btn btn-primary" onclick="save_service_note(\'service_note_form_'+note_id+'\');">Сохранить</button>\
		<button class="btn btn-secondary pull-right" onclick="$(\'#edit_service_note\').html(\'\');">Закрыть</button>';
    create_window_centered_blue("edit_service_note_div","Запись в автосервис","edit_service_note",table);
}

function edit_zakaz_in_service_note(note_id){
	var zakaz_id=$("#service_note_form_"+note_id+" #zakaz_id").val();
	var company_id=$("#service_note_form_"+note_id+" #service_company_id").val();
	var send=[];
	if(parseInt(zakaz_id)===0) {
		bootbox.alert("Не выбран заказ!");
		return;
	}
	send['zakaz_id']=zakaz_id;
	send['company_id']=company_id;
	load_module(3).then(function(d){
		setTimeout(get_timed_zakaz_details,200,zakaz_id);
	})
	
}

function get_timed_zakaz_details(zakaz_id){
	get_zakazes().then(function(d){
		get_zakaz_details1('zakaz_form_'+zakaz_id).then(function(z){
			$("#zakaz_details_tr_"+zakaz_id).show();
			let el = document.getElementById('zakaz_details_tr_'+zakaz_id);
			if(el!==null) el.scrollIntoView({block: "center"});
			else bootbox.alert("Не могу найти заказ, возможно выбран другой склад. Выберите правильный склад(магазин) в верхней части интерфейса");
		});
	});
}

function add_zakaz_in_service_note(note_id){
	var send=[];
	send['company_id']=$("form#service_note_form_"+note_id+" input[name=company_id]").val();
	send['delivery_type']=1;
	send['delivery_type_id']=$("#my_sklad").val();
	send['payment_type']=1;
	send['status']=1;
	send['from_service']=1;
	send['from_service_id']=$("#my_service").val();
	send['car_id']=$("form#service_note_form_"+note_id+" input[name=note_car_id]").val();

	api_query_array("/api/index.php",send,"save_zakaz").then(function(data){
		if(data.status=="ok"){
			$("form#service_note_form_"+note_id+" input[name=zakaz_id]").val(data.zakaz_id);
			$("form#service_note_form_"+note_id+" input[name=zakaz_name]").val(data.zakaz_id);
		}
	})
}

function get_note_employees(note_id=0){
	var search_employee=$("#service_note_form_"+note_id+" input#note_employee_name").val();
	if(typeof(search_employee)=="undefined") search_employee="";
	var send=new Array();
	send['search_service_employees']=search_employee;
	api_query_array("/api/index.php",send,"get_service_employees").then(function(data){
		var datalen=data.service_employees.length;
		var table="";
		//table+="<div><input placeholder='фильтр по клиенту' type='text' class='form-control' id='search_payment_client' value='"+search_payment_client+"' onchange='get_payment_clients("+payment_id+")'></div>";
		table+="<div style='max-height:400px; min-width:250px; overflow: auto;'><table class='table table-hover'><thead><tr><th>Фамилия</th><th>Имя</th></tr></thead><tbody>";
		for (var i=0; i<datalen; i++){
		table += '<tr onclick="change_note_employee('+data.service_employees[i].id+',\''+data.service_employees[i].lastname.replace(/\"/g,"")+'\',\''+data.service_employees[i].name+'\',\''+data.service_employees[i].surname+'\','+note_id+');"><td>'+data.service_employees[i].lastname+'</td><td>'+data.service_employees[i].name+'</td></tr>';
		}
		table += "</tbody></table></div>";
		create_window("note_employees_"+note_id+"_div","Выберите работника","note_employees_"+note_id,table);
		//$("#payment_"+payment_id+"_clients_div").css("top","180px");
		//$("#payment_"+payment_id+"_clients_div").css("left","150px");
	});
}

function change_note_employee(id,lastname,name,surname,note_id=0){
    $("#service_note_form_"+note_id+" [id=note_employee_id]").val(id);
    $("#service_note_form_"+note_id+" [id=note_employee_name]").val((lastname+" "+name+" "+surname));
    $("#note_employees_"+note_id).html('');
}

function get_note_clients(note_id=0,company_id=0){
	var search_note_client=$("#service_note_form_"+note_id+" input#service_company_name").val();
	if(typeof(search_note_client)=="undefined") search_note_client="";
	var send=new Array();
	send['search_clients_client_name']=search_note_client;
	api_query_array("/api/index.php",send,"get_clients").then(function(data){
		var datalen=data.clients.length;
		var table="";
		//table+="<div><input placeholder='фильтр по клиенту' type='text' class='form-control' id='search_payment_client' value='"+search_payment_client+"' onchange='get_payment_clients("+payment_id+")'></div>";
		table+="<div style='max-height:400px; overflow: auto;'><table class='table table-hover'><thead><tr><th>Наименование</th><th>ИНН</th></tr></thead><tbody>";
		for (var i=0; i<datalen; i++){
			if(company_id>0 && company_id===parseInt(data.clients[i].id)) {
				change_note_client(data.clients[i].id,data.clients[i].name.replace(/\"/g,""),note_id);
				return 1;
			}
			table += '<tr onclick="change_note_client('+data.clients[i].id+',\''+data.clients[i].name.replace(/\"/g,"")+'\','+note_id+');"><td>'+data.clients[i].name+'</td><td>'+data.clients[i].inn+'</td></tr>';
		}
		table += "</tbody></table></div>";
		create_window("note_"+note_id+"_clients_div","Выберите клиента","note_"+note_id+"_clients",table);
		//$("#payment_"+payment_id+"_clients_div").css("top","180px");
		//$("#payment_"+payment_id+"_clients_div").css("left","150px");
	});
}

function change_note_client(id,name,note_id=0){
    $("#service_note_form_"+note_id+" [name=company_id]").val(id);
    $("#service_note_form_"+note_id+" [name=company_name]").val(name);
    //$("#service_note_form_"+payment_id+" [id=from_inn]").val(from_inn);
    //$("#service_note_form_"+payment_id+" [id=from_kpp]").val(from_kpp);
    $("#note_"+note_id+"_clients").html('');
    $("#service_note_form_"+note_id+" [name=note_car_id]").val('');
    $("#service_note_form_"+note_id+" [name=note_car_name]").val('');
	get_note_cars(note_id,id);
}

function get_note_cars(note_id=0,company_id=0){
    var send=[];
	if(parseInt(company_id)>0)
		send['company_id']=company_id;
	else
    	send['company_id']=$("#service_note_form_"+note_id+" input#service_company_id").val();
    api_query_array("/api/index.php",send,"get_company_cars").then(function(data){
        /*if(data.company_cars.length==1){
            change_note_car(note_id,data.company_cars[0].id,data.company_cars[0].auto_maker_name,data.company_cars[0].auto_model,data.company_cars[0].auto_gov_num,data.company_cars[0].probeg);
        }
        else {*/
            var table='<div><button type="button" class="btn btn-primary" onclick="edit_company_car_service('+send['company_id']+',0,'+note_id+');">Добавить</button></div><div id="new_company_car_service"></div>';
            table+="<div style='max-height:400px; overflow: auto;'><table class='table table-hover'><thead><tr><th>Марка</th><th>Модель</th><th>Гос.номер</th><th>VIN</th><th></th></tr></thead><tbody>";
            var len=data.company_cars.length;
            for(var i=0; i<len; i++){
                table+='<tr>';
				table+='<td onclick="change_note_car('+note_id+','+data.company_cars[i].id+',\''+data.company_cars[i].auto_maker_name+'\',\''+data.company_cars[i].auto_model+'\',\''+data.company_cars[i].auto_gov_num+'\','+data.company_cars[i].probeg+');">'+data.company_cars[i].auto_maker_name+'</td>\
				<td onclick="change_note_car('+note_id+','+data.company_cars[i].id+',\''+data.company_cars[i].auto_maker_name+'\',\''+data.company_cars[i].auto_model+'\',\''+data.company_cars[i].auto_gov_num+'\','+data.company_cars[i].probeg+');">'+data.company_cars[i].auto_model+'</td>\
				<td  onclick="change_note_car('+note_id+','+data.company_cars[i].id+',\''+data.company_cars[i].auto_maker_name+'\',\''+data.company_cars[i].auto_model+'\',\''+data.company_cars[i].auto_gov_num+'\','+data.company_cars[i].probeg+');">'+data.company_cars[i].auto_gov_num+'</td>\
				<td onclick="change_note_car('+note_id+','+data.company_cars[i].id+',\''+data.company_cars[i].auto_maker_name+'\',\''+data.company_cars[i].auto_model+'\',\''+data.company_cars[i].auto_gov_num+'\','+data.company_cars[i].probeg+');">'+data.company_cars[i].vin+'</td>';
				table+='<td><div id="edit_company_car_service_'+data.company_cars[i].id+'" style="position:absolute;width:400px;left:0px;"></div>\
				<div class="btn-group" style="display: flex;">\
				<a onclick="edit_company_car_service('+send['company_id']+','+data.company_cars[i].id+','+note_id+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a>\
				<a title="Удалить" onclick="bootbox.confirm(\'Вы точно хотите удалить этот автомобиль?\',function(result){ if(result) delete_company_car_service('+data.company_cars[i].id+','+note_id+');})"><img src="/new_images/garbage.svg" style="width:20px;"></a></div></td>';
				table+='</tr>';
            }
            table+='</tbody></table></div>';
            create_window("note_"+note_id+"_cars_div","Выберите автомобиль","note_"+note_id+"_cars",table);
        //}
    });
}

function delete_company_car_service(company_car_id,note_id){
	var send=[];
	send['company_car_id']=company_car_id;
	api_query_array('/api/index.php',send,'delete_company_car').then(function(data){
		if(data.status=='ok') 
			get_note_cars(note_id);
	});
}

function change_note_car(note_id,car_id,car_maker,car_model,car_num,mileage){
    $("#service_note_form_"+note_id+" [id=note_car_id]").val(car_id);
	$("#service_note_form_"+note_id+" [id=mileage]").val(mileage);
    $("#service_note_form_"+note_id+" [id=note_car_name]").val(car_maker+" "+car_model+" "+car_num);
    $("#note_"+note_id+"_cars").html('');
}

function save_service_note(form){
	send = [];
	send['zakaz_id']=$("#"+form+" input[name=zakaz_id]").val();
	send['car_id']=$("#"+form+" input[name=note_car_id]").val();

	api_query("/api/index.php",form,"save_service_note").then(function(data){
		if(send['zakaz_id'] != 0)
		{
			api_query_array("/api/index.php",send,"save_zakaz").then(function(data1){
				if(data.status=="ok" && data1.status=="ok"){
					$('#edit_service_note').html('');
					//$('#content_8').html('');
					//load_module(8);
					rebuild_calendar("holder");
					
				}
			})
		}
		else{
			if(data.status=="ok"){
				$('#edit_service_note').html('');
				//$('#content_8').html('');
				//load_module(8);
				rebuild_calendar("holder");
				
			}
		}
	});
}

function delete_service_note(note_id){
	bootbox.confirm('Вы точно хотите удалить запись?',function(result){ 
		if(result){
			var send=[];
			send['service_note_id']=note_id;
			api_query_array("/api/index.php",send,"delete_service_note").then(function(data){
				if(data.status=="ok"){
					//$('#edit_service_note').html('');
					//rebuild_calendar("holder");
					$('#content_8').html('');
					load_module(8);
				}
			});
		}
	});
}

function add_new_client_in_service_note(note_id){
    var table='<div class="form-group row col-sm-12">\
    <label for="service_company_name" class="col-sm-4 col-form-label">ФИО</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" id="service_company_name" placeholder="ФИО" name="company_name" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_name" id="company_name_label" onclick="clear_search_order_text(\'service_company_name\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="service_company_mphone" class="col-sm-4 col-form-label">Телефон</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" id="service_company_mphone" placeholder="Телефон" name="company_mphone" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_mphone" id="company_name_label" onclick="clear_search_order_text(\'service_company_mphone\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="service_company_email" class="col-sm-4 col-form-label">E-MAIL</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" id="service_company_email" placeholder="E-MAIL" name="company_email" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_email" id="company_name_label" onclick="clear_search_order_text(\'service_company_email\');"></label>\
    </div>\
    </div>\
    <div class="form-group row col-sm-12">\
    <label for="service_company_vin" class="col-sm-4 col-form-label">VIN</label>\
    <div class="col-sm-8">\
      <input type="text" class="form-control search_str" id="service_company_vin" placeholder="VIN" name="company_vin" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_vin" id="company_name_label" onclick="clear_search_order_text(\'service_company_vin\');"></label>\
    </div>\
    </div>\
    <button class="btn btn-primary" onclick="fast_save_company_service_note('+note_id+');" type="button">Сохранить</button>';
    create_window("fast_new_client_service_div","Быстрое заведение клиента","fast_new_client_service",table);
}

function fast_save_company_service_note(note_id){
	var send=new Array();
	send['company_name']=$("#fast_new_client_service input[name=company_name]").val();
	send['mphone']=$("#fast_new_client_service input[name=company_mphone]").val();
	send['email']=$("#fast_new_client_service input[name=company_email]").val();
	send['vin']=$("#fast_new_client_service input[name=company_vin]").val();
    api_query_array("/api/index.php",send,"fast_save_company").then(function(data){
        if(data.status=="ok"){
            if(typeof(data.companys)!="undefined" && data.companys.length>0){
                var table='Существуют клиенты с похожим именем, выберите существующего клиента или измените поле ФИО для уникальности\
                <table class="table table-hover"><thead><tr><th>ФИО</th><th>Телефон</th><th>E-MAIL</th></tr></thead><tbody>';
                var complen=data.companys.length;
                for(var i=0; i<complen; i++){
                    table+='<tr style="cursor:pointer;" onclick="select_client_in_service_note('+parseInt(data.companys[i].id)+','+note_id+')"><td>'+data.companys[i].name+'</td><td>'+data.companys[i].mphone+'</td><td>'+data.companys[i].email+'</td></tr>';
                }
                table+='</tbody></table>';
                bootbox.alert(table);
            }
            else {
				$("#service_note_form_"+note_id+" input#service_company_name").val(send['company_name']);
                select_client_in_service_note(parseInt(data.company_id),note_id);
            }
        }
    });
}

function select_client_in_service_note(company_id,note_id){
    bootbox.hideAll();
    $("#fast_new_client_service").html('');
    get_note_clients(note_id,company_id);
}

function edit_company_car_service(company_id,car_id,note_id){
	if(typeof(car_id)=="undefined" || car_id==0){
		print_company_car_form_service(company_id,0,undefined,note_id);
	}
	else {
	  var send=new Array();
	  send['company_car_id']=car_id;
	  api_query_array("/api/index.php",send,"get_company_car").then(function(data){
		print_company_car_form_service(company_id,car_id,data,note_id);
	  });
	}
  }
  
  function print_company_car_form_service(company_id,car_id,data,note_id){
	var isdata=1;
	if(typeof(data)=="undefined") isdata=0;
	var table='<div id="company_car_form_service">';
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
	  print_select_auto_makers_services(car);
		//  <input type="text" class="form-control" value="'+car.auto_maker_name+'" name="auto_maker_name">\
		//  <input type="hidden" name="auto_maker_id" value="'+car.auto_maker_id+'">\
	table+=' </div>\
	</div>\
	</div>\
	<div class="form-group row">\
	 <label for="auto_model" class="col-sm-5">Модель автомобиля:</label>\
	 <div class="col-sm-7 pull-right">\
	 	  <input type="hidden" name="auto_motor_id" value="'; if(isdata) table+=data.company_car.auto_motor_id; table+='">\
		  <input type="text" class="form-control search_str" id="auto_model" value="'; if(isdata) table+=data.company_car.auto_model; table+='" name="auto_model"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_model" id="auto_model_label" onclick="clear_search_order_text(\'auto_model\');"></label>\
	 </div>\
	 <div id="auto_model_select" style="z-index:11;"></div>\
	</div>\
	<div class="form-group row">\
	 <label for="vin" class="col-sm-5">VIN:</label>\
	 <div class="col-sm-7 pull-right">\
		  <input type="text" class="form-control search_str" id="vin" value="'; if(isdata) table+=data.company_car.vin; table+='" name="vin"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="vin" id="vin_label" onclick="clear_search_order_text(\'vin\');"></label>\
	 </div>\
	</div>\
	<div class="form-group row">\
	 <label for="auto_gov_num" class="col-sm-5 col-form-label">Гос. номер авто:</label>\
	  <div class="col-sm-7">\
		  <input type="text" class="form-control search_str" id="auto_gov_num" value="'; if(isdata) table+=data.company_car.auto_gov_num; table+='" name="auto_gov_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_gov_num" id="auto_gov_num_label" onclick="clear_search_order_text(\'auto_gov_num\');"></label>\
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
	  <label for="engine_num" class="col-sm-5 col-form-label">Номер кузова:</label>\
	  <div class="col-sm-7">\
		  <input type="text" class="form-control search_str" id="kuzov_num" value="'; if(isdata) table+=data.company_car.engine_num; table+='" name="kuzov_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="kuzov_num" id="kuzov_num_label" onclick="clear_search_order_text(\'kuzov_num\');"></label>\
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
	</div>\
	<button class="btn btn-primary" onclick="save_company_car_service('+note_id+');" type="button">Сохранить</button>\
	';
	if(!isdata) create_window("new_company_car_service_div","Добавление автомобиля клиента","new_company_car_service",table);
	else create_window("edit_company_car_service_"+car_id+"_div","Редактирование автомобиля клиента","edit_company_car_service_"+car_id,table);
  }

  function print_select_auto_makers_services(car){
	$("#auto_maker_select").html('<input name="auto_maker_name" class="form-control col-sm-7">');
	api_query("/api/index.php","some_form","get_auto_makers").then(function(data){
	  var select='<select name="auto_maker_id" class="form-control col-sm-7" onchange="get_auto_models_services();">';
	  data.auto_makers.forEach(function(auto_maker){
		select+='<option value="'+auto_maker.id+'"';
		if(auto_maker.id==car.auto_maker_id) select+=' selected="selected"';
		select+='>'+auto_maker.name+'</option>';
	  });
	  select+='</select>';
	  $("#auto_maker_select").html(select);
	});
  }
  
  function get_auto_models_services(car){
	var send=[];
	send['auto_maker_id']=$("select[name=auto_maker_id]").val();
	api_query_array("/api/index.php",send,"get_auto_models").then(function(data){
	  var table='<div style="height: 350px; width:710px; overflow: auto;"><table class="table table-hover">\
	  <thead><tr><th>Производитель</th><th>Модель</th><th>Двигатель</th><th>код двиг.</th><th>Модификация</th><th>год выпуска</th></tr></thead><tbody>'
	  data.auto_models.forEach(function(auto_model){
		table+='<tr onclick="set_auto_model_services('+auto_model.id+',\''+auto_model.model+'\')">';
		table+='<td>'+auto_model.make+'</td><td> '+auto_model.model+'</td><td> '+auto_model.engineSalesName+'</td><td> '+auto_model.engineCode+'</td><td> '+auto_model.modification_name+'</td><td> '+auto_model.year+'</td></tr>';
	  })
	  table+='</tbody></table></div>';
	  if(data.auto_models.length>0)
		create_window("auto_model_select_div","Выберите модель","auto_model_select",table);
	  else 
		$("#company_car_form_service input[name=auto_motor_id]").val(0);
	})
  }
  
  function set_auto_model_services(id,model){
	$("#company_car_form_service input[name=auto_motor_id]").val(id);
	$("#company_car_form_service input[name=auto_model]").val(model);
	close_window("auto_model_select");
  }
  
  function save_company_car_service(note_id){
	var send=[];
	send['auto_maker_id']=$("#company_car_form_service select[name=auto_maker_id]").val();
	auto_maker_name = $("#company_car_form_service select[name=auto_maker_id] option:selected").text();
	send['auto_model']=$("#company_car_form_service input[name=auto_model]").val();
	send['auto_motor_id']=$("#company_car_form_service input[name=auto_motor_id]").val();
	send['vin']=$("#company_car_form_service input[name=vin]").val();
	send['auto_gov_num']=$("#company_car_form_service input[name=auto_gov_num]").val();
	send['auto_doc_num']=$("#company_car_form_service input[name=auto_doc_num]").val();
	send['engine_num']=$("#company_car_form_service input[name=engine_num]").val();
	send['kuzov_num']=$("#company_car_form_service input[name=kuzov_num]").val();
	send['made_year']=$("#company_car_form_service input[name=made_year]").val();
	send['probeg']=$("#company_car_form_service input[name=probeg]").val();
	send['company_id']=$("#company_car_form_service input[name=company_id]").val();
	send['company_car_id']=$("#company_car_form_service input[name=company_car_id]").val();
	api_query_array("/api/index.php",send,"save_company_car").then(function(data){
	  if(data.status=="ok"){
		var company_id=$("#company_car_form_service input[name=company_id]").val();
		if(send['company_car_id']>0)
		{
			get_note_cars(note_id);
		}
		else{
			$('div[id^=note_'+note_id+'_cars]').html('');
			// change_note_car(note_id,send['company_car_id'],car_maker,car_model,car_num,mileage);
			change_note_car(note_id,send['company_car_id'],auto_maker_name,send['auto_model'],send['auto_gov_num'])
		}
		$("div[id^=edit_company_car]").html('');
		$("div[id^=new_company_car]").html('');
	  }
	}) 
  }

  function get_services(){
	  api_query("/api/index.php","some_form","get_services").then(function(data){
		var table='<table class="table table-hover"><thead><th>№</th><th>Наименование</th><th>Адрес</th><th>Склад выдачи деталей</th><th>Выбран</th><th></th></thead><tbody>';
		var len=data.services.length;
		for(let i=0; i<len; i++){
			table+='<tr>';
			table+='<td>'+(i+1)+'</td><td>'+data.services[i].name+'</td><td>'+data.services[i].address+'</td><td>'+data.services[i].sklad_name+'</td>';
			if(parseInt(data.services[i].id)==parseInt($("#my_service").val())) table+='<td><img src="/images/ok.svg" style="width:16px;"></td>';
			else table+='<td></td>';
			table+='<td><a onclick="edit_service('+data.services[i].id+');"><img src="/new_images/edit.svg" class="menuimg"></a> <a onclick="bootbox.confirm(\'Вы точно хотите удалить ваш автосервис?\',function(result){ if(result) delete_service('+data.services[i].id+');})"><img src="/new_images/garbage.svg" class="menuimg"></a></td>';
			table+='</tr>';
		}
		table+='</tbody></table>';
		$("#services_list").html(table);
	  })
  }

  function add_service(){
	  var data={};
	  data.id=0;
	  data.name='';
	  data.address='';
	  data.sklad_id=0;
	  data.sklad_name='';
	  print_service_data(data);
  }

  function edit_service(id){
	var send=[];
	send['service_id']=id;
	api_query_array("/api/index.php",send,"get_service").then(function(data){
		if(data.status=="ok"){
			print_service_data(data.service);
		}
	})
}

  function print_service_data(data){
	var table='<form id="edit_service_form"><table class="table"><tbody>';
	table+='<tr><td>Наименование сервиса </td><td> <input class="form-control" type="text" id="service_name" name="name" value="'+data.name+'"><input type="hidden" name="service_id" value="'+data.id+'"></td></tr>';
	table+='<tr><td>Адрес сервиса </td><td> <input class="form-control" type="text" id="service_address" name="address" value="'+data.address+'"></td></tr>';
	table+='<tr><td>Склад выдачи деталей </td><td> <input class="form-control" type="text" id="service_sklad_name" name="sklad_name" value="'+data.sklad_name+'" onclick="select_sklad_for_service()" placeholder="Нажмите для выбора склада"><input type="hidden" name="sklad_id" id="service_sklad_id" value="'+data.sklad_id+'"></td></tr>';
	table+='<tr><td><button class="btn btn-primary btn-sm" type="button" onclick="save_service();">Сохранить</button></td>';
	table+='<td><div id="select_sklad_for_service"></div><button class="btn btn-default btn-sm pull-right" type="button" onclick="$(\'#new_service\').html(\'\')">Отмена</button></td></tr>';
	table+='</form>';
	create_window_centered_blue("new_service_div","Редактирование сервиса","new_service",table);
  }

  function save_service(){
	  api_query("/api/index.php","edit_service_form","save_service").then(function(data){
		  if(data.status=="ok"){
			$('#new_service').html('');
			get_services();
			get_my_services();
		  }
	  })
  }

  function select_sklad_for_service(){
    api_query("/api/index.php","some_form","get_sklads").then(function(data){
    var datalen=data.sklads.length;
    var table="";
    table+="<table class=\"table table-hover\"><tr><th>№</th><th>Наименование</th><th>Адрес</th><th>Описание</th><th></th></tr>";
    for (var i=0; i<datalen; i++){
		if(typeof(data.sklads[i].name)=="string") data.sklads[i].name=data.sklads[i].name.replaceAll('"','&quot;').replaceAll("'","&apos;");
		table += "<tr onclick=\"$('#edit_service_form input[name=sklad_id]').val("+data.sklads[i].id+"); $('#edit_service_form input[name=sklad_name]').val('"+data.sklads[i].name+"'); $(\'#select_sklad_for_service\').hide();\"><td>"+(i+1)+"</td><td>" + data.sklads[i].name + "</td><td>"+data.sklads[i].address+"</td><td>"+data.sklads[i].descr+"</td>";
		table += "</tr>";
    }
    create_window("select_sklad_for_service_div","Выберите склад","select_sklad_for_service",table)
 });
}

function delete_service(id){
	var send=[];
	send['service_id']=id;
	api_query_array("/api/index.php",send,"delete_service").then(function(data){
		if(data.status=="ok"){
		  //$('#new_service').html('');
		  get_services();
		  get_my_services();
		}
	})
}

function get_my_services(){
	api_query("/api/index.php","some_form","get_services").then(function(data){
		let len=data.services.length;
		var ul="";
		for(let i=0; i<len; i++){
			ul+='<option value="'+data.services[i].id+'"';
			if(parseInt(data.my_service_id)==parseInt(data.services[i].id)) ul+=' selected="selected"';
			ul+='>'+data.services[i].name+'</option>';
		}
		$("#my_service").html(ul);
	});
}

function get_deliverys_to_workshop(){
	var defer=$.Deferred();
	api_query("/api/index.php","some_form","get_delivery_to_workshops").then(function(data){
		if(data.status=="ok"){
			var table='<table class="table table-hover"><thead><th>№</th><th>дата</th><th>сотрудник</th><th>Склад выдачи</th><th>№ заказа</th><th>статус</th><th></th></thead><tbody>';
			var len=data.delivery_to_workshops.length;
			for(let i=0; i<len; i++){
				table+='<tr><td>'+(i+1)+'<div id="deliv_to_work_det_'+data.delivery_to_workshops[i].id+'"></div></td>';
				table+='<td>'+convertTZ(data.delivery_to_workshops[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>';
				table+='<td>'+data.delivery_to_workshops[i].employee_name+'</td>';
				table+='<td>'+data.delivery_to_workshops[i].sklad_name+'</td>';
				table+='<td>'+data.delivery_to_workshops[i].zakaz_id+'</td>';
				table+='<td>'+data.statuses[data.delivery_to_workshops[i].status].descr+'</td>';
				table+='<td>';
				table+='<a onclick="edit_delivery_to_workshop('+data.delivery_to_workshops[i].id+');"><img src="/new_images/edit.svg" class="menuimg"></a>';
				table+='<a onclick="get_delivery_to_workshop_details('+data.delivery_to_workshops[i].id+');"><img src="/new_images/file.svg" class="menuimg"></a>';
				table+='</td>';
				table+='</tr>';
			}
			$("#deliverys_to_workshop_list").html(table);
		}
		defer.resolve(data);
	});
	return defer.promise();
}

function get_delivery_to_workshop_details(delivery_to_workshop_id){
	var send=[];
	send['delivery_to_workshop_id']=delivery_to_workshop_id;
	api_query_array("/api/index.php",send,"get_delivery_to_workshop_details").then(function(data){
		if(data.status=="ok"){
			var table='<table class="table table-hover"><thead><th>артикул</th><th>бренд</th><th>наименование</th><th>кол-во к выдаче</th><th>кол-во выдано</th><th>кол-во возвр.</th><th>статус</th><th></th></thead><tbody>';
			var len=data.delivery_to_workshop_details.length;
			for (let i=0; i<len; i++){
				table+='<tr><td>'+data.delivery_to_workshop_details[i].article+'</td><td>'+data.delivery_to_workshop_details[i].brand+'</td>\
				<td><div id="edit_delivery_to_workshop_detail_'+data.delivery_to_workshop_details[i].id+'"></div>\
				<div id="rdtwd_'+data.delivery_to_workshop_details[i].id+'"></div>\
				'+data.delivery_to_workshop_details[i].name+'</td>\
				<td>'+data.delivery_to_workshop_details[i].count+'</td><td>'+data.delivery_to_workshop_details[i].delivered_count+'</td>';
				table+='<td>'+data.delivery_to_workshop_details[i].returned_count+'</td><td>'+data.statuses[data.delivery_to_workshop_details[i].status].descr+'</td>';
				table+='<td>';
				table+='<a onclick="edit_delivery_to_workshop_detail('+data.delivery_to_workshop_details[i].id+');"><img src="/new_images/edit.svg" class="menuimg"></a>';
				table+='<a onclick="delete_delivery_to_workshop_detail('+data.delivery_to_workshop_details[i].id+');"><img src="/new_images/garbage.svg" class="menuimg"></a>';
				table+='<a onclick="return_delivery_to_workshop_detail('+data.delivery_to_workshop_details[i].id+','+data.delivery_to_workshop_details[i].count+');"><img src="/new_images/return.svg" style="width: 40px; padding-left: 3px; padding-right: 3px;" title="Вернуть на склад"></a>';
				table+='</td>';
				table+='</tr>';
			}
			table+='</tbody></table>';
			create_window_centered_blue("deliv_to_work_det_"+delivery_to_workshop_id+"_div","Детали в ремзоне","deliv_to_work_det_"+delivery_to_workshop_id,table);
		}
	});
}

function edit_delivery_to_workshop(delivery_to_workshop_id){
	var send=[];
	send['delivery_to_workshop_id']=delivery_to_workshop_id;
	api_query_array("/api/index.php",send,"get_delivery_to_workshop").then(function(data){
		if(data.status=="ok"){
			var table='<form id="delivery_to_workshop_'+delivery_to_workshop_id+'"><table class="table table-hover"><tbody>';
			//var len=data.delivery_to_workshop.length;
			
			table+='<tr><td>Сотрудник</td><td>'+data.delivery_to_workshop.employee_name+'<input type="hidden" name="delivery_to_workshop_id" value="'+delivery_to_workshop_id+'"></td></tr>';
			table+='<tr><td>Склад выдачи</td><td>'+data.delivery_to_workshop.sklad_name+'</td></tr>';
			table+='<tr><td>№ заказа</td><td>'+data.delivery_to_workshop.zakaz_id+'</td></tr>';
			table+='<tr><td>Дата создания</td><td>'+convertTZ(data.delivery_to_workshop.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td></tr>';
			table+='<tr><td>Статус</td><td><select class="form-control" name="status">';
			for(st_ind in data.statuses){
				table+='<option value="'+data.statuses[st_ind]['id']+'" '+(data.statuses[st_ind]['id']==data.delivery_to_workshop.status?'selected="selected"':'')+'>'+data.statuses[st_ind]['descr']+'</option>';
			}
			table+='</select></td></tr>';
			table+='<tr><td><button type="button" class="btn btn-primary" onclick="save_delivery_to_workshop('+delivery_to_workshop_id+')">Сохранить</button></td>';
			table+='<td><button type="button" class="btn btn-default pull-right" onclick="close_window(\''+"deliv_to_work_det_"+delivery_to_workshop_id+'\');">Отменить</button></td></tr>';
			table+='</tbody></table></form>';
			create_window_centered_blue("deliv_to_work_det_"+delivery_to_workshop_id+"_div","Выдача детали в ремзону","deliv_to_work_det_"+delivery_to_workshop_id,table);
		}
	});
}

function save_delivery_to_workshop(delivery_to_workshop_id){
	api_query("/api/index.php",'delivery_to_workshop_'+delivery_to_workshop_id,"save_delivery_to_workshop").then(function(data){
		if(data.status=="ok"){
			get_deliverys_to_workshop();
		}
	});
}

function edit_delivery_to_workshop_detail(delivery_to_workshop_detail_id){
	var send=[];
	send['delivery_to_workshop_detail_id']=delivery_to_workshop_detail_id;
	api_query_array("/api/index.php",send,"get_delivery_to_workshop_detail").then(function(data){
		if(data.status=="ok"){
			var ed=data.delivery_to_workshop_detail;
			var table='<form id="form_edit_delivery_to_workshop_detail_'+ed.id+'"><table class="table table-hover"><tbody>';
			table+='<tr><td>Артикул</td><td>'+ed['article']+'</td></tr>';
			table+='<tr><td>Бренд</td><td>'+ed['brand']+'</td></tr>';
			table+='<tr><td>Наименование</td><td>'+ed['name']+'</td></tr>';
			table+='<tr><td>Кол-во к выдаче</td><td><input class="form-control" name="count" value="'+ed['count']+'"></td></tr>';
			table+='<tr><td>Кол-во выдано</td><td><input class="form-control" name="delivered_count" value="'+ed['delivered_count']+'"></td></tr>';
			table+='<tr><td>Кол-во вовращено</td><td><input class="form-control" name="returned_count" value="'+ed['returned_count']+'"></td></tr>';
			table+='<tr><td>Статус</td><td><select name="status" class="form-control">';
			for(st_ind in data.statuses){
				table+='<option value="'+data.statuses[st_ind]['id']+'" '+(data.statuses[st_ind]['id']==ed.status?'selected="selected"':'')+'>'+data.statuses[st_ind]['descr']+'</option>';
			}
			table+='</select></td></tr>';
			table+='<tr><td><button type="button" class="btn btn-primary" onclick="save_delivery_to_workshop_detail('+ed.id+')">Сохранить</button></td>\
				<td><button type="button" class="btn btn-default pull-right" onclick="close_window(\'edit_delivery_to_workshop_detail_'+ed.id+'\')">Отменить</button></td></tr>';
			table+='</form>';
			create_window('edit_delivery_to_workshop_detail_'+ed.id+'_div',"Редактирование выдачи",'edit_delivery_to_workshop_detail_'+ed.id,table);
		}
	});
}

function return_delivery_to_workshop_detail(delivery_to_workshop_detail_id,count){
	var table='<br>';
	table+='<div class="input-group input-group-xs">возвращаемое количество: \
		<span class="input-group-btn">\
        	<button class="btn btn-default btn-xs" type="button" onclick="decrease_rdtwd_det_count('+delivery_to_workshop_detail_id+','+count+')">-</button>\
		</span>\
		<input type="text" class="form-control1" value="1" style="width:38px; height: 22px; text-align:center;" id="rdtwd_count_'+delivery_to_workshop_detail_id+'" onchange="change_rdtwd_det_count('+delivery_to_workshop_detail_id+','+count+');">\
		<span class="input-group-btn">\
			<button class="btn btn-default btn-xs" type="button" onclick="increase_rdtwd_det_count('+delivery_to_workshop_detail_id+','+count+')">+</button>\
		</span></div>\
		<br><div><button class="btn btn-primary btn-sm" onclick="return_dtwd('+delivery_to_workshop_detail_id+');">Вернуть на склад</button><button class="btn btn-default btn-sm pull-right" onclick="close_window(\'rdtwd_'+delivery_to_workshop_detail_id+'\')">Отменить</button></div>';
	create_window("rdtwd_"+delivery_to_workshop_detail_id+"_div","Укажите количество возвращаемых деталей","rdtwd_"+delivery_to_workshop_detail_id,table);
}

function return_dtwd(delivery_to_workshop_detail_id){
	var send=[];
	send['delivery_to_workshop_detail_id']=delivery_to_workshop_detail_id;
	send['return_count']=parseInt($("#rdtwd_count_"+delivery_to_workshop_detail_id).val());
	api_query_array("/api/index.php",send,"return_delivery_to_workshop_detail").then(function(data){
		get_deliverys_to_workshop().then(function(data){
			if(data.status=="ok"){
				get_delivery_to_workshop_details(delivery_to_workshop_id);
			}
		})
	});
}

function decrease_rdtwd_det_count(delivery_to_workshop_detail_id,count){
	var return_count=parseInt($("#rdtwd_count_"+delivery_to_workshop_detail_id).val());
	if((return_count-1)>0) {
		$("#rdtwd_count_"+delivery_to_workshop_detail_id).val((return_count-1));
	}
}

function increase_rdtwd_det_count(delivery_to_workshop_detail_id,count){
	var return_count=parseInt($("#rdtwd_count_"+delivery_to_workshop_detail_id).val());
	if((return_count+1)<=count) {
		$("#rdtwd_count_"+delivery_to_workshop_detail_id).val((return_count+1));
	}
}

function change_rdtwd_det_count(delivery_to_workshop_detail_id,count){
	var return_count=parseInt($("#rdtwd_count_"+delivery_to_workshop_detail_id).val());
	if((return_count)>0 && (return_count)<=count) {
		$("#rdtwd_count_"+delivery_to_workshop_detail_id).val(return_count);
	}
	if(return_count>count)
			$("#rdtwd_count_"+delivery_to_workshop_detail_id).val(count);
		else
			if(return_count<=0)
				$("#rdtwd_count_"+delivery_to_workshop_detail_id).val(1);
}

function load_jobes_to_base(api,tab){ 
	var send=[];
	send['jobes']=api;
	api_query_array("/api/index.php",send,"save_jobes").then(function(data){
	  if(data.status=="ok"){
		get_service_jobs();
	  }
	});
}