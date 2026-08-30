var basket_details=new Array();
var makezakaz_default_company={
    company_id: 471,
    company_name: "Розничный клиент"
};

function toggle_basket_detail(i){
    if(parseInt(basket_details[i]['checked'])==1 && !$("#basket_detail_check_"+i).prop("checked")) basket_details[i]['checked']=0;
    else {
        if($("#basket_detail_check_"+i).prop("checked")) basket_details[i]['checked']=1;
        if(!$("#basket_detail_check_"+i).prop("checked")) basket_details[i]['checked']=0;
    }
}

function toggle_basket_details(){
    var datalen=basket_details.length;
    if (document.getElementById('all_basket_check').checked) var checked=1;
    else var checked=0;
    if(datalen>0){
	for (var i=0; i<datalen; i++){
	    if(checked==1) basket_details[i]['checked']=1;
	    else basket_details[i]['checked']=0;
	}
    }
    print_basket_details();
    document.getElementById('all_basket_check').checked=checked;
}

function add_to_basket_by_ean(detail_id=0){
    var send=new Array();
    send['ean13']=$("#basket_ean13").val();
    send['sklad_id']=$("#my_sklad").val();
    api_query_array("/api/index.php",send,"search_by_ean13").then(function(data){
        if(data.search_by=="document"){
            var len=data.document_details.length;
            if(detail_id==0){           
                if(data.status=="ok" && len>0){
                    if(len==1){
                        var send1=new Array();
                        send1['article']=data.document_details[0].article;
                        send1['brand']=data.document_details[0].brand;
                        send1['brand_id']=data.document_details[0].brand_id;
                        send1['cost']=data.document_details[0].sale_price;
                        send1['cost_sum']=data.document_details[0].sale_price;
                        send1['count']=data.document_details[0].count;
                        send1['deliverer']=data.document_details[0].sklad_name;
                        send1['deliverer_id']=data.document_details[0].sklad_id;
                        send1['deliverer_type']="sklad";
                        send1['detail_id']=data.document_details[0].detail_id;
                        send1['fast_sale']=1;
                        send1['name']=data.document_details[0].name;
                        send1['price']=data.document_details[0].price;
                        send1['stock']=data.document_details[0].stock;
                        send1['ean13']=data.document_details[0].ean13;
                        send1['my_code']=data.document_details[0].my_code;
                        send1['document_id']=parseInt(data.document_details[0].document_id);
                        send1['document_detail_id']=parseInt(data.document_details[0].id);
                        send1['to_cart_count']=1;
                        api_query_array("/api/index.php",send1,"save_basket_detail").then(function(data1){
                            if(data1.status=="ok"){
                                get_basket_details();
                            }
                        });
                    }
                    else {
                        var table='<table class="table table-hover"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>кол-во</th><th>цена</th></tr></thead><tbody>';
                        for (let i=0; i<len; i++ ){
                            table+='<tr onclick="add_to_basket_by_ean('+data.document_details[i].detail_id+')"><td>'+data.document_details[i].article+'</td>\
                            <td>'+data.document_details[i].brand+'</td>\
                            <td>'+data.document_details[i].name+'</td>\
                            <td>'+data.document_details[i].count+'</td>\
                            <td>'+data.document_details[i].sale_price+'</td></tr>';
                        }
                        table+='</tbody></table>';
                        create_window("select_by_ean_details_div","Выберите деталь","select_by_ean_details",table);
                    }
                }
                else {
                    bootbox.alert("Такого товара нет на складе");
                }
            }
            else {
                for (let i=0; i<len; i++ ){
                    if(parseInt(detail_id)===parseInt(data.document_details[i].detail_id)){
                        var send1=new Array();
                        send1['article']=data.document_details[i].article;
                        send1['brand']=data.document_details[i].brand;
                        send1['brand_id']=data.document_details[i].brand_id;
                        send1['cost']=data.document_details[i].sale_price;
                        send1['cost_sum']=data.document_details[i].sale_price;
                        send1['count']=data.document_details[i].count;
                        send1['deliverer']=data.document_details[i].sklad_name;
                        send1['deliverer_id']=data.document_details[i].sklad_id;
                        send1['deliverer_type']="sklad";
                        send1['detail_id']=data.document_details[i].detail_id;
                        send1['fast_sale']=1;
                        send1['name']=data.document_details[i].name;
                        send1['price']=data.document_details[i].price;
                        send1['stock']=data.document_details[i].stock;
                        send1['ean13']=data.document_details[i].ean13;
                        send1['my_code']=data.document_details[i].my_code;
                        send1['document_id']=parseInt(data.document_details[i].document_id);
                        send1['document_detail_id']=parseInt(data.document_details[i].id);
                        send1['to_cart_count']=1;
                        api_query_array("/api/index.php",send1,"save_basket_detail").then(function(data1){
                            if(data1.status=="ok"){
                                get_basket_details();
                            }
                        });
                    }
                }
            }
        }
        else {
            var len=data.sklad_details.length;
            if(detail_id==0){           
                if(data.status=="ok" && len>0){
                    if(len==1){
                        var send1=new Array();
                        send1['article']=data.sklad_details[0].article;
                        send1['brand']=data.sklad_details[0].brand;
                        send1['brand_id']=data.sklad_details[0].brand_id;
                        send1['cost']=data.sklad_details[0].sale_price;
                        send1['cost_sum']=data.sklad_details[0].sale_price;
                        send1['count']=data.sklad_details[0].count;
                        send1['deliverer']=data.sklad_details[0].sklad_name;
                        send1['deliverer_id']=data.sklad_details[0].sklad_id;
                        send1['deliverer_type']="sklad";
                        send1['detail_id']=data.sklad_details[0].detail_id;
                        send1['fast_sale']=1;
                        send1['name']=data.sklad_details[0].name;
                        send1['price']=data.sklad_details[0].price;
                        send1['stock']=data.sklad_details[0].stock;
                        send1['ean13']=data.sklad_details[0].ean13;
                        send1['my_code']=data.sklad_details[0].my_code;
                        send1['to_cart_count']=1;
                        api_query_array("/api/index.php",send1,"save_basket_detail").then(function(data1){
                            if(data1.status=="ok"){
                                get_basket_details();
                            }
                        });
                    }
                    else {
                        var table='<table class="table table-hover"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>кол-во</th><th>цена</th></tr></thead><tbody>';
                        for (let i=0; i<len; i++ ){
                            table+='<tr onclick="add_to_basket_by_ean('+data.sklad_details[i].detail_id+')"><td>'+data.sklad_details[i].article+'</td>\
                            <td>'+data.sklad_details[i].brand+'</td>\
                            <td>'+data.sklad_details[i].name+'</td>\
                            <td>'+data.sklad_details[i].count+'</td>\
                            <td>'+data.sklad_details[i].sale_price+'</td></tr>';
                        }
                        table+='</tbody></table>';
                        create_window("select_by_ean_details_div","Выберите деталь","select_by_ean_details",table);
                    }
                }
                else {
                    bootbox.alert("Такого товара нет на складе");
                }
            }
            else {
                for (let i=0; i<len; i++ ){
                    if(parseInt(detail_id)===parseInt(data.sklad_details[i].detail_id)){
                        var send1=new Array();
                        send1['article']=data.sklad_details[i].article;
                        send1['brand']=data.sklad_details[i].brand;
                        send1['brand_id']=data.sklad_details[i].brand_id;
                        send1['cost']=data.sklad_details[i].sale_price;
                        send1['cost_sum']=data.sklad_details[i].sale_price;
                        send1['count']=data.sklad_details[i].count;
                        send1['deliverer']=data.sklad_details[i].sklad_name;
                        send1['deliverer_id']=data.sklad_details[i].sklad_id;
                        send1['deliverer_type']="sklad";
                        send1['detail_id']=data.sklad_details[i].detail_id;
                        send1['fast_sale']=1;
                        send1['name']=data.sklad_details[i].name;
                        send1['price']=data.sklad_details[i].price;
                        send1['stock']=data.sklad_details[i].stock;
                        send1['ean13']=data.sklad_details[i].ean13;
                        send1['my_code']=data.sklad_details[i].my_code;
                        send1['to_cart_count']=1;
                        api_query_array("/api/index.php",send1,"save_basket_detail").then(function(data1){
                            if(data1.status=="ok"){
                                get_basket_details();
                            }
                        });
                    }
                }
            }
        }
    });
}

function check_basket_details_by_deliverer(deliv_type,deliv_id){
    if($("#basket_deliverer_checkbox_"+deliv_type+"_"+deliv_id).prop("checked")){
        var trs=$("tr[deliverer_type="+deliv_type+"][deliverer_id="+deliv_id+"]");
        for(var i of trs){
            if(!$("#"+i.id).find("input[id^=basket_detail_check]").prop("checked")){
                $("#"+i.id).find("input[id^=basket_detail_check]").click();
            }
        }
    }
    else {
        var trs=$("tr[deliverer_type="+deliv_type+"][deliverer_id="+deliv_id+"]");
        for(var i of trs){
            if($("#"+i.id).find("input[id^=basket_detail_check]").prop("checked")){
                $("#"+i.id).find("input[id^=basket_detail_check]").click();
            }
        }
    }
}

function change_print_basket_href(){
    if($("#print_basket_art").prop("checked")){
        $("#print_basket_href").attr("href","/print_basket.php?article=1");
    }
    else {
        $("#print_basket_href").attr("href","/print_basket.php");
    }
}

function change_basket_detail_name(i){
    let val=$("#basket_detail_name_input_"+i).val().replace(/\n/g,"");
    basket_details[i].name=val;
    $("#basket_detail_name_"+i).text(val)
    //print_basket_details();
}

function restore_basket_detail_name(i){
    let val=$("#basket_detail_old_name_"+i).val();
    //basket_details[i].name=val;
    $("#basket_detail_name_"+i).text(val)
    //print_basket_details();
}

function edit_basket_detail_name(i){
    let old_val=$("#basket_detail_name_"+i).text();
    let html=old_val+'<textarea style="position:absolute; width:400px;" class="form-control" type="text" id="basket_detail_name_input_'+i+'"\
     onkeyup="if(event.keyCode===13) change_basket_detail_name('+i+'); if(event.keyCode===27) restore_basket_detail_name('+i+');" onfocusout="restore_basket_detail_name('+i+')">'+old_val+'</textarea>\
    <input type="hidden" id="basket_detail_old_name_'+i+'" value="'+old_val+'">';
    $("#basket_detail_name_"+i).html(html);
    $("#basket_detail_name_input_"+i).focus();
}

