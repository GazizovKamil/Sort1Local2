var calendar_plan_selected_date=new Date();

var $currentPopover = null;
  $(document).on('shown.bs.popover', function (ev) {
    var $target = $(ev.target);
    if ($currentPopover && ($currentPopover.get(0) != $target.get(0))) {
      $currentPopover.popover('toggle');
    }
    $currentPopover = $target;
  }).on('hidden.bs.popover', function (ev) {
    var $target = $(ev.target);
    if ($currentPopover && ($currentPopover.get(0) == $target.get(0))) {
      $currentPopover = null;
    }
  });

$.extend({
    quicktmpl: function (template) {return new Function("obj","var p=[],print=function(){p.push.apply(p,arguments);};with(obj){p.push('"+template.replace(/[\r\t\n]/g," ").split("{{").join("\t").replace(/((^|\}\})[^\t]*)'/g,"$1\r").replace(/\t:(.*?)\}\}/g,"',$1,'").split("\t").join("');").split("}}").join("p.push('").split("\r").join("\\'")+"');}return p.join('');")}
});

$.extend(Date.prototype, {
  //provides a string that is _year_month_day, intended to be widely usable as a css class
  toDateCssClass:  function () { 
    return '_' + this.getFullYear() + '_' + (this.getMonth() + 1) + '_' + this.getDate(); 
  },
  //this generates a number useful for comparing two dates; 
  toDateInt: function () { 
    return ((this.getFullYear()*12) + this.getMonth())*32 + this.getDate(); 
  },
  toTimeString: function() {
    var hours = this.getHours(),
        minutes = this.getMinutes(),
        hour = hours,
        ampm = "";
    if (hours === 0 && minutes===0) { return ''; }
    if (minutes > 0) {
      return hour + ':' + minutes + ampm;
    }
    else
      return hour + ':00';
    return hour + ampm;
  }
});


