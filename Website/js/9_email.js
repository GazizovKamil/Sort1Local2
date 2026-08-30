function edit_email_config(id=0){
    if(id>0){
        var send=[];
        send['email_config_id']=id;
        api_query_array("/api/index.php",send,"get_email_config").then(function(data){
            if(data.status=="ok"){
                create_window_centered_blue("edit_email_config_div","Создание конфигурации почты","edit_email_config",print_email_config(data.email_config));
            }
        });
    }
    else {
        var data={
            id:0,
            main_company_id: $("#mycompany"),
            name: "",
            email_provider_id: 1,
            email_provider_text: "yandex",
            login: "",
            password: "",
            tested:0,
            price_folder: "",
            user_id: 0,
            deleted: 0
        };
        create_window_centered_blue("edit_email_config_div","Создание конфигурации почты","edit_email_config",print_email_config(data));
    }
}

function print_email_config(data){
    var table='';
    table+='<form id="email_config_form">\
    <input type="hidden" name="email_config_id" value="'+data.id+'">\
    <input type="hidden" id="email_config_tested" name="email_config_tested" value="'+data.tested+'">\
    <div class="row"><div class="col-sm-5">Наименование:</div><div class="col-sm-7"><input type="text" id="email_config_name" name="name" class="form-control" value="'+data.name+'"></div></div>\
    <div class="row"><div class="col-sm-5">Расположение:</div><div class="col-sm-7">\
    <select id="email_provider_id" name="email_provider_id" class="form-control">\
    <option value="1" '+(data.email_provider_id==1?"selected":"")+'>Почта на Яндексе</option>\
    <option value="2" '+(data.email_provider_id==2?"selected":"")+'>Почта на mail.ru</option>\
    <option value="3" '+(data.email_provider_id==3?"selected":"")+'>Почта на gmail.com</option>\
    </select>\
    </div>\
    </div>\
    <div class="row"><div class="col-sm-5">Имя пользователя:</div><div class="col-sm-7"><input type="text" readonly onfocus="this.removeAttribute(\'readonly\');" id="email_config_login" name="email_config_login" class="form-control" value="'+data.login+'"></div></div>\
    <div class="row"><div class="col-sm-5">Пароль:</div><div class="col-sm-7"><input type="password" readonly onfocus="this.removeAttribute(\'readonly\');" id="email_config_password" name="email_config_password" class="form-control password_eye" value="'+data.password+'"><label style="position: absolute; top: 0.7em; right: 0.3em;" for="email_config_password" id="email_password_eye" onclick="toggle_password()"></label></div></div>\
    <div class="row"><div class="col-sm-5">Папка для работы с прайс листами:</div><div class="col-sm-7"><input type="text" id="email_config_price_folder" name="email_config_price_folder" class="form-control" value="'+data.price_folder+'"></div></div>\
     <hr>\
    <div class="row"><div class="col-sm-5">Статус:</div><div class="col-sm-7"><span id="email_config_status">'+(data.tested==1?"<b style='color: green'>Тест успешно пройден</b>":"")+'</span></div></div>\
    <hr>\
    <div class="row"><div class="col-sm-5"><button class="btn btn-success" onclick="test_email_config()" type="button">Проверить</button></div><div class="col-sm-7"></div></div>\
    <hr>\
    <div class="row"><div class="col-sm-5"><button class="btn btn-primary" onclick="save_email_config()" type="button">Сохранить</button></div><div class="col-sm-7"><button class="btn btn-default pull-right" onclick="close_window(\'edit_email_config\')" type="button">Отменить</button></div></div>\
    </form>';
    return table;
}

function test_email_config(){
    api_query("/api/index.php","email_config_form","test_email_config").then(function(data){
        if(data.status=="ok"){
            $("#email_config_tested").val(1);
            var table='<font color="green"><b>Тест успешно пройден,</b><br> список папок на сервере:</font><br><table><tbody>';
            for(var i in data.mlist){
                table+='<tr><td>'+data.mlist[i]+'</td></tr>';
            }
            table+='</tbody></table>';
            document.getElementById("email_config_status").innerHTML=table;
        }else {
            $("#email_config_tested").val(0);
        }
    })

}

function save_email_config(){
    api_query("/api/index.php","email_config_form","save_email_config").then(function(data){
        if(data.status=="ok"){
            close_window('edit_email_config');
            get_email_configs();
        }
    })

}

function toggle_password(){
    const icon = document.getElementById('email_password_eye');
    let password = document.getElementById('email_config_password');
    if(password.type === "password") {
        password.type = "text";
        password.classList.add("password_eye_no");
        password.classList.remove("password_eye");
      }
      else {
        password.type = "password";
        password.classList.add("password_eye");
        password.classList.remove("password_eye_no");
      }
}

function get_email_configs(){
    api_query("/api/index.php","some_form","get_email_configs").then(function(data){
        if(data.status=="ok"){
            var table='<table class="table table-hover"><thead><tr><th>№</th><th>Наименование</th><th>Провайдер почты</th><th>Папка</th><th>Тестировано</th><th></th></tr></thead><tbody>';
            for (var i in data.email_configs){
                table+='<tr><td>'+(parseInt(i)+1)+'</td>\
                <td>'+data.email_configs[i].name+'</td>\
                <td>'+data.email_configs[i].email_provider_text+'</td>\
                <td>'+data.email_configs[i].price_folder+'</td>\
                <td>'+(data.email_configs[i].tested==1?'<img src="/images/ok.svg" style="width:16px;">':'')+'</td>\
                <td><a onclick="edit_email_config('+data.email_configs[i].id+');" title="Редактировать почту"><img src="/new_images/edit.svg" class="menuimg"></a></td></tr>';
            }
            table+='</table>';
            document.getElementById("email_config_list").innerHTML=table;
        }
    })

}