function print_basket_details(){
    var datalen=basket_details.length;
    var table="<br><div class='row'>\
    <div class='col-sm-2'>\
    <b>Детали в корзине:</b> </div>\
    <div class='col-sm-1'><input type='checkbox' id='print_basket_art' onchange='change_print_basket_href();'> :Арт <a href='/print_basket.php' target='_blank' id='print_basket_href'><img src='/new_images/printer.svg' style='width:25px;' title='Печать списка из корзины'></a>\
    </div>\
    <div class='col-sm-2'>"
    table+=' з.ц. <input type="checkbox" id="basket_show_dealer_price" onchange="toggle_basket_dealer_price();" title="закуп цена" '+(typeof($("#basket_show_dealer_price").html())=="undefined"?' checked':($("#basket_show_dealer_price").prop("checked")?' checked':''))+'>';

    table+=" групп. по постав. <input type='checkbox' id='basket_group_by_deliverer'  onchange='get_basket_details();' title='группировать по поставщикам'\
    ";
    if($("#basket_group_by_deliverer").prop("checked")) table+=' checked';
    table+=">"
    table+="</div>";
    table+='<div class="col-sm-3" style="text-align:right">Добавить в корзину со склада:</div>\
    <div class="col-sm-3"><input type="text" class="form-control" size="3" placeholder="Штрих-код, артикул, мой код" id="basket_ean13" onchange="add_to_basket_by_ean();" autocomplete="off"></div>\
    <div class="col-sm-1"><sup style="font-size: 120%; cursor: pointer; top: 0.6em; float: left" title="Поставьте курсор в это окно и отсканируйте штрих-код детали">⍰</sup></div>\
    </div><div id="select_by_ean_details"></div>';
    table+='<hr>';
    if(datalen>0){
        table += "<table class=\"table table-hover basket-details\">\
        <thead>\
            <tr>\
                <th>№</th>\
                <th><input type='checkbox' onclick='toggle_basket_details()' id='all_basket_check'></th>\
                <th>Артикул</th>\
                <th>Брэнд</th>\
                <th>Наименование</th>\
                <th>Срок доставки</th>\
                <th class='basket_dealer_price'>Закуп.цена</th>\
                <th>Цена</th>\
                <th>Кол-во</th>\
                <th>Сумма</th>\
                <th>Тип</th>\
                <th>Поставщик</th>\
                <th>Комментарий</th>\
                <th title='Быстрая продажа'>БП</th>\
                <th title='Импортировано из корзины сотрудника'>Имп.</th>\
                <th>Акциз.</th>\
                <th></th>\
            </tr>\
        </thead>\
        <tbody>";
        var id=0;
        var basket_sum=0, basket_dealer_sum=0, deliverer_id="0",deliverer_sum=[];
        for (var i=0; i<datalen; i++){
            id=i;
            if(typeof(basket_details[i].deliverer_id)!="undefined" && $("#basket_group_by_deliverer").prop("checked") && basket_details[i].deliverer_id!=deliverer_id){
                if(deliverer_id!="0") 
                    table+='<tr style="background-color:lightgray"><td colspan="6">Итого</td><td><b>'+deliverer_sum[deliverer_id]['basket_dealer_sum'].toFixed(2)+'</b></td><td colspan="2"></td><td><b>'+deliverer_sum[deliverer_id]['basket_sum'].toFixed(2)+'</b></td><td colspan="6"></td></tr>';
                deliverer_id=basket_details[i].deliverer_id;
                deliverer_sum[deliverer_id]={basket_dealer_sum:0,basket_sum:0};
                table+='<tr style="background-color: #90ffeb"><td></td>\
                <td><input type="checkbox" onchange="check_basket_details_by_deliverer('+basket_details[i].deliverer_type+','+basket_details[i].deliverer_id+')" id="basket_deliverer_checkbox_'+basket_details[i].deliverer_type+'_'+basket_details[i].deliverer_id+'"></td>\
                <td colspan="14"><b>';
                switch(parseInt(basket_details[i].deliverer_type)){
                    case 1:
                        if(typeof(basket_details[i].sklad_name)!="undefined" && basket_details[i].sklad_name!==null) table += "Склад "+basket_details[i].sklad_name;
                        else table += "Склад";
                        break;
                    case 2:
                        if(typeof(basket_details[i].pricelist_name)!="undefined" && basket_details[i].pricelist_name!==null) table += "Прайс "+basket_details[i].pricelist_name;
                        else table += "Прайс";
                        break;
                    case 3:
                        if(typeof(basket_details[i].deliverer_name)!="undefined" && basket_details[i].deliverer_name!==null) table += basket_details[i].deliverer_name;
                        else table += "Онлайн";
                        break;
                }
                
                table+='</b></td></tr>';
            }
            table += "<tr id='basket_detail_id_"+i+"' deliverer_type='"+basket_details[i].deliverer_type+"' deliverer_id='"+basket_details[i].deliverer_id+"'";
            if(parseFloat(basket_details[i].old_count)>0 && basket_details[i].count!=basket_details[i].old_count){
                table+=" style='background-color:yellow;'";
            }
            table+="><td><div id='edit_basket_detail_"+basket_details[i].id+"'></div>"+(i+1)+"</td>";
            table += "<td><input type='checkbox' id='basket_detail_check_"+i+"' onclick='toggle_basket_detail("+i+")'";
            if(parseInt(basket_details[i]['checked'])==1) table+=" checked='checked'";
            table += "></td>";
            table += "<td>" + basket_details[i].article + "</td><td>"+basket_details[i].brand+"</td><td ondblclick='edit_basket_detail_name("+i+")' id='basket_detail_name_"+i+"'>"+basket_details[i].name+"</td><td>"+basket_details[i].time+"</td>";
            if(typeof(my_roles.modules_rights.modules.m0)!="undefined" && my_roles.modules_rights.modules.m0.rights.show_basket_sale_price.show==0) table+="<td>не доступно</td>";
            else table+="<td class='basket_dealer_price'>"+basket_details[i].dealer_price+"</td>";
            table+="<td><input type='text' class='form-control' value='"+basket_details[i].price+"' style='width:100px; text-align:center;' id='cart_price_"+i+"' onchange='change_basket_price("+id+");'></td>";
            table += "<td><div class='input-group'><span class='input-group-btn'><button class='btn btn-default' type='button' onclick='decrease_basket_count("+id+")'>-</button></span> <input type='text' class='form-control' value='"+basket_details[i].count+"' style='width:58px; text-align:center;' id='cart_count_"+i+"' onchange='change_basket_count("+id+");'> <span class='input-group-btn'><button class='btn btn-default' type='button'  onclick='increase_basket_count("+id+")'>+</button></div></span></td>";
            table += "<td><div id='cart_count_price_"+i+"'>"+(basket_details[i].price*basket_details[i].count).toFixed(2)+"</div></td>";
            switch(parseInt(basket_details[i].deliverer_type)){
                case -1:
                    table += "<td>Не определен</td><td>Не определен</td>";
                    break;
                case 1:
                    if(typeof(basket_details[i].sklad_name)!="undefined" && basket_details[i].sklad_name!==null) table += "<td>Склад</td><td>"+basket_details[i].sklad_name+"</td>";
                    else table += "<td>Склад</td><td></td>";
                    break;
                case 2:
                    if(typeof(basket_details[i].pricelist_name)!="undefined" && basket_details[i].pricelist_name!==null) table += "<td>Прайс</td><td>"+basket_details[i].pricelist_name+"</td>";
                    else table += "<td>Прайс</td><td></td>";
                    break;
                case 3:
                    if(typeof(basket_details[i].deliverer_name)!="undefined" && basket_details[i].deliverer_name!==null) table += "<td>Онлайн</td><td>"+basket_details[i].deliverer_name+"</td>";
                    else table += "<td>Онлайн</td><td></td>";
                    break;
                default: table+="<td></td>";
            }
            table += "<td><input id='cart_comment_"+i+"' value='"+basket_details[i].comment+"' onchange='change_comment("+i+")' class='form-control' title='"+basket_details[i].comment+"'></td>";
            if(basket_details[i].fast_sale=="1") table += '<td>Да</td>';
            else table += '<td></td>';
            if(basket_details[i].imported_from_user_name===null) table += '<td></td>';
            else table += '<td>'+(basket_details[i].imported_from_user_name!==null?basket_details[i].imported_from_user_name:"")+' '+(basket_details[i].imported_from_user_lastname!==null?basket_details[i].imported_from_user_lastname:"")+'</td>';
            table+='<td><input type="checkbox" id="basket_detail_is_excise_'+basket_details[i].id+'" '+(basket_details[i].is_excise==1?"checked":"")+' onchange="change_basket_is_excise('+basket_details[i].id+','+basket_details[i].detail_id+')"></td>';
            table += "<td><form id='delete_detail_"+basket_details[i].id+"'><input type=\"hidden\" name=\"id\" value=\""+basket_details[i].id+"\"><input type=\"hidden\" name=\"basket_id\" value=\""+basket_details[i].basket_id+"\"></form>";
            table += "<div class='btn-group' style='display: flex;'>";
            table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
            table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь из корзины?\',function(result){ if(result) api_query('/api/index.php','delete_detail_"+basket_details[i].id+"','delete_basket_detail').then(function(data){if(data.status=='ok') get_basket_details();});});\"></button>";
            table += "</div>";
            table += "</td>";
            table += "</tr>";
            basket_sum+=basket_details[i].price*basket_details[i].count;
            basket_dealer_sum+=basket_details[i].dealer_price*basket_details[i].count;
            if(deliverer_id!="0"){
                deliverer_sum[deliverer_id]['basket_sum']+=basket_details[i].price*basket_details[i].count;
                deliverer_sum[deliverer_id]['basket_dealer_sum']+=basket_details[i].dealer_price*basket_details[i].count;
            }
        }
        if(deliverer_id!="0") 
                    table+='<tr style="background-color:lightgray"><td colspan="6">Итого</td><td><b>'+deliverer_sum[deliverer_id]['basket_dealer_sum'].toFixed(2)+'</b></td><td colspan="2"></td><td><b>'+deliverer_sum[deliverer_id]['basket_sum'].toFixed(2)+'</b></td><td colspan="6"></td></tr>';
        table += "<tr><td colspan='2'><div id='make_zakaz'></div></td><td colspan='4'><b>Итого</b></td><td><b><div id='cart_dealer_sale_sum'>";
        if(typeof(my_roles.modules_rights.modules.m0)!="undefined" && my_roles.modules_rights.modules.m0.rights.show_basket_sale_price.show==0) table+="";
        else table+=basket_dealer_sum.toFixed(2);
        table+="</div></b></td><td colspan='2'></td><td><b><div id='cart_sale_sum'>"+basket_sum.toFixed(2)+"</div></b></td><td colspan='7'></td></tr>";
        table += "</tbody></table>";
        table += "<div class='row'><div class='col-sm-2'><button class='btn btn-danger btn-sm' onclick='bootbox.confirm(\"Вы точно хотите очистить корзину?\",function(result){ if(result) clear_basket(); })'>Очистить корзину</button></div>\
            <div class='col-sm-2'><button class='btn btn-success btn-sm' onclick='make_zakaz(1);' title='Нажатие инициирует упрощённую продажу тех товаров из корзины, что находятся на выбранном складе'>Быстрая продажа</button></div>";
            table +="<div class='col-sm-8'><div class='pull-right'>";
            table+='<button class="btn btn-danger btn-sm" onclick="bootbox.confirm(\'Вы точно хотите удалить выбранные детали из корзины?\',function(result){ if(result) delete_basket_selected(); })">Удалить выбранное</button> ';
            if(parseInt(my_roles.id)<3){
                table +="<button class='btn btn-success btn-sm' onclick='unload_basket()'>Выгрузить корзину</button>";
            }
            table+="&nbsp;<button class='btn btn-default btn-sm' onclick='save_basket()'>Сохранить изменения в корзине</button>&nbsp;<button class='btn btn-primary btn-sm' onclick='make_zakaz();'>Оформить заказ</button></div>\
                <div class='select_basket' id='select_basket'></div>\
            </div>";
            table +="<div class='row'>\
            </div>";
    }
    else {
	    table+="Ваша корзина пуста";
        if(parseInt(my_roles.id)<3){
            table +="<div class='col-sm-8'>\
                <div class='pull-right'><button class='btn btn-success btn-sm' onclick='unload_basket()'>Выгрузить корзину</button></div>\
                <div class='select_basket' id='select_basket'></div>\
            </div>";
        }   
    }
    $('[id^=content_]').css("display","none");
    var element =  document.getElementById('content_basket');
    if (typeof(element) == 'undefined' || element == null){
      $("#contents").append('<div id="content_basket" style="margin-top:29px; margin-left: 108px; overflow: auto; padding: 10px; background-color: #ffffff; min-height: 100vh;"><div id="basket_details"></div></div>');
    }
    setLocation("/account/basket");
    $("#basket_details").html(table);
    $('#content_basket').css("display","block");
    activate_mod_link("basket");
    $("#basket_ean13").focus();
}

function toggle_basket_dealer_price(){
    var st="none";
    if($(".basket_dealer_price").css("display")=="none") st="table-cell";
    $(".basket_dealer_price").css("display",st);
}

function get_basket_details(){
 basket_details=new Array();
 var send=[];
 send['group_by_deliverer']=$("#basket_group_by_deliverer").prop("checked");
 api_query_array("/api/index.php",send,"get_basket_details").then(function(data){
    var datalen=data.basket_details.length;
    var table="<br><b>Детали в корзине:</b><hr>";
    //table += "<div class='row' style='padding:5px;'><div class='col-xs-4'><button class=\"btn btn-primary btn-sm\" onclick=\"add_new_detail("+$('#'+sklad_form+' [name=sklad_id]').val()+")\">Добавить деталь</button></div>";
    //table += "<div class='col-xs-4 pull-right'><div class='input-group input-group-sm'><input type='text' class='form-control'><span class='input-group-btn'><button class='btn btn-default' type='button'>Поиск</button></span></div></div>";
    //table += "</div><div id='add_new_sklad_detail'></div>";
    if(datalen>0){
        table += "<table class=\"table table-hover\"><thead><tr><th>№</th><th><input type='checkbox' onclick='toggle_basket_details()' id='all_basket_check'></th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Срок доставки</th><th>Цена</th><th>Кол-во</th><th>Сумма</th><th>Комментарий</th><th></th></tr></thead><tbody>";
        var id=0;
        var basket_sum=0;
        basket_details=new Array();
        var last_update=new Date("1970-01-01T00:00:00");
        var detail_upd_date=new Date();
        var last_upd_detail=0;
        for (var i=0; i<datalen; i++){
            basket_details[i]=new Array();
            basket_details[i]=data.basket_details[i];
            detail_upd_date=new Date(basket_details[i].update_date.replace(" ","T"));
            //console.log("last_upd_date="+last_update);
            //console.log("detail_upd_date="+detail_upd_date);
            if(last_update<detail_upd_date){
                last_update=detail_upd_date;
                last_upd_detail=i;
            }
        }
        basket_details[last_upd_detail].last_update=1;
    }
    $("#shop_cart_count").html(datalen);
    print_basket_details();
 });
}

function recalculation_basket_sum(){
    basket_sum = 0;
    basket_dealer_sum = 0;
    basket_details.forEach(basket_detail => {
        basket_sum += basket_detail.count * basket_detail.price;
        basket_dealer_sum += basket_detail.count * basket_detail.dealer_price;
    });
    if(typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_basket_sale_price.show==1)
        $("#cart_dealer_sale_sum").html(basket_dealer_sum.toFixed(2));
    $("#cart_sale_sum").html(basket_sum.toFixed(2));
}

function decrease_basket_count(id){
    var old_basket_detail_count=basket_details[id]['count'];
    if(basket_details[id]['count']>1) {
        if(typeof(basket_details[id]['multiplicity'])!="undefined" && parseInt(basket_details[id]['multiplicity'])>1)
            basket_details[id]['count']=parseInt(basket_details[id]['count'])-parseInt(basket_details[id]['multiplicity']);
        else
    	    basket_details[id]['count']--;
    	$("#cart_count_"+id).val(basket_details[id]['count']);
    	$("#cart_count_price_"+id).html((basket_details[id]['price']*basket_details[id]['count']).toFixed(2));
    	//print_basket_details();
        //save_basket();
        var send_bd={ ...basket_details[id] };
        send_bd.to_cart_count=-1;
        send_bd.count=basket_details[id].max_count;
        send_bd.mcount=basket_details[id].min_count;
        api_query_array("/api/index.php",send_bd,"save_basket_detail").then(function(data){
            if(data.status=="err"){
                $("#cart_count_"+id).val(old_basket_detail_count);
            }
            if(data.status=="ok"){
                $("[id^=basket_detail_id_]").css("background-color","");
                $("#basket_detail_id_"+id).css("background-color","yellow");
            }
        });
    }
    recalculation_basket_sum();
}

function increase_basket_count(id){
    var old_basket_detail_count=basket_details[id]['count'];
    if(basket_details[id]['count']<basket_details[id]['max_count']){
        if(typeof(basket_details[id]['multiplicity'])!="undefined" && parseInt(basket_details[id]['multiplicity'])>1)
            basket_details[id]['count']=parseInt(basket_details[id]['count'])+parseInt(basket_details[id]['multiplicity']);
        else
    	    basket_details[id]['count']++;
    	$("#cart_count_"+id).val(basket_details[id]['count']);
    	$("#cart_count_price_"+id).html((basket_details[id]['price']*basket_details[id]['count']).toFixed(2));
    	//print_basket_details();
        //save_basket();
        var send_bd={ ...basket_details[id] };
        send_bd.to_cart_count=1;
        send_bd.count=basket_details[id].max_count;
        send_bd.mcount=basket_details[id].min_count;
        api_query_array("/api/index.php",send_bd,"save_basket_detail").then(function(data){
            if(data.status=="err"){
                $("#cart_count_"+id).val(old_basket_detail_count);
            }
            if(data.status=="ok"){
                $("[id^=basket_detail_id_]").css("background-color","");
                $("#basket_detail_id_"+id).css("background-color","yellow");
            }
        });
    }
    recalculation_basket_sum();
}

function change_basket_count(id){
    if($("#cart_count_"+id).val()<=basket_details[id]['max_count'] && $("#cart_count_"+id).val()>1){
    	basket_details[id]['count']=$("#cart_count_"+id).val();
    	$("#cart_count_"+id).val(basket_details[id]['count']);
    	$("#cart_count_price_"+id).html((basket_details[id]['price']*basket_details[id]['count']).toFixed(2));
    }
    else {
    	if($("#cart_count_"+id).val()>1) bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "Нельзя указать количество больше чем есть в наличии"});
    	$("#cart_count_"+id).val(basket_details[id]['count']);
    }
    // print_basket_details();
    recalculation_basket_sum();
    save_basket();
}

function change_basket_price(id){
    if(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.write==0){
        $("#cart_price_"+id).val(basket_details[id]['price']);
        bootbox.alert("У Вас нет прав для изменения цены");
        return 0;    
    }
    if(parseFloat($("#cart_price_"+id).val())>parseFloat(basket_details[id]['dealer_price']) && parseFloat($("#cart_price_"+id).val())>1){
    	basket_details[id]['price']=$("#cart_price_"+id).val();
    	$("#cart_price_"+id).val(basket_details[id]['price']);
    	$("#cart_count_price_"+id).html((basket_details[id]['price']*basket_details[id]['count']).toFixed(2));
    }
    else {
    	if($("#cart_price_"+id).val()>1) bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "Нельзя указать цену меньше чем та, по которой вы покупаете. Указана цена покупки от поставщика"});
        //$("#cart_price_"+id).val(basket_details[id]['dealer_price']);
        basket_details[id]['price']=basket_details[id]['dealer_price'];
    }
    // print_basket_details();
    recalculation_basket_sum();
    save_basket();
}