(function ($) {

  //t here is a function which gets passed an options object and returns a string of html. I am using quicktmpl to create it based on the template located over in the html block
  var t = $.quicktmpl($('#tmpl_plan').get(0).innerHTML);
  
  function calendar_plan($el, options) {
    //actions aren't currently in the template, but could be added easily...
    $el.on('click', '.js-cal-prev', function () {
      switch(options.mode) {
        case 'year': options.date.setFullYear(options.date.getFullYear() - 1); break;
        case 'month': options.date.setMonth(options.date.getMonth() - 1); break;
        case 'week': options.date.setDate(options.date.getDate() - 7); break;
        case 'day':  options.date.setDate(options.date.getDate() - 1); break;
      }
      rebuild_calendar_plan('holder_plan');
      //draw();
    }).on('click', '.js-cal-next', function () {
      switch(options.mode) {
        case 'year': options.date.setFullYear(options.date.getFullYear() + 1); break;
        case 'month': options.date.setMonth(options.date.getMonth() + 1); break;
        case 'week': options.date.setDate(options.date.getDate() + 7); break;
        case 'day':  options.date.setDate(options.date.getDate() + 1); break;
      }
      rebuild_calendar_plan('holder_plan');
      //draw();
    }).on('click', '.js-cal-option.btn', function () {
      var $t = $(this), o = $t.data();
      if (o.date) { o.date = new Date(o.date); calendar_plan_selected_date=o.date; }
      $.extend(options, o);
      //options.date=o.date;
      //rebuild_calendar_plan('holder_plan');
      draw();
    }).on('click', '.calendar-month', function () {
      var $t = $(this), o = $t.data();
      if (o.date) { o.date = new Date(o.date); calendar_plan_selected_date=o.date; }
      $.extend(options, o);
      //options.date=o.date;
      rebuild_calendar_plan('holder_plan');
      //draw();
    }).on('click', '.js-cal-years', function () {
      var $t = $(this), 
          haspop = $t.data('popover'),
          s = '', 
          y = options.date.getFullYear() - 2, 
          l = y + 5;
      if (haspop) { return true; }
      for (; y < l; y++) {
        s += '<button type="button" class="btn btn-default btn-lg btn-block js-cal-option" data-date="' + (new Date(y, 1, 1)).toISOString() + '" data-mode="year">'+y + '</button>';
      }
      $t.popover({content: s, html: true, placement: 'auto top'}).popover('toggle');
      return false;
    }).on('click', '.event', function () {
      var $t = $(this), 
          index = +($t.attr('data-index')), 
          haspop = $t.data('popover'),
          data, time;
          
      if (haspop || isNaN(index)) { return true; }
      data = options.data[index];
      time = data.start.toTimeString();
      if (time && data.end) { time = time + ' - ' + data.end.toTimeString(); }
      //$t.data('popover',true);
      //$t.popover({content: '<p><strong>' + time + '</strong></p>'+data.text, html: true, placement: 'auto left'}).popover('toggle');
      //edit_service_note(data.workplace_id,data.start_note.getHours(),(data.start_note.getMinutes() < 30 ? 0 : 30),data.start_note.toLocaleDateString());
      return false;
    });
    function dayAddEvent(index, event) {
      if (!!event.allDay) {
        //monthAddEvent(index, event);
        return;
      }
      var $event = $('<div/>', {'class': 'event', text: event.text, title: event.title, 'data-index': index}),
          start = event.start,
          end = event.end || start,
          time = event.start.toTimeString(),
          hour = start.getHours(),
          endhour = end.getHours(),
          timeclass = '.'+event.workplace_id+'_time-22-0',
          startint = start.toDateInt(),
          dateint = options.date.toDateInt(),
          endint = end.toDateInt();
      if (startint > dateint || endint < dateint) { return; }
      if (!!time) {
        $event.html('' + time + '-' + event.end.toTimeString() + '</strong><div> ' + event.title1 + '</div>');
      }
      $event.toggleClass('begin', startint === dateint);
      $event.toggleClass('end', endint === dateint);
      if (hour < 6) {
        timeclass = '.'+event.workplace_id+'_time-0-0';
      }
      if (hour>=6 && hour < 22) {
        timeclass = '.'+event.workplace_id+'_time-' + hour + '-' + (start.getMinutes() < 30 ? '0' : '30');
      }
      var rows=(endhour-hour)*2+(end.getMinutes() < 30 ? 0 : 1)-(start.getMinutes() < 30 ? 0 : 1);
      //console.log("start:"+start.toDateInt());
      //console.log("stop:"+end.toDateInt());
      var delhour=hour,delminutes=start.getMinutes();
      for(var i=0;i<rows; i++){
        if(i>0) $("."+event.workplace_id+"_time-"+delhour+"-"+delminutes).remove();
        if(delminutes==30) {delminutes=0; delhour++;}
        else delminutes=30;
      }
      $(timeclass).html('');
      var $event_over=$('<div/>',{'class': timeclass.replace(/\./g,"")+"_div",html:'<div class="pull-right">\
      <img src="/new_images/edit.svg" style="width:15px;cursor:pointer;" onclick="edit_service_note('+event.workplace_id+','+event.start_note.getHours()+','+(event.start_note.getMinutes() < 30 ? 0 : 30)+',\''+event.start_note.toLocaleDateString()+'\');"> \
      <img src="/new_images/garbage.svg" style="width:15px;cursor:pointer;" onclick="delete_service_note('+event.note_id+');"> \
      </div>'});
      //$event_over.append('<img src="/new_images/edit.svg" class="pull-righ" style="width:14px;cursor:pointer;" onclick="edit_service_note('+event.workplace_id+','+hour+','+(start.getMinutes() < 30 ? 0 : 30)+');">');
      $event_over.append($event);
      //$event_over.css('height',"100%");
      //$event.css('position',"relative");
      //$event.css('top',"15px");
      $(timeclass).append($event_over);
      $(timeclass).attr('rowspan',rows);
      $(timeclass).attr('ondblclick',"edit_service_note("+event.workplace_id+","+event.start_note.getHours()+","+(event.start_note.getMinutes() < 30 ? 0 : 30)+",'"+event.start_note.toLocaleDateString()+"');");
      $(timeclass).css('background-color',"#"+(event.workplace_id%10)+"ddddd");
    }
    
    function monthAddEvent(index, event) {
      var $event = $('<div/>', {'class': 'event', text: event.title, title: event.title, 'data-index': index}),
          e = new Date(event.start),
          dateclass = e.toDateCssClass(),
          day = $('.' + e.toDateCssClass()),
          empty = $('<div/>', {'class':'clear event', html:'&nbsp;'}), 
          numbevents = 0, 
          time = event.start.toTimeString(),
          endday = event.end && $('.' + event.end.toDateCssClass()).length > 0,
          checkanyway = new Date(e.getFullYear(), e.getMonth(), e.getDate()+40),
          existing,
          i;
        $event.toggleClass('all-day', !!event.allDay);
        // podp = плановые оплаты из документов поступления
        if(day.find(".day_data_plan_minus").html() && day.find(".day_data_plan_minus").html()!=""){

        }
        else {
            day.append('<div class="day_data_plan_minus"><div class="row"><div class="col-sm-6"><b>Расходы:</b></div><div class="col-sm-6 day_data_plan_minus_sum" style="font-weight:bold;"></div></div></div>');
        }
        if(day.find(".day_data_plan_plus").html() && day.find(".day_data_plan_plus").html()!=""){

        }
        else {
            day.append('<div class="day_data_plan_plus"><div class="row"><div class="col-sm-6"><b>Доходы:</b></div><div class="col-sm-6 day_data_plan_plus_sum" style="font-weight:bold;"></div></div></div>');
        }
        var day_minus_sum=(day.find(".day_data_plan_minus_sum").text()?parseFloat(day.find(".day_data_plan_minus_sum").text()):0);
        var day_plus_sum=(day.find(".day_data_plan_plus_sum").text()?parseFloat(day.find(".day_data_plan_plus_sum").text()):0);
        if(event.type=="doc"){
            var podp_sum=(day.find(".day_podp_sum").text()?parseFloat(day.find(".day_podp_sum").text()):0);
            if(typeof(event.document_det_pos)!="undefined"){
              podp_sum+=parseFloat(event.document_det_pos.pos_sum);
              day_minus_sum+=parseFloat(event.document_det_pos.pos_sum);
            }
            $event.html('<div style="padding-left: 10px;"><div class="row"><div class="col-sm-6" title="плановые оплаты из документов поступления"> ПОДП:</div><div class="day_podp_sum col-sm-6">'+podp_sum.toFixed(2)+'</div></div></div>');
            var day_podp_info=day.find(".day_podp_info");
            if(typeof(day_podp_info.html())!="undefined" && day_podp_info.html()!=""){
                day_podp_info.html($event.html());
            }
            else {
                day.find(".day_data_plan_minus").append('<div class="day_podp_info">'+$event.html()+"</div>");
            }
        }
        if(event.type=="rko"){
            var rkos_sum=(day.find(".day_rkos_sum").text()?parseFloat(day.find(".day_rkos_sum").text()):0);
            rkos_sum+=parseFloat(event.rkos.summ);
            day_minus_sum+=parseFloat(event.rkos.summ);
            $event.html('<div style="padding-left: 10px;"><div class="row"><div class="col-sm-6" title="Расходный Кассовый Ордер"> РКО:</div><div class="day_rkos_sum col-sm-6">'+rkos_sum.toFixed(2)+'</div></div></div>');
            var day_rkos_info=day.find(".day_rkos_info");
            if(typeof(day_rkos_info.html())!="undefined" && day_rkos_info.html()!=""){
                day_rkos_info.html($event.html());
            }
            else {
                day.find(".day_data_plan_minus").append('<div class="day_rkos_info">'+$event.html()+"</div>");
            }
        }
        
        if(event.type=="plpay"){
          var plpay_sum=(day.find(".day_plpay_sum").text()?parseFloat(day.find(".day_plpay_sum").text()):0);
          plpay_sum+=parseFloat(event.plpay.summ);
          day_minus_sum+=parseFloat(event.plpay.summ);
          $event.html('<div style="padding-left: 10px;"><div class="row"><div class="col-sm-6" title="Плановые платежи"> Пл.плат.:</div><div class="day_plpay_sum col-sm-6">'+plpay_sum.toFixed(2)+'</div></div></div>');
          var day_plpay_info=day.find(".day_plpay_info");
          if(typeof(day_plpay_info.html())!="undefined" && day_plpay_info.html()!=""){
              day_plpay_info.html($event.html());
          }
          else {
              day.find(".day_data_plan_minus").append('<div class="day_plpay_info">'+$event.html()+"</div>");
              //console.log(day.html());
          }
        }

        if(event.type=="delivery_payments"){
            var dp_sum=(day.find(".day_dp_sum").text()?parseFloat(day.find(".day_dp_sum").text()):0);
            dp_sum+=parseFloat(event.delivery_payments.summ);
            day_minus_sum+=parseFloat(event.delivery_payments.summ);
            $event.html('<div style="padding-left: 10px;"><div class="row"><div class="col-sm-6" title="Оплата поставщикам"> ОП:</div><div class="day_dp_sum col-sm-6">'+dp_sum.toFixed(2)+'</div></div></div>');
            var day_dp_info=day.find(".day_dp_info");
            if(typeof(day_dp_info.html())!="undefined" && day_dp_info.html()!=""){
                day_dp_info.html($event.html());
            }
            else {
                day.find(".day_data_plan_minus").append('<div class="day_dp_info">'+$event.html()+"</div>");
            }
        }
        if(event.type=="obor"){
            var obor_sum=(day.find(".day_obor_sum").text()?parseFloat(day.find(".day_obor_sum").text()):0);
            obor_sum+=parseFloat(event.obor.sale_sum);
            day_plus_sum+=parseFloat(event.obor.sale_sum);
            $event.html('<div style="padding-left: 10px;"><div class="row"><div class="col-sm-6" title="Оборот"> Оборот:</div><div class="day_obor_sum col-sm-6">'+obor_sum.toFixed(2)+'</div></div></div>');
            var day_obor_info=day.find(".day_obor_info");
            if(typeof(day_obor_info.html())!="undefined" && day_obor_info.html()!=""){
                day_obor_info.html($event.html());
            }
            else {
                day.find(".day_data_plan_plus").append('<div class="day_obor_info">'+$event.html()+"</div>");
            }
        }
        if(event.type=="pko"){
            var pkos_sum=(day.find(".day_pkos_sum").text()?parseFloat(day.find(".day_pkos_sum").text()):0);
            pkos_sum+=parseFloat(event.pkos.summ);
            day_plus_sum+=parseFloat(event.pkos.summ);
            $event.html('<div style="padding-left: 10px;"><div class="row"><div class="col-sm-6" title="Приходный Кассовый Ордер"> ПКО:</div><div class="day_pkos_sum col-sm-6">'+pkos_sum.toFixed(2)+'</div></div></div>');
            var day_pkos_info=day.find(".day_pkos_info");
            if(typeof(day_pkos_info.html())!="undefined" && day_pkos_info.html()!=""){
                day_pkos_info.html($event.html());
            }
            else {
                day.find(".day_data_plan_plus").append('<div class="day_pkos_info">'+$event.html()+"</div>");
            }
        }
        day.find(".day_data_plan_minus_sum").html(day_minus_sum.toFixed(2));
        day.find(".day_data_plan_plus_sum").html(day_plus_sum.toFixed(2));
        if(parseFloat(day.find(".day_data_plan_minus_sum").text())>parseFloat(day.find(".day_data_plan_plus_sum").text())) day.find(".day_data_plan_minus_sum").css("color","red");
        else day.find(".day_data_plan_minus_sum").css("color","black");
        if(parseFloat(day.find(".day_data_plan_plus_sum").text())>=parseFloat(day.find(".day_data_plan_minus_sum").text())) day.find(".day_data_plan_plus_sum").css("color","green");
        else day.find(".day_data_plan_plus_sum").css("color","black");
        //day.append('<div class="day_event">'+event.document_det_pos.pos_sum+" </div>");
        //day.append('<div class="day_sum"> sum='+sum+" </div>");
    }

    function yearAddEvents(events, year) {
      var counts = [0,0,0,0,0,0,0,0,0,0,0,0];
      $.each(events, function (i, v) {
        if (v.start.getFullYear() === year) {
            counts[v.start.getMonth()]++;
        }
      });
      $.each(counts, function (i, v) {
        if (v!==0) {
            $('.month-'+i).append('<span class="badge">'+v+'</span>');
        }
      });
    }
    
    function draw() {
      $el.html(t(options));
      //potential optimization (untested), this object could be keyed into a dictionary on the dateclass string; the object would need to be reset and the first entry would have to be made here
      $('.' + (calendar_plan_selected_date).toDateCssClass()).addClass('today');
      if (options.data && options.data.length) {
        if (options.mode === 'year') {
            yearAddEvents(options.data, options.date.getFullYear());
        } else if (options.mode === 'month' || options.mode === 'week') {
            $.each(options.data, monthAddEvent);
        } else {
            $.each(options.data, dayAddEvent);
        }
      }
    }
    
    draw();    
  }
  
  ;(function (defaults, $, window, document) {
    $.extend({
      calendar_plan: function (options) {
        return $.extend(defaults, options);
      }
    }).fn.extend({
      calendar_plan: function (options) {
        options = $.extend({}, defaults, options);
        return $(this).each(function () {
          var $this = $(this);
          calendar_plan($this, options);
        });
      }
    });
  })({
    days: ["Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота"],
    months: ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
    shortMonths: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
    date: (calendar_plan_selected_date),
    daycss: ["c-sunday", "", "", "", "", "", "c-saturday"],
    todayname: "Сегодня",
    thismonthcss: "current",
    lastmonthcss: "outside",
    nextmonthcss: "outside",
    mode: "month",
    data: []
  }, jQuery, window, document);
    
})(jQuery);

