// + test from nastya


function group_show_item_vals(type,i,tab,article,brand){
  var orig_items=g_all_orig_items[tab][article];
  var analog_items=g_all_analog_items[tab][article];
  var sklad_orig_items=g_all_sklad_orig_items[tab][article];
  var sklad_analog_items=g_all_sklad_analog_items[tab][article];
  var table='<table class="table table-hover">';
  if(type==0){
    $.each(Object.assign({},orig_items[i]),function(item_key,item_val){
      if(typeof(full_detail_info[item_key])!="undefined" && full_detail_info[item_key]['show']==1 && item_val!="") table+='<tr><td>'+full_detail_info[item_key]['descr_rus']+'</td><td>'+item_val+'</td></tr>';
    });
  }
  if(type==1){
    $.each(Object.assign({},analog_items[i]),function(item_key,item_val){
      if(typeof(full_detail_info[item_key])!="undefined" && full_detail_info[item_key]['show']==1 && item_val!="") table+='<tr><td>'+full_detail_info[item_key]['descr_rus']+'</td><td>'+item_val+'</td></tr>';
    });
  }
  if(type==2){
    $.each(Object.assign({},sklad_orig_items[i]),function(item_key,item_val){
      if(typeof(full_detail_info[item_key])!="undefined" && full_detail_info[item_key]['show']==1 && item_val!="") table+='<tr><td>'+full_detail_info[item_key]['descr_rus']+'</td><td>'+item_val+'</td></tr>';
    });
  }
  if(type==3){
    $.each(Object.assign({},sklad_analog_items[i]),function(item_key,item_val){
      if(typeof(full_detail_info[item_key])!="undefined" && full_detail_info[item_key]['show']==1 && item_val!="") table+='<tr><td>'+full_detail_info[item_key]['descr_rus']+'</td><td>'+item_val+'</td></tr>';
    });
  }
  table+='</table>';
  if(type==0) {
    if(typeof(orig_items[i]['img'])!="undefined" && orig_items[i]['img']!="" && orig_items[i]['img']!=null) table+='<img src="'+orig_items[i]['img']+'">';
    //if(typeof(orig_items[i]['detail_url'])!="undefined" && orig_items[i]['detail_url']!="" && orig_items[i]['detail_url']!=null) table+='<a target=_blank href="'+orig_items[i]['detail_url']+'">Дополнительно</a>';
  }
  if(type==1) {
    if(typeof(analog_items[i]['img'])!="undefined" && analog_items[i]['img']!="" && analog_items[i]['img']!=null) table+='<img src="'+analog_items[i]['img']+'">';
    //if(typeof(analog_items[i]['detail_url'])!="undefined" && analog_items[i]['detail_url']!="" && analog_items[i]['detail_url']!=null) table+=' <a target=_blank href="'+analog_items[i]['detail_url']+'">Дополнительно</a>';
  }
  if(type==2) {
    if(typeof(sklad_orig_items[i]['img'])!="undefined" && sklad_orig_items[i]['img']!="" && sklad_orig_items[i]['img']!=null) table+='<img src="'+sklad_orig_items[i]['img']+'">';
    //if(typeof(sklad_orig_items[i]['detail_url'])!="undefined" && sklad_orig_items[i]['detail_url']!="" && sklad_orig_items[i]['detail_url']!=null) table+='<a target=_blank href="'+sklad_orig_items[i]['detail_url']+'">Дополнительно</a>';
  }
  if(type==3) {
    if(typeof(sklad_analog_items[i]['img'])!="undefined" && sklad_analog_items[i]['img']!="" && sklad_analog_items[i]['img']!=null) table+='<img src="'+sklad_analog_items[i]['img']+'">';
    //if(typeof(sklad_analog_items[i]['detail_url'])!="undefined" && sklad_analog_items[i]['detail_url']!="" && sklad_analog_items[i]['detail_url']!=null) table+=' <a target=_blank href="'+sklad_analog_items[i]['detail_url']+'">Дополнительно</a>';
  }
  table+='<p align="center"><button class="btn btn-primary" ';
  if(type==0) table+='onclick="close_window(\'show_orig_item_'+tab+'_'+article+'_'+i+'\')"';
  if(type==1) table+='onclick="close_window(\'show_analog_item_'+tab+'_'+article+'_'+i+'\')"';
  table+='>Ok</button</p>';
  if(type==0) create_window_centered_blue("show_orig_item_"+tab+"_"+i+"_div","Дополнительные данные детали","show_orig_item_"+tab+"_"+article+"_"+i,table);
  if(type==1) create_window_centered_blue("show_analog_item_"+tab+"_"+i+"_div","Дополнительные данные детали","show_analog_item_"+tab+"_"+article+"_"+i,table);
}


var g_all_orig_items=new Array();
var g_all_analog_items=new Array();
var g_filter = new Array();
var g_orig_show_count=new Array();
var g_analog_show_count=new Array();
var g_all_sklad_orig_items=new Array();
var g_all_sklad_analog_items=new Array();
var g_sklad_orig_show_count=new Array();
var g_sklad_analog_show_count=new Array();