function change_comment(id){
    basket_details[id]['comment']=$("#cart_comment_"+id).val();
    save_basket();
}

function unload_basket(){
    api_query("/api/index.php","some_form","get_my_company_users_basket").then(function(data){
        var table='<table class="table table-hover"><thead><tr><th>ФИО</th><th>email</th><th></th><th></th></tr></thead><tbody>';
        for (let i=0; i<data.users.length; i++ ){
            user_name = ""+data.users[i].lastname+" "+ data.users[i].name +" "+ data.users[i].middlename+"";
            table+='<tr><td>'+user_name+'</td>\
            <td>'+data.users[i].email+'</td>';
            table+='<td><a onclick="show_user_basket('+data.users[i].basket_id+',\''+user_name+'\')" title="Информация по корзине"><img src="/new_images/edit.svg" class="menuimg"></a></td>\
            <td><div class="select_basket_user" id="select_basket_user"></div><td></tr>';
        }
        table+='</tbody></table>';
        create_window("select_basket_div","Выберите корзину","select_basket",table);
    });
}

var basket_details_company_user = new Array();

function unload_basket_user(basket_id){
    var send=[];
    send['basket_id']=basket_id;
    send['details_id']= new Array();
    for (var i=0; i<basket_details_company_user.length; i++){
        if(parseInt(basket_details_company_user[i]['checked']) == 1)
        {
            send['details_id'].push(parseInt(basket_details_company_user[i]['detail_id']));
        }
    }
    api_query_array("/api/index.php",send,"unload_basket_user").then(function(data){
        get_basket_details();
    });
}

function toggle_basket_details_user(basket_id,user_name){
    var datalen=basket_details_company_user.length;
    if (document.getElementById('all_basket_user_check').checked) var checked=true;
    else var checked=false;
    if(datalen>0){
        for (var i=0; i<datalen; i++){
            if(checked==1) basket_details_company_user[i]['checked']=1;
            else basket_details_company_user[i]['checked']=0;
        }
    }
    reload_user_basket(basket_id,user_name);
    document.getElementById('all_basket_user_check').checked=checked;
}

function toggle_basket_detail_user(i){
    if(parseInt(basket_details_company_user[i]['checked'])==1 && !$("#basket_detail_user_check_"+i).prop("checked")) basket_details_company_user[i]['checked']=0;
    else {
        if($("#basket_detail_user_check_"+i).prop("checked")) basket_details_company_user[i]['checked']=1;
        if(!$("#basket_detail_user_check_"+i).prop("checked")) basket_details_company_user[i]['checked']=0;
    }
    document.getElementById('all_basket_user_check').checked=false;
}

function show_user_basket(basket_id, user_name){
    var send=[];
    send['basket_id']=basket_id;
    api_query_array("/api/index.php",send,"get_basket_details").then(function(data){
        var table='<table class="table table-hover"><thead><tr><th><input type="checkbox" onclick="toggle_basket_details_user('+basket_id+',\''+user_name+'\')" id="all_basket_user_check"></th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Кол-во</th></tr></thead><tbody>';
        basket_details_company_user = data.basket_details;
        var datalen = data.basket_details.length;
        if(datalen>0 && basket_id != null){
            for (let i=0; i<datalen; i++ ){
                table+='<tr><td>';
                if(data.basket_details[i].checked == 1){
                    table+='<input type="checkbox" id="basket_detail_user_check_'+i+'" onclick="toggle_basket_detail_user('+i+')" checked="checked"></td>';
                }
                else{
                    table+='<input type="checkbox" id="basket_detail_user_check_'+i+'" onclick="toggle_basket_detail_user('+i+')" checked="unchecked"></td>';
                }
                table+='<td>'+data.basket_details[i].article+'</td>\
                <td>'+data.basket_details[i].brand+'</td>\
                <td>'+data.basket_details[i].name+'</td>\
                <td>'+data.basket_details[i].count+'</td>';
            }
        }
        else{
            basket_details_company_user = new Array();
            table+='<td>Корзина пуста</td>';
        }
        table+='</tbody></table>';
        table+='<button class="btn btn-primary" onclick="unload_basket_user('+basket_id+')">Выгрузить корзину</button>';
        create_window("select_basket_user_div",'Корзина ('+user_name+')',"select_basket_user",table);
    });
}

function reload_user_basket(basket_id,user_name){
    var table='<table class="table table-hover"><thead><tr><th><input type="checkbox" onclick="toggle_basket_details_user('+basket_id+',\''+user_name+'\')" id="all_basket_user_check"></th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Кол-во</th></tr></thead><tbody>';
    var datalen = basket_details_company_user.length;
    if(datalen>0){
        for (let i=0; i<datalen; i++ ){
            table+='<tr><td>';
            if(basket_details_company_user[i]['checked'] == 1){
                table+='<input type="checkbox" id="basket_detail_user_check_'+i+'" onclick="toggle_basket_detail_user('+i+')" checked></td>';
            }
            else{
                table+='<input type="checkbox" id="basket_detail_user_check_'+i+'" onclick="toggle_basket_detail_user('+i+')"></td>';
            }
            table+='<td>'+basket_details_company_user[i]['article']+'</td>\
            <td>'+basket_details_company_user[i]['brand']+'</td>\
            <td>'+basket_details_company_user[i]['name']+'</td>\
            <td>'+basket_details_company_user[i]['count']+'</td>';
        }
    }
    else{
        table+='<td>Корзина пуста</td>';
    }
    table+='</tbody></table>';
    table+='<button class="btn btn-primary" onclick="unload_basket_user('+basket_id+')">Выгрузить корзину</button>';
    create_window("select_basket_user_div","Корзина ("+user_name+")","select_basket_user",table);
}

function save_basket(){
    var defer=$.Deferred();
    var basket_details_arr=new Array();
    basket_details_arr['basket_details']=basket_details;
    api_query_array("/api/index.php",basket_details_arr,"save_basket").then(function(data){
        defer.resolve();
        get_basket_count();
    });
    return defer.promise();
}

function clear_basket(){
    api_query("/api/index.php","some_form","clear_basket").then(function(data){
        if(data.status=="ok"){
            get_basket_count();
            get_basket_details();
        }
      
    });
}

var zakaz=new Array();
zakaz['id']=0;
var dogovors=new Array();

function print_zakaz_details(fast_sale){
    if(typeof(fast_sale)=="undefined") fast_sale=0;
    is_set_cashback=$("#set_all_cashback").prop("checked");
    var datalen=basket_details.length;
    var table="<div class='row'><div class='col-sm-4'><b>Детали в заказе:</b></div>";
    table+=' <div class="col-sm-8"><button class="btn btn-default btn-sm" onclick="close_window(\'make_zakaz\')">Отмена</button>\
    <button class="btn btn-primary btn-sm" onclick="save_basket_zakaz('+fast_sale+');" id="make_zakaz_button"';
    if(fast_sale) table+=' title="Нажатие на эту кнопку сделает 3 действия: примет оплату с клиента, спишет детали со склада и создаст документ Продажа"';
    table+='>Оформить заказ</button></div></div><br>';
    table+="<hr style=\"margin-top:0px;margin-bottom:0px;\">";
    var zakaz_count=0;
    zakaz['details']=new Array();
    if(datalen>0){
	var table_header = "<table class=\"table table-hover\" style=\"margin-bottom:0px;\"><thead>\
    <tr><th>№</th><th></th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Срок доставки</th><th>Цена нач.</th><th>Скидка</th>";
    table_header += "<th><input type='checkbox' onchange='set_all_cashback("+fast_sale+")' id='set_all_cashback' "+(is_set_cashback?"checked":"")+"></th><th> Кэшбэк</th><th>Цена со скид.</th><th>Кол-во</th><th>Сумма</th><th>Комментарий</th><th>Акц.</th></tr></thead><tbody>";
	var id=0;
	var basket_sum=0,cashback_sum=0;;
	var table_body="",table_footer="";
	for (var i=0; i<datalen; i++){
        if(fast_sale && (parseInt(basket_details[i].deliverer_type)!=1 || parseInt(basket_details[i].deliverer_id)!=parseInt($("#my_sklad").val()))){
            basket_details[i]['checked']=0;
        }
	    if(parseInt(basket_details[i]['checked'])==1){
            id=i;
            if(typeof(basket_details[i].sale_price)=="undefined" || parseFloat(basket_details[i].sale_price)==0) {
                //if(zakaz['company_id']===-1) 
                //    basket_details[i].sale_price=parseFloat(basket_details[i].dealer_price).toFixed(2);
                //else 
                    //basket_details[i].sale_price=parseFloat(basket_details[i].price).toFixed(2);

            }
            if(typeof(basket_details[i].is_cashback)=="undefined") basket_details[i].is_cashback=0;
            zakaz['details'][zakaz_count]=basket_details[i];
            
            table_body += "<tr><td><div id='edit_basket_detail_"+basket_details[i].id+"'></div>"+(i+1)+"</td>";
            table_body += "<td></td>";
            table_body += "<td>" + basket_details[i].article + "</td><td>"+basket_details[i].brand+"</td><td>"+basket_details[i].name+"</td>\
            <td>"+basket_details[i].time+"</td>\
            <td>"+basket_details[i].price+"</td><td>"+(typeof(basket_details[i].skidka)!="undefined"?basket_details[i].skidka:0)+"</td>";
            table_body+="<td><input type='checkbox' id='is_cashback_"+i+"' onchange='set_cashback("+zakaz_count+","+fast_sale+")'";
            if(basket_details[i].is_cashback)  table_body+=' checked';
            table_body += "> </td><td>"+(basket_details[i].is_cashback?parseFloat(basket_details[i].cashback*basket_details[i].count).toFixed(2):"0")+"</td>"
            table_body += "<td>"+parseFloat(basket_details[i].sale_price).toFixed(2)+"</td>";
            table_body += "<td><div class='input-group'>"+basket_details[i].count+"</div></td>";
            table_body += "<td><div id='cart_count_price_"+i+"'>"+(basket_details[i].sale_price*basket_details[i].count).toFixed(2)+"</div></td>";
            table_body += "<td style='max-width:200px;'>"+basket_details[i].comment+"</td>";
            table_body += '<td><input type="checkbox" id="basket_detail_is_excise_'+basket_details[i].id+'" '+(basket_details[i].is_excise==1?"checked":"")+' onchange="change_basket_is_excise('+basket_details[i].id+','+basket_details[i].detail_id+')"></td>';
            table_body += "</tr>";
            basket_sum+=basket_details[i].sale_price*basket_details[i].count;
            cashback_sum+=(basket_details[i].is_cashback?parseFloat(basket_details[i].cashback*basket_details[i].count):0);
            zakaz_count++;
	    }
	}
	if(zakaz_count==0){
	    table+="<font color='red'>Вы не выбрали детали для размещения. Выберите в корзине необходимые детали.</font>";
	}
	else {
	    table_footer += "<tr><td colspan='7'><div id='make_zakaz'></div></td><td colspan='2'><b>Итого</b></td><td>"+cashback_sum.toFixed(2)+"</td><td colspan='2'></td><td><b>"+basket_sum.toFixed(2)+"</b></td><td colspan='3'></td></tr>";
	    table_footer += "</tbody></table>";
	    table+=table_header+table_body+table_footer;
	}
    }
    else {
	    table+="<font color='red'>Нет деталей в корзине. Положите в корзину необходимые детали.</font>";
    }
    //get_basket_details();
    $("#zakaz_details").html(table);
    get_fast_zakaz_change();
}

function set_cashback(zakaz_id,fast_sale){
    if(zakaz.details[zakaz_id].is_cashback){
        zakaz.details[zakaz_id].is_cashback=0;
        zakaz.details[zakaz_id].cashback=0;
        //zakaz.details[zakaz_id].is_cashback=0;
        zakaz.details[zakaz_id].sale_price=zakaz.details[zakaz_id].sale_price_w_skidka;
        //zakaz.details[zakaz_id].sale_price=zakaz.details[zakaz_id].sale_price_w_skidka;
        print_zakaz_details(fast_sale);
    }
    else {
        zakaz.details[zakaz_id].is_cashback=1;
        //zakaz.details[zakaz_id].is_cashback=1;
        zakaz.details[zakaz_id].sale_price_w_skidka=zakaz.details[zakaz_id].sale_price;
        if(typeof(zakaz.details[zakaz_id].detail_discount_from_cashback)=="undefined") zakaz.details[zakaz_id].detail_discount_from_cashback=0;
        zakaz.details[zakaz_id].sale_price=zakaz.details[zakaz_id].price-zakaz.details[zakaz_id].detail_discount_from_cashback;
        zakaz.details[zakaz_id].cashback=zakaz.details[zakaz_id].sale_price-zakaz.details[zakaz_id].sale_price_w_skidka;
        if(zakaz.details[zakaz_id].cashback<0) zakaz.details[zakaz_id].cashback=0;
        print_zakaz_details(fast_sale);
    }
}

