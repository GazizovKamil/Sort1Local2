function add_company_site(){
    var table='';
    table+='<form id="new_company_site_form" onsubmit="event.preventDefault(); save_company_site();">\
    Укажите имя вашего сайта (hostname), например my.site.name\
    <div class="input-group input-group-sm">\
          <input required type="text" class="form-control input-sm" name="site_name" id="site_name" value="">\
        <div class="input-group-btn">\
            <button class="btn btn-default btn-sm" type="button" onclick="save_company_site();">Сохранить</button>\
        </div>\
      </div>\
    </form>';
    create_window_centered_blue("new_company_site_div","Введите адрес вашего сайта, привязанного к данной организации","new_company_site",table);
  }
  
function save_company_site(){
    api_query("/api/index.php","new_company_site_form","save_company_site").then(function(data){
      console.log(data)
      if(data.status=="ok"){
        $("#new_company_site").html('');
        get_company_sites();
      }
    });
}
  
function save_company_site_header(site_id){
  var send=[];
  send['site_id'] = site_id;
  send['header_name'] = document.getElementById('header_name').value;
  let element = document.getElementById('header_id');
  var header_id = element && element.value ? element.value : 0;
  send['header_id'] = header_id;

  api_query_array("/api/index.php",send,"save_company_site_header").then(function(data){
    if(data.status=="ok"){
      $("#new_site_header").html('');
      $("#edit_header_name_"+header_id+"").html('');
      site_headers.push(data.header);
      if(header_id != 0){
        $("#"+header_id+"").html(data.header.name);
      }
      else{
        edit_company_site("",site_id);
      }
      get_company_sites();
    }
  });
}

function get_company_sites(){
    api_query("/api/index.php","some_form","get_company_sites").then(function(data){
        var table='<table class="table"><thead><tr><th>Адрес сайта</th><th>Принадлежность компании</th><th></th></tr></thead><tbody>';
        var company_id;
        for(var i=0;i<data.company_sites.length; i++){
            company_id=data.company_sites[i].company_id;
            table+='<tr><td>'+data.company_sites[i].site_name+'</td><td>'+data.my_companys[company_id].name+'</td><td>\
            <a onclick="edit_company_site(\''+data.company_sites[i].site_name+'\','+data.company_sites[i].id+');"><img src="/new_images/edit.svg" style="width:20px;"></a>\
            <a onclick="bootbox.confirm(\'Вы точно хотите удалить ваш сайт?\',function(result){ if(result) {delete_site('+data.company_sites[i].id+')}});"><img src="/new_images/garbage.svg" style="width:20px;"></a>\
            </td></tr>';
        }
        table+='</tbody></table>';
        $('#company_sites_list').html(table);
    });
}

var site_headers = [];