function group_items_to_table(tab,article,brand){
    //$("body").css("cursor", "progress");
    if(typeof(brand)=="undefined" || brand=="undefined") brand="";
    if(typeof(g_filter[tab])=="undefined") g_filter[tab]=new Array();
    if(typeof(g_orig_show_count[tab])=="undefined") g_orig_show_count[tab]=new Array();
    if(typeof(g_analog_show_count[tab])=="undefined") g_analog_show_count[tab]=new Array();
    if(typeof(g_analog_show_count[tab])=="undefined") g_analog_show_count[tab]=new Array();
    if(typeof(g_sklad_orig_show_count[tab])=="undefined") g_sklad_orig_show_count[tab]=new Array();
    if(typeof(g_sklad_analog_show_count[tab])=="undefined") g_sklad_analog_show_count[tab]=new Array();

    if(typeof(g_filter[tab][article])=="undefined") g_filter[tab][article]=new Array();
    if(typeof(g_orig_show_count[tab][article])=="undefined") g_orig_show_count[tab][article]=20;
    if(typeof(g_analog_show_count[tab][article])=="undefined") g_analog_show_count[tab][article]=20;
    if(typeof(g_sklad_orig_show_count[tab][article])=="undefined") g_sklad_orig_show_count[tab][article]=20;
    if(typeof(g_sklad_analog_show_count[tab][article])=="undefined") g_sklad_analog_show_count[tab][article]=20;
    var items=g_all_items[tab][article];
    var items_count=items.length;
    var i,orig_i=0,analog_i=0,sklad_orig_i=0,sklad_analog_i=0;
    var search_str=article;
    var orig_items=new Array(),analog_items=new Array(),sklad_orig_items=new Array(),sklad_analog_items=new Array();
    for (i=0; i<items_count; i++){
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
      }
      if(typeof(g_filter[tab][article]['article'])=="undefined"){
          g_filter[tab][article]['article']=new Array();
      }
      if(typeof(g_filter[tab][article]['article'][clear_word(items[i]["article"])])=="undefined"){
        if(items[i]["article"]==null) items[i]["article"]="";
              g_filter[tab][article]['article'][clear_word(items[i]["article"])]=new Array();
              g_filter[tab][article]['article'][clear_word(items[i]["article"])]['check']=0;
              g_filter[tab][article]['article'][clear_word(items[i]["article"])]['print']=items[i]["article"].toUpperCase();
      }

      if(typeof(g_filter[tab][article]['brand'])=="undefined"){
          g_filter[tab][article]['brand']=new Array();
      }
      if(typeof(g_filter[tab][article]['brand'][clear_word(items[i]["brand"])])=="undefined"){
        if(items[i]["brand"]==null) items[i]["brand"]="";
              g_filter[tab][article]['brand'][clear_word(items[i]["brand"])]=new Array();
              g_filter[tab][article]['brand'][clear_word(items[i]["brand"])]['check']=0;
              g_filter[tab][article]['brand'][clear_word(items[i]["brand"])]['print']=items[i]["brand"].toUpperCase();

      }
      if(typeof(g_filter[tab][article]['name'])=="undefined"){
          g_filter[tab][article]['name']=new Array();
      }
      if(typeof(g_filter[tab][article]['name'][clear_word(items[i]["name"])])=="undefined"){
        if(items[i]["name"]==null) items[i]["name"]=""; 
              g_filter[tab][article]['name'][clear_word(items[i]["name"])]=new Array();
              g_filter[tab][article]['name'][clear_word(items[i]["name"])]['check']=0;
              g_filter[tab][article]['name'][clear_word(items[i]["name"])]['print']=items[i]["name"].toUpperCase();

      }

      if(typeof(g_filter[tab][article]['count'])=="undefined"){
          g_filter[tab][article]['count']=new Array();
        }
      if(typeof(g_filter[tab][article]['count'][items[i]["count"]])=="undefined")
          g_filter[tab][article]['count'][items[i]["count"]]=0;

      if(typeof(g_filter[tab][article]['time'])=="undefined"){
          g_filter[tab][article]['time']=new Array();
        }
      if(typeof(g_filter[tab][article]['time'][items[i]["time"]])=="undefined")
          g_filter[tab][article]['time'][items[i]["time"]]=0;

      if(typeof(g_filter[tab][article]['city_name'])=="undefined"){
          g_filter[tab][article]['city_name']=new Array();
        }

      if(typeof(g_filter[tab][article]['city_name'][clear_word(items[i]["city_name"])])=="undefined"){
        if(items[i]["city_name"]==null) items[i]["city_name"]="";
        g_filter[tab][article]['city_name'][clear_word(items[i]["city_name"])]=new Array();
        g_filter[tab][article]['city_name'][clear_word(items[i]["city_name"])]['check']=0;
        g_filter[tab][article]['city_name'][clear_word(items[i]["city_name"])]['print']=items[i]["city_name"].toUpperCase();
      }

      if(typeof(g_filter[tab][article]['stock'])=="undefined"){
        g_filter[tab][article]['stock']=new Array();
      }
      if(typeof(g_filter[tab][article]['stock'][clear_word(items[i]["stock"])])=="undefined"){
        if(items[i]["stock"]==null) items[i]["stock"]="";
        g_filter[tab][article]['stock'][clear_word(items[i]["stock"])]=new Array();
        g_filter[tab][article]['stock'][clear_word(items[i]["stock"])]['check']=0;
        g_filter[tab][article]['stock'][clear_word(items[i]["stock"])]['print']=items[i]["stock"].toUpperCase();
      }


      if(typeof(g_filter[tab][article]['deliverer'])=="undefined"){
        g_filter[tab][article]['deliverer']=new Array();
      }

      if(typeof(g_filter[tab][article]['deliverer'][clear_word(items[i]["deliverer"])])=="undefined"){
        if(items[i]["deliverer"]==null) items[i]["deliverer"]="";
        g_filter[tab][article]['deliverer'][clear_word(items[i]["deliverer"])]=new Array();
        g_filter[tab][article]['deliverer'][clear_word(items[i]["deliverer"])]['check']=0;
        g_filter[tab][article]['deliverer'][clear_word(items[i]["deliverer"])]['print']=items[i]["deliverer"].toUpperCase();
      }
      //  distinct[tab]['brand'][items[i]["brand"]]=items[i]["brand"];
      //  distinct[tab]['article'][items[i]["article"]]=items[i]["article"];
      //  distinct[tab]['article'][items[i]["article"]]=items[i]["article"];
      //  distinct[tab]['article'][items[i]["article"]]=items[i]["article"];

        if (typeof(items[i]["article"])!="undefined" && items[i]["article"].replace(/[\s+\.-]/g,"").toUpperCase()==search_str.replace(/[\s+\.-]/g,"").toUpperCase()) {
            if(typeof(g_filter[tab][article]['filter_count'])!="undefined" && g_filter[tab][article]['filter_count']>0){
              //if(items[i]["article"].search(RegExp(filter_text,"i")) != -1 || items[i]["brand"].search(RegExp(filter_text,"i")) != -1 || items[i]["name"].search(RegExp(filter_text,"i")) != -1 ){
              //if(/filter_text/i.test(items[i]["article"]) || items[i]["brand"].search("/"+filter_text+"/i") != -1 || items[i]["name"].search("/"+filter_text+"/i") != -1 ){
              if(group_filter_1(tab,i,article,brand)){
                if(items[i]['deliverer_type']=="price_list"){
                  sklad_orig_items[sklad_orig_i]=items[i];
    	            sklad_orig_items[sklad_orig_i]['item_index']=i;
                  sklad_orig_i++;
                }
                else {
                  orig_items[orig_i]=items[i];
    	            orig_items[orig_i]['item_index']=i;
                  orig_i++;
                }
              }
            }
            else {
              if(items[i]['deliverer_type']=="price_list"){
                sklad_orig_items[sklad_orig_i]=items[i];
                sklad_orig_items[sklad_orig_i]['item_index']=i;
                sklad_orig_i++;
              }
              else {
                orig_items[orig_i]=items[i];
                orig_items[orig_i]['item_index']=i;
                orig_i++;
              }
            }
        }
        else {
          if(typeof(g_filter[tab][article]['filter_count'])!="undefined" && g_filter[tab][article]['filter_count']>0){
            //if(items[i]["article"].search(RegExp(filter_text,"i")) != -1 || items[i]["brand"].search(RegExp(filter_text,"i")) != -1 || items[i]["name"].search(RegExp(filter_text,"i")) != -1 ){
            if(group_filter_1(tab,i,article,brand)) {
              if(items[i]['deliverer_type']=="price_list"){
                sklad_analog_items[sklad_analog_i]=items[i];
                sklad_analog_items[sklad_analog_i]['item_index']=i;
                sklad_analog_i++;
              }
              else {
                analog_items[analog_i]=items[i];
                analog_items[analog_i]['item_index']=i;
                analog_i++;
              }
            }
          }
          else {
            if(items[i]['deliverer_type']=="price_list"){
              sklad_analog_items[sklad_analog_i]=items[i];
              sklad_analog_items[sklad_analog_i]['item_index']=i;
              sklad_analog_i++;
            }
            else {
              analog_items[analog_i]=items[i];
              analog_items[analog_i]['item_index']=i;
              analog_i++;
            }
          }
        }
    }
    if(typeof(g_all_orig_items[tab])=="undefined") g_all_orig_items[tab]=new Array();
    if(typeof(g_all_analog_items[tab])=="undefined") g_all_analog_items[tab]=new Array();
    if(typeof(g_all_sklad_orig_items[tab])=="undefined") g_all_sklad_orig_items[tab]=new Array();
    if(typeof(g_all_sklad_analog_items[tab])=="undefined") g_all_sklad_analog_items[tab]=new Array();

    g_all_orig_items[tab][article]=orig_items;
    g_all_analog_items[tab][article]=analog_items;
    g_all_sklad_orig_items[tab][article]=sklad_orig_items;
    g_all_sklad_analog_items[tab][article]=sklad_analog_items;
    //if($("#show_price_"+tab).prop("checked")) 
    var show_extended_price=1;
    //else var show_extended_price=0;
    var table="";
    table+="<table class='table table-hover fixtable search-data' style='font-size:11px;'>";
    table+="<div class='clickable'>";
    table+="<thead id='header-fixed'><tr><th></th>";
    table+=group_make_header("article","Артикул",tab,article,brand);
    table+=group_make_header("brand","Брэнд",tab,article,brand);
    table+=group_make_header("name","Наименование",tab,article,brand);
    if(show_extended_price) table+="<th>Цена закупки</th>";
    table+=group_make_header("cost","Цена",tab,article,brand);
    table+=group_make_header("count","Кол-во",tab,article,brand);
    table+=group_make_header("time","Срок пост.",tab,article,brand);
    table+=group_make_header("city_name","Город",tab,article,brand);
    table+=group_make_header("stock","Склад",tab,article,brand);
    table+=group_make_header("deliverer","Поставщик",tab,article,brand);

    table+="<th><img src='/new_images/bar-chart.svg' style='width: 20px;'></th><th></th></tr><thead>";

    if(sklad_orig_items.length>0){
      table+="<tbody><tr><td colspan='13'><span id='sklad_orig_arrow_"+tab+"_"+article+"' class='glyphicon glyphicon-circle-arrow-down' onclick='$(\"#sklad_orig_items_"+tab+"_"+article+"\").toggle(); $(\"#sklad_orig_arrow_"+tab+"_"+article+"\").toggleClass(\"glyphicon-circle-arrow-down glyphicon-circle-arrow-right\");'></span><b> Склад - Запрошенный артикул:</b></td></tr></tbody>";
      table+="<tbody id='sklad_orig_items_"+tab+"_"+article+"' style='overflow: auto;'>";
      if(sklad_orig_items.length<=g_sklad_orig_show_count[tab][article])
        var sklad_orig_items_show_count=sklad_orig_items.length;
      else
        var sklad_orig_items_show_count=g_sklad_orig_show_count[tab][article];
      for (i=0; i<sklad_orig_items_show_count; i++){
        sklad_orig_items[i]["prim"]="";
        if(typeof(sklad_orig_items[i]["additional"])!="undefined" && sklad_orig_items[i]["additional"].length>0) sklad_orig_items[i]["prim"]+="Примечание: "+sklad_orig_items[i]["additional"]+"\n";
          table+="<tr title='"+sklad_orig_items[i]["prim"]+"' ondblclick='group_show_item_vals(0,"+i+","+tab+",\""+article+"\",\""+brand+"\");'><td><div id='group_show_sklad_orig_item_"+tab+"_"+article+"_"+i+"'></div>";
          sklad_orig_items[i]["attention"]="";
          if(typeof(sklad_orig_items[i]["mcount"])!="undefined" && parseInt(sklad_orig_items[i]["mcount"])>1) sklad_orig_items[i]["attention"]+="Минимальное количество: "+sklad_orig_items[i]["mcount"]+"\n";
          if(typeof(sklad_orig_items[i]["multiplicity"])!="undefined" && sklad_orig_items[i]["multiplicity"]>1) sklad_orig_items[i]["attention"]+="Кратность заказа: "+sklad_orig_items[i]["multiplicity"]+"\n";
          if(typeof(sklad_orig_items[i]["return"])!="undefined" && sklad_orig_items[i]["return"]==0) sklad_orig_items[i]["attention"]+="Внимание: Возврат невозможен!!!\n";
          if(sklad_orig_items[i]["attention"].length>0){
  	         if(typeof(sklad_orig_items[i]["return"])!="undefined" && sklad_orig_items[i]["return"]==0) table+='<img src="/images/warning-red.png" width="20px" title="'+sklad_orig_items[i]["attention"]+'">';
  	         else table+='<img src="/images/warning.png" width="20px" title="'+sklad_orig_items[i]["attention"]+'">';
  	      }
          if(typeof(sklad_orig_items[i]['img'])!="undefined" && sklad_orig_items[i]['img']!="" && sklad_orig_items[i]['img']!=null) table+='<img src="/images/image-icon.png" height="20px">';
          table+="</td><td>"+sklad_orig_items[i]["article"]+"</td><td>"+sklad_orig_items[i]["brand"]+"</td><td>"+sklad_orig_items[i]["name"]+"</td>";
  	      if(show_extended_price)
            table+="<td>"+sklad_orig_items[i]['price']+"</td>";
  	      table+="<td><b>"+sklad_orig_items[i]["cost"]+"</b></td><td>"+(sklad_orig_items[i]["count"]>0?sklad_orig_items[i]["count"]:"Под заказ")+"</td><td>"+(sklad_orig_items[i]["time"]>0?(sklad_orig_items[i]["time"]+" д."):"В наличии")+"</td>"
          table+="<td>"+sklad_orig_items[i]["city_name"]+"</td><td>"+sklad_orig_items[i]["stock"]+"</td><td>"+sklad_orig_items[i]["deliverer"]+"</td>";
          table+="<td>";
          if(sklad_orig_items[i]["chance"]>89)
            table+="<div class='progress'><div class='progress-bar progress-bar-success' role='progressbar' aria-valuenow='"+sklad_orig_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+sklad_orig_items[i]["chance"]+"%; height:20px;'><span>"+sklad_orig_items[i]["chance"]+"%</span></div></div>";
          if(sklad_orig_items[i]["chance"]>69 && sklad_orig_items[i]["chance"]<90)
            table+="<div class='progress'><div class='progress-bar progress-bar-warning' role='progressbar' aria-valuenow='"+sklad_orig_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+sklad_orig_items[i]["chance"]+"%; height:20px;'><span>"+sklad_orig_items[i]["chance"]+"%</span></div></div>";
          if(sklad_orig_items[i]["chance"]>0 && sklad_orig_items[i]["chance"]<70)
            table+="<div class='progress'><div class='progress-bar progress-bar-danger' role='progressbar' aria-valuenow='"+sklad_orig_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+sklad_orig_items[i]["chance"]+"%; height:20px;'><span>"+sklad_orig_items[i]["chance"]+"%</span></div></div>";
          table+="</td>";
  	      if(typeof($("#search_form_"+tab+" [name=zakaz_id]").val())!="undefined" && $("#search_form_"+tab+" [name=zakaz_id]").val()!=0){
            if(g_endsearch[tab][article]==0)
              table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            else
              table+="<td><a onclick='to_reorder("+sklad_orig_items[i]['item_index']+","+tab+");'>&#x21C6</a></td>";
          }
          else {
            if(endsearch[tab]==0)
              table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            else
              table+="<td><a onclick='to_cart("+sklad_orig_items[i]['item_index']+","+tab+");'><img src='/new_images/shopping-cart.svg' style='width: 15px;'></a></td>";
          }
          table+="</tr>";
      }
      if(sklad_orig_items_show_count==20 && sklad_orig_items.length>20){
        table+='<tr><td colspan="13">Показаны первые 20 позиций <a onclick="set_sklad_orig_items_show('+tab+','+sklad_orig_items.length+')">показать все</a></td>';
      }
      else {
        if(sklad_orig_items.length>20)
          table+='<tr><td colspan="13"><a onclick="set_sklad_orig_items_show('+tab+',20)">показать 20</a></td>';
      }
      table+="</tbody>";
    }
    if(sklad_analog_items.length>0){
      table+="<tbody><tr><td colspan='13'><span id='sklad_analog_arrow_"+tab+"' class='glyphicon glyphicon-circle-arrow-down' onclick='$(\"#sklad_analog_items_"+tab+"_"+article+"\").toggle(); $(\"#sklad_analog_arrow_"+tab+"_"+article+"\").toggleClass(\"glyphicon-circle-arrow-down glyphicon-circle-arrow-right\");'></span><b> Склад - Аналоги:</b></td></tr></tbody>";
      table+="<tbody id='sklad_analog_items_"+tab+"'>";
      if(sklad_analog_items.length<g_sklad_analog_show_count[tab][article])
        var sklad_analog_items_show_count=sklad_analog_items.length;
      else
        var sklad_analog_items_show_count=g_sklad_analog_show_count[tab][article];
      for (i=0; i<sklad_analog_items_show_count; i++){
          sklad_analog_items[i]["prim"]="";
          if(typeof(sklad_analog_items[i]["additional"])!="undefined" && sklad_analog_items[i]["additional"].length>0) sklad_analog_items[i]["prim"]+="Примечание: "+sklad_analog_items[i]["additional"]+"\n";
          table+="<tr title='"+sklad_analog_items[i]["prim"]+"' ondblclick='group_show_item_vals(1,"+i+","+tab+",\""+article+"\",\""+brand+"\");'><td><div id='show_sklad_analog_item_"+tab+"_"+article+"_"+i+"'></div>";
          sklad_analog_items[i]["attention"]="";
          if(typeof(sklad_analog_items[i]["mcount"])!="undefined" && parseInt(sklad_analog_items[i]["mcount"])>1) sklad_analog_items[i]["attention"]+="Минимальное количество: "+sklad_analog_items[i]["mcount"]+"\n";
          if(typeof(sklad_analog_items[i]["multiplicity"])!="undefined" && sklad_analog_items[i]["multiplicity"]>1) sklad_analog_items[i]["attention"]+="Кратность заказа: "+sklad_analog_items[i]["multiplicity"]+"\n";
          if(typeof(sklad_analog_items[i]["return"])!="undefined" && sklad_analog_items[i]["return"]==0) sklad_analog_items[i]["attention"]+="Внимание: Возврат невозможен!!!\n";
          if(sklad_analog_items[i]["attention"].length>0) {
      	    if(typeof(sklad_analog_items[i]["return"])!="undefined" && sklad_analog_items[i]["return"]==0)  table+='<img src="/images/warning-red.png" width="20px" title="'+sklad_analog_items[i]["attention"]+'">';
      	    else table+='<img src="/images/warning.png" width="20px" title="'+sklad_analog_items[i]["attention"]+'">';
  	      }
          if(typeof(sklad_analog_items[i]['img'])!="undefined" && sklad_analog_items[i]['img']!="" && sklad_analog_items[i]['img']!=null) table+='<img src="/images/image-icon.png" height="20px">';
          table+="</td><td>"+sklad_analog_items[i]["article"]+"</td><td>"+sklad_analog_items[i]["brand"]+"</td><td>"+sklad_analog_items[i]["name"]+"</td>";
        	if(show_extended_price) table+="<td>"+sklad_analog_items[i]['price']+"</td>";
        	table+="<td><b>"+sklad_analog_items[i]["cost"]+"</b></td><td>"+(sklad_analog_items[i]["count"]>0?sklad_analog_items[i]["count"]:"Под заказ")+"</td><td>"+(sklad_analog_items[i]["time"]>0?(sklad_analog_items[i]["time"]+" д."):"В наличии")+"</td>";
          table+="<td>"+sklad_analog_items[i]["city_name"]+"</td><td>"+sklad_analog_items[i]["stock"]+"</td><td>"+sklad_analog_items[i]["deliverer"]+"</td>";
          table+="<td>";
          if(sklad_analog_items[i]["chance"]>89)
            table+="<div class='progress'><div class='progress-bar progress-bar-success' role='progressbar' aria-valuenow='"+sklad_analog_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+sklad_analog_items[i]["chance"]+"%; height:20px;'><span>"+sklad_analog_items[i]["chance"]+"%</span></div></div>";
          if(sklad_analog_items[i]["chance"]>69 && sklad_analog_items[i]["chance"]<90)
            table+="<div class='progress'><div class='progress-bar progress-bar-warning' role='progressbar' aria-valuenow='"+sklad_analog_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+sklad_analog_items[i]["chance"]+"%; height:20px;'><span>"+sklad_analog_items[i]["chance"]+"%</span></div></div>";
          if(sklad_analog_items[i]["chance"]>0 && sklad_analog_items[i]["chance"]<70)
            table+="<div class='progress'><div class='progress-bar progress-bar-danger' role='progressbar' aria-valuenow='"+sklad_analog_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+sklad_analog_items[i]["chance"]+"%; height:20px;'><span>"+sklad_analog_items[i]["chance"]+"%</span></div></div>";
          //if(analog_items[i]["chance"]>0)
            //table+="<div class='progress'><div class='progress-bar' role='progressbar' aria-valuenow='"+analog_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+analog_items[i]["chance"]+"%; height:20px;'><span>"+analog_items[i]["chance"]+"%</span></div></div>";
          table+="</td>";
          if(typeof($("#search_form_"+tab+" [name=zakaz_id]").val())!="undefined" && $("#search_form_"+tab+" [name=zakaz_id]").val()!=0){
            if(g_endsearch[tab][article]==0)
              table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            else
              table+="<td><a onclick='to_reorder("+sklad_analog_items[i]['item_index']+","+tab+");'>&#x21C6</a></td>";
          }
          else {
            if(endsearch[tab]==0)
              table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            else
              table+="<td><a onclick='to_cart("+sklad_analog_items[i]['item_index']+","+tab+");'><img src='/new_images/shopping-cart.svg' style='width: 15px;'></a></td>";
          }
          table+="</tr>";
      }
      if(sklad_analog_items_show_count==20 && sklad_analog_items.length>20){
        table+='<tr><td colspan="13">Показаны первые 20 позиций <a onclick="group_set_sklad_analog_items_show('+tab+','+sklad_analog_items.length+',\''+article+'\',\''+brand+'\')">показать все</a></td>';
      }
      else {
        if(sklad_analog_items.length>20)
          table+='<tr><td colspan="13"><a onclick="group_set_sklad_analog_items_show('+tab+',20,\''+article+'\',\''+brand+'\')">показать 20</a></td>';
      }
      table+="</tbody>";
    }

    if(orig_items.length>0){
      table+="<tbody><tr><td colspan='13'><span id='orig_arrow_"+tab+"_"+article+"' class='glyphicon glyphicon-circle-arrow-down' onclick='$(\"#orig_items_"+tab+"_"+article+"\").toggle(); $(\"#orig_arrow_"+tab+"_"+article+"\").toggleClass(\"glyphicon-circle-arrow-down glyphicon-circle-arrow-right\");'></span> Поставщики - Запрошенный артикул:</td></tr></tbody>";
      table+="<tbody id='orig_items_"+tab+"_"+article+"' style='overflow: auto;'>";
      if(orig_items.length<=g_orig_show_count[tab][article])
        var orig_items_show_count=orig_items.length;
      else
        var orig_items_show_count=g_orig_show_count[tab][article];
      for (i=0; i<orig_items_show_count; i++){
        orig_items[i]["prim"]="";
        if(typeof(orig_items[i]["additional"])!="undefined" && orig_items[i]["additional"].length>0) orig_items[i]["prim"]+="Примечание: "+orig_items[i]["additional"]+"\n";
          table+="<tr title='"+orig_items[i]["prim"]+"' ondblclick='group_show_item_vals(0,"+i+","+tab+",\""+article+"\",\""+brand+"\");'><td><div id='show_orig_item_"+tab+"_"+article+"_"+i+"'></div>";
          orig_items[i]["attention"]="";
          if(typeof(orig_items[i]["mcount"])!="undefined" && parseInt(orig_items[i]["mcount"])>1) orig_items[i]["attention"]+="Минимальное количество: "+orig_items[i]["mcount"]+"\n";
          if(typeof(orig_items[i]["multiplicity"])!="undefined" && orig_items[i]["multiplicity"]>1) orig_items[i]["attention"]+="Кратность заказа: "+orig_items[i]["multiplicity"]+"\n";
          if(typeof(orig_items[i]["return"])!="undefined" && orig_items[i]["return"]==0) orig_items[i]["attention"]+="Внимание: Возврат невозможен!!!\n";
          if(orig_items[i]["attention"].length>0){
  	         if(typeof(orig_items[i]["return"])!="undefined" && orig_items[i]["return"]==0) table+='<img src="/images/warning-red.png" width="20px" title="'+orig_items[i]["attention"]+'">';
  	         else table+='<img src="/images/warning.png" width="20px" title="'+orig_items[i]["attention"]+'">';
  	      }
          if(typeof(orig_items[i]['img'])!="undefined" && orig_items[i]['img']!="" && orig_items[i]['img']!=null) table+='<img src="/images/image-icon.png" height="20px">';
          table+="</td><td>"+orig_items[i]["article"]+"</td><td>"+orig_items[i]["brand"]+"</td><td>"+orig_items[i]["name"]+"</td>";
  	      if(show_extended_price)
            table+="<td>"+orig_items[i]['price']+"</td>";
  	      table+="<td><b>"+orig_items[i]["cost"]+"</b></td><td>"+(orig_items[i]["count"]>0?orig_items[i]["count"]:"Под заказ")+"</td><td>"+(orig_items[i]["time"]>0?(orig_items[i]["time"]+" д."):"В наличии")+"</td>"
          table+="<td>"+orig_items[i]["city_name"]+"</td><td>"+orig_items[i]["stock"]+"</td><td>"+orig_items[i]["deliverer"]+"</td>";
          table+="<td>";
          if(orig_items[i]["chance"]>89)
            table+="<div class='progress'><div class='progress-bar progress-bar-success' role='progressbar' aria-valuenow='"+orig_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+orig_items[i]["chance"]+"%; height:20px;'><span>"+orig_items[i]["chance"]+"%</span></div></div>";
          if(orig_items[i]["chance"]>69 && orig_items[i]["chance"]<90)
            table+="<div class='progress'><div class='progress-bar progress-bar-warning' role='progressbar' aria-valuenow='"+orig_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+orig_items[i]["chance"]+"%; height:20px;'><span>"+orig_items[i]["chance"]+"%</span></div></div>";
          if(orig_items[i]["chance"]>0 && orig_items[i]["chance"]<70)
            table+="<div class='progress'><div class='progress-bar progress-bar-danger' role='progressbar' aria-valuenow='"+orig_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+orig_items[i]["chance"]+"%; height:20px;'><span>"+orig_items[i]["chance"]+"%</span></div></div>";
          table+="</td>";
  	      if(typeof($("#search_form_"+tab+" [name=zakaz_id]").val())!="undefined" && $("#search_form_"+tab+" [name=zakaz_id]").val()!=0){
            if(g_endsearch[tab][article]==0)
              table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            else
              table+="<td><a onclick='to_reorder("+orig_items[i]['item_index']+","+tab+");'>&#x21C6</a></td>";
          }
          else {
            if(g_endsearch[tab][article]==0)
              table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            else
              table+="<td><a onclick='group_to_cart("+orig_items[i]['item_index']+","+tab+",\""+article+"\",\""+brand+"\");'><img src='/new_images/shopping-cart.svg' style='width: 15px;'></a></td>";
          }
          table+="</tr>";
      }
      if(orig_items_show_count==20 && orig_items.length>20){
        table+='<tr><td colspan="13">Показаны первые 20 позиций <a onclick="group_set_orig_items_show('+tab+','+orig_items.length+',\''+article+'\',\''+brand+'\')">показать все</a></td>';
      }
      else {
        if(orig_items.length>20)
          table+='<tr><td colspan="13"><a onclick="group_set_orig_items_show('+tab+',20,\''+article+'\',\''+brand+'\')">показать 20</a></td>';
      }
      table+="</tbody>";
    }
    if(analog_items.length>0){
      table+="<tbody><tr><td colspan='13'><span id='analog_arrow_"+tab+"_"+article+"' class='glyphicon glyphicon-circle-arrow-down' onclick='$(\"#analog_items_"+tab+"_"+article+"\").toggle(); $(\"#analog_arrow_"+tab+"_"+article+"\").toggleClass(\"glyphicon-circle-arrow-down glyphicon-circle-arrow-right\");'></span> Поставщики - Аналоги:</td></tr></tbody>";
      table+="<tbody id='analog_items_"+tab+"_"+article+"'>";
      //table+="<table class='table table-hover fixtable' id='analog_items'>";
      //<thead id='header-fixed'><tr><th onclick='sort_items(\"article\")'>Артикул</th><th onclick='sort_items(\"brand\")'>Брэнд</th><th onclick='sort_items(\"name\")'>Наименование</th><th onclick='sort_items(\"cost\")'>Цена</th><th onclick='sort_items(\"count\")'>Кол-во</th><th onclick='sort_items(\"time\")'>Срок поставки</th><th>Поставщик</th><th></th></tr><thead>";
      //table+="<tr id='tbody-fixsize' style='overflow: auto;'>";
      if(analog_items.length<g_analog_show_count[tab][article])
        var analog_items_show_count=analog_items.length;
      else
        var analog_items_show_count=g_analog_show_count[tab][article];
      for (i=0; i<analog_items_show_count; i++){
          analog_items[i]["prim"]="";
          if(typeof(analog_items[i]["additional"])!="undefined" && analog_items[i]["additional"].length>0) analog_items[i]["prim"]+="Примечание: "+analog_items[i]["additional"]+"\n";
          table+="<tr title='"+analog_items[i]["prim"]+"' ondblclick='group_show_item_vals(1,"+i+","+tab+",\""+article+"\",\""+brand+"\");'><td><div id='show_analog_item_"+tab+"_"+article+"_"+i+"'></div>";
          analog_items[i]["attention"]="";
          if(typeof(analog_items[i]["mcount"])!="undefined" && parseInt(analog_items[i]["mcount"])>1) analog_items[i]["attention"]+="Минимальное количество: "+analog_items[i]["mcount"]+"\n";
          if(typeof(analog_items[i]["multiplicity"])!="undefined" && analog_items[i]["multiplicity"]>1) analog_items[i]["attention"]+="Кратность заказа: "+analog_items[i]["multiplicity"]+"\n";
          if(typeof(analog_items[i]["return"])!="undefined" && analog_items[i]["return"]==0) analog_items[i]["attention"]+="Внимание: Возврат невозможен!!!\n";
          if(analog_items[i]["attention"].length>0) {
      	    if(typeof(analog_items[i]["return"])!="undefined" && analog_items[i]["return"]==0)  table+='<img src="/images/warning-red.png" width="20px" title="'+analog_items[i]["attention"]+'">';
      	    else table+='<img src="/images/warning.png" width="20px" title="'+analog_items[i]["attention"]+'">';
  	      }
          if(typeof(analog_items[i]['img'])!="undefined" && analog_items[i]['img']!="" && analog_items[i]['img']!=null) table+='<img src="/images/image-icon.png" height="20px">';
          table+="</td><td>"+analog_items[i]["article"]+"</td><td>"+analog_items[i]["brand"]+"</td><td>"+analog_items[i]["name"]+"</td>";
        	if(show_extended_price) table+="<td>"+analog_items[i]['price']+"</td>";
        	table+="<td><b>"+analog_items[i]["cost"]+"</b></td><td>"+(analog_items[i]["count"]>0?analog_items[i]["count"]:"Под заказ")+"</td><td>"+(analog_items[i]["time"]>0?(analog_items[i]["time"]+" д."):"В наличии")+"</td>";
          table+="<td>"+analog_items[i]["city_name"]+"</td><td>"+analog_items[i]["stock"]+"</td><td>"+analog_items[i]["deliverer"]+"</td>";
          table+="<td>";
          if(analog_items[i]["chance"]>89)
            table+="<div class='progress'><div class='progress-bar progress-bar-success' role='progressbar' aria-valuenow='"+analog_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+analog_items[i]["chance"]+"%; height:20px;'><span>"+analog_items[i]["chance"]+"%</span></div></div>";
          if(analog_items[i]["chance"]>69 && analog_items[i]["chance"]<90)
            table+="<div class='progress'><div class='progress-bar progress-bar-warning' role='progressbar' aria-valuenow='"+analog_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+analog_items[i]["chance"]+"%; height:20px;'><span>"+analog_items[i]["chance"]+"%</span></div></div>";
          if(analog_items[i]["chance"]>0 && analog_items[i]["chance"]<70)
            table+="<div class='progress'><div class='progress-bar progress-bar-danger' role='progressbar' aria-valuenow='"+analog_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+analog_items[i]["chance"]+"%; height:20px;'><span>"+analog_items[i]["chance"]+"%</span></div></div>";
          //if(analog_items[i]["chance"]>0)
            //table+="<div class='progress'><div class='progress-bar' role='progressbar' aria-valuenow='"+analog_items[i]["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+analog_items[i]["chance"]+"%; height:20px;'><span>"+analog_items[i]["chance"]+"%</span></div></div>";
          table+="</td>";
          if(typeof($("#search_form_"+tab+" [name=zakaz_id]").val())!="undefined" && $("#search_form_"+tab+" [name=zakaz_id]").val()!=0) {
            if(g_endsearch[tab][article]==0)
              table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            else
              table+="<td><a onclick='to_reorder("+analog_items[i]['item_index']+","+tab+");'>&#x21C6</a></td>";
          }
          else {
            if(g_endsearch[tab][article]==0)
              table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            else
              table+="<td><a onclick='group_to_cart("+analog_items[i]['item_index']+","+tab+",\""+article+"\",\""+brand+"\");'><img src='/new_images/shopping-cart.svg' style='width: 15px;'></a></td>";
          }
          table+="</tr>";
      }
      if(analog_items_show_count==20 && analog_items.length>20){
        table+='<tr><td colspan="13">Показаны первые 20 позиций <a onclick="group_set_analog_items_show('+tab+','+analog_items.length+',\''+article+'\',\''+brand+'\')">показать все</a></td>';
      }
      else {
        if(analog_items.length>20)
          table+='<tr><td colspan="13"><a onclick="group_set_analog_items_show('+tab+',20,\''+article+'\',\''+brand+'\')">показать 20</a></td>';
      }
      table+="</tbody>";
    }
    table+="</table>";
    $("#zapchasti_content_"+tab+"_"+article+"_"+brand).css("font-size","12px");
    $("#zapchasti_content_"+tab+"_"+article+"_"+brand).html(table);
    //$("body").css("cursor", "default");
    //resize_table();
    //$("#header-fixed").css("position","fixed");
}