function set_all_cashback(fast_sale){
    is_set_cashback=$("#set_all_cashback").prop("checked");
    let zakaz_cashback_discount=parseFloat($("#zakaz_cashback_discount").val());
    let zakaz_company_cashback=parseFloat($("#zakaz_company_cashback").text());
    if(isNaN(zakaz_company_cashback)) zakaz_company_cashback=0;
    if(isNaN(zakaz_cashback_discount)) zakaz_cashback_discount=0;
    if(zakaz_cashback_discount>zakaz_company_cashback) {
        zakaz_cashback_discount=zakaz_company_cashback;
        $("#zakaz_cashback_discount").val(zakaz_company_cashback);
    }
    let zdlen=zakaz.details.length;
    for(zakaz_id in zakaz.details){
        delete zakaz.details[zakaz_id].detail_discount_from_cashback;
        if(is_set_cashback){
            //if(zakaz_cashback_discount>0) 
            zakaz.details[zakaz_id].sale_price=zakaz.details[zakaz_id].price;
            zakaz.details[zakaz_id].sale_price_w_skidka=Math.ceil(zakaz.details[zakaz_id].price-(zakaz.details[zakaz_id].price/100)*zakaz.details[zakaz_id].skidka);
            //else

        }
        else{
          zakaz.details[zakaz_id].sale_price=Math.ceil(zakaz.details[zakaz_id].price-(zakaz.details[zakaz_id].price/100)*zakaz.details[zakaz_id].skidka);

        }
    }
    if(zdlen>0) var discount_from_cashback=zakaz_cashback_discount/zdlen;
    else var discount_from_cashback=0;
    var detail_discount_from_cashback=0;
    var ostatok=0;
    zakaz.details.sort(function(a,b){ return a.price-b.price});
    for(zakaz_id in zakaz.details){
        //delete zakaz.details[zakaz_id].detail_discount_from_cashback;
        //zakaz.details[zakaz_id].sale_price=zakaz.details[zakaz_id].price;
        detail_discount_from_cashback=discount_from_cashback/zakaz.details[zakaz_id].count;
        if(is_set_cashback){
            if(zakaz.details[zakaz_id].is_cashback==0){
                zakaz.details[zakaz_id].is_cashback=1;
                //zakaz.details[zakaz_id].sale_price_w_skidka=zakaz.details[zakaz_id].sale_price;
                if(typeof(zakaz.details[zakaz_id].detail_discount_from_cashback)=="undefined") zakaz.details[zakaz_id].detail_discount_from_cashback=0;
                zakaz.details[zakaz_id].sale_price=Math.ceil(zakaz.details[zakaz_id].price-(zakaz.details[zakaz_id].detail_discount_from_cashback));
                zakaz.details[zakaz_id].cashback=zakaz.details[zakaz_id].sale_price-zakaz.details[zakaz_id].sale_price_w_skidka;
                if(zakaz.details[zakaz_id].cashback<0) zakaz.details[zakaz_id].cashback=0;
            }
        }
        else {
            if(zakaz.details[zakaz_id].is_cashback==1){
                zakaz.details[zakaz_id].sale_price=Math.ceil(zakaz.details[zakaz_id].price-(zakaz.details[zakaz_id].price/100)*zakaz.details[zakaz_id].skidka);
                zakaz.details[zakaz_id].is_cashback=0;
                zakaz.details[zakaz_id].cashback=0;
                //zakaz.details[zakaz_id].sale_price=zakaz.details[zakaz_id].sale_price_w_skidka;
                
            }
        }
        
        if(typeof(zakaz.details[zakaz_id].detail_discount_from_cashback)=="undefined" ){
            zakaz.details[zakaz_id].sale_price-=(detail_discount_from_cashback);
            zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback;
            if(zakaz.details[zakaz_id].sale_price<0) {
                ostatok+=-(zakaz.details[zakaz_id].sale_price*zakaz.details[zakaz_id].count);
                zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback-ostatok;
                zakaz.details[zakaz_id].sale_price=0;
            }
            else {
                if(ostatok>0){
                    zakaz.details[zakaz_id].sale_price-=(ostatok/zakaz.details[zakaz_id].count);
                    if(zakaz.details[zakaz_id].sale_price<0){
                        ostatok=-(zakaz.details[zakaz_id].sale_price*zakaz.details[zakaz_id].count);
                        zakaz.details[zakaz_id].sale_price=0;
                        zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback+ostatok;
                    }
                    else {
                        zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback+ostatok;
                        ostatok=0;
                    }
                }
                else {
                    zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback;
                }
            }
        }
        /* else { 
            zakaz.details[zakaz_id].sale_price-=(detail_discount_from_cashback-zakaz.details[zakaz_id].detail_discount_from_cashback);
            if(zakaz.details[zakaz_id].sale_price<0) {
                ostatok+=-(zakaz.details[zakaz_id].sale_price*zakaz.details[zakaz_id].count);
                zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback+(zakaz.details[zakaz_id].sale_price*zakaz.details[zakaz_id].count);
                zakaz.details[zakaz_id].sale_price=0;
            }
            else {
                if(ostatok>0){
                    zakaz.details[zakaz_id].sale_price-=ostatok/zakaz.details[zakaz_id].count;
                    if(zakaz.details[zakaz_id].sale_price<0){
                        zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback+(ostatok+(zakaz.details[zakaz_id].sale_price*zakaz.details[zakaz_id].count));
                        ostatok=-(zakaz.details[zakaz_id].sale_price*zakaz.details[zakaz_id].count);
                        zakaz.details[zakaz_id].sale_price=0;
                        //zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback+ostatok;
                    }
                    else {
                        zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback+ostatok;
                        ostatok=0;
                    }
                }
                else {
                    zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback;
                }
            }
            //zakaz.details[zakaz_id].detail_discount_from_cashback=detail_discount_from_cashback;
        } */
    }
    $("#zakaz_cashback_discount").val(zakaz_cashback_discount-ostatok);
    ostatok=0;
    print_zakaz_details(fast_sale);
    get_fast_zakaz_change();
}

function delete_basket_selected(){
    var datalen=basket_details.length;
    zakaz['details']=new Array();
    if(datalen>0){
        var id=0,zakaz_count=0;
        for (var i=0; i<datalen; i++){
            if(parseInt(basket_details[i]['checked'])==1){
                id=i;
                zakaz['details'][zakaz_count]=basket_details[i];
                zakaz_count++;
            }
        }
    }
    save_basket().then(function(data1){
        api_query_array("/api/index.php",zakaz,"delete_basket_details").then(function(data){
            if(data.status=="ok"){
                get_basket_details();

            }
        });
    })
}

function get_zakaz_clients(fast_zakaz,selected_client_id,page=1){
    if(typeof(selected_client_id)=="undefined") selected_client_id=0;
    if(typeof(fast_zakaz)=="undefined") fast_zakaz=0;
    var send=[];
    if(parseInt(selected_client_id)>0) send['client_id']=selected_client_id;
    //send['limit']=100;
    send['search_clients_client_name']=$("#zakaz_company_name").val();
    send['page']=page;
    api_query_array("/api/index.php",send,"get_clients").then(function(data){
        var datalen=data.clients.length;
        var table="";
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
                    table += 'get_zakaz_clients('+fast_zakaz+','+selected_client_id+','+i+')">...</a></li>';
                }
                if (x==1) xx++;
            }
            else {
                if (y==1) {
                if (yy==0){
                    table += '<li';
                    table += '><a href="#" onclick="';
                    table += 'get_zakaz_clients('+fast_zakaz+','+selected_client_id+','+i+')">...</a></li>';
                }
                if (y==1) yy++;
                }
                else {
                table += '<li';
                if(selected_page==i) table+= " class='active'";
                table += '><a href="#" onclick="';
                table += 'get_zakaz_clients('+fast_zakaz+','+selected_client_id+','+i+')">'+i+'</a></li>';
                }
            }
        }
        table += '</ul></div>';
        var datalen=data.clients.length;
        if(parseInt(selected_client_id)>0 && datalen==1){
            $("#zakaz_company_name").val(data.clients[0].name);
            $("#zakaz_company_id").val(data.clients[0].id);
            return;
        }
        table+="<div style='max-height: 350px; width:550px; overflow: auto;'>\
        <table class='table table-hover'><thead><tr><th>Наименование</th><th>Телефон</th><th>ИНН</th><th>КПП</th></tr></thead><tbody>";
        var searchstr=$("#zakaz_company_name").val();
        if(searchstr=="" || "Себе на склад".toUpperCase().replace(/\"/g,"").indexOf(searchstr.replace(/\"/g,"").toUpperCase())!=-1){
            table += "<tr onclick='set_zakaz_client(-1,\"Себе на склад\","+fast_zakaz+");' style='cursor:pointer;'>";
            table+="<td>Себе на склад</td><td></td><td></td>";
            table+='</tr>';
        }
        for (var i=0; i<datalen; i++){
            //if(searchstr=="" || data.clients[i].name.toUpperCase().replace(/\"/g,"").indexOf(searchstr.replace(/\"/g,"").toUpperCase())!=-1 || data.clients[i].mphone.indexOf(searchstr)!=-1){    
                table += "<tr onclick='set_zakaz_client("+data.clients[i].id+",\""+data.clients[i].name.replace(/\"/g,"")+"\","+fast_zakaz+","+data.clients[i].show_descr+",\""+data.clients[i].descr+"\");' style='cursor:pointer;'>";
                table+="<td>"+data.clients[i].name+"</td><td>"+data.clients[i].mphone+"</td><td>"+data.clients[i].inn+"</td><td>"+data.clients[i].kpp+"</td>";
                table+='</tr>';
            //}
        }
        table += "</tbody></table></div>";
        create_window("zakaz_company_list_div","Выберите клиента","zakaz_company_list",table);
    });
}

function set_zakaz_client(client_id,client_name,fast_zakaz,show_descr=0,descr=""){
    for(let i of zakaz.details){
        if(typeof(i.skidka)!="undefined") i.skidka=0;
        if(typeof(i.cashback)!="undefined") i.cashback=0;
        if(typeof(i.cashback)!="undefined") i.cashback=0;
        if(typeof(i.sale_price)!="undefined") i.sale_price=i.price;
        if(typeof(i.detail_discount_from_cashback)!="undefined") i.detail_discount_from_cashback=0;
        if(typeof(i.sale_price_w_skidka)!="undefined") i.sale_price_w_skidka=i.price;
    }
    $("#zakaz_company_cashback").text(0);
    $("#zakaz_cashback_discount").val(0);
    print_zakaz_details(fast_zakaz);
    if (client_id != 471){
        let str = '<input class="form-check-input" type="radio" name="exampleRadios" id="exampleRadios4" value="option4" onclick="select_zakaz_delivery_address(4)">\
    	<label class="form-check-label" for="exampleRadios4" style="font-weight:normal;">\
    	    Логистическая компания\
    	</label>';
        $('#logistic_company_form_check').html(str);
    }else{
        $('#logistic_company_form_check').html('');
    }
    if(client_id!=0){
        zakaz['company_id']=parseInt(client_id);
        $("#zakaz_company_id").val(client_id);
        $("#zakaz_company_name").val(client_name);
        $("#zakaz_company_list").html('');
        get_zakaz_client_dogovors(fast_zakaz);
        get_zakaz_client_cars();
        select_zakaz_sklad(fast_zakaz);
        if(show_descr){
            bootbox.alert("<b style='color: red;'>Примечание к клиенту!!!</b><hr>"+descr);
        }
    }
    
}

function get_zakaz_client_dogovors(fast_sale=0){
    send=new Array();
    send['company_id']=$("#zakaz_company_id").val();
    if(parseInt(send['company_id'])>0){
      api_query_array("/api/index.php",send,"get_company_dogovors").then(function(data){
        var datalen=data.dogovors.length;
        var table="<select id='zakaz_company_dogovor_id' name='zakaz_company_dogovor_id' onchange='set_zakaz_company_dogovor("+fast_sale+");' class='form-control input-sm'>";
        //table+="<option value='0'>-------</option>";
        //dogovors=data.dogovors;
        for (var i=0; i<datalen; i++){
        	dogovors[data.dogovors[i].id]=data.dogovors[i];
        	table += "<option value='"+data.dogovors[i].id+"'";
        	if(i==0) table+=" selected='selected'";
            var dogovor_date=data.dogovors[i].create_date.replace(/(\d+)-(\d+)-(\d+)\s+\d+:\d+:\d+/,"$3.$2.$1");
        	table += ">Договор №"+data.dogovors[i].id+" от "+dogovor_date+" "+data.dogovors[i].descr+"</option>";
        }
        
        table += "</select>";
        $("#zakaz_company_dogovors_list").html(table);
        if(datalen==0){
            reset_zakaz_details_price();
            set_zakaz_company_dogovor(fast_sale);
            print_zakaz_details(fast_sale);
            //return;
        }
        if(datalen>=1) 
            if(typeof(data.dogovors[0])!="undefined" && data.dogovors[0].is_cashback_by_default==1 && $("#set_all_cashback").prop("checked")==false) {
                $("#set_all_cashback").click();
            }
            if(typeof(data.dogovors[0])!="undefined" && data.dogovors[0].is_cashback_by_default==0 && $("#set_all_cashback").prop("checked")==true) {
                $("#set_all_cashback").click();
            }
            set_zakaz_company_dogovor(fast_sale);
     });
   }
   else {
     $("#zakaz_company_dogovors_list").html('');
     reset_zakaz_details_price();
     print_zakaz_details(fast_sale);
   }
}

function get_zakaz_client_cars(){
    send=new Array();
    send['company_id']=$("#zakaz_company_id").val();
    if(parseInt(send['company_id'])>0){
      api_query_array("/api/index.php",send,"get_company_cars").then(function(data){
        var datalen=data.company_cars.length;
        var table="<select id='zakaz_company_car_id' name='car_id' class='form-control input-sm'>";
        //table+="<option value='0'>-------</option>";
        //dogovors=data.dogovors;
        for (var i=0; i<datalen; i++){
        	//company_cars[data.company_cars[i].id]=data.company_cars[i];
        	table += "<option value='"+data.company_cars[i].id+"'";
        	if(i==0) table+=" selected='selected'";
        	table += ">Марка:"+data.company_cars[i].auto_maker_name+" Модель:"+data.company_cars[i].auto_model+" Гос.Н:"+data.company_cars[i].auto_gov_num+" VIN:"+data.company_cars[i].vin+"</option>";
        }
        /*if(datalen==0){
            reset_zakaz_details_price();
            set_zakaz_company_car();
            print_zakaz_details();
            //return;
        }*/
        table += "</select>";
        $("#zakaz_company_cars_list").html(table);
        /*if(datalen>=1) 
            set_zakaz_company_car();*/
     });
   }
   else {
     $("#zakaz_company_cars_list").html('');
     //reset_zakaz_details_price();
     //print_zakaz_details();
   }
}

function set_zakaz_company(fast_zakaz){
    zakaz['company_id']=parseInt($('#zakaz_company_id').val());
    get_zakaz_client_dogovors(fast_zakaz);
    get_zakaz_client_cars();
    select_zakaz_sklad(fast_zakaz);
}

