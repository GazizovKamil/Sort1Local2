var module_data=[];


var sha256 = function sha256(ascii) {
    function rightRotate(value, amount) {
        return (value>>>amount) | (value<<(32 - amount));
    };

    var mathPow = Math.pow;
    var maxWord = mathPow(2, 32);
    var lengthProperty = 'length'
    var i, j; // Used as a counter across the whole file
    var result = ''

    var words = [];
    var asciiBitLength = ascii[lengthProperty]*8;

    //* caching results is optional - remove/add slash from front of this line to toggle
    // Initial hash value: first 32 bits of the fractional parts of the square roots of the first 8 primes
    // (we actually calculate the first 64, but extra values are just ignored)
    var hash = sha256.h = sha256.h || [];
    // Round constants: first 32 bits of the fractional parts of the cube roots of the first 64 primes
    var k = sha256.k = sha256.k || [];
    var primeCounter = k[lengthProperty];
    /*/
    var hash = [], k = [];
    var primeCounter = 0;
    //*/

    var isComposite = {};
    for (var candidate = 2; primeCounter < 64; candidate++) {
        if (!isComposite[candidate]) {
            for (i = 0; i < 313; i += candidate) {
                isComposite[i] = candidate;
            }
            hash[primeCounter] = (mathPow(candidate, .5)*maxWord)|0;
            k[primeCounter++] = (mathPow(candidate, 1/3)*maxWord)|0;
        }
    }

    ascii += '\x80' // Append Ƈ' bit (plus zero padding)
    while (ascii[lengthProperty]%64 - 56) ascii += '\x00' // More zero padding
    for (i = 0; i < ascii[lengthProperty]; i++) {
        j = ascii.charCodeAt(i);
        if (j>>8) return; // ASCII check: only accept characters in range 0-255
        words[i>>2] |= j << ((3 - i)%4)*8;
    }
    words[words[lengthProperty]] = ((asciiBitLength/maxWord)|0);
    words[words[lengthProperty]] = (asciiBitLength)

    // process each chunk
    for (j = 0; j < words[lengthProperty];) {
        var w = words.slice(j, j += 16); // The message is expanded into 64 words as part of the iteration
        var oldHash = hash;
        // This is now the undefinedworking hash", often labelled as variables a...g
        // (we have to truncate as well, otherwise extra entries at the end accumulate
        hash = hash.slice(0, 8);

        for (i = 0; i < 64; i++) {
            var i2 = i + j;
            // Expand the message into 64 words
            // Used below if
            var w15 = w[i - 15], w2 = w[i - 2];

            // Iterate
            var a = hash[0], e = hash[4];
            var temp1 = hash[7]
                + (rightRotate(e, 6) ^ rightRotate(e, 11) ^ rightRotate(e, 25)) // S1
                + ((e&hash[5])^((~e)&hash[6])) // ch
                + k[i]
                // Expand the message schedule if needed
                + (w[i] = (i < 16) ? w[i] : (
                        w[i - 16]
                        + (rightRotate(w15, 7) ^ rightRotate(w15, 18) ^ (w15>>>3)) // s0
                        + w[i - 7]
                        + (rightRotate(w2, 17) ^ rightRotate(w2, 19) ^ (w2>>>10)) // s1
                    )|0
                );
            // This is only used once, so *could* be moved below, but it only saves 4 bytes and makes things unreadble
            var temp2 = (rightRotate(a, 2) ^ rightRotate(a, 13) ^ rightRotate(a, 22)) // S0
                + ((a&hash[1])^(a&hash[2])^(hash[1]&hash[2])); // maj

            hash = [(temp1 + temp2)|0].concat(hash); // We don't bother trimming off the extra ones, they're harmless as long as we're truncating when we do the slice()
            hash[4] = (hash[4] + temp1)|0;
        }

        for (i = 0; i < 8; i++) {
            hash[i] = (hash[i] + oldHash[i])|0;
        }
    }

    for (i = 0; i < 8; i++) {
        for (j = 3; j + 1; j--) {
            var b = (hash[i]>>(j*8))&255;
            result += ((b < 16) ? 0 : '') + b.toString(16);
        }
    }
    return result;
};

var serializeArray = function (form) {

	// Setup our serialized data
	var serialized = [];

	// Loop through each field in the form
	for (var i = 0; i < form.elements.length; i++) {

		var field = form.elements[i];

		// Don't serialize fields without a name, submits, buttons, file and reset inputs, and disabled fields
		if (!field.name || field.disabled || field.type === 'file' || field.type === 'reset' || field.type === 'submit' || field.type === 'button') continue;

		// If a multi-select, get all selections
		if (field.type === 'select-multiple') {
			for (var n = 0; n < field.options.length; n++) {
				if (!field.options[n].selected) continue;
				serialized[field.name]=field.options[n].value;
			}
		}

		// Convert field data to a query string
		else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
			    serialized[field.name]=field.value;
		}
	}

	return serialized;

};

function explode(d, s, l){
    var out=[], tmp, pos;
    if (l)
    {
        tmp = s;
        pos = s.indexOf(d)
        while(l-1 && pos>=0)
        {
            out.push(tmp.substr(0, pos));
            tmp = tmp.substr(pos+d.length);
            l--;
            pos = tmp.indexOf(d);
        }
        out.push(tmp);
    }
    else
        out = s.split(d);
    return out;
}

function formatNumber(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1 ')
}

function activate_mod_link(module_id){
    $('button[id^=mod_link_]').removeClass('active');
    $('#mod_link_' + module_id).addClass('active');
}

function setLocation(curLoc){
    try {
      history.pushState(null, null, curLoc);
      return;
    } catch(e) {}
    location.hash = '#' + curLoc;
}

