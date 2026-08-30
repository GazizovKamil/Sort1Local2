function get_plugins(profile_id){
    api_query("/api/index.php","plugins_form","get_plugins").then(function(data){

    if(data.err=="activation needed"){
    	var table='\
    	    <form id="get_lic">\
    	    Введите код активации: \
    	    <input name="activation_code" id="activation_code" placeholder="код активации">\
    	    <button type="button" onclick="activate_sort1(\'get_lic\');">Активировать</button>\
    	    </form>\
    	';
    	$("#sort1_list").html(table);
    }
    else {
      	var plugin_types=['Грузовые','Легковые','Грузовые и легковые'];
      	var datalen=0;
        if(typeof(data.plugins)!=="undefined") datalen=data.plugins.length;
      	var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование</th><th>Тип</th><th>Действия</th><th></th></tr></thead><tbody>";
      	for (var i=0; i<datalen; i++){
      	    table += "<tr><td>"+(i+1)+"<div id='edit_plugin_"+data.plugins[i].plugin_id+"'></div></td><td><img src='"+data.plugins[i].icon+"'> " + data.plugins[i].name + "</td><td>"+plugin_types[parseInt(data.plugins[i].type)-1]+"</td>";
      	    table += "<td><div class='btn-group' style='display: flex;'>";
      	    table += "<a onclick=\"edit_plugin(\'"+data.plugins[i].plugin_id+"\');\" title='Редактировать поставщика'><img src='/new_images/edit.svg' style='width:20px;'></a>";
      	    table += "</div></td>";
            if(parseInt(data.plugins[i].tested)==1) table+= "<td><img src=\"/images/ok.svg\" style=\"width:20px;\"></td>";
            else table+= "<td></td>";
      	    table += "</tr>";
      	}
      	table+= "</tbody></table>";
      	$("#sort1_list").html(table);
    }
 });
}

