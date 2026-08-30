function save_user_data(){
    var postdata=$('#profile_user_data').serializeJSON();
    postdata.action="save_user_data";
    $.ajax(
    {
	url: "/api/index.php",
	data: JSON.stringify(postdata),
	contenType: "application/json",
	type: "POST"
    }).done(function(data){
	if (data.status=="ok"){
	    alert(data.msg);
	    location.reload();
	}
	else alert(data.err);
    })
}

function save_client(){
    var postdata = $("#new_client_form").serialize();
    $.get("/modules/save_company.php?" + postdata,
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
    });
}

function fire_user(user_id){
	var send=[];
	send['user_id']=user_id;
	bootbox.confirm('Вы действительно хотите уволить сотрудника?',function(result){ 
		if(result) {
			api_query_array("/api/index.php",send,"fire_user").then(function(data){
				location.reload();
			});
		}
	})
}

function fill_form(type){
    if (type=="inn") var inn=$("#inn").val();
    if (type=="name") var org_name=$("#company_name").val();
    $.get("/modules/get_org_info.php",
	{
	    inn: inn,
	    org_name: org_name
	}
    )
    .done(function(data){
      	var obj_len=Object.keys(data).length;
      	var dirname="";
      	if (obj_len > 0){
      	    var count=data.suggestions.length;
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
      	    $("#address").val(data.suggestions[0].data.address.data.source);
      	    $("#company_name").val(data.suggestions[0].value);
      	    $("#ruk").val(data.suggestions[0].data.management.name);
      	    $("#rukdol").val(data.suggestions[0].data.management.post);
      	}
      	if (count>1){
            select_company_from_list(data);
      	    /*var select="выберите организацию\n";
      	    $.each(data.suggestions,function(i, item){
          		if(typeof(item.data.management) == 'undefined' || item.data.management === null) dirname="";
          		else dirname=item.data.management.name;
          		if (item.data.state.status=="ACTIVE") select+=(i+1)+" - "+item.value+" "+item.data.inn+"/"+item.data.kpp+" "+dirname+"\n";
      	    });
      	    var selected=prompt(select,"");
      	    if (selected>0){
              set_company_data(selected-1); */
              /*
          		$("#kpp").val(data.suggestions[selected-1].data.kpp);
          		$("#inn").val(data.suggestions[selected-1].data.inn);
          		$("#ogrn").val(data.suggestions[selected-1].data.ogrn);
          		$("#address").val(data.suggestions[selected-1].data.address.data.source);
          		$("#company_name").val(data.suggestions[selected-1].value);
          		$("#ruk").val(data.suggestions[selected-1].data.management.name);
          		$("#rukdol").val(data.suggestions[selected-1].data.management.post);
              */
      	    //}
      	}
    });
}

function select_company_from_list(data){
  var table='<table class="table table-hover"><thead><tr><th>Наименование организации</th><th>ИНН/КПП</th><th>Руководитель</th></tr></thead><tbody>'
  $.each(data.suggestions,function(i, item){
    if(typeof(item.data.management) == 'undefined' || item.data.management === null) dirname="";
    else dirname=item.data.management.name;
    if (item.data.state.status=="ACTIVE") table+='<tr onclick="set_company_id('+i+');"><td>'+item.value+'</td><td>'+item.data.inn+'/'+item.data.kpp+'</td><td>'+dirname+'</td></tr>';
  });
  table+='</tbody></table>';
  create_window_centered_blue("company_list_for_select_div","Выберите компанию для автоматического заполнения данных","company_list_for_select",table);
}

function set_company_data(id){
  if (id>0){
    $("#kpp").val(data.suggestions[id].data.kpp);
    $("#inn").val(data.suggestions[id].data.inn);
    $("#ogrn").val(data.suggestions[id].data.ogrn);
    $("#address").val(data.suggestions[id].data.address.data.source);
    $("#company_name").val(data.suggestions[id].value);
    $("#ruk").val(data.suggestions[id].data.management.name);
    $("#rukdol").val(data.suggestions[id].data.management.post);
    $("#company_list_for_select").html('');
  }
}
