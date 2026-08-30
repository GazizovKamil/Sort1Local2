<?php
session_start();
include "include/db_safe.inc.php";
$db=new SafeMySQL();
if(isset($_GET['phone_code'])){
  $user1=$db->getRow("select * from users where mphone_confirmation_code=?s and username=?s",$_GET['phone_code'],$_GET['email']);
  if($user1){
    if($user1['email_confirmed']==1){
      include "api/classes/Components/Other/mail_sender.php";
      $message_data = array(
        'to'		=> $user1['email'],
        'to_name' 	=> "",
        'title'		=> "Ваши данные для входа на поисковый портал sort1",
        'text'		=> "Здравствуйте!<br> Вы успешно зарегистрировались на портале управления магазином запасных частей <a href=\"https://sort1.pro\">sort1.pro</a><br>
        Для входа используйте имя пользователя: ".$user1['username']." пароль: ".$user1['password'],
        'alt_text'	=> strip_tags($message),
      );	

      sendMail($message_data);
      $db->query("update users set mphone_confirmed=1 where id=?i",$user1['id']);
      //$_SESSION['user_id']=$user1['id'];
      //$_SESSION['company_id']=$user1['company_id'];
      //$_SESSION['main_company']=$user1['main_company_id'];
      //$_SESSION['roles']=$user1['roles'];
      //Sort1s::activate();
			//file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." activate stoped\nregister 0 started\n",FILE_APPEND);
			//Sort1s::register($db,0);
			//		file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." register 0 stoped\nregister 1 started\n",FILE_APPEND);
			//Sort1s::register($db,1);
			//		file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." register 1 stoped\nparam_sync started\n",FILE_APPEND);
			//Sort1s::param_sync($db);
			//		file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." param_sync stoped\n",FILE_APPEND);
      header("location: /account/login");
      exit(0);
    }
  }
  else {
    echo '
      <body>
        Ошибочный код подтверждения
      </body>
      ';
  }
}
?>
<html>
<head>
<meta charset="utf-8">
<style>
td {
    font-size: 12px;
    padding: 0px 2px 0px 2px;
}
.pad10top{
  padding: 10px 0px 0px 0px;
}
.td_underline {
    border-bottom: 1px solid black;
    vertical-align: bottom;
}
.td_leftline {
  border-left: 1px solid black;
}
.tr {
 text-align: right;
}
.bordered {
 border: 1px solid black;
}
.centered{
  text-align: center;
}
.space{
  width: 10px;
}
.sign{
  width: 120px;
}
.f8pt {
  padding: 0;
  font-size: 8px;
}
.linesign{
  position: relative;
  top: 9px;
  padding: 0;
}
.utext{
  position: relative;
  top: 10px;
  text-align: left;
}
</style>
</head>
<?php
  if(isset($_GET['hash'])){
    $email_confirmation_code=$_GET['hash'];
    $email=$_GET['email'];
    $user=$db->getRow("select * from users where email_confirmation_code=?s and username=?s",$email_confirmation_code,$email);
    if($user){
      $res=$db->query("update users set email_confirmed=1 where id=?i",$user['id']);
      $sms_url="https://smsc.ru/sys/send.php?login=nur100574&psw=Nurkiicq1&phones=".$user['mphone']."&mes=".$user['mphone_confirmation_code']."&fmt=3&id=".$user['id'];
      if((int)$user['mphone_confirmed']==0) {
        if((time()-strtotime($user['mphone_code_sent']))>60) {
          $sms_data=file_get_contents($sms_url);
          $res=$db->query("update users set mphone_code_sent=?s where id=?i",date("Y-m-d H:i:s"),$user['id']);
          $sms_sent=1;
        }
        else {
          $sms_sent=0;
        }

      }
      else {
        echo "<body>Ваши данные уже подтверждены, данные для входа отправлены вам на емаил</body>";
        exit(0);
      }
      file_put_contents("/var/log/sort1/sms_send.log","sms return data:".print_r($sms_data,true)."\n",FILE_APPEND);
      echo '<body>
      <p>Ваш e-mail подтверждён.</p> 
      <form action="/confirm_email.php" method="GET">
      Введите код из смс-сообщения, пришедший на телефон, указанный при регистрации.<br><br>
        <input type="text" name="phone_code"><br><br>
        <input type="hidden" name="email" value="'.$email.'">
        <input type="submit" value="Подтвердить">
      </form>
      <br>
      Если смс нет, новый можно запросить через 3 минуты. Для этого через 3 минуты обновите страницу.
      </body>';
    }
    else {
      echo '
      <body>
        Ошибочный код подтверждения
      </body>
      ';
      //exit(0);
    }
  }
?>
</html>