function group_set_orig_items_show(tab,show_count,article,brand){
  g_orig_show_count[tab][article]=show_count;
  group_items_to_table(tab,article,brand);
}

function group_set_analog_items_show(tab,show_count,article,brand){
  g_analog_show_count[tab][article]=show_count;
  group_items_to_table(tab,article,brand);
}

function group_set_sklad_orig_items_show(tab,show_count,article,brand){
  g_sklad_orig_show_count[tab][article]=show_count;
  group_items_to_table(tab,article,brand);
}

function group_set_sklad_analog_items_show(tab,show_count,article,brand){
  g_sklad_analog_show_count[tab][article]=show_count;
  group_items_to_table(tab,article,brand);
}

function group_to_cart(id,tab,article,brand){
    var items=g_all_items[tab][article];
    var table='<table style="width: 350px; padding: 10px;">';
    table+='<tr style="padding: 10px;"><td style="width: 230px;">'+items[id]['brand']+' <a href="">'+items[id]['article']+'</a></td><td></td></tr>';
    table+='<tr><tr><td>'+items[id]['name']+'</td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><b>Количество</b></td><td></td></tr>';
    if(typeof(items[id]['multiplicity'])!="undefined" && items[id]['multiplicity']>1)
      items[id]['to_cart_count']=items[id]['multiplicity'];
    else
      items[id]['to_cart_count']=1;
    if(typeof(items[id]['mcount'])!="undefined" && items[id]['mcount']>1)
        items[id]['to_cart_count']=items[id]['mcount'];
    items[id]['cost_sum']=items[id]['cost'];
    table+='<tr><tr><td>\
            <div class="input-group">\
              <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="group_decrease_cart_count('+id+','+tab+',\''+article+'\',\''+brand+'\')">-</button></span> \
              <input type="text" class="form-control" value="'+items[id]['to_cart_count']+'" style="width:58px; text-align:center;" id="cart_count" onchange="change_cart_count('+id+','+tab+');"> \
              <span class="input-group-btn"><button class="btn btn-default" type="button"  onclick="group_increase_cart_count('+id+','+tab+',\''+article+'\',\''+brand+'\')">+</button></span>\
            </div>\
            </td>\
            <td><b><span id="cart_count_price">'+items[id]['cost_sum']+'</span> руб.</b></td></tr>';
    if(items[id]['count']==0) table+='<tr><tr><td>Под заказ</td><td></td></tr>';
    else table+='<tr><tr><td>в наличии '+items[id]['count']+' шт.</td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><b>Комментарий к заказу</b></td><td></td></tr>';
    table+='<tr><tr><td><input type="text" id="cart_comment" name="cart_comment"></td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><b>Срок поставки</b></td><td></td></tr>';
    table+='<tr><tr><td>'+items[id]['time']+' д.</td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><button onclick="group_save_basket_detail('+id+','+tab+',\''+article+'\',\''+brand+'\')" class="btn btn-default">Добавить</button> <button class="btn btn-default pull-right" onclick="close_window(\'to_cart_div\')">Отменить</button></td><td></td></tr>';
    table+='</table>';
    create_window_centered_blue("to_cart_div","Добавление в корзину",'select_brands_'+tab,table);
}

