// + test from nastya


function g_show_item_vals(type,i,tab,article){
  var table='<table class="table table-hover">';
    $.each(Object.assign({},g_all_items[tab][article][i]),function(item_key,item_val){
      if(typeof(full_detail_info[item_key])!="undefined" && full_detail_info[item_key]['show']==1 && item_val!="") 
        table+='<tr><td>'+full_detail_info[item_key]['descr_rus']+'</td><td>'+item_val+'</td></tr>';
    });
  table+='</table>';
  if(typeof(g_all_items[tab][article][i]['img'])!="undefined" && g_all_items[tab][article][i]['img']!="" && g_all_items[tab][article][i]['img']!=null) 
    table+='<img src="/get_file/'+g_all_items[tab][article][i]['deliverer_id']+'/'+g_all_items[tab][article][i]['img']+'">';
  table+='<p align="center"><button class="btn btn-primary" ';
  table+='onclick="close_window(\'g_show_item_'+tab+"_"+article+'\')"';
  table+='>Ok</button</p>';
  create_window_centered_blue("g_show_item_"+tab+"_"+article+"_div","Дополнительные данные детали","g_show_item_"+tab+"_"+article,table);
}


function g_items_to_table(tab,article,brand){
  //$("body").css("cursor", "progress");
  if(typeof(g_items_group[tab])=="undefined") g_items_group[tab]=[];
  if(typeof(g_items_group[tab][article])=="undefined") g_items_group[tab][article]=items_group[tab];
  if(typeof(g_items_group_count[tab][article])=="undefined") {
    g_items_group_count[tab][article]=new Array();
    for(var g=0;g<g_items_group[tab][article][0].length;g++){
      g_items_group_count[tab][article][g_items_group[tab][article][0][g]]=0;
    }
  }
  if(typeof(g_filter[tab])=="undefined") g_filter[tab]={};//new Array();
  if(typeof(g_filter[tab][article])=="undefined") g_filter[tab][article]={};//new Array();
  if(typeof(g_item_groups_data[tab])=="undefined") g_item_groups_data[tab]=new Array();
  if(typeof(g_item_groups_data[tab][article])=="undefined") g_item_groups_data[tab][article]=new Array();
  if(typeof(g_show_count[tab])=="undefined") g_show_count[tab]=new Array();
  if(typeof(g_show_count[tab][article])=="undefined"){
    g_show_count[tab][article]=new Array();
    for(var g=0; g<g_items_group[tab][article].length; g++){ 
      for (var t=0; t<g_items_group[tab][article][g].length; t++){
        g_show_count[tab][article][g_items_group[tab][article][g][t]]=20;
      }
    }
  }
  var items=g_all_items[tab][article];
  if(typeof(items)!="undefined")
    var items_count=items.length;
  else return 0;
  var i,orig_i=0,analog_i=0,sklad_orig_i=0,sklad_analog_i=0;
  var search_str=article;
  var group_items=[];
  group_items[tab]=[];
  for(var g=0; g<g_items_group[tab][article].length; g++){ 
    for (var t=0; t<g_items_group[tab][article][g].length; t++){
      group_items[tab][g_items_group[tab][article][g][t]]=[];
    }
  }
  //var orig_items=new Array(),analog_items=new Array(),sklad_orig_items=new Array(),sklad_analog_items=new Array();
  var search_brand=brand.replace(/[\s+\.\/_]/g,"").toUpperCase();
  var search_brand_id=0;//parseInt($("#search_form_"+tab+" input[name=brand_id]").val());
  //set_filter(tab,'brand',search_brand);
  if(typeof(g_filter[tab][article]['filter_counter'])=="undefined"){
    g_filter[tab][article]['filter_counter']={};
    g_filter[tab][article]['filter_counter']['article']=0;
    g_filter[tab][article]['filter_counter']['brand']=0;
    g_filter[tab][article]['filter_counter']['name']=0;
    g_filter[tab][article]['filter_counter']['count']=0;
    g_filter[tab][article]['filter_counter']['time']=0;
    g_filter[tab][article]['filter_counter']['city_name']=0;
    g_filter[tab][article]['filter_counter']['stock']=0;
    g_filter[tab][article]['filter_counter']['deliverer']=0;
    g_filter[tab][article]['filter_counter']['pp']=0;
    
  }
  if(typeof(g_filter[tab][article]['article'])=="undefined"){
      g_filter[tab][article]['article']=new Array();
  }
  if(typeof(g_filter[tab][article]['brand'])=="undefined"){
    g_filter[tab][article]['brand']=new Array();
  }
  if(typeof(g_filter[tab][article]['name'])=="undefined"){
    g_filter[tab][article]['name']=new Array();
  }
  if(typeof(g_filter[tab][article]['count'])=="undefined"){
    g_filter[tab][article]['count']=new Array();
  }
  if(typeof(g_filter[tab][article]['time'])=="undefined"){
    g_filter[tab][article]['time']=new Array();
  }
  if(typeof(g_filter[tab][article]['city_name'])=="undefined"){
    g_filter[tab][article]['city_name']=new Array();
  }
  if(typeof(g_filter[tab][article]['stock'])=="undefined"){
    g_filter[tab][article]['stock']=new Array();
  }
  if(typeof(g_filter[tab][article]['deliverer'])=="undefined"){
    g_filter[tab][article]['deliverer']=new Array();
  }
  if(typeof(g_filter[tab][article]['pp'])=="undefined"){
    g_filter[tab][article]['pp']=new Array();
  }
  var all_time_filter=$("#group_time_range_"+tab).val();
  if(parseInt(all_time_filter)<41 && g_filter[tab][article]['filter_counter']['time']==0){
    group_set_filter(tab,'time',btoa(toBinary(all_time_filter)),btoa(toBinary(article)),btoa(toBinary(brand)));
  }
  var round_for=parseInt($('#round_for').val());
  if(typeof(round_for)=="undefined" || parseInt(round_for)==0) round_for=1;

  
  if(typeof(workers[tab])=="undefined") {
    workers[tab]=new Array();
    workers[tab]['worker']=new Worker("/js/filter_worker.js?ver=1.4.5");
    workers[tab]['working']=0;
  }
  if(workers[tab]['working']==0){
    workers[tab]['working']=1;
    workers[tab]['start_time']=(new Date().getTime());  
    workers[tab]['worker'].postMessage(
      {
        items: items, 
        filter: g_filter[tab], 
        search_str: search_str, 
        round_for: round_for, 
        items_count: items_count, 
        tab: article, 
        search_brand: search_brand, 
        search_brand_id: search_brand_id,
        items_group: g_items_group[tab][article]
      }
    );
  }
  workers[tab]['worker'].onmessage=function(e){
    if(e.data.messtype=="proc_count") {
      $("#worker_"+tab).html("выполнено "+e.data.proc+"%, строк: "+e.data.strings);
    }
    else {
      $.unblockUI();
      workers[tab]['stop_time']=(new Date().getTime());
      $("#worker_"+tab).html("выполнено 100%, время выполнения: "+((workers[tab]['stop_time']-workers[tab]['start_time'])/1000)+" c.");
      workers[tab]['working']=0;
      items=[];
      items=e.data.items;
      g_filter[tab]=e.data.filter;
      group_items=e.data.group_items;
      if(typeof(g_filtered_items[tab])=="undefined") g_filtered_items[tab]=new Array();
      g_filtered_items[tab][article]=e.data.group_items;
      if(!$("#analog_group_search_"+tab).prop("checked")) group_items['analog']=[];
      //if(typeof(g_items_group_count[tab])=="undefined") g_items_group_count[tab]=[];
      g_items_group_count[tab][article]['sklad_orig']=group_items['sklad_orig'].length;
      g_items_group_count[tab][article]['sklad_analog']=group_items['sklad_analog'].length;
      g_items_group_count[tab][article]['orig']=group_items['orig'].length;
      g_items_group_count[tab][article]['analog']=group_items['analog'].length;
      var show_extended_price=1;
      var table="<div>";
      table+='<span style="width:50%; padding-left: 5px;"> Группировка: Тип';
      if(g_items_group[tab][article].length>1) {
        for (var s=1; s<g_items_group[tab][article].length; s++){ 
          table+='-><a onclick="remove_from_g_items_group(\''+g_items_group[tab][article][s]+'\','+tab+',\''+article+'\',\''+brand+'\');">'+full_detail_info[g_items_group[tab][article][s]]['descr_rus']+'</a>';
        }
      }
      //table+=' <input type="checkbox" id="default_group_by_'+tab+'" onchange="set_default_group_by('+tab+');"';
      //if(JSON.stringify(default_items_group)===JSON.stringify(items_group[tab])) table+=' checked="checked" ';
      //table+='> По умолчанию';
      table+='</span>';
      table+="<span id='search_info_"+tab+"_"+article+"_"+brand+"' class='pull-right' style='width:50%'> Найдено: на складе - "+(g_items_group_count[tab][article]['sklad_orig']+g_items_group_count[tab][article]['sklad_analog'])+", Запрошеный артикул - "+g_items_group_count[tab][article]['orig']+", Аналоги - "+g_items_group_count[tab][article]['analog']+"</span>";
      table+='</div>';
      table+="<div id='g_show_item_"+tab+"_"+article+"'></div><table class='table table-hover table-striped fixtable search-data'>";
      table+="<div class='clickable'>";
      table+="<thead id='header-fixed'><tr><th colspan='"+(g_items_group[tab][article].length)+"'><img src='/images/catalog_tree_sm.png' style='width:20px;'></th>";
      table+=g_make_header("article","Артикул",tab,article,brand);
      table+=g_make_header("brand","Брэнд",tab,article,brand);
      table+=g_make_header("name","Наименование",tab,article,brand);
      if(show_extended_price) table+="<th>Цена закуп.</th>";
      table+=g_make_header("cost","Цена",tab,article,brand);
      table+=g_make_header("count","К-во",tab,article,brand);
      table+=g_make_header("time","Срок пост.",tab,article,brand);
      table+=g_make_header("city_name","Город",tab,article,brand);
      table+=g_make_header("stock","Склад",tab,article,brand);
      table+=g_make_header("deliverer","Поставщик",tab,article,brand);

      table+="<th title='Вероятность поставки'><img src='/new_images/bar-chart.svg' style='width: 20px;'></th><th></th></tr><thead>";
      var display=1;
      for(var igc0=0; igc0<g_items_group[tab][article][0].length; igc0++){
        if(g_items_group[tab][article].length>1){
          if(typeof(g_toggle_classes[tab][article])=="undefined") g_toggle_classes[tab][article]=[];
          g_toggle_classes[tab][article][g_items_group[tab][article][0][igc0]+"_items_"+tab+"_"+article]=1;
          table+="<tbody><tr style='background-color: #d3f3d3;'>\
            <td colspan='"+(12+parseInt(g_items_group[tab][article].length))+"'>\
            <span id='"+g_items_group[tab][article][0][igc0]+"_items_"+tab+"_"+article+"_gname' class='glyphicon glyphicon-circle-arrow-down' onclick='g_toggle_class(\""+g_items_group[tab][article][0][igc0]+"_items_"+tab+"_"+article+"\",0,"+tab+",\""+article+"\");'> \
            <b>"+items_group_names[igc0]+"</b></td></tr></tbody>";
            if(typeof(g_toggle_classes[tab][article][g_items_group[tab][article][0][igc0]+"_items_"+tab+"_"+article])=="undefined" || g_toggle_classes[tab][article][g_items_group[tab][article][0][igc0]+"_items_"+tab+"_"+article]==1) {
              display=1;
            }
            else display=0;
          //table+="<tbody class='"+items_group[tab][0][igc0]+"_items_"+tab+"' style='overflow: auto;'></tbody>";
          table+=print_g_item_group(group_items[g_items_group[tab][article][0][igc0]],1,tab,article,brand,g_items_group[tab][article][0][igc0],show_extended_price,igc0,g_items_group[tab][article][0][igc0]+"_items_"+tab+"_"+article,display);
        }
        else{
          if(group_items[g_items_group[tab][article][0][igc0]].length>0){
            table+="<tbody><tr style='background-color: #d3f3d3;'><td colspan='12'><span id='"+g_items_group[tab][article][0][igc0]+"_arrow_"+tab+"_"+article+"' class='glyphicon glyphicon-circle-arrow-down' onclick='$(\"#"+g_items_group[tab][article][0][igc0]+"_items_"+tab+"_"+article+"\").toggle(); $(\"#"+g_items_group[tab][article][0][igc0]+"_arrow_"+tab+"_"+article+"\").toggleClass(\"glyphicon-circle-arrow-down glyphicon-circle-arrow-right\");'></span><b> "+items_group_names[igc0]+"</b></td></tr></tbody>";
            table+="<tbody id='"+g_items_group[tab][article][0][igc0]+"_items_"+tab+"_"+article+"' style='overflow: auto;'>";
            table+=print_g_items_tbody(group_items[g_items_group[tab][article][0][igc0]],tab,article,brand,g_items_group[tab][article][0][igc0],show_extended_price);
            table+="</tbody>";
          }
        }
      }
      table+="</table>";
      $("#zapchasti_content_"+tab+"_"+article+"_"+brand).css("font-size","12px");
      $("#zapchasti_content_"+tab+"_"+article+"_"+brand).html(table);
      $("#search_info_"+tab+"_"+article+"_"+brand).html("Найдено: на складе - "+(g_items_group_count[tab][article]['sklad_orig']+g_items_group_count[tab][article]['sklad_analog'])+", Запрошеный артикул - "+g_items_group_count[tab][article]['orig']+", Аналоги - "+g_items_group_count[tab][article]['analog']+"</span>");
      $.unblockUI();
    }
  } // if worker enabled
  //$("body").css("cursor", "default");
  //resize_table();
  //$("#header-fixed").css("position","fixed");
}