function edit_plugin(plid){
    var send_arr=new Array();
    send_arr['plid']=plid;
    $("div [id^=edit_plugin_]").html('');
    api_query_array("/api/index.php",send_arr,"get_plugin_settings").then(function(data){
      	var params_len=data.params.length;
      	var table='<form id="api_config_form">';
        table+='<input type="hidden" name="plid" id="plid" value="'+plid+'">';
        table+='<table style="padding:5px" class="table">';
      	$.each(data.params,function(param_key,param_val){
      	    table+="<tr><td>"+param_val.descr+"</td>";
      	    if(param_val.type==0) {
          		table+="<td><input name='"+param_key+"' id='"+param_key+"' value='";
          		if(param_val.value!=null) table+=param_val.value;
          		table+="' class='form-control'></td>";
      	    }
      	    if(param_val.type==1){
          		table+='<td><select name="'+param_key+'" id="'+param_key+'" class="form-control">';
          		$.each(param_val.values_descr,function(val_key,val_val){
          		    table+='<option value="'+val_key+'"';
          		    if(param_val.value==val_key) table+=' selected="selected"';
          		    table+=">"+val_val+"</option>";
          		});
          		table+="</select></td>";
      	    }
      	    if(param_val.type==11){
          		table+='<td><select name="'+param_key+'" id="'+param_key+'" class="form-control">';
          		$.each(param_val.values_descr,function(val_key,val_val){
          		    table+='<option value="'+val_key+'"';
          		    if(param_val.value==val_key) table+=' selected="selected"';
          		    table+=">"+val_val+"</option>";
          		});
          		table+="</select></td>";
      	    }
      	    table+="</tr>";
      	});
      	table+='<tr><td><a onclick="get_params('+plid+')" style="cursor: pointer">Проверить</a></td><td><div id="api_settings_check"></div></td></tr>';
      	table+='</table></form>';
        table+='<input type="hidden" name="plugin_tested" id="plugin_tested" value="'+data.tested+'">';
        table+='<div class="form-group row"><label for="plugin_enabled" class="col-sm-6 col-form-label">Включен при поиске</label>';
        table+='<div class="col-sm-6 pull-right"><input type="checkbox" id="plugin_enabled"';
        if(data.enabled==1) table+=' checked="checked" ';
        table+='></div></div>';
        table+='<div class="form-group row"><label for="use_on_client_search" class="col-sm-6 col-form-label">Включен при поиске в интернет магазине</label>';
        table+='<div class="col-sm-6 pull-right"><input type="checkbox" id="use_on_client_search"';
        if(data.use_on_client_search==1) table+=' checked="checked" ';
        table+='></div></div>';
        table+='<div class="form-group row">';
        table+='<label for="plugin_price_type" class="col-sm-6 col-form-label">Наценка</label>';
        table+='<div class="col-sm-6 pull-right"><select id="plugin_price_type" class="form-control">';
        table+='<option value="0">- не выбрана</option>';
        $.each(data.price_types,function(pt_key,pt_val){
          table+='<option value="'+pt_val.id+'"';
          if(pt_val.id==data.price_type_id) table+=' selected="selected"';
          table+='>'+pt_val.descr+'</option>';
        });
        table+='</select></div>';
        table+='</div>';
        table+='<div class="form-group row">';
        table+='<label for="deliverer_company_id" class="col-sm-6 col-form-label">Компания-поставщик</label>';
        table+='<div class="col-sm-6 pull-right"><input type="hidden" name="deliverer_company_id" id="deliverer_company_id"';
        table+=' value="'+data.deliverer_company_id+'"';
        table+='>';
        table+='<input type="text" class="form-control" name="deliverer_company_name" id="deliverer_company_name" readonly="readonly" onclick="get_plugin_dealers('+data.deliverer_company_id+');" ';
        if(data.deliverer_company_name!="") table+=' value="'+str_to_val(data.deliverer_company_name)+'"';
        else table+=' value="Не назначен" ';
        table+='><div id="plugin_dealers"></div>';
        table+='</div></div>';
      	table+="<button class='btn btn-primary' onclick='save_plugin_settings("+plid+");'>Сохранить</button><button class='btn btn-default pull-right' onclick='close_window(\"edit_plugin_"+plid+"\");'>Отменить</button>";
      	create_window("edit_plugin_"+plid+"_div","Параметры доступа к сайту поставщика","edit_plugin_"+plid,table);
        place_to_center("edit_plugin_"+plid+"_div");
        //alert(table);
        });
      }

      function get_plugin_dealers(id){
       api_query("/api/index.php","some_form","get_dealers").then(function(data){
          var datalen=data.dealers.length;
          var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование</th><th>ИНН / КПП</th><th>Адрес</th></tr></thead><tbody>";
          table+='<tr onclick="set_plugin_company(0,\'Не назначен\')"><td>0</td><td>0/0</td><td>Не назначен</td></tr>';
          for (var i=0; i<datalen; i++){
          	table+='<tr onclick="set_plugin_company('+data.dealers[i].id+',\''+str_to_val(data.dealers[i].name)+'\')">';
            table+='<td><div id="dealer_data_'+data.dealers[i].id+'"></div><div id="dealer_users_'+data.dealers[i].id+'"></div>'+(i+1)+'</td>';
            table+="<td>" + data.dealers[i].name + "</td>";
            table+="<td>"+data.dealers[i].inn+"/"+data.dealers[i].kpp+"</td>";
            table+="<td>"+data.dealers[i].address+"</td>";
          	table += "</td></tr>";
          }
          table += "</tbody></table>";
          create_window("plugin_dealers_div","Выберите поставщика","plugin_dealers",table);
       });
      }

      function set_plugin_company(id,name){
        $("#deliverer_company_id").val(id);
        $("#deliverer_company_name").val(name);
        $("#plugin_dealers").html('');
      }

      function save_plugin_settings(plid){
          form_data = $("#api_config_form").serializeArray();
          var form=new Array();
          form['plid']=$("#plid").val();
          form['params']=form_data;
          form['plugin_tested']=$("#plugin_tested").val();
          if($("#plugin_enabled").prop('checked')) form['plugin_enabled']="on";
          else form['plugin_enabled']="off";
          if($("#use_on_client_search").prop('checked')) form['use_on_client_search']="on";
          else form['use_on_client_search']="off";
          if($("#trust_kross").prop('checked')) form['trust_kross']="on";
          else form['trust_kross']="off";
          form['plugin_price_type']=$("#plugin_price_type").val();
          form['deliverer_company_id']=$("#deliverer_company_id").val();
          api_query_array("/api/index.php",form,"save_plugin_settings").then(function(data){
            $("#edit_plugin_"+plid).html('');
          });

      }