function group_decrease_cart_count(id,tab,article,brand){
    var items=g_all_items[tab][article];
    if(typeof(items[id]['mcount'])!="undefined" && parseInt(items[id]['mcount'])>=items[id]['to_cart_count']){
      return 0;
    }
    if(items[id]['to_cart_count']>1) {
    	if(typeof(items[id]['multiplicity'])!="undefined" && items[id]['multiplicity']>1){
        if((items[id]['to_cart_count']-items[id]['multiplicity'])>0)
          items[id]['to_cart_count']=items[id]['to_cart_count']-items[id]['multiplicity'];
      }
      else
        items[id]['to_cart_count']--;
    	$("#cart_count").val(items[id]['to_cart_count']);
    	$("#cart_count_price").html((items[id]['cost']*items[id]['to_cart_count']).toFixed(2));
    }
}

function group_increase_cart_count(id,tab,article,brand){
    var items=g_all_items[tab][article];
    if(items[id]['to_cart_count']<items[id]['count']){
      if(typeof(items[id]['multiplicity'])!="undefined" && items[id]['multiplicity']>1)
        items[id]['to_cart_count']=parseInt(items[id]['to_cart_count'])+parseInt(items[id]['multiplicity']);
      else
        items[id]['to_cart_count']++;
    	$("#cart_count").val(items[id]['to_cart_count']);
    	$("#cart_count_price").html((items[id]['cost']*items[id]['to_cart_count']).toFixed(2));
    }
    if(items[id]['count']==0) //под заказ можем заказать сколько угодно
      items[id]['to_cart_count']++;
      $("#cart_count").val(items[id]['to_cart_count']);
    	$("#cart_count_price").html((items[id]['cost']*items[id]['to_cart_count']).toFixed(2));
}

