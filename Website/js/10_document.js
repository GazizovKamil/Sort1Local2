var document_data={};
var selected_documents=[];
var document_details_round=10;
var document_details_fields={};
document_details_fields.my_code=0;
document_details_fields.ean13=0;
document_details_fields.article=0;
document_details_fields.brand=0;
document_details_fields.name=0;
document_details_fields.price=0;
document_details_fields.count=0;
document_details_fields.time=0;
document_details_fields.create_date=0;
document_details_fields.zakaz_id=0;
document_details_fields.company_name=0;
get_document_options();

function set_print_document_menu_a_href(document_id,type){
  switch(type){
    case 1:
      if($("#print_document_show_art_"+document_id).prop('checked')){
        $('#print_document_menu_a_'+document_id).attr('href',$('#print_document_menu_a_'+document_id).attr('href').replace('&showart=1',''));
        $('#print_document_menu_a_'+document_id).attr('href',$('#print_document_menu_a_'+document_id).attr('href')+'&showart=1');
      }
      else 
        $('#print_document_menu_a_'+document_id).attr('href',$('#print_document_menu_a_'+documentid).attr('href').replace('&showart=1',''));
      if($("#print_document_show_place_"+document_id).prop('checked')){
        $('#print_document_menu_a_'+document_id).attr('href',$('#print_document_menu_a_'+document_id).attr('href').replace('&showplace=1',''));
        $('#print_document_menu_a_'+document_id).attr('href',$('#print_document_menu_a_'+document_id).attr('href')+'&showplace=1');
      }
      else 
        $('#print_document_menu_a_'+document_id).attr('href',$('#print_document_menu_a_'+document_id).attr('href').replace('&showplace=1',''));
      break;
    case 2:
      if($("#print_tovarcheck_show_d_art_"+document_id).prop('checked'))
        $('#print_tovarcheck_d_menu_a_'+document_id).attr('href',$('#print_tovarcheck_d_menu_a_'+document_id).attr('href')+'&showart=1');
      else 
        $('#print_tovarcheck_d_menu_a_'+document_id).attr('href',$('#print_tovarcheck_d_menu_a_'+document_id).attr('href').replace('&showart=1',''));
      break;
    case 3:
      if($("#print_schet_show_d_art_"+document_id).prop('checked'))
        $('#print_schet_d_menu_a_'+document_id).attr('href',$('#print_schet_d_menu_a_'+document_id).attr('href')+'&showart=1');
      else 
        $('#print_schet_d_menu_a_'+document_id).attr('href',$('#print_schet_d_menu_a_'+document_id).attr('href').replace('&showart=1',''));
      break;
  }
}

function show_document_print_menu(document_id){
  if($("#document_print_menu_"+document_id).html()!='') {
    $("#document_print_menu_"+document_id).html('');
    return 0;
  }
  var menu='';
  menu+='<div style="border: solid black 1px; width: 240px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
  menu+='<button type="button" class="close pull-right" onclick="$(\'#document_print_menu_'+document_id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
  menu+='<table class="table table-hover">';
  menu+='<tr><td><a href="/schet.php?document_id='+document_id+'" id="print_schet_d_menu_a_'+document_id+'" target="_blank">Напечатать счет</a>\
  <div class="pull-right"><input type="checkbox" onclick="set_print_document_menu_a_href('+document_id+',3);" title="Показывать артикул" id="print_schet_show_d_art_'+document_id+'"> Арт.</div></td></tr>';
  menu+='<tr><td><a href="/print_tovar_check.php?document_id='+document_id+'" id="print_tovarcheck_d_menu_a_'+document_id+'" target="_blank">Товарный чек</a>\
  <div class="pull-right"><input type="checkbox" onclick="set_print_document_menu_a_href('+document_id+',2);" title="Показывать артикул" id="print_tovarcheck_show_d_art_'+document_id+'"> Арт.</div></td></tr>';
  menu+='<tr><td><a href="/schet_fact_simple.php?document_id='+document_id+'" target="_blank">Напечатать счет-фактуру</a></td></tr>';
  menu+='<tr><td><a href="/tn.php?document_id='+document_id+'" target="_blank">Печать товарной накладной</a></td></tr>';
  menu+='<tr><td><a href="/akt.php?document_id='+document_id+'" target="_blank">Печать акта выполненных работ</a></td></tr>';
  menu+='<tr><td><a href="/upd_simple_new.php?document_id='+document_id+'" target="_blank">Напечатать УПД</a>\
  <a class="pull-right" onclick="get_upd_xls(\'+\','+document_id+');"><img src="/new_images/excel_32.png" style="width: 30px;"></a>\
  </td></tr>';
  menu+='</table></div>';
  $("#document_print_menu_"+document_id).html(menu);
}

function show_prihod_print_menu(document_id){
  if($("#document_print_menu_"+document_id).html()!='') {
    $("#document_print_menu_"+document_id).html('');
    return 0;
  }
  var menu='';
  menu+='<div style="border: solid black 1px; width: 240px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
  menu+='<button type="button" class="close pull-right" onclick="$(\'#document_print_menu_'+document_id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
  menu+='<table class="table table-hover">';
  menu+='<tr><td><a href="/tn_prihod.php?document_id='+document_id+'" target="_blank">Печать внутр. накл. с ценами продаж</a></td></tr>';
  menu+='</table></div>';
  $("#document_print_menu_"+document_id).html(menu);
}

function show_client_return_print_menu(document_id){
  if($("#document_print_menu_"+document_id).html()!='') {
    $("#document_print_menu_"+document_id).html('');
    return 0;
  }
  var menu='';
  menu+='<div style="border: solid black 1px; width: 240px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
  menu+='<button type="button" class="close pull-right" onclick="$(\'#document_print_menu_'+document_id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
  menu+='<table class="table table-hover">';
  menu+='<tr><td><a href="/print_refund.php?document_id='+document_id+'" target="_blank">Печать акта возврата товара</a></td></tr>';
  menu+='<tr><td><a href="/ukd_simple_new.php?document_id='+document_id+'" target="_blank">Печать УКД</a><a class="pull-right" onclick="get_ukd_xls(\'+\','+document_id+');"><img src="/new_images/excel_32.png" style="width: 30px;"></a></td></tr>';
  menu+='</table></div>';
  $("#document_print_menu_"+document_id).html(menu);
}

function select_document(document_id){
  if(selected_documents.indexOf(document_id)>=0)  selected_documents.splice(selected_documents.indexOf(document_id));
  else selected_documents.push(document_id);
}