function set_g_items_show(tab,article,brand,group,count){
  g_show_count[tab][article][group]=count;
  g_items_to_table(tab,article,brand);
}

function g_toggle_class(send_class_name,i,tab,article){
if(g_toggle_classes[tab][article][send_class_name]==1) {
  g_toggle_classes[tab][article][send_class_name]=0;
  $("[class^="+send_class_name+"_]").hide(); 
  $("."+send_class_name+"_gname").show();
  $("#"+send_class_name+"_gname").attr("class","glyphicon glyphicon-circle-arrow-right");
  //$("#"+send_class_name+"_"+i).toggleClass("glyphicon-circle-arrow-right glyphicon-circle-arrow-down");
}
else {
  g_toggle_classes[tab][article][send_class_name]=1;
  $("[class^="+send_class_name+"_]").each(function(index){
    if($(this).attr('class').indexOf("_gname")!=-1) {
      $(this).show();
      $("#"+send_class_name+"_gname").attr("class","glyphicon glyphicon-circle-arrow-down");
      if(g_toggle_classes[tab][article][$(this).attr('class').replace("_gname","")]==1) $("."+$(this).attr('class').replace("_gname","")+"_details").show();
    }
    //else {
    //  if(toggle_classes[tab][$(this).attr('class')]==1) $(this).show(); 
    //}
    //$("#"+send_class_name+"_"+i).toggleClass("glyphicon-circle-arrow-right glyphicon-circle-arrow-down");
  });
}
}