function activate_sort1(form,profile_id){
  api_query("/api/index.php",form,"get_lic").then(function(data){
      	if (data.status=="ok"){
      	    get_profile_plugins(profile_id,1);
      	}
      	else {
      	    var table='\
      	    <form id="get_lic" onsubmit="activate_sort1(\'get_lic\','+profile_id+');">\
      	    Введите код активации: \
      	    <input name="activation_code" id="activation_code" placeholder="код активации">\
      	    <button type="button" onclick="activate_sort1(\'get_lic\','+profile_id+');">Активировать</button>\
      	    </form>\
      	    ';
      	    $("#sort1_activation_list_"+profile_id).html(table);
      	}
  });
}

function get_params(plid,params) {
  var defer=$.Deferred();
	$("#api_settings_check").html("<img src=\"/images/30.gif\">");
        var form_data = $("#api_config_form").serializeArray();
	var form=new Array();
	form['params']=form_data;
	form['plid']=plid;
	api_query_array("/api/index.php",form,"get_params").then(function(data){
		//alert(data);
        	if (data.authorized == "OK") {
		    $.each(data.params, function (name, val) {
          if(val.values!==null){
            if (val.values.length >0)
              var vals=val.values.split("||");
            else {
              var vals=[val.values];
            }
            if (val.values_descr.length > 0)
              var vals_descr=val.values_descr.split("||");
            else
              var vals_descr=[val.values_descr];
          }
			    var i,options;
			    options+='<option value="">-</option>';
          if(val.values!==null){
            for (i=0; i< vals.length; ++i){
              options+='<option value="' + vals[i] + '"';
              if(vals[i]==params[name]) options+=' selected="selected"';
              options+='>' + vals_descr[i] + '</option>';
            }
          }
			    $("#" + name).html(options);

			  });
		    $("#api_settings_check").html("<font color='green'>ok</font>");
        $("input[name=plugin_tested]").val(1);
		}
		else {
        $("#api_settings_check").html("<font color='red'>" + data.authorized + "</font>");
        $("input[name=plugin_tested]").val(0);
    }
    defer.resolve(data);
  });
  return defer.promise();
  //отмена действия по умолчанию для кнопки submit
  //e.preventDefault();
}

function clear_search_text_plugins(id){
  $("#"+id).val('');
  get_plugins();
}


function get_profile_plugins(profile_id,toggle){
  if(profile_id==0){ 
    profile_id=$("#selected_profile").val();
    $("form#plugins_form input[name=profile_id]").val(profile_id);
  }
  if(profile_id!=parseInt($("#active_my_profile_id").val()) && profile_id!=parseInt($("#active_internetshop_profile_id").val()) && profile_id!=parseInt($("#active_shop_profile_id").val())){
    var delete_button='<a onclick="delete_online_profile('+profile_id+')"><img src="/new_images/garbage.svg" width="20px"></a>';
    $("#delete_profile_button").html(delete_button);
  }
  else {
    $("#delete_profile_button").html('');
  }
  if(profile_id!=parseInt($("#active_my_profile_id").val())){
    var activate_button_my_profile=' <a onclick="save_company_online_profile(3,'+profile_id+')">Сделать моим активным профилем</a>';
    $("#active_my_profile").html(activate_button_my_profile);
  }
  else {
    var activate_button_my_profile=' Мой профиль';
    $("#active_my_profile").html(activate_button_my_profile);
  }
  if(profile_id!=parseInt($("#active_internetshop_profile_id").val())){
    var activate_button_internetshop_profile='<a onclick="save_company_online_profile(1,'+profile_id+')">Сделать активным профилем Интернет магазина</a>';
    $("#active_internetshop_profile").html(activate_button_internetshop_profile);
  }
  else {
    var activate_button_my_profile=' активный профиль Интернет магазина';
    $("#active_internetshop_profile").html(activate_button_my_profile);
  }
  if(profile_id!=parseInt($("#active_shop_profile_id").val())){
    var activate_button_shop_profile='<a onclick="save_company_online_profile(2,'+profile_id+')">Сделать активным профилем розничного магазина</a>';
    $("#active_shop_profile").html(activate_button_shop_profile);
  }
  else {
    var activate_button_my_profile=' активный профиль розничного магазина';
    $("#active_shop_profile").html(activate_button_my_profile);
  }
  //if(profile_id==parseInt($("#active_my_profile_id").val())){
  //  var activate_button_my_profile=' активный профиль';
  //  $("#active_my_profile").html(activate_button_my_profile);
  //}
    api_query("/api/index.php","plugins_form","get_plugins").then(function(data){
      if(data.err=="activation needed"){
        var table='\
            <form id="get_lic">\
            Введите код активации: \
            <input name="activation_code" id="activation_code" placeholder="код активации">\
            <button type="button" onclick="activate_sort1(\'get_lic\','+profile_id+');">Активировать</button>\
            </form>\
        ';
        $("#sort1_activation_list").html(table);
        if(typeof(toggle)!="undefined")
            $("#sort1_activation_list").show();
      }
      else {
          $("#sort1_activation_list").hide();
          var plugin_types=['Грузовые','Легковые','Грузовые и легковые'];
          var datalen=0;
          if(typeof(data.plugins)!=="undefined") datalen=data.plugins.length;
          var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование</th><th>Тип</th><th>Действия</th><th></th></tr></thead><tbody>";
          for (var i=0; i<datalen; i++){
              table += "<tr ondblclick=\"edit_profile_plugin("+data.plugins[i].plugin_id+","+data.profile_id+",'"+data.plugins[i].name+"');\"><td>"+(i+1)+"<div id='edit_plugin_"+data.profile_id+"_"+data.plugins[i].plugin_id+"'></div></td><td><img src='"+data.plugins[i].icon+"' style=' width:16px;'> <a href='http://"+data.plugins[i].name+"' target='_blank'>"+ data.plugins[i].name + "</a></td><td>"+plugin_types[parseInt(data.plugins[i].type)-1]+"</td>";
              table += "<td><div class='btn-group' style='display: flex;'>";
              table += "<a onclick=\"edit_profile_plugin("+data.plugins[i].plugin_id+","+data.profile_id+",'"+data.plugins[i].name+"');\" title='Редактировать поставщика'><img src='/new_images/edit.svg' style='width:20px;'></a>";
              table += "</div></td>";
              if(parseInt(data.plugins[i].tested)==1 && parseInt(data.plugins[i].enabled)==1) table+= "<td><img src=\"/images/ok.svg\" style=\"width:20px;\"></td>";
              else table+= "<td></td>";
              table += "</tr>";
          }
          table+= "</tbody></table>";
          $("#sort1_list").html(table);
          if(typeof(toggle)!="undefined")
            $("#profile_list").show();
      }
    });
}