function load_module(module_id){
    var defer=$.Deferred();
    var element =  document.getElementById('content_'+module_id);
    if (typeof(element) == 'undefined' || element == null){
      $("#contents").append('<div class="module_contents" id="content_'+module_id+'" style="margin-top:29px; margin-left: 90px; overflow: auto; padding: 7px; background-color: #ffffff; min-height: 96vh;"></div>');
    }
    if($('#content_'+module_id).html()=="") {
      $.get("/load_module.php",
        {
            id: module_id
        }
      )
      .done(function(data){
        if( data.error == "auth need") location.href="/";
        $('[id^=content_]').css("display","none");
        if($('#content_'+module_id).html()=="") $('#content_'+module_id).html(data.content);
        $('#content_'+module_id).css("display","block");
        activate_mod_link(module_id);
        setLocation("/modules/" + module_id);
        var module_content=document.getElementById('content_'+module_id);
        module_content.addEventListener("click",function(){module_scroll(module_id)});
        if(typeof(module_data[module_id])!="undefined") $('html').scrollTop((module_data[module_id]['scroll_to']));
        defer.resolve();
      });
    }
    else {
      $('[id^=content_]').css("display","none");
      $('#content_'+module_id).css("display","block");
      activate_mod_link(module_id);
      setLocation("/modules/" + module_id);
      var module_content=document.getElementById('content_'+module_id);
      //module_content.addEventListener("click",function(){module_scroll(module_id)});
      if(typeof(module_data[module_id])!="undefined") $('html').scrollTop((module_data[module_id]['scroll_to']));
      defer.resolve();
    }
    return defer.promise();
}

function resolve_api_url(api_url){
    if (api_url === "/api/index.php") {
        return window.location.origin + api_url;
    }

    return api_url;
}

function api_query_obj(api_url,arr,action){
    var defer=$.Deferred();
    arr['action']=action;
    api_url = resolve_api_url(api_url);

    $.ajax(
    {
    	url: api_url,
    	data: JSON.stringify(arr),
    	//data: arr,
    	contentType: "application/json",
    	type: "POST"
    }).done(function(data){
      	defer.resolve(data);
      	if (data.status=="ok"){
      	    if(typeof(data.msg)!="undefined" && data.msg!="") {
          		bootbox.alert({
          		    message: data.msg,
          		    callback: function(){
          		    }
          		});
      	    }
      	}
      	else {
      	    if(data.err!="" && typeof(data.err)!="undefined") {
                $.unblockUI();
                bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: data.err});
              }
      	}
    }).fail(function( xhr, textStatus ) {
        defer.reject();
        $.unblockUI();
        $("body").css("cursor","default");
        $("li a").css("cursor","pointer");
        if(xhr.status==500){
            //$.unblockUI();
            bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "произошла ошибка при выполнении запроса, обратитесь пожалуйста к администратору сервера"});
        }
    	if (typeof(xhr.responseJSON)!="undefined" && xhr.responseJSON.status=="err" && xhr.responseJSON.err=="Auth need"){
    	    location.href="/account/login";
    	}
	  });
    return defer.promise();
}

function api_query_array(api_url,arr,action){
    var defer=$.Deferred();
    arr['action']=action;
    api_url = resolve_api_url(api_url);

    $.ajax(
    {
    	url: api_url,
    	data: JSON.stringify(Object.assign({},arr)),
    	//data: arr,
    	contentType: "application/json",
    	type: "POST"
    }).done(function(data){
        defer.resolve(data);
      	if (data.status=="ok"){
      	    if(typeof(data.msg)!="undefined" && data.msg!="") {
          		bootbox.alert({
          		    message: data.msg,
          		    callback: function(){
          		    }
          		});
      	    }
      	}
      	else {
      	    if(data.err!="" && typeof(data.err)!="undefined") {
                $.unblockUI();
                bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: data.err});
              }
      	}
    }).fail(function( xhr, textStatus ) {
            defer.reject();
            $.unblockUI();
            $("body").css("cursor","default");
            $("li a").css("cursor","pointer");
            if(xhr.status==500){
                //$.unblockUI();
                bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "произошла ошибка при выполнении запроса, обратитесь пожалуйста к администратору сервера"});
            }
    	    if (typeof(xhr.responseJSON)!="undefined" && xhr.responseJSON.status=="err" && xhr.responseJSON.err=="Auth need"){
    		      location.href="/account/login";
    	    }
	  });
    return defer.promise();
}

function api_query(api_url,form_id,action){
    var postdata=$('#'+form_id).serializeJSON();
    var defer=$.Deferred();
    postdata.action=action;
    api_url = resolve_api_url(api_url);

    $.ajax(
    {
    	url: api_url,
    	data: JSON.stringify(postdata),
    	contentType: "application/json",
    	type: "POST"
    }).done(function(data){
        defer.resolve(data);
    	if (data.status=="ok"){
    	    if(typeof(data.msg)!="undefined" && data.msg!="") {
        		bootbox.alert({
        		    message: data.msg,
        		    callback: function(){
        		    }
    		    });
	        }
    	}
    	else {
    	    if(data.err!="" && typeof(data.err)!="undefined"){
                $.unblockUI();
                bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: data.err});
            }
    	}
    }).fail(function( xhr, textStatus ) {
        defer.reject();
        $.unblockUI();
        $("body").css("cursor","default");
        $("li a").css("cursor","pointer");
        if(xhr.status==500){
            //$.unblockUI();
            bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "произошла ошибка при выполнении запроса, обратитесь пожалуйста к администратору сервера"});
        }
    	if (typeof(xhr.responseJSON)!="undefined" && xhr.responseJSON.status=="err" && xhr.responseJSON.err=="Auth need"){
    	    location.href="/account/login";
        }
        defer.reject;
    	//alert( [ xhr.status, textStatus ] );
    });
    return defer.promise();
}