function set_zakaz_company_dogovor(fast_sale=0){
    zakaz['company_dogovor_id']=parseInt($('#zakaz_company_dogovor_id').val()) || 0;
    var discount = parseInt($('#zakaz_discount').val()) || 0;
    var round_for=1;
    if(zakaz['company_dogovor_id']==0){
        
        if(discount != 0){
            var proc = parseInt(discount);
            var detlen=zakaz.details.length;
            
            for(var i=0; i<detlen; i++){
                zakaz.details[i].skidka=proc;
                zakaz.details[i].sale_price=(Math.ceil((parseFloat(zakaz.details[i].price)-parseFloat(zakaz.details[i].price)/100*proc)/round_for)*round_for).toFixed(2);
                if(parseFloat(zakaz.details[i].sale_price)<parseFloat(zakaz.details[i].dealer_price)) zakaz.details[i].sale_price=zakaz.details[i].dealer_price;
                if(typeof(zakaz.details[i].is_cashback)!="undefined" && zakaz.details[i].is_cashback){
                    zakaz.details[i].cashback=zakaz.details[i].price-zakaz.details[i].sale_price;
                    zakaz.details[i].sale_price_w_skidka=zakaz.details[i].sale_price;
                    zakaz.details[i].sale_price=zakaz.details[i].price;
                }
            }  
            print_zakaz_details(fast_sale); 
        }
        else {
            var detlen=zakaz.details.length;
            for(var i=0; i<detlen; i++){
                zakaz.details[i].skidka=0;
                zakaz.details[i].sale_price=zakaz.details[i].price;
                zakaz.details[i].sale_price_w_skidka=zakaz.details[i].price;
            }
            reset_zakaz_details_price();
            print_zakaz_details(fast_sale);
        }
        return;
    }
    var send=new Array();
    send['price_type_id']=dogovors[zakaz['company_dogovor_id']].price_type;
    if(dogovors[zakaz['company_dogovor_id']].is_cashback_by_default==1 && $("#set_all_cashback").prop("checked")==false) {
        $("#set_all_cashback").click();
    }
    if(dogovors[zakaz['company_dogovor_id']].is_cashback_by_default==0 && $("#set_all_cashback").prop("checked")==true) {
        $("#set_all_cashback").click();
    }
    send['company_id']=zakaz['company_id'];
    api_query_array("/api/index.php",send,"get_price_type").then(function(data){
        $("#zakaz_company_cashback").text(data.company_balance.cashback);
        if(data.price_type.proc>0){
            if(discount != 0)
            {
                data.price_type.proc = parseInt(discount);
            }
            if(parseInt(data.price_type.round_for)>1){
                round_for=parseInt(data.price_type.round_for);
            }
            var detlen=zakaz.details.length;
            for(var i=0; i<detlen; i++){ 
                zakaz.details[i].skidka=data.price_type.proc;
                if(data.price_type.type==1) 
                    zakaz.details[i].sale_price=(Math.ceil((parseFloat(zakaz.details[i].price)-parseFloat(zakaz.details[i].price)/100*data.price_type.proc)/round_for)*round_for).toFixed(2);
                else {
                    if(data.price_type.type==2) {
                        zakaz.details[i].sale_price=(Math.ceil((parseFloat(zakaz.details[i].price)+parseFloat(zakaz.details[i].price)/100*data.price_type.proc)/data.price_type.round_for)*data.price_type.round_for).toFixed(2);
                    }
                    else {
                        
                    }
                }
                if(parseFloat(zakaz.details[i].sale_price)<parseFloat(zakaz.details[i].dealer_price)) zakaz.details[i].sale_price=zakaz.details[i].dealer_price;
                if(typeof(zakaz.details[i].is_cashback)!="undefined" && zakaz.details[i].is_cashback){
                    zakaz.details[i].cashback=zakaz.details[i].price-zakaz.details[i].sale_price;
                    zakaz.details[i].sale_price_w_skidka=zakaz.details[i].sale_price;
                    zakaz.details[i].sale_price=zakaz.details[i].price;
                }
            }
            print_zakaz_details(fast_sale);
        }
        else {
            if(data.price_type.type==3){
                valslen=data.price_type.differencial_values.length;
                var detlen=zakaz.details.length;
                for(var i=0; i<detlen; i++){
                    var skidka=0;
                    if(discount != 0)
                    {
                        skidka = discount;
                        zakaz.details[i].sale_price=(parseFloat(zakaz.details[i].price)-zakaz.details[i].price/100*skidka).toFixed(2);
                        if(parseFloat(zakaz.details[i].sale_price)<parseFloat(zakaz.details[i].dealer_price)) zakaz.details[i].sale_price=zakaz.details[i].dealer_price;
                    }
                    else{
                        if(parseInt(data.price_type.use_sum_trade)==0){
                            for(var x=0; x<valslen; x++){
                                if(parseFloat(zakaz.details[i].sale_price)>parseFloat(data.price_type.differencial_values[x].min_sum)){
                                    skidka=data.price_type.differencial_values[x].value;
                                    round_for=data.price_type.differencial_values[x].round_for;
                                }
                            }
                        }
                        else {
                            if(parseInt(data.price_type.use_sum_trade)==1){
                                for(var x=0; x<valslen; x++){
                                    if(data.company_balance!==null)
                                    if(parseFloat(data.company_balance.sum_trade)>=parseFloat(data.price_type.differencial_values[x].min_sum)){
                                        skidka=data.price_type.differencial_values[x].value;
                                        round_for=data.price_type.differencial_values[x].round_for;
                                    }
                                }
                            }
                        }
                        
                        zakaz.details[i].sale_price=(Math.ceil((parseFloat(zakaz.details[i].price)-zakaz.details[i].price/100*skidka)/round_for)*round_for).toFixed(2);
                        if(parseFloat(zakaz.details[i].sale_price)<parseFloat(zakaz.details[i].dealer_price)) zakaz.details[i].sale_price=zakaz.details[i].dealer_price;
                    }
                    zakaz.details[i].skidka=skidka;
                    if(typeof(zakaz.details[i].is_cashback)!="undefined" && zakaz.details[i].is_cashback){
                        zakaz.details[i].cashback=zakaz.details[i].price-zakaz.details[i].sale_price;
                        zakaz.details[i].sale_price_w_skidka=zakaz.details[i].sale_price;
                        zakaz.details[i].sale_price=zakaz.details[i].price;
                    }
                }
                print_zakaz_details(fast_sale);
            }
            else {
                var detlen=zakaz.details.length;
                var skidka=0;
                if(discount != 0)
                {
                    skidka = discount;
                }
                for(var i=0; i<detlen; i++){
                    zakaz.details[i].sale_price=(parseFloat(zakaz.details[i].price)-zakaz.details[i].price/100*skidka).toFixed(2);
                    if(parseFloat(zakaz.details[i].sale_price)<parseFloat(zakaz.details[i].dealer_price)) zakaz.details[i].sale_price=zakaz.details[i].dealer_price;
                }
                // reset_zakaz_details_price();
                print_zakaz_details(fast_sale);
            }
        }
    });
}

function reset_zakaz_details_price(){
  var detlen=zakaz.details.length;
  zakaz.company_dogovor_id=0;
  for(var i=0; i<detlen; i++){
    //if(zakaz['company_id']==-1) 
    //    basket_details[i].sale_price=parseFloat(basket_details[i].dealer_price).toFixed(2);
    //else 
        zakaz.details[i].sale_price=parseFloat(zakaz.details[i].price).toFixed(2);
        zakaz.details[i].skidka=0;
        zakaz.details[i].sale_price_w_skidka=parseFloat(zakaz.details[i].price).toFixed(2);
  }
  //print_zakaz_details();
}

function get_makezakaz_opts(){
    var defer=$.Deferred();
    //return defer.resolve();
    var send=[];
    send['type']="makezakaz";
    api_query_array("/api/index.php",send,"get_user_pref").then(function(data){
      if(data.status=="ok" && typeof(data.makezakaz)!="undefined" && data.makezakaz!=""){
        makezakaz_default_company.company_id=data.makezakaz.company_id;
        makezakaz_default_company.company_name=data.makezakaz.company_name;
      }
      defer.resolve();
    }
    );
    return defer.promise();
  }
  
  function save_makezakaz_opts(){
    var clientdata=[];
    clientdata['company_name']=$("#make_zakaz_div input[name=zakaz_company_name]").val();
    clientdata['company_id']=$("#make_zakaz_div input[name=zakaz_company_id]").val();
    var send=[];
    
    send['data']=JSON.stringify(Object.assign({},clientdata));
    //console.log(send.data);
    send['type']="makezakaz";
    api_query_array("/api/index.php",send,"save_user_pref").then(function(data){
      if(data.status=="ok"){
        bootbox.alert("Клиент по умолчанию успешно сохранен");
        get_makezakaz_opts();
      }
    });
    
  }

function make_zakaz(fast_zakaz){
    if(typeof(fast_zakaz)=="undefined") fast_zakaz=0;
    zakaz=new Array();
    var table='<div id="zakaz_details" style="min-width:850px;"></div>';
    table+='';
    table+='<div class="row">\
    <div class="col-sm-4"><b>Скидка в %:</b></div><div class="col-sm-2"><input type="text" class="form-control search_str input-sm" name="zakaz_discount" id="zakaz_discount" value="0" style="width: 110px;" onchange="discount('+fast_zakaz+')"></input>\
    <label style="position: absolute; top: 0.8em; left: 10.5em;" for="zakaz_discount" id="zakaz_discount_label" onclick="clear_discount('+fast_zakaz+');"></label>\
    </div>\
    <div class="col-sm-2">\
    <b>Доступно бонусов: </b></div><div class="col-sm-1"><b><span id="zakaz_company_cashback"></b></span></div>\
    <div class="col-sm-1">\
    <b>списать: </b></div><div class="col-sm-2"><input type="text" class="form-control search_str input-sm" id="zakaz_cashback_discount" name="zakaz_cashback_discount" value="0" style="width: 110px;" onchange="set_all_cashback('+fast_zakaz+');">\
    </div>\
    </div>';
    // table+='<label style="position: absolute; top: 0.8em; left: 21%;" for="zakaz_company" id="zakaz_company_label" onclick="get_zakaz_clients('+fast_zakaz+');"></label></div><br>';
    table+='<div class="row" style="padding:3px;"><div class="col-sm-4"><b>Выберите клиента: ';
    //if(fast_zakaz) 
        
    table+='</b></div>';
    table+='<div id="zakaz_company" class="col-sm-8"><div class="input-group input-group-sm" style="width:100%">\
        <input type="hidden" name="zakaz_company_id" id="zakaz_company_id"';
    //if(fast_zakaz) 
        table+='value="'+makezakaz_default_company.company_id+'">';
    //else table+='value="-1">';
    table+='<input type="text" class="form-control search_str input-sm" name="zakaz_company_name" id="zakaz_company_name"';
    //if(fast_zakaz) 
        table+='value="'+makezakaz_default_company.company_name+'"';
    //else table+='value="Себе на склад"';
    table+=' autocomplete="off" onclick="/*this.value=\'\'; get_zakaz_clients('+fast_zakaz+');*/" onkeyup="get_zakaz_clients('+fast_zakaz+');" title="начните набирать наименование клиента, или оставьте поле поле пустым чтобы увидеть всех клиентов">\
    <label style="position: absolute; top: 0.7em; /*right: 1.2em;*/" for="zakaz_company_name" id="zakaz_company_name_label" onclick="clear_search_order_text(\'zakaz_company_name\');get_zakaz_clients('+fast_zakaz+');"></label>';
    table+='';
    table+='<div class="input-group-btn" style="width:100px;">\
        <button title="выбор клиента из списка" class="btn btn-sm btn-default" onclick="clear_search_order_text(\'zakaz_company_name\');get_zakaz_clients('+fast_zakaz+');">...</button>\
        <button title="добавление нового клиента" class="btn btn-sm btn-default" onclick="add_new_client_in_zakaz();">+</button>\
        <button title="сохранить выбранного клиента, как  клиента по умолчанию" class="btn btn-sm btn-default" onclick="bootbox.confirm(\'Вы точно хотите сохранить данного клиента, клиентом по умолчанию?\',function(result){ if(result) save_makezakaz_opts();})"><img src="/new_images/diskette.png" style="width: 20px;"></button>\
        </div>\
    </div></div>\
    </div>\
    <div id="fast_new_client"></div><div id="zakaz_company_list"></div>';
    table+='<div class="row" style="padding:2px;"><div class="col-sm-4"><b>Выберите договор:</b></div>';
    table+='<div id="zakaz_company_dogovors_list" class="col-sm-8"></div></div>';
    table+='<div class="row" style="padding:2px;"><div class="col-sm-4"><b>Выберите автомобиль:</b></div>';
    table+='<div id="zakaz_company_cars_list" class="col-sm-8"></div></div>';
    table+='<div class="row" style="padding:2px;"><div class="col-sm-4"><b>Комментарий к заказу:</b></div>\
    <div class="col-sm-8"><textarea id="zakaz_comment" onchange="set_zakaz_comment();" class="form-control" rows="1"></textarea></div></div>';
    table+='<div class="row" style="padding:2px;"><div class="col-sm-4"><b>Канал продаж заказа:</b></div>\
    <div class="col-sm-8"><div id="select_zakaz_marketing_channel"></div><input type="text" id="zakaz_marketing_channel_name" onclick="select_zakaz_marketing_channel_name();" class="form-control input-sm" readonly>\
    <input type="hidden" name="marketing_channel_id" id="zakaz_marketing_channel_id"></div></div>';
    table+='<div class="row" style="padding:2px;"><div class="col-sm-4"><b>Выберите способ доставки:</b></div>\
    <div class="col-sm-8"><div class="form-check">\
    	<input class="form-check-input" type="radio" name="exampleRadios" id="exampleRadios1" value="option1" checked onclick="select_zakaz_delivery_address(1)">\
    	<label class="form-check-label" for="exampleRadios1" style="font-weight:normal;">\
	    Самовывоз\
	    </label>\
    </div>';
    table+='<div class="form-check" id="logistic_company_form_check">\
        </div>';
    if(!fast_zakaz){
        table+='<div class="form-check">\
    	<input class="form-check-input" type="radio" name="exampleRadios" id="exampleRadios2" value="option2" onclick="select_zakaz_delivery_address(2)">\
    	<label class="form-check-label" for="exampleRadios2" style="font-weight:normal;">\
    	    Выбрать адрес доставки\
    	</label>\
        </div>\
        <div class="form-check">\
            <input class="form-check-input" type="radio" name="exampleRadios" id="exampleRadios3" value="option3" onclick="select_zakaz_delivery_address(3)">\
            <label class="form-check-label" for="exampleRadios3" style="font-weight:normal;">\
                Новый адрес доставки\
            </label>\
        </div>';
    //}
    
    }
    table+='</div></div>';
    if(!fast_zakaz){
        table+='<div id="zakaz_fullfilment_div"></div>\
        <div id="select_logistic_config"></div>\
        <div id="zakaz_address_div"></div>';
    }
    table+='<div class="row" style="padding:2px;"><div class="col-sm-4"><b>Выберите тип оплаты:</b></div>\
    <div class="col-sm-8"><div class="row">\
    <div class="col-sm-4">\
    <div class="form-check">\
    	<input class="form-check-input" type="radio" name="payment_type" id="p_type1" value="option1" checked onclick="set_zakaz_payment_type(1)">\
    	<label class="form-check-label" for="p_type1" style="font-weight:normal;">\
    	    Наличными в офисе\
    	</label>\
    </div>\
    </div>\
    <div class="col-sm-4">'+(fast_zakaz==1?'внесено: <input type="text" id="fast_zakaz_cash_input" onchange="get_fast_zakaz_change();"  class="form-control1">':'')+'</div>\
    <div class="col-sm-4">'+(fast_zakaz==1?'сдача: <input type="text" id="fast_zakaz_cash_change" disabled class="form-control1">':'')+'</div>\
    </div>\
    <div class="form-check">\
    	<input class="form-check-input" type="radio" name="payment_type" id="p_type2" value="option2" onclick="set_zakaz_payment_type(2)">\
    	<label class="form-check-label" for="p_type2" style="font-weight:normal;">\
    	    Оплата банковской картой\
    	</label>\
    </div>\
    <div class="form-check">\
    	<input class="form-check-input" type="radio" name="payment_type" id="p_type3" value="option3" onclick="set_zakaz_payment_type(3)">\
    	<label class="form-check-label" for="p_type3" style="font-weight:normal;">\
    	    Наличными курьеру при доставке\
    	</label>\
    </div>\
    <div class="form-check">\
    	<input class="form-check-input" type="radio" name="payment_type" id="p_type4" value="option4" onclick="set_zakaz_payment_type(4)">\
    	<label class="form-check-label" for="p_type4" style="font-weight:normal;">\
    	    Безналичная оплата (по счету)\
    	</label>\
    </div>\
    <div class="form-check">\
    	<input class="form-check-input" type="radio" name="payment_type" id="p_type6" value="option6" onclick="set_zakaz_payment_type(6)">\
    	<label class="form-check-label" for="p_type6" style="font-weight:normal;">\
    	    Оплата по QR коду (СБП)\
    	</label>\
    </div>\
    <div class="form-check">\
    	<input class="form-check-input" type="radio" name="payment_type" id="p_type7" value="option7" onclick="set_zakaz_payment_type(7)">\
    	<label class="form-check-label" for="p_type7" style="font-weight:normal;">\
    	    Перевод на карту\
    	</label>\
    </div></div></div>\
    '+(fast_zakaz==1?'<input type="checkbox" id="fiscalize_notPrintCheck"> Не печатать чек <br>':'')+(fast_zakaz==1?'<input type="checkbox" id="dont_fiscalize"> Не фискализировать <br>':'')+'\
    <br>\
    <div class="row"><div class="col-sm-4"></div><div class="col-sm-8"><button class="btn btn-default btn-sm" onclick="close_window(\'make_zakaz\')">Отмена</button>';
    table+=' <button class="btn btn-primary btn-sm" onclick="save_basket_zakaz('+fast_zakaz+');" id="make_zakaz_button"';
    if(fast_zakaz) table+=' title="Нажатие на эту кнопку сделает 3 действия: примет оплату с клиента, спишет детали со склада и создаст документ Продажа"';
    table+='>Оформить заказ</button></div></div>';
    create_window("make_zakaz_div","Оформление заказа","make_zakaz",table);
    save_basket();
    for(let z of basket_details){ // обнулим, могут остаться от предыдущего клиента
        z.skidka=0;
        z.is_cashback=0;
        z.cashback=0;
    }
    //select_zakaz_sklad(fast_zakaz);
    print_zakaz_details(fast_zakaz);
    place_to_center("make_zakaz_div");
    set_zakaz_company(fast_zakaz);
}