function edit_company_site(site_name,site_id){
    site_headers.length = 0;
    var table='';
    var send=[];
    send['site_id']=site_id;
    api_query_array("/api/index.php",send,"get_company_site").then(function(data){
      table+='<div style="width:1000px;"><form id="new_company_site_form" onsubmit="event.preventDefault(); save_company_site_with_JSON();">\
        <h4>Настройки сайта</h4>\
        <div class="row">\
          <div class="col-sm-3">\
            Наименование сайта\
          </div>\
          <div class="col-sm-9">\
              <input type="hidden" name="site_id" value="'+data.company_site.id+'"> \
              <input required type="text" class="form-control input-sm" name="site_name" id="site_name" value="'+data.company_site.site_name+'">\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Логотип сайта\
          </div>\
          <div class="col-sm-7">\
              <div id="site_logo_img">'+'<img src="'+(data.company_site.shop_logo!=""?data.company_site.shop_logo:"")+'" style="width:150px;"></img>'+'</div>\
          </div>\
          <div class="col-sm-2">\
              <span class="btn btn-default fileinput-button btn-sm pull-right">\
                <span>...</span>\
                <input type="file" class="form-control input-sm" name="site_logo" id="site_logo" onchange="convert_logo_to_base64(this);">\
                <input type="hidden" name="shop_logo" value="'+(data.company_site.shop_logo!=""?data.company_site.shop_logo:"")+'" id="shop_logo">\
              </span>\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Иконка сайта(favicon)\
          </div>\
          <div class="col-sm-7">\
              <div id="site_favicon_img" style="padding:5px;">'+'<img src="'+(data.company_site.favicon!=""?data.company_site.favicon:"")+'" style="width:16px;"></img>'+'</div>\
          </div>\
          <div class="col-sm-2">\
              <span class="btn btn-default fileinput-button btn-sm pull-right">\
                <span>...</span>\
                <input type="file" class="form-control input-sm" name="favicon_icon" id="favicon_icon" onchange="convert_favicon_to_base64(this);">\
                <input type="hidden" name="favicon" value="'+(data.company_site.favicon!=""?data.company_site.favicon:"")+'" id="favicon">\
              </span>\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Цветовая палитра сайта\
          </div>\
          <div class="col-sm-9">\
              <a onclick="edit_color_site('+site_id+')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
              <div id="div_edit_color"></div>\
          </div>\
        </div>\
        <hr><div class="row">\
          <div style="display: flex; justify-content: center;">\
              <button class="btn btn-success" onclick="add_site_header('+site_id+')">Добавить заголовок</button>\
          </div>\
          <div style="display: flex; justify-content: center;">\
              <div id="new_site_header"></div>\
          </div>\
        </div>';
        // <hr><div class="row">\
        //   <div class="col-sm-3">\
        //     О нас\
        //   </div>\
        //   <div class="col-sm-9">\
        //       <a onclick="edit_site_html(\'site_about\')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
        //       <div style="float: right;display: -webkit-box;">\
        //         <p>Включён:&nbsp</p>\
        //         <input type="checkbox" name="about_enabled" id="about_enabled"';
        //         if(parseInt(data.company_site.about_enabled)==1) table+=' checked';
        //         table+='>\
        //       </div>\
        //       <div id="site_about_html" style="overflow:auto; display:none" onclick="edit_site_html(\'site_about\')">'+data.company_site.about+'</div>\
        //       <textarea class="form-control" name="site_about" id="site_about" style="display:none">'+data.company_site.about+'</textarea>\
        //   </div>\
        // </div>\
        // <hr><div class="row">\
        //   <div class="col-sm-3">\
        //     Доставка\
        //   </div>\
        //   <div class="col-sm-9">\
        //       <a onclick="edit_site_html(\'site_delivery\')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
        //       <div style="float: right;display: -webkit-box;">\
        //         <p>Включён:&nbsp</p>\
        //         <input type="checkbox" name="delivery_enabled" id="delivery_enabled"';
        //         if(parseInt(data.company_site.delivery_enabled)==1) table+=' checked';
        //         table+='>\
        //       </div>\
        //       <div id="site_delivery_html" style="overflow:auto; height: 120px; display:none" onclick="edit_site_html(\'site_delivery\')">'+data.company_site.delivery+'</div>\
        //       <textarea class="form-control" name="site_delivery" id="site_delivery" style="display:none">'+data.company_site.delivery+'</textarea>\
        //   </div>\
        // </div>\
        // <hr><div class="row">\
        //   <div class="col-sm-3">\
        //     Оплата\
        //   </div>\
        //   <div class="col-sm-9">\
        //       <a onclick="edit_site_html(\'site_payment\')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
        //       <div style="float: right;display: -webkit-box;">\
        //         <p>Включён:&nbsp</p>\
        //         <input type="checkbox" name="payment_enabled" id="payment_enabled"';
        //         if(parseInt(data.company_site.payment_enabled)==1) table+=' checked';
        //         table+='>\
        //       </div>\
        //       <textarea class="form-control" name="site_payment" id="site_payment" style="display:none">'+data.company_site.payment+'</textarea>\
        //   </div>\
        // </div>\
        // <hr><div class="row">\
        //   <div class="col-sm-3">\
        //     Возврат и гарантия\
        //   </div>\
        //   <div class="col-sm-9">\
        //       <a onclick="edit_site_html(\'site_return_garant\')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
        //       <div style="float: right;display: -webkit-box;">\
        //         <p>Включён:&nbsp</p>\
        //         <input type="checkbox" name="return_garant_enabled" id="return_garant_enabled"';
        //         if(parseInt(data.company_site.return_garant_enabled)==1) table+=' checked';
        //         table+='>\
        //       </div>\
        //       <textarea class="form-control" name="site_return_garant" id="site_return_garant" style="display:none">'+data.company_site.return_garant+'</textarea>\
        //   </div>\
        // </div>\
        // <hr><div class="row">\
        //   <div class="col-sm-3">\
        //     Оферта\
        //   </div>\
        //   <div class="col-sm-9">\
        //       <a onclick="edit_site_html(\'site_oferta\')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
        //       <div style="float: right;display: -webkit-box;">\
        //         <p>Включён:&nbsp</p>\
        //         <input type="checkbox" name="oferta_enabled" id="oferta_enabled"';
        //         if(parseInt(data.company_site.oferta_enabled)==1) table+=' checked';
        //         table+='>\
        //       </div>\
        //       <textarea class="form-control" name="site_oferta" id="site_oferta" style="display:none">'+data.company_site.oferta+'</textarea>\
        //   </div>\
        // </div>\
        table += '<hr><div class="row">\
          <div class="col-sm-3">\
          <label> Запрос по VIN</label>\
          </div>\
          <div class="col-sm-9">\
              <input type="text" placeholder="Укажить chat_id телеграмма" style="display: inline-grid;width:75%;" class="form-control" name="tg_chat_id" id="tg_chat_id" value="'+data.company_site.tg_chat_id+'">\
              <a onclick="open_window_with_instruction_for_request_VIN();"><img src="/new_images/file.svg" style="width: 25px;"></a>\
              <div id="instructions_for_request_to_vin"></div>\
              <div style="float: right;display: -webkit-box;">\
                <p>Включён:&nbsp</p>\
                <input type="checkbox" name="request_vin_enabled" id="request_vin_enabled"';
                if(parseInt(data.company_site.request_vin_enabled)==1) table+=' checked';
                table+='>\
              </div>\
          </div>\
        </div>';
        table += '<hr><div class="row">\
          <div class="col-sm-3">\
          <label> Рейтинг в яндексе</label>\
          </div>\
          <div class="col-sm-9">\
              <input type="text" placeholder="Укажите ссылку на widget" style="display: inline-grid;width:75%;" class="form-control" name="yandex_rating_value" id="yandex_rating_value" value="'+data.company_site.yandex_rating_value+'">\
              <div style="float: right;display: -webkit-box;">\
                <p>Включён:&nbsp</p>\
                <input type="checkbox" name="yandex_rating_enabled" id="yandex_rating_enabled"';
                if(parseInt(data.company_site.yandex_rating_enabled)==1) table+=' checked';
                table+='>\
              </div>\
          </div>\
        </div>';
        table += '<hr><div class="row">\
          <div class="col-sm-3">\
          <label> Laximo</label>\
          </div>\
          <div class="col-sm-9">\
              <a onclick="edit_laximo('+site_id+')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
              <div id="div_edit_laximo"></div>\
              <div style="float: right;display: -webkit-box;">\
                <p>Включён:&nbsp</p>\
                <input type="checkbox" name="laximo_enabled" id="laximo_enabled"';
                if(parseInt(data.company_site.laximo_enabled)==1) table+=' checked';
                table+='>\
              </div>\
          </div>\
        </div>';
        table += '<hr><div class="row">\
          <div class="col-sm-3">\
          <label> Поиск по VIN</label>\
          </div>\
          <div class="col-sm-9">\
              <a onclick="edit_ftv('+site_id+')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
              <div id="div_edit_ftv"></div>\
              <div style="float: right;display: -webkit-box;">\
                <p>Включён:&nbsp</p>\
                <input type="checkbox" name="find_to_vin_enabled" id="find_to_vin_enabled"';
                if(parseInt(data.company_site.find_to_vin_enabled)==1) table+=' checked';
                table+='>\
              </div>\
          </div>\
        </div>';
        table += '<hr><div class="row">\
          <div class="col-sm-3">\
          <label> Популярные запчасти</label>\
          </div>\
          <div class="col-sm-9">\
              <div style="float: right;display: -webkit-box;">\
                <p>Включён:&nbsp</p>\
                <input type="checkbox" name="popular_parts_enabled" id="popular_parts_enabled"';
                if(parseInt(data.company_site.popular_parts_enabled)==1) table+=' checked';
                table+='>\
              </div>\
          </div>\
        </div>';
        table += '<hr><div class="row">\
          <div class="col-sm-3">\
          <label> Запчасти по категориям</label>\
          </div>\
          <div class="col-sm-9">\
              <div style="float: right;display: -webkit-box;">\
                <p>Включён:&nbsp</p>\
                <input type="checkbox" name="parts_by_categorys_enabled" id="parts_by_categorys_enabled"';
                if(parseInt(data.company_site.parts_by_categorys_enabled)==1) table+=' checked';
                table+='>\
              </div>\
          </div>\
        </div>';
        table += '<hr><div class="row">\
          <div class="col-sm-3">\
          <label> Популярные товары</label>\
          </div>\
          <div class="col-sm-9">\
              <div style="float: right;display: -webkit-box;">\
                <p>Включён:&nbsp</p>\
                <input type="checkbox" name="popular_goods_enabled" id="popular_goods_enabled"';
                if(parseInt(data.company_site.popular_goods_enabled)==1) table+=' checked';
                table+='>\
              </div>\
          </div>\
        </div>';
        table += '<hr><div class="row">\
          <div class="col-sm-3">\
          <label> Популярные категории</label>\
          </div>\
          <div class="col-sm-9">\
              <div style="float: right;display: -webkit-box;">\
                <p>Включён:&nbsp</p>\
                <input type="checkbox" name="popular_categories" id="popular_categories"';
                if(parseInt(data.company_site.popular_categories)==1) table+=' checked';
                table+='>\
              </div>\
          </div>\
        </div>';
        table += '<hr><div class="row">\
          <div class="col-sm-3">\
            <label>Политика конфеденциальности</label>\
          </div>\
          <div class="col-sm-9">\
              <a onclick="edit_site_html(\'site_privacy\')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
              <div style="float: right;display: -webkit-box;">\
                <p>Включён:&nbsp</p>\
                <input type="checkbox" name="privacy_enabled" id="privacy_enabled"';
                if(parseInt(data.company_site.privacy_enabled)==1) table+=' checked';
                table+='>\
              </div>\
              <textarea class="form-control" name="site_privacy" id="site_privacy" style="display:none">'+data.company_site.privacy+'</textarea>\
          </div>\
        </div>';
        // <hr><div class="row">\
        //   <div class="col-sm-3">\
        //     Контакты\
        //   </div>\
        //   <div class="col-sm-9">\
        //       <a onclick="edit_site_html(\'site_contacts\')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
        //       <div style="float: right;display: -webkit-box;">\
        //         <p>Включён:&nbsp</p>\
        //         <input type="checkbox" name="contacts_enabled" id="contacts_enabled"';
        //         if(parseInt(data.company_site.contacts_enabled)==1) table+=' checked';
        //         table+='>\
        //       </div>\
        //       <textarea class="form-control" name="site_contacts" id="site_contacts" style="display:none">'+data.company_site.contacts+'</textarea>\
        //   </div>\
        // </div>\
        table += '<hr><div class="row">\
          <div class="col-sm-3">\
          <label> Текст на главной</label>\
          </div>\
          <div class="col-sm-9">\
              <a onclick="edit_site_html(\'site_text_on_main\')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
              <div style="float: right;display: -webkit-box;">\
                <p>Включён:&nbsp</p>\
                <input type="checkbox" name="text_on_main_enabled" id="text_on_main_enabled"';
                if(parseInt(data.company_site.text_on_main_enabled)==1) table+=' checked';
                table+='>\
              </div>\
              <textarea class="form-control" name="site_text_on_main" id="site_text_on_main" style="display:none">'+data.company_site.text_on_main+'</textarea>\
          </div>\
        </div>';
        for (var i = 0; i < data.headers.length; i++) {
          site_headers.push(data.headers[i]);
          table += '<hr><div class="row">\
            <div class="col-sm-3">\
              <label id="'+data.headers[i].id+'">'+data.headers[i].name+'</label>\
              <a onclick="edit_header_name('+data.headers[i].id+','+site_id+',\''+data.headers[i].name+'\')"><img src="/new_images/pencil_edit.svg" style="width: 20px;"></a>\
              <div id="edit_header_name_'+data.headers[i].id+'"></div>\
            </div>\
            <div class="col-sm-9">\
                <a onclick="edit_site_html(\''+data.headers[i].uri+'\')"><img src="/new_images/edit.svg" style="width: 25px;"></a>\
                <a onclick="bootbox.confirm(\'Вы точно хотите удалить заголовок?\',function(result){ if(result) {delete_site_header('+site_id+','+data.headers[i].id+')}});"><img src="/new_images/garbage.svg" style="width:25px;"></a>\
                <div style="float: right;display: -webkit-box;">\
                  <p>Включён:&nbsp</p>\
                  <input type="checkbox" name="'+data.headers[i].uri+'_enabled" id="'+data.headers[i].uri+'_enabled"';
                  if(parseInt(data.headers[i].enabled)==1) table+=' checked';
                  table+='>\
                </div>\
                <textarea class="form-control" name="'+data.headers[i].uri+'" id="'+data.headers[i].uri+'" style="display:none">'+data.headers[i].value+'</textarea>\
            </div>\
          </div>';
        }
        table += '<hr>\
        <div class="row">\
            <div class="col-sm-3">\
                Использовать каталог Sort1\
            </div>\
            <div class="col-sm-9">\
              <input type="checkbox" name="use_catalog_sort1" id="use_catalog_sort1" onchange="toggleConfigureButton()"';
              if(parseInt(data.company_site.use_catalog_sort1)==1) table+=' checked';
              table+='>\
            </div>\
        </div>\
        <div id="setting_cat" style="width: 100%;';
        if(parseInt(data.company_site.use_catalog_sort1)==0) table+=' display: none; ';
        table+='">\
          <hr>\
          <div class="row">\
              <div class="col-sm-3">\
                  Каталог\
              </div>\
              <div class="col-sm-9">\
                  <input type="button" class="btn btn-primary" name="btn_setting_cat" id="btn_setting_cat" onclick="open_cat_settings('+site_id+')" value="Настроить">\
                  <div id="settings_categorys"></div>\
              </div>\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Координаты магазина (широта,долгота)\
          </div>\
          <div class="col-sm-9">\
              <input type="text" class="form-control" name="shop_coords" id="shop_coords" value="'+data.company_site.shop_coords+'">\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Адрес магазина\
          </div>\
          <div class="col-sm-9">\
              <input type="text" class="form-control" name="shop_address" id="shop_address" value="'+data.company_site.shop_address+'">\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Телеграм\
          </div>\
          <div class="col-sm-9">\
            <input type="text" class="form-control" name="shop_telegram" id="shop_telegram" value="'+data.company_site.shop_telegram+'">\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            WhatsApp\
          </div>\
          <div class="col-sm-9">\
            <input type="text" class="form-control" name="shop_whatsapp" id="shop_whatsapp" value="'+data.company_site.shop_whatsapp+'">\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Viber\
          </div>\
          <div class="col-sm-9">\
              <input type="text" class="form-control" name="shop_viber" id="shop_viber" value="'+data.company_site.shop_viber+'">\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Телефон\
          </div>\
          <div class="col-sm-9">\
              <input type="text" class="form-control" name="shop_phone" id="shop_phone" value="'+data.company_site.shop_phone+'">\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Email\
          </div>\
          <div class="col-sm-9">\
              <input type="text" class="form-control" name="shop_email" id="shop_email" value="'+data.company_site.shop_email+'">\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Проверять телефон при регистрации\
          </div>\
          <div class="col-sm-9">\
              <input type="checkbox" name="shop_verify_phone" id="shop_verify_phone"';
          if(parseInt(data.company_site.shop_verify_phone)==1) table+=' checked';
          table+='>\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            ключ API для sms.ru\
          </div>\
          <div class="col-sm-9">\
              <input type="text" class="form-control" name="shop_sms_apikey" id="shop_sms_apikey" value="'+data.company_site.shop_sms_apikey+'">\
          </div>\
        </div>\
        <hr><div class="row">\
          <div class="col-sm-3">\
            Подключить каталог\
          </div>\
          <div class="col-sm-9">\
              <input type="checkbox" id="catalog_active" name="catalog_active" onchange="change_active_catalog($('+"'"+'#catalog_active'+"'"+')[0].checked)"'
              if (data.company_site.id_catalog!=null){
                table +=' checked';
                if (data.company_site.catalog_config==null){
                  change_active_catalog(true,data.company_site.id_catalog);
                }else{
                  change_active_catalog(true,data.company_site.id_catalog,data.company_site.catalog_config);
                }
              }
              table+='>\
          </div>\
        </div>\
        <div class="row" id="div_select_catalog"></div>\
        </form>\
        <button class="btn btn-primary btn-sm" type="button" onclick="save_company_site_with_JSON();">Сохранить</button>\
      </div>';
      create_window_centered_blue("new_company_site_div","Введите адрес вашего сайта, привязанного к данной организации","new_company_site",table);
    });
  }

  function add_site_header(site_id){
    var table='';
    table+='<form id="new_site_header_form" onsubmit="event.preventDefault(); save_company_site_header('+site_id+');">\
        Название заголовка\
        <div class="input-group input-group-sm">\
          <input required type="text" class="form-control input-sm" name="header_name" id="header_name" value="">\
        <div class="input-group-btn">\
            <button class="btn btn-default btn-sm" type="button" onclick="save_company_site_header('+site_id+');">Сохранить</button>\
        </div>\
      </div>\
    </form>';
    create_window("new_site_header_div","Введите название заголовка","new_site_header",table);
  }

  function delete_site_header(site_id,header_id){
    var send=new Array();
    send['header_id']=header_id;
    api_query_array("/api/index.php",send,"delete_site_header").then(function(data){
        if(data.status=="ok"){
          edit_company_site("",site_id);
        }
    });
  }

  function edit_header_name(header_id,site_id,header_name){
    var table='';
    table+='<form id="new_site_header_form" onsubmit="event.preventDefault(); save_company_site_header('+site_id+');">\
        Название заголовка\
        <div class="input-group input-group-sm">\
          <input required type="text" class="form-control input-sm" name="header_name" id="header_name" value="'+header_name+'">\
          <input required type="text" class="form-control input-sm" name="header_id" id="header_id" value="'+header_id+'" style="display:none">\
        <div class="input-group-btn">\
            <button class="btn btn-default btn-sm" type="button" onclick="save_company_site_header('+site_id+');">Сохранить</button>\
        </div>\
      </div>\
    </form>';
    create_window("edit_header_name_div","Введите название заголовка","edit_header_name_"+header_id,table);
  }

  function toggleConfigureButton() {
    const catalogCheckbox = document.getElementById('use_catalog_sort1');
    const configureButton = document.getElementById('setting_cat');

    if (catalogCheckbox.checked) {
        configureButton.style.display = 'inline-block';
    } else {
        configureButton.style.display = 'none';
    }
  }

  async function save_company_site_with_JSON(){
    var postdata = $("#new_company_site_form").serializeJSON();
    postdata['catalog_config']={};
    for (var i = 0; i < site_headers.length; i++) {
      site_headers[i]['value'] = postdata[site_headers[i]['uri']];
      site_headers[i]['enabled'] = postdata[site_headers[i]['uri']+"_enabled"] == 'on' ? 1 : 0;
    }
    postdata['headers']=site_headers;
    let id_catalog = $("#select_catalog").val();
    //console.log(id_catalog);
    if (id_catalog != 0 && id_catalog != undefined){
      let arr = [];
      arr['id'] = id_catalog;
      await api_query_array("/api/index.php",arr, "get_config_catalog").then(function(data){
        for (let i = 0; i < data.config.length; ++i){
          postdata['catalog_config'][data.config[i].name] = $("#"+data.config[i].name).val();
        }
      });
    }
    await api_query_array("/api/index.php", postdata, "save_company_site").then(function(data){
      if(data.status=="ok"){
        $("#new_company_site").html('');
        get_company_sites();
      }
    });
  }

  function change_active_catalog(val,id_catalog,catalog_config){
    if (val){
      api_query("/api/index.php","some_form","get_catalogs").then(function(data){
        let select = '<hr width="1000px"><div class="col-sm-3">Каталоги</div><div class="col-sm-9"><select id="select_catalog" name="select_catalog" class="form-control" onchange="change_select_catalog(this.options[this.selectedIndex].value)">';
        select += '<option value="0"></option>';
        for (let i = 0; i < data.catalogs.length; ++i){
          select += '<option value="'+data.catalogs[i].id+'">'+data.catalogs[i].name_catalog+'</option>';
        }
        select += '</select></div><br>';
        select += '<div id="config_catalog"></div>'
        $("#div_select_catalog").html(select);
        if (id_catalog!=undefined){
          $("#select_catalog option[value='"+id_catalog+"']").prop('selected',true);
          if (catalog_config != undefined){
            change_select_catalog(id_catalog, catalog_config);
          }else{
            change_select_catalog(id_catalog);
          }
        }
      });
    }else{
      $("#div_select_catalog").html('');
    }
  }
