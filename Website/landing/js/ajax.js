$(document).ready(function() {

  $('#login_button').click(async function(event){
    authorize();
  /*  if ($("#name").val().length > 0 && $("#mphone").val().length > 0 && $("#email").val().length > 0 && $('input[type="checkbox"]').prop("checked") == true)  {

    let send = {
      lastname: $("#lastname").val(),
      name: $("#name").val(),
      middlename: $("#middlename").val(),
      inn: $("#inn").val(),
      email: $("#email").val(),
      mphone: $("#mphone").val(),
      action: "register_user"
      };
    let url='https://sort1.pro/api/index.php';
    let response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json;charset=utf-8'
      },
      body: JSON.stringify(send)
    });
    let result = await response.json();
    if(result.status=="ok"){
      $("#server_response").text("Проверьте вашу почту, где подтвердите мобильный телефон, и завершите регистрацию."); */
/*      $('#wrapper').fadeIn(297,function(){
            $('#zb2')
            .css('display', 'block')
            .animate({opacity: 1}, 198);
        });
 */
	//ym(62779606,'reachGoal','conv_reg');
	//gtag('event', 'conv_reg');
	//VK.Retargeting.Event('good-reg');
});
  /*  else {
     // alert(result.err);
      $("#server_response_err").text((result.err));
      $('#wrapper').fadeIn(297,	function(){
            $('#zb2')
            .css('display', 'block')
            .animate({opacity: 1}, 198);
        });
	ym(62779606,'reachGoal','ret_reg');
        gtag('event', 'ret_reg');
        VK.Retargeting.Event('error-reg');
    }
    }
    else {} */
  //});

  $('div.close3, #wrapper').click( function(){
    $('#zb2').animate({opacity: 0}, 198, function(){
      $(this).css('display', 'none');
      $('#wrapper').fadeOut(297);
    });
  });

});


$(document).ready(function() {
  $('#reg_btn').click(async function(event){
    if ($("#reg_name").val().length > 0 && $("#mphone").val().length > 0 && $("#email").val().length > 0 && $('input[type="checkbox"]').prop("checked") == true)  {

    let send = {
      lastname: $("#lastname").val(),
      name: $("#reg_name").val(),
      middlename: $("#middlename").val(),
      inn: $("#inn").val(),
      email: $("#email").val(),
      mphone: $("#mphone").val(),
      action: "register_user"
      };
    let url='/api/index.php';
    let response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json;charset=utf-8'
      },
      body: JSON.stringify(send)
    });
    let result = await response.json();
    if(result.status=="ok"){
      $("#server_response_err").text("Проверьте вашу почту, где подтвердите мобильный телефон, и завершите регистрацию."); 
      $('#wrapper').fadeIn(297,function(){
            $('#zb2')
            .css('display', 'block')
            .animate({opacity: 1}, 198);
        });
	//ym(62779606,'reachGoal','conv_reg');
	//gtag('event', 'conv_reg');
	//VK.Retargeting.Event('good-reg');
//});
    }
    else {
     // alert(result.err);
      $("#server_response_err").text((result.err));
      $('#wrapper').fadeIn(297,	function(){
            $('#zb2')
            .css('display', 'block')
            .animate({opacity: 1}, 198);
        });
	//ym(62779606,'reachGoal','ret_reg');
        //gtag('event', 'ret_reg');
        //VK.Retargeting.Event('error-reg');
    }
    }
    else {} 
  });

  $('div.close3, #wrapper').click( function(){
    $('#zb2').animate({opacity: 0}, 198, function(){
      $(this).css('display', 'none');
      $('#wrapper').fadeOut(297);
    });
  });

});