function get_fast_zakaz_change(){
    var zakaz_sum=0;
    for (let i in zakaz.details){
        zakaz_sum+=parseFloat(zakaz.details[i].sale_price)*parseFloat(zakaz.details[i].count);
    }
    var input_cash=parseFloat($("#fast_zakaz_cash_input").val());
    if(isNaN(input_cash)) input_cash=0;
    var zakaz_cashback_discount=$("#zakaz_cashback_discount").val();
    zakaz_sum-=zakaz_cashback_discount;
    if(zakaz_sum<0) zakaz_sum=0;
    var change=input_cash-zakaz_sum;
    $("#fast_zakaz_cash_change").val(change<0?0:change);
}

function discount(fast_sale=0) {
    var discount = $('#zakaz_discount').val();
    set_zakaz_company_dogovor(fast_sale);
}

function clear_discount(fast_sale=0){
    $('#zakaz_discount').val(0);
    set_zakaz_company_dogovor(fast_sale);
}

function select_zakaz_delivery_address(id){
    switch(id){
	case 1:
        $("#zakaz_fullfilment_div").html('');
        select_zakaz_sklad();
	    break;
	case 2:
        select_z_delivery_address();
        select_z_fullfilment();
	    break;
	case 3:
        new_z_delivery_address();
        select_z_fullfilment();
	    break;
    case 4:
        select_z_fullfilment();
        $("#zakaz_address_div").html('');
        select_delivery_config();
        break;
    }
}

function fill_clients_fields_dostavista(){
    var id_client = $('#zakaz_company_id').val();
    var send = [];
    send['company_id'] = id_client;
    api_query_array("/api/index.php",send,"get_company").then(function(data){
        $("#nameDelivery").val(data.name);
        mask.value = data.mphone.substring(1);
        if (data.delivery_addresses.length > 1){
            var arrayAddressDelivery = [];
            for (let i = 0; i < data.delivery_addresses.length; ++i){
                arrayAddressDelivery[i] = ({text: data.delivery_addresses[i].delivery_address, value: data.delivery_addresses[i].id});
            }
            bootbox.prompt({
                title: "Выберите адрес доставки",
                inputType: 'select',
                inputOptions: arrayAddressDelivery,
                callback: function (result) {
                    zakaz['delivery_type_id'] = result;
                    $("#addressDelivery").val(data.delivery_addresses.find(z=>z.id==result).delivery_address);
                    $("#addressDelivery").focus();
                }
            });
        }else if (data.delivery_addresses.length == 1){
            zakaz['delivery_type_id'] = data.delivery_addresses[0].id;
            $("#addressDelivery").val(data.delivery_addresses[0].delivery_address);
            $("#addressDelivery").focus();
        }
        
    });
}

function fill_clients_fields_cdek(){
    var id_client = $('#zakaz_company_id').val();
    var send = [];
    send['company_id'] = id_client;
    api_query_array("/api/index.php",send,"get_company").then(function(data){
        $("#nameDelivery").val(data.name);
        mask.value = data.mphone.substring(1);
    });
}

var logistic_configs;
async function select_delivery_config(){
    let str = '<hr>';
    zakaz['delivery_type']=4;
    await api_query("/api/index.php","some_form","get_name_logistic_config").then(function(data){
        console.log(data);
        if(data.status == "err"){
            return;
        }else{
            logistic_configs = data.logistic_configs;
            str += '<b>Выберите конфиг</b><select class="form-control" id="logistic_config_id">';
            str += '<option value="0" selected style="display: none;">Не выбрано</option>';
            for (let i = 0; i < data.logistic_configs.length; ++i){
                str+='<option value="'+data.logistic_configs[i].id+'">'+data.logistic_configs[i].config_name+'</option>';
            }
            str += '</select>'
            str += '<br>';

            $("#select_logistic_config").html(str);
        }
    }); 

    // Function to handle option selection
    $("#logistic_config_id").change(function() {
        let selected_config_id = $(this).val();
        let logistic_id = logistic_configs.find(config => config.id === selected_config_id).logistics_id;
        if(logistic_id == 1){
            select_dostavista();
            fill_clients_fields_dostavista();
        }

        if(logistic_id == 2){
            select_cdek();
            fill_clients_fields_cdek();
        }
    });
}

var addressSklad;
var coordinatesSklad;
var addressDelivery;
var coordinatesDelivery;
var mask;
async function select_dostavista(){
    let str = '<hr>';
    zakaz['delivery_type']=4;

    str += '<b>Адрес склада</b><input class="form-control search_str" type="text" id="addressSklad"/>';
    str += '<br>';
    str += '<b>Адрес доставки</b><input class="form-control search_str" type="text" id="addressDelivery"/>';
    str += '<br>';
    str += '<b>Имя получателя</b><input class="form-control search_str" type="text" id="nameDelivery"/>';
    str += '<br>';
    str += '<b>Номер получателя</b><input class="form-control search_str" type="text" id="numberphoneDelivery" placeholder="+7(000)000-00-00"/>';
    str += '<br>';
    str += '<b>Укажите категорию товара (например, «видеорегистратор»), а не его модель, которая ничего не говорит курьеру. Укажите габариты отправления, если необходимо</b>';
    str += '<textarea id="matter_dostavista" class="form-control">Автозапчасть</textarea>';
    str += '<br>';
    str += '<b>Типы транспорта</b>';
    str += '<select id="vehicle_type_id" class="form-control">';
    str += '<option value="1">Легковой автомобиль / джип / пикап (до 500 кг).</option>';
    str += '<option value="2">Каблук (до 700 кг).</option>';
    str += '<option value="3">Микроавтобус / портер (до 1000 кг).</option>';
    str += '<option value="4">Газель (до 1500 кг).</option>';
    str += '<option value="5">Грузовой автомобиль.</option>';
    str += '<option value="6">Пеший курьер.</option>';
    str += '<option value="7">Легковой автомобиль.</option>';
    str += '</select>';
    str += '<br>';
    str += '<b>Общий вес отправления, кг.(Целое число)</b>';
    str += '<input type="number" class="form-control search_str" id="total_weight_kg" value="0" style="width: 20%">';
    str += '<br>';
    str += '<b>Отправлять SMS уведомления о статусе заказа</b><input type="checkbox" id="is_client_notification_enabled">';
    str += '<br>';
    str += '<b>Отправлять SMS с интервалом прибытия и телефоном курьера</b><input type="checkbox" id="is_contact_person_notification_enabled">';
    str += '<br>';
    str += '<b>Требуемое число грузчиков (включая водителя). Максимум 11 человек, минимум 0.</b>';
    str += '<input type="number" class="form-control search_str" id="loaders_count" value="0" style="width: 20%">';
    str += '<div id="result_amount" style="float:right;"></div>'
    str += '<hr>';
    str += '<button type="button" onclick="calc_dostavista()" class="btn btn-warning center-block" style="float:right;">Рассчитать</button><br>';
    $("#zakaz_address_div").html(str);
    var element = document.getElementById('numberphoneDelivery');
    var maskOptions = {
        mask: '+7(000)000-00-00',
        lazy: false
    } 
    mask = new IMask(element, maskOptions);
    addressSklad = new ymaps.SuggestView('addressSklad', {results: 5});
    addressSklad.events.add("select", function(e) {
        ymaps.geocode(e.get('item').value).then(
            async function (res){
                coordinatesSklad = res.geoObjects.get(0).geometry.getCoordinates();
            },
            function (err) {
                // обработка ошибки
                alert('Обратитесь в службу поддержки');
            }
        );
    })
    addressDelivery = new ymaps.SuggestView('addressDelivery', {results: 5});
    addressDelivery.events.add("select", function(e) {
        ymaps.geocode(e.get('item').value).then(
            async function (res){
                coordinatesDelivery = res.geoObjects.get(0).geometry.getCoordinates();
            },
            function (err) {
                // обработка ошибки
                alert('Обратитесь в службу поддержки');
            }
        );
    })   
}


function cdek_delivery_open(where="from"){
    $("#pvzWhere").val(where);
    if(typeof(window.widget)=="undefined"){
        window.widget = new window.CDEKWidget({
            popup: true,
            root: 'cdek-map-to',
            apiKey: '297f0577-9695-4bdf-a0af-75f2d9a9c42c',
            servicePath: '/CdekService.php?id=' + $("#logistic_config_id").val(),
            defaultLocation: 'Казань',
            onChoose: function(_type, tariff, address) {
                setPVZadress(address.code,address.address,address.postal_code);
                this.close();
            }
        });
    }
    window.widget.open();
}

function setPVZadress(code,addr, postal_code){
    let where = $("#pvzWhere").val();
    switch(where){
        case "to":
            $('[id="pvzTo"]').val(code);
            $('[id="addressDelivery"]').val(addr);
            $('[id="postalCodeTo"]').val(postal_code);
            break;
        case "from":
            $('[id="pvzFrom"]').val(code);
            $('[id="addressSklad"]').val(addr);
            $('[id="postalCodeFrom"]').val(postal_code);
            break;
    }
}

function select_cdek(){
    let str = '<hr>';
    zakaz['delivery_type'] = 4;
    zakaz['details'] = []; // Array to store details data
    api_query("/api/index.php", "some_form", "get_details_weights").then(function(data) {
        // console.log(data);
        if (data.status == "err") {
            return;
        } else {

            
           
            str += '<div class="table-responsive">';
            str += '<table class="table table-bordered">';
            str += '<thead><tr><th>Артикул</th><th>Вес</th></tr></thead>';
            str += '<tbody>';
            
            data.parameters.forEach(function(parameter) {
                str += '<tr>';
                str += '<td>' + parameter.article + ', ' + parameter.brand + '</td>';
                str += '<td><input type="text" class="form-control weight_input" data-detail-id="' + parameter.detail_id + '" value="' + parameter.weight + '"></td>';
                str += '</tr>';
                
                // Store the details data in the zakaz object
                // zakaz['details'].push({detail_id: parameter.detail_id, article: parameter.article, weight: parameter.weight});
            });
            
            str += '</tbody>';
            str += '</table>';
            str += '</div>';
    
            str += '<b>Отправить</b><input class="form-control search_str" onclick="cdek_delivery_open(\'from\')" type="text" id="addressSklad"/>';
            str += '<br>';
            str += '<b>Доставить</b><input class="form-control search_str" type="text" onclick="cdek_delivery_open(\'to\')" id="addressDelivery"/>';
            str += '<br>';
            str += '<input type="hidden" id="pvzFrom" />';
            str += '<input type="hidden" id="pvzTo" /><input type="hidden" id="pvzWhere" />';
            str += '<input type="hidden" id="postalCodeFrom" />';
            str += '<input type="hidden" id="postalCodeTo" />';

            str += '<div class="form-group">';
            str += '<label for="nameDelivery">Имя получателя</label>';
            str += '<input type="text" class="form-control" id="nameDelivery"/>';
            str += '</div>';
    
            str += '<div class="form-group">';
            str += '<label for="numberphoneDelivery">Номер получателя</label>';
            str += '<input type="text" class="form-control" id="numberphoneDelivery" placeholder="+7(000)000-00-00"/>';
            str += '</div>';
    
            str += '<div class="form-group">';
            str += '<label for="comment_input">Комментарий</label>';
            str += '<textarea class="form-control" id="comment_input"></textarea>';
            str += '</div>';
    
            str += '<div class="checkbox">';
            str += '<label><input type="checkbox" id="is_client_notification_enabled">Доставка в счет клиента</label>';
            str += '</div>';
            str += '<div id="result_amount"></div>'
            str += '<button type="button" onclick="calc_cdek()" class="btn btn-warning">Рассчитать</button>';

            $("#zakaz_address_div").html(str);
    
            var element = document.getElementById('numberphoneDelivery');
            var maskOptions = {
                mask: '+7(000)000-00-00',
                lazy: false
            };
            mask = new IMask(element, maskOptions);
        }
    });
}

function calc_dostavista(){
    let tmp = check_and_field_dostavista();
    if (tmp == false){
        return;
    }
    api_query_array("/api/index.php",dostavista_send,"calculate_shipping_cost_Dostavista").then(function (data){
        $('#result_amount').html('<b>Стоимость доставки:'+data.payment_amount+'</b>');
    });
}

function calc_cdek(){
    let tmp = check_and_field_cdek();
    if (tmp == false){
        return;
    }
    api_query_array("/api/index.php",cdek_send,"shipping_cost_calculation_cdek").then(function (data){
        if(data.status == 'ok')
            $('#result_amount').html('<b>Стоимость доставки:'+data.total_sum+'</b>');
        else{
            alert(data.msg);
        }
    });
}

var dostavista_send;
function check_and_field_dostavista(){
    console.log(dostavista_send);
    dostavista_send = Array();
    if ($('#addressSklad').val() == ''){
        alert("Заполните Адрес склада!");
        return false;
    }else{
        dostavista_send['addressSklad'] = $('#addressSklad').val();
        dostavista_send['coordinatesSklad'] = coordinatesSklad;
    }
    if ($('#addressDelivery').val() == ''){
        alert("Заполните Адрес доставки!");
        return false;
    }else{
        dostavista_send['addressDelivery'] = $('#addressDelivery').val();
        dostavista_send['coordinatesDelivery'] = coordinatesDelivery;
    }
    if ($('#matter_dostavista').val() == ""){
        alert("Заполните категории товаров!");
        return false;
    }else{
        dostavista_send['comment'] = $('#matter_dostavista').val();
    }
    dostavista_send['delivery_type']= $('#vehicle_type_id').val();
    if (parseInt($('#total_weight_kg').val()) > 0){
        dostavista_send['total_weight_kg'] = parseInt($('#total_weight_kg').val());
    }else{
        alert("Заполните сколько кг весит поссылка.");
        return false;
    }

    dostavista_send['is_client_notification_enabled'] = $('#is_client_notification_enabled').is(':checked');
    dostavista_send['is_contact_person_notification_enabled'] = $('#is_contact_person_notification_enabled').is(':checked');
    let count_loaders = parseInt($('#loaders_count').val());
    if (count_loaders >= 0 && count_loaders <= 11){
        dostavista_send['loaders_count'] = count_loaders;
    }else{
        alert('Укажите допустимое количество грузчиков!');
        return false;
    }
    if ($("#nameDelivery").val() == ""){
        alert('Укажите имя получателя!');
        return false;
    }else{
        dostavista_send['nameDelivery'] = $("#nameDelivery").val();
    }
    dostavista_send['numberphoneDelivery'] = $("#numberphoneDelivery").val();
    dostavista_send['logistic_config_id'] = $('#logistic_config_id').val();
    dostavista_send['details'] = zakaz.details;
    return true;
}