function group_change_cart_count(id,tab,article,brand){
    var items=g_all_items[tab][article];
    if($("#cart_count").val()<=items[id]['count'] && $("#cart_count").val()>1){
    	items[id]['to_cart_count']=$("#cart_count").val();
    	$("#cart_count").val(items[id]['to_cart_count']);
    	$("#cart_count_price").html((items[id]['cost']*items[id]['to_cart_count']).toFixed(2));
    }
    else {
    	if($("#cart_count").val()>1) bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "Нельзя указать количество больше, чем есть в наличии"});
    	$("#cart_count").val(items[id]['to_cart_count']);
    }
}

function group_save_basket_detail(id,tab,article,brand){
    var items=g_all_items[tab][article];
    items[id]['comment']=$("#cart_comment").val();
    api_query_array("/api/index.php",items[id],"save_basket_detail").then(function(data){
      	if(data.status=="ok") $("#select_brands_"+tab).html("");
        get_basket_count();
    });
}

var g_all_items = new Array();
var g_endsearch=new Array();
var g_stop_search=new Array();
var g_plugins_started = new Array();
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
    group_items_to_table(tab,article,brand);

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
  var table='<div><button class="btn btn-primary" onclick="group_items_to_table('+tab+',\''+article+'\',\''+brand+'\');">Применить</button>';
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