function load_company_data(company_id){
    api_query("/api/index.php","delete_company_"+company_id,"get_company").done( function(data){
	var data_html='<form id="new_client_form">\
	<div class="form-group row">\
	        <label for="company_type" class="col-sm-3 col-form-label">ОКОПФ</label>\
	        <div class="col-sm-9">\
    		<select class="form-control" id="company_type" placeholder="" name="okopf">';
	comp_types_len=data.company_types.length;
	for(var i=0; i<comp_types_len; i++){
	    if (data.company_types[i].id == data.type || ( i==0 && data.type==0 )) data_html+="<option value=\""+data.company_types[i].id+"\" selected=\"selected\">"+data.company_types[i].type+"</option>";
	    else data_html+="<option value=\""+data.company_types[i].id+"\">"+data.company_types[i].type+"</option>";
	}
	data_html+='      </select>\
    <input type="hidden" name="company_id" value="'+data.id+'">\
    </div>\
    </div>';
    data_html+='<div class="form-group row">\
	        <label for="company_btype" class="col-sm-3 col-form-label">Тип компании</label>\
	        <div class="col-sm-9">\
    		<select class="form-control" id="company_btype" placeholder="" name="btype">';
	comp_btypes_len=data.company_btypes.length;
	for(var i=0; i<comp_btypes_len; i++){
	    if (data.company_btypes[i].id == data.btype || (data.btype==0 && i==0)) data_html+="<option value=\""+data.company_btypes[i].id+"\" selected=\"selected\">"+data.company_btypes[i].descr+"</option>";
	    else data_html+="<option value=\""+data.company_btypes[i].id+"\">"+data.company_btypes[i].descr+"</option>";
	}
    data_html+='      </select>\
    </div></div>';
    data_html+='<div class="form-group row">\
	        <label for="company_tax_type" class="col-sm-3 col-form-label">Система налогообложения</label>\
	        <div class="col-sm-9">\
    		<select class="form-control" id="company_tax_type" placeholder="" name="tax_type">';
	comp_tax_types_len=data.tax_types.length;
	for(var i=0; i<comp_tax_types_len; i++){
	    if (data.tax_types[i].id == data.tax_type || (data.tax_type==0 && i==0)) data_html+="<option value=\""+data.tax_types[i].id+"\" selected=\"selected\">"+data.tax_types[i].name+" "+data.tax_types[i].tax_rate+" %</option>";
	    else data_html+="<option value=\""+data.tax_types[i].id+"\">"+data.tax_types[i].name+" "+data.tax_types[i].tax_rate+" %</option>";
	}
    data_html+='      </select>\
    </div></div>';
    data_html+='<div class="form-group row">\
    <label for="company_name" class="col-sm-3 col-form-label">Наименование организации</label>\
    <div class="col-sm-9">\
      <input type="text" class="form-control" id="company_name" placeholder="Наименование организации" name="company_name" value=\''+data.name+'\'>\
    </div>\
    <div id="companys_list">\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="company_inn" class="col-sm-3 col-form-label">ИНН</label>\
    <div class="col-sm-9 pull-right">\
      <input type="text" class="form-control" id="inn" placeholder="ИНН" name="inn" value="'+data.inn+'">\
    </div>\
    </div>\
    <div class="form-group row">\
    <label for="inputPassword3" class="col-sm-3 col-form-label">КПП</label>\
    <div class="col-sm-9 pull-right">\
      <input type="text" class="form-control" id="kpp" placeholder="КПП" name="kpp" value="'+data.kpp+'">\
    </div>\
    </div>\
    <a href="#" onclick="$(\'#advanced_company\').toggle()">Дополнительно</a>\
    <div id="advanced_company" style="display:none">\
	<div class="form-group row">\
	    <label for="inputAddress" class="col-sm-3 col-form-label">Юридический адрес</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control" id="address" placeholder="Юридический адрес" name="address" value="'+data.address+'">\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputogrn" class="col-sm-3 col-form-label">ОГРН</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control" id="ogrn" placeholder="ОГРН" name="ogrn" value="'+data.ogrn+'">\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputrs" class="col-sm-3 col-form-label">Расчетный счет</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control" id="rs" placeholder="Расчетный счет" name="rs" value="'+data.rs+'">\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputbank" class="col-sm-3 col-form-label">Банк</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control" id="bank" placeholder="Банк" name="bank" value="'+data.bank+'">\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputks" class="col-sm-3 col-form-label">Кор. счет</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control" id="ks" placeholder="Кор. счет" name="ks" value="'+data.ks+'">\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputbik" class="col-sm-3 col-form-label">БИК</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control" id="bik" placeholder="БИК" name="bik" value="'+data.bik+'">\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputruk" class="col-sm-3 col-form-label">Руководитель</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control" id="ruk" placeholder="Руководитель" name="ruk" value="'+data.ruk+'">\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="inputrukdol" class="col-sm-3 col-form-label">Должность руководителя</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control" id="rukdol" placeholder="Должность руководителя" name="rukdol" value="'+data.rukdol+'">\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="tel" class="col-sm-3 col-form-label">Телефон</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control" id="mphone" placeholder="Телефон" name="mphone" value="'+data.mphone+'">\
	    </div>\
	</div>\
	<div class="form-group row">\
	    <label for="email" class="col-sm-3 col-form-label">E-mail</label>\
	    <div class="col-sm-9 pull-right">\
    	    <input type="text" class="form-control" id="email" placeholder="E-mail" name="email" value="'+data.email+'">\
	    </div>\
	</div>\
    </div>\
    </form>';
    $('#client_content').html(data_html);
    });
}


function get_my_companies(){
 api_query("/api/index.php","some_form","get_my_companies").then(function(data){
    var datalen=data.clients.length;
    var table="<table class=\"table\"><tr><th>№</th><th>Наименование</th><th>ИНН / КПП</th><th>Адрес</th><th></th></tr>";
    for (var i=0; i<datalen; i++){
	table += "<tr><td><div id='my_company_data_"+data.clients[i].id+"'></div>"+(i+1)+"</td><td>" + data.clients[i].name + "</td><td>"+data.clients[i].inn+" / "+data.clients[i].kpp+"</td><td>"+data.clients[i].address+"</td>";
	table += "<td><form id='delete_company_"+data.clients[i].id+"'><input type=\"hidden\" name=\"company_id\" value=\""+data.clients[i].id+"\"><input type='hidden' name='btype' value='3'></form>";
	table += "<a onclick=\"show_company_data1("+data.clients[i].id+",3);\"><img src=\"/new_images/edit.svg\" style=\"width:20px;\"></a>";
	table += " <a ";
	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить вашу компанию?\',function(result){ if(result) api_query('/api/index.php','delete_company_"+data.clients[i].id+"','delete_company').then(function(data){if(data.status=='ok') location.reload()});});\"><img src=\"/new_images/garbage.svg\" style=\"width:20px;\"></a></td>";
	table += "</tr>";
    }
    $("#company_list_new").html(table);
 });

}

    function authorize() {
        api_query_array("/api/index.php",[],"get_seed").then(function(data1){
            var send=new Array();
            send['login']=$('#login').val();
            var pass=$('#password').val();
            var seed=data1.seed;
            send['password']=sha256(pass + seed);
            api_query_array("/api/index.php",send,"login").then(function(data){
                if (data.status=='ok'){
                    
                    location.href='/modules/1';
                
                }
                else {
                    $('#login_alert').html(data.err);
                    $('#login_alert').show();
                }
            });
        })
    }

    function register_user() {
    	var send=new Array();
    	send['lastname']=$('#lastname').val();
    	send['name']=$('#name').val();
        send['middlename']=$('#middlename').val();
        send['inn']=$('#inn').val();
        send['email']=$('#email').val();
        send['mphone']=$('#mphone').val();
    	api_query_array("/api/index.php",send,"register_user").then(function(data){
    		if (data.status=='ok'){
                //location.href='/modules/1';
                $("#content_reg").html('Данные для продолжения регистрации отправлены на вашу почту');
    		}
    		else {
    		    $('#login_alert').html(data.err);
    		    $('#login_alert').show();
    		}
    	});
    }

    function logout() {
	var postdata={action: 'logout'};
	$.ajax(
	    {
		url: '/api/index.php',
		type: 'POST',
		contentType: 'application/json',
		data: JSON.stringify(postdata)
	    }).done(function(data){
		if (data.status=='ok'){
		    location.href='/account/login';
		}
		else {
		    $('#login_alert').html(data.err);
		    $('#login_alert').show();
		}
	    })
    }

