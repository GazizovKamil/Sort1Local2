<?php
//print_r($_POST);
include "/var/www/html1/include/lib.php";
if($routes[1]=="account" && $routes[2]=="login") {
 if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
    echo "Logged In";
 }
 else {
    echo "<script> 
    </script>";
    echo "<div class='alert alert-danger role='alert' id='login_alert' style='display:none'></div><div class=\"col-sm-6\">Авторизация";
    //echo "<form method=\"POST\" action=\"/account/login\" id=\"login_form\">";
    echo "<div class=\"form-group\">
    <label for=\"login\">Имя пользователя</label>
    <input type=\"text\" class=\"form-control\" id=\"login\" aria-describedby=\"loginHelp\" placeholder=\"Имя пользователя\" name=\"login\">
    </div>";
    $seed=hash("sha256",md5(date("Y-m-d")));
    echo "<div class=\"form-group\">
    <label for=\"password\">Пароль</label>
    <input type=\"password\" class=\"form-control\" id=\"password\" aria-describedby=\"passwordHelp\" placeholder=\"Пароль\" name=\"password\" onchange1=\"authorize();\">
    <input type=\"hidden\" id=\"seed\" value=\"$seed\" name=\"seed\">
    </div>";
    echo "<button type=\"button\" class=\"btn btn-primary\" onclick=\"authorize();\">Войти</button>";
    //echo "</form>";
    echo "</div>";
 }
}
?>