function print_g_item_group(items,igc,tab,article,brand,item_group,show_extended_price,igc0,class_name,display){
var table="";
if(igc>=g_items_group[tab][article].length) {
  table+="<tbody style='overflow: auto; ";
  if(g_toggle_classes[tab][article][class_name]==1 && display==1) table+="";
  else table+=" display:none;";
  table+="' class='";
  table+=class_name+"_details";
  table+="'>";  
  table+=print_g_items_tbody(items,tab,article,brand,item_group,show_extended_price,class_name);
  table+="</tbody>";
  g_items_group_count[tab][article][g_items_group[tab][article][0][igc0]]+=items.length;
  return table;
}
else {
  var keys=Object.keys(items);
  igc++;
  var send_class_name;//=class_name;
  for(var i=0; i<keys.length; i++){
    var index=keys[i].replace(/[\s\.\/_&\-\'\"\(\)\\,!#$=<>\[\]%]/g,"").toUpperCase();
    send_class_name=class_name+"_"+index;
    var itm=items[keys[i]];
    if(typeof(g_toggle_classes[tab])=="undefined") g_toggle_classes[tab]=[];
    if(typeof(g_toggle_classes[tab][article])=="undefined") g_toggle_classes[tab][article]=[];
    if(igc<g_items_group[tab][article].length) { 
      //table+="class='glyphicon glyphicon-circle-arrow-down' ";  
      if(typeof(g_toggle_classes[tab][article][send_class_name])=="undefined") g_toggle_classes[tab][article][send_class_name]=1;
    }
    else { 
      //table+="class='glyphicon glyphicon-circle-arrow-right' "; 
      if(typeof(g_toggle_classes[tab][article][send_class_name])=="undefined") g_toggle_classes[tab][article][send_class_name]=0;
    }
    var pre_table=print_g_item_group(itm,igc,tab,article,brand,item_group,show_extended_price,igc0,send_class_name,display);
    table+="<tbody class='"+send_class_name+"_gname'><tr style='background-color: #e3f3e3;' ondblclick='g_toggle_class(\""+send_class_name+"\","+i+","+tab+",\""+article+"\")'>";
    for(var k=0; k<(igc-1); k++) table+="<td></td>";
    table+="<td style='width:12px; padding: 2px;'>\
    <span id='"+send_class_name+"_gname' ";
    if(igc<g_items_group[tab][article].length || g_toggle_classes[tab][article][send_class_name]==1) { 
      table+="class='glyphicon glyphicon-circle-arrow-down' ";  
      //if(typeof(toggle_classes[tab][send_class_name])=="undefined") toggle_classes[tab][send_class_name]=1;
    }
    else { 
      table+="class='glyphicon glyphicon-circle-arrow-right' "; 
      //if(typeof(toggle_classes[tab][send_class_name])=="undefined") toggle_classes[tab][send_class_name]=0;
    }
    table+="onclick='g_toggle_class(\""+send_class_name+"\","+i+","+tab+",\""+article+"\")'>\
    </span>\
    </td><td colspan='"+(2+parseInt(g_items_group[tab][article].length+1)-igc)+"'>\
    <b> "+keys[i]+" </b></td>";
    if(show_extended_price){
      table+="<td><b>";
      if(typeof(g_item_groups_data[tab][article][send_class_name])!="undefined") table+="<span>"+parseFloat(g_item_groups_data[tab][article][send_class_name]['dealer_price']).toFixed(2)+"</span>";
      table+="</b></td>";
    }
    table+="<td><b>";
    if(typeof(g_item_groups_data[tab][article][send_class_name])!="undefined") table+="<span>"+parseFloat(g_item_groups_data[tab][article][send_class_name]['price']).toFixed(2)+"</span>";
    table+="</b></td>";
    table+="<td><b>";
    if(typeof(g_item_groups_data[tab][article][send_class_name])!="undefined") table+="<span>"+((g_item_groups_data[tab][article][send_class_name]['count']>0)?g_item_groups_data[tab][article][send_class_name]['count']:"Под заказ")+"</span>";
    table+="</b></td>";
    table+="<td><b>";
    if(typeof(g_item_groups_data[tab][article][send_class_name])!="undefined") table+="<span>"+(g_item_groups_data[tab][article][send_class_name]['time']>0?g_item_groups_data[tab][article][send_class_name]['time']:(g_item_groups_data[tab][article][send_class_name]['count']>0?"В наличии":"Под заказ"))+"</span>";
    table+="</b></td>";
    table+="<td colspan='5'></td></tr></tbody>";
    table+=pre_table;
    //send_class_name=class_name;
  }
  return table;
}

}

function print_g_items_tbody(itemsi,tab,article,brand,item_group,show_extended_price,class_name){
    var table="";
    if(itemsi.length<=g_show_count[tab][article][item_group])
      var items_show_count=itemsi.length;
    else
      var items_show_count=g_show_count[tab][article][item_group];
    if(typeof(g_item_groups_data[tab][article])=="undefined") g_item_groups_data[tab][article]=new Array();
    if(typeof(g_item_groups_data[tab][article][class_name])=="undefined") g_item_groups_data[tab][article][class_name]=new Array();
    g_item_groups_data[tab][article][class_name]['dealer_price']=g_all_items[tab][article][itemsi[0]['item_index']]['price'];
    g_item_groups_data[tab][article][class_name]['price']=g_all_items[tab][article][itemsi[0]['item_index']]['cost'];//items[0]['cost'];
    g_item_groups_data[tab][article][class_name]['count']=g_all_items[tab][article][itemsi[0]['item_index']]['count'];//items[0]['count'];
    g_item_groups_data[tab][article][class_name]['time']=g_all_items[tab][article][itemsi[0]['item_index']]['time'];//items[0]['time'];
    for (i=0; i<items_show_count; i++){
      var item=g_all_items[tab][article][itemsi[i]['item_index']];
      if(typeof(item)=="undefined") continue;
      item["prim"]="";
      if(typeof(item["additional"])!="undefined" && item["additional"].length>0) item["prim"]+="Примечание: "+item["additional"]+"\n";
        table+="<tr title='"+item["prim"]+"' ondblclick='g_show_item_vals(0,"+itemsi[i]['item_index']+","+tab+",\""+article+"\");'>";
        //for(var k=0; k<(items_group.length); k++) table+="<td></td>";
        table+="<td colspan='"+(items_group[tab].length)+"' nowrap>";//<div id='g_show_item_"+tab+"_"+article+"' class='pull-right'></div>";
        item["attention"]="";
        if(typeof(item["mcount"])!="undefined" && parseInt(item["mcount"])>1) item["attention"]+="Минимальное количество: "+item["mcount"]+"\n";
        if(typeof(item["multiplicity"])!="undefined" && item["multiplicity"]>1) item["attention"]+="Кратность заказа: "+item["multiplicity"]+"\n";
        if(typeof(item["return"])!="undefined" && item["return"]==0) item["attention"]+="Внимание: Возврат невозможен!!!\n";
        if(item["attention"].length>0){
           if(typeof(item["return"])!="undefined" && item["return"]==0) table+='<img src="/images/warning-red.png" width="16px" title="'+item["attention"]+'" class="pull-right">';
           else table+='<img src="/images/warning.png" width="16px" title="'+item["attention"]+'">';
        }
        if(typeof(item['img'])!="undefined" && item['img']!="" && item['img']!=null) table+='<img src="/images/image-icon.png" height="20px" class="pull-right">';
        if(clear_word(item["article"])!=clear_word(article) || clear_word(item["brand"])!=clear_word(brand)) 
            table+='<a onclick="save_cross_direct(\''+article+'\',\''+brand+'\',\''+item["article"]+'\',\''+item["brand"]+'\',\''+((item["name"]!==null && typeof(item["name"])!="undefined")?item["name"].replaceAll("'","").replaceAll(">","").replaceAll('"',''):"")+'\');" class="pull-left">\
            <img src="/new_images/exchange.svg" style="width:16px;" title="добавить в мои кроссы"></a> ';
        table+="</td><td>"+item["article"]+"</td><td>"+item["brand"]+"</td><td>"+item["name"]+"</td>";
        if(show_extended_price)
          table+="<td>"+item['price']+"</td>";
        table+="<td><b>"+item["cost"]+"</b></td><td>"+(item["count"]>0?item["count"]:"Под заказ")+"</td><td>"+((item["time"]>0)?(item["time"]+" д."):(item['count']>0?"В наличии":"Под заказ"))+"</td>"
        table+="<td>"+item["city_name"]+"</td><td>"+item["stock"]+"</td><td>";
        table+="<img style='width:16px;' src='";
        switch(item['deliverer_type']){
          case "sort1": if(typeof(plugins_data[item['deliverer_id']])!="undefined") table+=plugins_data[item['deliverer_id']].icon;
                        break;
          case "sklad": table+="/new_images/stock.svg"; break;
          case "price_list": table+="/new_images/file.svg"; break;
        }
        table+="'> ";
        table+=item["deliverer"]+"</td>";
        table+="<td>";
        if(item['deliverer_type']=="price_list"){
          let updatedatetime=item['update_date'].split(" ");
          let updatedate=updatedatetime[0].split("-");
          let udate=updatedate[2]+'.'+updatedate[1]+'.'+updatedate[0];
          table+='<span style="font-size: 10px;" title="Время обновления: '+udate+' '+updatedatetime[1]+'">'+udate+'</span>';
        }
        else {
          if(item["chance"]>=89)
            table+="<div class='progress'><div class='progress-bar progress-bar-success' role='progressbar' aria-valuenow='"+item["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+item["chance"]+"%; height:20px;'><span>"+item["chance"]+"%</span></div></div>";
          if(item["chance"]>=69 && item["chance"]<89)
            table+="<div class='progress'><div class='progress-bar progress-bar-warning' role='progressbar' aria-valuenow='"+item["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+item["chance"]+"%; height:20px;'><span>"+item["chance"]+"%</span></div></div>";
          if(item["chance"]>0 && item["chance"]<69)
            table+="<div class='progress'><div class='progress-bar progress-bar-danger' role='progressbar' aria-valuenow='"+item["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+item["chance"]+"%; height:20px;'><span>"+item["chance"]+"%</span></div></div>";
        }
        table+="</td>";
        table+="<td><a onclick='g_to_cart("+itemsi[i]['item_index']+","+tab+",\""+article+"\",\""+brand+"\");'><img src='/new_images/shopping-cart.svg' style='width: 15px;'></a></td>";
        table+="</tr>";
    }
    if(items_show_count==20 && itemsi.length>20){
      table+='<tr><td colspan="13">Показаны первые 20 позиций <a onclick="set_g_items_show('+tab+',\''+article+'\',\''+brand+'\',\''+item_group+'\','+itemsi.length+')">показать все</a></td>';
    }
    else {
      if(itemsi.length>20)
        table+='<tr><td colspan="13"><a onclick="set_g_items_show('+tab+',\''+article+'\',\''+brand+'\',\''+item_group+'\',20)">показать 20</a></td>';
    }
    
    return table;
}

function group_set_orig_items_show(tab,show_count,article,brand){
  g_orig_show_count[tab][article]=show_count;
  g_items_to_table(tab,article,brand);
}

function group_set_analog_items_show(tab,show_count,article,brand){
  g_analog_show_count[tab][article]=show_count;
  g_items_to_table(tab,article,brand);
}

function group_set_sklad_orig_items_show(tab,show_count,article,brand){
  g_sklad_orig_show_count[tab][article]=show_count;
  g_items_to_table(tab,article,brand);
}

function group_set_sklad_analog_items_show(tab,show_count,article,brand){
  g_sklad_analog_show_count[tab][article]=show_count;
  g_items_to_table(tab,article,brand);
}

function g_to_cart(id,tab,article,brand){
  if(typeof(g_saved_basket_detail[tab])=="undefined") g_saved_basket_detail[tab]=[];
  if(typeof(g_saved_basket_detail[tab][article])=="undefined") g_saved_basket_detail[tab][article]=[];
    g_saved_basket_detail[tab][article]=g_all_items[tab][article][id];//.slice();
    var item=g_saved_basket_detail[tab][article];
    //var items=g_all_items[tab][article];
    var table='<table style="width: 350px; padding: 10px;">';
    if(id==-1){
      table+='<tr style="padding: 10px;"><td colspan="4">Бренд: <input type="text" value="'+item['brand']+'" class="form-control">Артикул: <input class="form-control" type="text" value="'+item['article']+'"></td></tr>';
      table+='<tr><tr><td colspan="4">Наименование: <input type="text" value="'+item['name']+'" class="form-control" onchange="group_change_cart_detail_name('+id+','+tab+',\''+article+'\',\''+brand+'\',this.value)"></td></tr>';
    }else {
      table+='<tr style="padding: 10px;"><td style="width: 230px;">'+item['brand']+' <a href="">'+item['article']+'</a></td><td></td></tr>';
      table+='<tr><tr><td>'+item['name']+'</td><td></td></tr>';
    }
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    if(id==-1){ 
      table+='<tr><tr><th>Количество</th><th>Цена покупки</th><th>Цена продажи</th><th>Сумма</th></tr>';
    }
    else {
      table+='<tr><tr><th>Количество</th><th>Цена продажи</th><th>Сумма</th></tr>';
    }
    if(typeof(item['multiplicity'])!="undefined" && item['multiplicity']>1)
      item['to_cart_count']=item['multiplicity'];
    else
      item['to_cart_count']=1;
    if(typeof(item['mcount'])!="undefined" && item['mcount']>1)
        item['to_cart_count']=item['mcount'];
    item['cost_sum']=item['cost'];
    /*table+='<tr><tr><td>\
            <div class="input-group">\
              <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="group_decrease_cart_count('+id+','+tab+',\''+article+'\',\''+brand+'\')">-</button></span> \
              <input type="text" class="form-control" value="'+item['to_cart_count']+'" style="width:58px; text-align:center;" id="cart_count" onchange="change_cart_count('+id+','+tab+');"> \
              <span class="input-group-btn"><button class="btn btn-default" type="button"  onclick="group_increase_cart_count('+id+','+tab+',\''+article+'\',\''+brand+'\')">+</button></span>\
            </div>\
            </td>\
            <td><b><span id="cart_count_price">'+item['cost_sum']+'</span> руб.</b></td></tr>'; */
    table+='<tr><td>\
            <div class="input-group">\
              <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="group_decrease_cart_count('+id+','+tab+',\''+article+'\',\''+brand+'\')">-</button></span> \
              <input type="text" class="form-control" value="'+item['to_cart_count']+'" style="width:58px; text-align:center;" id="cart_count" onchange="change_cart_count('+id+','+tab+');"> \
              <span class="input-group-btn"><button class="btn btn-default" type="button"  onclick="group_increase_cart_count('+id+','+tab+',\''+article+'\',\''+brand+'\')">+</button></span>\
            </div>\
            </td>';
    if(id==-1){
      table+='<td>\
            <div style="display: flex; justify-content: center; align-items: center; gap: 5px; ">\
              <b><input style="text-align: center; width:100px" class="form-control" onchange="group_change_cart_dealer_price('+id+','+tab+',\''+article+'\',\''+brand+'\');" id="cart_count_dealer_price" value='+((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)?item['price']:"???")+' '+(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.show==0?'disabled':'')+'></b>\
            </div>\
            </td>';
    }
    table+='<td>\
            <div style="display: flex; justify-content: center; align-items: center; gap: 5px; ">\
              <b><input style="text-align: center; width:100px" class="form-control" onchange="group_change_cart_price('+id+','+tab+',\''+article+'\',\''+brand+'\');" id="cart_count_price" value='+((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)?item['cost']:"???")+' '+(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.show==0?'disabled':'')+'></b>\
            </div>\
            </td>\
            <td>\
            <div style="display: flex; justify-content: center; align-items: center; gap: 5px; ">\
              <b><input style="text-align: center; width:100px" class="form-control" id="cart_total_cost" value='+((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)?parseFloat(item['cost_sum']).toFixed(2):"???")+' readonly>\
            </div>\
          </td></tr>';
    if(item['count']==0) table+='<tr><tr><td>Под заказ</td><td></td></tr>';
    else table+='<tr><tr><td>в наличии '+item['count']+' шт.</td><td></td></tr>';
    table+='<tr><td>&nbsp</td><td></td><td></td></tr>';
    table+='<tr><td><b>Комментарий к заказу</b></td><td></td></tr>';
    table+='<tr><td colspan="4"><input type="text" id="cart_comment" name="cart_comment" class="form-control"></td></tr>';
    table+='<tr><td>&nbsp</td><td></td><td></td></tr>';
    table+='<tr><td><b>Срок поставки</b></td><td></td><td></td></tr>';
    table+='<tr><td>'+item['time']+' д.</td><td></td><td></td></tr>';
    table+='<tr><td>&nbsp</td><td></td><td></td></tr>';
    table+='<tr><td colspan="4"><button onclick="group_save_basket_detail('+id+','+tab+',\''+article+'\',\''+brand+'\')" class="btn btn-sm btn-primary">Добавить</button> <button class="btn btn-sm btn-default pull-right" onclick="close_window(\'to_cart_div\')">Отменить</button></td></tr>';
    table+='</table>';
    create_window_centered_blue("to_cart_div","Добавление в корзину",'select_brands_'+tab,table);
}

function group_decrease_cart_count(id,tab,article,brand){
    var item=g_saved_basket_detail[tab][article];
    if(typeof(item['mcount'])!="undefined" && parseInt(item['mcount'])>=item['to_cart_count']){
      return 0;
    }
    if(item['to_cart_count']>1) {
    	if(typeof(item['multiplicity'])!="undefined" && item['multiplicity']>1){
        if((item['to_cart_count']-item['multiplicity'])>0)
          item['to_cart_count']=item['to_cart_count']-item['multiplicity'];
      }
      else
        item['to_cart_count']--;
    	$("#cart_count").val(item['to_cart_count']);
    	$("#cart_count_price").html((item['cost']*item['to_cart_count']).toFixed(2));
      if((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0))
    	  $("#cart_total_cost").val((item['cost']*item['to_cart_count']).toFixed(2));
    }
}

function group_increase_cart_count(id,tab,article,brand){
    var item=g_saved_basket_detail[tab][article];
    if(item['to_cart_count']<item['count']){
      if(typeof(item['multiplicity'])!="undefined" && item['multiplicity']>1)
        item['to_cart_count']=parseInt(item['to_cart_count'])+parseInt(item['multiplicity']);
      else
        item['to_cart_count']++;
    	$("#cart_count").val(item['to_cart_count']);
    	$("#cart_count_price").html((item['cost']*item['to_cart_count']).toFixed(2));
    }
    if(item['count']==0) //под заказ можем заказать сколько угодно
      item['to_cart_count']++;
      $("#cart_count").val(item['to_cart_count']);
    	$("#cart_count_price").html((item['cost']*item['to_cart_count']).toFixed(2));
      if((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0))
    	  $("#cart_total_cost").val((item['cost']*item['to_cart_count']).toFixed(2));
}

function group_change_cart_count(id,tab,article,brand){
    var item=g_saved_basket_detail[tab][article];
    if($("#cart_count").val()<=item['count'] && $("#cart_count").val()>1){
    	item['to_cart_count']=$("#cart_count").val();
    	$("#cart_count").val(item['to_cart_count']);
    	$("#cart_count_price").html((item['cost']*item['to_cart_count']).toFixed(2));
    }
    else {
    	if($("#cart_count").val()>1) bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "Нельзя указать количество больше, чем есть в наличии"});
    	$("#cart_count").val(item['to_cart_count']);
    }
}

function group_change_cart_price(id,tab,article,brand){
  if(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.write==0){
      $("#cart_count_price").val(g_saved_basket_detail[tab][article]['cost']);
      bootbox.alert("У Вас нет прав для изменения цены");
      return 0;    
  }
  if(parseFloat($("#cart_count_price").val())>parseFloat(g_saved_basket_detail[tab][article]['price']) && parseFloat($("#cart_count_price").val())>1){
    g_saved_basket_detail[tab][article]['cost']=$("#cart_count_price").val();
    //$("#cart_total_cost").val(saved_basket_detail[tab]['cost_sun']);
    $("#cart_total_cost").val((g_saved_basket_detail[tab][article]['cost']*g_saved_basket_detail[tab][article]['to_cart_count']).toFixed(2));
  }
  else {
    if($("#cart_count_price").val()>1) bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "Нельзя указать цену меньше чем та, по которой вы покупаете. Указана цена покупки от поставщика"});
      //$("#cart_price_"+id).val(basket_details[id]['dealer_price']);
      g_saved_basket_detail[tab][article]['cost']=g_saved_basket_detail[tab][article]['real_cost'];
  }
}

function group_change_cart_dealer_price(id,tab,article,brand){
  if(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.write==0){
      $("#cart_count_dealer_price").val(g_saved_basket_detail[tab][article]['price']);
      bootbox.alert("У Вас нет прав для изменения цены");
      return 0;    
  }
    g_saved_basket_detail[tab][article]['price']=$("#cart_count_dealer_price").val();
    //$("#cart_total_cost").val(saved_basket_detail[tab]['cost_sun']);
}

function group_change_cart_detail_name(id,tab,article,brand,name){
  g_saved_basket_detail[tab][article]['name']=name;
}

function group_save_basket_detail(id,tab,article,brand){
    var item=g_saved_basket_detail[tab][article];
    item['comment']=$("#cart_comment").val();
    api_query_array("/api/index.php",item,"save_basket_detail").then(function(data){
      	if(data.status=="ok") $("#select_brands_"+tab).html("");
        get_basket_count();
    });
}

var g_all_items = new Array();
var g_filtered_items = new Array();
var g_endsearch=new Array();
var g_stop_search=new Array();
var g_plugins_started = new Array();
var g_plugin_statuses = new Array();
//var i=0;

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function group_sort_items(s,tab,article,brand){
//      items.sort();
g_all_items[tab][article]["sort_field"]=s;
g_all_items[tab][article]["sort_direction"]="up";
    var items=g_all_items[tab][article];
    items.sort(function(a, b) {
        if (s=="article") { if(a.article == b.article) return 0; else { if(a.article > b.article) return 1; else if(a.article < b.article) return -1; }}
        if (s=="brand") { if(a.brand == b.brand) return 0; else { if(a.brand > b.brand) return 1; else if(a.brand < b.brand) return -1; }}
        if (s=="name") { if(a.name == b.name) return 0; else { if(a.name > b.name) return 1; else if(a.name < b.name) return -1; }}
        if (s=="cost") { return a.cost-b.cost; }
        if (s=="count") { return a.count-b.count; }
        if (s=="time") { if(a.time == b.time) return 0; else { if(a.time > b.time) return 1; else if(a.time < b.time) return -1; }}
        if (s=="city_name") { if(a.city_name == b.city_name) return 0; else { if(a.city_name > b.city_name) return 1; else if(a.city_name < b.city_name) return -1; }}
        if (s=="stock") { if(a.stock == b.stock) return 0; else { if(a.stock > b.stock) return 1; else if(a.stock < b.stock) return -1; }}
        if (s=="deliverer") { if(a.deliverer == b.deliverer) return 0; else { if(a.deliverer > b.deliverer) return 1; else if(a.deliverer < b.deliverer) return -1; }}
    });
    //alert(items.join('\n'));
    g_items_to_table(tab,article,brand);

}

function group_filter_1(tab, i,article,brand){
  if(typeof(g_filter[tab][article]['filter_count'])=="undefined" || g_filter[tab][article]['filter_count']==0) return 1;
  var item=g_all_items[tab][article][i];
  var flag=new Array();
  var ret=0,filter_text_ret=0;
  if(item["article"]==null) item["article"]="";
  if(item["name"]==null) item["name"]="";
  if(item["brand"]==null) item["brand"]="";
  if(item["article"].search(RegExp(g_filter[tab][article]['filter_text'],"i")) != -1 || item["brand"].search(RegExp(g_filter[tab][article]['filter_text'],"i")) != -1 || item["name"].search(RegExp(g_filter[tab][article]['filter_text'],"i")) != -1 )
    filter_text_ret=1;
  if(g_filter[tab][article]['filter_text']=="") filter_text_ret=1;
  for(let field in g_filter[tab][article]){
    if(g_filter[tab][article]['filter_counter'][field]==0) continue;
    flag[field]=new Array();
    flag[field]['valid']=0;
    flag[field]['active_filter_count']=0;
    for(let key in g_filter[tab][article][field]){
      if((field=='count') || (field=='time')){
        if(g_filter[tab][article][field][key]>0){
            flag[field]['active_filter_count']++;
            if(field=='count'){
              if(key<=item[field]) {
                  flag[field]['valid']++;
                  break;
              }
            }
            if(field=='time'){
              if(key>=item[field]) {
                  flag[field]['valid']++;
                  break;
              }
            }
        }
      }
      else {
          if(g_filter[tab][article][field][key]['check']>0){
              flag[field]['active_filter_count']++;
              if(key==clear_word(item[field])) {
                  flag[field]['valid']++;
                  break;
              }
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

function group_sort_filter(field_name,tab,article,brand){
//      items.sort();
  var items=g_filter[tab][article][field_name];
  g_filter[tab][article][field_name]={};
  Object.keys(items).sort().forEach(function(key){
    g_filter[tab][article][field_name][key]=items[key];
  });
}

function group_print_filter(tab, field_name,article,brand) {
  if(typeof(brand)=="undefined") brand="";
  var table='<div><button class="btn btn-primary" onclick="g_items_to_table('+tab+',\''+article+'\',\''+brand+'\');">Применить</button>';
  table+='<button class="btn btn-default pull-right" onclick="group_clear_filter_by_name('+tab+',\''+field_name+'\',\''+article+'\',\''+brand+'\');">Очистить</button></div>';
  table+='<div class="search_filter_div"><div class = "filter_header">';
  table+='';
  table+='</div><table class="table">';
  // g_filter[tab][article][field_name].forEach(function(val,key){
  //  table+='<tr><td><input type="checkbox"></td><td>'+key+'</td></tr>'
//  });
  group_sort_filter(field_name,tab,article,brand);
  for(var key in g_filter[tab][article][field_name]) {
    if (key.length != 0)  {
      table+='<tr><td><input type="checkbox" onclick="group_set_filter('+tab+',\''+field_name+'\',\''+btoa(toBinary(key))+'\',\''+btoa(toBinary(article))+'\',\''+btoa(toBinary(brand))+'\');"';
      if (typeof(g_filter[tab][article][field_name][key])== "number" && g_filter[tab][article][field_name][key]==1)
        table+=' checked="checked"';
      if (g_filter[tab][article][field_name][key]['check']) {
        table+=' checked="checked"';
      }
      if (typeof(g_filter[tab][article][field_name][key])== "number" ) {
        table+='></td><td>'+key+'</td></tr>';
      }
      else {
        table+='></td><td>'+g_filter[tab][article][field_name][key]['print']+'</td></tr>';
      }
    }
  }
  table += "</table></div>";
  create_window("filter_div_"+tab+article+field_name,"Выберите элементы фильтра",'select_filter_'+tab+article+field_name,table);
  //sort_filter(field_name,tab);
}

function group_set_filter(tab, field_name, key,article,brand) {
    key=fromBinary(atob(key));
    article=fromBinary(atob(article));
    brand=fromBinary(atob(brand));
    if(typeof(g_filter[tab][article]['filter_count'])=="undefined") g_filter[tab][article]['filter_count']=0;
    if(typeof(g_filter[tab][article]['filter_counter'])=="undefined") g_filter[tab][article]['filter_counter']={};
    if(typeof(g_filter[tab][article]['filter_counter'][field_name])=="undefined") g_filter[tab][article]['filter_counter'][field_name]=0;
    if(typeof(g_filter[tab][article][field_name][key])=="undefined") {
      if(field_name=="count" || field_name=="time") g_filter[tab][article][field_name][key]=0;
      else g_filter[tab][article][field_name][key]=new Array();
    }
    if(typeof(g_filter[tab][article][field_name][key])=="number"){
      if (g_filter[tab][article][field_name][key]){
        g_filter[tab][article][field_name][key] = 0;
        g_filter[tab][article]['filter_counter'][field_name]--;
        g_filter[tab][article]['filter_count']--;
      }
      else {
        g_filter[tab][article][field_name][key] = 1;
        g_filter[tab][article]['filter_counter'][field_name]++;
        g_filter[tab][article]['filter_count']++;

      }
    }
    else {
      if (g_filter[tab][article][field_name][key]['check']){
        g_filter[tab][article][field_name][key]['check'] = 0;
        g_filter[tab][article]['filter_count']--;
        g_filter[tab][article]['filter_counter'][field_name]--;
      }
      else {
        g_filter[tab][article][field_name][key]['check'] = 1;
        g_filter[tab][article]['filter_count']++;
        g_filter[tab][article]['filter_counter'][field_name]++;
      }
    }
    //items_to_table(tab);
    //filter[tab][field_name][key] = (g_filter[tab][article][field_name][key] == 1)?0:1;
}

function add_to_g_items_group(field,tab,article,brand){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Группирую...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  if(workers[tab]['working']==1) {
    bootbox.alert("Обработчик пока занят, попробуйте позже");
    $.unblockUI();
    return 1;
  }
  var len=g_items_group[tab][article].length;
  g_items_group[tab][article][len]=field;
  g_items_to_table(tab,article,brand);
}

function remove_from_g_items_group(field,tab,article,brand){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Убираю группу...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  if(workers[tab]['working']==1) {
    bootbox.alert("Обработчик пока занят, попробуйте позже");
    $.unblockUI();
    return 1;
  }
  var len=g_items_group[tab][article].length;
  g_items_group[tab][article].splice(g_items_group[tab][article].indexOf(field),1);
  //items_group=items_group.map(function(el){ if(Array.isArray(el) || el!=field) return el; else return false;});
  g_items_to_table(tab,article,brand);
}

function g_make_header(field,field_name,tab,article,brand){
  var table='';
  if(typeof(g_filter[tab][article])!="undefined" && typeof(g_filter[tab][article]['filter_counter'])!="undefined" && g_filter[tab][article]['filter_counter'][field] > 0) table+='<th>';
  else table+='<th class="filter-css">';
  if(g_items_group[tab][article].indexOf(field)==-1) table+='<img src="/images/growth.png" title="Группировать по данному полю" onclick="add_to_g_items_group(\''+field+'\','+tab+',\''+article+'\',\''+brand+'\');" style="cursor: pointer;">';
  if(typeof(g_all_items[tab][article]["sort_field"])!="undefined" && g_all_items[tab][article]["sort_field"]==field) {
    table+=""
    if(g_all_items[tab][article]["sort_direction"]=="up") {
      table+="<span><a onclick='group_sort_items_desc(\""+field+"\","+tab+",\""+article+"\",\""+brand+"\");'>"+field_name+" &#9650</a></span>";
      table+="\t";
      if (typeof(g_filter[tab][article][field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="group_print_filter('+tab+',\''+field+'\',\''+article+'\',\''+brand+'\');">';
        table+='<svg class = "filt" viewBox="0 0 80 90" ';
        if(g_filter[tab][article]['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+=' focusable=false style="width: 30px; height:16px;" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div  id="select_filter_'+tab+article+field+'"></div>';
        }
    }
    else {
      table+="<a onclick='group_sort_items(\""+field+"\","+tab+",\""+article+"\",\""+brand+"\");'>"+field_name+" &#9660</a> ";
      table+="\t";
      if (typeof(g_filter[tab][article][field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="group_print_filter('+tab+',\''+field+'\',\''+article+'\',\''+brand+'\');">';
        table+='<svg viewBox="0 0 80 90" ';
        if(g_filter[tab][article]['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+= 'focusable=false style="width: 30px; height:16px;" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div id="select_filter_'+tab+article+field+'"></div>';
      }
    }
  }
  else {
    table+="<a class='clickable' onclick='group_sort_items(\""+field+"\","+tab+",\""+article+"\",\""+brand+"\")'>"+field_name+"";
    table+="\t";
    if (typeof(g_filter[tab][article][field]) != "undefined") {
      table+='<a href="#" class="drop-down-list-function filter-acss" onclick="group_print_filter('+tab+',\''+field+'\',\''+article+'\',\''+brand+'\');">';
      table+='<svg viewBox="0 0 80 90" ';
      if(typeof(g_filter[tab][article]['filter_counter']) != "undefined" && g_filter[tab][article]['filter_counter'][field] > 0 && typeof(g_filter[tab][article]['filter_counter'][field]) != "undefined") {
        table+='class="opacity-06"';
      }
      else {
        table+='class="opacity"';
      }
      table+='focusable=false style="width: 30px; height:16px;" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
      table+="</a>";
      table+='<div id="select_filter_'+tab+article+field+'"></div>';
    }
  }

  table+="</th>";
  return table;
}


function group_sort_items_desc(s,tab,article,brand){
//      items.sort();
g_all_items[tab][article]["sort_field"]=s;
g_all_items[tab][article]["sort_direction"]="down";
    var items=g_all_items[tab][article];
    items.sort(function(a, b) {

        if (s=="article") { if(b.article == a.article) return 0; else { if(b.article > a.article) return 1; else if(b.article < a.article) return -1; }}
        if (s=="brand") { if(b.brand == a.brand) return 0; else { if(b.brand > a.brand) return 1; else if(b.brand < a.brand) return -1; }}
        if (s=="name") { if(b.name == a.name) return 0; else { if(b.name > a.name) return 1; else if(b.name < a.name) return -1; }}
        if (s=="cost") { return b.cost-a.cost; }
        if (s=="count") { return b.count-a.count; }
        if (s=="time") { if(b.time == a.time) return 0; else { if(b.time > a.time) return 1; else if(b.time < a.time) return -1; }}
        if (s=="city_name") { if(b.city_name == a.city_name) return 0; else { if(b.city_name > a.city_name) return 1; else if(b.city_name < a.city_name) return -1; }}
        if (s=="stock") { if(b.stock == a.stock) return 0; else { if(b.stock > a.stock) return 1; else if(b.stock < a.stock) return -1; }}
        if (s=="deliverer") { if(b.deliverer == a.deliverer) return 0; else { if(b.deliverer > a.deliverer) return 1; else if(b.deliverer < a.deliverer) return -1; }}
            });
    //alert(items.join('\n'));
    g_items_to_table(tab,article,brand);
}

function group_search(tab,article,brand){
    //g_all_items[tab][article]=[];
    //g_filter[tab][article]=[];
    var items=g_all_items[tab][article];
    var i=(items.length>0)?items.length:0;
    g_endsearch[tab][article]=0;
    var search_str=article;//$("#search_str_"+tab).val();
    if(search_str=="") {
      bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "Поисковая строка не может быть пустой"});
      return 0;
    }
    //$("#search_tab_name_"+tab).html(search_str);
    //$("#request_id_"+tab).val('');
    //$("#search_status_"+tab).html("<img src=\"/images/30.gif\">");
    //$("#search_tab_status_"+tab).html("<img src=\"/new_images/waiting.gif\" style=\"width:10px\">");
    
    var defer=$.Deferred();
    var send=new Array();
    send['article']=article;
    send['brand']=brand;
    send['request_id']=$("#request_id_"+tab+"_"+article+"_"+brand).val();
    if($("#only_stock_search_"+tab).prop("checked")) send['fast_sale']="on";
    if($("#group_price_search_"+tab).prop("checked")) send['search_in_prices']="on";
    api_query_array("/api/index.php",send,"search_by_article").then(function(data){
                if (data.status == "ok") {
                    $.each(data.sklad_details, function (name, val) {
                            items[i]={};//new Array();
                            items[i]["article"]=(val.article == null) ? "" : val.article;
                            items[i]["brand"]=val.brand;
                            items[i]["name"]=val.name;
                            items[i]["cost"]=val.sale_price;
                            items[i]["count"]=(val.count == null)? 0 : parseInt(val.count);
                            items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
                            items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
                  			    items[i]['deliverer']=val.sklad_name;
                  			    items[i]['deliverer_id']=parseInt(val.sklad_id);
                  			    items[i]['deliverer_type']='sklad';
                  			    items[i]['detail_id']=parseInt(val.detail_id);
                  			    items[i]['brand_id']=parseInt(val.brand_id);
                  			    items[i]['city_name']=val.city_name;
                  			    items[i]['city_id']=parseInt(val.city_id);
                  			    items[i]['stock']=val.sklad_name;
                            if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                  			    if(typeof(val.price)!="undefined") items[i]['price']=val.price;
                            if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : val.multiplicity;
                            if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :val.mcount;
                  			    items[i]['chance']=100;
                            i++;
                        });
                    $.each(data.price_details, function (name, val) {
                            items[i]={};//new Array();
                            items[i]["article"]=(val.article == null) ? "" : val.article;
                            items[i]["brand"]=val.brand;
                            items[i]["name"]=val.name;
                            items[i]["cost"]=val.sale_price;
                            items[i]["count"]=(val.count == null)? 0 : parseInt(val.count);
                            items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
                            items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
                  			    items[i]['deliverer']=val.price_list_name;
                  			    items[i]['deliverer_id']=parseInt(val.price_list_id);
                  			    items[i]['deliverer_type']='price_list';
                  			    items[i]['detail_id']=parseInt(val.detail_id);
                  			    items[i]['brand_id']=parseInt(val.brand_id);
                  			    items[i]['city_name']=val.city_name;
                  			    items[i]['city_id']=parseInt(val.city_id);
                  			    items[i]['stock']=val.price_list_name;
                            items[i]['create_date']=val.create_date;
                            items[i]['update_date']=val.update_date;
                            if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                  			    if(typeof(val.price)!="undefined") items[i]['price']=val.price;
                            if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 :val.multiplicity;
                            if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 : val.mcount;
                  			    items[i]['chance']=100;
                            i++;
                        });
                    group_sort_items("cost",tab,article,brand);
                    g_items_to_table(tab,article,brand);                   
		                group_search_sort1(tab,article,brand); 
                    defer.resolve();
                }
                else {
                  group_search_sort1(tab,article,brand);
                  $("#zapchasti_content_"+tab).html("<font color='red'>"+data.msg+"</font>");
                  defer.resolve();
                }
        });
        //.fail(function() {
        //            console.log("Не возможно установить соединение с сервером");
        //});
        return defer.promise();
}

function group_search_sort1(tab,article,brand,time){
  if(typeof(time)=="undefined") time=1;
  if(typeof(brand)=="undefined") brand="";
  var items=g_all_items[tab][article];
  var search_str=article;
  //$("#search_tab_name_"+tab).html(search_str);
  //alert(endsearch);
  if(g_endsearch[tab][article]==0){
     //$("#search_status_"+tab).html("<img src=\"/images/30.gif\">");
     $("#g_s_status_"+tab+"_"+article).html("<img src=\"/new_images/waiting.gif\" style=\"width:10px\">");
  }
  else{
    $("#g_s_status_"+tab+"_"+article).html("");
    //$("#search_tab_status_"+tab).html("");
  }
  var send=new Array();
  send['article']=article;
  send['brand']=brand;
  send['request_id']=$("#request_id_"+tab+"_"+article+"_"+brand).val();
  if($("#only_stock_search_"+tab).prop("checked")) send['fast_sale']="on";
  if($("#group_price_search_"+tab).prop("checked")) send['search_in_prices']="on";
  api_query_array("/api/index.php",send,"search_sort1").then(function(data) {
  //alert(data);
    if(typeof(data.plugins_started)!="undefined"){
      g_plugins_started[tab][article]=new Array();
      g_plugins_started[tab][article]=new Array();
      for(var i=0;i<data.plugins_started.length;i++){
        g_plugins_started[tab][article][i]=data.plugins_started[i];
        g_plugins_started[tab][article][i].loaded=0;
      }
      group_show_plugins(tab,article,brand);
    }
    if(typeof(data.plugin_statuses)!="undefined"){
      if(typeof(g_plugin_statuses[tab])=="undefined") g_plugin_statuses[tab]=new Array();
      $.each(data.plugin_statuses,function(plid,val){
        if(typeof(g_plugin_statuses[tab][article])=="undefined") g_plugin_statuses[tab][article]={};
        if(typeof(g_plugin_statuses[tab][article][plid])=="undefined") g_plugin_statuses[tab][article][plid]={};
        g_plugin_statuses[tab][article][plid].status=data.plugin_statuses[plid].status;
        g_plugin_statuses[tab][article][plid].errors=data.plugin_statuses[plid].errors;
      });
      group_show_plugins(tab,article,brand);
    }
    if(typeof(g_plugins_started[tab][article])=="undefined" && $("#fast_sale_"+tab).prop("checked")==false){
      bootbox.alert('Вы ещё не настроили ни одного онлайн-поставщика. <a onclick="$(\'.bootbox-close-button\').click();load_module(9);">Настроить</a> или <a onclick="$(\'.bootbox-close-button\').click();load_module(12);setTimeout(open_help_doc(),3000);">Посмотреть руководство</a>');
    }
    if(data.reqid!="") $("#request_id_"+tab+"_"+article+"_"+brand).val(data.reqid);
    if(data.searchstatus=="end" || time>40) {
      g_endsearch[tab][article]=1;
      //$("#g_s_status_"+tab+"_"+article).html("");
      $("#g_s_status_"+tab+"_"+article).html("<img src=\"/images/ok.svg\" style=\"width:10px\">");
      //g_all_items[tab][article]
      group_sort_items("cost",tab,article,brand);
      g_items_to_table(tab,article,brand);
    }
    if(data.authorized == "OK"){
        if (data.status == "ok") {
            var i=(items.length>0)?items.length:0;
            var loaded_plugins=new Set();
            $.each(data.items, function (name, val) {
              if(val.article!==null)
              if(val.article.replace(/[\s\.\/_&\-\'\"\(\)\\,!#$=<>\[\]%]/g,"").toUpperCase()==article.replace(/[\s\.\/_&\-\'\"\(\)\\,!#$=<>\[\]%]/g,"").toUpperCase() || $("#analog_group_search_"+tab).prop("checked")){
              //alert(val.art);
              items[i]={};//new Array();
              loaded_plugins.add(val.plid);
              items[i]["article"]=(val.article == null) ? "" : val.article;
              items[i]["brand"]=val.brand;
              items[i]["name"]=val.name;
              if(typeof(val.sale_price)!="undefined") items[i]["cost"]=val.sale_price;
              else items[i]["cost"]=val.cost;
              if(typeof(val.count)!="number") items[i]["count"]=(val.count == null)? 0 : Number(val.count.replace(/\D+/g,""));
              else items[i]["count"]=val.count;
              if(typeof(val.time)!="number") items[i]["time"]=(val.time == null) ? 0 : Number(val.time.replace(/\D+/g,""));
              else items[i]["time"]=val.time;
              items[i]['deliverer_type']='sort1';
              items[i]['deliverer']=val.pl_name;
              items[i]['deliverer_id']=val.plid;
              items[i]['sort1_id']=val.id;
              items[i]['sort1_sreqid']=data.reqid;
              items[i]['deliverer_online_profile_id']=val.deliverer_online_profile_id;
              items[i]['city_name']=(val.city_name == null) ? "":val.city_name;
              items[i]['stock']=(val.stock == null) ? "" : val.stock;
              items[i]['chance']=(val.chance == null) ? 0 : val.chance;
              if(typeof(val.cost)!="undefined") items[i]['price']=val.cost;
              if(typeof(val.img)!="undefined" && val.img!="") items[i]['img']=val.img;
              if(typeof(val.detail_url)!="undefined" && val.detail_url!="") items[i]['detail_url']=val.detail_url;
              if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :parseInt(val.mcount);
              if(typeof(val.additional)!="undefined") items[i]['additional']=val.additional;
              if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : parseInt(val.multiplicity);
              if(typeof(val.return)!="undefined") items[i]['return']=val.return;
              if( (typeof(items['cost'])=="undefined" || parseFloat(items['cost'])>parseFloat(val.cost)) && parseFloat(val.count)>0 ){
                $.each(val,function(item_key,item_val){
                  items[item_key]=item_val;
                });
              }
              i++;
            }
          });
          if  (data.items.length>0) {
            for(var k=0;k<g_plugins_started[tab][article].length;k++){
              if(loaded_plugins.has(g_plugins_started[tab][article][k].plugin_id)) g_plugins_started[tab][article][k].loaded=1;
            }
            group_show_plugins(tab,article,brand);
            group_sort_items("cost",tab,article,brand);
            g_items_to_table(tab,article,brand);
          }
          if(g_endsearch[tab][article]==0)
             setTimeout(group_search_sort1,3000,tab,article,brand,++time);
          //else
            // $("#search_status_"+tab).html("");
      }
      else {
            //$("#zapchasti_content").html("<font color=\'red\'>"+data.msg+"</font>");
            //$("#search_status_"+tab).html("");
            $("#g_s_status_"+tab+"_"+article+"_"+brand).html("");
            g_endsearch[tab][article]=1;
            g_items_to_table(tab,article,brand);
      }
    }
    else {
      $("#zapchasti_content_"+tab+"_"+article).html("<font color=\'red\'>" + data.authorized + "</font>");
      //$("#search_status_"+tab).html("");
      $("#g_s_status_"+tab+"_"+article+"_"+brand).html("");
      g_endsearch[tab][article]=1;
      g_items_to_table(tab,article,brand);
    }
  });
  //.fail(function() {
    //  	console.log("Не возможно установить соединение с сервером");
      //  $("#search_status_"+tab).html("");
       // endsearch=1;
    //});

}

function group_show_plugins(tab,article,brand){
  var table="";
  for(var i=0; i<g_plugins_started[tab][article].length; i++){
    //table+='<div id="plugins_started_'+tab+'_'+plugins_started[tab][i].plugin_id+'">';
    table+=' <a onclick="g_select_plugin_on_tab('+tab+',\''+article+'\',\''+brand+'\','+i+');"><img src="'+g_plugins_started[tab][article][i].icon+'" id="plugins_started_'+tab+'_'+article+'_'+g_plugins_started[tab][article][i].plugin_id+'"';
    if(typeof(g_plugin_statuses[tab])!="undefined" && typeof(g_plugin_statuses[tab][article])!="undefined" && typeof(g_plugin_statuses[tab][article][g_plugins_started[tab][article][i].plugin_id])!="undefined" && parseInt(g_plugin_statuses[tab][article][g_plugins_started[tab][article][i].plugin_id].status)==4){
      table+=' style="border: 3px double red;" title="'+g_plugins_started[tab][article][i].name+' '+g_plugin_statuses[tab][article][g_plugins_started[tab][article][i].plugin_id].errors+'"';
    }
    else {
      if(g_plugins_started[tab][article][i].loaded==0) {
        if(typeof(g_plugin_statuses[tab])!="undefined" && typeof(g_plugin_statuses[tab][article])!="undefined" && typeof(g_plugin_statuses[tab][article][g_plugins_started[tab][article][i].plugin_id])!="undefined" && parseInt(g_plugin_statuses[tab][article][g_plugins_started[tab][article][i].plugin_id].status)==5)
          table+=' style="opacity: 50%; width:16px; border-bottom: 2px solid green;"';
        else table+=' style="opacity: 20%; width:16px;"';
      }
      else {
        table+='style="width:16px;';
        if(typeof(g_plugin_statuses[tab])!="undefined" && typeof(g_plugin_statuses[tab][article])!="undefined" && typeof(g_plugin_statuses[tab][article][g_plugins_started[tab][article][i].plugin_id])!="undefined" && parseInt(g_plugin_statuses[tab][article][g_plugins_started[tab][article][i].plugin_id].status)==5)
          table+='border-bottom: 3px solid green;';
        table+='"';
      }
      table+=' title="'+g_plugins_started[tab][article][i].name+'"';
    }
    table+='></a>';
    //table+='</div>';
  }
  $("#plugins_started_"+tab+"_"+article).html(table);
}

function group_clear_search_text(input_id,tab,article,brand){
  $('#'+input_id).val('');
  group_runTextFilter(tab,article,brand);
}

function group_clear_search_form(form_id,article,brand){
  $("#"+form_id+" [name=brand]").val('');
  $("#"+form_id+" [name=brand_id]").val('');
  $("#"+form_id+" [name=detail_id]").val('');
}

function group_select_brands(tab){
    clear_search_form("search_form_"+tab);
    api_query("/api/index.php","search_form_"+tab,"get_brands_online").then(function(data){
    	var brands=new Array();
    	var table="<table class='table table-hover'><thead><th>артикул</th><th>наименование</th><th>брэнд</th></thead><tbody>";
    	var brands_count=data.brands.length;
    	$.each(data.brands, function(key,val){
    	    table += '<tr style="cursor:pointer" onclick="set_brand('+val.brand_id+','+val.detail_id+',\''+val.brand+'\','+tab+'); $(\'#select_brands_'+tab+'\').html(\'\'); search('+tab+');"><td>'+val.article+'</td><td>'+val.name+'</td><td><b>'+val.brand+'</b></td></tr>';
    	});
    	table += '</tbody></table>';
    	if(brands_count>0){
    	    if(brands_count>1)
    		    create_window_gray("select_brands_"+tab+"_div","Выберите брэнд",'select_brands_'+tab,table);
    	    else {
        		$("#search_form_"+tab+" [id=brand_id]").val(data.brands[0].brand_id);
        		$("#search_form_"+tab+" [id=brand]").val(data.brands[0].brand);
        		$("#search_form_"+tab+" [id=detail_id]").val(data.brands[0].detail_id);
        		search(tab);
    	    }
    	}
    	else {
    	    $("#search_form_"+tab+" [id=brand_id]").val('');
    	    $("#search_form_"+tab+" [id=brand]").val('');
    	    $("#search_form_"+tab+" [id=detail_id]").val('');
    	    search(tab);
    	}
    });

}

var g_keyTimer;
var group_lists=new Array();
function group_get_filter_text(tab,article,brand){
//    var city_name=$("#city_name").val();
    clearTimeout(g_keyTimer);
    g_keyTimer = setTimeout(group_runTextFilter, 1000, tab,article,brand);
}

function group_runTextFilter(tab,article,brand){
    if(typeof(g_all_items[tab][article])!="undefined" && g_all_items[tab][article].length>0){
      //var filter_text=$("#filter_text_"+tab).val();
      //if (filter_text!='' && filter_text.length>1){
      if(typeof(g_filter[tab][article]['filter_count'])=="undefined") g_filter[tab][article]['filter_count']=0;
      g_filter[tab][article]['filter_text']=$("#g_filter_text_"+tab+"_"+article).val();
      if(g_filter[tab][article]['filter_text']!="") g_filter[tab][article]['filter_count']++;
      else g_filter[tab][article]['filter_count']--;
      g_items_to_table(tab,article,brand);
      //}
    }
}

function group_clear_filter(tab,article,brand) {
  if(typeof(g_filter[tab][article])!="undefined") {
    $("body").css("cursor", "progress");
    if(typeof(g_filter[tab][article]['filter_text'])=="undefined" || g_filter[tab][article]['filter_text']=="")
      g_filter[tab][article]['filter_count']=0;
    Object.keys(g_filter[tab][article]).forEach(function(field){
      if(typeof(g_filter[tab][article]['filter_counter'])=="undefined") g_filter[tab][article]['filter_counter']={};
      g_filter[tab][article]['filter_counter'][field]=0;
      if(field!="filter_counter"){
        Object.keys(g_filter[tab][article][field]).forEach(function(filter_key){
          if(field=="count" || field=="time") {
            if(g_filter[tab][article][field][filter_key]==1) {
              g_filter[tab][article][field][filter_key]=0;
            }
          }
          else
            if(g_filter[tab][article][field][filter_key]['check']==1) {
              g_filter[tab][article][field][filter_key]['check']=0;
            }
        });
    }
    });
    g_items_to_table(tab,article,brand);
    $("body").css("cursor", "default");
  }
}

function group_clear_filter_by_name(tab,field,article,brand,print) {
  if(typeof(g_filter[tab][article])!="undefined") {
    $("body").css("cursor", "progress");
    //if(g_filter[tab][article]['filter_text']=="")
    //  g_filter[tab][article]['filter_count']=0;
      if(typeof(g_filter[tab][article]['filter_counter'])=="undefined") g_filter[tab][article]['filter_counter']={};
      g_filter[tab][article]['filter_counter'][field]=0;
      if(field!="filter_counter"){
        Object.keys(g_filter[tab][article][field]).forEach(function(filter_key){
          if(field=="count" || field=="time") {
            if(g_filter[tab][article][field][filter_key]==1) {
              g_filter[tab][article][field][filter_key]=0;
            }
          }
          else
            if(g_filter[tab][article][field][filter_key]['check']==1) {
              g_filter[tab][article][field][filter_key]['check']=0;
            }
        });
    }
    if(typeof(print)=="undefined" || print===1) g_items_to_table(tab,article,brand);
    $("body").css("cursor", "default");
  }
}

function select_fill_sklad(tab){
  api_query("/api/index.php","some_form","get_sklads").then(function(data){
    if(data.status=="ok"){
      var table='<table class="table table-hover"><tbody>';
      var skladslen=data.sklads.length;
      for(var i=0;i<skladslen;i++){
        table+='<tr><td><a onclick="fill_sklad('+data.sklads[i].id+','+tab+');">'+data.sklads[i].name+'</a></td></tr>';
      }
      table+="</tbody></table>";
      create_window("select_fill_sklad_"+tab+"_div","Выберите склад","select_fill_sklad_"+tab,table);
    }
  });
}

function fill_sklad(sklad_id,tab){
  var send=[];
  send['sklad_id']=sklad_id;
  api_query_array("/api/index.php",send,"get_sklad_fill").then(function(data){
    if(data.status=="ok" && data.sklad_fill.length>0){
      $("#select_fill_sklad_"+tab).html('');
      load_groupsearch_list(data.sklad_fill,tab);
    }
    else {
      $("#select_fill_sklad_"+tab).html('');
      bootbox.alert("На этом складе не заполнены минимальные остатки или на складе есть достаточное количество деталей");
    }
  }); 
}

function edit_groupsearch_list(tab){
  if(typeof(group_lists[tab])=="undefined") group_lists[tab]=[];  
  var table='';
  table+='<button type="button" class="btn btn-primary" onclick="start_group_search_manual('+tab+');">Начать</button>\
   <button type="button" class="btn btn-default pull-right" onclick="add_to_group_lists('+tab+');">Добавить</button>';
  table+='<div style="max-height:600px; overflow:auto">'+print_groupsearch_list(tab)+'</div>';
  create_window("edit_groupsearch_list_"+tab+"_div","Редактирование списка гр. поиска","edit_groupsearch_list_"+tab,table);
}

function print_groupsearch_list(tab){
  var table='<table class="table table-hover">';
  table+='<thead><tr><th>№</th>';
  if(typeof(group_lists[tab][0])=="undefined") group_lists[tab][0]={"article":"","name":"","brand":"","kolvo":"","price":""};
  for(let ind in group_lists[tab][0]){
    table+='<th>'+((typeof(ind)!="undefined")?ind:"")+'</th>';
  }
  table+='<th></th></tr></thead><tbody>';
  var len=group_lists[tab].length;
  for(var i=(len-1); i>=0; i--){
    table+='<tr><td>'+(i+1)+'</td>';
    for(let ind in group_lists[tab][0]){
      table+='<td><input type="text" onchange="change_group_lists('+tab+','+i+',\''+ind+'\',this.value);" value="'+((typeof(group_lists[tab][i][ind])!="undefined")?group_lists[tab][i][ind]:"")+'"></td>';
    }
    table+='<td><a onclick="remove_from_group_lists('+tab+','+i+');"><img src="/new_images/garbage.svg" style="width:16px;"></a></td></tr>';
  }
  table+='</tbody></table>';
  return table;
}

function add_to_group_lists(tab){
  group_lists[tab].push({"article":"","name":"","brand":"","kolvo":"","price":""});
  edit_groupsearch_list(tab);
}

function remove_from_group_lists(tab,i){
  group_lists[tab].splice(i,1);
  edit_groupsearch_list(tab);
}

function change_group_lists(tab,i,ind,value){
  if(typeof(group_lists[tab][i])=="undefined") group_lists[tab][i]=[];
  group_lists[tab][i][ind]=clear_word(value);
}

function add_to_groupsearch_list(el,tab){
  if(typeof(tab)=="undefined") tab=0;
  if(typeof(group_lists[tab])=="undefined") group_lists[tab]=[]; 
  var len=group_lists[tab].length;
  if(typeof(el)!="undefined" && typeof(el.article)!="undefined" && el.article!="undefined" && el.article!="" && el.article.length>2) {
    group_lists[tab].push(el);

  }
}

function start_group_search_manual(tab){
  load_groupsearch_list(group_lists[tab],tab);
  $("#edit_groupsearch_list_"+tab).html('');
}

function load_groupsearch_list(group_list,tab,start_search=1){
  if(typeof(tab)=="undefined") tab=0;
  var len=group_list.length;
  var table='<table class="table table-hover" style="max-width: 125px; font-size: 10px;"><tbody>';
  for (var i=0; i<len; i++){
    if(typeof(group_list[i])!="undefined" && typeof(group_list[i].article)!="undefined" && group_list[i].article!="undefined" && group_list[i].article!="" && group_list[i].article.length>2) {
      group_list[i].article=group_list[i].article.replace(/[\s\.\/_&\-\'\"\(\)\\,!#$=<>\[\]%]/g,"").toUpperCase();
      table+='<tr';
      if(i==0) table+=' class="active" ';
      table+=' id="group_search_list_'+tab+'_'+group_list[i].article+'" onclick="show_group_search_res('+tab+',\''+group_list[i].article+'\',\''+group_list[i].brand+'\');"><td>'+(i+1)+'</td><td id="g_s_status_'+tab+'_'+group_list[i].article+'" width="25px"></td><td width="100px;"';
      if(typeof(group_list[i].name)!="undefined" && group_list[i].name!="") table += 'title="'+group_list[i].name+'"';
      table += '>'+group_list[i].article+'</td></tr>';
    }
    else {
      group_list.splice(i,1);
      len=group_list.length;
      i--;
    }
  }
  table+='</tbody></table>';
  $("#zapchasti_list_"+tab).html(table);
  $("#search_header_"+tab).hide();
  $("#search_tab_name_"+tab).html('Групповой поиск');
  $("input.excel_reader_load").val('');
  g_endsearch[tab]=new Array();
  if(start_search){ 
    g_all_items[tab]={};//new Array();
    $("div[id^=zapchasti_group_"+tab+"_]").remove();
    start_group_search(tab,group_list);
  }
}

function start_group_search(tab,group_list,i){
  if(typeof(time)=="undefined") time=1;
  //if(typeof(group_list[i].brand)=="undefined") group_list[i].brand="";
  if(i==group_list.length){
    $("#search_tab_status_"+tab).html('<img src="/images/ok.svg" style="width:10px">');
    $("#stop_group_search_"+tab).hide();
    return 1;
  }
  
  if(typeof(g_all_items[tab])=="undefined") g_all_items[tab] = new Array();
  if(typeof(g_endsearch[tab])=="undefined") g_endsearch[tab]=new Array();
  if(typeof(g_plugins_started[tab])=="undefined") g_plugins_started[tab] = new Array();
  if(typeof(i)=="undefined") {
    i=0;
    g_stop_search[tab]=0;
    $("#stop_group_search_"+tab).show();
    $("#zapchasti_list_"+tab).show();
    $("#zapchasti_content_"+tab).html('');
    group_lists[tab]=group_list;
    if(typeof(group_list[i].brand)=="undefined") group_list[i].brand="";
    if(typeof(group_list[i].name)=="undefined") group_list[i].name="не указано";
    if(typeof(group_list[i].kolvo)=="undefined") group_list[i].kolvo=0;
    /*var gl_length=group_list.length;
    for(var gl_i=0; gl_i<gl_length; gl_i++){
      if(typeof(group_list[i].kolvo)!="undefined" && group_list[gl_i].article==group_list[i].article && gl_i!=i){
        group_list[i].kolvo+=group_list[gl_i].kolvo;
      }
    }*/
    if(group_list[i].kolvo==0 || isNaN(group_list[i].kolvo)) group_list[i].kolvo="не указано";
    if(typeof(group_list[i].price)=="undefined") group_list[i].price="не указана";
    var table="";
    table+="<div id='zapchasti_group_"+tab+"_"+group_list[i].article+"'><div class='row'>";
    table+='<div class="col-sm-2">\
              <div class="input-group input-group-sm" style="padding-left:10px;">\
                <input required type="text" name="g_filter_text" id="g_filter_text_'+tab+'_'+group_list[i].article+'" class="form-control search_str" placeholder="Убрать лишнее" onkeyup="group_get_filter_text('+tab+',\''+group_list[i].article+'\',\''+group_list[i].brand+'\');">\
                <label for="g_filter_text_'+tab+'" id="g_filter_text_label_'+tab+'" onclick="group_clear_search_text(\'g_filter_text_'+tab+'_'+group_list[i].article+'\','+tab+',\''+group_list[i].article+'\',\''+group_list[i].brand+'\');"></label>\
              </div>\
            </div>\
            <div class="col-sm-2"><button class="btn btn-default btn-xs" onclick="skip_group_search(\''+group_list[i].article+'\','+tab+');">Пропустить</button>\
            </div>\
            <div class="col-sm-8">\
              <div id="plugins_started_'+tab+'_'+group_list[i].article+'" class="pull-right"></div>\
            </div>\
          </div>';
    table+="<input type='hidden' id='request_id_"+tab+"_"+group_list[i].article+"_"+group_list[i].brand+"'>";
    table+="<center> Ищем: артикул: <b>"+group_list[i].article+"</b> брэнд: <b>"+group_list[i].brand+"</b> наименование: <b>"+group_list[i].name+"</b> кол-во: <b>"+group_list[i].kolvo+"</b> цена: <b>"+group_list[i].price+"</b> \
    <button onclick='g_to_cart(-1,"+tab+",\""+group_list[i].article+"\",\""+group_list[i].brand+"\");'><img src='/new_images/shopping-cart.svg' style='width: 15px;'></button>\
    <a onclick=\"add_crosses_direct('"+group_list[i].article+"','"+group_list[i].brand+"','"+group_list[i].name+"');\">\
    <img src=\"/new_images/exchange.svg\" style=\"width:16px;\" title=\"добавить в мои кроссы\"></a>\
    </center><br>";
    
    table+="<div id='zapchasti_content_"+tab+"_"+group_list[i].article+"_"+group_list[i].brand+"'></div>";
    table+="</div>";
    if(typeof($("#zapchasti_group_"+tab+"_"+group_list[i].article).html())=='undefined') $("#zapchasti_content_"+tab).append(table);
  }
  if(typeof(group_list[i].article)=="undefined" && group_list[i].article=="undefined"){
    return 1;
  }

  if(i==0 || g_endsearch[tab][group_list[i].article]==1){
    if(typeof(g_endsearch[tab][group_list[i].article])!="undefined" && g_endsearch[tab][group_list[i].article]==1) {
      i++;
      if(i<group_list.length && typeof(group_list[i])!="undefined" && typeof(group_list[i].article)!="undefined"){
        if(typeof(group_list[i].brand)=="undefined") group_list[i].brand="";
        //if(typeof(group_list[i].brand)=="undefined") group_list[i].brand="не указан";
        if(typeof(group_list[i].name)=="undefined") group_list[i].name="не указано";
        if(typeof(group_list[i].kolvo)=="undefined" || isNaN(group_list[i].kolvo)) group_list[i].kolvo="не указано";
        if(typeof(group_list[i].price)=="undefined") group_list[i].price="не указана";
        var table="<div id='zapchasti_group_"+tab+"_"+group_list[i].article+"' style='display:none;'><div class='row'>";
        //table+='<div class="input-group" style="padding-left:10px;">\
        //<input required type="text" name="g_filter_text" id="g_filter_text_'+tab+'_'+group_list[i].article+'" class="form-control search_str" placeholder="Убрать мусор" onkeyup="group_get_filter_text('+tab+',\''+group_list[i].article+'\',\''+group_list[i].brand+'\');">\
        //<label for="g_filter_text_'+tab+'" id="g_filter_text_label_'+tab+'" onclick="group_clear_search_text(\'g_filter_text_'+tab+'_'+group_list[i].article+'\','+tab+',\''+group_list[i].article+'\',\''+group_list[i].brand+'\');"></label>\
        //</div>';
        table+='<div class="col-sm-2">\
                  <div class="input-group input-group-sm" style="padding-left:10px;">\
                    <input required type="text" name="g_filter_text" id="g_filter_text_'+tab+'_'+group_list[i].article+'" class="form-control search_str" placeholder="Убрать мусор" onkeyup="group_get_filter_text('+tab+',\''+group_list[i].article+'\',\''+group_list[i].brand+'\');">\
                    <label for="g_filter_text_'+tab+'" id="g_filter_text_label_'+tab+'" onclick="group_clear_search_text(\'g_filter_text_'+tab+'_'+group_list[i].article+'\','+tab+',\''+group_list[i].article+'\',\''+group_list[i].brand+'\');"></label>\
                  </div>\
                </div>\
                <div class="col-sm-2"><button class="btn btn-default btn-xs" onclick="skip_group_search(\''+group_list[i].article+'\','+tab+');">Пропустить</button></div>\
                <div class="col-sm-8">\
                  <div id="plugins_started_'+tab+'_'+group_list[i].article+'" class="pull-right"></div>\
                </div>\
              </div>';
        table+="<input type='hidden' id='request_id_"+tab+"_"+group_list[i].article+"_"+group_list[i].brand+"'>";
        table+='<center> Ищем: артикул: <b>'+group_list[i].article+"</b> брэнд: <b>"+group_list[i].brand+"</b> наименование: <b>"+group_list[i].name+"</b> кол-во: <b>"+group_list[i].kolvo+"</b> цена: <b>"+group_list[i].price+"</b>\
        <button onclick='g_to_cart(-1,"+tab+",\""+group_list[i].article+"\",\""+group_list[i].brand+"\");'><img src='/new_images/shopping-cart.svg' style='width: 15px;'></button>\
        &nbsp;&nbsp;<a onclick=\"add_crosses_direct('"+group_list[i].article+"','"+group_list[i].brand+"','"+group_list[i].name+"');\">\
          <img src=\"/new_images/exchange.svg\" style=\"width:16px;\" title=\"добавить в мои кроссы\"></a>\
        </center><br>";
        table+="<div id='zapchasti_content_"+tab+"_"+group_list[i].article+"_"+group_list[i].brand+"'></div>";
        table+="</div>";
        if(typeof($("#zapchasti_group_"+tab+"_"+group_list[i].article).html())=='undefined') $("#zapchasti_content_"+tab).append(table);
      } 
    }
    if(i<group_list.length && typeof(g_endsearch[tab][group_list[i].article])=="undefined"){
      //g_all_items[tab][group_list[i].article] = new Array();
      if(typeof(g_all_items[tab][group_list[i].article])=="undefined") {
        g_all_items[tab][group_list[i].article] = new Array();
        g_all_items[tab][group_list[i].article][-1] = [];
      }
      g_all_items[tab][group_list[i].article][-1]['article']=group_list[i].article;
      g_all_items[tab][group_list[i].article][-1]['brand']=group_list[i].brand;
      g_all_items[tab][group_list[i].article][-1]['name']=group_list[i].name;
      g_all_items[tab][group_list[i].article][-1]['count']=group_list[i].kolvo>0?group_list[i].kolvo:0;
      g_all_items[tab][group_list[i].article][-1]['cost']=group_list[i].price>0?group_list[i].price:0;
      g_all_items[tab][group_list[i].article][-1]['price']=group_list[i].price>0?group_list[i].price:0;
      g_all_items[tab][group_list[i].article][-1]['deliverer_type']="unknown";
      g_all_items[tab][group_list[i].article][-1]['deliverer_id']=-1;
      g_all_items[tab][group_list[i].article][-1]['deliverer']="Не определенный поставщик";
      g_all_items[tab][group_list[i].article][-1]['chance']=0;
      g_all_items[tab][group_list[i].article][-1]['time']=100;
      g_endsearch[tab][group_list[i].article]=0;
      g_plugins_started[tab][group_list[i].article] = new Array();
      if(typeof(group_list[i].article)!="undefined" && group_list[i].article!="undefined")
        group_search(tab,group_list[i].article,group_list[i].brand,1);
    }
  }
  if(i<=group_list.length)
    if(g_stop_search[tab]==0)
      setTimeout(start_group_search,3000,tab,group_list,i);
}

function show_group_search_res(tab,article,brand){
  $("div[id^=zapchasti_group_"+tab+"]").hide();
  $("div[id^=zapchasti_group_"+tab+"_"+article+"]").show();
  $("tr[id^=group_search_list_"+tab+"]").removeClass("active");
  $("#group_search_list_"+tab+"_"+article).addClass("active");
  group_show_plugins(tab,article,brand);
  g_items_to_table(tab,article,brand);
}

function stop_group_search(tab){
  g_stop_search[tab]=1;
  if(typeof(group_lists[tab])!="undefined"){
    var len=group_lists[tab].length;
    for (var i=0; i<len; i++){
      if(typeof(g_endsearch[tab][group_lists[tab][i].article])=="undefined" || g_endsearch[tab][group_lists[tab][i].article]==0){
        g_endsearch[tab][group_lists[tab][i].article]=2;
        $("#g_s_status_"+tab+"_"+group_lists[tab][i].article).html('<img src="/images/cancel.svg" style="width:10px;" title="Поиск отменен">');
      }
    }
  }
  $("#stop_group_search_"+tab).hide();
  $("#continue_group_search_"+tab).show();
}

function skip_group_search(article,tab){
  //g_stop_search[tab]=1;
  //var len=group_lists[tab].length;
  g_endsearch[tab][article]=1;
  //$("#stop_group_search_"+tab).hide();
  //$("#continue_group_search_"+tab).show();
}

function continue_group_search(tab){
  g_stop_search[tab]=0;
  var len=group_lists[tab].length;
  //var start_index=0;
  for (var i=0; i<len; i++){
    if(g_endsearch[tab][group_lists[tab][i].article]==2){
      if(typeof(start_index)=="undefined") var start_index=i;
      delete g_endsearch[tab][group_lists[tab][i].article];
      $("#g_s_status_"+tab+"_"+group_lists[tab][i].article).html('');
      $("#zapchasti_group_"+tab+"_"+group_lists[tab][i].article).remove();
      $("#request_id_"+tab+"_"+group_lists[tab][i].article+"_"+group_lists[tab][i].brand).val('');
    }
  }
  if(typeof(start_index)!="undefined"){
    if(start_index<1) start_group_search(tab,group_lists[tab]);
    else 
      start_group_search(tab,group_lists[tab],start_index-1);
    $("#stop_group_search_"+tab).show();
    $("#continue_group_search_"+tab).hide();
  }
}

function g_select_plugin_on_tab(tab,article,brand,plugin_index){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Применяем фильтр...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  setTimeout(g_real_select_plugin_on_tab,50,tab,article,brand,plugin_index);
  
  //for(var i in plugins_started[tab]){
   // $('#plugins_started_'+tab+'_'+plugins_started[tab][i].plugin_id).css('border','');
  //}
  //$('#plugins_started_'+tab+'_'+plugins_started[tab][plugin_index].plugin_id).attr('width','25px');
}

function g_real_select_plugin_on_tab(tab,article,brand,plugin_index){
  group_clear_filter_by_name(tab,'deliverer',article,brand,0);
  group_set_filter(tab,'deliverer',btoa(toBinary(clear_word(g_plugins_started[tab][article][plugin_index].name))),btoa(toBinary(article)),btoa(toBinary(brand))); 
  g_items_to_table(tab,article,brand);
  $.unblockUI(); 
}

function get_group_search_xls(tab){
  var to_xls=[];
  var x=0;
  for (var i in g_all_items[tab]){
    var item=g_all_items[tab][i];
    if(typeof(item)!="undefined" && typeof(item['article'])!="undefined" && typeof(item['article'])=="string"){
      item['article']=item['article'].replace(/[\s\.\/_&\-\'\"\(\)\\,!#$=<>\[\]%]/g,"").toUpperCase();
      if(typeof(g_filtered_items[tab][item['article']])!="undefined" && typeof(g_filtered_items[tab][item['article']]['orig'])!="undefined"  && typeof(g_filtered_items[tab][item['article']]['orig'][0])!="undefined" && typeof(g_filtered_items[tab][item['article']]['orig'][0]['item_index'])!="undefined"){
        var orig_item=g_all_items[tab][i][g_filtered_items[tab][item['article']]['orig'][0]['item_index']];
        if(typeof(orig_item)!="undefined"){
          to_xls[x]={
            article:orig_item['article'],
            brand:orig_item['brand'],
            name:orig_item['name'],
            count:orig_item['count'],
            price:orig_item['price'],
            cost:orig_item['cost'],
            time:orig_item['time'],
            pl_name:orig_item['deliverer']
          };
          if(!$("#group_export_xls_with_dealer_price_"+tab).prop("checked")) delete(to_xls[x]['price']);
          to_xls[x]['sklad_count']=0;
          for(var j=0; j<item.length; j++){
            if(item[j].deliverer_type=="sklad" && item[j].article.replace(/[\s\.\/_&\-\'\"\(\)\\,!#$=<>\[\]%]/g,"").toUpperCase()==i.replace(/[\s\.\/_&\-\'\"\(\)\\,!#$=<>\[\]%]/g,"").toUpperCase()){
              to_xls[x]['sklad_count']=item[j].count;
              break;
            }
          }
          //replace(/[\s\.\/_&\-\'\"\(\)\\,!#$=<>\[\]%]/g,"").toUpperCase()
          x++;
        }
      }
    }
  }
  //console.log(to_xls);
  var sheet=XLSX.utils.json_to_sheet(to_xls);
  var book=XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(book, sheet, "Цены");
  XLSX.writeFile(book, "Group_search.xlsx", { compression: true });
}