function create_window_new(id,header,in_id,content_data){
    var content='<div id="'+id+'" class="window" title="'+header+'">\
    	<div id="'+id+'_content" style="padding: 5px; background-color:#eee; min-width: 750px;">';
    content+=content_data;
    content+='</div>\
    </div>';
    $("#"+in_id).html(content);
    $("#"+id).dialog({width: "auto", height: "600"});
}

function create_window(id,header,in_id,content_data){
    $("#"+id).remove();
    var stopX=0,stopY=0,setX=0,setY=0;
    var content='<div id="'+id+'" class="window">\
    	<div style="background-color: #2e6da4; color: #fff; cursor: move; padding: 5px;">\
		<button type="button" class="close pull-right" onclick="close_window(\''+in_id+'\');"><span>&times;</span></button>\
    		<div id="'+id+'_header" style="padding-right: 20px;">'+header+'</div>\
    	</div>\
    	<div id="'+id+'_content" style="padding: 5px; background-color:#fff;">';
    content+=content_data;
    content+='</div>\
    </div>';
    //$("#"+in_id).html(content);
    document.getElementById(in_id).innerHTML=content;
    var h=parseInt($("#"+id).css('height'));
    $("#"+id).show();
    $("#"+id).css("position","absolute");
    $("#"+id).draggable({ 
        cancel: "div#"+id+"_content",
        drag: function( event, ui ) {
            if(event.pageX<=0){
                if(!setX) stopX=ui.position.left;
                setX=1;
                ui.position.left = stopX;
            }
            if(event.pageY<=30){
                if(!setY) stopY=ui.position.top;
                setY=1;
                ui.position.top = stopY;
            }
            //ui.position.top = Math.max( 30, ui.position.top );
            //ui.position.left = Math.max( 0, ui.position.left );
            //console.log(ui.position.top+" "+ui.position.left+" "+event.pageX+" "+event.pageY+"\n");
        }
    });
    var div_width=$("#"+id).innerWidth();
    var left_pos=window.innerWidth-div_width-20;
    //$("#"+id).css("top",window.pageYOffset+top_pos);
    if(parseInt($("#"+id).offset().left)+div_width+20>window.innerWidth){
      $("#"+id).css("left",left_pos);
      //alert($("#"+id).css("left"));
    }
    //var top=parseInt($("#"+id).offset().top);
    //var newtop=top-20;
    //$("#"+id).css('top',newtop+"px");
    /* $("#"+id+"_content").draggable("option","disabled",true); */
}


function create_window_simple(id,header,in_id,content_data){
    $("#"+id).remove();
    var stopX=0,stopY=0,setX=0,setY=0;
    var content='<div id="'+id+'" class="window_gray">\
    	<div style="background-color: #888; color: #fff; cursor: move; padding: 5px;">\
		<button type="button" class="close pull-right" onclick="$(\'#'+in_id+'\').html(\'\');"><span>&times;</span></button>\
    		<div id="'+id+'_header">'+header+'</div>\
    	</div>\
    	<div id="'+id+'_content" style="padding: 10px; background-color:#fff;">';
    content+=content_data;
    content+='</div>\
    </div>';
    $("#"+in_id).html(content);
    $("#"+id).show();
    $("#"+id).draggable({ 
        cancel: "div#"+id+"_content",
        drag: function( event, ui ) {
            if(event.pageX<=0){
                if(!setX) stopX=ui.position.left;
                setX=1;
                ui.position.left = stopX;
            }
            if(event.pageY<=30){
                if(!setY) stopY=ui.position.top;
                setY=1;
                ui.position.top = stopY;
            }
            //ui.position.top = Math.max( 30, ui.position.top );
            //ui.position.left = Math.max( 0, ui.position.left );
        } 
    });
    /* $("#"+id+"_content").draggable("option","disabled",true); */
}

function create_window_gray(id,header,in_id,content_data){
    $("#"+id).remove();
    var stopX=0,stopY=0,setX=0,setY=0;
    var content='<div id="'+id+'" class="window_gray">\
    	<div style="background-color: #888; color: #fff; cursor: move; padding: 5px;">\
		<button type="button" class="close pull-right" onclick="$(\'#'+in_id+'\').html(\'\');"><span>&times;</span></button>\
    		<div id="'+id+'_header">'+header+'</div>\
    	</div>\
    	<div id="'+id+'_content" style="padding: 10px; background-color:#fff;">';
    content+=content_data;
    content+='</div>\
    </div>';
    $("#"+in_id).html(content);
    $("#"+id).show();
    $("#"+id).draggable({ 
        cancel: "div#"+id+"_content",
        drag: function( event, ui ) {
            if(event.pageX<=0){
                if(!setX) stopX=ui.position.left;
                setX=1;
                ui.position.left = stopX;
            }
            if(event.pageY<=30){
                if(!setY) stopY=ui.position.top;
                setY=1;
                ui.position.top = stopY;
            }
            //ui.position.top = Math.max( 30, ui.position.top );
            //ui.position.left = Math.max( 0, ui.position.left );
        } 
    });
    /* $("#"+id+"_content").draggable("option","disabled",true); */
}