//<hr><div class="col-sm-3">Домен для виджета</div><div class="col-sm-9"><input type="text" id="domen_acat_online" name="domen_acat_online" value="" class="form-control"></div>
  function change_select_catalog(id_catalog, catalog_config){
    if (id_catalog != 0){
      let arr = [];
      arr['id'] = id_catalog;
      api_query_array("/api/index.php",arr, "get_config_catalog").then(function(data){
        //$("#config_catalog").html(data.config.catalog_config);
        let str = "";
        for (let i = 0; i < data.config.length; ++i){
          str += '<hr width="1000px"><div class="col-sm-3">'+data.config[i].dscr+'</div>';
          let val = '';
          try{
          if (JSON.parse(catalog_config)[data.config[i].name]!=undefined){
            val=JSON.parse(catalog_config)[data.config[i].name].toString();
          }
          }catch{}
          str += '<div class="col-sm-9"><input type="text" id="'+data.config[i].name+'" name="'+data.config[i].name+'" value="'+ val +'" class="form-control"></div>';
        }
        $('#config_catalog').html(str);
        
      });
      
    }
    else{
      $("#config_catalog").html('');
    }
  }

  function edit_site_html(id){
    //$("#"+id+"_html").css("display","none");
    //$("#"+id).css("display","block");
    if(typeof($("#"+id).parent().find(".note-editor").html())!="undefined"){
      $('#'+id).summernote('destroy');
      $("#"+id).css("display","none");
    }
    else {
      $('#'+id).summernote({
        //placeholder: "Hello stand alone ui",
        tabsize: 2,
        //height: 120,
        //width: 650,
        toolbar: [
          ["style", ["style"]],
          ["font", ["bold", "underline", "clear"]],
          ["color", ["color"]],
          ["para", ["ul", "ol", "paragraph"]],
          ["table", ["table"]],
          ["insert", ["link", "picture", "video"]],
          ["view", ["fullscreen", "codeview", "help"]]
        ]
      });
    }
  }

  function open_window_with_instruction_for_request_VIN(){
    var table ='<div>';
    table += '<h1>Инструкция по подключению запроса по VIN</h1>'
    table += '<h3>1. Взять chat_id телеграмм чата куда будут приходить запросы</h3>';
    table += '<font>Как получить чат id вы можете ознакомиться по следующей </font> <a target="_blank" href="https://docs.leadconverter.su/faq/populyarnye-voprosy/telegram/kak-uznat-id-telegram-gruppy-chata"><i>ссылке</i></a>';
    table += '<h3>2. Добавьте бота в чат в которой должны идти запросы</h3>';
    table += '<font>Добавьте <a target="_blank" href="https://t.me/Sort1CorpBot">бота</a> в чат в которой должны идти запросы и дайте ему права администратора, после чего попробуйте тестово отправить запрос с сайта, если ничего не пришло в чат обратитесь в тех.поддержку.</font>';
    table += '</div>';
    create_window("new_instructions_for_request_to_vin","Инструкция","instructions_for_request_to_vin",table);
  }

  var ftv_arr;
  var ftv_id = 0;
  function window_create_ftv(site_id){
    var send=[];
    send['site_id']=site_id;
    api_query_array("/api/index.php",send,"get_ftv").then(function(data){
      if (data.status == "ok"){
        ftv_arr = data.ftv;
        var table='<div class="row"><div class="col-sm-5" style="padding-top:7px;"><b>Поиск по вину от: </b></div><div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="find_to_vin" id="find_to_vin" onchange="print_ftv_config('+site_id+');">';
        table+='<option hidden disabled selected value>Выберите от кого будет каталог</option>';
        for(var i=0; i<ftv_arr.length; i++){
          table+='<option value="'+i+'">'+ftv_arr[i].name+'</option>';
        }
        table+='</select></div></div>';
        table+='<div id="ftv_config"></div>';
        create_window("new_div_edit_ftv","Добавление поиска по вину","div_edit_ftv",table);
        // setTimeout(print_logistic_config(),10);
      }
    });
  }

  function window_edit_ftv(site_id, ftv_config_id){
    var send=[];
    send['site_id']=site_id;
    send['ftv_config_id'] = ftv_config_id;
    api_query_array("/api/index.php",send,"get_ftv").then(function(data){
      if (data.status == "ok"){
        ftv_arr = data.ftv;
        var table='<div class="row"><div class="col-sm-5" style="padding-top:7px;"><b>Поиск по вину от: </b></div><div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="find_to_vin" id="find_to_vin" onchange="print_ftv_config('+site_id+');">';
        var findIndex = ftv_arr.findIndex(z=> z.id == data.ftv_find_id);
        table+='<option value="'+findIndex+'">'+ftv_arr[findIndex].name+'</option>';
        for(var i=0; i<ftv_arr.length; i++){
          if (i != findIndex){
            table+='<option value="'+i+'">'+ftv_arr[i].name+'</option>';
          }
        }
        table+='</select></div></div>';
        table+='<div id="ftv_config"></div>';
        create_window("new_div_edit_ftv","Добавление поиска по вину","div_edit_ftv",table);
        setTimeout(print_ftv_config(site_id, ftv_config_id),10);
      }
    });
  }

  function print_ftv_config(site_id, ftv_config_id = null){
    var ftv_id=$("#find_to_vin").val();
    if (ftv_id != null){
      if (ftv_config_id == null){
        if(typeof(ftv_id)=="undefined") return 0;
        var table='<table class="table"><thead><th colspan="2">Конфигурация</th></thead><tbody>';
        for (var i=0; i<ftv_arr[ftv_id].find_to_vin_config.length; i++){
          var conf=ftv_arr[ftv_id].find_to_vin_config[i];
          table+='<tr><td>'+conf.descr;
          if(typeof(conf.title)!="undefined") table+=' <a title="'+conf.title+'">?</a> ';
          table+=':</td><td>';
          if(conf.type=="boolean") {
            table+='<input type="checkbox" name="VIN_'+conf.name+'" id="VIN_'+conf.name+'"';
            if(conf.value) table+=' checked="checked"';
            table+='>';
          }
          else table+='<input class="form-control" type="text" name="VIN_'+conf.name+'" id="VIN_'+conf.name+'" value="'+conf.value+'">';
          table+='</td></tr>';
        }
        table+='<tr><td><button class="btn btn-primary" type="button" onclick="save_ftv('+site_id+');">Сохранить</button></td><td><button class="btn btn-default pull-right" onclick="close_window(\'div_edit_ftv\');">Отменить</button></td></tr>';
        table+='</tbody></table>';
        $("#ftv_config").html(table);
        //place_to_center("new_div_edit_ftv");
      }
      else{
        var send = [];
        send['ftv_config_id'] = ftv_config_id;
        api_query_array("/api/index.php",send,"get_ftv_config").then(function(data){
          if(typeof(ftv_id)=="undefined") return 0;
          var table='<table class="table"><thead><th colspan="2">Конфигурация</th></thead><tbody>';
          for (var i=0; i<ftv_arr[ftv_id].find_to_vin_config.length; i++){
            var conf=ftv_arr[ftv_id].find_to_vin_config[i];
            table+='<tr><td>'+conf.descr;
            if(typeof(conf.title)!="undefined") table+=' <a title="'+conf.title+'">?</a> ';
            table+=':</td><td>';
            if(conf.type=="boolean") {
              table+='<input type="checkbox" name="VIN_'+conf.name+'" id="VIN_'+conf.name+'"';
              if(data.ftv_config[conf.name]) table+=' checked="checked"';
              table+='>';
            }
            else table+='<input class="form-control" type="text" name="VIN_'+conf.name+'" id="VIN_'+conf.name+'" value="'+data.ftv_config[conf.name]+'">';
            table+='</td></tr>';
          }
          table+='<tr><td><button class="btn btn-primary" type="button" onclick="save_ftv('+site_id+');">Сохранить</button></td><td><button class="btn btn-default pull-right" onclick="close_window(\'div_edit_ftv\');">Отменить</button></td></tr>';
          table+='</tbody></table>';
          $("#ftv_config").html(table);
          //place_to_center("new_div_edit_ftv");
        })
      }
    }
  }

  function save_ftv(site_id){
    var send={};
    send['find_to_vin_id']=ftv_arr[$("#find_to_vin").val()].id;
    send['ftv_id']=ftv_id;
    send['site_id']=site_id;
    send['find_to_vin_config']={};
    for (var i=0; i<ftv_arr[$("#find_to_vin").val()].find_to_vin_config.length; i++){
      var conf=ftv_arr[$("#find_to_vin").val()].find_to_vin_config[i];
      if(conf.type=="boolean") {
        if($('#VIN_'+conf.name).prop('checked')) send['find_to_vin_config'][conf.name]=true;
        else send['find_to_vin_config'][conf.name]=false;
      }
      else send['find_to_vin_config'][conf.name]=$("#VIN_"+conf.name).val();
    }
    api_query_array("/api/index.php",send,"save_ftv_config").then(function(data){
      if(data.status=="ok"){
        $("#div_edit_ftv").html('');
      }
    });
    
  }

  function edit_ftv(site_id){
    var send=[];
    send['site_id']=site_id;
    api_query_array("/api/index.php",send,"get_ftv_id").then(function(data){
      if (data.status == "ok"){
        if (data.ftv_config_id != null && data.ftv_config_id > 0){
          window_edit_ftv(site_id, data.ftv_config_id);
        }else{
          window_create_ftv(site_id);
        }
      }
    });
  }

  function edit_laximo(site_id){
    var send=[];
    send['site_id']=site_id;
    api_query_array("/api/index.php",send,"get_laximo_data").then(function(data){
      var table = '<div>';
      table += '<input id="laximo_login" class="form-control" type="text" placeholder="login" value="'+data.laximo_data.laximo_login+'" />';
      table += '<input id="laximo_key" class="form-control" type="text" placeholder="key" value="'+data.laximo_data.laximo_key+'" />';
      table += '<button class="btn btn-primary btn-sm" type="button" onclick="save_laximo('+site_id+');">Сохранить</button>';
      table += '</div>';
      create_window("laximo_div","Редактирование конфигурации laximo","div_edit_laximo",table);
    });
  }

  function save_laximo(site_id){
    var send = [];
    send['site_id'] = site_id;
    send['laximo_login'] = $('#laximo_login').val();
    send['laximo_key'] = $('#laximo_key').val();
    api_query_array("/api/index.php",send,"save_laximo_data").then(function(data){
      $('#div_edit_laximo').empty();
    })
  }

  var iframe;
  var iframeWindow = null;
  function edit_color_site(site_id){
    var send=[];
    send['site_id']=site_id;
    api_query_array("/api/index.php",send,"get_colors_site").then(function(data){
      var colors = data.colors; // Предполагается, что API возвращает объект с цветами
      var table = "<div id='color-palette'>";

      // Для каждого цвета создаем элементы для отображения и редактирования
      Object.keys(colors).forEach(function(key) {
          table += "<div class='color-item' style='padding:5px;'>";
          table += "<label for='color-" + key + "'>" + key + "</label>"; // Название цвета
          table += "<input style='float:right;' type='color' id='color-" + key + "' value='" + colors[key] + "' onChange='updateColor(\"" + key + "\", this.value)'>";
          table += "</div>";
      });
      table += '<iframe id="exampleStyle" style="width:80vw;height:80vh;" src="https://shop.sort1.pro"></iframe><br/>';
      table += '<button class="btn btn-primary btn-sm" type="button" onclick="save_color_site('+data.id_colors+')">Сохранить</button>';
      table += "</div>";
      create_window_centered_blue("edit_color","Редактирование цвета","div_edit_color",table);
      iframe = document.getElementById('exampleStyle');
      iframe.onload = function() {
        iframeWindow = iframe.contentWindow;
        updateColor();
      };
    });
    
  }

  function updateColor(){
    var send = [];
    send['color'] = $('#color-color').val();
    send['color_dark'] = $('#color-color_dark').val();
    send['text_in_color_dark'] = $('#color-text_in_color_dark').val();
    send['color_button'] = $('#color-color_button').val();
    send['text_color_in_button'] = $('#color-text_color_in_button').val();
    send['color_links'] = $('#color-color_links').val();
    send['color_links_analog'] = $('#color-color_links_analog').val();
    send['color_footer'] = $('#color-color_footer').val();
    iframeWindow.postMessage(send, 'https://shop.sort1.pro/');
  }

  function save_color_site(id_color_site){
    var send = [];
    send['id_color_site'] = id_color_site;
    send['color'] = $('#color-color').val();
    send['color_dark'] = $('#color-color_dark').val();
    send['text_in_color_dark'] = $('#color-text_in_color_dark').val();
    send['color_button'] = $('#color-color_button').val();
    send['text_color_in_button'] = $('#color-text_color_in_button').val();
    send['color_links'] = $('#color-color_links').val();
    send['color_links_analog'] = $('#color-color_links_analog').val();
    send['color_footer'] = $('#color-color_footer').val();
    api_query_array("/api/index.php",send,"save_colors_site").then(function(data){
      $('#div_edit_color').empty();
    })
  }

  function convert_logo_to_base64(event){
    console.log(event.files);
    let myFiles = {}
    // if you expect files by default, make this disabled
    // we will wait until the last file being processed
    let isFilesReady = true;
    var files = event.files;
    const inputKey = document.getElementById('site_logo').getAttribute('name')
    const filePromises = Object.entries(files).map(item => {
      return new Promise((resolve, reject) => {
        const [index, file] = item
        const reader = new FileReader();
        reader.readAsBinaryString(file);
  
        reader.onload = function(event) {
          // if it's multiple upload field then set the object key as picture[0], picture[1]
          // otherwise just use picture
          const fileKey = `${inputKey}${files.length > 1 ? `[${index}]` : ''}`
          // Convert Base64 to data URI
          // Assign it to your object
          myFiles[fileKey] = `data:${file.type};base64,${btoa(event.target.result)}`
  
          resolve()
        };
        reader.onerror = function() {
          console.log("can't read the file");
          reject()
        };
      })
    })
  
    Promise.all(filePromises)
      .then(() => {
        console.log('ready to submit')
        isFilesReady = true
        console.log(myFiles);
        $("#shop_logo").val(myFiles['site_logo']);
        $("#site_logo_img").html('<img src="'+myFiles['site_logo']+'" style="width:150px;">');
      })
      .catch((error) => {
        console.log(error)
        console.log('something wrong happened')
      })
  }

  function convert_favicon_to_base64(event){
    console.log(event.files);
    let myFiles = {}
    // if you expect files by default, make this disabled
    // we will wait until the last file being processed
    let isFilesReady = true;
    var files = event.files;
    const inputKey = document.getElementById('favicon_icon').getAttribute('name')
    const filePromises = Object.entries(files).map(item => {
      return new Promise((resolve, reject) => {
        const [index, file] = item
        const reader = new FileReader();
        reader.readAsBinaryString(file);
  
        reader.onload = function(event) {
          // if it's multiple upload field then set the object key as picture[0], picture[1]
          // otherwise just use picture
          const fileKey = `${inputKey}${files.length > 1 ? `[${index}]` : ''}`
          // Convert Base64 to data URI
          // Assign it to your object
          myFiles[fileKey] = `data:${file.type};base64,${btoa(event.target.result)}`
  
          resolve()
        };
        reader.onerror = function() {
          console.log("can't read the file");
          reject()
        };
      })
    })
  
    Promise.all(filePromises)
      .then(() => {
        console.log('ready to submit')
        isFilesReady = true
        console.log(myFiles);
        $("#favicon").val(myFiles['favicon_icon']);
        $("#site_favicon_img").html('<img src="'+myFiles['favicon_icon']+'" style="width:16px;">');
      })
      .catch((error) => {
        console.log(error)
        console.log('something wrong happened')
      })
  }