function edit_profile_plugin(plid,profile_id){
    var send_arr=new Array();
    send_arr['plid']=plid;
    send_arr['profile_id']=profile_id;
    $("div [id^=edit_plugin_]").html('');
    $("div [id=online_profile_data_0]").html('');
    api_query_array("/api/index.php",send_arr,"get_plugin_settings").then(function(data){
      	var params_len=data.params.length;
	      var table='<div style="min-width:500px;"><br><p style="text-align: center">Внимание! Поставщики, чьи сайты работают<br>\
        на платформах ABCP и Tradesoft, требуют подключаться к ним по API.<br>\
        Отправьте им заявку для включения API и укажите<br>\
        IP адреса, указанные ниже:<br>\
        213.159.206.21, 213.159.206.30, 213.159.206.31, 213.159.206.32.</p>';
      	table+='<form id="api_config_form">';
        table+='<input type="hidden" name="plid" id="plid" value="'+plid+'">';
        table+='<input type="hidden" name="profile_id" id="profile_id" value="'+profile_id+'">';
        table+='<table style="padding:5px" class="table">';
        var empty_auth=0,auth_type_11=0;
        var params_11=new Array();
      	$.each(data.params,function(param_key,param_val){
      	    table+="<tr><td>"+param_val.descr+"</td>";
      	    if(param_val.type==0) {
          		table+="<td><input name='"+param_key+"' id='"+param_key+"' value='";
          		if(param_val.value!=null) table+=param_val.value;
              table+="' class='form-control'></td>";
              if(param_val.auth==1 && param_val.value!==null) empty_auth=1;
      	    }
      	    if(param_val.type==1){
          		table+='<td><select name="'+param_key+'" id="'+param_key+'" class="form-control">';
          		$.each(param_val.values_descr,function(val_key,val_val){
          		    table+='<option value="'+val_key+'"';
          		    if(param_val.value==val_key) table+=' selected="selected"';
          		    table+=">"+val_val+"</option>";
          		});
          		table+="</select></td>";
      	    }
      	    if(param_val.type==11){
              auth_type_11=1;
              params_11[param_key]=param_val.value;
          		table+='<td><select name="'+param_key+'" id="'+param_key+'" class="form-control">';
          		$.each(param_val.values_descr,function(val_key,val_val){
          		    table+='<option value="'+val_key+'"';
          		    if(param_val.value==val_key) table+=' selected="selected"';
          		    table+=">"+val_val+"</option>";
          		});
          		table+="</select></td>";
      	    }
      	    table+="</tr>";
      	});
      	table+='<tr><td><button onclick="get_params('+plid+','+profile_id+')" class="btn btn-sm btn-success" type="button">Проверить</button></td><td><div id="api_settings_check"></div></td></tr>';
      	table+='</table></form>';
        table+='<input type="hidden" name="plugin_tested" id="plugin_tested" value="'+data.tested+'">';
        table+='<hr style="margin-top:0px; margin-bottom:5px;">';
        table+='<div class="form-group row"><label for="plugin_enabled" class="col-sm-6 col-form-label">Включен при поиске</label>';
        table+='<div class="col-sm-6 pull-right"><input type="checkbox" id="plugin_enabled"';
        if(data.enabled==1) table+=' checked="checked" ';
        table+='></div></div>';
        table+='<div class="form-group row"><label for="use_on_client_search" class="col-sm-6 col-form-label">Включен при поиске в интернет магазине</label>';
        table+='<div class="col-sm-6 pull-right"><input type="checkbox" id="use_on_client_search"';
        if(data.use_on_client_search==1) table+=' checked="checked" ';
        table+='></div></div>';
        table+='<div class="form-group row"><label for="trust_kross" class="col-sm-6 col-form-label">Доверять аналогам поставщика</label>';
        table+='<div class="col-sm-6 pull-right"><input type="checkbox" id="trust_kross"';
        if(data.trust_kross==1) table+=' checked="checked" ';
        table+='></div></div>';
        if(data.can_make_order=="1"){
          table+='<div class="form-group row"><label for="make_order" class="col-sm-6 col-form-label">Отправлять детали в заказ</label>';
          table+='<div class="col-sm-6 pull-right"><input type="checkbox" id="make_order"';
          if(data.make_order=="1") table+=' checked="checked" ';
          table+='></div></div>';
        }
        table+='<hr style="margin-top:0px; margin-bottom:5px;">';
        table+='<div class="form-group row">';
        table+='<label for="plugin_price_type" class="col-sm-6 col-form-label">Наценка</label>';
        table+='<div class="col-sm-6 pull-right"><select id="plugin_price_type" class="form-control">';
        table+='<option value="0">- не выбрана</option>';
        $.each(data.price_types,function(pt_key,pt_val){
          table+='<option value="'+pt_val.id+'"';
          if(pt_val.id==data.price_type_id) table+=' selected="selected"';
          table+='>'+pt_val.descr+'</option>';
        });
        table+='</select></div>';
        table+='</div>';
        table+='<div class="form-group row">';
        table+='<label for="deliverer_company_id" class="col-sm-6 col-form-label">Компания-поставщик</label>';
        table+='<div class="col-sm-6 pull-right">\
          <div class="input-group input-group-sm" style="width:100%">\
            <input type="hidden" name="deliverer_company_id" id="deliverer_company_id"';
        table+=' value="'+data.deliverer_company_id+'"';
        table+='>';
        table+='<input type="text" class="form-control" name="deliverer_company_name" id="deliverer_company_name" readonly="readonly" onclick="get_profile_plugin_dealers('+data.deliverer_company_id+');" ';
        if(data.deliverer_company_name!="") table+=' value="'+str_to_val(data.deliverer_company_name)+'"';
        else table+=' value="Не назначен" ';
        table+='>\
        <div class="input-group-btn">\
          <button title="добавление нового поставщика" class="btn btn-sm btn-default" onclick="show_company_data1(0,9);" type="button"  style="width:100%">+</button>\
        </div>\
        ';
        table+='</div></div></div><div id="plugin_dealers"></div>';
        table+='<div class="form-group row">';
        table+='<label for="plugin_price_type" class="col-sm-6 col-form-label">Увел. срок поставки на<sup title="Увеличение срока поставки на введенное количество дней">&#9072;</sup></label>';
        table+='<div class="col-sm-6"><input class="form-control" type="text" name="delivery_days" id="delivery_days"';
        table+=' value="'+data.delivery_days+'"';
        table+='></div>';
        table+='</div>';
      	table+="<button class='btn btn-primary' onclick='save_profile_plugin_settings("+plid+");'>Сохранить</button><button class='btn btn-default pull-right' onclick='close_window(\"edit_plugin_"+profile_id+"_"+plid+"\");'>Отменить</button>";
      	table+='</div>';
        create_window("edit_plugin_"+profile_id+"_"+plid+"_div","Параметры доступа к сайту поставщика","edit_plugin_"+profile_id+"_"+plid,table);
        place_to_center("edit_plugin_"+profile_id+"_"+plid+"_div");
        if(empty_auth && auth_type_11) 
          get_params(plid,params_11).then(function(data2){
            setTimeout(place_to_center,10,"edit_plugin_"+profile_id+"_"+plid+"_div");
          });
        //alert(table);
        });
      }

      function get_profile_plugin_dealers(id){
       api_query("/api/index.php","some_form","get_dealers").then(function(data){
          var datalen=data.dealers.length;
          var table="<div style='height:350px; overflow: auto;'><table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование</th><th>ИНН / КПП</th><th>Адрес</th></tr></thead><tbody>";
          table+='<tr onclick="set_plugin_company(0,\'Не назначен\')"><td>0</td><td>0/0</td><td>Не назначен</td></tr>';
          for (var i=0; i<datalen; i++){
          	table+='<tr onclick="set_profile_plugin_company('+data.dealers[i].id+',\''+str_to_val(data.dealers[i].name)+'\')">';
            table+='<td><div id="dealer_data_'+data.dealers[i].id+'"></div><div id="dealer_users_'+data.dealers[i].id+'"></div>'+(i+1)+'</td>';
            table+="<td>" + data.dealers[i].name + "</td>";
            table+="<td>"+data.dealers[i].inn+"/"+data.dealers[i].kpp+"</td>";
            table+="<td>"+data.dealers[i].address+"</td>";
          	table += "</td></tr>";
          }
          table += "</tbody></table></div>";
          create_window("plugin_dealers_div","Выберите поставщика","plugin_dealers",table);
       });
      }

      function set_profile_plugin_company(id,name){
        $("#deliverer_company_id").val(id);
        $("#deliverer_company_name").val(name);
        $("#plugin_dealers").html('');
      }

      function save_profile_plugin_settings(plid){
          form_data = $("#api_config_form").serializeArray();
          var form=new Array(); 
          form['plid']=$("#plid").val();
          form['profile_id']=$("#profile_id").val();
          form['params']=form_data;
          form['plugin_tested']=$("#plugin_tested").val();
          if($("#make_order").prop('checked')) form['make_order']="on";
          if($("#plugin_enabled").prop('checked')) form['plugin_enabled']="on";
          else form['plugin_enabled']="off";
          if($("#use_on_client_search").prop('checked')) form['use_on_client_search']="on";
          else form['use_on_client_search']="off";
          if($("#trust_kross").prop('checked')) form['trust_kross']="on";
          else form['trust_kross']="off";
          form['plugin_price_type']=$("#plugin_price_type").val();
          form['deliverer_company_id']=$("#deliverer_company_id").val();
          form['delivery_days']=$("#delivery_days").val();
          if(form['plugin_tested']==0){
            $.blockUI({ css: { 
              border: 'none', 
              padding: '15px', 
              backgroundColor: '#000', 
              '-webkit-border-radius': '10px', 
              '-moz-border-radius': '10px', 
              opacity: .5, 
              color: '#fff'
              },
              message: 'Проверяем правильность введенных параметров...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
            });
            //bootbox.confirm("Вы не проверили правильность введенных данных, или ввели неправильные данные, все равно сохранить?",function(result){
              //if(result){
                api_query_array("/api/index.php",form,"save_plugin_settings").then(function(data){
                  $.unblockUI();
                  $("#edit_plugin_"+form['profile_id']+"_"+plid).html('');
                  //get_online_profiles();
                  get_profile_plugins(0,1);
                });
              //}
              //else return;
            //});
          }
          else {
            api_query_array("/api/index.php",form,"save_plugin_settings").then(function(data){
              $.unblockUI();
              $("#edit_plugin_"+form['profile_id']+"_"+plid).html('');
              //get_online_profiles();
              get_profile_plugins(0,1);
            });
          }
          

      }

      function clear_search_text_profile_plugins(id,profile_id){
        $("#"+id).val('');
        get_profile_plugins(profile_id);
      }
