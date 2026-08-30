$('.form').find('input, textarea').on('keyup blur focus', function (e) {

  var $this = $(this),
      label = $this.prev('label');

	  if (e.type === 'keyup') {
			if ($this.val() === '') {
          label.removeClass('active highlight');
        } else {
          label.addClass('active highlight');
        }
    } else if (e.type === 'blur') {
    	if( $this.val() === '' ) {
    		label.removeClass('active highlight');
			} else {
		    label.removeClass('highlight');
			}
    } else if (e.type === 'focus') {

      if( $this.val() === '' ) {
    		label.removeClass('highlight');
			}
      else if( $this.val() !== '' ) {
		    label.addClass('highlight');
			}
    }

});

$('.tab a').on('click', function (e) {

  e.preventDefault();

  $(this).parent().addClass('active');
  $(this).parent().siblings().removeClass('active');

  target = $(this).attr('href');

  $('.tab-content > div').not(target).hide();

  $(target).fadeIn(600);

});

$('#reg').on('click', function (e) {

  e.preventDefault();

  var firstElement = document.getElementById("signup");
  var secondElement = document.getElementById("login_reg");
  var forgotElement = document.getElementById("forgot");
  var regActive = document.getElementById("login_btn")
  var entrActive = document.getElementById("signup_btn")

  $(entrActive).removeClass('active');
  $(regActive).addClass('active');
   // находим все элементы <ul>
   // находим все элементы <ul>

  firstElement.style.display = "none";
  secondElement.style.display = "block";
  forgotElement.style.display =  "none";
  $('.form h2').text('Войдите или зарегистрируйтесь');
});

$('#entr').on('click', function (e) {

  e.preventDefault();

  var firstElement = document.getElementById("signup");
  var secondElement = document.getElementById("login_reg");
  var forgotElement = document.getElementById("forgot");
  var regActive = document.getElementById("login_btn")
  var entrActive = document.getElementById("signup_btn")

  $(regActive).removeClass('active');
  $(entrActive).addClass('active');
   // находим все элементы <ul>
   // находим все элементы <ul>

  secondElement.style.display = "none";
  firstElement.style.display = "block";
  forgotElement.style.display =  "none";
  $('.form h2').text('Войдите или зарегистрируйтесь');
});

//сука быдлокодинг... копирую чтоб добавить еще одну кнопку...
$('#entr_btn').on('click', function (e) {

  e.preventDefault();

  var firstElement = document.getElementById("signup");
  var secondElement = document.getElementById("login_reg");
  var forgotElement = document.getElementById("forgot");
  var regActive = document.getElementById("login_btn")
  var entrActive = document.getElementById("signup_btn")

  $(regActive).removeClass('active');
  $(entrActive).addClass('active');
   // находим все элементы <ul>
   // находим все элементы <ul>

  secondElement.style.display = "none";
  firstElement.style.display = "block";
  forgotElement.style.display =  "none";
  $('.form h2').text('Войдите или зарегистрируйтесь');
});

$('#forg_link').on('click', function (e) {

  e.preventDefault();

  var firstElement = document.getElementById("signup");
  var secondElement = document.getElementById("login_reg");
  var forgotElement = document.getElementById("forgot");
  var regActive = document.getElementById("login_btn")
  var entrActive = document.getElementById("signup_btn")

  $(regActive).removeClass('active');
  $(entrActive).removeClass('active');
   // находим все элементы <ul>
   // находим все элементы <ul>

  secondElement.style.display = "none";
  firstElement.style.display = "none";
  forgotElement.style.display =  "block";
  
  $('.form h2').text('Восстановление доступа');
});