function delete_site(site_id){
    var send=new Array();
    send['site_id']=site_id;
    api_query_array("/api/index.php",send,"delete_company_site").then(function(data){
        if(data.status=="ok"){
            get_company_sites();
        }
    });
}

function site_logo_file_upload(input){
  var xhr = new XMLHttpRequest();
  xhr.upload.onprogress = function(e) {
      display_site_logo_file_load_state(e.loaded, e.total)
  }
  xhr.upload.onload = function(e) {
          console.log('file upload')
  }
  xhr.onreadystatechange = function() {
      if (xhr.readyState == XMLHttpRequest.DONE) {
          var ret_data=JSON.parse(xhr.response);
          if(ret_data.status=="ok"){
              var len=ret_data.loaded_files.length;
              for(var i=0; i<len; i++){
                  site_logo_files.push(ret_data.loaded_files[i]);
                  $("#edit_site_logo_form").append('<input type="hidden" name="bug_loaded_files[]" value="'+ret_data.loaded_files[i].id+'">')
              }
              print_loaded_files();
          }
      }
  }

  xhr.open("POST", "/api/index.php", true);
  xhr.send(new FormData(input.parentElement));
}

function print_loaded_files(){
  var len=bug_files.length;
  var table='<table><tbody>';
  for(var i=0; i<len; i++){
      table+='<tr><td>'+bug_files[i].name+'</td></tr>';
  }
  table+='</tbody></table>';
  $("#bug_loaded_files").html(table);
}