function create_window_centered_gray(id,header,in_id,content_data){
    $("#"+id).remove();
    var stopX=0,stopY=0,setX=0,setY=0;
    var content='<div id="'+id+'" class="window_gray_centered">\
    	<div style="background-color: #888; color: #fff; cursor: move; padding: 5px;">\
		<button type="button" class="close pull-right" onclick="$(\'#'+in_id+'\').html(\'\');"><span>&times;</span></button>\
    		<div id="'+id+'_header" style="padding-right: 20px;">'+header+'</div>\
    	</div>\
    	<div id="'+id+'_content" style="padding: 10px; background-color:#fff; height: 418px;">';
    content+=content_data;
    content+='</div>\
    </div>';
    $("#"+in_id).html(content);
    $("#"+id).show();
    $("#"+id).draggable({ 
        cancel: "div#"+id+"_content",
        drag: function( event, ui ) {
            if(event.pageX<=0){
                if(!setX) stopX=ui.position.left;
                setX=1;
                ui.position.left = stopX;
            }
            if(event.pageY<=30){
                if(!setY) stopY=ui.position.top;
                setY=1;
                ui.position.top = stopY;
            }
            //ui.position.top = Math.max( 30, ui.position.top );
            //ui.position.left = Math.max( 0, ui.position.left );
        }
    });
    var div_height=$("#"+id).innerHeight();
    var top_pos=window.innerHeight/2-div_height/2;
    var div_width=$("#"+id).innerWidth();
    var left_pos=window.innerWidth/2-div_width/2;
    $("#"+id).css("top",window.pageYOffset+top_pos);
    $("#"+id).css("left",left_pos);
    /* $("#"+id+"_content").draggable("option","disabled",true); */
}

function create_window_centered_blue(id,header,in_id,content_data){
    $("#"+id).remove();
    var stopX=0,stopY=0,setX=0,setY=0;
    var content='<div id="'+id+'" class="window_blue_centered">\
    	<div style="background-color: #2e6da4; color: #fff; cursor: move; padding: 5px;">\
		<button type="button" class="close pull-right" onclick="$(\'#'+in_id+'\').html(\'\');"><span>&times;</span></button>\
    		<div id="'+id+'_header" style="padding-right: 20px;">'+header+'</div>\
    	</div>\
    	<div id="'+id+'_content" style="padding: 10px; background-color:#fff;">';
    content+=content_data;
    content+='</div>\
    </div>';
    //$("#"+in_id).html(content);
    if(document.getElementById(in_id)===null) return;
    document.getElementById(in_id).innerHTML=content;
    $("#"+id).show();
    $("#"+id).draggable({ 
        cancel: "div#"+id+"_content",
        drag: function( event, ui ) {
            if(event.pageX<=0){
                if(!setX) stopX=ui.position.left;
                setX=1;
                ui.position.left = stopX;
            }
            if(event.pageY<=30){
                if(!setY) stopY=ui.position.top;
                setY=1;
                ui.position.top = stopY;
            }
            //ui.position.top = Math.max( 30, ui.position.top );
            //ui.position.left = Math.max( 0, ui.position.left );
            //console.log(ui.position.top+" "+ui.position.left+"\n");
        } 
    });
    var div_height=$("#"+id).innerHeight();
    var top_pos=window.innerHeight/2-div_height/2;
    if(top_pos<30) top_pos=30;
    var div_width=$("#"+id).innerWidth();
    var left_pos=window.innerWidth/2-div_width/2;
    //$("#"+id).css("position","absolute");
    //$("#"+id).css("top",window.pageYOffset+top_pos);
    //$("#"+id).css("left",left_pos);
    document.getElementById(id).style.position="absolute";
    
    document.getElementById(id).style.top=(window.pageYOffset+top_pos).toFixed(0)+"px";
    document.getElementById(id).style.left=left_pos.toFixed(0)+"px";
    /* $("#"+id+"_content").draggable("option","disabled",true); */
}

function create_window_left_blue(id,header,in_id,content_data){
    $("#"+id).remove();
    var stopX=0,stopY=0,setX=0,setY=0;
    var content='<div id="'+id+'" class="window">\
    	<div style="background-color: #2e6da4; color: #fff; cursor: move; padding: 5px;">\
		<button type="button" class="close pull-right" onclick="$(\'#'+in_id+'\').html(\'\');"><span>&times;</span></button>\
    		<div id="'+id+'_header">'+header+'</div>\
    	</div>\
    	<div id="'+id+'_content" style="padding: 10px; background-color:#fff;">';
    content+=content_data;
    content+='</div>\
    </div>';
    $("#"+in_id).html(content);
    $("#"+id).show();
    $("#"+id).draggable({ 
        cancel: "div#"+id+"_content",
        drag: function( event, ui ) {
            if(event.pageX<=0){
                if(!setX) stopX=ui.position.left;
                setX=1;
                ui.position.left = stopX;
            }
            if(event.pageY<=30){
                if(!setY) stopY=ui.position.top;
                setY=1;
                ui.position.top = stopY;
            }
            //ui.position.top = Math.max( 30, ui.position.top );
            //ui.position.left = Math.max( 0, ui.position.left );
        } 
    });
    var div_height=$("#"+id).innerHeight();
    var top_pos=window.innerHeight/2-div_height/2;
    if(top_pos<30) top_pos=30;
    var div_width=$("#"+id).innerWidth();
    var div_pos=$("#"+id).position();
    var left_pos=div_pos.left-div_width;
    //if(left_pos<100) left_pos=100;
    //$("#"+id).css("top",window.pageYOffset+top_pos);
    $("#"+id).css("left",left_pos);
    /* $("#"+id+"_content").draggable("option","disabled",true); */
}