var cdek_send;
function check_and_field_cdek(){
    const nameDelivery = document.getElementById("nameDelivery").value;
    const numberphoneDelivery = document.getElementById("numberphoneDelivery").value;
    const addressSklad = document.getElementById("postalCodeFrom").value;
    const addressDelivery = document.getElementById("postalCodeTo").value;
    const commentInput = document.getElementById("comment_input").value;
    
    cdek_send = {
        nameDelivery: nameDelivery,
        numberphoneDelivery: numberphoneDelivery,
        addressSklad: addressSklad,
        addressDelivery: addressDelivery,
        commentInput: commentInput,
        details: []
    };

    let isDetailsFilled = true;

    // Add details data to the orderData array
    document.querySelectorAll('.weight_input').forEach(function(input) {
        const detailId = input.getAttribute('data-detail-id');
        const weight = input.value;
        const article = input.parentElement.previousElementSibling.innerHTML;
        if (!weight) {
            alert("Заполните данные по весу товара в таблице.");
            isDetailsFilled = false;
            return;
        }

        let detailData = {
            detail_id: detailId,
            article: article,
            weight: weight
        };

        cdek_send.details.push(detailData);
    });

    if (!isDetailsFilled) {
        return false;
    }

    if (nameDelivery && numberphoneDelivery && addressSklad && addressDelivery) {
        return true;
    } else {
        alert("Пожалуйста заполните все обязательные поля.");
        return false;
    }
}

function select_zakaz_sklad(fast_zakaz){
    if(typeof(fast_zakaz)=="undefined") fast_zakaz=0;
    var my_sklad_id=$("#my_sklad").val();
    var action="get_delivery_sklads";
    if(zakaz['company_id']==-1) action="get_sklads";
    api_query("/api/index.php","some_form",action).then(function(data){
    	var table="<div class='row' style='padding:2px;'><div class='col-sm-4'><b>Выберите место выдачи: </b></div><div class='col-sm-8'>";
        if(typeof(data.sklads)=="undefined" || data.sklads.length==0){
            bootbox.alert("У Вас нет ни одного склада выдачи, перейдите во вкладку склад, отредактируйте склад, укажите 'Является складом выдачи'");
            $("#make_zakaz_button").attr("disabled","disabled");
        }
    	for(var i=0; i<data.sklads.length; i++){
    	    table+='\
    	    <div class="form-check">\
                <input class="form-check-input" ';
            if(data.sklads[i].id==my_sklad_id){
                table+='checked="checked"';
                set_zakaz_sklad_address(data.sklads[i].id,data.sklads[i].address);
            }
            /*if(i==0){
                if(!fast_zakaz) {
                    table+='checked="checked"';
                    set_zakaz_sklad_address(data.sklads[i].id,data.sklads[i].address);
                }
            }
            else {
                table+='';
            }*/
            table+=' type="radio" name="zakaz_address" id="zakaz_address_'+data.sklads[i].id+'" value="'+data.sklads[i].id+'" onclick="set_zakaz_sklad_address('+data.sklads[i].id+',\''+data.sklads[i].address+'\')">\
        		<span class="form-check-label" for="zakaz_address_'+data.sklads[i].id+'">\
        		'+data.sklads[i].name+' адрес:'+data.sklads[i].address+'\
        		</span>\
    	    </div>\
    	    ';
    	}
        table+='</div></div>';
    	$("#zakaz_address_div").html(table);
        place_to_center("make_zakaz_div");
    });
}

function select_z_delivery_address(){
    var send=new Array();
    send['company_id']=zakaz['company_id'];
        api_query_array("/api/index.php",send,"get_delivery_addresses").then(function(data){
            var table="<div class='row'><div class='col-sm-4'> <b>Выберите адрес доставки:</b> </div>";
        if(data.delivery_addresses.length==0){
            table+='<div class="col-sm-8"><b>Клиент не имеет зарегистрированных адресов доставки </b> <button class="btn btn-primary" onclick="$(\'#exampleRadios3\').click();">добавить адрес доставки</button></div>';
        }
        else {
            table+='<div class="col-sm-8">';
            for(var i=0; i<data.delivery_addresses.length; i++){
                table+='\
                <div class="form-check">\
                    <input class="form-check-input" type="radio" name="zakaz_delivery_address" id="zakaz_delivery_address_'+data.delivery_addresses[i].id+'" value="'+data.delivery_addresses[i].id+'" onclick="set_zakaz_delivery_address('+data.delivery_addresses[i].id+',\''+data.delivery_addresses[i].delivery_address+'\')">\
                    <label class="form-check-label" for="zakaz_delivery_address_'+data.delivery_addresses[i].id+'">\
                    '+data.delivery_addresses[i].delivery_address+'\
                    </label>\
                </div>\
                ';
            }
            table+='</div>';
        }
        table+='</div>';
        $("#zakaz_address_div").html(table);
    });
}

function select_z_fullfilment(){
    var send=new Array();
    send['company_id']=zakaz['company_id'];
    api_query_array("/api/index.php",send,"get_fullfilment_id").then(function(data){
        var table="<div class='row'>";
        if(typeof(data.fullfilments)!="undefined"){
           if(data.fullfilments.length<2){
                table+="<div class='col-sm-4'> <b>Склад формирования заказа:</b></div>";
                table+="<div class='col-sm-8'> <b>"+data.fullfilments[0].name+"</b></div>";
                set_zakaz_fullfilment(data.fullfilments[0].id);
                set_address_zakaz_sklad(data.fullfilments[0].id);
           } 
           else {
                table+="<div class='col-sm-4'> <b>Выберите склад формирования заказа:</b> </div>";
                table+="<div class='col-sm-8'><select name=\"zakaz_fullfilment_id\" id=\"zakaz_fullfilment_id\" onchange=\"set_zakaz_fullfilment();\" class='form-control input-sm'>"
                // table+="<option value=\"0\">Не выбран</option>";
                for(var i=0; i<data.fullfilments.length; i++){
                    if($("#my_sklad").val() == data.fullfilments[i].id){
                        table+="<option value=\""+data.fullfilments[i].id+"\" selected>"+data.fullfilments[i].name+" </option>";
                        set_zakaz_fullfilment(data.fullfilments[i].id);
                    }
                    else{
                        table+="<option value=\""+data.fullfilments[i].id+"\">"+data.fullfilments[i].name+"</option>";
                    }
                }
                table+="</select>";
                table+='</div>';
           }
        }
        else {
            if(typeof(data.sklads)!="undefined"){
                if(data.sklads.length<2){
                    table+="<div class='col-sm-4'> <b>Склад формирования заказа:</b></div>";
                    table+="<div class='col-sm-8'> <b>"+data.sklads[0].name+"</b></div>";
                    set_zakaz_fullfilment(data.sklads[0].id);
                    set_address_zakaz_sklad(data.sklads[0].id);
               } 
               else {
                    table+="<div class='col-sm-4'> <b>Выберите склад формирования заказа:</b> </div>";
                    table+="<div class='col-sm-8'><select name=\"zakaz_fullfilment_id\" id=\"zakaz_fullfilment_id\" onchange=\"set_zakaz_fullfilment();\" class='form-control input-sm'>"
                    // table+="<option value=\"0\">Не выбран</option>";
                    for(var i=0; i<data.sklads.length; i++){
                        if($("#my_sklad").val() == data.fullfilments[i].id){
                            table+="<option value=\""+data.fullfilments[i].id+"\" selected>"+data.fullfilments[i].name+"</option>";
                            set_zakaz_fullfilment(data.fullfilments[i].id);
                        }
                        else{
                            table+="<option value=\""+data.fullfilments[i].id+"\">"+data.fullfilments[i].name+"</option>";
                        }
                    }
                    table+="</select></div>";
               } 
            }
        }
       //var table="<br> Выберите склад формирования заказа: ";
        table+='</div>';
    	$("#zakaz_fullfilment_div").html(table);
    });
}

function new_z_delivery_address(){
    var table="<div class='row'><div class='col-sm-4'><b>Введите новый адрес доставки:</b></div><div class='col-sm-8'><textarea id='new_delivery_address' onchange='set_new_z_delivery_address();' class='form-control'></textarea></div></div>";
    $("#zakaz_address_div").html(table);
}

function set_new_z_delivery_address(){
    zakaz['delivery_type']=2;
    zakaz['delivery_address']=$('#new_delivery_address').val();
    zakaz['is_new_address']=1;
    zakaz['delivery_type_id']=0;
}

function set_zakaz_sklad_address(id,address){
    zakaz['delivery_type']=1;
    zakaz['delivery_address']=address;
    zakaz['delivery_type_id']=id;
}

function set_zakaz_delivery_address(id,address){
    zakaz['delivery_type']=2;
    zakaz['delivery_address']=address;
    zakaz['delivery_type_id']=id;
    zakaz['is_new_address']=0;
}

function set_zakaz_fullfilment(id){
    if(typeof(id)!="undefined"){
        zakaz['fullfilment_id']=id;
    }
    else {
        id=$("#zakaz_fullfilment_id").val();
        zakaz['fullfilment_id']=id;
    }
    set_address_zakaz_sklad(id);
}

function set_address_zakaz_sklad(id){
    if (id != 0 && $('#exampleRadios4').is(':checked')){
        api_query_array("/api/index.php",send,"get_fullfilment_address").then(function(data){
            console.log(data.fullfilments.find(z=>z.id==zakaz['fullfilment_id']));
            console.log(addressSklad);
            $('#addressSklad').val(data.fullfilments.find(z=>z.id==zakaz['fullfilment_id']).address);
            $('#addressSklad').focus();
            // addressSklad.value= data.fullfilments.find(z=>z.id==zakaz['fullfilment_id']).address; //Придумать как установить значение в строку
            ymaps.geocode(data.fullfilments.find(z=>z.id==zakaz['fullfilment_id']).address).then(
                async function (res){
                    coordinatesSklad = res.geoObjects.get(0).geometry.getCoordinates();
                    console.log(coordinatesSklad);
                },
                function (err) {
                    // обработка ошибки
                    alert('Обратитесь в службу поддержки');
                }
            );
        });
    }
}

function set_zakaz_payment_type(id){
    zakaz['payment_type']=id;
}

function set_zakaz_comment(){
    zakaz['comment']=$("#zakaz_comment").val();
}

function set_zakaz_marketing_channel_name(name,id,edit_zakaz=0){
    if(!edit_zakaz) zakaz['marketing_channel_name']=name;
    $("#zakaz_marketing_channel_name").val(name);
    if(!edit_zakaz) zakaz['marketing_channel_id']=id;
    $("#zakaz_marketing_channel_id").val(id);
    $("#select_zakaz_marketing_channel").html('');
}

function select_zakaz_marketing_channel_name(edit_zakaz=0){
    api_query("/api/index.php","some_form","get_marketing_channels").then(function(data){
        if(data.status=="ok"){
            var table='<table class="table table-hover"><tbody>';
            if(data.marketing_channels.length<1){
                table+='<tr><td>У вас не настроены каналы продаж, добавьте хотя бы 1 канал продаж (Настройки -> Каналы продаж -> добавить)</td></tr>'
            }
            else {
                for(let i in data.marketing_channels){
                    table+='<tr><td onclick="set_zakaz_marketing_channel_name(\''+data.marketing_channels[i].name+'\','+data.marketing_channels[i].id+','+edit_zakaz+')">'+data.marketing_channels[i].name+'</td></tr>';
                }
            }
            table+='</table>';
            create_window("select_zakaz_marketing_channel_div","Выберите канал продаж","select_zakaz_marketing_channel",table)
        }
    })
}

function save_basket_zakaz(fast_sale){
    // if($('#exampleRadios4').is(':checked') && !check_and_field_dostavista()) return; 
    $.blockUI({ css: { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .5, 
        color: '#fff'
        },
        message: 'Оформляем заказ...'
      });
    if ($('#exampleRadios4').is(':checked')) {
        zakaz['delivery_address'] = $('#addressDelivery').val();
        zakaz['is_new_address'] = 0;
    }
    if(typeof(fast_sale)=="undefined" || parseInt(fast_sale)==0) fast_sale=0;
    else if(fast_sale==1) zakaz['fast_sale']=1;
    if(typeof(zakaz['payment_type'])=="undefined") zakaz['payment_type']=1;
    zakaz['sum']=0;
    for (let i in zakaz.details){
        zakaz['sum']+=parseFloat(zakaz.details[i].sale_price)*parseFloat(zakaz.details[i].count);
    }
    //if(typeof(zakaz['notPrintCheck'])=="undefined") zakaz['notPrintCheck']=0;
    if(typeof($("#fiscalize_notPrintCheck").prop("checked"))!="undefined" && $("#fiscalize_notPrintCheck").prop("checked")){
        zakaz['notPrintCheck']=true;
    }
    if(typeof($("#dont_fiscalize").prop("checked"))!="undefined" && $("#dont_fiscalize").prop("checked")){
        zakaz['dont_fiscalize']=true;
    }
    zakaz['zakaz_cashback_discount']=$("#zakaz_cashback_discount").val();
    zakaz['car_id']=$("#zakaz_company_car_id").val();
    api_query_array("/api/index.php",zakaz,"save_zakaz").then(function(data){
        $.unblockUI();
    	if(data.status=="ok") {
            if(zakaz.payment_type==2 && fast_sale==1){
                api_query("/api/index.php","some_form","get_active_kassas").then(function(data1){
                    var send=new Array();
                    send['zakaz_id']=data.zakaz_id;
                    send['company_id']=zakaz.company_id;
                    send['payment_target']="Оплата заказа №"+data.zakaz_id;
                    send['payment_direction']=1;
                    send['is_advance']=0;
                    send['dont_fiscalize']=0;
                    send['summ']=zakaz.sum;
                    send['payment_type']=zakaz.payment_type;
                    send['fast_sale']=1;
                    if(typeof(zakaz['notPrintCheck'])!="undefined") {
                        send['notPrintCheck']=zakaz['notPrintCheck'];
                    }
                    $.unblockUI();
                    if(data1.kassas.length>0){ //&& send['no_fiscalize']==false){
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
                        payData.ReceiptNumber=data.zakaz_id;
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
                        PayByPaymentCard(payData,send);
                    }
                    }
                })
            }
            let dostavista_bool = $('#exampleRadios4').is(':checked');
            if(typeof(data.payment)!="undefined"){
                if(typeof(data.payment.check_data)!="undefined" && data.payment.check_data!==null){
                    if(parseInt(data.payment.check_data.paymentId)>0){
                        fiscalize_payment(parseInt(data.payment.check_data.paymentId),"",zakaz["notPrintCheck"]);
                    }
                }
            }
    	    $('#make_zakaz').html('');
    	    //get_basket_details();
            get_basket_count();
            if(typeof(data.zakaz_id)!="undefined" && parseInt(data.zakaz_id)>0){
                if(zakaz.company_id==-1) open_zakaz(data.zakaz_id,'to_sklad');
                else open_zakaz(data.zakaz_id);
                if (dostavista_bool){
                    dostavista_send['zakaz_id_in_sort1'] = data.zakaz_id;
                    api_query_array("/api/index.php",dostavista_send,"save_order_logistic").then(function (data){

                    });
                }
            }
    	}
    });
}