function display_bug_file_load_state(loaded, total){
  $("#bug_file_upload_status").attr("aria-valuenow",parseInt(loaded/total*100)+"%");
  $("#bug_file_upload_status").css("width",parseInt(loaded/total*100)+"%");
  $("#bug_file_upload_status").html(parseInt(loaded/total*100)+"%");
}

var disabled_cat = [];

function open_cat_settings(site_id) {
  var table = '';

  var send = [];
  send['site_id'] = site_id;
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
  api_query_array("/api/index.php", send, "get_company_site").then(function(dataSite) {
    if (dataSite.company_site.disabled_categorys) {
      var dis_categorys = JSON.parse(dataSite.company_site.disabled_categorys) || [];
    } else {
      var dis_categorys = [];
    }
    api_query("/api/index.php", "some_form", "get_all_categorys").then(function(data) {
      // Рекурсивная функция для отрисовки категорий с подкатегориями
      function renderCategories(categories, indentLevel, parentDisabled) {
        categories.forEach(function(category) {
          var indentation = '&nbsp;&nbsp;'.repeat(indentLevel);
          var arrow = '';
          if (category.hasOwnProperty('subcategories') && Array.isArray(category.subcategories) && category.subcategories.length > 0) {
            arrow = '<span class="arrow" style="cursor: pointer;" data-category-id="' + category.id + '">▶</span>';
          }
          else{
            arrow = '&nbsp&nbsp&nbsp';
          }
          var disabled = dis_categorys.includes(category.id);
          var checkbox = '&nbsp;<input type="checkbox" name="category_checkbox" value="' + category.id + '"';
          if (disabled) {
              disabled_cat.push(category.id);
              checkbox += ' ';
          } else {
            checkbox += ' checked';
            var index = dis_categorys.indexOf(category.id);
            if (index > -1) {
              disabled_cat.splice(index, 1);
            }
          }
          checkbox += '> &nbsp;&nbsp;';
          table += indentation + arrow + checkbox + '<span class="parent-category">' + category.name + '</span><br>';
          if (category.hasOwnProperty('subcategories') && Array.isArray(category.subcategories)) {
            table += '<div class="child-category hidden" data-parent-category-id="' + category.id + '">';
            renderCategories(category.subcategories, indentLevel + 1, disabled);
            table += '</div>';
          }
        });
      }
      renderCategories(data.categories, 0, false);
      console.log(dis_categorys);
      table += '&nbsp;&nbsp;';
      table += '<button" class="btn btn-primary" onclick="saveCategories(\'' + dataSite.company_site.site_name + '\',' + site_id + ')">Сохранить</button>';
      create_window("settings_categorys_div", "Настройка католога сайта", "settings_categorys", table);
      handleArrowClick();

      var checkboxes = document.querySelectorAll('input[name="category_checkbox"]');
      checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('click', function(event) {
          var categoryId = event.target.value;
          var checked = event.target.checked;
          if (!checked) {
            if (!disabled_cat.includes(categoryId)) {
              disabled_cat.push(categoryId);
            }
            // Удаление дочерних элементов из массива disabled_cat
            var childCategories = document.querySelectorAll('.child-category[data-parent-category-id="' + categoryId + '"]');
            childCategories.forEach(function(childCategory) {
              var childCheckboxes = childCategory.querySelectorAll('input[name="category_checkbox"]');
              childCheckboxes.forEach(function(childCheckbox) {
                var childCategoryId = childCheckbox.value;
                var childIndex = disabled_cat.indexOf(childCategoryId);
                if (childIndex > -1) {
                  disabled_cat.splice(childIndex, 1);
                }
              });
            });
          } else {
            var index = disabled_cat.indexOf(categoryId);
            if (index > -1) {
              disabled_cat.splice(index, 1);
            }
            // Удаление categoryId из массива disabled_cat дочерних элементов
            var childCategories = document.querySelectorAll('.child-category[data-parent-category-id="' + categoryId + '"]');
            childCategories.forEach(function(childCategory) {
              var childCheckboxes = childCategory.querySelectorAll('input[name="category_checkbox"]');
              childCheckboxes.forEach(function(childCheckbox) {
                var childCategoryId = childCheckbox.value;
                var childIndex = disabled_cat.indexOf(childCategoryId);
                if (childIndex > -1) {
                  disabled_cat.splice(childIndex, 1);
                }
              });
            });
          }
          toggleChildCheckboxes(categoryId, checked);
          console.log(disabled_cat);
        });
      });

      disabled_cat.forEach(function(disabledCategory) {
        toggleChildCheckboxes(disabledCategory, false);
      });
      $.unblockUI();
    });
  }, function(data){
    $.unblockUI();
  });
}