function place_to_center(id){
  var div_height=$("#"+id).innerHeight();
  var top_pos=window.innerHeight/2-div_height/2;
  if(top_pos<30) top_pos=30;
  var div_width=$("#"+id).innerWidth();
  var left_pos=window.innerWidth/2-div_width/2;
  $("#"+id).css("top",window.pageYOffset+top_pos);
  $("#"+id).css("left",left_pos);
}

function create_simple_div(id,in_id,content_data){
    $("#"+id).remove();
    var content='<div id="'+id+'" class="window_simple">';
    content+='<div style="background-color: #2e6da4; color: #fff; cursor: move; padding: 5px;">\
	<button type="button" class="close pull-right" onclick="$(\'#'+in_id+'\').html(\'\');"><span>&times;</span></button>\
	<div id="'+id+'_header">Выберите ...</div></div>';
    content+='<div id="'+id+'_content">'+content_data;
    content+='</div>';
    $("#"+in_id).html(content);
    $("#"+id).show();
    //$("#"+id).draggable({ cancel: "div#"+id+"_content" });
    /* $("#"+id+"_content").draggable("option","disabled",true); */
}

function close_window(id){
    $("#"+id).html('');
}

function file_uploader(){
    'use strict';
    //if(typeof(index)=="undefined") index="";
    var url = '/api/index.php';
    $('[id^=fileupload]').fileupload({ 
        url: url, 
        dataType: 'json',
        done: function (e, data) {
    	    var sheets=build_excel_sheets(data.result,data.result.base_type);
    	    create_window('select_price_cols_div_'+data.result.base_id,'Сопоставление колонок','select_price_cols_'+data.result.base_id,sheets);
    	    if(!data.result.hasOwnProperty('selected_page')){
    		      $('#a_sheet_0').click();
    	    }
            else {
                $('#a_sheet_'+data.selected_page).click();
            }
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

function save_file_params(base_id,base_type){
    wait_start();
    api_query("/api/index.php","selected_fields_form_"+base_id+"_"+$('#active_sheet_'+base_type).val(),"SetColAssoc").then(function(data){
        wait_stop();
        if(data.status=="ok") {
            $('#select_price_cols_'+base_id).html('');
            get_price_list_details('price_list_form_'+base_id);
            check_loader_status(data.job_id,base_id,base_type);
	    }
    });
}

function check_loader_status(job_id){
    let send=[];
    send['job_id']=job_id;
    api_query_array("/api/index.php",send,"get_loader_job_status").then(function(data){
        var table='';
        if(parseInt(data.job.percent)<100){
            table+='<div class="progress">\
                <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="'+data.job.percent+'" aria-valuemin="0" aria-valuemax="100" style="width:'+data.job.percent+'%; height:20px;">\
                    <span>'+data.job.percent+'%</span>\
                </div>\
            </div>';
            document.getElementById("loader_progress_bar").innerHTML=table;
            setTimeout(check_loader_status,1000,job_id);
        }
        else{
            //table+='Загрузка завершена';
            document.getElementById("loader_progress_header").innerHTML='Загрузка завершена';
            table+='<div class="progress">\
                <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="'+data.job.percent+'" aria-valuemin="0" aria-valuemax="100" style="width:'+data.job.percent+'%; height:20px;">\
                    <span>'+data.job.percent+'%</span>\
                </div>\
            </div>';
            if(data.job.not_saved_details!==null){
                var len=data.job.not_saved_details.length;
                table+='<h4>Не удалось загрузить следующие детали:</h4><table class="table table-hover">\
                <thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Цена</th><th>кол-во</th><th>срок</th><th>Ошибка</th></tr></thead><tbody>';
                for (let i=0; i<len; i++){
                    table+='<tr><td>'+data.job.not_saved_details[i].article+'</td><td>'+data.job.not_saved_details[i].brand+'</td><td>'+data.job.not_saved_details[i].name+'</td>\
                    <td>'+data.job.not_saved_details[i].price+'</td><td>'+data.job.not_saved_details[i].count+'</td><td>'+data.job.not_saved_details[i].time+'</td>\
                    <td>'+(typeof(data.job.not_saved_details[i].error)!="undefined"?data.job.not_saved_details[i].error.err:"")+'</td></tr>';
                }
                table+='</tbody></table>';
            }
            document.getElementById("loader_progress_bar").innerHTML=table;
            if(data.job.base_type=="3"){
                get_documents("+").then(function(){
                    edit_document('delete_document_'+data.job.base_id,'+');
                });
            }
            else {
                if(data.job.base_type=="2"){
                    get_price_lists().then(function(){
                        get_price_list_details('price_list_form_'+data.job.base_id);
                    });
                }
            }
        }
    });
}

var handle_selects_on_change=function(){
        $("select[name^=column]").on('change', function(){
                var prev_val = $(this).find('option[selected = "selected"]').val();
                //////debugger;
                $(this).find('option[selected = "selected"]').removeAttr('selected');
                $(this).find('option[value = "' + $(this).val() + '"]').attr('selected', "selected");
                $('option[value="'+ prev_val +'"]').show();
                if($(this).val() == 'other')
                {
                        $("input[name='other[" + $(this).attr('id') + "]']").show();
                }
                else
                {
                        $("input[name='other[" + $(this).attr('id') + "]']").hide();
                        if ($(this).val() != 'skip')
                        {
                                $('option[value="'+ $(this).val() +'"]').hide();
                                $(this).removeClass("bg-grey bg-font-grey");
                        }
                        else
                        {
                                $(this).addClass("bg-grey bg-font-grey");
                        }
                }
        });


}

function load_sheet_data(sheet_num,base_id,base_type){
    $('#sheet_'+base_id+'_'+sheet_num).html('Загружаю....');
    api_query("/api/index.php","sheet_form_"+base_id+'_'+sheet_num,"get_uploaded_file_page").then(function(data){
	var table=build_table(data);
	$('#selected_fields_form_'+base_id).html('');
	$('#sheet_'+base_id+'_'+sheet_num).html(table);
	$('#active_sheet_'+base_type).val(sheet_num);
	handle_selects_on_change();
    });
}

function PrintElem(elem)
{
    var mywindow = window.open('', 'PRINT', 'height=600,width=800');

    mywindow.document.write('<html><head><title>' + document.title  + '</title>');
    mywindow.document.write('<style>@media print\
    {    \
        .no-print, .no-print *\
        {\
            display: none !important;\
        }\
    }</style></head><body >');
    //mywindow.document.write('<h1>' + document.title  + '</h1>');
    mywindow.document.write(document.getElementById(elem).innerHTML);
    mywindow.document.write('</body></html>');

    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10*/

    mywindow.print();
    mywindow.close();

    return true;
}

var build_excel_sheets=function(data,base_type){
    var ul='<input type="hidden" name="active_sheet_'+base_type+'" id="active_sheet_'+base_type+'" value="0">\
    \
    <button class="btn btn-primary btn-small pull-right" onclick="save_file_params('+data.base_id+','+base_type+')">Сохранить</button><br/>';
    ul+='';
    ul+='<ul class="nav nav-tabs" id="sheets">';
    $.each(data.sheetNames, function(skey,sval){
    	ul+='<li>';
    	ul+='<form id="sheet_form_'+data.base_id+'_'+skey+'"><input type="hidden" name="base_id" value="'+data.base_id+'"><input type="hidden" name="base_type" value="'+data.base_type+'"><input type="hidden" name="selected_page" value="'+skey+'"></form>';
    	ul+='<a href="#sheet_'+data.base_id+'_'+skey+'" data-toggle="tab" rel="'+skey+'" onclick="load_sheet_data('+skey+','+data.base_id+','+data.base_type+')" id="a_sheet_'+skey+'">'+sval+'</a>';
    	ul+='</li>';
    });
    ul+="</ul>";
    ul+='<div class="tab-content">';
    $.each(data.sheetNames, function(skey,sval){
	     ul+='<div class="tab-pane" id="sheet_'+data.base_id+'_'+skey+'"></div>';
    });
    ul+="</div>";
    return ul;
}

var build_table=function(data)
{
  ////debugger;
  var table_str='<form id = "selected_fields_form_'+data.base_id+'_'+data.selected_page+'"><input type="hidden" name="base_id" value="' + data.base_id + '"/><input type="hidden" name="base_type" value="'+data.base_type+'"/>';
	table_str+='<input type="hidden" name="selected_page" value="';
	if(data.hasOwnProperty('selected_page')) table_str+=data.selected_page;
	else table_str+='0';
	table_str+='">';
    table_str+='<input type="checkbox" name="put_zero_count" id="put_zero_count"> Загружать 0 остатки; \
    <input type="text" name="cross_delimiter" id="cross_delimiter" value="" size="1"> разделитель кроссов\
    <input type="checkbox" name="change_sklad_price" id="change_price_sklad"> Изменять цену продажи на складе';
        var opt_obj = {skip: "Пропустить", art: "Артикул", brand: "Бренд", name: "Наименование", cost_no_nds: "Цена без НДС", cost: "Цена с НДС", nds: "Ставка НДС", cnt: "Остаток", time: "Срок", city: "Город", mcount:"Мин. партия",my_code:"Мой код", place:"Местоположение", sale_price:"Цена продажи",min_count_must_have:"Минимальный остаток",ean13:"Штрих код (EAN13)",analogs:"Кроссы",detail_size:"Размеры детали",descr:"Примечание",images: "Картинки",other: "Другое"};

        if (data.hasOwnProperty('col_assoc'))
        {
                var field_selector_array = [];
                var other_array = [];
                var col_assoc = $.parseJSON(data.col_assoc);
                ////debugger;
                for (var $l = 0; $l < col_assoc.length; $l++)
                {
                        field_selector_array[$l] = "";
                        other_array[$l] = "";
                        if(!opt_obj.hasOwnProperty(col_assoc[$l]))
                        {
                                other_array[$l] = col_assoc[$l];
                                col_assoc[$l] = 'other';
                        }
                        $.each(opt_obj, function(k, val)
                        {
                                field_selector_array[$l] += '<option value="'+ k +'"';
                                if (k == col_assoc[$l])
                                {
                                        field_selector_array[$l] += ' selected = "selected"'
                                }
                                if (col_assoc.indexOf(k) !== -1 && k != 'skip' && k != 'other')
                                {
                                        field_selector_array[$l] += ' style="display: none;"';
                                }
                                // if(k=='skip')
                                // {
                                        // field_selector_array[$l] += " class='form-control bg-grey bg-font-grey'";
                                // }
                                // else
                                // {
                                        // field_selector_array[$l] += " class='form-control'";
                                // }

                                field_selector_array[$l] += '>' + val + '</option>';
                        });
                }
        }
        else
        {
                var field_selector = '';
                $.each(opt_obj, function(k, val)
                {
                        field_selector += '<option value="'+ k +'"';
                        if (k == 'skip')
                                field_selector += ' selected = "selected"';
                        field_selector += '>' + val + '</option>';
                });
        }
        table_str+='<table class="table table-striped table-bordered table-hover" style="font-size:10px;">';
        if(data.data != undefined)
        {
                table_str+='<thead style="background-color: white;"><tr><th>#</th>';
                if(true)//data.data[0].length > 1 && data.data[0][0] != null)
                {
                        //Если загрузка была с одним количеством столбцов а потом изменилось дополнить массив
                        //debugger;
                    if(field_selector_array!=undefined) {
                        if(data.data[0].length>0) var data0len=data.data[0].length;
                            else {
                                if(Object.keys(data.data[0]).length>0) {var data0len=Object.keys(data.data[0]).length}
                            }
                        for(var $l = field_selector_array.length; $l < data0len; $l++)
                        {
                                $.each(opt_obj, function(k, val)
                                {
                                        field_selector_array[$l] += '<option value="'+ k +'"';
                                        if (k == 'skip')
                                        {
                                                field_selector_array[$l] += ' selected = "selected"'
                                        }
                                        field_selector_array[$l] += '>' + val + '</option>';
                                });
                                other_array[$l] = "";
                        }
                    }
			/* if (data.hasOwnProperty(data.data[0])) var datadata0len=data.data[0].length;
			else var datadata0len=0; */
			if (data.data.length>0 || Object.keys(data.data).length>0){
                            if(data.data[0].length>0) var data0len=data.data[0].length;
                            else {
                                if(Object.keys(data.data[0]).length>0) {var data0len=Object.keys(data.data[0]).length}
                            }
                            //alert('array data[0] length='+data.data[0].length);
                            //alert('obj data[0] length='+Object.keys(data.data[0]).length);
                    	    for(var $l = 0; $l < data0len; $l++)
                    	    {
                                table_str += '<th><select class="form-control" name = "columns[' + $l + ']" id = "' + $l + '">';
                                if (typeof field_selector !== "undefined")
                                {
                                        table_str += field_selector;
                                }
                                else
                                        if (typeof field_selector_array !== "undefined")
                                        {
                                                table_str += field_selector_array[$l];
                                        }
                                table_str += '</select><input name="other[' + $l + ']" type="text"';
                                if (typeof field_selector_array !== "undefined" && other_array[$l] !== "")
                                {
                                        table_str += ' style="margin-top:5px;" value ="' + other_array[$l] + '"';
                                }
                                else
                                {
                                        table_str += ' style="display:none;margin-top:5px;" ';
                                }
                                table_str +=' class="form-control" placeholder="Например: Вес"></th>';
                    	    }
			}
			else {
			    table_str += '<th>Лист пустой, данные отсутстуют</th>';
			}
                        table_str+='</tr></thead>';

                        table_str+='<tbody>';

                        var i=1;
                        $.each(data.data,function(index, value){
                                table_str+='<tr>';
                                table_str+='<td>'+i+"</td>";
                                i++;
                                $.each(value,function(ind, val){
                                        table_str+='<td>'+((val==null)?"&nbsp;":val)+'</td>';
                                });
                                table_str+='</tr>';
                        });
                }
                else
                {
                        table_str += '<th>Пусто</th>';
                }
        }

        table_str+='</tbody>';
        table_str+='</table></form>';
        return table_str;
};

function escapeRegExp(string){
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
}

function get_basket_count(){
  api_query("/api/index.php","some_from","get_basket_count").then(function(data){
    if(data.status=="ok"){
      $("#shop_cart_count").html(data.details_count);
    }
  });
}

function str_to_val(str){
    if(typeof(str)!="undefined")
        return str.replace(/'/g,'&#39;').replace(/"/g,'&#34;');
    else return "";
}

function change_company(){
  var send=new Array();
  send['company_id']=$("#mycompany").val();
  send['action']="change_company";
  api_query_array("/api/index.php",send,"change_company").then(function(data){
    if(typeof(data.status)!="undefined" && data.status=="ok"){
      location.reload();
    }
  });
}

function change_my_sklad(){
    var send=new Array();
    send['my_sklad_id']=$("#my_sklad").val();
    //send['action']="change_my_sklad";
    api_query_array("/api/index.php",send,"change_my_sklad").then(function(data){
      if(typeof(data.status)!="undefined" && data.status=="ok"){
        location.reload();
      }
    });
  }

  function change_my_service(){
    var send=new Array();
    send['my_service_id']=$("#my_service").val();
    //send['action']="change_my_sklad";
    api_query_array("/api/index.php",send,"change_my_service").then(function(data){
      if(typeof(data.status)!="undefined" && data.status=="ok"){
        location.reload();
      }
    });
  }

function range(start,len) {
    var result=[];
    for (var idx=start.charCodeAt(0),end=parseInt(start.charCodeAt(0))+parseInt(len); idx <end; ++idx){
      result.push(String.fromCharCode(idx));
    }
    return result;
  };

  function wait_start(){
    $("#loadMe").modal({
        backdrop: "static", //remove ability to close modal with click
        keyboard: false, //remove option to close with keyboard
        show: true //Display loader!
      });
  }

  function wait_stop(){
    $("#loadMe").modal("hide");
  }

  function get_system_messages(){
      api_query("/api/index.php","some_form","get_system_messages").then(function(data){
        var table='<div style="height:300px; overflow:auto;"><table class="table table-hover" style="width:500px;">\
        <thead style="color:black"><tr><th>дата</th><th>сообщение</th></tr></thead><tbody style="color:black">';
        for (var i=0; i<data.system_messages.length; i++){
            table+='<tr><td>'+data.system_messages[i].create_date+'</td><td>'+data.system_messages[i].message+'</td></tr>';
        }
        table+='</tbody></table></dvi>';
        create_window_gray("system_messages_list_div","Системные сообщения","system_messages_list",table);
      });
  }

function getDateToString(date_input){
    //if(typeof(date_input)=="undefined")
        var now = new Date();
    //else var now = new Date(date_input);
    var year=now.getFullYear();
    if(typeof(date_input)!="undefined" && typeof(date_input[0])!="undefined") {
        year+=date_input[0];
    }
    var month = (now.getMonth() + 1);  
    if(typeof(date_input)!="undefined" && typeof(date_input[1])!="undefined") {
        month = (now.getMonth() + date_input[1]);
    }             
    var day = now.getDate();
    if (month < 10) 
        month = "0" + month;
    if (day < 10) 
        day = "0" + day;
    var today = year + '-' + month + '-' + day;
    return today;
}

function toBinary(string) {
    const codeUnits = new Uint16Array(string.length);
    for (let i = 0; i < codeUnits.length; i++) {
      codeUnits[i] = string.charCodeAt(i);
    }
    return String.fromCharCode(...new Uint8Array(codeUnits.buffer));
}

function fromBinary(binary) {
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < bytes.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return String.fromCharCode(...new Uint16Array(bytes.buffer));
}

function module_scroll(module_id){
        if(typeof(module_data[module_id])=="undefined") module_data[module_id]={};
        module_data[module_id]['scroll_to']=parseInt($('html').scrollTop());//e.pageY;
}

function change_my_password(){
    var send=[];
    send['old_password']=$("#old_password").val();
    send['new_password']=$("#new_password").val();
    send['new_password_conf']=$("#new_password_conf").val();
    api_query_array("/api/index.php",send,"change_my_password").then(function(data){
        if(data.status=="ok"){
            bootbox.alert("<span style='color:green;'>Пароль успешно изменен</span>");
        }
        //else bootbox.alert("<span style='color:red;'>Не удалось изменить пароль: "+data.err+"</span>");
    });
}

function isDateValid(dateStr) {
    return !isNaN(new Date(dateStr));
}