var CalDataPlan = [],
    date = calendar_plan_selected_date,
    d = date.getDate(),
    d1 = d,
    m = date.getMonth(),
    y = date.getFullYear(),
    i,
    end, 
    j, 
    c = 1063, 
    c1 = 3329,
    h, 
    m;
    CalDataPlan['RKOs'] = [];



function rebuild_calendar_plan(cal_id,remove=1){
  //$("#"+cal_id).html('');
  CalDataPlan=[];
  //var date = new Date(),
  d = calendar_plan_selected_date.getDate(),
  m = calendar_plan_selected_date.getMonth(),
  y = calendar_plan_selected_date.getFullYear(),
  send=new Array();
  send['year']=y; 
  send['month']=m+1;
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Формируем отчет...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query_array("/api/index.php",send,"get_plan_month_balance").then(function(data1){
    $.unblockUI();
    //var year,month,day,hour,minute;
    var events=data1.documents_data.documents;
    for (var i=0; i<events.length; i++){
      if(events[i].pay_date!='0000-00-00'){
        var str_date=events[i].pay_date.split(" ");
        var stp_date=events[i].pay_date.split(" ");
      }
      else {
        var str_date=events[i].create_date.split(" ");
        var stp_date=events[i].create_date.split(" ");
      }
      date_s=str_date[0].split("-");
      time_s="00:00:00".split(":");
      date_e=stp_date[0].split("-");
      time_e="23:59:00".split(":");
      var title1='Автомобиль: ';
      
      var title="";
      var start_date=new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]);
      var stop_date=new Date(date_e[0],date_e[1]-1,date_e[2],time_e[0],time_e[1]);
      var days=Math.round((stop_date-start_date)/(1000*60*60*24));
      if(days>0 || date_e[1]!=date_s[1]){
            CalDataPlan.push({
              title: title,
              title1: "document",
              type: "doc",
              start: start_date,//new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]),
              end: new Date(date_s[0],parseInt(date_s[1])-1,date_s[2],'22','00'),
              text: "",
              document_det_pos: data1.documents_data.document_det_pos[events[i].id],
              document_job_pos: data1.documents_data.document_job_pos[events[i].id],
              note_id: events[i].id,
              start_note: start_date
            });
      }
      else {
        CalDataPlan.push({
            title: title,
            title1: "document",
            type: "doc",
            start: start_date,//new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]),
            end: new Date(date_s[0],parseInt(date_s[1])-1,date_s[2],'22','00'),
            text: "",
            document_det_pos: data1.documents_data.document_det_pos[events[i].id],
            document_job_pos: data1.documents_data.document_job_pos[events[i].id],
            note_id: events[i].id,
            start_note: start_date
          });
      }
    }

    if(typeof(data1.rkos_data)!="undefined" && data1.rkos_data!==null){
        var events=data1.rkos_data.RKOs;
        for (var i=0; i<events.length; i++){
            var str_date=events[i].create_date.split(" ");
            var stp_date=events[i].create_date.split(" ");
            date_s=str_date[0].split("-");
            time_s="00:00:00".split(":");
            date_e=stp_date[0].split("-");
            time_e="23:59:00".split(":");
            var title1='Автомобиль: ';
            
            var title="";
            var start_date=new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]);
            var stop_date=new Date(date_e[0],date_e[1]-1,date_e[2],time_e[0],time_e[1]);
            var days=Math.round((stop_date-start_date)/(1000*60*60*24));
            CalDataPlan.push({
                title: title,
                title1: "rko",
                type: "rko",
                start: start_date,//new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]),
                end: new Date(date_s[0],parseInt(date_s[1])-1,date_s[2],'22','00'),
                text: "",
                rkos: events[i],
                note_id: events[i].id,
                start_note: start_date
            });
        }
    }

    if(typeof(data1.oborot)!="undefined" && data1.oborot!==null){
        var events=data1.oborot.sale_summ;
        for (var i in events){
            var str_date=events[i].date.split(" ");
            var stp_date=events[i].date.split(" ");
            date_s=str_date[0].split("-");
            time_s="00:00:00".split(":");
            date_e=stp_date[0].split("-");
            time_e="23:59:00".split(":");
            var title1='Автомобиль: ';
            
            var title="";
            var start_date=new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]);
            var stop_date=new Date(date_e[0],date_e[1]-1,date_e[2],time_e[0],time_e[1]);
            var days=Math.round((stop_date-start_date)/(1000*60*60*24));
            CalDataPlan.push({
            title: title,
            title1: "oborot",
            type: "obor",
            start: start_date,//new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]),
            end: new Date(date_s[0],parseInt(date_s[1])-1,date_s[2],'22','00'),
            text: "",
            obor: events[i],
            note_id: events[i].id,
            start_note: start_date
            });
        }
    }

    if(typeof(data1.pkos_data)!="undefined" && data1.pkos_data!==null){
        var events=data1.pkos_data.PKOs;
        for (var i=0; i<events.length; i++){
            var str_date=events[i].create_date.split(" ");
            var stp_date=events[i].create_date.split(" ");
            date_s=str_date[0].split("-");
            time_s="00:00:00".split(":");
            date_e=stp_date[0].split("-");
            time_e="23:59:00".split(":");
            var title1='PKO';
            var title="";
            var start_date=new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]);
            var stop_date=new Date(date_e[0],date_e[1]-1,date_e[2],time_e[0],time_e[1]);
            var days=Math.round((stop_date-start_date)/(1000*60*60*24));
            CalDataPlan.push({
                title: title,
                title1: "pko",
                type: "pko",
                start: start_date,//new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]),
                end: new Date(date_s[0],parseInt(date_s[1])-1,date_s[2],'22','00'),
                text: "",
                pkos: events[i],
                note_id: events[i].id,
                start_note: start_date
            });
        }
    }

    if(typeof(data1.planned_payments)!="undefined" && data1.planned_payments!==null){
      var events=data1.planned_payments;
      for (var i in events){

          time_s="00:00:00".split(":");
          date_e=stp_date[0].split("-");
          time_e="23:59:00".split(":");
          
          var title="";
          if(events[i]['repeat_period']==2){
            var start_date=new Date(y,m,events[i].day_of_month,time_s[0],time_s[1]);
            var stop_date=new Date(y,m,events[i].day_of_month,time_e[0],time_e[1]);
            var days=Math.round((stop_date-start_date)/(1000*60*60*24));
            CalDataPlan.push({
              title: title,
              title1: "planned_payment",
              type: "plpay",
              start: start_date,//new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]),
              end: stop_date,
              text: "",
              plpay: events[i],
              note_id: events[i].id,
              start_note: start_date
            });
          }
      }
  }

    if(data1.delivery_payments!==null){
        var events=data1.delivery_payments.payments;
        for (var i in events){
        var str_date=events[i].create_date.split(" ");
        var stp_date=events[i].create_date.split(" ");
        date_s=str_date[0].split("-");
        time_s="00:00:00".split(":");
        date_e=stp_date[0].split("-");
        time_e="23:59:00".split(":");
        var title1='Автомобиль: ';
        
        var title="";
        var start_date=new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]);
        var stop_date=new Date(date_e[0],date_e[1]-1,date_e[2],time_e[0],time_e[1]);
        var days=Math.round((stop_date-start_date)/(1000*60*60*24));
            CalDataPlan.push({
                title: title,
                title1: "delivery_payments",
                type: "delivery_payments",
                start: start_date,//new Date(date_s[0],date_s[1]-1,date_s[2],time_s[0],time_s[1]),
                end: new Date(date_s[0],parseInt(date_s[1])-1,date_s[2],'22','00'),
                text: "",
                delivery_payments: events[i],
                note_id: events[i].id,
                start_note: start_date
            });
        }
    }
    
    CalDataPlan.sort(function(a,b) { return (+a.start) - (+b.start); });
      
    //data must be sorted by start date
    if(remove){
      $("#holder_plan").remove();
      $("#holder_plan_container").append('<div id="holder_plan"></div>');
    }
    //Actually do everything
    $('#'+cal_id).calendar_plan({ 
      data: CalDataPlan,
      date: calendar_plan_selected_date
    });
  });
  /*$('#'+cal_id).calendar_plan({ 
    data: data
  });*/
}