function handleArrowClick() {
  var arrows = document.querySelectorAll('.arrow');
  arrows.forEach(function(arrow) {
    arrow.addEventListener('click', function(event) {
      var categoryId = event.target.getAttribute('data-category-id');
      var childCategory = document.querySelector('.child-category[data-parent-category-id="' + categoryId + '"]');
      if (childCategory) {
        childCategory.classList.toggle('hidden');
        arrow.textContent = childCategory.classList.contains('hidden') ? '▶' : '▼';
      }
      event.stopPropagation(); // Остановка распространения события, чтобы не влиять на родительские категории
    });
  });
}

function toggleChildCheckboxes(parentCategoryId, checked) {
  var childCategories = document.querySelectorAll('.child-category[data-parent-category-id="' + parentCategoryId + '"]');
  childCategories.forEach(function(childCategory) {
    var checkboxes = childCategory.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(function(checkbox) {
      checkbox.checked = checked;
      checkbox.disabled = !checked;
      toggleChildCheckboxes(checkbox.value, checked);
    });
  });
}

function saveCategories(site_name,site_id) {
  var dis_categorys_json = JSON.stringify(disabled_cat);
  var send=new Array();
  send['site_id']=site_id;
  send['site_name']=site_name;
  send['disabled_categorys']=dis_categorys_json;
  api_query_array("/api/index.php",send,"save_company_site").then(function(data){
      if(data.status=="ok"){
          // get_company_sites();
          $("#settings_categorys").html('');
          disabled_cat = new Array();
      }
  });
}