function get_documents(znak){
 var send_arr=new Array(); 
 selected_documents=[];
 var defer=$.Deferred();
    //alert(znak);
    send_arr['znak']=znak;
    if(znak=="+") var znak_ch="_plus";
    if(znak=="-") var znak_ch="_minus";
    if(znak=="rtd") var znak_ch="_rtd";
    if(znak=="rfc") var znak_ch="_rfc";
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
 api_query("/api/index.php","document_client_search"+znak_ch,"get_documents").then(function(data){
    if(typeof(data.search_document_date_from)!="undefined") $("#search_document_date_from"+znak_ch).val(data.search_document_date_from);
    if(typeof(data.search_document_date_to)!="undefined") $("#search_document_date_to"+znak_ch).val(data.search_document_date_to);
    if(typeof(data.search_document_article)!="undefined") $("#search_document_article"+znak_ch).val(data.search_document_article);
    if(typeof(data.search_document_client_name)!="undefined") $("#search_document_client_name"+znak_ch).val(data.search_document_client_name);
      var datalen=data.documents.length;
    var table="<table class=\"table table-hover\"><thead><tr><th></th><th></th><th></th><th>№</th><th>№ док.</th><th>Дата документа</th><th>Тип документа</th><th>Компания</th><th>Склад</th><th>Описание</th><th>Дата создания</th>";
    table += "<th>Менеджер</th><th>Позиций</th><th>Кол-во</th><th>Сумма</th><th>Обр.</th>";
    if(znak=="rtd") table+="<th>Принят поставщиком</th>";
    table+="<th></th></tr></thead></tbody>";
    var documents_sum=0,documents_sum_count=0,documents_sum_pos=0;
    for (var i=0; i<datalen; i++){
    	table += "<tr ondblclick=\"edit_document('delete_document_"+data.documents[i].id+"','"+znak+"');\" "+(data.documents[i].deleted=="1"?" style='background:pink;'":((znak=="-" && (data.documents[i].type==1 || data.documents[i].type==2 || data.documents[i].type==4 || data.documents[i].type==6))?" style='background:lightcyan'":""))+">\
      <td>"+(data.documents[i].is_deleted_details=="1"?"<b style='color:red;font-size: 19px;'>!</b>":"")+"</td><td>"+(i+1)+"</td><td><input type='checkbox' onchange='select_document("+data.documents[i].id+")'></td><td>"+data.documents[i].id+"</td><td>";
      if(data.documents[i].number!=""){
        table+=data.documents[i].number;
      }
      else {
        if(znak=="-") table+="0000-"+data.documents[i].id.padStart(6,"0");
        else table+=data.documents[i].number;
      }
      table+="</td><td>"+data.documents[i].document_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
      table += '<td>'+data.document_types[data.documents[i].type_id].name+'</td>';
      table += "<td>" + data.documents[i].name +' '+data.documents[i].inn+'/'+data.documents[i].kpp+ "</td><td>"+data.documents[i].sklad_name+"</td><td>"+data.documents[i].comment+"</td>";
    	table += "<td>"+convertTZ(data.documents[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
      table += "<td>"+data.documents[i].user_lastname+"</td><td>";
      let doc_all_pos=0;
      let doc_all_count=0;
      let doc_all_sum=0;
      if(typeof(data.document_det_pos[data.documents[i].id])!="undefined") {
        doc_all_pos+=parseFloat(data.document_det_pos[data.documents[i].id].positions);
        doc_all_count+=parseFloat(data.document_det_pos[data.documents[i].id].pos_count);
        doc_all_sum+=parseFloat(data.document_det_pos[data.documents[i].id].pos_sum);
      }
      if(typeof(data.document_job_pos[data.documents[i].id])!="undefined") {
        doc_all_pos+=parseFloat(data.document_job_pos[data.documents[i].id].positions);
        doc_all_count+=parseFloat(data.document_job_pos[data.documents[i].id].pos_count);
        doc_all_sum+=parseFloat(data.document_job_pos[data.documents[i].id].pos_sum);
      }
        table += doc_all_pos; documents_sum_pos+=doc_all_pos;
      table +="</td><td>";
      table += doc_all_count; documents_sum_count+=doc_all_count;
      table += '</td><td nowrap align="right">';
      table += formatNumber(doc_all_sum.toFixed(2)); documents_sum+=doc_all_sum;
      if(data.documents[i].obrabotan=="1") table+='<td><input type="checkbox" checked onchange="set_document_obrabotan('+data.documents[i].id+',this.checked)"></td>';// table+='<td><img src="/images/ok.svg" style="width:15px;"></td>';
      else table+='<td><input type="checkbox" onchange="set_document_obrabotan('+data.documents[i].id+',this.checked)"></td>';
      if(znak=="rtd"){
        if(data.documents[i].return_confirmed=="1") table+='<td><img src="/images/ok.svg" style="width:15px;"> '+data.documents[i].return_confirm_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>';
        else table+='<td></td>';
      }
      table += "</td>";
      table += "<td><form id='delete_document_"+data.documents[i].id+"'><input type=\"hidden\" name=\"document_id\" value=\""+data.documents[i].id+"\">";
      //if(data.documents[i].)
      table += "</form>";
      table+="<div class='btn-group";
      if(znak=="-")// && (parseInt(data.documents[i].type_id)!=2 && parseInt(data.documents[i].type_id)!=6)) 
        table+=" pull-right";
      table+="' style='display: flex;'>";
      if(znak=="-" && (parseInt(data.documents[i].type_id)==2 || parseInt(data.documents[i].type_id)==6)) {
        table += '<a onclick="show_document_print_menu('+data.documents[i].id+');" title="Печать"><img src="/new_images/printer.svg" class="menuimg"></a>';
      }
      if(znak=="+" && parseInt(data.documents[i].type_id)==1) {
        table += '<a onclick="show_prihod_print_menu('+data.documents[i].id+');" title="Печать"><img src="/new_images/printer.svg" class="menuimg"></a>';
      }
      if(znak=="rfc" && (parseInt(data.documents[i].type_id)==6)) {
        table+='<a onclick="show_client_return_print_menu('+data.documents[i].id +')" target="_blank" title="Печать акта возврата товара"><img src="/new_images/printer.svg" class="menuimg"></a>';
      }
      if(znak=="rtd" && (parseInt(data.documents[i].type_id)==7)) {
        table+='<a href="/tn.php?document_id='+data.documents[i].id +'&type=rtd" target="_blank" title="Печать Торг-12"><img src="/new_images/printer.svg" class="menuimg"></a>';
      }
      table += '<div id="document_print_menu_'+data.documents[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div>';
      table += "&nbsp;<a onclick=\"edit_document('delete_document_"+data.documents[i].id+"','"+znak+"');\" title='Редактировать документ'><img src='/new_images/edit.svg' class='menuimg'></a>";
    	//table += "<a onclick=\"get_document_data('document_form_"+data.documents[i].id+"','"+znak+"')\" title='Просмотреть детали'><img src='/new_images/file.svg' class='menuimg'></a>";
    	table += '<form id="document_form_'+data.documents[i].id+'" style="display:none"><input type="hidden" name="action" value="get_document_details"><input type="hidden" name="znak" value="'+znak+'">';
    	table += "<input type='hidden' name='document_id' value='"+data.documents[i].id+"'>\
      <input type='hidden' name='sklad_id' value='"+data.documents[i].sklad_id+"'>\
      <input type='hidden' name='show_deleted' value='"+($('#search_document_show_deleted'+znak_ch).prop('checked')?1:0)+"' class='show_deleted_document_details_class'>\
      <input type='hidden' name='page' value='1'><input type='hidden' name='search' value=''>\
      </form>";
    	table += "<a title='Удалить document' ";
    	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить этот документ?\',function(result){ if(result) api_query('/api/index.php','delete_document_"+data.documents[i].id+"','delete_document').then(function(data){if(data.status=='ok') get_documents('"+znak+"')});});\"><img src='/new_images/garbage.svg' class='menuimg'></a>"
    	table += "</div></td>";
    	table += "</tr>";
    }
    table+='<tr style="font-weight:bold"><td colspan="12">Итого</td><td>'+documents_sum_pos+'</td><td>'+documents_sum_count+'</td><td nowrap align="right">'+formatNumber(documents_sum.toFixed(2))+'</td><td></td>';
    if(znak=="rtd") table+='<td></td>';
    table+='<td><button onclick="get_document_details_in_sklad(\''+znak_ch+'\')" class="btn btn-xs btn-primary" type="button">Остатки на складе</button></td></tr>';
    table+='<tr><td colspan="5">Действия с документами</td><td colspan="12">';
    switch(znak){
      case "+": document.getElementById("prihod_list").innerHTML=table; break;
      case "-": 
        table+='<button type="button" class="btn btn-default btn-xs" onclick="bootbox.confirm(\'Данное действие необратимо! Дейтвительно хотите объединить документы?\',function(result){ if(result) join_documents(\''+znak+'\'); });">объединить выбр.</button>'; 
        break;
      case "rtd": document.getElementById("rtd_list").innerHTML=table; break;
      case "rfc": document.getElementById("rfc_list").innerHTML=table; break;
    }
    table+='</td></tr>';
    table+="</tbody></table><div id='document_details_in_sklad_"+znak_ch+"'></div>";
    switch(znak){
      case "+": document.getElementById("prihod_list").innerHTML=table; break;
      case "-": document.getElementById("rashod_list").innerHTML=table; break;
      case "rtd": document.getElementById("rtd_list").innerHTML=table; break;
      case "rfc": document.getElementById("rfc_list").innerHTML=table; break;
    }
    $.unblockUI();
    return defer.resolve();
 });
 return defer.promise();
}

function get_document_details_in_sklad(zn){
  var send=[];
  send['documents']=selected_documents;
  send['sale_price']=true;
  api_query_array("/api/index.php",send,"get_document_details_in_sklad").then(function(data){
    var table='<div style="height: 500px; overflow: auto;"><table class="table table-hover">';
    var len=data.sklad_details.length;
    for(var i=0; i<len; i++){
      table+='<tr><td>'+data.sklad_details[i].article+'</td><td>'+data.sklad_details[i].brand+'</td><td>'+data.sklad_details[i].name+'</td><td>'+(data.sklad_details[i].count-data.sklad_details[i].reserved_count)+'</td><td>'+data.sklad_details[i].sale_price+'</td></tr>';
    }
    table+='</table></div>';
    create_window_centered_blue("document_details_in_sklad_"+zn+"_div","Остатки на складе деталей из выбранных документов","document_details_in_sklad_"+zn,table)
  })
}

function join_documents(znak){
  var send=[];
  send['documents']=selected_documents;
  api_query_array("/api/index.php",send,"join_documents").then(function(data){
    if(data.status=="ok"){
      get_documents(znak);
    }
  })
}

function set_document_obrabotan(document_id,checked){
  var send=[];
  send['document_id']=document_id;
  send['obrabotan']=(checked?1:0);
  api_query_array("/api/index.php",send,"set_document_obrabotan").then(function(data){
    
  })
}

function clear_search_order_text(input_id){
  $('#'+input_id).val('');
  //runTextFilterOrd();
        }

function get_document_details1(document_form){
 api_query("/api/index.php",document_form,"get_document_details").then(function(data){
    var datalen=data.document_details.length;
    var table="<div class='row' style='padding:5px;'><div class='col-xs-4'><button class=\"btn btn-primary btn-sm\" onclick=\"add_new_document_detail("+$('#'+document_form+' [name=document_id]').val()+","+$('#'+document_form+' [name=sklad_id]').val()+")\">Добавить деталь</button></div>";
    table += "<div class='col-xs-4 pull-right'><div class='input-group input-group-sm'><input type='text' id='newDocumentSearch' class='form-control search_str'><label style='position: absolute; top: 0.65em;' for='newDocumentSearch' id='newDocumentSearch_label' onclick='clear_search_order_text(\"newDocumentSearch\");'></label><span class='input-group-btn'><button class='btn btn-default' type='button'>Поиск</button></span></div></div>";
    table += "</div><div id='add_new_detail_div'></div>";
    table += "<table class=\"table table-hover\"><thead><tr><th>№</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Цена</th><th>Кол-во</th><th>Срок доставки</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
      table += "<tr";
      if(data.document_details[i].deleted=="1") table+=' style="background-color: pink;"';
      table+="><td>"+(i+1)+"</td>\
      <td>\
        <a onclick='show_detail_documents("+data.document_details[i].detail_id+",0,\"document\")' title='Посмотреть движение товара'>" + data.document_details[i].article + "</a>\
        <div id='show_document_detail_documents_"+data.document_details[i].detail_id+"'></div>\
      </td><td>"+data.document_details[i].brand+"</td><td>"+data.document_details[i].name+"</td><td>"+data.document_details[i].price+"</td><td>"+data.document_details[i].count+"</td><td>"+data.document_details[i].time+"</td>";
      table += "<td><form id='delete_detail_"+data.document_details[i].detail_id+"'><input type=\"hidden\" name=\"detail_id\" value=\""+data.document_details[i].detail_id+"\"><input type=\"hidden\" name=\"document_id\" value=\""+data.document_details[i].document_id+"\"><input type=\"hidden\" name=\"sklad_id\" value=\""+data.document_details[i].sklad_id+"\"></form>";
      table += "<div class='btn-group' style='display: flex;'><button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\"  onclick=\"edit_detail('delete_detail_"+data.document_details[i].detail_id+"');\"></button>";
      table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
      table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь со склада?\',function(result){ if(result) api_query('/api/index.php','delete_detail_"+data.document_details[i].detail_id+"','delete_document_detail').then(function(data){if(data.status=='ok') get_document_details('document_form_"+data.document_details[i].document_id+"')});});\"></button>";
      table += "</div></td>";
      table += "</tr>";
    }
    table += "</tbody></table></div>";
    create_window("document_details","Детали в документе "+data.document_comment,"details_"+data.document_id,table);
//    $("#sklad_details_LongTitle").html("Детали в документе "+data.document_comment);
//    $("#sklad_details_content").html(table);
//    $("#sklad_details").show();
 });
}

function get_document_data(document_form,znak,page=1,search=''){
  $("[id^=details_]").html('');
  var table='<hr style="margin-top: 10px; margin-bottom:10px;"><ul class="nav nav-tabs">\
  <li class="active"><a data-toggle="tab" href="#document_tovary" onclick="get_document_details(\''+document_form+'\',\''+znak+'\');" id="document_tovary_link">Товары</a></li>\
  <li><a data-toggle="tab" href="#document_uslugi" onclick="get_document_jobs(\''+document_form+'\',\''+znak+'\');" id="document_uslugi_link">Услуги</a></li>\
  </ul>\
  <div class="tab-content">\
    <div id="document_tovary" class="tab-pane fade active in" style="padding-top:5px;"></div>\
    <div id="document_uslugi" class="tab-pane fade" style="padding-top:5px;"></div>\
  </div>';
  var document_id=$("#"+document_form+" input[name=document_id]").val();
  var id="details_"+document_id;
  //create_window(id+"_div","Товары и услуги, документ №"+document_id,id,table);
  document.getElementById(id).innerHTML=table;
  $('#document_form_'+document_id+' input[name=page]').val(page);
  $('#document_form_'+document_id+' input[name=search]').val(search);
  get_document_details(document_form,znak);
}

function get_document_data_for_export(document_form,znak){
  $("[id^=details_]").html('');
  var table='<ul class="nav nav-tabs">\
  <li class="active"><a data-toggle="tab" href="#document_tovary" onclick="get_document_details(\''+document_form+'\',\''+znak+'\');" id="document_tovary_link">Товары</a></li>\
  <li><a data-toggle="tab" href="#document_uslugi" onclick="get_document_jobs(\''+document_form+'\',\''+znak+'\');" id="document_uslugi_link">Услуги</a></li>\
  </ul>\
  <div class="tab-content">\
    <div id="document_tovary" class="tab-pane fade active in" style="padding-top:5px;"></div>\
    <div id="document_uslugi" class="tab-pane fade" style="padding-top:5px;"></div>\
  </div>';
  var document_id=$("#"+document_form+" input[name=document_id]").val();
  var id="details_for_export_"+document_id;
  create_window(id+"_div","Товары и услуги, документ №"+document_id,id,table);
  get_document_details(document_form,znak);
}

var filterDocumentDetails = '';
// var today = new Date();
// today.setFullYear(today.getFullYear() - 10);
// filterDocumentDetails['name'] = "";
// filterDocumentDetails['date_from'] = today.toISOString().split("T")[0];
// filterDocumentDetails['date_to'] = new Date().toISOString().split("T")[0];

function get_document_details(document_form,znak, filterZnak=false){
  var send=$('#'+document_form).serializeJSON();
  if (filterDocumentDetails != ''){
    send['filterDocDetails'] = filterDocumentDetails;
  }
  document.getElementById("document_tovary").innerHTML="Загружаю...";
 api_query_array("/api/index.php",send,"get_document_details").then(function(data){
    var datalen=data.document_details.length;
    var table="<div class='row' style='padding:5px;'>";
    table+="<div class='col-xs-2'>";
    table+="<button class=\"btn btn-primary btn-xs\" onclick=\"add_new_document_detail("+$('#'+document_form+' [name=document_id]').val()+","+$('#'+document_form+' [name=sklad_id]').val()+",\'"+znak+"\')\">Добавить деталь</button></div>";
    //var znak=$("#"+document_form+" input[name=znak]").val();
    if(znak=="+"){
      table+="<div class='col-xs-2'><button class=\"btn btn-primary btn-xs\" onclick=\"add_document_details_from_zakazes("+$('#'+document_form+' [name=document_id]').val()+","+$('#'+document_form+' [name=sklad_id]').val()+",\'"+znak+"\')\">Добавить из заказов</button>\
      <div id='add_zakaz_details_by_document_id_list' style='position: absolute; top:-400px; left:-100px;'></div></div>";
      table += '<div class="col-xs-2"><span class="btn btn-success fileinput-button btn-xs">\
        <span>Загрузить файл</span>\
      	<form id="fileupload2">\
      	<input type="hidden" name="document_id" value="'+data.document_id+'">\
      	<input type="hidden" name="action" value="upload_file">';
      table += '<input type="hidden" name="sklad_id" value="'+$('#'+document_form+' [name=sklad_id]').val()+'">';
      table += '<input id="fileupload_2" type="file" name="files[]" multiple>\
      	</form>\
        </span></div>';
      table+='<div class="col-xs-2"><b>\
        Позиций: '+data.details_count+', сумма: '+parseFloat(data.document_sum).toFixed(2)+'</b>\
        </div>';
      table+='<div class="col-xs-1">\
      <div class="row"><a href="/print_price_for_detail.php?document_id='+data.document_id+'" target="_blank" title="Печать ценника всех деталей документа"><img src="/new_images/printer.svg" class="menuimg"></a>\
      <a href="/print_price_tag_for_detail.php?document_id='+data.document_id+'" target="_blank" title="Печать ценника на витрину"><img src="/images/price-tag.png" class="menuimg"></a>\
      <a onclick="get_document_details_xls('+data.document_id+');" title="Выгрузить список деталей в формате xlsx"><img src="/new_images/excel_32.png" style="width: 30px;"></a>\
      </div>\
      </div>';
    }
    if(znak=="+") table += "<div class='col-xs-3'>";
    else table += "<div class='col-xs-9'>";
    table+="<div class='input-group input-group-sm  pull-right'>";
    table += "<span id='document_search_"+data.document_id+"'><input type='text' id='searchDocumentDetail' class='form-control input-sm search_str' name='search'";
    if (data.hasOwnProperty('search')) table+="value='"+data.search+"'";
    else table+="value=''";
    table += "onkeyup='if(event.keyCode===13 || event.keyCode===27) {$(\"#"+document_form+" [name=page]\").val(1);get_document_details(\""+document_form+"\",\""+znak+"\");}'\
     onchange='$(\"#"+document_form+" [name=search]\").val($(\"#document_search_"+data.document_id+" input#searchDocumentDetail\").val());'><label style='position: absolute; top: 0.65em;' for='searchDocumentDetail' id='searchDocument_label' onclick='clear_search_order_text(\"searchDocumentDetail\");$(\"#"+document_form+" [name=search]\").val(\"\")'></label></span>";
    table += "<span class='input-group-btn'><button class='btn btn-default btn-sm' type='button' onclick='$(\"#"+document_form+" [name=page]\").val(1);get_document_details(\""+document_form+"\",\""+znak+"\")'>Поиск</button></span></div></div>";
    table += "</div><div id='add_new_document_detail' style='position: absolute; top:100px;'></div><div id='select_price_cols_"+data.document_id+"'></div>";
    table += '<div id="progress" class="progress col-sm-9"  style="display:none;">\
        <div class="progress-bar progress-bar-success"></div>\
    </div>'; 
    table += '<div style="height: 30px; border-bottom: 1px solid #337ab7;"><ul class="pagination pagination-sm" style="margin: -1px 0px;">';
    var x=0,y=0,xx=0,yy=0;
    if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
    else var selected_page=1;
    for (var i=1; i<=data.document_pages; i++){
    	if(i>(selected_page+6) && i<(data.document_pages-1)){
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
    		    table += '><a href="#" onclick="$(\'#'+document_form+' input[name=page]\').val(\''+i+'\');';
    		    if($('#document_search_'+data.document_id+' [name=search]').val()!="") table += '$(\'#'+document_form+' input[name=search]\').val(\''+data.search+'\');';
    		    table += 'get_document_details(\''+document_form+'\',\''+znak+'\')">...</a></li>';
    		}
    		if (x==1) xx++;
    	}
    	else {
    	    if (y==1) {
        		if (yy==0){
        		    table += '<li';
        		    table += '><a href="#" onclick="$(\'#'+document_form+' input[name=page]\').val(\''+i+'\');';
        		    if($('#document_search_'+data.document_id+' [name=search]').val()!="") table += '$(\'#'+document_form+' input[name=search]\').val(\''+data.search+'\');';
        		    table += 'get_document_details(\''+document_form+'\',\''+znak+'\')">...</a></li>';
        		}
        		if (y==1) yy++;
    	    }
    	    else {
        		table += '<li';
        		if(selected_page==i) table+= " class='active'";
        		table += '><a href="#" onclick="$(\'#'+document_form+' input[name=page]\').val(\''+i+'\');';
        		if($('#document_search_'+data.document_id+' [name=search]').val()!="") table += '$(\'#'+document_form+' input[name=search]\').val(\''+data.search+'\');';
        		table += 'get_document_details(\''+document_form+'\',\''+znak+'\')">'+i+'</a></li>';
    	    }
    	}
    }
    table += '</ul></div>'; 
    table += "<table id=\"table_document_details\" class=\"table table-hover\" style=\"display:block; max-height: 100vh; overflow:auto; width:100%; min-height:150px;\">\
    <thead><tr><th>№</th><th title='Обработан'>Обр.</th><th>Мой код</th><th>EAN13</th><th>Артикул</th><th>Брэнд</th><th><a onclick='print_name_filter(\""+document_form+"\",\""+znak+"\")'>Наименование";
    if (filterZnak){
      if (filterDocumentDetails == 'name'){
        table += '▲';
      }else if (filterDocumentDetails == 'name desc'){
        table += '▼';
      }
    }
    table += "</a></th><th style='text-align: right'>Цена</th><th nowrap>к-во</th><th>Сумма</th>"+(znak=="+"?"<th style='text-align: right'>расч. цена прод.</th><th style='text-align: right'>моя цена прод.</th>":"")+"<th>Срок доставки</th><th><a onclick='print_create_date_filter(\""+document_form+"\",\""+znak+"\")'>Создан";
    if (filterZnak){
      if (filterDocumentDetails == 'date'){
        table += '▲';
      }else if (filterDocumentDetails == 'date desc'){
        table += '▼';
      }
    }
    table += "</a></th><th>№ заказа</th><th>Клиент</th><th>печать</th><th style='text-align: right'><a><img src='/new_images/settings.png' style='width:16px;'></a></th></tr></thead><tbody>";
    var doc_dets=[],pos_count=0;
    for (var i=0; i<datalen; i++){
      var detail_price=0,sale_price=0;
      //if(typeof(doc_dets[data.document_details[i].id])!="undefined") continue;
      doc_dets[data.document_details[i].id]=1;
      pos_count++;
      if(znak=="rtd") {
        detail_price=parseFloat(data.document_details[i].dealer_price);
        sale_price=parseFloat(data.document_details[i].price);
      }
      else {
        detail_price=parseFloat(data.document_details[i].price);
        sale_price=parseFloat(data.document_details[i].sale_price);
      }
    	table += "<tr";
      if(data.document_details[i].deleted=="1") table+=' style="background-color: pink;"';
      table+="><td>"+(parseInt((data.selected_page-1)*20)+pos_count)+"<div id='edit_document_detail_"+data.document_details[i].id+"'></div></td>\
      <td><input type='checkbox' id='checkbox_"+data.document_details[i].id+"' onchange='checked_document_detail(" + data.document_details[i].id + ") '";
      if(data.document_details[i].checked==1) table+=" checked='checked'";
      table+="/></td>";
      table+="<td>" + data.document_details[i].my_code + "</td>\
      <td>" + data.document_details[i].ean13 + "</td>\
      <td nowrap>\
        <a onclick='navigator.clipboard.writeText(\""+data.document_details[i].article+"\");'><img src='/new_images/copy.png' style='width:18px;' title='скопировать артикул в буфер обмена'></a>\
        <a onclick='show_detail_documents("+data.document_details[i].detail_id+",0,\"document\")' title='Посмотреть движение товара'>" + data.document_details[i].article + "</a>\
        <div id='show_document_detail_documents_"+data.document_details[i].detail_id+"'></div>\
      </td>\
      <td>"+data.document_details[i].brand+"</td>\
      <td>"+data.document_details[i].name+"</td>\
      <td align='right' nowrap>"+formatNumber(detail_price)+"</td>\
      <td>"+data.document_details[i].count+"</td>\
      <td align='right' nowrap>"+formatNumber((data.document_details[i].count*detail_price).toFixed(2))+"</td>";

      //if(sale_price==0){
        //let detail_price=parseFloat($("#"+form_id+" input[name=price]").val());
        let detail_markup=0;
        if(znak=="+"){
          if(typeof(document_data.diff_markup)!="undefined" && document_data.diff_markup.length>0){
            
            for (let i=0; i<document_data.diff_markup.length; i++){
              if(detail_price>parseFloat(document_data.diff_markup[i].min_sum) && detail_price<=parseFloat(document_data.diff_markup[i].max_sum)){
                detail_markup=parseFloat(document_data.diff_markup[i].value);
              }
            }
            if(detail_markup==0 && document_data.diff_markup[document_data.diff_markup.length-1].max_sum==0 && detail_price>parseFloat(document_data.diff_markup[document_data.diff_markup.length-1].min_sum)){
              detail_markup=parseFloat(document_data.diff_markup[document_data.diff_markup.length-1].value);
            }
            table+='<td align="right" nowrap>'+formatNumber((Math.ceil(detail_price+(detail_price/100)*detail_markup)).toFixed(2))+'</td>';
          }
          else {
            if(typeof(document_data.sklad_markup)!="undefined"){
              table+='<td align="right" nowrap>'+formatNumber((Math.ceil(detail_price+(detail_price/100)*parseFloat(document_data.sklad_markup))).toFixed(2))+'</td>';
            }
          }
        }
      //}
      //else {
      //  table+='<td align="right" nowrap>'+formatNumber(sale_price.toFixed(2))+'</td>';
      //}

      if(znak=="+") 
        table+="<td align='right' nowrap><input type='text' value='"+formatNumber(sale_price.toFixed(2))+"' id='sale_price_"+data.document_details[i].id+"' style='width:75px;' onchange='save_document_detail_sale_price("+data.document_details[i].id+","+data.document_details[i].document_id+",this.value,"+data.document_details[i].detail_id+",\""+data.document_details[i].article+"\",\""+data.document_details[i].brand+"\")'></td>";
      table+="<td>"+data.document_details[i].time+"</td><td>"+convertTZ(data.document_details[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
      table+='<td>'+(data.document_details[i].zakaz_id!==null?data.document_details[i].zakaz_id:"")+'</td><td>'+(data.document_details[i].company_name!==null?data.document_details[i].company_name:"")+'</td>';
      table+="<td><input type='checkbox' id='print_checkbox_"+data.document_details[i].id+"' onchange='print_document_detail(" + data.document_details[i].id + ") '";
      if(data.document_details[i].print==1) table+=" checked='checked'";
      table+="/></td>";
      table += "<td><form id='delete_detail_"+data.document_details[i].id+"'>\
      <input type=\"hidden\" name=\"document_detail_id\" value=\""+data.document_details[i].id+"\">\
      </form>";
      
    	table += "<div class='btn-group' style='display: flex;'>\
      <a href='/print_price_for_detail.php?document_detail_id="+data.document_details[i].id+"' target='_blank'  title='Печать ценника детали'><img src='/new_images/printer.svg' class='menuimg'></a> \
      <a onclick=\"edit_document_detail('delete_detail_"+data.document_details[i].id+"','"+znak+"');\"><img src='/new_images/edit.svg' class='menuimg'></a>";
    	table += " <a onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь со склада?\',function(result){ if(result) api_query('/api/index.php','delete_detail_"+data.document_details[i].id+"','delete_document_detail').then(function(data){if(data.status=='ok') get_documents('"+znak+"');get_document_details('document_form_"+data.document_details[i].document_id+"','"+znak+"');});});\">\
      <img src='/new_images/garbage.svg' class='menuimg'>\
      </a>";
      if(znak=="+")
        table+= ' <a onclick="show_document_detail_menu('+data.document_details[i].id+','+data.document_details[i].document_id+',\''+znak+'\')"><img src="/new_images/list.svg" style="width:20px;"></a>\
        <div id="document_detail_menu_'+data.document_details[i].id+'" style="position:absolute; z-index:10; top: -24px; left: -150px;"></div>';
    	table += "</div></td>";
    	table += "</tr>";
    }
    table += "</tbody></table>";
    //table+="<script>file_uploader(2);</script>";
    //$("[id^=details_]").html('');
    //create_window("document_details_div_"+data.document_id,"Детали в документе "+data.document_comment,"details_"+data.document_id,table);
    document.getElementById("document_tovary").innerHTML=table;
    var znakch='minus';
    if(znak=="+") znakch="plus";
    setTimeout(place_to_center,200,"add_new_document"+znakch);
    file_uploader(2);
 });
}

function save_document_detail_sale_price(id,document_id,value,detail_id,article,brand){
  var send=[];
  send['id']=id;
  send['document_id']=document_id;
  send['sale_price']=value;
  send['detail_id']=detail_id;
  send['article']=article;
  send['brand']=brand;
  api_query_array("/api/index.php",send,"save_document_detail").then(function(data){
    //if(data.status=="ok") bootbox.alert("Цена детали успешно изменена");
  })
}

function print_name_filter(document_form,znak) {
  if (filterDocumentDetails == 'name'){
    filterDocumentDetails = 'name desc';
  }else{
    filterDocumentDetails = 'name';
  }
  get_document_details(document_form,znak, true);
  // var table='<div>';
  // table += "<input placeholder='фильтр по наименованию' type='text' class='form-control' id='filter_name' value='"+filterDocumentDetails['name']+"' onkeyup='filterDocumentDetails[\"name\"]=value;filteringDocumentDetail();'></input>";
  // table += "</div>";
  // create_window("div_select_name_filter","Введите наименование",'select_name_filter',table);
  //sort_filter(field_name,tab);
}

function print_create_date_filter(document_form,znak) {
  if (filterDocumentDetails == 'date'){
    filterDocumentDetails = 'date desc';
  }else{
    filterDocumentDetails = 'date';
  }
  get_document_details(document_form,znak, true);
  // var table='<div style="display: flex;">';
  // table += "<input type='date' class='form-control' value='"+filterDocumentDetails['date_from']+"' onchange='filterDocumentDetails[\"date_from\"]=value;filteringDocumentDetail();'></input>&nbsp_&nbsp<input type='date' class='form-control' value='"+filterDocumentDetails['date_to']+"' onchange='filterDocumentDetails[\"date_to\"]=value;filteringDocumentDetail();'></input>";
  // table += "</div>";
  // create_window("div_select_date_filter","Выберите даты",'select_date_filter',table);
  //sort_filter(field_name,tab);
}

function filteringDocumentDetail(){
  // var filter = filterDocumentDetails['name'].toLowerCase();
  // var table = document.getElementById("table_document_details");
  // var rows = table.getElementsByTagName("tr");
  
  // var startDate = moment(filterDocumentDetails['date_from']).toDate();
  // var endDate = moment(filterDocumentDetails['date_to']).toDate();
  // for (var i = 1; i < rows.length; i++) {
  //     var name = rows[i].cells[4].textContent.toLowerCase();
  //     var dateToCheck =  moment(rows[i].cells[10].textContent.split(' ')[0], 'DD.MM.YYYY').toDate();
      
  //     if (name.includes(filter) && dateToCheck >= startDate && dateToCheck <= endDate) {
  //         rows[i].style.display = "";
  //     } else {
  //         rows[i].style.display = "none";
  //     }
  // }
}

function get_document_jobs(document_form,znak){
  api_query("/api/index.php",document_form,"get_document_jobs").then(function(data){
     var datalen=data.document_jobs.length;
     var table="<div class='row' style='padding:5px;'>";
     table+="<div class='col-xs-3'>";
     table+="<button class=\"btn btn-primary btn-sm\" onclick=\"add_new_document_job("+$('#'+document_form+' [name=document_id]').val()+","+$('#'+document_form+' [name=sklad_id]').val()+",\'"+znak+"\')\">Добавить услугу</button></div>";
     //if(znak=="+") 
     //   table += "<div class='col-xs-9'>";
     //else 
        table += "<div class='col-xs-9'>";
     table+="<div class='input-group input-group-sm  pull-right'>";
     table += "<span id='document_search_"+data.document_id+"'><input type='text' id='searchDocumentJob' class='form-control input-sm search_str' name='search'";
     if (data.hasOwnProperty('search')) table+="value='"+data.search+"'";
     else table+="value=''";
     table += " onchange='$(\"#"+document_form+" [name=search]\").val($(\"#document_search_"+data.document_id+" input#searchDocumentJob\").val());'><label style='position: absolute; top: 0.65em;' for='searchDocumentJob' id='searchDocument_label' onclick='clear_search_order_text(\"searchDocumentJob\");$(\"#"+document_form+" [name=search]\").val(\"\");'></label></span>";
     table += "<span class='input-group-btn'><button class='btn btn-default btn-sm' type='button' onclick='$(\"#"+document_form+" [name=page]\").val(1);get_document_jobs(\""+document_form+"\",\""+znak+"\")'>Поиск</button></span></div></div>";
     table += "</div><div id='add_new_document_job'></div><div id='select_price_cols_"+data.document_id+"'></div>";
     table += '<div id="progress" class="progress col-sm-9"  style="display:none;">\
         <div class="progress-bar progress-bar-success"></div>\
     </div>';
     table += '<div id="edit_document_job"></div><div style="height: 50px; display: none;"><ul class="pagination pagination-sm">';
     table += '</ul></div>';
     table += "<table class=\"table table-hover\"><thead><tr><th>№</th><th>Наименование услуг</th><th>Цена</th><th>Кол-во</th><th>Сумма</th><th>Создан</th><th>Исполнители</th><th></th></tr></thead><tbody>";
     for (var i=0; i<datalen; i++){
       table += "<tr><td>"+(i+1)+"<div id='edit_document_job_"+data.document_jobs[i].id+"'></div></td><td>" + data.document_jobs[i].job_name + "</td>\
       <td align='right' nowrap>"+formatNumber(data.document_jobs[i].price)+"</td><td>"+data.document_jobs[i].count+"</td>\
       <td align='right' nowrap>"+formatNumber((data.document_jobs[i].count*data.document_jobs[i].price).toFixed(2))+"</td><td>"+convertTZ(data.document_jobs[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
       table+='<td>';
       var jobempl=data.job_empl[data.document_jobs[i].id];
       var x=0,zakaz_jobs={};zakaz_empproc={};
       for(var j in jobempl){
        if(typeof(zakaz_jobs[data.document_jobs[i].id])=="undefined") zakaz_jobs[data.document_jobs[i].id]={};
        if(typeof(zakaz_jobs[data.document_jobs[i].id][jobempl[j].zakaz_job_id])=="undefined") zakaz_jobs[data.document_jobs[i].id][jobempl[j].zakaz_job_id]=0;
        zakaz_jobs[data.document_jobs[i].id][jobempl[j].zakaz_job_id]++;
        if(typeof(zakaz_empproc[data.document_jobs[i].id])=="undefined") zakaz_empproc[data.document_jobs[i].id]={};
        if(typeof(zakaz_empproc[data.document_jobs[i].id][jobempl[j].employee_id])=="undefined") zakaz_empproc[data.document_jobs[i].id][jobempl[j].employee_id]=0;
        zakaz_empproc[data.document_jobs[i].id][jobempl[j].employee_id]+=parseFloat(jobempl[j].proc);
       }
       //console.log(zakaz_jobs);
       //console.log(zakaz_empproc);
       var shown={};
       for(var j in jobempl){
        if(x>0) table+="<br>";
        if(typeof(shown[jobempl[j].employee_id])=="undefined")
          table+=jobempl[j].name+' '+jobempl[j].surname+' '+jobempl[j].lastname+' | проц.уч:'+(zakaz_empproc[data.document_jobs[i].id][jobempl[j].employee_id]/(Object.keys(zakaz_jobs[data.document_jobs[i].id]).length))+"%";
        x++;
        shown[jobempl[j].employee_id]=1;
       }
       table+='</td>';
       table += "<td><form id='delete_job_"+data.document_jobs[i].id+"'>\
       <input type=\"hidden\" name=\"document_job_id\" value=\""+data.document_jobs[i].id+"\">\
       </form>";
       table += "";
       table += "<div class='btn-group' style='display: flex;'>\
       <a onclick=\"edit_document_job("+data.document_jobs[i].id+",'"+znak+"');\"><img src='/new_images/edit.svg' class='menuimg'></a>";
       table += " <a onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь со склада?\',function(result){ if(result) api_query('/api/index.php','delete_job_"+data.document_jobs[i].id+"','delete_document_job').then(function(data){if(data.status=='ok') get_documents('"+znak+"');get_document_jobs('document_form_"+data.document_jobs[i].document_id+"','"+znak+"');});});\">\
       <img src='/new_images/garbage.svg' class='menuimg'>\
       </a>";
       table += "</div></td>";
       table += "</tr>";
     } 
     table += "</tbody></table>";
     /*table+="\
     <script>\
        file_uploader(2);\
     </script>"; */
     //$("[id^=details_]").html('');
     //create_window("document_details_div_"+data.document_id,"Детали в документе "+data.document_comment,"details_"+data.document_id,table);
     document.getElementById("document_uslugi").innerHTML=table;
  });
 }

function select_sklad(doc_type){
    api_query("/api/index.php","some_form","get_sklads").then(function(data){
    var datalen=data.sklads.length;
    var table="";
    table+="<table class=\"table table-hover\"><tr><th>№</th><th>Наименование</th><th>Адрес</th><th>Описание</th><th></th></tr>";
    for (var i=0; i<datalen; i++){
      table += "<tr onclick=\"$('#sklad_id_"+doc_type+"').val("+data.sklads[i].id+"); $('#sklad_name_"+doc_type+"').val('"+data.sklads[i].name.replaceAll("'","&apos;").replaceAll('"',"&quot;")+"'); $(\'#select_sklad_"+doc_type+"\').hide();\"><td>"+(i+1)+"</td><td>" + data.sklads[i].name + "</td><td>"+data.sklads[i].address+"</td><td>"+data.sklads[i].descr+"</td>";
      table += "</tr>";
    }
    create_window("select_sklad_"+doc_type,"Выберите склад","sklad_list_new_"+doc_type,table)
 });
}

function select_dealer(znakchar){
  var send=[];
  send['search_clients_dealer_name']=$("#company_name_"+znakchar).val();
    api_query_array("/api/index.php",send,"get_dealers").then(function(data){
    var datalen=data.dealers.length;
    var table="";
    table+='<div style="width: 550px; height:300px; overflow:auto;"><table class="table table-hover"><thead><tr><th>№</th><th>Наименование</th><th>кр. наим.</th><th>Адрес</th><th>ИНН/КПП</th><th></th></tr></thead><tbody>';
    for (var i=0; i<datalen; i++){
    	table += "<tr onclick=\"$('#company_id_"+znakchar+"').val("+data.dealers[i].id+"); $('#company_name_"+znakchar+"').val('"+data.dealers[i].name.replace(/\"/g,"")+"'); $(\'#select_dealer_"+znakchar+"\').hide();select_dealer_dogovor('"+znakchar+"',1)\"><td>"+(i+1)+"</td>\
    		<td>" + data.dealers[i].name + "</td><td>"+data.dealers[i].short_name+"</td><td>"+data.dealers[i].address+"</td><td>"+data.dealers[i].inn+"/"+data.dealers[i].kpp+"</td>";
    	table += "</tr>";
    }
    table+='</tbody></table></div>';
    $("#new_document_form_"+znakchar+" input[name=company_name]").attr("placeholder","Начните набирать поставщика");
    create_window("select_dealer_"+znakchar,"Выберите поставщика","dealer_list_new_"+znakchar,table);
 });
}

function select_dealer_dogovor(znakchar,select_default=0){
  var send=[];
  send['company_id']=$("#company_id_"+znakchar).val();
    api_query_array("/api/index.php",send,"get_company_dogovors").then(function(data){
    var datalen=data.dogovors.length;
    if(datalen==1 && select_default==1){
      i=0;
      $("#company_dogovor_id_"+znakchar).val(data.dogovors[i].id); $('#company_dogovor_name_'+znakchar).val("договор № "+(data.dogovors[i].num!=''?data.dogovors[i].num:data.dogovors[i].id)+" "+data.dogovors[i].descr.replace(/\"/g,"")); $('#select_dealer_dogovor_'+znakchar).hide();
    }
    else {
      if(datalen>1){
        var table="";
        table+='<div style="width: 550px; height:300px; overflow:auto;"><table class="table table-hover"><thead>\
        <tr><th>№</th><th>Наименование</th><th>время кр. лимита.</th><th>Кр. лимит</th></tr></thead><tbody>';
        for (var i=0; i<datalen; i++){
          table += "<tr onclick=\"$('#company_dogovor_id_"+znakchar+"').val("+data.dogovors[i].id+"); $('#company_dogovor_name_"+znakchar+"').val('договор № "+(data.dogovors[i].num!=''?data.dogovors[i].num:data.dogovors[i].id)+" "+data.dogovors[i].descr.replace(/\"/g,"")+"'); $(\'#select_dealer_dogovor_"+znakchar+"\').hide();\"><td>"+(i+1)+"</td>\
            <td>" + data.dogovors[i].descr + "</td><td>"+data.dogovors[i].credit_limit_time+"</td><td>"+data.dogovors[i].credit_limit+"</td>";
          table += "</tr>";
        }
        table+='</tbody></table></div>';
        create_window("select_dealer_dogovor_"+znakchar,"Выберите договор","dealer_dogovor_list_new_"+znakchar,table);
      }
      else {
        $("#company_dogovor_id_"+znakchar).val(0); 
        $('#company_dogovor_name_'+znakchar).val("договор не заведен"); 
        $('#select_dealer_dogovor_'+znakchar).hide();
      }
    }
 });
}

function select_client(znakchar,page=1){
  var send=[];
  send['search_clients_client_name']=$("#company_name_"+znakchar).val();
  send['page']=page;
    api_query_array("/api/index.php",send,"get_clients").then(function(data){
    var datalen=data.clients.length;
    //var table="";
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
				table += '><a href="#" onclick="';
				table += 'select_client(\''+znakchar+'\','+i+')">...</a></li>';
			}
			if (x==1) xx++;
		}
		else {
			if (y==1) {
			if (yy==0){
				table += '<li';
				table += '><a href="#" onclick="';
				table += 'select_client(\''+znakchar+'\','+i+')">...</a></li>';
			}
			if (y==1) yy++;
			}
			else {
			table += '<li';
			if(selected_page==i) table+= " class='active'";
			table += '><a href="#" onclick="';
			table += 'select_client(\''+znakchar+'\','+i+')">'+i+'</a></li>';
			}
		}
    }
    table += '</ul></div>';
    table+="<div style=\"width: 550px; height:350px; overflow:auto;\"><table class=\"table table-hover\"><tr><th>№</th><th>Наименование</th><th>Адрес</th><th>ИНН/КПП</th><th></th></tr>";
    for (var i=0; i<datalen; i++){
    	table += "<tr onclick=\"$('#company_id_"+znakchar+"').val("+data.clients[i].id+"); $('#company_name_"+znakchar+"').val('"+data.clients[i].name.replace(/\"/g,"")+"'); $(\'#select_client_"+znakchar+"\').hide();\">\
        <td>"+((page-1)*20+i+1)+"</td>\
    		<td>" + data.clients[i].name + "</td><td>"+data.clients[i].address+"</td><td>"+data.clients[i].inn+"/"+data.clients[i].kpp+"</td>";
    	table += "</tr>";
    }
    table+='</tbody></table></div>';
    create_window("select_client_"+znakchar,"Выберите покупателя","dealer_list_new_"+znakchar,table);
 });
}

function select_doc_type(form,znak){
  switch(znak){ 
    case "+": var znakchar="plus"; break;
    case "rtd": var znakchar="rtd"; break;
    case "-": var znakchar="minus"; break;
    case "rfc": var znakchar="rfc"; break;
  }
    api_query("/api/index.php",form,"get_document_types").then(function(data){
    var datalen=data.document_types.length;
    var table="";
    table+="<table class=\"table table-hover\"><tbody>";
    for (var i=0; i<datalen; i++){
    	if (znak==data.document_types[i].znak && data.document_types[i].id!="6"){
    	    table += "<tr style=\"cursor:pointer;\" onclick=\"change_document_type("+data.document_types[i].id+");$('#type_id_"+znakchar+"').val("+data.document_types[i].id+"); $('#type_id_name_"+znakchar+"').val('"+data.document_types[i].descr.replace(/\"/g,"")+"'); $(\'#select_doc_type_"+znakchar+"\').hide();\">\
    		<td>"+data.document_types[i].descr+"</td>";
    	    table += "</tr>";
    	}
    }
    table+='</tbody></table>';
    create_window("select_doc_type_"+znakchar,"Выберите тип документа","doc_types_list_new_"+znakchar,table);
 });
}

function change_document_type(type){
  switch(parseInt(type)){
    case 1: break;
    case 5: break;
    case 6:
      var data_html='<label for="company_name" class="col-sm-5 col-form-label text-nowrap">Выберите покупателя</label>\
      <div class="col-sm-9">\
        <input type="hidden" name="company_id" id="company_id_-" value="">\
        <input type="text" class="form-control search_str" name="company_name" id="company_name_-" onclick="select_client(\'-\');" value="" readonly placeholder="Нажмите чтобы выбрать"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_name_-" id="company_name_label" onclick="clear_search_order_text(\'company_name_-\');"></label>\
        <div id="dealer_list_new_-"></div>\
      </div>';
      $("#document_client_deliverer_row").html(data_html);
      break;
  }
}

function save_document(znak){
  switch(znak){ 
    case "+": var znakchar="plus"; break;
    case "rtd": var znakchar="rtd"; break;
    case "-": var znakchar="minus"; break;
    case "rfc": var znakchar="rfc"; break;
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
    message: 'Сохраняем документ...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query("/api/index.php","new_document_form_"+znakchar,"save_document").done(function(data){
	if (data.status=="ok"){
	    switch(znak){
        case "+": var znakchar="plus"; break;
	      case "-": var znakchar="minus"; break;
        case "rtd": var znakchar="rtd"; break;
        case "rfc": var znakchar="rfc"; break;
      }
	    $('#add_new_document'+znakchar).hide();
      //$("#search_document_client_name_"+znakchar).val('');
      //$("#search_document_client_name_"+znakchar).val('');
      const date = new Date();

      let day = date.getDate();
      let month = date.getMonth() + 1;
      let year = date.getFullYear();
      if($("#search_document_date_to_"+znakchar).val()=="") $("#search_document_date_to_"+znakchar).val(year+'-'+month+'-'+day);
      get_documents(znak).then(function(data1){
        if($("#new_document_form_"+znakchar+" input[name=is_new]").val()==1){
          edit_document("delete_document_"+data.document_id,znak);
          $.unblockUI();
        }
        else $.unblockUI();
      })
      
	}
    });
}

function print_edit_document_form(data,znak,upd=0){
  switch(znak){ 
    case "+": var znakchar="plus"; break;
    case "rtd": var znakchar="rtd"; break;
    case "-": var znakchar="minus"; break;
    case "rfc": var znakchar="rfc"; break;
  }
  var data_html='';  
  data_html+='\
      <button class="btn btn-primary btn-xs" onclick="save_document(\''+znak+'\');">Сохранить</button>\
      <button class="btn btn-secondary pull-right btn-xs" onclick="$(\'#new_document'+znakchar+'\').html(\'\');">Закрыть</button><br><br>\
      <form id="new_document_form_'+znakchar+'">\
      <div class="form-group row">\
      <div class="col-sm-4">\
      <label for="sklad_name" class="col-sm-4 col-form-label text-nowrap">Склад</label>\
      </div>\
      <div class="col-sm-8">\
      <input type="hidden" name="is_new" value="0">\
      <input type="hidden" name="sklad_id" id="sklad_id_'+znakchar+'" value="'+data.document.sklad_id+'">\
      <input type="text" class="form-control input-mini" name="sklad_name" id="sklad_name_'+znakchar+'" value="'+data.sklad_name+'" onclick="select_sklad(\''+znakchar+'\');" readonly placeholder="Нажмите чтобы выбрать">\
      <input type="hidden" name="document_id" id="document_id" value="'+data.document.id+'">\
      <div id="sklad_list_new_'+znakchar+'"></div>\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4">\
      <label for="company_name" class="col-sm-5 col-form-label text-nowrap">';
      if(znakchar=="plus" || znakchar=="rtd") data_html+='Поставщик';
      else data_html+='Клиент';
      data_html+='</label>\
      </div>\
      <div class="col-sm-4">\
      <input type="hidden" name="company_id" id="company_id_'+znakchar+'" value="'+data.document.company_id+'">\
      <input type="text" class="form-control input-mini" name="company_name" id="company_name_'+znakchar+'"';
      if(znakchar=="plus" || znakchar=="rtd") {
        data_html+=' onclick="this.value=\'\'; $(\'#company_id_'+znakchar+'\').val(\'0\'); select_dealer(\''+znakchar+'\');"';
        data_html+=' value="'+data.company_name+'" placeholder="Нажмите чтобы выбрать" autocomplete="off" onkeyup="select_dealer(\''+znakchar+'\');">';
      }
      else {
        data_html+=' onclick="this.value=\'\'; $(\'#company_id_'+znakchar+'\').val(\'0\'); select_client(\''+znakchar+'\');"';
        data_html+=' value="'+data.company_name+'" placeholder="Нажмите чтобы выбрать" autocomplete="off" onkeyup="select_client(\''+znakchar+'\');">';
      }
      data_html+='<div id="dealer_list_new_'+znakchar+'"></div><div id="dealer_dogovor_list_new_'+znakchar+'"></div>\
      </div>\
      <div class="col-sm-1">\
      договор:\
      </div>\
      <div class="col-sm-3">\
        <input type="hidden" name="company_dogovor_id" id="company_dogovor_id_'+znakchar+'" value="'+data.document.dogovor_id+'">\
        <input type="text" class="form-control input-mini" name="company_dogovor_name" id="company_dogovor_name_'+znakchar+'" onclick="this.value=\'\'; select_dealer_dogovor(\''+znakchar+'\');" value="'+data.document.dogovor_id+'" onkeyup="select_dealer_dogovor(\''+znakchar+'\');" placeholder="Нажмите чтобы выбрать" autocomplete="off">\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4">\
      <label for="document_date" class="col-sm-4 col-form-label text-nowrap">Оплатить до:</label>\
      </div>\
      <div class="col-sm-8">\
        <input type="date" class="form-control input-mini" id="pay_date" placeholder="" name="pay_date" value="'+data.document.pay_date+'">\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4">\
      <label for="type_id" class="col-form-label text-nowrap col-sm-4">Тип документа</label>\
      </div>\
      <div class="col-sm-8">\
    <input type="hidden" id="type_id_'+znakchar+'" name="type_id" value="'+data.document.type_id+'">\
        <input type="text" class="form-control input-mini" id="type_id_name_'+znakchar+'" name="type_id_name" value="'+data.type_id_name+'" readonly onclick="select_doc_type(\'some_form\',\''+znak+'\');" placeholder="Нажмите чтобы выбрать">\
        <div id="doc_types_list_new_'+znakchar+'"></div>\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4">\
      <label for="doc_number" class="col-form-label text-nowrap col-sm-4">№ Накладной или УПД</label>\
      </div>\
      <div class="col-sm-8">\
        <input type="text" class="form-control search_str input-mini" id="doc_number" placeholder="" name="number" value="';
      if(data.document.number!=""){
        data_html+=data.document.number;
      }
      else {
        if(znakchar=="minus") data_html+="0000-"+data.document.id.padStart(6,"0");
        else data_html+=data.document.number;
      }

      data_html+='"><label style="position: absolute; top: 0.4em; right: 1.2em;" for="doc_number" id="doc_number_label" onclick="clear_search_order_text(\'doc_number\');"></label>\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4">\
      <label for="document_date" class="col-sm-5 col-form-label text-nowrap">Дата документа</label>\
      </div>\
      <div class="col-sm-8">\
        <input type="date" class="form-control input-mini" id="document_date" placeholder="" name="document_date" value="'+data.document.document_date+'">\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4">\
      <label for="chf_number" class="col-sm-5 col-form-label text-nowrap">№ Счет-фактуры</label>\
      </div>\
      <div class="col-sm-8">\
        <input type="text" class="form-control search_str input-mini" id="chf_number" placeholder="" name="chf_number" value="';
        if(data.document.chf_number!=""){
          data_html+=data.document.chf_number;
        }
        else {
          if(znakchar=="minus") data_html+="0000-"+data.document.id.padStart(6,"0");
          else data_html+=data.document.chf_number
        }
      data_html+='"><label style="position: absolute; top: 0.4em; right: 1.2em;" for="chf_number" id="chf_number_label" onclick="clear_search_order_text(\'chf_number\');"></label>\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4">\
      <label for="chf_date" class="col-sm-5 col-form-label text-nowrap">Дата документа</label>\
      </div>\
      <div class="col-sm-8">\
        <input type="date" class="form-control input-mini" id="chf_date" placeholder="" name="chf_date" value="';
      if(data.document.chf_date!="0000-00-00" && data.document.chf_date!="") data_html+=data.document.chf_date;
      //else data_html+=data.document.document_date;
      data_html+='">';
      data_html+='</div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4">\
      <label for="comment" class="col-sm-5 col-form-label text-nowrap">Комментарий к документу</label>\
      </div>\
      <div class="col-sm-8">\
        <input type="text" onclick="this.select();" class="form-control search_str input-mini" id="comment" placeholder="" name="comment" value="'+data.document.comment+'"><label style="position: absolute; top: 0.4em; right: 1.2em;" for="comment" id="comment_label" onclick="clear_search_order_text(\'comment\');"></label>\
      </div>\
      </div>\
      <div class="form-group row">\
      <div class="col-sm-4">\
      <label for="obrabotan" class="col-sm-5 col-form-label text-nowrap">Обработан</label>\
      </div>\
      <div class="col-sm-2">\
        <input type="checkbox" class="" id="obrabotan" placeholder="" name="obrabotan"';
      if(data.document.obrabotan==1) data_html+=' checked="checked"';
      data_html+='>\
      </div>\
      <div class="col-sm-4" style="border-left:1px solid lightgrey">\
      <label for="upd_vydan" class="col-sm-5 col-form-label text-nowrap"> УПД/Сч.Факт. выдана клиенту</label>\
      </div>\
      <div class="col-sm-2">\
        <input type="checkbox" class="" id="upd_vydan" placeholder="" name="upd_vydan"';
      if(data.document.upd_vydan==1) data_html+=' checked="checked"';
      data_html+='>\
      </div>\
      </div>';
      if(parseInt(data.document.type_id)==7){
        data_html+='<div class="form-group row">\
        <div class="col-sm-4">';
        data_html+='<label for="return_confirmed" class="col-sm-5 col-form-label text-nowrap">Возврат принят поставщиком</label>\
        </div>\
        <div class="col-sm-8">';
        if(data.document.return_confirmed==1) 
          data_html+='<div class="form-group row">\
          <div class="col-sm-1">\
          <img src="/images/ok.svg" style="width:16px;">\
          </div>\
          <div class="col-sm-4">\
          <input type="date" id="return_confirm_date" name="return_confirm_date" class="form-control" value="'+data.document.return_confirm_date+'" readonly>\
          </div>\
          </div>\
          <div id="return_confirm_date_request"></div>';
          //<img src="/images/ok.svg" style="width:16px;"> '+data.document.return_confirm_date;
        else 
          data_html+='<div class="form-group row">\
          <div class="col-sm-1">\
          <input type="checkbox" class="" id="return_confirmed" placeholder="" name="return_confirmed" onclick="set_return_confirm_date(\''+znakchar+'\')">\
          </div>\
          <div class="col-sm-4">\
          <input type="date" id="return_confirm_date" name="return_confirm_date" class="form-control" style="display:none;">\
          </div>\
          </div>\
          <div id="return_confirm_date_request"></div>';
      data_html+='\
        </div>\
        </div>';
      }  
      data_html+='<div id="document_details">';
      data_html+='</div></form>\
      <div id="details_'+data.document.id+'"></div>\
      <div id="return_document_detail" style="position: relative; left: 450px; top:-200px"></div>\
      ';
      return data_html;
}

function set_return_confirm_date(znakchar){
  if($("#new_document_form_"+znakchar+" input#return_confirmed").prop("checked")){
    var table='<input type="date" id="return_confirm_date_request_input"  value="'+(new Date().toISOString().substring(0, 10))+'">\
    <button class="btn btn-primary btn-sm" onclick="save_return_confirm_date(\''+znakchar+'\')" type="button">Сохранить</button>';
    create_window("return_confirm_date_request_div","Укажите дату","return_confirm_date_request",table);
  }
  else {
    $("#return_confirm_date_request").html('');
    $("#return_confirm_date").css('display',"none");
  }
}

function save_return_confirm_date(znakchar){
  $("#return_confirm_date").val($("#return_confirm_date_request_input").val());
  $("#return_confirm_date").css('display',"block");
  $("#return_confirm_date_request").html('');
}

function edit_document(document_form,znak){
  switch(znak){ 
    case "+": var znakchar="plus"; break;
    case "rtd": var znakchar="rtd"; break;
    case "-": var znakchar="minus"; break;
    case "rfc": var znakchar="rfc"; break;
  }
  api_query("/api/index.php",document_form,"get_document").done(function(data){
    if(data.status=="ok"){
      var data_html='';
      data_html+=print_edit_document_form(data,znak);
      create_window_centered_blue("add_new_document"+znakchar,"Изменение документа","new_document"+znakchar,data_html);
      document_data=data;
      
      get_document_data('document_form_'+data.document.id,znak);
    }
    });
}

function add_new_document(znak){
  switch(znak){ 
    case "+": var znakchar="plus"; break;
    case "rtd": var znakchar="rtd"; break;
    case "-": var znakchar="minus"; break;
    case "rfc": var znakchar="rfc"; break;
  }
    var data_html='<form id="new_document_form_'+znakchar+'">\
    <div class="form-group row">\
    <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Тип документа</label>\
    <div class="col-sm-7">\
      <input type="hidden" name="is_new" value="1">\
	    <input type="hidden" id="type_id_'+znakchar+'" name="type_id" value="1">\
      <input type="text" class="form-control" id="type_id_name_'+znakchar+'" name="type_id_name" value="Поступление товара (Товарная накладная)" readonly onclick="select_doc_type(\'some_form\',\''+znak+'\');" placeholder="Нажмите чтобы выбрать">\
      <div id="doc_types_list_new_'+znakchar+'"></div>\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Выберите склад</label>\
    <div class="col-sm-7">\
     <input type="hidden" name="sklad_id" id="sklad_id_'+znakchar+'" value="">\
     <input type="text" class="form-control " name="sklad_name" id="sklad_name_'+znakchar+'" value="" onclick="select_sklad(\''+znakchar+'\');" readonly placeholder="Нажмите чтобы выбрать">\
    <div id="sklad_list_new_'+znakchar+'"></div>\
    </div>\
    </div>\
    <div class="form-group row" id="document_client_deliverer_row">';
    if(znak=="+") {
      data_html+='<label for="company_name" class="col-sm-5 col-form-label text-nowrap">Выберите поставщика</label>\
      <div class="col-sm-7">\
        <input type="hidden" name="company_id" id="company_id_'+znakchar+'" value="">\
        <input type="text" class="form-control" name="company_name" id="company_name_'+znakchar+'" onclick="this.value=\'\'; select_dealer(\''+znakchar+'\');" value="" onkeyup="select_dealer(\''+znakchar+'\');" placeholder="Нажмите чтобы выбрать" autocomplete="off">\
        <div id="dealer_list_new_'+znakchar+'"></div>\
      </div>';
    }
    else {
      data_html+='<label for="company_name" class="col-sm-5 col-form-label text-nowrap">Выберите покупателя</label>\
      <div class="col-sm-7">\
        <input type="hidden" name="company_id" id="company_id_'+znakchar+'" value="">\
        <input type="text" class="form-control" name="company_name" id="company_name_'+znakchar+'" onclick="select_client(\''+znakchar+'\');" value="" readonly placeholder="Нажмите чтобы выбрать">\
        <div id="dealer_list_new_'+znakchar+'"></div>\
      </div>';
    }
    data_html+='</div><div class="form-group row" id="document_client_deliverer_dogovors_row">';
    if(znak=="+") {
      data_html+='<div class="col-sm-5"><label for="company_dogovor" class="col-form-label text-nowrap">Выберите договор</label></div>\
      <div class="col-sm-7">\
        <input type="hidden" name="company_dogovor_id" id="company_dogovor_id_'+znakchar+'" value="">\
        <input type="text" class="form-control" name="company_dogovor_name" id="company_dogovor_name_'+znakchar+'" onclick="this.value=\'\'; select_dealer_dogovor(\''+znakchar+'\');" value="" onkeyup="select_dealer_dogovor(\''+znakchar+'\');" placeholder="Нажмите чтобы выбрать" autocomplete="off">\
        <div id="dealer_dogovor_list_new_'+znakchar+'"></div>\
      </div>';
    }
    var now = new Date();
    var month = (now.getMonth() + 1);               
    var day = now.getDate();
    if (month < 10) 
        month = "0" + month;
    if (day < 10) 
        day = "0" + day;
    var today = now.getFullYear() + '-' + month + '-' + day;
    data_html+='</div>\
    <!--div class="form-group row">\
    <label for="sklad_address" class="col-sm-5 col-form-label text-nowrap">№ документа</label>\
    <div class="col-sm-9">\
      <input type="text" class="form-control search_str" id="doc_number" placeholder="" name="number" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="doc_number" id="doc_number_label" onclick="clear_search_order_text(\'doc_number\');"></label>\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="document_date" class="col-sm-5 col-form-label text-nowrap">Дата документа</label>\
    <div class="col-sm-9">\
      <input type="date" class="form-control" id="document_date" placeholder="" name="document_date" value="'+today+'">\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="sklad_descr" class="col-sm-5 col-form-label text-nowrap">Комментарий к документу</label>\
    <div class="col-sm-9">\
      <input type="text" class="form-control search_str" id="comment" placeholder="" name="comment" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="comment" id="comment_label" onclick="clear_search_order_text(\'comment\');"></label>\
    </div>\
    </div--><div id="document_details">';

    data_html+='</div></form>\
    <button class="btn btn-primary" onclick="save_document(\''+znak+'\');">Сохранить</button>\
    <button class="btn btn-secondary pull-right" onclick="$(\'#new_document'+znakchar+'\').html(\'\');">Закрыть</button>\
    ';


    create_window_centered_blue("add_new_document"+znakchar,"Добавление нового документа","new_document"+znakchar,data_html);
    
    var selectedSkladValue = $("#my_sklad").val();

    var foundSklad = sklads.find(function(sklad) {
      return sklad.id === selectedSkladValue;
    });

    if (foundSklad) {
      $('#sklad_id_' + znakchar).val(foundSklad.id);
      $('#sklad_name_' + znakchar).val(foundSklad.name);
    }
}

function change_new_detail_brand(brand_id,detail_id,brand_name,detail_name,article,price=0,sale_price=0,ean13='',my_code='',detail_group_id=0,detail_group_name=''){
    if(typeof(article)!="undefined" && article.length>2) $("#add_new_document_detail_form input[name=article]").val(article);
    $("#add_new_document_detail_form input[name=brand_id]").val(brand_id);
    $("#add_new_document_detail_form input[name=detail_id]").val(detail_id);
    $("#add_new_document_detail_form input[name=brand]").val(brand_name);
    $("#add_new_document_detail_form input[name=name]").val(detail_name);
    $("#add_new_document_detail_form input[name=document_detail_group_name]").val(detail_group_name);
    $("#add_new_document_detail_form input[name=document_detail_group_id]").val(detail_group_id);
    $("#add_new_document_detail_form input[name=price]").val(price);
    $("#add_new_document_detail_form input[name=price_w_nds]").val(price);
    $("#add_new_document_detail_form input[name=ean13]").val(ean13);
    $("#add_new_document_detail_form input[name=my_code]").val(my_code);
    $("#add_new_document_detail_form input[name=price_without_nds]").val((parseFloat(price)/(1+parseInt($("#add_new_document_detail_form input[name=tax]").val())/100)).toFixed(2));
    $("#add_new_document_detail_form input[name=sale_price]").val(sale_price);
    $("#brand_helper").html('');
    $("#save_new_detail").removeAttr('disabled');
}

function change_new_detail_brand_from_sklad(brand_id,detail_id,brand_name,detail_name,article,price,tax,time,detail_group_id=0,detail_group_name=''){
    if(typeof(article)!="undefined" && article.length>2) $("#add_new_document_detail_form input[name=article]").val(article);
    $("#add_new_document_detail_form input[name=brand_id]").val(brand_id);
    $("#add_new_document_detail_form input[name=detail_id]").val(detail_id);
    $("#add_new_document_detail_form input[name=brand]").val(brand_name);
    $("#add_new_document_detail_form input[name=name]").val(detail_name);
    $("#add_new_document_detail_form input[name=price]").val(price);
    $("#add_new_document_detail_form input[name=tax]").val(tax);
    $("#add_new_document_detail_form input[name=time]").val(time);
    $("#add_new_document_detail_form input[name=document_detail_group_name]").val(detail_group_name);
    $("#add_new_document_detail_form input[name=document_detail_group_id]").val(detail_group_id);
    $("#brand_helper").html('');
    $("#save_new_detail").removeAttr('disabled');
}

function get_brands(){
      //$("#save_new_detail").attr('disabled',"disabled");
      var send=new Array();
      var price=0;
      var sale_price=0;
      var ean13='';
      var my_code='';
      var name='';
      send['article']=$('#add_new_document_detail_form input[name=article]').val().replace(" ","");
      send['brand']=$('#add_new_document_detail_form input[name=brand]').val().replace(" ","");
      $('#add_new_document_detail_form input[name=article]').val(send['article']);
      api_query_array("/api/index.php",send,"get_brands_online").then(function(data){
    	if (data!=null){
    	    var datalen=data.brands.length;
          if(datalen==1){
            if(send['brand']=="" || send['brand'].toUpperCase()==data.brands[0].brand.toUpperCase()){
              if(typeof(data.sklad_detail_names[data.brands[0].detail_id])!="undefined"){
                price=data.sklad_detail_names[data.brands[0].detail_id].price;
                sale_price=data.sklad_detail_names[data.brands[0].detail_id].detail_markup_price;
                ean13=data.sklad_detail_names[data.brands[0].detail_id].ean13;
                my_code=data.sklad_detail_names[data.brands[0].detail_id].my_code;
                name=data.sklad_detail_names[data.brands[0].detail_id].name;
                detail_group_name=data.sklad_detail_names[data.brands[0].detail_id].detail_group_name;
                detail_group_id=data.sklad_detail_names[data.brands[0].detail_id].detail_group_id;
              }
              else {
                price=0;
                sale_price=0;
                ean13='';
                my_code='';
                name=data.brands[0].name;
                detail_group_name="";
                detail_group_id=0;
              }
              change_new_detail_brand(data.brands[0].brand_id,data.brands[0].detail_id,data.brands[0].brand,name,send['article'],price,sale_price,ean13,my_code,detail_group_id,detail_group_name);
            }
            else {
              $('#add_new_document_detail_form input[name=detail_id]').val('');
              $('#add_new_document_detail_form input[name=brand_id]').val('');
            }
          }
          else {
      	    var table="<table class='table table-hover'><tr><th>Артикул</th><th>Брэнд</th><th>Наименование</th></tr>";
      	    for (var i=0; i<datalen; i++){
              if(data.brands[i].brand===null) data.brands[i].brand="";
              if(data.brands[i].name===null) data.brands[i].name="";
              if(typeof(data.sklad_detail_names[data.brands[i].detail_id])!="undefined"){
                price=data.sklad_detail_names[data.brands[i].detail_id].price;
                sale_price=data.sklad_detail_names[data.brands[i].detail_id].detail_markup_price;
                ean13=data.sklad_detail_names[data.brands[i].detail_id].ean13;
                my_code=data.sklad_detail_names[data.brands[i].detail_id].my_code;
                name=data.sklad_detail_names[data.brands[i].detail_id].name;
                detail_group_name=data.sklad_detail_names[data.brands[i].detail_id].detail_group_name;
                detail_group_id=data.sklad_detail_names[data.brands[i].detail_id].detail_group_id;
              }
              else {
                price=0;
                sale_price=0;
                ean13='';
                my_code='';
                name=data.brands[i].name;
                detail_group_id=0;
                detail_group_name="";
              }
      		    table+="<tr style='cursor:pointer' onclick='change_new_detail_brand("+data.brands[i].brand_id+","+data.brands[i].detail_id+",\""+data.brands[i].brand.replace(/'/g,'').replace(/"/g,'')+"\",\""+name.replace(/'/g,'').replace(/"/g,'')+"\",\""+send['article']+"\",\""+price+"\",\""+sale_price+"\",\""+ean13+"\",\""+my_code+"\","+detail_group_id+",\""+detail_group_name+"\");'>\
              <td>"+data.brands[i].article+"</td><td>"+data.brands[i].brand.replace(/'/g,'').replace(/"/g,'')+"</td><td>"+name.replace(/'/g,'').replace(/"/g,'')+"</td></tr>";
      	    }
      	    table+="</table>";
      	    if(datalen>1)
              create_window('brand_helper_div',"Выберите брэнд детали","brand_helper",table);
            else {
              $('#add_new_document_detail_form input[name=detail_id]').val('');
              $('#add_new_document_detail_form input[name=brand_id]').val('');
              $("#save_new_detail").removeAttr('disabled');
            }
          } 

    	}
    	else {
    	    var table="деталь не найдена в базе данных";
          $("#save_new_detail").removeAttr('disabled');
    	}
    });
}

function get_detail_from_sklad(){
      $("#save_new_detail").attr('disabled',"disabled");
      var send=new Array();
      send['search']=$('#add_new_document_detail_form input[name=article]').val();
      send['sklad_id']=$('#add_new_document_detail_form input[name=sklad_id]').val();
      api_query_array("/api/index.php",send,"get_sklad_details").then(function(data){
    	if (data!=null){
    	    var datalen=data.sklad_details.length;
          if(datalen==1){
            change_new_detail_brand_from_sklad(data.sklad_details[0].brand_id,data.sklad_details[0].detail_id,data.sklad_details[0].brand,data.sklad_details[0].name,data.sklad_details[0].article,data.sklad_details[0].price,data.sklad_details[0].tax,data.sklad_details[0].time);
          }
          else {
      	    var table="<table class='table table-hover'><tr><th>Артикул</th><th>Брэнд</th><th>Наименование</th></tr>";
      	    for (var i=0; i<datalen; i++){
      		      table+="<tr style='cursor:pointer' ";
                table+="onclick='change_new_detail_brand_from_sklad("+data.sklad_details[i].brand_id+","+data.sklad_details[i].detail_id+",\""+data.sklad_details[i].brand+"\",\""+data.sklad_details[i].name+"\",\""+data.sklad_details[0].article+"\","+data.sklad_details[0].price+","+data.sklad_details[0].tax+","+data.sklad_details[0].time+","+data.sklad_details[i].detail_group_id+",\""+data.sklad_details[i].detail_group_name+"\");'>";
                table+="<td>"+data.sklad_details[i].article+"</td><td>"+data.sklad_details[i].brand+"</td><td>"+data.sklad_details[i].name+"</td></tr>";
      	    }
      	    table+="</table>";
      	    if(datalen>1)
              create_window('brand_helper_div',"Выберите брэнд детали","brand_helper",table);
            else
              $("#save_new_detail").removeAttr('disabled');
          }

    	}
    	else {
    	    bootbox.alert("деталь не найдена на складе");
          $("#save_new_detail").removeAttr('disabled');
    	}
    });
}

function save_new_document_detail_to_base(document_id){
  //if($("[id^=edit_document_detail]").html()=="" || $("[id^=add_new_document_detail]").html()=="")
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
  var page=$('#document_form_'+document_id+' input[name=page]').val();
  var search=$('#document_form_'+document_id+' input[name=search]').val();
  if (parseInt(page)==0) page=1;
  if (typeof(page)=="undefined") search='';
    api_query("/api/index.php","add_new_document_detail_form","save_document_detail").then(function(data){
      if(data.status=="ok"){
        var znak=$("#document_form_"+document_id+" [name=znak]").val();
        get_documents(znak).then(function(){
          get_document_data("document_form_"+document_id,znak,page,search);
          $.unblockUI();
        }); 
      }
    });
}

function recalculate_document_sale_price(){
  var price=parseFloat($("#add_new_document_detail_form input[name=price]").val());
  var markup=parseFloat($("#add_new_document_detail_form input[name=markup]").val());
  var $pwm=(price+(price/100)*markup).toFixed(2);
  $("#add_new_document_detail_form input[name=sale_price]").val($pwm);
}

function edit_document_detail(detail_form,znak){
  api_query("/api/index.php",detail_form,"get_document_detail").done(function(data1){
    var data=data1.document_details[0];
    if(typeof(data1.diff_markup)!="undefined")
      document_data.diff_markup=data1.diff_markup;
    if(typeof(data1.sklad_markup)!="undefined")
      document_data.sklad_markup=parseFloat(data1.sklad_markup);
    //$('#add_new_detail_div').toggle();
    //$('#add_new_detail_header').html("Добавление детали:");
    var new_detail_content="<div style='width:750px;'><div id='new_my_cross_doc' style='position:relative; top: 560px; left: 100px;'></div><form id='add_new_document_detail_form'><table class='table' style='min-width:350px;'>";
    new_detail_content+="<tr><td>артикул: </td>";
    new_detail_content+="<td><input type='text' class='form-control search_str input-sm' id='article' name='article' value='"+data.article+"' onchange='get_brands();'><input type='hidden' name='subaction' value='edit'>";
    new_detail_content+="<input type='hidden' name='detail_id' value='"+data.detail_id+"'>\
    <input type='hidden' name='sklad_id' value='"+data.sklad_id+"'>\
    <input type='hidden' name='document_id' value='"+data.document_id+"'>\
    <input type='hidden' name='id' value='"+data.id+"'>\
    </td></tr>";
    new_detail_content+="<tr><td>бренд: </td><td><input type='text' class='form-control search_str input-sm' id='brand' name='brand' value='"+data.brand+"'><input type='hidden' name='brand_id' value='"+data.brand_id+"'>";
    new_detail_content+="<div id='brand_helper'>";
    new_detail_content+="</div></td></tr>";
    new_detail_content+="<tr><td>наименование: </td><td><input type='text' class='form-control search_str input-sm' id='name' name='name' value='"+data.name.replace("'","&apos;").replace('"',"&quot;").replace(">","&gt;").replace("<","&lt;")+"'></td></tr>";
    new_detail_content+="<tr><td>Изменить наименование на складе: </td><td><input type='checkbox' id='document_detail_change_sklad_name' name='change_sklad_name' checked></td></tr>";
    new_detail_content+="<tr><td>цена без НДС: </td><td><input type='text' class='form-control search_str input-sm' name='price_without_nds' id='price_without_nds' value='"+((znak=="rtd"?data.dealer_price:data.price)/(1+data.tax/100)).toFixed(2)+"' onchange='change_price();'></td></tr>";
    new_detail_content+="<tr><td>цена с НДС: </td><td><input type='text' class='form-control search_str input-sm' name='price' id='price' value='"+(znak=="rtd"?data.dealer_price:data.price)+"' onchange='change_price_without_nds();'></td></tr>";
    new_detail_content+="<tr><td>НДС в %: </td><td><input type='text' class='form-control search_str input-sm' name='tax' id='tax' value='"+data.tax+"' onchange='recalc_price_w_nds_and_sum(\"add_new_document_detail_form\")'></td></tr>";
    new_detail_content+="<tr><td>кол-во: </td><td><input type='text' class='form-control search_str input-sm' id='count' name='count' value='"+data.count+"'></td></tr>";
    new_detail_content+="<tr><td>проданное кол-во: </td><td><input type='text' class='form-control search_str input-sm' id='sell_count' name='sell_count' value='"+data.sell_count+"'></td></tr>";
    new_detail_content+="<tr><td>возвращено поставщику: </td><td><input type='text' class='form-control search_str input-sm' id='returned_to_dealer_count' name='returned_to_dealer_count' value='"+data.returned_to_dealer_count+"'></td></tr>";
    new_detail_content+="<tr><td>наценка в %: </td><td><input type='text' class='form-control search_str input-sm' id='sell_markup' name='markup' value='"+data.markup+"' onchange='recalculate_document_sale_price();'></td></tr>";
    new_detail_content+="<tr><td>расчетная цена продажи: </td>";
    let detail_markup=0;
        if(typeof(document_data.diff_markup)!="undefined"){
          
          for (let i=0; i<document_data.diff_markup.length; i++){
            if(data.price>parseFloat(document_data.diff_markup[i].min_sum) && data.price<=parseFloat(document_data.diff_markup[i].max_sum)){
              detail_markup=parseFloat(document_data.diff_markup[i].value);
            }
          }
          if(detail_markup==0 && typeof(document_data.diff_markup[document_data.diff_markup.length-1])!="undefined" && document_data.diff_markup[document_data.diff_markup.length-1].max_sum==0 && data.price>parseFloat(document_data.diff_markup[document_data.diff_markup.length-1].min_sum)){
            detail_markup=parseFloat(document_data.diff_markup[document_data.diff_markup.length-1].value);
          }
          new_detail_content+='<td nowrap>'+(Math.ceil(parseFloat(data.price)+(parseFloat(data.price)/100)*detail_markup)).toFixed(2)+'</td>';
        }
        else {
          if(typeof(document_data.sklad_markup)!="undefined"){
            new_detail_content+='<td nowrap>'+(Math.ceil(parseFloat(data.price)+(parseFloat(data.price)/100)*parseFloat(document_data.sklad_markup))).toFixed(2)+'</td>';
          }
        }
    new_detail_content+="</tr>";
    new_detail_content+="<tr><td>моя цена продажи: </td>\
    <td>\
    <div class='input-group input-group-sm col-sm-12'>\
    <span>\
      <input type='text' onclick='this.select();' class='form-control search_str input-sm' name='sale_price' id='sale_price' value='"+(znak=="rtd"?data.price:data.sale_price)+"'>\
    </span>\
    <span class='input-group-btn'>\
    <button class='btn btn-default btn-sm' type='button' onclick='recalc_price_w_nds_and_sum(\"add_new_document_detail_form\");' title='Расчитать цену'>...</button>\
    </span></div>";
    new_detail_content+="<tr><td>срок доставки: </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' id='time_1' name='time' value='"+data.time+"'></td></tr>";
    new_detail_content+="<tr><td>акцизный товар: </td><td><input type='checkbox' class='' id='is_excise' name='is_excise'";
    if(data.is_excise=="1") new_detail_content+=" checked='checked'";
    new_detail_content+="></td></tr>";
    new_detail_content+="<tr><td>размеры детали: </td><td><input type='text' class='form-control input-sm' id='detail_size' name='detail_size' value='"+data.detail_size+"'></td></tr>";
    
    new_detail_content+="<tr><td>маркированный товар: </td><td><input type='checkbox' class='' id='is_marking' name='is_marking'";
    if(data.is_marking=="1") new_detail_content+=" checked='checked'";
    new_detail_content+="></td></tr>";
    new_detail_content+="<tr><td>Марки: </td><td>";
    new_detail_content+="<button type='button' class='btn btn-xs btn-primary pull-right' onclick='set_detail_mark(\""+detail_form+"\","+data.id+",\""+znak+"\");'> + </button>";
    if(typeof(data1.detail_marks)!="undefined" && data1.detail_marks.length>0){
        new_detail_content+="<table class='table'><thead><tr><th>Марка</th></tr></thead><tbody>";
        for(var i=0; i<data1.detail_marks.length; i++){
            new_detail_content+='<tr><td>'+data1.detail_marks[i].mark;
            new_detail_content+='</td>';
            new_detail_content+='<td nowrap><div  class="pull-right">';
            new_detail_content+='<a onclick="edit_detail_mark(\''+detail_form+'\','+data1.detail_marks[i].id+','+data.id+',\''+znak+'\');"><img src="/new_images/edit.svg" width="15px;"></a>';
            new_detail_content+='<a onclick="bootbox.confirm(\'Вы точно хотите удалить запись?\',function(result){ if(result) delete_detail_mark('+data1.detail_marks[i].id+','+data.id+',\''+detail_form+'\',\''+znak+'\'); });"><img src="/new_images/garbage.svg" width="15px;"></a>';
            new_detail_content+='</div></td></tr>';
        }
        new_detail_content+="</tbody></table>";
    }
    new_detail_content+="</td></tr>";
    
    new_detail_content+="<tr><td>ГТД: </td><td>";
    new_detail_content+="<button type='button' class='btn btn-xs btn-primary pull-right' onclick='set_detail_gtd(\""+detail_form+"\","+data.id+",\""+znak+"\");'> + </button>";
    if(data1.detail_gtds.length>0){
        new_detail_content+="<table class='table'><thead><tr><th>№</th><th>страна</th><th></th></tr></thead><tbody>";
        for(var i=0; i<data1.detail_gtds.length; i++){
            new_detail_content+='<tr><td>'+data1.detail_gtds[i].custom_num+'/'+data1.detail_gtds[i].doc_date+'/'+data1.detail_gtds[i].num;
            if(data1.detail_gtds[i].pos_num.length>0) new_detail_content+='/'+data1.detail_gtds[i].pos_num;
            new_detail_content+=' </td>';
            new_detail_content+='<td> '+data1.detail_gtds[i].country_name+'</td>';
            new_detail_content+='<td nowrap><div  class="pull-right">';
            new_detail_content+='<a onclick="edit_detail_gtd(\''+detail_form+'\','+data1.detail_gtds[i].id+','+data.id+',\''+znak+'\');"><img src="/new_images/edit.svg" width="15px;"></a>';
            new_detail_content+='<a onclick="bootbox.confirm(\'Вы точно хотите удалить запись?\',function(result){ if(result) delete_detail_gtd('+data1.detail_gtds[i].id+','+data.id+',\''+detail_form+'\'); });"><img src="/new_images/garbage.svg" width="15px;"></a>';
            new_detail_content+='</div></td></tr>';
        }
        new_detail_content+="</tbody></table>";
    }
    new_detail_content+="</td></tr>";

    new_detail_content+="<tr><td>Местоположение: </td><td>";
    new_detail_content+="<button type='button' class='btn btn-xs btn-primary pull-right' onclick='set_sklad_detail_location("+data.sklad_id+","+data.detail_id+","+data1.sklad_topology+",\""+detail_form+"\","+data.id+")'> + </button>";
    if(data1.detail_locations.length>0){
        new_detail_content+="<table class='table'><thead><tr><th>Место</th><th>кол-во</th><th></th></tr></thead><tbody>";
        for(var i=0; i<data1.detail_locations.length; i++){
            new_detail_content+='<tr><td>'+data1.detail_locations[i].location+' </td>';
            new_detail_content+='<td> '+data1.detail_locations[i].count+'</td>';
            new_detail_content+='<td><div  class="pull-right">';
            new_detail_content+='<a onclick="edit_detail_location('+data1.detail_locations[i].sklad_id+','+data.detail_id+','+data1.sklad_topology+',\''+data1.detail_locations[i].location+'\',\''+detail_form+'\','+data1.detail_locations[i].id+','+data.id+',\''+znak+'\');"><img src="/new_images/edit.svg" width="15px;"></a>';
            new_detail_content+='<a onclick="bootbox.confirm(\'Вы точно хотите удалить запись?\',function(result){ if(result) delete_detail_location('+data1.detail_locations[i].sklad_id+','+data.detail_id+',\''+data1.detail_locations[i].location+'\',\''+detail_form+'\',\''+znak+'\'); });"><img src="/new_images/garbage.svg" width="15px;"></a>';
            new_detail_content+='</div></td></tr>';
        }
        new_detail_content+="</tbody></table>";
    }
    new_detail_content+="</td></tr>";

    new_detail_content+="<tr><td>Кроссы: </td><td>";
    new_detail_content+="<button type='button' class='btn btn-xs btn-primary pull-right' onclick='edit_cross(0,\"doc\","+data.detail_id+",\""+data.article+"\",\""+data.brand+"\",\""+data.name.replace("'","&apos;").replace('"',"&quot;").replace(">","&gt;").replace("<","&lt;")+"\")'> + </button></td></tr>";
    if(typeof(data1.detail_crosses)!="undefined" && data1.detail_crosses.length>0){
        new_detail_content+="<tr><td></td><td><div style='overflow: auto; max-height: 160px; max-width:610px;'><table class='table'><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th></th></tr></thead><tbody>";
        for(var i=0; i<data1.detail_crosses.length; i++){
            new_detail_content+='<tr><td>'+data1.detail_crosses[i].article+' </td>';
            new_detail_content+='<td> '+data1.detail_crosses[i].brand+'</td>';
            new_detail_content+='<td> '+data1.detail_crosses[i].name+'</td>';
            new_detail_content+='<td>';
            if(typeof(data1.detail_crosses[i].id)!="undefined"){
              new_detail_content+='<a onclick="edit_cross('+data1.detail_crosses[i].id+',\'doc\');"><img src="/new_images/edit.svg" width="15px;"></a>';
              new_detail_content+='<a onclick="bootbox.confirm(\'Вы точно хотите удалить запись?\',function(result){ if(result) delete_cross('+data1.detail_crosses[i].id+','+data.detail_id+'); });"><img src="/new_images/garbage.svg" width="15px;"></a></td>';
            }
            new_detail_content+='</tr>';
        }
        new_detail_content+="</tbody></table></div>";
    }
    new_detail_content+="</td></tr>";

    new_detail_content+="<tr><td>\
    штрих-код(ean13): </td><td>\
    <div class='input-group input-group-sm col-sm-12'>\
    <span>\
      <input type='text' onclick='this.select();' class='form-control search_str input-sm' name='ean13' id='ean13' value='"+data.ean13+"' onchange1='fill_new_document_detail_by_ean13();'>\
      <label style='position: absolute; top: 36.3em; right: 1.3em;' for='ean13' id='ean13_label' onclick='clear_search_order_text(\"ean13\");'></label>\
    </span>\
    <span class='input-group-btn'>\
    <button class='btn btn-default btn-sm' type='button' onclick='get_ean13_of_detail("+data.detail_id+",\"document\",\"ean13\");' title='Получить код'>...</button>\
    </span></div></td></tr>";
    new_detail_content+="<tr><td>Мой код: </td><td>\
    <div class='input-group input-group-sm col-sm-12'>\
    <span>\
      <input type='text' onclick='this.select();' class='form-control search_str input-sm' name='my_code' id='my_code' value='"+data.my_code+"'>\
      <label style='position: absolute; top: 36.3em; right: 1.3em;' for='my_code' id='my_code_label' onclick='clear_search_order_text(\"my_code\");'></label>\
    </span>\
    <span class='input-group-btn'>\
    <button class='btn btn-default btn-sm' type='button' onclick='get_ean13_of_detail("+data.detail_id+",\"document\",\"my_code\");' title='Получить код'>...</button>\
    </span></div>\
    </td></tr>";
    new_detail_content+="<tr><td>Товарная группа: </td><td><div id='sel_document_groups_list_root_"+data.id+"'></div>\
    <input type='text' id='document_detail_group_name_"+data.id+"' class='form-control search_str' onclick='select_document_detail_groups(0,0,0,"+data.id+");' name='document_detail_group_name' value='"+data.detail_group_name+"'>\
    <input type='hidden' name='document_detail_group_id' id='document_detail_group_id_"+data.id+"' value='"+data.detail_group_id+"'></td></tr>";
    new_detail_content+="<tr><td colspan='2'>\
    <button type='button' class='btn btn-primary btn-xs pull-left' id='save_new_detail' onclick='save_new_document_detail_to_base("+data.document_id+");'>Сохранить</button>\
    <button type='button' class='btn btn-secondary btn-xs pull-right' id='close_new_detail' onclick='close_window(\"edit_document_detail_"+data.id+"\");'>Закрыть</button>\
    </td></tr>";
    new_detail_content+="</table></form>";
    new_detail_content+="<div id='document_detail_topology_select' style='position: absolute; top:550px; left:100px;'>\
    </div><div id='document_detail_gtd_select' style='position: absolute; top:520px; left:100px; width:550px;'></div>\
    <div id='document_detail_mark_select' style='position: absolute; top:490px; left:100px; width:550px;'></div></td></tr></div>";
    $("[id^=edit_document_detail]").html('');
    $("[id^=add_new_document_detail]").html('');
    create_window("edit_document_detail_"+data.id+"_div","Изменение данных детали: ","edit_document_detail_"+data.id,new_detail_content);
    $("#edit_document_detail_"+data.id+"_div").css('top',"120px");
//    $('#new_detail_header_content').html("Изменение данных детали: ");
//    $('#add_new_detail_content').html(new_detail_content);
//    $('#add_new_detail_div').draggable();
 });
}

function select_document_detail_groups(group_id=0,glubina=0,force=0,in_div=0){
  if($("#sel_det_document_group_znak_"+group_id).html()=="-" && !force){
    $("#sel_document_groups_list_"+group_id).html('');
    $("#sel_det_document_group_znak_"+group_id).html('+');
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
       <td><a onclick="select_document_detail_groups('+data.detail_groups[i].id+','+(glubina)+',0,'+in_div+');" id="sel_det_document_group_znak_'+data.detail_groups[i].id+'">+</a>\
        <a onclick="set_document_detail_group('+data.detail_groups[i].id+',\''+data.detail_groups[i].group_name+'\','+in_div+');">'+data.detail_groups[i].group_name+'</a> </td>\
       <td> \
       </td></tr>';
       table+='<tr><td id="sel_document_groups_list_'+data.detail_groups[i].id+'" style="padding-left:'+((glubina+1)*10)+'px;"></td><td> </td></tr>';
     }
     table+='</tbody></table>';
     if (glubina==1)
      create_window("sel_document_groups_list_"+group_id+"_div","выберите группу","sel_document_groups_list_root_"+in_div,table);
     else 
      document.getElementById("sel_document_groups_list_"+group_id).innerHTML=table;
      $("#sel_det_document_group_znak_"+group_id).html('-');
   }
   else {
     document.getElementById("sel_document_groups_list_root_"+in_div).innerHTML='Группы еще не заведены';
   }
  });
}

function set_document_detail_group(group_id,group_name,in_div=0){
  $("#document_detail_group_name_"+in_div).val(group_name);
  $("#document_detail_group_id_"+in_div).val(group_id);
  close_window("sel_document_groups_list_root_"+in_div);
}

function get_ean13_of_detail(detail_id,type,id){
  var send=[];
  send['detail_id']=detail_id;
  api_query_array("/api/index.php",send,"get_ean13_of_detail").then(function(data){
    if(data.status=="ok"){
      switch(type){
        case "document": $("#add_new_document_detail_form #"+id).val(data.details[0].ean13); break;
        case "sklad": $("#add_new_detail_form #detail_"+id).val(data.details[0].ean13); break;
      }
    }
  })
}

function change_price(){
  var pwonds=parseFloat($("#price_without_nds").val().replace(",","."));
  $("#price_without_nds").val(pwonds);
  var tax=parseFloat($("#tax").val().replace(",","."));
  $("#tax").val(tax);
  $("#price").val((pwonds+(pwonds/100)*tax).toFixed(2));
}

function change_price_without_nds(){
  var pwnds=parseFloat($("#price").val().replace(",","."));
  $("#price").val(pwnds);
  var tax=parseFloat($("#tax").val().replace(",","."));
  $("#tax").val(tax);
  $("#price_without_nds").val((pwnds/(1+tax/100)).toFixed(2));
}

function add_new_document_detail(document_id,sklad_id,znak){
    //$('#add_new_detail_div').toggle();
    //$('#add_new_detail_header').html("Добавление детали:");
  var send=new Array();
  send['document_id']=document_id;
  api_query_array("/api/index.php",send,"get_document").then(function(data){
    var new_detail_content="<div style='width:650px;'><form id='add_new_document_detail_form'><table class='table' style='min-width:300px;'>";
    new_detail_content+="<tr><td style='vertical-align: middle;'>артикул: </td>";
    if(znak=="+"){
      new_detail_content+="<td>\
      <div class='input-group input-group-sm col-sm-12'>\
      <span><input type='text' class='form-control search_str input-sm' id='article' name='article' value='' onchange='get_brands();'>\
      <label style='position: absolute; top: 0.6em;' for='article' id='article_label' onclick='clear_search_order_text(\"article\");'></label></span>\
      <span class='input-group-btn'><button class='btn btn-default btn-sm' type='button' onclick='get_zakaz_details_by_document_id(\""+document_id+"\");' title='Выбрать из списка заказов'>...</button></span>\
      <input type='hidden' name='subaction' value='add'></div><div id='zakaz_details_by_document_id_list' style='z-index: 11;'></div>";
    }
    else {
      new_detail_content+="<td><input type='text' class='form-control search_str input-sm' id='article' name='article' value='' onchange='get_detail_from_sklad();'><label style='position: absolute; top: 4em; right: 1.3em;' for='article' id='article_label' onclick='clear_search_order_text(\"article\");'></label><input type='hidden' name='subaction' value='add'>";
    }
    new_detail_content+="<input type='hidden' name='document_id' value='"+document_id+"'><input type='hidden' name='detail_id' value=''><input type='hidden' name='sklad_id' value='"+sklad_id+"'></td></tr>";
    new_detail_content+="<tr><td style=\"vertical-align: middle;\">брэнд: </td><td><input type='text' class='form-control search_str input-sm' id='brand' name='brand' value='' onchange='get_brands();'><label style='position: absolute; top: 7.4em; right: 1.3em;' for='brand' id='brand_label' onclick='clear_search_order_text(\"brand\");'></label><input type='hidden' name='brand_id' value=''>";
    new_detail_content+="<div id='brand_helper'>";
    new_detail_content+="</div></td></tr>";
    new_detail_content+="<tr><td style=\"vertical-align: middle;\">наименование: </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' id='name' name='name' value=''><label style='position: absolute; top: 10.8em; right: 1.3em;' for='name' id='name_label' onclick='clear_search_order_text(\"name\");'></label></td></tr>";
    new_detail_content+="<tr><td style=\"vertical-align: middle;\">цена без НДС: </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' id='price_without_nds' name='price_without_nds' value='0' onchange='recalc_price_w_nds_and_sum(\"add_new_document_detail_form\")'><label style='position: absolute; top: 14.1em; right: 1.3em;' for='price_without_nds' id='price_without_nds_label' onclick='clear_search_order_text(\"price_without_nds\");'></label></td></tr>";
    new_detail_content+="<tr><td style=\"vertical-align: middle;\">НДС в %: </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' name='tax' id='tax' value='";
    if(data.company_nds>0) {
      new_detail_content+=data.company_nds;
    }
    else new_detail_content+="0";
    if(typeof(data.diff_markup)!="undefined")
      document_data.diff_markup=data.diff_markup;
    if(typeof(data.sklad_markup)!="undefined")
      document_data.sklad_markup=parseFloat(data.sklad_markup);
    new_detail_content+="' onchange='recalc_price_w_nds_and_sum(\"add_new_document_detail_form\")'><label style='position: absolute; top: 17.6em; right: 1.3em;' for='tax' id='tax_label' onclick='clear_search_order_text(\"tax\");'></label></td></tr>";
    new_detail_content+="<tr><td style=\"vertical-align: middle;\">кол-во: </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' id='count' name='count' value='1' onchange='recalc_price_w_nds_and_sum(\"add_new_document_detail_form\")'><label style='position: absolute; top: 21.1em; right: 1.3em;' for='count' id='count_label' onclick='clear_search_order_text(\"count\");'></label><input type='hidden' name='time' value='0'></td></tr>";
    new_detail_content+="<tr><td style=\"vertical-align: middle;\">цена с НДС: </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' name='price' id='price' value='0' onchange='recalc_price_and_sum_from_nds(\"add_new_document_detail_form\");'><label style='position: absolute; top: 24.9em; right: 1.3em;' for='price' id='price_label' onclick='clear_search_order_text(\"price_w_nds\");'></label></td></tr>";
    new_detail_content+="<tr><td style=\"vertical-align: middle;\" nowrap>сумма с НДС: </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' name='sum_w_nds' id='sum_w_nds' value='0' onchange='recalc_price_from_sum(\"add_new_document_detail_form\");'><label style='position: absolute; top: 28.2em; right: 1.3em;' for='sum_w_nds' id='sum_w_nds_label' onclick='clear_search_order_text(\"sum_w_nds\");'></label></td></tr>";
    new_detail_content+="<tr><td style=\"vertical-align: middle;\" nowrap>штрих-код(ean13): </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' name='ean13' id='ean13' value='' onchange='fill_new_document_detail_by_ean13();'><label style='position: absolute; top: 31.7em; right: 1.3em;' for='ean13' id='ean13_label' onclick='clear_search_order_text(\"ean13\");'></label></td></tr>";
    new_detail_content+="<tr><td style=\"vertical-align: middle;\">Мой код: </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' name='my_code' id='my_code' value='' onchange='fill_new_document_detail_by_my_code();'><label style='position: absolute; top: 35.0em; right: 1.3em;' for='my_code' id='my_code_label' onclick='clear_search_order_text(\"my_code\");'></label></td></tr>";
    new_detail_content+="<tr><td>цена продажи: </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' name='sale_price' id='sale_price' value='0'><label style='position: absolute; top: 38.4em; right: 1.3em;' for='sale_price' id='sale_price_label' onclick='clear_search_order_text(\"sale_price\");'></label></td></tr>";
    new_detail_content+="<tr><td>Товарная группа: </td><td><div id='sel_document_groups_list_root_0'></div><input type='text' id='document_detail_group_name_0' class='form-control search_str' onclick='select_document_detail_groups();' name='document_detail_group_name' value=''>\
    <input type='hidden' name='document_detail_group_id' id='document_detail_group_id_0' value=''></td></tr>";
    new_detail_content+="<tr><td>акцизный товар: </td><td><input type='checkbox' class='' id='is_excise' name='is_excise'";
    if(data.is_excise=="1") new_detail_content+=" checked='checked'";
    new_detail_content+="></td></tr>";
    new_detail_content+="<tr><td style=\"vertical-align: middle;\">ГТД: </td><td><input type='text' class='form-control search_str input-sm' onclick='this.select();' name='gtd' id='gtd' value='' onchange='' placeholder='________/______/_______/_____'></td></tr>";
    api_query("/api/index.php","some_form","get_countrys").then(function(data2){
      var option='<option value="0"> --- </option>';
      data2.countrys.forEach(function(item){
        option+='<option value="'+item.code+'">'+item.name+'</option>';
      });
      new_detail_content+="<tr><td style=\"vertical-align: middle;\">Страна произв.: </td><td><select name='country_code' class='form-control'>"+option+"</select></td></tr>";
      new_detail_content+="<tr><td></td><td><button type='button' class='btn btn-secondary' id='save_new_detail' onclick='save_new_document_detail_to_base("+document_id+");'>Сохранить</button></td></tr>";
      new_detail_content+="</table></form></div>";
      $("[id^=edit_document_detail]").html('');
      create_window("add_new_document_detail_div","Добавление детали: ","add_new_document_detail",new_detail_content);
      $("#gtd").mask("88888888/888888/8888888/88888",{autoclear: false});
  //    $('#new_detail_header_content').html("Добавление детали: ");
  //    $('#add_new_detail_content').html(new_detail_content);
  //    $('#add_new_detail_div').draggable();
    });
  });
}

function recalc_price_and_sum_from_nds(form_id){
  var price_nds=parseFloat($("#"+form_id+" input[name=price]").val().replace(",",".").replace(" ",""));
  var count=parseInt($("#"+form_id+" input[name=count]").val().replace(",","."));
  var tax=parseInt($("#"+form_id+" input[name=tax]").val().replace(",","."));
  $("#"+form_id+" input[name=price_without_nds]").val(parseFloat(price_nds/(1+(tax/100))).toFixed(2));
  $("#"+form_id+" input[name=price]").val(parseFloat(price_nds).toFixed(2));
  $("#"+form_id+" input[name=sum_w_nds]").val(parseFloat(price_nds*count).toFixed(2));
  let detail_price=parseFloat($("#"+form_id+" input[name=price]").val());
  let detail_markup=0;
  if(typeof(document_data.diff_markup)!="undefined"){
    for (let i=0; i<document_data.diff_markup.length; i++){
      if(detail_price>parseFloat(document_data.diff_markup[i].min_sum) && detail_price<=parseFloat(document_data.diff_markup[i].max_sum)){
        detail_markup=parseFloat(document_data.diff_markup[i].value);
      }
    }
    if(detail_markup==0 && document_data.diff_markup[document_data.diff_markup.length-1].max_sum==0 && detail_price>parseFloat(document_data.diff_markup[document_data.diff_markup.length-1].min_sum)){
      detail_markup=parseFloat(document_data.diff_markup[document_data.diff_markup.length-1].value);
    }
    $("#"+form_id+" input[name=sale_price]").val((Math.ceil((detail_price+(detail_price/100)*detail_markup)/document_details_round)*document_details_round).toFixed(2));
  }
  else {
    if(typeof(document_data.sklad_markup)!="undefined"){
      $("#"+form_id+" input[name=sale_price]").val((Math.ceil((detail_price+(detail_price/100)*document_data.sklad_markup)/document_details_round)*document_details_round).toFixed(2));
    }
  }
}

function recalc_price_w_nds_and_sum(form_id){
  var new_price=parseFloat($("#"+form_id+" input[name=price_without_nds]").val().replace(",",".").replace(" ",""));
  var count=parseInt($("#"+form_id+" input[name=count]").val().replace(",","."));
  var tax=parseInt($("#"+form_id+" input[name=tax]").val().replace(",","."));
  $("#"+form_id+" input[name=price_without_nds]").val(parseFloat(new_price).toFixed(2));
  $("#"+form_id+" input[name=price]").val(parseFloat(new_price*(1+(tax/100))).toFixed(2));
  $("#"+form_id+" input[name=sum_w_nds]").val(($("#"+form_id+" input[name=price]").val()*count).toFixed(2));
  let detail_price=parseFloat($("#"+form_id+" input[name=price]").val());
  let detail_markup=0;
  if(typeof(document_data.diff_markup)!="undefined"){
    
    for (let i=0; i<document_data.diff_markup.length; i++){
      if(detail_price>parseFloat(document_data.diff_markup[i].min_sum) && detail_price<=parseFloat(document_data.diff_markup[i].max_sum)){
        detail_markup=parseFloat(document_data.diff_markup[i].value);
      }
    }
    if(detail_markup==0 && document_data.diff_markup[document_data.diff_markup.length-1].max_sum==0 && detail_price>parseFloat(document_data.diff_markup[document_data.diff_markup.length-1].min_sum)){
      detail_markup=parseFloat(document_data.diff_markup[document_data.diff_markup.length-1].value);
    }
    $("#"+form_id+" input[name=sale_price]").val((Math.ceil((detail_price+(detail_price/100)*detail_markup)/document_details_round)*document_details_round).toFixed(2));
  }
  else {
    if(typeof(document_data.sklad_markup)!="undefined"){
      $("#"+form_id+" input[name=sale_price]").val((Math.ceil((detail_price+(detail_price/100)*document_data.sklad_markup)/document_details_round)*document_details_round).toFixed(2));
    }
  }
}

function recalc_price_from_sum(form_id){
  var sum_w_nds=parseFloat($("#"+form_id+" input[name=sum_w_nds]").val().replace(",",".").replace(" ",""));
  var count=parseInt($("#"+form_id+" input[name=count]").val().replace(",","."));
  var tax=parseInt($("#"+form_id+" input[name=tax]").val().replace(",","."));
  $("#"+form_id+" input[name=price_without_nds]").val(parseFloat(sum_w_nds/count/(1+(tax/100))).toFixed(2));
  $("#"+form_id+" input[name=price]").val(parseFloat(sum_w_nds/count).toFixed(2));
  $("#"+form_id+" input[name=sum_w_nds]").val(parseFloat(sum_w_nds).toFixed(2));
  let detail_price=parseFloat($("#"+form_id+" input[name=price]").val());
  let detail_markup=0;
  if(typeof(document_data.diff_markup)!="undefined"){
    
    for (let i=0; i<document_data.diff_markup.length; i++){
      if(detail_price>parseFloat(document_data.diff_markup[i].min_sum) && detail_price<=parseFloat(document_data.diff_markup[i].max_sum)){
        detail_markup=parseFloat(document_data.diff_markup[i].value);
      }
    }
    if(detail_markup==0 && document_data.diff_markup[document_data.diff_markup.length-1].max_sum==0 && detail_price>parseFloat(document_data.diff_markup[document_data.diff_markup.length-1].min_sum)){
      detail_markup=parseFloat(document_data.diff_markup[document_data.diff_markup.length-1].value);
    }
    $("#"+form_id+" input[name=sale_price]").val((Math.ceil((detail_price+(detail_price/100)*detail_markup)/document_details_round)*document_details_round).toFixed(2));
  }
  else {
    if(typeof(document_data.sklad_markup)!="undefined"){
      $("#"+form_id+" input[name=sale_price]").val((Math.ceil((detail_price+(detail_price/100)*document_data.sklad_markup)/document_details_round)*document_details_round).toFixed(2));
    }
  }
}

function fill_new_document_detail_by_ean13(){
  var send=new Array();
  if($("form#add_new_document_detail_form input[name=article]").val()!='') return;
  send['ean13']=$("form#add_new_document_detail_form input[name=ean13]").val();
  if(send['ean13']!="")
  api_query_array("/api/index.php",send,"get_detail_by_ean13").then(function(data){
    if(typeof(data.details)!="undefined"){
      if(data.details.length==1){
        $("form#add_new_document_detail_form input[name=article]").val(data.details[0].article);
        $("form#add_new_document_detail_form input[name=brand]").val(data.details[0].brand);
        $("form#add_new_document_detail_form input[name=name]").val(data.details[0].name);
        $("form#add_new_document_detail_form input[name=detail_id]").val(data.details[0].detail_id);
        //$("form#add_new_document_detail_form input[name=ean13]").val(data.details[0].ean13);
        $("form#add_new_document_detail_form input[name=price]").val(data.details[0].price);
        if(typeof(data.details[0].last_document_price)!="undefined"){
          $("form#add_new_document_detail_form input[name=price]").val(data.details[0].last_document_price);
          $("form#add_new_document_detail_form input[name=sum_w_nds]").val(data.details[0].last_document_price);
          $("#add_new_document_detail_form input[name=price_without_nds]").val((parseFloat(data.details[0].last_document_price)/(1+parseInt($("#add_new_document_detail_form input[name=tax]").val())/100)).toFixed(2));
        }
      }
      else {
        //show variants of details
        var datalen=data.details.length;
        var table="<table class='table table-hover'><tr><th>detail_id</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th></tr>";
        for (var i=0; i<datalen; i++){
            table+="<tr style='cursor:pointer' onclick='change_new_detail_brand("+data.details[i].brand_id+","+data.details[i].detail_id+",\""+data.details[i].brand+"\",\""+data.details[i].name+"\",\""+data.details[i].article+"\",\""+(typeof(data.details[0].last_document_price)!="undefined"?data.details[0].last_document_price:0)+"\",0,\""+data.details[i].ean13+"\");'><td>\
            "+data.details[i].detail_id+"</td><td>"+data.details[i].article+"</td><td>"+data.details[i].brand+"</td><td>"+data.details[i].name+"</td></tr>";
        }
        table+="</table>";
        create_window('brand_helper_div',"Выберите брэнд детали","brand_helper",table);
      }
    }
  });
}

function fill_new_document_detail_by_my_code(){
  var send=new Array();
  if($("form#add_new_document_detail_form input[name=article]").val()!='') return;
  send['my_code']=$("form#add_new_document_detail_form input[name=my_code]").val();
  if(send['my_code']!="")
  api_query_array("/api/index.php",send,"get_detail_by_my_code").then(function(data){
    if(typeof(data.details)!="undefined"){
      if(data.details.length==1){
        $("form#add_new_document_detail_form input[name=article]").val(data.details[0].article);
        $("form#add_new_document_detail_form input[name=brand]").val(data.details[0].brand);
        $("form#add_new_document_detail_form input[name=name]").val(data.details[0].name);
        $("form#add_new_document_detail_form input[name=detail_id]").val(data.details[0].detail_id);
        $("form#add_new_document_detail_form input[name=ean13]").val(data.details[0].ean13);
        $("form#add_new_document_detail_form input[name=price]").val(data.details[0].price);
        if(typeof(data.details[0].last_document_price)!="undefined"){
          $("form#add_new_document_detail_form input[name=price]").val(data.details[0].last_document_price);
          $("form#add_new_document_detail_form input[name=sum_w_nds]").val(data.details[0].last_document_price);
          $("#add_new_document_detail_form input[name=price_without_nds]").val((parseFloat(data.details[0].last_document_price)/(1+parseInt($("#add_new_document_detail_form input[name=tax]").val())/100)).toFixed(2));
        }
      }
      else {
        //show variants of details
        var datalen=data.details.length;
        var table="<table class='table table-hover'><tr><th>detail_id</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th></tr>";
        for (var i=0; i<datalen; i++){
            table+="<tr style='cursor:pointer' onclick='change_new_detail_brand("+data.details[i].brand_id+","+data.details[i].detail_id+",\""+data.details[i].brand+"\",\""+data.details[i].name+"\",\""+data.details[i].article+"\",\""+(typeof(data.details[0].last_document_price)!="undefined"?data.details[0].last_document_price:0)+"\",0,\""+data.details[i].ean13+"\");'><td>\
            "+data.details[i].detail_id+"</td><td>"+data.details[i].article+"</td><td>"+data.details[i].brand+"</td><td>"+data.details[i].name+"</td></tr>";
        }
        table+="</table>";
        create_window('brand_helper_div',"Выберите брэнд детали","brand_helper",table);
      }
    }
  });
}

function get_document_detail_sklad_location(document_id,document_detail_id){
  var send=new Array();
  send['document_detail_id']=document_detail_id;
  api_query_array("/api/inde.php",send,"get_document_sklad_topology").then(function(data){

  });
}
//$( function() {
//    $( ".draggable" ).draggable();
//  } );

function get_invents(){
  var znak_ch="inv";
  var send=new Array();
  send['search_invent_date_from']=$("#search_invent_date_from").val();
  send['search_invent_date_to']=$("#search_invent_date_to").val();
  send['search_invent_sklad_name']=$("#search_invent_sklad_name").val();
  api_query_array("/api/index.php",send,"get_invents").then(function(data){
    if(typeof(data.search_invent_date_from)!="undefined") 
      $("#search_invent_date_from").val(data.search_invent_date_from);
    if(typeof(data.search_invent_date_to)!="undefined") 
      $("#search_invent_date_to").val(data.search_invent_date_to);
    if(typeof(data.search_invent_sklad_name)!="undefined") 
      $("#search_invent_sklad_name").val(data.search_invent_sklad_name);
    var datalen=data.invents.length;
    var table=""
    
    table+="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Склад</th><th>Описание</th><th>Тип</th><th>Дата создания</th>";
    table += "<th>Позиций</th><th>Кол-во</th><th>Сумма</th><th>статус</th><th></th></tr></thead></tbody>";
    var invents_sum=0,invents_sum_count=0,invents_sum_pos=0;
    for (var i=0; i<datalen; i++){
      if(data.invents[i].invent_pos_sum!==null) invents_sum+=parseFloat(data.invents[i].invent_pos_sum);
      if(data.invents[i].invent_pos_count!==null) invents_sum_pos+=parseFloat(data.invents[i].invent_pos_count);
      if(data.invents[i].invent_positions!==null) invents_sum_count+=parseFloat(data.invents[i].invent_positions);
    	table += "<tr><td><div id='invent_details_"+data.invents[i].id+"'></div>"+data.invents[i].id+"</td>";
      table += "<td>"+data.invents[i].sklad_name+"</td><td>"+data.invents[i].descr+"</td>";
      table += '<td>'+(data.invents[i].type=="1" ? "Частичная" : "Полная")+'</td>';
    	table += "<td>"+convertTZ(data.invents[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td><td>";
      if(parseFloat(data.invents[i].invent_positions)>0)
        table += data.invents[i].invent_positions;
      else
        table+="0";
      table +="</td><td>";
      if(parseFloat(data.invents[i].invent_pos_count)>0)
        table += data.invents[i].invent_pos_count;
      else
        table += "0";
      table += '</td><td nowrap align="left">';
      if(parseFloat(data.invents[i].invent_pos_sum).toFixed(2)>0)
        table += formatNumber(parseFloat(data.invents[i].invent_pos_sum).toFixed(2));
      else table += "0";
      table += "</td>";
      switch(parseInt(data.invents[i].status)){
        case 1: table+='<td>Создан</td>'; break;
        case 20: table+='<td>Идет инвентаризация</td>'; break;
        case 30: table+='<td>Завершена</td>'; break;
      }
    	table += "<td><form id='delete_invent_"+data.invents[i].id+"'><input type=\"hidden\" name=\"invent_id\" value=\""+data.invents[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
      //if(znak=="-") table += '<a onclick="show_document_print_menu('+data.documents[i].id+');" title="Печать"><img src="/new_images/printer.svg" class="menuimg"></a>';
      table += '<div id="invent_print_menu_'+data.invents[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div>';
      table += "&nbsp;<a onclick=\"edit_invent("+data.invents[i].id+");\" title='Редактировать документ'><img src='/new_images/edit.svg' class='menuimg'></a>";
    	table += "<a onclick=\"get_invent_details("+data.invents[i].id+","+data.invents[i].status+")\" title='Просмотреть список'><img src='/new_images/file.svg' class='menuimg'></a>";
    	table += '<form id="invent_form_'+data.invents[i].id+'" style="display:none"><input type="hidden" name="action" value="get_invent_details">';
    	table += "<input type='hidden' name='invent_id' value='"+data.invents[i].id+"'><input type='hidden' name='sklad_id' value='"+data.invents[i].sklad_id+"'></form>";
    	table += "<a title='Удалить document' ";
    	table += "onclick=\"delete_invent("+data.invents[i].id+")\"><img src='/new_images/garbage.svg' class='menuimg'></a>"
    	table += "</div></td>";
    	table += "</tr>";
    }
    table+='<tr style="font-weight:bold"><td colspan="5">Итого</td><td>'+invents_sum_count+'</td><td>'+invents_sum_pos+'</td><td>'+invents_sum.toFixed(2)+'</td><td colspan="2"></td></tr>';
    table+="</tbody></table>";
    $("#invent_list").html(table);
  });
}

function delete_invent(id){
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
        api_query('/api/index.php','invent_form_'+id,'delete_invent').then(function(data){
          $.unblockUI();
          if(data.status=='ok') 
            get_invents();
        });
      }
    }
  );
}

function add_new_invent(){
  var znakchar="inv";
  var table='\
  <form id="new_invent_form">\
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
        <label for="invent_type" class="col-sm-5 col-form-label text-nowrap">Тип инвентаризации <sup title="Тип инвентаризации: (полная или частичная). При выборе полной инвентаризации, в случае начала данной инвентаризации, выбранный склад полностью блокируется на выдачу и прием товаров до окнчания инвентаризации. При частичной инвентаризации блокируются только детали по которым идет процесс инвентаризации.">⍰</sup></label>\
        <div class="col-sm-7">\
          <select name="invent_type" id="invent_type_'+znakchar+'" class="form-control">\
          <option value="1"';
          //if(parseInt(data.invent.type)==1) table+=' selected="selected"';
          table+='>Частичная</option>\
          <option value="2"';
          //if(parseInt(data.invent.type)==2) table+=' selected="selected"';
          table+='>Полная</option>\
          </select>\
          <div id="sklad_list_new_'+znakchar+'"></div>\
        </div>\
      </div>\
  </form>\
  <div class="row">\
  <div class="col-sm-6"><button class="btn btn-primary" onclick="save_invent();">Сохранить</button></div>\
  <div class="col-sm-6"><button class="btn btn-default pull-right" onclick="$(\'#new_invent\').html(\'\');">Отменить</button></div>\
  </div>';
  create_window_centered_blue("new_invent_div","Добавление нового документа инвентаризации","new_invent",table);
}

function edit_invent(invent_id){
  var znakchar="inv";
  var send=new Array();
  send['invent_id']=invent_id;
  api_query_array("/api/index.php",send,"get_invent").then(function(data){
    var table='\
    <form id="new_invent_form">\
      <div class="form-group row">\
        <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Описание</label>\
        <div class="col-sm-7">\
          <input type="hidden" name="invent_id" value="'+invent_id+'">\
          <input type="hidden" name="is_new" value="0">\
          <input type="text" class="form-control" id="descr_'+znakchar+'" name="descr" value="'+data.invent.descr+'">\
        </div>\
      </div>\
      <div class="form-group row">\
        <label for="sklad_name" class="col-sm-5 col-form-label text-nowrap">Выберите склад</label>\
        <div class="col-sm-7">\
          <input type="hidden" name="sklad_id" id="sklad_id_'+znakchar+'" value="'+data.invent.sklad_id+'">\
          <input type="text" class="form-control" name="sklad_name" id="sklad_name_'+znakchar+'" onclick="select_sklad(\''+znakchar+'\');" readonly value="'+data.invent.sklad_name+'">\
          <div id="sklad_list_new_'+znakchar+'"></div>\
        </div>\
      </div>\
      <div class="form-group row">\
        <label for="invent_type" class="col-sm-5 col-form-label text-nowrap">Тип инвентаризации <sup title="Тип инвентаризации: (полная или частичная). При выборе полной инвентаризации, в случае начала данной инвентаризации, выбранный склад полностью блокируется на выдачу и прием товаров до окнчания инвентаризации. При частичной инвентаризации блокируются только детали по которым идет процесс инвентаризации.">⍰</sup></label>\
        <div class="col-sm-7">\
          <select name="invent_type" id="invent_type_'+znakchar+'" class="form-control">\
          <option value="1"';
          if(parseInt(data.invent.type)==1) table+=' selected="selected"';
          table+='>Частичная</option>\
          <option value="2"';
          if(parseInt(data.invent.type)==2) table+=' selected="selected"';
          table+='>Полная</option>\
          </select>\
          <div id="sklad_list_new_'+znakchar+'"></div>\
        </div>\
      </div>\
    \
    <b>Инвентаризационная комиссия:</b> <button type="button" class="pull-right btn btn-xs btn-primary" onclick="select_new_invent_user('+invent_id+');" title="добавить члена комиссии"> + </button><div id="select_invent_user"></div>';
    var invent_users_len=data.invent_users.length;
    if(invent_users_len>0) table+='<table class="table"><thead><th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Председатель</th></thead><tbody>';
    for(var i=0; i<invent_users_len; i++){
      table+='<tr><td>'+data.invent_users[i].lastname+'</td><td>'+data.invent_users[i].name+'</td><td>'+data.invent_users[i].middlename+'</td><td>';
      if(data.invent_users[i].is_header=="1") {
        table+='<input type="radio" name="is_header" value="'+data.invent_users[i].user_id+'" checked="checked">';
      }
      else { 
        table+='<input type="radio" name="is_header" value="'+data.invent_users[i].user_id+'">';
      }
      table+='</td></tr>';
    }
    if(invent_users_len>0) table+='</tbody></table>';
    table+='</form>';
    table+='<br><div class="row">\
    <div class="col-sm-6"><button class="btn btn-primary" onclick="save_invent();">Сохранить</button></div>\
    <div class="col-sm-6"><button class="btn btn-default pull-right" onclick="$(\'#new_invent\').html(\'\');">Отменить</button></div>\
    </div>';
    create_window_centered_blue("new_invent_div","Изменение документа инвентаризации","new_invent",table);
  });
}

function select_new_invent_user(invent_id){
  api_query("/api/index.php","some_form","get_my_company_users").then(function(data){
    var userslen=data.users.length;
    var table='<table class="table table-hover"><thead><td>Фамилия</td><td>Имя</td><td>Отчество</td></thead><tbody>';
    for (var i=0; i<userslen; i++){
      table+='<tr onclick="add_invent_user('+invent_id+','+data.users[i].id+');"><td>'+data.users[i].lastname+'</td><td>'+data.users[i].name+'</td><td>'+data.users[i].middlename+'</td></tr>';
    }
    table+='</tbody></table>';
    create_window("select_invent_user_div","Добавление нового члена комиссии","select_invent_user",table);
  });
}

function add_invent_user(invent_id,user_id){
  var send=new Array();
  send['user_id']=user_id;
  send['invent_id']=invent_id;
  api_query_array("/api/index.php",send,"add_invent_user").then(function(data){
    if(data.status=="ok"){
      $("#select_invent_user").html('');
      edit_invent(invent_id);
    }
  });

}

function save_invent(){
  api_query("/api/index.php","new_invent_form","save_invent").then(function(data){
    if(data.status=="ok"){
      $("#new_invent").html("");
      get_invents();
    }
  });
}

function get_invent_details(invent_id,invent_status){
  var send=new Array();
  send['invent_id']=invent_id;
  send['search_article']=$("#invent_search_article").val();
  //send['search_ean']=$("#invent_search_ean").val();
  send['search_name']=$("#invent_search_name").val();
  send['search_brand']=$("#invent_search_brand").val();
  send['search_code']=$("#invent_search_code").val();
  send['selected_page']=$("#invent_selected_page").val();
  send['show_zero']=$("#invent_show_zero").prop("checked");
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
  api_query_array("/api/index.php",send,"get_invent_details").then(function(data){
    if(data.status=="ok"){
      var table='<div class="row">\
      <div class="col-sm-4">';
      if(invent_status==20) {
        table+='<button class="btn btn-primary" onclick="invent_submit('+invent_id+');">Закончить инвентаризацию</button>';
        table+='<a onclick="get_invent_detail_xls('+invent_id+');"><img src="/new_images/excel_32.png" style="width: 30px;"></a>';
      }
      if(invent_status==1) table+='<button class="btn btn-primary" onclick="invent_start('+invent_id+');">Начать инвентаризацию</button>';
      table+='</div>\
      <div class="col-sm-8">\
      <div class="input-group input-group-sm pull-right">\
        <span for="invent_show_zero" class="input-group-addon">\
        <input type="checkbox" name="show_zero" id="invent_show_zero" onchange="get_invent_details('+invent_id+','+invent_status+');"';
        if(data.show_zero!==null && typeof(data.show_zero)!="undefined" && data.show_zero==1) table+=" checked";
        else {
          if(typeof(send['show_zero'])=="undefined" && data.show_zero) table+=" checked";
        }
        table+='>\
        Показать 0 ост.</span>\
        <label for="invent_search_article" class="input-group-addon">Артикул:</label>\
        <input class="form-control" type="text" name="search_article" id="invent_search_article" onchange="get_invent_details('+invent_id+','+invent_status+');" value="';
        if(data.search_article!==null && typeof(data.search_article)!="undefined") table+=data.search_article;
        table+='">\
        <label for="invent_search_brand" class="input-group-addon">Бренд:</label>\
        <input class="form-control" type="text" name="search_brand" id="invent_search_brand" onchange="get_invent_details('+invent_id+','+invent_status+');" value="';
        if(data.search_brand!==null && typeof(data.search_brand)!="undefined") table+=data.search_brand;
        table+='">\
        <label for="invent_search_name" class="input-group-addon">Наименование:</label>\
        <input class="form-control" type="text" name="search_name" id="invent_search_name" onchange="get_invent_details('+invent_id+','+invent_status+');" value="';
        if(data.search_name!==null && typeof(data.search_name)!="undefined") table+=data.search_name;
        table+='">\
        <label for="invent_search_code" class="input-group-addon">штрих-код:</label>\
        <input class="form-control" type="text" name="search_code" id="invent_search_code" onchange="get_invent_details('+invent_id+','+invent_status+');" onkeyup="if(event.keyCode===13) {get_invent_details('+invent_id+','+invent_status+');}" value="';
        if(data.search_code!==null && typeof(data.search_code)!="undefined") table+=data.search_code;
        table+='">\
        <input type="hidden" name="invent_selected_page" id="invent_selected_page" ';
        if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
        else var selected_page=1;
        table+=' value="'+selected_page+'"';
        table+='>\
      </div>\
      </div>\
      </div>';

      table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
      var x=0,y=0,xx=0,yy=0;
      
      for (var i=1; i<=data.invent_pages; i++){
        if(i>(selected_page+6) && i<(data.invent_pages-1)){
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
              table += '><a href="#" onclick="$(\'#invent_selected_page\').val(\''+i+'\');';
              //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_invent_details('+invent_id+',\''+invent_status+'\')">...</a></li>';
          }
          if (x==1) xx++;
        }
        else {
            if (y==1) {
          if (yy==0){
              table += '<li';
              table += '><a href="#" onclick="$(\'#invent_selected_page\').val(\''+i+'\');';
              //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_invent_details('+invent_id+',\''+invent_status+'\')">...</a></li>';
          }
          if (y==1) yy++;
            }
            else {
          table += '<li';
          if(selected_page==i) table+= " class='active'";
          table += '><a href="#" onclick="$(\'#invent_selected_page\').val(\''+i+'\');';
          //if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
          table += 'get_invent_details('+invent_id+',\''+invent_status+'\')">'+i+'</a></li>';
            }
        }
      }
      table += '</ul></div>';

      table+='<div style="height: 73vh; overflow:auto;">\
      <table class="table table-hover">\
      <thead>\
      <tr><th><input type="checkbox" id="invent_checkbox_all" onclick="check_all_invent_details();"></th><th>№</th><th>артикул</th><th>наименование</th><th>бренд</th><th>кол-во факт</th><th>кол-во учет</th><th>отклонение</th><th>цена</th><th>цена продажи</th><th>сумма факт</th><th>сумма учет</th><th>Обработан</th></tr>\
      </thead><tbody>';
      var len=data.invent_details.length;
      for (var i=0; i<len; i++){
        table+='<tr><td>';
        switch(parseInt(data.invent_details[i].status)) {
          case 0: table+='<input type="checkbox" id="invent_checkbox_'+data.invent_details[i].id+'" onchange="add_to_invent_start('+data.invent_details[i].id+')">'; break;
          case 1: table+='<input type="checkbox" id="invent_checkbox_'+data.invent_details[i].id+'" onchange="add_to_invent_start('+data.invent_details[i].id+')" checked="checked">'; break;
          case 20: table+='<input type="checkbox" id="invent_checkbox_'+data.invent_details[i].id+'" onchange="add_to_invent_start('+data.invent_details[i].id+')" checked="checked">'; break;
          case 30: table+='<img src="/images/ok.svg" style="width:20px;">'; break;
        }
        table+='</td>';
        table+='<td>'+(i+1)+'</td><td>'+data.invent_details[i].article+'</td><td>'+data.invent_details[i].name+'</td><td>'+data.invent_details[i].brand+'</td>\
        <td>\
        <input type="text" size="2" name="count_fact_'+data.invent_details[i].id+'" id="count_fact_'+data.invent_details[i].id+'"\
         value="'+(parseInt(data.invent_details[i].otklonenie)!=0?data.invent_details[i].count_fact:data.invent_details[i].count_sklad)+'" onchange="change_invent_fact('+data.invent_details[i].id+')" onfocus="this.select()" ';
        if(parseInt(data.invent_details[i].status)==30) table+=" disabled";
        table+='>\
        </td>\
        <td id="invent_sklad_count_'+data.invent_details[i].id+'">'+data.invent_details[i].count_sklad+'</td>\
        <td id="otklonenie_'+data.invent_details[i].id+'">'+data.invent_details[i].otklonenie+'</td>\
        <td id="invent_price_'+data.invent_details[i].id+'">'+data.invent_details[i].price+'</td>\
        <td id="invent_detail_markup_price_'+data.invent_details[i].id+'">'+data.invent_details[i].detail_markup_price+'</td>\
        <td id="sum_fact_'+data.invent_details[i].id+'">'+(parseFloat(data.invent_details[i].count_fact)*parseFloat(data.invent_details[i].price)).toFixed(2)+'</td>\
        <td>'+(data.invent_details[i].count_sklad*data.invent_details[i].price).toFixed(2)+'</td>';
        table+='<td><input type="checkbox" name="invent_detail_processed" id="invent_detail_processed_'+data.invent_details[i].id+'" '+(data.invent_details[i].processed=="1"?"checked":"")+' onchange="change_invent_processed('+data.invent_details[i].id+');"></td>';
        table+='</tr>';
      }
      table+='</tbody></table><div>';
      $.unblockUI();
      create_window_centered_blue("invent_details_"+invent_id+"_div","Инвентаризационная опись","invent_details_"+invent_id,table);
      if( (data.search_name=='' || data.search_name===null) && (data.search_brand=='' || data.search_brand===null) && (data.search_article=='' || data.search_article===null) ){
        setTimeout(function(){
          document.getElementById("invent_search_code").focus();
        },10);
      }
    }
  }, function(data){
    $.unblockUI();
    if(1) { // пока выключил здесь
      setTimeout(function(){
        document.getElementById("invent_search_code").focus();
      },10);
    }
  });
}


function get_invent_detail_xls(invent_id){
  var send=new Array();
  send['invent_id']=invent_id;
  api_query_array("/api/index.php",send,"get_invent_details_xls").then(function(data){
    // if(data.status=="err"){
    //   $("#checkbox_"+invent_id).prop('checked', false);
    // }
    var blob = b64toBlob(data.file); //new Blob([data.file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "export.xlsx";
    link.click();
  });
}

function check_all_invent_details(){
  $("input[id^=invent_checkbox").each(function(index){
    if($("#invent_checkbox_all").prop("checked")){
      if(!$(this).prop("checked")) $(this).click();
    }
    else {
      if($(this).prop("checked")) $(this).click();
    }
  })
}

function add_to_invent_start(invent_detail_id){
  var send=new Array();
  send['invent_detail_id']=invent_detail_id;
  api_query_array("/api/index.php",send,"add_invent_detail_to_start").then(function(data){
    if(data.status=="err"){
      $("#checkbox_"+invent_detail_id).prop('checked', false);
    }
  });
}

function change_invent_processed(invent_detail_id){
  var send=new Array();
  send['invent_detail_id']=invent_detail_id;
  api_query_array("/api/index.php",send,"invent_detail_processed").then(function(data){
    if(data.status=="err"){
      $("#invent_detail_processed_"+invent_detail_id).prop('checked', false);
    }
  });
}

function invent_start(invent_id){
  var send=new Array();
  send['invent_id']=invent_id;
  api_query_array("/api/index.php",send,"start_invent").then(function(data){
    get_invents();
  });
}

function change_invent_fact(invent_detail_id){
  var fact_count=parseInt($("#count_fact_"+invent_detail_id).val());
  var price=parseFloat($("#invent_price_"+invent_detail_id).html());
  var sklad_count=$("#invent_sklad_count_"+invent_detail_id).html();
  var fact_sum=(parseFloat(fact_count)*parseFloat(price)).toFixed(2);
  $("#sum_fact_"+invent_detail_id).html(fact_sum);
  $("#otklonenie_"+invent_detail_id).html((fact_count-sklad_count));
  var send=new Array();
  send['fact_count']=fact_count;
  send['otklonenie']=(fact_count-sklad_count);
  send['invent_detail_id']=invent_detail_id;
  api_query_array("/api/index.php",send,"save_invent_detail").then(function(data){

  });
}

function invent_submit(invent_id){
  var send=new Array();
  send['invent_id']=invent_id;
  api_query_array("/api/index.php",send,"invent_submit").then(function(data){
    if(data.status=="err" && typeof(data.detail)!="undefined"){
      bootbox.alert("Не сходится количество товара на складе: артикул"+data.err_detail['article']+", бренд:"+data.err_detail['brand']);
    }
    get_invents();
  });
}

function edit_detail_gtd(detail_form,gtd_id,document_details_id,znak){
  var send=new Array();
  send['gtd_id']=gtd_id;
  api_query_array("/api/index.php",send,"get_document_det_gtd").then(function(data1){
    var table='<form id="document_detail_gtd">\
    <input type="hidden" name="document_details_id" id="gtd_document_details_id" value="'+document_details_id+'">\
    <input type="hidden" name="gtd_id" id="gtd_id" value="'+gtd_id+'">\
    <table class="table"><thead><tr><th nowrap>код таможни/дата/номер/номер поз.</th><th>Страна происхождения</th></tr></thead><tbody>';
    api_query("/api/index.php","some_form","get_countrys").then(function(data){
      var option="";
      data.countrys.forEach(function(item){
        if(item.code==data1.gtd.country_code)
          option+='<option value="'+item.code+'" selected="selected">'+item.name+'</option>';
        else
        option+='<option value="'+item.code+'">'+item.name+'</option>';
      });
      table+='<tr><td style="width: 22em;">';
      table+='<input type="text" name="gtd_num" class="form-control" value="'+data1.gtd.custom_num+'/'+data1.gtd.doc_date+'/'+data1.gtd.num+'/'+data1.gtd.pos_num+'" id="gtd_num" size="30" placeholder="________/______/_______/_____">';
      table+='';
      table+='</td>';
      table+='<td><select name="country_code" class="form-control">'+option+'</select></td></tr>';
      table+='<tr><td><button type="button" class="btn btn-success" onclick="save_detail_gtd(\'document_detail_gtd\',\''+detail_form+'\',\''+znak+'\');">Сохранить</button></td>';
      table+='<td><button type="button" class="btn btn-default pull-right" onclick="close_window(\'document_detail_gtd_select\');">Отмена</button></td></tr>';
      table+='<tbody></table></form>';
      create_window("document_detail_gtd_select_div","Введите ГТД","document_detail_gtd_select",table);
      $("#gtd_num").mask("88888888/888888/8888888/88888",{autoclear: false});
    });
  });

}

function set_sklad_detail_cross(oem_article,oem_brand,oem_name,oem_detail_id,detail_form,document_detail_id){

}

function delete_detail_gtd(gtd_id,document_details_id,detail_form,znak){
  var send=new Array();
  send['gtd_id']=gtd_id;
  send['document_details_id']=document_details_id;
  api_query_array("/api/index.php",send,"delete_document_det_gtd").then(function(data){
    if(data.status=="ok"){
      edit_document_detail(detail_form,znak);
    }
  });
}

function set_detail_gtd(detail_form,document_details_id,znak){
    var send=new Array();
    var table='<form id="document_detail_gtd">\
    <input type="hidden" name="document_details_id" id="gtd_document_details_id" value="'+document_details_id+'">\
    <table class="table"><thead><tr><th nowrap>код таможни/дата/номер/номер поз.</th><th>Страна происхождения</th></tr></thead><tbody>';
    api_query("/api/index.php","some_form","get_countrys").then(function(data){
      var option="";
      data.countrys.forEach(function(item){
        option+='<option value="'+item.code+'">'+item.name+'</option>';
      });
      table+='<tr><td style="width: 22em;">';
      table+='<input type="text" name="gtd_num" class="form-control" value="" id="gtd_num" size="30" placeholder="________/______/_______/_____">';
      table+='';
      table+='</td>';
      table+='<td><select name="country_code" class="form-control">'+option+'</select></td></tr>';
      table+='<tr><td><button type="button" class="btn btn-success" onclick="save_detail_gtd(\'document_detail_gtd\',\''+detail_form+'\',\''+znak+'\');">Сохранить</button></td>';
      table+='<td><button type="button" class="btn btn-default pull-right" onclick="close_window(\'document_detail_gtd_select\');">Отмена</button></td></tr>';
      table+='<tbody></table></form>';
      create_window("document_detail_gtd_select_div","Введите ГТД","document_detail_gtd_select",table);
      $("#gtd_num").mask("88888888/888888/8888888/88888",{autoclear: false});
    });
}

function save_detail_gtd(form_id,detail_form,znak){
    api_query("/api/index.php",form_id,"save_gtd").then(function(data){
      if(data.status=="ok"){
        close_window('document_detail_gtd_select');
        edit_document_detail(detail_form,znak);
      }
    });
    return false;
}

function new_export(date_from,date_to){
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
  var send=new Array();
  send['znak']="exp";
  send['date_from']=$("#search_export_date_from").val();
  send['date_to']=$("#search_export_date_to").val();
  send['type_ids']=new Array();
  if($("#search_export_return_client").prop("checked")) 
    send['type_ids'][send['type_ids'].length]=6;
  if($("#search_export_return_postav").prop("checked")) 
    send['type_ids'][send['type_ids'].length]=7;
  if($("#search_export_realizaciya").prop("checked")) 
    send['type_ids'][send['type_ids'].length]=2;
  if($("#search_export_prihod").prop("checked")) 
    send['type_ids'][send['type_ids'].length]=1;
  if($("#search_export_PKO").prop("checked")) 
    send['type_ids'][send['type_ids'].length]="PKO";
    if($("#search_export_RKO").prop("checked")) 
    send['type_ids'][send['type_ids'].length]="RKO";
  if($("#search_export_ORP").prop("checked")) 
    send['type_ids'][send['type_ids'].length]="ORP";
  send['search_document_orgtype']=$("#search_export_orgtype").val();
  send['date_type']=$("#export_search input[name=date_type]:checked").val();
  api_query_array("/api/index.php",send,"get_1c_export_file").then(function(data){
    $.unblockUI();
    //alert(data.export_file);
    var blob = new Blob([data.export_file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "export.xml";
    link.click();
  });
}

var export_documents=new Array();

function get_documents_for_export(znak){
  $("#prihod_list").html('');
  $("#rashod_list").html('');
  var send=new Array();
  var defer=$.Deferred();
    //alert(znak);
    send['znak']="exp";
    send['search_document_date_from']=$("#search_export_date_from").val();
    send['search_document_date_to']=$("#search_export_date_to").val();
    send['search_document_orgtype']=$("#search_export_orgtype").val();
    send['type_ids']=new Array();
    if($("#search_export_realizaciya").prop("checked")) send['type_ids'][send['type_ids'].length]=2;
    if($("#search_export_prihod").prop("checked")) send['type_ids'][send['type_ids'].length]=1;
    if($("#search_export_return_client").prop("checked")) send['type_ids'][send['type_ids'].length]=6;
    if($("#search_export_return_postav").prop("checked")) send['type_ids'][send['type_ids'].length]=7;
    send['date_type']=$("#export_search input[name=date_type]:checked").val();
    api_query_array("/api/index.php",send,"get_documents").then(function(data){
      export_documents=data.documents;
      if(typeof(data.search_document_date_from)!="undefined") $("#search_export_date_from").val(data.search_document_date_from);
      if(typeof(data.search_document_date_to)!="undefined") $("#search_export_date_to").val(data.search_document_date_to);
      if(typeof(data.search_document_article)!="undefined") $("#search_document_article"+znak_ch).val(data.search_document_article);
      if(typeof(data.search_document_client_name)!="undefined") $("#search_document_client_name"+znak_ch).val(data.search_document_client_name);
        var datalen=data.documents.length;
      var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Тип документа</th><th>Компания</th><th>Склад</th><th>Описание</th><th>Дата создания</th>";
      table += "<th>Позиций</th><th>Кол-во</th><th>Сумма</th><th></th></tr></thead></tbody>";
      var documents_sum=0,documents_sum_count=0,documents_sum_pos=0;
      for (var i=0; i<datalen; i++){
        if(typeof(data.document_det_pos[data.documents[i].id])!="undefined" && data.document_det_pos[data.documents[i].id].pos_sum!==null) documents_sum+=parseFloat(data.document_det_pos[data.documents[i].id].pos_sum);
        if(typeof(data.document_det_pos[data.documents[i].id])!="undefined" && data.document_det_pos[data.documents[i].id].pos_count!==null) documents_sum_pos+=parseFloat(data.document_det_pos[data.documents[i].id].pos_count);
        if(typeof(data.document_det_pos[data.documents[i].id])!="undefined" && data.document_det_pos[data.documents[i].id].positions!==null) documents_sum_count+=parseFloat(data.document_det_pos[data.documents[i].id].positions);
        table += "<tr><td><div id='details_for_export_"+data.documents[i].id+"'></div>";
        if(data.documents[i].number!=""){
          table+=data.documents[i].number;
        }
        else {
          if(znak=="-") table+="0000-"+data.documents[i].id.padStart(6,"0");
          else table+=data.documents[i].number;
        }
        table+="</td>";
        table += '<td>'+data.document_types[data.documents[i].type_id].name+'</td>';
        table += "<td>" + data.documents[i].name + "</td><td>"+data.documents[i].sklad_name+"</td><td>"+data.documents[i].comment+"</td>";
        table += "<td>"+convertTZ(data.documents[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td><td>";
        if(typeof(data.document_det_pos[data.documents[i].id])!="undefined" && parseFloat(data.document_det_pos[data.documents[i].id].positions)>0)
          table += data.document_det_pos[data.documents[i].id].positions;
        else
          table+="0";
        table +="</td><td>";
        if(typeof(data.document_det_pos[data.documents[i].id])!="undefined" && parseFloat(data.document_det_pos[data.documents[i].id].pos_count)>0)
          table += data.document_det_pos[data.documents[i].id].pos_count;
        else
          table += "0";
        table += '</td><td nowrap align="right">';
        if(typeof(data.document_det_pos[data.documents[i].id])!="undefined" && parseFloat(data.document_det_pos[data.documents[i].id].pos_sum).toFixed(2)>0)
          table += formatNumber(parseFloat(data.document_det_pos[data.documents[i].id].pos_sum).toFixed(2));
        else table += "0";
        table += "</td>";
        table += "<td>";
        table+="<form id='delete_document_"+data.documents[i].id+"'><input type=\"hidden\" name=\"document_id\" value=\""+data.documents[i].id+"\"></form>";
        table+="<div class='btn-group";
        //if(znak=="-" && (parseInt(data.documents[i].type_id)!=2 && parseInt(data.documents[i].type_id)!=6)) 
        table+=" pull-right";
        table+="' style='display: flex;'>";
        //if(znak=="-" && (parseInt(data.documents[i].type_id)==2 || parseInt(data.documents[i].type_id)==6)) {
        //  table += '<a onclick="show_document_print_menu('+data.documents[i].id+');" title="Печать"><img src="/new_images/printer.svg" class="menuimg"></a>';
        //}
        //table += '<div id="document_print_menu_'+data.documents[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div>';
        //table += "&nbsp;<a onclick=\"edit_document('delete_document_"+data.documents[i].id+"','"+znak+"');\" title='Редактировать документ'><img src='/new_images/edit.svg' class='menuimg'></a>";
        if(parseInt(data.documents[i].type_id)==2 || parseInt(data.documents[i].type_id)==6)
          table += "<a onclick=\"get_document_data_for_export('document_form_"+data.documents[i].id+"','+')\" title='Просмотреть детали'><img src='/new_images/file.svg' class='menuimg'></a>";
        else
          table += "<a onclick=\"get_document_data_for_export('document_form_"+data.documents[i].id+"','-')\" title='Просмотреть детали'><img src='/new_images/file.svg' class='menuimg'></a>";
        table += '<form id="document_form_'+data.documents[i].id+'" style="display:none"><input type="hidden" name="action" value="get_document_details"><input type="hidden" name="znak" value="'+znak+'">';
        table += "<input type='hidden' name='document_id' value='"+data.documents[i].id+"'><input type='hidden' name='sklad_id' value='"+data.documents[i].sklad_id+"'><input type='hidden' name='page' value='1'><input type='hidden' name='search' value=''></form>";
        table += "<a title='Удалить document' ";
        table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить этот документ?\',function(result){ if(result) api_query('/api/index.php','delete_document_"+data.documents[i].id+"','delete_document').then(function(data){if(data.status=='ok') get_documents('"+znak+"')});});\"><img src='/new_images/garbage.svg' class='menuimg'></a>"
        table += "</div>"; 
        table+="</td>";
        table += "</tr>";
      }
      table+='<tr style="font-weight:bold"><td colspan="6">Итого</td><td>'+documents_sum_count+'</td><td>'+documents_sum_pos+'</td><td nowrap align="right">'+formatNumber(documents_sum.toFixed(2))+'</td><td></td></tr>';
      table+="</tbody></table>";
      if(znak=="+") $("#export_prihod_list").html(table);
      else
        if(znak=="-") $("#export_real_list").html(table);
      return defer.resolve();
    });
  return defer.promise();
 }

 function get_zakaz_details_by_document_id(document_id){
   var send=[];
   send['document_id']=document_id;
   api_query_array("/api/index.php",send,"get_zakaz_details_by_document_id").then(function(data){
    var detlen=data.zakaz_details.length;
    var table='<div style="max-height:450px; max-width:670px; overflow:auto;"><table class="table table-hover">';
    table+='<thead><tr><th>артикул</th><th>бренд</th><th>наименование</th><th>цена</th><th>кол.</th><th>№заказа</th></tr></thead><tbody>';
    for(var i=0;i<detlen; i++){
      table+='<tr onclick="set_detail_to_document_detail(\''+data.zakaz_details[i].article+'\',\''+data.zakaz_details[i].brand+'\',\''+data.zakaz_details[i].name.replace(/\"/g,"").replace(/\'/g,"")+'\','+data.zakaz_details[i].detail_id+','+data.zakaz_details[i].brand_id+',\''+data.zakaz_details[i].dealer_price+'\',\''+data.zakaz_details[i].count+'\')"><td>';
      table+=data.zakaz_details[i].article+'</td><td>'+data.zakaz_details[i].brand+'</td><td>'+data.zakaz_details[i].name+'</td><td>'+data.zakaz_details[i].dealer_price+'</td><td>'+data.zakaz_details[i].count+'</td><td>'+data.zakaz_details[i].zakaz_id+'</td></tr>';
    }
    if(i==0) table+="<tr><td colspan='5'>Нет активных, заказнных позиций по данному поставщику. Возможно у Вас нет привязки поставщика в настройках профиля. Выберите в настройках онлайн поиска поставщика.</td></tr>"
    table+='</tbody></table></div>';
    create_window("zakaz_details_by_document_id_list_div","Выберите деталь","zakaz_details_by_document_id_list",table);
   });
 }

 function add_document_details_from_zakazes(document_id,sklad_id){
  var send=[];
  send['document_id']=document_id;
   
  api_query_array("/api/index.php",send,"get_zakaz_details_by_document_id").then(function(data){
   var detlen=data.zakaz_details.length;
   var zd={};
   for(var i=0;i<detlen; i++){
    zd[data.zakaz_details[i].id]=data.zakaz_details[i];
   }
   var table='<div style="height:450px; min-width:990px; overflow:auto; border-bottom: 1px solid lightgray;"><table class="table table-hover">';
   table+='<thead><tr><th></th><th>артикул</th><th>бренд</th><th>наименование</th><th>цена закуп</th><th>кол.</th><th>цена заказа</th><th>цена прод. склада</th><th>№заказа</th><th>Клиент</th><th>Склад оприх.</th><th>Мой код</th><th>EAN13</th><th>товарная группа</th></tr></thead><tbody>';
   for(var i in zd){
    //if(search_str=="" || data.zakaz_details[i].article.toLowerCase().includes(search_str.toLowerCase()) || data.zakaz_details[i].brand.toLowerCase().includes(search_str.toLowerCase()) || data.zakaz_details[i].name.toLowerCase().includes(search_str.toLowerCase())){
     table+='<tr class="search_tr">';
     table+='<td><input type="checkbox" \
      id="add_zakaz_detail_to_document_'+zd[i].id+'" \
      data-id="'+zd[i].id+'" \
      data-article="'+zd[i].article+'" \
      data-brand="'+zd[i].brand+'" \
      data-name="'+zd[i].name.replace(/\"/g,"").replace(/\'/g,"")+'" \
      data-detail_id="'+zd[i].detail_id+'" \
      data-brand_id="'+zd[i].brand_id+'" \
      data-dealer_price="'+zd[i].dealer_price+'" \
      data-price="'+zd[i].price+'" \
      data-count="'+zd[i].count+'" \
      data-zakaz_id="'+zd[i].zakaz_id+'" \
      data-zakaz_details_id="'+zd[i].id+'" \
      data-sklad_sale_price="'+zd[i].sklad_sale_price+'" \
      data-is_excise="'+zd[i].is_excise+'" \
      data-self_zakaz_sale_price="'+zd[i].self_zakaz_sale_price+'" \
      data-document_set_price="'+zd[i].document_set_price+'" \
      data-company_id="'+zd[i].company_id+'" \
      data-main_company_id="'+zd[i].main_company_id+'"\
      ></td>';
     table+='<td class="search_article">'+zd[i].article+'</td><td class="search_brand">'+zd[i].brand+'</td><td class="search_name">'+zd[i].name+'</td><td>'+zd[i].dealer_price+'</td>\
     <td><input type="text" size="3" value="'+zd[i].count+'" onchange="change_zakaz_detail_to_document_attr('+zd[i].id+',\'count\',this.value)"></td>\
     <td>'+zd[i].price+'</td><td>'+zd[i].sklad_sale_price+'</td>\
     <td>'+zd[i].zakaz_id+'</td><td>'+zd[i].company_name+'</td><td>'+zd[i].sklad_name+'</td>\
     <td><input type="text" class="" id="detail_my_code_'+zd[i].id+'" value="';
     if(zd[i].sklad_my_code!==null) table+=zd[i].sklad_my_code;
     table+='"></td>';
     table+='<td><input type="text" class="" id="detail_ean13_'+zd[i].id+'" value="';
     if(zd[i].sklad_ean13!==null) table+=zd[i].sklad_ean13;
     table+='"></td>';
     table+="<td><div id='sel_document_groups_list_root_"+zd[i].id+"' style='position:absolute; left:550px; min-width:300px;'></div>\
      <input type='text' id='document_detail_group_name_"+zd[i].id+"' class='' onclick='select_document_detail_groups(0,0,0,"+zd[i].id+");' name='document_detail_group_name' value='"+((zd[i].detail_group_name===null || typeof(zd[i].detail_group_name)=="undefined")?"":zd[i].detail_group_name)+"'>\
      <input type='hidden' name='document_detail_group_id' id='document_detail_group_id_"+zd[i].id+"' value='"+((zd[i].detail_group_id===null || typeof(zd[i].detail_group_id)=="undefined")?"":zd[i].detail_group_id)+"'></td>";
     table+='</tr>';
    //}
   }
   if(i==0) table+="<tr><td colspan='5'>Нет активных, заказнных позиций по данному поставщику. Возможно у Вас нет привязки поставщика в настройках профиля. Выберите в настройках онлайн поиска поставщика.</td></tr>"
   table+='</tbody></table></div>';
   table+='<div class="row">\
    <div class="col-sm-6"><button class="btn btn-primary" onclick="add_zakaz_details_to_document('+document_id+','+sklad_id+');">Сохранить</button></div>\
    <div class="col-sm-6"><input class="form-control pull-right" type="text" id="search_add_doc_from_zakaz" placeholder="быстрый поиск" onchange="filter_document_details_from_zakaz();"></div>\
    </div><script>filter_document_details_from_zakaz()</script>';
   create_window("add_zakaz_details_by_document_id_list_div","Выберите деталь","add_zakaz_details_by_document_id_list",table);
  });
 }

 function filter_document_details_from_zakaz(){
  if(typeof($("#search_add_doc_from_zakaz").val())!="undefined") var search_str=$("#search_add_doc_from_zakaz").val();
  else var search_str="";
  $(".search_tr").map(function() {
    var tr=$(this);
    if(search_str=="" 
      || tr.find(".search_article").html().toLowerCase().includes(search_str.toLowerCase()) 
      || tr.find(".search_brand").html().toLowerCase().includes(search_str.toLowerCase())
      || tr.find(".search_name").html().toLowerCase().includes(search_str.toLowerCase())
      ){
        tr.css('display','table-row');
    }
    else {
      tr.css('display','none');
    }
  }
  )
 }

 function change_zakaz_detail_to_document_attr(data_id,param,value){
  $("#add_zakaz_detail_to_document_"+data_id).attr('data-'+param,value);

 }

 function add_zakaz_details_to_document(document_id,sklad_id){
   var send={};
   send['document_id']=document_id;
   send['sklad_id']=sklad_id;
   send['details']=[];
   $("input[id^=add_zakaz_detail_to_document]").each(function(){
    if($("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).prop("checked")){
      var detail_data={};
      detail_data['article']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-article');
      detail_data['brand']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-brand');
      detail_data['name']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-name');
      detail_data['detail_id']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-detail_id');
      detail_data['brand_id']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-brand_id');
      detail_data['dealer_price']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-dealer_price');
      detail_data['price']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-dealer_price');
      detail_data['is_excise']=parseInt($("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-is_excise'))==1?"on":"";
      detail_data['zakaz_id']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-zakaz_id');
      detail_data['zakaz_details_id']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-zakaz_details_id');
      if(
        typeof($("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-sklad_sale_price'))!="undefined" 
        && parseFloat($("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-sklad_sale_price'))>0
      ){
        if(parseInt($("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-self_zakaz_sale_price'))==0){
          detail_data['sale_price']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-sklad_sale_price');
        }
        else {
          if(parseInt($("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-company_id'))==parseInt($("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-main_company_id'))){
            detail_data['sale_price']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-price');
          }
          else {
            detail_data['sale_price']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-sklad_sale_price');
          }
        }
      } 
      else {
          detail_data['sale_price']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-price');       
      }
      detail_data['my_code']=$("input#detail_my_code_"+$(this).attr('data-id')).val();
      
      detail_data['ean13']=$("input#detail_ean13_"+$(this).attr('data-id')).val();
      detail_data['document_detail_group_id']=$("input#document_detail_group_id_"+$(this).attr('data-id')).val();
      detail_data['document_detail_group_name']=$("input#document_detail_group_name_"+$(this).attr('data-id')).val();
      if(parseFloat(detail_data['price'])===0.0) detail_data['price']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-price');
      detail_data['count']=$("#add_zakaz_detail_to_document_"+$(this).attr('data-id')).attr('data-count');
      send['details'].push(detail_data);
    }
   });
   api_query_obj("/api/index.php",send,"save_document_details").then(function(data){
    if(typeof(data.not_saved_documents)!="undefined" && data.not_saved_documents.length>0){
      var err_str="<table class='table table-hover'><thead><th>артикул</th><th>бренд</th><th>наименование</th><th>ошибка</th></thead><tbody>";
      for(var doc of data.not_saved_documents){
        err_str+='<tr><td>'+doc.article+'</td><td>'+doc.brand+'</td><td>'+doc.name+'</td><td>'+doc.err_reason.err+'</td></tr>';
      }
      err_str+='</tbody></table>';
      bootbox.alert("Некоторые детали не удалось добавить, пожалуйста проверьте<br>"+err_str);
    }
    else {
      $("#add_zakaz_details_by_document_id_list").html('');
    }
    var znak=$("#document_form_"+document_id+" [name=znak]").val();
      get_documents(znak).then(function(){
        get_document_data("document_form_"+document_id,znak);
      });
   });
   return send;
 }

 function set_detail_to_document_detail(article,brand,name,detail_id,brand_id,dealer_price,count){
  $("#zakaz_details_by_document_id_list").html('');
  $("#add_new_document_detail_form [name=article]").val(article);
  $("#add_new_document_detail_form [name=brand]").val(brand);
  $("#add_new_document_detail_form [name=name]").val(name);
  $("#add_new_document_detail_form [name=detail_id]").val(detail_id);
  $("#add_new_document_detail_form [name=brand_id]").val(brand_id);
  $("#add_new_document_detail_form [name=price]").val(dealer_price);
  $("#add_new_document_detail_form [name=count]").val(count);
  $("#add_new_document_detail_form [name=tax]").val(0);
  $("#add_new_document_detail_form [name=sum_w_nds]").val(count*dealer_price);
  recalc_price_and_sum_from_nds('add_new_document_detail_form');
  $("#save_new_detail").removeAttr('disabled');
}

function edit_document_job(document_job_id,znak){
  if(typeof(document_job_id)!="undefined" && parseInt(document_job_id)>0){
    var send=[];
    send['document_job_id']=document_job_id;
    api_query_array("/api/index.php",send,"get_gocument_job").then(function(data){
      if(data.status=="ok"){
        var table='<form id="edit_document_job_'+document_job_id+'"><table class="table table-hover"><tbody>';
        table+='<tr><td>Наименование услуги</td><td><input type="text" class="form-control" name="job_name" value="'+data.document_jobs[0]['job_name']+'" disabled></td></tr>';
        table+='<tr><td>Цена</td><td><input type="text" class="form-control" name="price" value="'+data.document_jobs[0]['price']+'"></td></tr>';
        table+='<tr><td>Количество</td><td><input type="text" class="form-control" name="count" value="'+data.document_jobs[0]['count']+'"></td></tr>';
        if(znak=="-"){
          table+='<tr><td>Автосервис</td><td><input type="text" class="form-control" name="employee_name" value="'+data.document_jobs[0]['service_name']+'" disabled></td></tr>';
          table+='<tr><td>Рабочее место</td><td><input type="text" class="form-control" name="employee_name" value="'+data.document_jobs[0]['workplace_name']+'" disabled></td></tr>';
          table+='<tr><td>Исполнитель услуги</td><td><input type="text" class="form-control" name="employee_name" value="'+data.document_jobs[0]['employee_name']+'" disabled></td></tr>';
          
        }
        create_window("edit_document_job_"+document_job_id+"_div","Редактирование работ","edit_document_job_"+document_job_id,table);
      }
    });

  }
}

function add_new_document_job(document_id,sklad_id,znak){
  var table='<form id="new_document_job_form">\
  <input type="hidden" name="document_id" value="'+document_id+'">\
  <table class="table"><tbody>';
  table+='<tr>\
    <td>Наименование услуги:</td>\
    <td><input type="text" name="job_name" class="form-control" onkeyup="select_jobs_for_document(\''+znak+'\');"><input type="hidden" name="job_id"><div id="select_document_jobs"></div></td>\
  </tr>';
  table+='<tr><td>Цена:</td><td><input type="text" name="price" class="form-control"></td></tr>';
  table+='<tr><td>Кол-во:</td><td><input type="text" name="count" class="form-control"></td></tr>';
  table+='<tr>\
    <td><button type="button" class="btn btn-primary" onclick="save_document_job('+document_id+',\''+znak+'\');">Сохранить</button></td>\
    <td><button type="button" class="btn btn-default pull-right" onclick="close_window(\'edit_document_job\');">Отменить</button></td>\
  </tr>';
  table+='</tbody></table></form>';
  create_window("edit_document_job_div"," Добавление / изменение услуги","edit_document_job",table);
}

function select_jobs_for_document(znak){
  var send=[];
  if(znak=="+")
    send['job_type']=3;
  else
    send['job_type']=1;
  send['search_service_jobs']=$('#new_document_job_form input[name=job_name]').val();
  api_query_array("/api/index.php",send,"get_service_jobs").then(function(data){
    if(data.status=="ok"){
      var len=data.service_jobs.length;
      var table='<table class="table table-hover"><thead><tr><th>№</th><th>Наименование работ</th><th>Цена</th><th>код работ</th><th>штрих-код</th><th>работник</th></tr></thead><tbody>';
      for(var i=0; i<len; i++){
        table+='<tr onclick="set_document_job('+data.service_jobs[i].id+',\''+data.service_jobs[i].name+'\');"><td>'+(i+1)+'</td><td>'+data.service_jobs[i].name+'</td><td>'+data.service_jobs[i].price+'</td><td>'+data.service_jobs[i].job_code+'</td>';
        table+='<td>'+data.service_jobs[i].shtrih_code+'</td><td>';
        if(parseInt(data.service_jobs[i].default_employee)>0) table+=data.service_jobs[i].employee_lastname+' '+data.service_jobs[i].employee_name;
        else table+='не назначен';
        table+='</td>';
        table+='</tr>';
      }
      table+='</tbody></table>';
      if(len>0) 
        create_window("select_document_jobs_div","Выберите работу","select_document_jobs",table);
    }
    else {
      $("#select_document_jobs").html('');
    }
  });
}

function set_document_job(job_id,job_name){
  $("#new_document_job_form input[name=job_id]").val(job_id);
  $("#new_document_job_form input[name=job_name]").val(job_name);
  $("#select_document_jobs").html('');
}

function save_document_job(document_id,znak){
  api_query("/api/index.php","new_document_job_form","save_document_job").then(function(data){
    if(data.status=="ok"){
      $("#edit_document_job").html('');
      get_document_jobs("document_form_"+document_id,znak);
    }
  });
}

function get_documents_csv(znak){
  var send=new Array();
  send['znak']=znak;
  if(znak=="+") var znak_ch="_plus";
  if(znak=="-") var znak_ch="_minus";
  if(znak=="rtd") var znak_ch="_rtd";
  if(znak=="rfc") var znak_ch="_rfc";
  api_query("/api/index.php","document_client_search"+znak_ch,"get_documents_csv").then(function(data){
  //api_query_array("/api/index.php",send,"get_documents_csv").then(function(data){
    //alert(data.export_file);

    var blob = b64toBlob(data.file); //new Blob([data.file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "export.csv";
    link.click();
  });
}

function get_documents_xls(znak){
  var send=new Array();
  send['znak']=znak;
  if(znak=="+") var znak_ch="_plus";
  if(znak=="-") var znak_ch="_minus";
  if(znak=="rtd") var znak_ch="_rtd";
  if(znak=="rfc") var znak_ch="_rfc";
  api_query("/api/index.php","document_client_search"+znak_ch,"get_documents_xls").then(function(data){
  //api_query_array("/api/index.php",send,"get_documents_xls").then(function(data){
    //alert(data.export_file);

    var blob = b64toBlob(data.file); //new Blob([data.file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "export.xlsx";
    link.click();
  });
}

function get_upd_xls(znak,id){
  var send=new Array();
  send['znak']=znak;
  send['document_id']=id;
  api_query_array("/api/index.php",send,"get_upd_xls").then(function(data){
  //api_query_array("/api/index.php",send,"get_documents_xls").then(function(data){
    //alert(data.export_file);
    var blob = b64toBlob(data.file); //new Blob([data.file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "upd.xlsx";
    link.click();
  });
}

function get_ukd_xls(znak,id){
  var send=new Array();
  send['znak']=znak;
  send['document_id']=id;
  api_query_array("/api/index.php",send,"get_ukd_xls").then(function(data){
  //api_query_array("/api/index.php",send,"get_documents_xls").then(function(data){
    //alert(data.export_file);
    var blob = b64toBlob(data.file); //new Blob([data.file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "ukd.xlsx";
    link.click();
  });
}

function show_document_detail_menu(document_detail_id,document_id,znak){
  var menu='';
  menu+='<div style="border: solid black 1px; width: 240px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
  menu+='<button type="button" class="close pull-right" onclick="$(\'#document_detail_menu_'+document_detail_id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
  menu+='<table class="table table-hover">';
  menu+='<tr><td><a onclick="return_document_detail_form('+document_detail_id+','+document_id+',\''+znak+'\')">Вернуть поставщику</a></td></tr>';
  menu+='</table></div>';
  $("#document_detail_menu_"+document_detail_id).html(menu);
}

function return_document_detail_form(document_detail_id,document_id,znak){
  var table='<form id="return_document_detail_form_'+document_detail_id+'">';
  table+='<table class="table">';
  table+='<tr><td>Количество</td><td><input type="text" name="return_count" value="1"><input type="hidden" name="document_detail_id" value="'+document_detail_id+'"></td></tr>';
  table+='<tr><td><button type="button" class="btn btn-sm btn-primary" onclick="return_document_detail('+document_detail_id+','+document_id+',\''+znak+'\')">Вернуть поставщику</button></td>\
  <td><button class="btn btn-sm btn-default" type="button" onclick="$(\'#return_document_detail\').html(\'\')">Отменить</button></td></tr>';
  table+='</table></form>';
  create_window("return_document_detail_div","Возврат поставщику","return_document_detail",table);
}

function return_document_detail(document_detail_id,document_id,znak){
  api_query("/api/index.php","return_document_detail_form_"+document_detail_id,"make_document_detail_return_to_dealer").then(function(data){
    if(data.status=="ok"){
      $('#return_document_detail').html('');
      get_document_details('document_form_'+document_id,znak);
    }
  })
}

function get_document_details_xls(document_id){
  api_query("/api/index.php","document_form_"+document_id,"get_document_details_xls").then(function(data){
    //alert(data.export_file);

    var blob = b64toBlob(data.file); //new Blob([data.file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "document_details_"+document_id+".xlsx";
    link.click();
  });
}

function checked_document_detail(id){
  var send=new Array();
  send['document_detail_id']=id;
  var checkbox = document.getElementById('checkbox_' + id);
  var value = checkbox.checked ? 1 : 0;
  send['checked']=value;

  api_query_array("/api/index.php",send,"checked_document_details").then(function(data){
    if(data.status=="err"){
      var checkbox = document.getElementById('checkbox_' + id);
      checkbox.checked = 0;
    }
  });
}

function print_document_detail(id){
  var send=new Array();
  send['document_detail_id']=id;
  var checkbox = document.getElementById('print_checkbox_' + id);
  var value = checkbox.checked ? 1 : 0;
  send['checked']=value;

  api_query_array("/api/index.php",send,"print_document_details").then(function(data){
    if(data.status=="err"){
      var checkbox = document.getElementById('print_checkbox_' + id);
      checkbox.checked = 0;
    }
  });
}

function delete_detail_mark(mark_id,document_details_id,detail_form,znak){
  var send=new Array();
  send['detail_mark_id']=mark_id;
  send['mark_znak']=znak;
  send['document_details_id']=document_details_id;
  api_query_array("/api/index.php",send,"delete_detail_mark").then(function(data){
    if(data.status=="ok"){
      edit_document_detail(detail_form,znak);
    }
  });
}

function set_detail_mark(detail_form,document_details_id,znak){
    //var send=new Array();
    var table='<div id="document_detail_mark">\
    <input type="hidden" name="document_details_id" id="mark_document_details_id" value="'+document_details_id+'">\
    <input type="hidden" name="mark_znak" id="mark_znak" value="'+znak+'">\
    <table class="table"><thead><tr><th nowrap colspan="2">код маркировки</th></tr></thead><tbody>';
    table+='<tr><td style="width: 22em;" colspan="2">';
    table+='<input type="text" name="detail_mark" class="form-control" value="" id="detail_mark" size="60" placeholder="Отсканируйте код маркировки" onchange="save_detail_mark(\'document_detail_mark\',\''+detail_form+'\',\''+znak+'\');">';
    table+='</td></tr>';
    table+='<tr><td><button type="button" class="btn btn-success" onclick="save_detail_mark(\'document_detail_mark\',\''+detail_form+'\',\''+znak+'\');">Сохранить</button></td>';
    table+='<td><button type="button" class="btn btn-default pull-right" onclick="close_window(\'document_detail_mark_select\');">Отмена</button></td></tr>';
    table+='<tbody></table></div>';
    create_window("document_detail_mark_select_div","Введите код маркировки","document_detail_mark_select",table);
}

function edit_detail_mark(detail_form,detail_mark_id,document_details_id,znak){
  var send=new Array();
  send['detail_mark_id']=detail_mark_id;
  api_query_array("/api/index.php",send,"get_detail_mark").then(function(data){
    if(data.status=="ok"){
      var table='<form id="document_detail_mark">\
      <input type="hidden" name="document_details_id" id="mark_document_details_id" value="'+document_details_id+'">\
      <input type="hidden" name="detail_mark_id" id="mark_document_detail_mark_id" value="'+detail_mark_id+'">\
      <input type="hidden" name="mark_znak" id="mark_znak" value="'+znak+'">\
      <table class="table"><thead><tr><th nowrap colspan="2">код маркировки</th></tr></thead><tbody>';
      table+='<tr><td style="width: 22em;" colspan="2">';
      table+='<input type="text" name="detail_mark" class="form-control" value="'+data.DetailMark.mark+'" id="detail_mark" size="30" placeholder="Отсканируйте код маркировки" onchange="save_detail_mark(\'document_detail_mark\',\''+detail_form+'\',\''+znak+'\');">';
      table+='</td></tr>';
      table+='<tr><td><button type="button" class="btn btn-success" onclick="save_detail_mark(\'document_detail_mark\',\''+detail_form+'\',\''+znak+'\');">Сохранить</button></td>';
      table+='<td><button type="button" class="btn btn-default pull-right" onclick="close_window(\'document_detail_mark_select\');">Отмена</button></td></tr>';
      table+='<tbody></table></form>';
      create_window("document_detail_mark_select_div","Введите код маркировки","document_detail_mark_select",table);
    }
  })
}

function save_detail_mark(form_id,detail_form,znak){
  var send=[];
  send['document_details_id']=$("#"+form_id+" input[name=document_details_id]").val();
  send['mark_znak']=$("#"+form_id+" input[name=mark_znak]").val();
  send['detail_mark']=$("#"+form_id+" input[name=detail_mark]").val();
    api_query_array("/api/index.php",send,"save_detail_mark").then(function(data){
      if(data.status=="ok"){
        close_window('document_detail_mark_select');
        edit_document_detail(detail_form,znak);
      }
    });
    return false;
}