function group_make_header(field,field_name,tab,article,brand){
  var table='';
  if(typeof(g_filter[tab][article]['filter_counter'])!="undefined" && g_filter[tab][article]['filter_counter'][field] > 0) table+='<th>';
  else table+='<th class="filter-css">';
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
    group_items_to_table(tab,article,brand);
}

function group_search(tab,article,brand){
    g_all_items[tab][article]=[];
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
    api_query_array("/api/index.php",send,"search_by_article").then(function(data){
                if (data.status == "ok") {
                    $.each(data.sklad_details, function (name, val) {
                            items[i]=new Array();
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
                            items[i]=new Array();
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
                            if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                  			    if(typeof(val.price)!="undefined") items[i]['price']=val.price;
                            if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 :val.multiplicity;
                            if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 : val.mcount;
                  			    items[i]['chance']=100;
                            i++;
                        });
                    group_sort_items("cost",tab,article,brand);
                    group_items_to_table(tab,article,brand);
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
    if(typeof(g_plugins_started[tab][article])=="undefined"){
      bootbox.alert('Вы ещё не настроили ни одного онлайн-поставщика. <a onclick="$(\'.bootbox-close-button\').click();load_module(9);">Настроить</a> или <a onclick="$(\'.bootbox-close-button\').click();load_module(12);setTimeout(open_help_doc(),3000);">Посмотреть руководство</a>');
    }
    if(data.reqid!="") $("#request_id_"+tab+"_"+article+"_"+brand).val(data.reqid);
    if(data.searchstatus=="end" || time>40) {
      g_endsearch[tab][article]=1;
      //$("#g_s_status_"+tab+"_"+article).html("");
      $("#g_s_status_"+tab+"_"+article).html("<img src=\"/images/ok.svg\" style=\"width:10px\">");
      group_items_to_table(tab,article,brand);
    }
    if(data.authorized == "OK"){
        if (data.status == "ok") {
            var i=(items.length>0)?items.length:0;
            var loaded_plugins=new Set();
            $.each(data.items, function (name, val) {
              //alert(val.art);
              items[i]=new Array();
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
              $.each(val,function(item_key,item_val){
                if(typeof(items[item_key])=="undefined") items[item_key]=item_val;
              })
              i++;
          });
          if  (data.items.length>0) {
            for(var k=0;k<g_plugins_started[tab][article].length;k++){
              if(loaded_plugins.has(g_plugins_started[tab][article][k].plugin_id)) g_plugins_started[tab][article][k].loaded=1;
            }
            group_show_plugins(tab,article,brand);
            group_sort_items("cost",tab,article,brand);
            group_items_to_table(tab,article,brand);
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
            group_items_to_table(tab,article,brand);
      }
    }
    else {
      $("#zapchasti_content_"+tab+"_"+article).html("<font color=\'red\'>" + data.authorized + "</font>");
      //$("#search_status_"+tab).html("");
      $("#g_s_status_"+tab+"_"+article+"_"+brand).html("");
      g_endsearch[tab][article]=1;
      group_items_to_table(tab,article,brand);
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
    table+=' <img src="'+g_plugins_started[tab][article][i].icon+'" title="'+g_plugins_started[tab][article][i].name+'" id="plugins_started_'+tab+'_'+article+'_'+g_plugins_started[tab][article][i].plugin_id+'"';
    if(g_plugins_started[tab][article][i].loaded==0) table+=' style="opacity: 20%; width:16px;"';
    else table+='style="width:16px;"';
    table+='>';
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
      group_items_to_table(tab,article,brand);
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
    group_items_to_table(tab,article,brand);
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
    if(typeof(print)=="undefined" || print===1) group_items_to_table(tab,article,brand);
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

function load_groupsearch_list(group_list,tab){
  if(typeof(tab)=="undefined") tab=0;
  var len=group_list.length;
  var table='<table class="table table-hover" style="max-width: 125px; font-size: 10px;"><tbody>';
  for (var i=0; i<len; i++){
    if(typeof(group_list[i])!="undefined" && typeof(group_list[i].article)!="undefined" && group_list[i].article!="undefined" && group_list[i].article!="" && group_list[i].article.length>2) {
      table+='<tr';
      if(i==0) table+=' class="active" ';
      table+=' id="group_search_list_'+tab+'_'+group_list[i].article+'" onclick="show_group_search_res('+tab+',\''+group_list[i].article+'\');"><td>'+(i+1)+'</td><td id="g_s_status_'+tab+'_'+group_list[i].article+'" width="25px"></td><td width="100px;"';
      if(typeof(group_list[i].name)!="undefined" && group_list[i].name!="") table += 'title="'+group_list[i].name+'"';
      table += '>'+group_list[i].article+'</td></tr>';
    }
    else {
      group_list.splice(i,1);
    }
  }
  table+='</tbody></table>';
  $("#zapchasti_list_"+tab).html(table);
  $("#search_header_"+tab).hide();
  $("#search_tab_name_"+tab).html('Групповой поиск');
  $("input.excel_reader_load").val('');
  g_endsearch[tab]=new Array();
  g_all_items[tab]=new Array();
  $("div[id^=zapchasti_group_"+tab+"_]").remove();
  start_group_search(tab,group_list);
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
                <input required type="text" name="g_filter_text" id="g_filter_text_'+tab+'_'+group_list[i].article+'" class="form-control search_str" placeholder="Убрать мусор" onkeyup="group_get_filter_text('+tab+',\''+group_list[i].article+'\',\''+group_list[i].brand+'\');">\
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
    table+='<center> Ищем: артикул: <b>'+group_list[i].article+"</b> брэнд: <b>"+group_list[i].brand+"</b> наименование: <b>"+group_list[i].name+"</b> кол-во: <b>"+group_list[i].kolvo+"</b> цена: <b>"+group_list[i].price+"</b></center><br>";
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
        table+='<center> Ищем: артикул: <b>'+group_list[i].article+"</b> брэнд: <b>"+group_list[i].brand+"</b> наименование: <b>"+group_list[i].name+"</b> кол-во: <b>"+group_list[i].kolvo+"</b> цена: <b>"+group_list[i].price+"</b></center><br>";
        table+="<div id='zapchasti_content_"+tab+"_"+group_list[i].article+"_"+group_list[i].brand+"'></div>";
        table+="</div>";
        if(typeof($("#zapchasti_group_"+tab+"_"+group_list[i].article).html())=='undefined') $("#zapchasti_content_"+tab).append(table);
      } 
    }
    if(i<group_list.length && typeof(g_endsearch[tab][group_list[i].article])=="undefined"){
      g_all_items[tab][group_list[i].article] = new Array();
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

function show_group_search_res(tab,article){
  $("div[id^=zapchasti_group_"+tab+"]").hide();
  $("div[id^=zapchasti_group_"+tab+"_"+article+"]").show();
  $("tr[id^=group_search_list_"+tab+"]").removeClass("active");
  $("#group_search_list_"+tab+"_"+article).addClass("active");
}

function stop_group_search(tab){
  g_stop_search[tab]=1;
  var len=group_lists[tab].length;
  for (var i=0; i<len; i++){
    if(typeof(g_endsearch[tab][group_lists[tab][i].article])=="undefined" || g_endsearch[tab][group_lists[tab][i].article]==0){
      g_endsearch[tab][group_lists[tab][i].article]=2;
      $("#g_s_status_"+tab+"_"+group_lists[tab][i].article).html('<img src="/images/cancel.svg" style="width:10px;" title="Поиск отменен">');
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
      g_all_items[tab][group_lists[tab][i].article]=[];
      g_all_orig_items[tab][group_lists[tab][i].article]=[];
      g_all_analog_items[tab][group_lists[tab][i].article]=[];
      g_all_sklad_orig_items[tab][group_lists[tab][i].article]=[];
      g_all_sklad_analog_items[tab][group_lists[tab][i].article]=[];
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