function add_in_zakaz_fill_form(type){
    var send=new Array();
    if (type=="inn") send['inn']=$("#add_in_zakaz_company_inn").val();
    if (type=="name") send['org_name']=$("#add_in_zakaz_company_name").val();
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
          $("#add_in_zakaz_company_kpp").val(data.suggestions[0].data.kpp);
          $("#add_in_zakaz_company_inn").val(data.suggestions[0].data.inn);
          $("#add_in_zakaz_company_ogrn").val(data.suggestions[0].data.ogrn);
		  $("#add_in_zakaz_company_okpo").val(data.suggestions[0].data.okpo);
		  $("#add_in_zakaz_company_okved").val(data.suggestions[0].data.okved);
          if (!jQuery.isEmptyObject(data.suggestions[0].data.address.value))
            $("#add_in_zakaz_company_uraddress").val(data.suggestions[0].data.address.value);
          else
            $("#add_in_zakaz_company_uraddress").val(data.suggestions[0].data.address.data.source);
          $("#add_in_zakaz_company_name").val(data.suggestions[0].value);
		  if(typeof(data.suggestions[0].data.management)!="undefined" && data.suggestions[0].data.management!==null){
			$("#add_in_zakaz_company_ruk").val(data.suggestions[0].data.management.name);
			$("#add_in_zakaz_company_rukdol").val(data.suggestions[0].data.management.post);
		  }
      	}
      	if (count>1){
            add_in_zakaz_select_company_from_list(data);
      	}
    });
}

function add_in_zakaz_select_company_from_list(data){
  var table='<table class="table table-hover"><thead><tr><th>Наименование организации</th><th>ИНН/КПП</th><th>Телефон</th><th>Руководитель</th></tr></thead><tbody>'
  $.each(data.suggestions,function(i, item){
    if(typeof(item.data.management) == 'undefined' || item.data.management === null) dirname="";
    else dirname=item.data.management.name;
    if (item.data.state.status=="ACTIVE") table+='<tr onclick="add_in_zakaz_set_company_data('+i+');">\
    <td>'+item.value+'</td>\
    <td nowrap>'+item.data.inn+(typeof(item.data.kpp)!="undefined"?" / "+item.data.kpp:"")+'</td>\
    <td>'+(item.data.phones===null?"":item.data.phones)+'</td>\
    <td>'+dirname+'</td></tr>';
  });
  table+='</tbody></table>';
  create_window_centered_blue("add_in_zakaz_company_company_list_for_select_div","Выберите компанию для автоматического заполнения данных","add_in_zakaz_company_company_list_for_select",table);
}

function add_in_zakaz_set_company_data(id){
  if (id>=0){
    $("#add_in_zakaz_company_kpp").val(companys_for_select.suggestions[id].data.kpp);
	$("#add_in_zakaz_company_inn").val(companys_for_select.suggestions[id].data.inn);
	$("#add_in_zakaz_company_ogrn").val(companys_for_select.suggestions[id].data.ogrn);
	$("#add_in_zakaz_company_okpo").val(companys_for_select.suggestions[id].data.okpo);
	$("#add_in_zakaz_company_okved").val(companys_for_select.suggestions[id].data.okved);
	if (!jQuery.isEmptyObject(companys_for_select.suggestions[id].data.address.value))
		$("#add_in_zakaz_address").val(companys_for_select.suggestions[id].data.address.value);
	else
		$("#add_in_zakaz_address").val(companys_for_select.suggestions[id].data.address.data.source);
	$("#add_in_zakaz_company_name").val(companys_for_select.suggestions[id].value);
	if(typeof(companys_for_select.suggestions[id].data.management)!="undefined" && companys_for_select.suggestions[id].data.management!==null){
		$("#add_in_zakaz_ruk").val(companys_for_select.suggestions[id].data.management.name);
		$("#add_in_zakaz_rukdol").val(companys_for_select.suggestions[id].data.management.post);
	}
	if(companys_for_select.suggestions[id].data.branch_type=="BRANCH"){
		$("#company_type").val(4);
		$("#main_orgs_list").parent().css("display","block");
	}
    $("#add_in_zakaz_company_company_list_for_select").html('');
  }
}

function change_add_in_zakaz_form(){
    var table='';
    var okopf=$("#add_in_zakaz_company_okopf").val();
    switch(parseInt(okopf)){
        case 3: table+='<div class="form-group row col-sm-12">\
            <label for="company_name" class="col-sm-4 col-form-label">ФИО</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_name" placeholder="ФИО" name="company_name" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_name" id="company_name_label" onclick="clear_search_order_text(\'company_name\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_mphone" class="col-sm-4 col-form-label">Телефон</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_mphone" placeholder="Телефон" name="company_mphone" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_mphone" id="company_name_label" onclick="clear_search_order_text(\'company_mphone\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_email" class="col-sm-4 col-form-label">E-MAIL</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_email" placeholder="E-MAIL" name="company_email" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_email" id="company_name_label" onclick="clear_search_order_text(\'company_email\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_vin" class="col-sm-4 col-form-label">VIN</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_vin" placeholder="VIN" name="company_vin" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_vin" id="company_name_label" onclick="clear_search_order_text(\'company_vin\');"></label>\
            </div>\
            </div>';
            break;
        case 2: table+='<div class="form-group row col-sm-12">\
            <label for="company_name" class="col-sm-4 col-form-label">Наименование организации</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_name" placeholder="Наименование организации" name="company_name" value=""  onchange="add_in_zakaz_fill_form(\'name\')"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_name" id="company_name_label" onclick="clear_search_order_text(\'company_name\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_inn" class="col-sm-4 col-form-label">ИНН</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_inn" placeholder="ИНН" name="company_inn" value="" onchange="add_in_zakaz_fill_form(\'inn\')">\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_kpp" class="col-sm-4 col-form-label">КПП</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_kpp" placeholder="КПП" name="company_kpp" value="">\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_uraddress" class="col-sm-4 col-form-label">Юр. адрес</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_uraddress" placeholder="Юр. адрес" name="company_uraddress" value="">\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_ogrn" class="col-sm-4 col-form-label">ОГРН</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_ogrn" placeholder="ОГРН" name="company_ogrn" value="">\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_okpo" class="col-sm-4 col-form-label">ОКПО</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_okpo" placeholder="ОКПО" name="company_okpo" value="">\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_okved" class="col-sm-4 col-form-label">ОКВЕД</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_okved" placeholder="ОКВЕД" name="company_okved" value="">\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_ruk" class="col-sm-4 col-form-label">Руководитель</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_ruk" placeholder="Руководитель" name="company_ruk" value="">\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_mphone" class="col-sm-4 col-form-label">Телефон</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_mphone" placeholder="Телефон" name="company_mphone" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_mphone" id="company_name_label" onclick="clear_search_order_text(\'company_mphone\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_email" class="col-sm-4 col-form-label">E-MAIL</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_email" placeholder="E-MAIL" name="company_email" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_email" id="company_name_label" onclick="clear_search_order_text(\'company_email\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_vin" class="col-sm-4 col-form-label">VIN</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_vin" placeholder="VIN" name="company_vin" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_vin" id="company_name_label" onclick="clear_search_order_text(\'company_vin\');"></label>\
            </div>\
            </div>';
            break;

        case 1: table+='<div class="form-group row col-sm-12">\
            <label for="company_name" class="col-sm-4 col-form-label">ФИО</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_name" placeholder="ФИО" name="company_name" value="" onchange="add_in_zakaz_fill_form(\'name\')">\
            <label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_name" id="company_name_label" onclick="clear_search_order_text(\'company_name\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_inn" class="col-sm-4 col-form-label">ИНН</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_inn" placeholder="ИНН" name="company_inn" value="" onchange="add_in_zakaz_fill_form(\'inn\')">\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_okpo" class="col-sm-4 col-form-label">ОКПО</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_okpo" placeholder="ОКПО" name="company_okpo" value="">\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_okved" class="col-sm-4 col-form-label">ОКВЕД</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_okved" placeholder="ОКВЕД" name="company_okved" value="">\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_mphone" class="col-sm-4 col-form-label">Телефон</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_mphone" placeholder="Телефон" name="company_mphone" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_mphone" id="company_name_label" onclick="clear_search_order_text(\'company_mphone\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_email" class="col-sm-4 col-form-label">E-MAIL</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_email" placeholder="E-MAIL" name="company_email" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_email" id="company_name_label" onclick="clear_search_order_text(\'company_email\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_vin" class="col-sm-4 col-form-label">VIN</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_vin" placeholder="VIN" name="company_vin" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_vin" id="company_name_label" onclick="clear_search_order_text(\'company_vin\');"></label>\
            </div>\
            </div>';
            break;
    }
    document.getElementById("add_company_in_zakaz_form").innerHTML=table;
}

function add_new_client_in_zakaz(){
    var send=[];
    send['price_type']=1;
    api_query_array("/api/index.php",send,"get_price_types").then(function(data){
        var table='<div class="form-group row col-sm-12">\
        <label for="company_okopf" class="col-sm-4 col-form-label">Тип клиента</label>\
        <div class="col-sm-8">\
        <select class="form-control" id="add_in_zakaz_company_okopf" placeholder="ФИО" name="company_okopf" onchange="change_add_in_zakaz_form();">\
        <option value="3">Физ. лицо</option>\
        <option value="1">Индивидуальный предприниматель</option>\
        <option value="2">Юр. лицо</option>\
        </select>\
        </div>\
        </div>\
        <div id="add_company_in_zakaz_form">\
            <div class="form-group row col-sm-12">\
            <label for="company_name" class="col-sm-4 col-form-label">ФИО</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="add_in_zakaz_company_name" placeholder="ФИО" name="company_name" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_name" id="company_name_label" onclick="clear_search_order_text(\'company_name\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_mphone" class="col-sm-4 col-form-label">Телефон</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="company_mphone" placeholder="Телефон" name="company_mphone" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_mphone" id="company_mphone_label" onclick="clear_search_order_text(\'company_mphone\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_email" class="col-sm-4 col-form-label">E-MAIL</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="company_email" placeholder="E-MAIL" name="company_email" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_email" id="company_email_label" onclick="clear_search_order_text(\'company_email\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_vin" class="col-sm-4 col-form-label">VIN</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="company_vin" placeholder="VIN" name="company_vin" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_vin" id="company_vin_label" onclick="clear_search_order_text(\'company_vin\');"></label>\
            </div>\
            </div>\
            <div class="form-group row col-sm-12">\
            <label for="company_auto_gov_num" class="col-sm-4 col-form-label">Гос. Номер</label>\
            <div class="col-sm-8">\
            <input type="text" class="form-control search_str" id="company_auto_gov_num" placeholder="Гос. номер" name="company_auto_gov_num" value=""><label style="position: absolute; top: 0.8em; right: 1.2em;" for="company_auto_gov_num" id="company_auto_gov_num_label" onclick="clear_search_order_text(\'company_auto_gov_num\');"></label>\
            </div>\
            </div>\
        </div>';

        table+='<div class="form-group row col-sm-12">\
        <label for="company_price_type" class="col-sm-4 col-form-label">Скидка</label>\
        <div class="col-sm-8">\
        <select class="form-control search_str" id="company_price_type" name="company_price_type">';
        table+='<option value="0">не выбрана</option>';
        for (var i=0; i<data.price_types.length; i++){
            table+='<option value="'+data.price_types[i].id+'">'+data.price_types[i].descr+'</option>';
        }
        table+='</select></div>\
        </div><div id="add_in_zakaz_company_company_list_for_select"></div>';

        table+='<button class="btn btn-primary" onclick="fast_save_company();" type="button">Сохранить</button>';
        create_window("fast_new_client_div","Быстрое заведение клиента","fast_new_client",table);
    });
}

function fast_save_company(){
	var send=new Array();
    send['okopf']=$("#fast_new_client select[name=company_okopf]").val();
	send['company_name']=$("#fast_new_client input[name=company_name]").val();
    send['company_inn']=$("#fast_new_client input[name=company_inn]").val();
    send['company_kpp']=$("#fast_new_client input[name=company_kpp]").val();
    send['company_ogrn']=$("#fast_new_client input[name=company_ogrn]").val();
    send['company_uraddress']=$("#fast_new_client input[name=company_uraddress]").val();
    send['company_okpo']=$("#fast_new_client input[name=company_okpo]").val();
    send['company_ruk']=$("#fast_new_client input[name=company_ruk]").val();
    send['company_okved']=$("#fast_new_client input[name=company_okved]").val();
	send['mphone']=$("#fast_new_client input[name=company_mphone]").val();
	send['email']=$("#fast_new_client input[name=company_email]").val();
	send['vin']=$("#fast_new_client input[name=company_vin]").val();
    send['auto_gov_num']=$("#fast_new_client input[name=company_auto_gov_num]").val();
    send['price_type']=$("#fast_new_client select[name=company_price_type]").val();
    api_query_array("/api/index.php",send,"fast_save_company").then(function(data){
        if(data.status=="ok"){
            if(typeof(data.companys)!="undefined" && data.companys.length>0){
                var table='Существуют клиенты с похожим именем, выберите существующего клиента или измените поле ФИО для уникальности\
                <table class="table table-hover"><thead><tr><th>ФИО</th><th>Телефон</th><th>E-MAIL</th></tr></thead><tbody>';
                var complen=data.companys.length;
                for(var i=0; i<complen; i++){
                    table+='<tr style="cursor:pointer;" onclick="select_client_in_zakaz('+data.companys[i].id+',\''+send['company_name']+'\')"><td>'+data.companys[i].name+'</td><td>'+data.companys[i].mphone+'</td><td>'+data.companys[i].email+'</td></tr>';
                }
                table+='</tbody></table>';
                bootbox.alert(table);
            }
            else {
                select_client_in_zakaz(data.company_id,send['company_name']);
            }
        }
    });
}

function select_client_in_zakaz(id,name){
    bootbox.hideAll();
    $("#fast_new_client").html('');
    set_zakaz_client(id,name,0);
    //get_zakaz_clients(1,id);
}

function change_basket_is_excise(basket_detail_id,detail_id){
    var send=[];
    send['basket_detail_id']=basket_detail_id;
    send['detail_id']=detail_id;
    send['is_excise']=$("#basket_detail_is_excise_"+basket_detail_id).prop("checked");
    
    api_query_array("/api/index.php",send,"set_excise_in_basket_detail").then(function(data){
        if(data.status=="ok"){
            for (var i in basket_details){
                if(parseInt(basket_details[i].id)==parseInt(basket_detail_id)){
                    basket_details[i].is_excise=(send['is_excise']?1:0);
                    break;
                }
            }
        }
    })
  }

  get_makezakaz_opts();
