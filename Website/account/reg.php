<?php
//print_r($_POST);
include "/var/www/html1/include/lib.php";
if($routes[1]=="account" && $routes[2]=="reg") {
 if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
    echo "Logged In";
 }
 else {
    echo "<script> 
    </script>";
    echo "<div class='alert alert-danger role='alert' id='login_alert' style='display:none'></div><div class=\"col-sm-6\"><b>Регистрация пользователя</b><hr>";
    //echo "<form method=\"POST\" action=\"/account/login\" id=\"login_form\">";
    echo "<div class=\"form-group\">
    <label for=\"lastname\">Фамилия</label>
    <input type=\"text\" class=\"form-control\" id=\"lastname\" aria-describedby=\"loginHelp\" placeholder=\"Фамилия\" name=\"lastname\">
    </div>
    <div class=\"form-group\">
    <label for=\"name\">Имя</label>
    <input type=\"text\" class=\"form-control\" id=\"name\" aria-describedby=\"loginHelp\" placeholder=\"Имя\" name=\"name\">
    </div>
    <div class=\"form-group\">
    <label for=\"name\">Отчество</label>
    <input type=\"text\" class=\"form-control\" id=\"middlename\" aria-describedby=\"loginHelp\" placeholder=\"Отчество\" name=\"middlename\">
    </div>
    <div class=\"form-group\">
    <label for=\"name\">ИНН организации</label>
    <input type=\"text\" class=\"form-control\" id=\"inn\" aria-describedby=\"loginHelp\" placeholder=\"ИНН организации\" name=\"inn\">
    </div>
    <div class=\"form-group\">
    <label for=\"email\">E-mail</label>
    <input type=\"text\" class=\"form-control\" id=\"email\" placeholder=\"E-mail\" name=\"email\">
    </div>
    <div class=\"form-group\">
    <label for=\"mphone\">Мобильный телефон</label>
    <input type=\"text\" class=\"form-control bfh-phone\" id=\"mphone\" placeholder=\"Мобильный телефон\" name=\"mphone\" data-format=\"+d (ddd) ddd-dd-dd\">
    </div>
    ";
    //$seed=hash("sha256",md5(date("Y-m-d")));
    echo "";
    echo "<button type=\"button\" class=\"btn btn-primary\" onclick=\"register_user();\">Зарегистрировать</button>";
    //echo "</form>";
    echo "</div>";
 }
}
?>
