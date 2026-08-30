
<?php
 session_start();
 if(!isset($_SESSION['user_id'])) {
   die("Необходимо авторизоваться");
 }
 include "include/db_safe.inc.php";
 $db=new SafeMySQL();
 if(isset($_GET['zakaz_id'])){
   $zakaz_id=$_GET['zakaz_id'];
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
   $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
 }
 if(isset($_GET['document_id'])){
	$zakaz_id=$db->getOne("select zakaz_id from document where id=?i",$_GET['document_id']);
	//$zakaz_id=$_GET['document_id'];
	/*if((int)$zakaz_id>0){
	 $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
	 $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
	}
	else { */
	 $zakaz_details=$db->getAll("select * from document_details where document_id=?i",$_GET['document_id']);
	 $zakaz_data=$db->getRow("select * from document where id=?i",$_GET['document_id']);
	 $zakaz_data['main_company_id']=$zakaz_data['main_company'];
	 $zakaz_data['id']=$zakaz_id;
	//}
 }
 if($_SESSION['main_company']!=$zakaz_data['main_company_id']){
   die("Выберите свой заказ");
 }
 //echo "select * from zakaz where id=$zakaz_id<br>";
 //echo "zakaz_data: ".print_r($zakaz_data,true)."<br>";
 $client_data=$db->getRow("select * from company where id=?i",$zakaz_data['company_id']);
 $mainc_data=$db->getRow("select * from company where id=?i",$zakaz_data['main_company_id']);
 $poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and deleted=0 order by id desc limit 1",$zakaz_data['main_company_id']);
 $pokupatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and deleted=0 order by id desc limit 1",$zakaz_data['company_id']);
 $mainc_taxtype=$db->getRow("select * from tax_type where id=?i",$mainc_data['tax_type']);
 $ruk_arr=explode(" ",$mainc_data['ruk']);
 //echo print_r($ruk_arr,true)."<br>";
 $ruk_name=mb_substr($ruk_arr[1],0,1);
 //echo print_r($ruk_name,true)."<br>";
 $ruk_otch=mb_substr($ruk_arr[2],0,1);
 $ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";
 //echo "main_company_id=".$zakaz_data['main_company_id']."<br>";
 //echo "poluchatel_rs_data: ".print_r($poluchatel_rs_data,true)."<br>";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>

	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title></title>
	<style type="text/css">
		body,div,table,thead,tbody,tfoot,tr,th,td,p { font-family:"Arial Cyr"; font-size:x-small }
		a.comment-indicator:hover + comment { background:#ffd; position:absolute; display:block; border:1px solid black; padding:0.5em;  }
		a.comment-indicator { background:red; display:inline-block; border:1px solid black; width:0.5em; height:0.5em;  }
		comment { display:none;  }
	</style>

</head>

<body>
<table cellspacing="0" border="0">
	<colgroup span="6" width="13"></colgroup>
	<colgroup width="7"></colgroup>
	<colgroup width="8"></colgroup>
	<colgroup span="80" width="13"></colgroup>
	<tr>
		<td colspan=88 height="15" align="right" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>Приложение N 1 к письму ФНС России от 21.10.2013 N ММВ-20-3/96@</font></td>
		</tr>
	<tr>
		<td colspan=8 rowspan=4 height="54" align="left" valign=top><font size=1><a href="https://tamali.net/forms/buchgalteriya/UPD/">Универсальный передаточный документ</a></font></td>
		<td style="border-left: 2px solid #232627" colspan=9 align="left"><font face="Times New Roman">Счет-фактура N </font></td>
		<td style="border-bottom: 1px solid #232627" colspan=10 align="center" sdnum="1049;0;@"><font face="Times New Roman"><?php echo $zakaz_data['id']?><br></font></td>
		<td colspan=2 align="center"><font face="Times New Roman">от</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=10 align="center" sdnum="1049;0;@"><font face="Times New Roman"><?php echo date("d.m.Y",time(strtotime($zakaz_data['create_date'])))?><br></font></td>
		<td colspan=4 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>(1)</font></td>
		<td colspan=45 rowspan=3 align="right" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>Приложение N 1 к постановлению Правительства Российской Федерации от 26 декабря 2011 года № 1137<br> (в редакции постановления Правительства Российской Федерации от 19.08.2017 № 981)</font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" colspan=9 align="left"><font face="Times New Roman">Исправление N</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=10 align="center" sdnum="1049;0;@"><font face="Times New Roman"><br></font></td>
		<td colspan=2 align="center"><font face="Times New Roman">от</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=10 align="center" sdnum="1049;0;@"><font face="Times New Roman"><br></font></td>
		<td colspan=4 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>(1а)</font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" colspan=35 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><b><font face="Times New Roman" size=1>Продавец</font></b></td>
		<td style="border-bottom: 1px solid #232627" colspan=58 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><?php echo $mainc_data['name'];?><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(2)</font></td>
		</tr>
	<tr>
		<td colspan=4 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Статус: </font></td>
		<td style="border-top: 2px solid #232627; border-bottom: 2px solid #232627; border-left: 2px solid #232627; border-right: 2px solid #232627" colspan=2 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627; border-right: 2px solid #232627" colspan=2 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Адрес</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=58 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>
			<?php echo $mainc_data['address'];?>
		<br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(2а)</font></td>
		</tr>
	<tr>
		<td style="border-right: 2px solid #232627" colspan=8 rowspan=9 height="135" align="left" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br>1 - счет-фактура и передаточный документ (акт) <br>2 - передаточный документ (акт)</font></td>
		<td style="border-left: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>ИНН/КПП продавца</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=58 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>
			<?php echo $mainc_data['inn']." / ".$mainc_data['kpp'];?><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(2б)</font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Грузоотправитель и его адрес</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=58 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(3)</font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Грузополучатель и его адрес</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=58 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(4)</font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>К платежно-расчетному документу</font></td>
		<td style="border-top: 1px solid #232627" colspan=2 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>N </font></td>
		<td style="border-bottom: 1px solid #232627" colspan=8 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=2 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>от</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=46 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(5)</font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><b><font face="Times New Roman" size=1>Покупатель</font></b></td>
		<td style="border-bottom: 1px solid #232627" colspan=58 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>
			<?php echo $client_data['name'];?><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(6)</font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Адрес</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=58 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>
			<?php echo $client_data['address'];?><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(6а)</font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>ИНН/КПП покупателя</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=58 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>
			<?php echo $client_data['inn']." / ".$client_data['kpp'];?><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(6б)</font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Валюта: наименование, код</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=58 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(7)</font></td>
		</tr>
	<tr>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=31 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Идентификатор государственного контракта, договора (соглашения)</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627" colspan=45 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>(8)</font></td>
		</tr>
	<tr>
		<td style="border-right: 2px solid #232627" colspan=8 height="6" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" colspan=80 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=2 rowspan=2 height="91" align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>N п/п</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627" colspan=6 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Код товара/ работ, услуг</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 2px solid #232627; border-right: 1px solid #232627" colspan=14 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Наименование товара (описание выполненных работ, оказанных услуг), имущественного права</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=3 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Код вида товара</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=9 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Единица измерения</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Коли-<br>чество (объем)</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=5 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Цена (тариф) за единицу измерения</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Стоимость товаров <br>(работ, услуг), имущественных прав без налога - всего</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>В том числе сумма акциза</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Нало-<br>говая ставка</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=6 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Сумма налога, предъявляемая покупателю</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Стоимость товаров <br>(работ, услуг), имущественных прав с налогом - всего</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=11 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Страна происхождения товара</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=6 rowspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Регистра-ционный номер таможенной декларации</font></td>
		</tr>
	<tr>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>код</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>условное обозначение (национальное)</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Цифро-<br>вой код</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Краткое наименование</font></td>
		</tr>
	<tr>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=2 height="15" align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>А</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627" colspan=6 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>Б</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 2px solid #232627; border-right: 1px solid #232627" colspan=14 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>1</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=3 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>1а</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>2</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>2а</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>3</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=5 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>4</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>5</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>6</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>7</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=6 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>8</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>9</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>10</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>10а</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=6 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>11</font></td>
		</tr>
		<?php
    $i=1; $zakaz_sum=0;$zakaz_count_sum=0;$sum_without_nds=0;$sum_nds=0;
    foreach($zakaz_details as $zd_key=>$zd_val){
      echo '<tr>
			 <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=2 height="16" align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>'.$i.'<br></font></td>
			 <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627" colspan=6 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 2px solid #232627; border-right: 1px solid #232627" colspan=14 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>'.$zd_val['name'].' ('.$zd_val['brand'].' '.$zd_val['article'].')</td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=3 align="center" valign=middle sdnum="1049;0;@"></td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=2 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1></td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1>шт.</td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 align="center" valign=middle sdnum="1049;0;#&nbsp;##0">'.$zd_val['count'].'</td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=5 align="center" valign=middle sdnum="1049;0;#&nbsp;##0,00">';
        if((int)$mainc_taxtype['is_nds']==1){
          echo number_format(round(($zd_val['price'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        }
        else
          echo number_format($zd_val['price'],2,"."," ");
        echo '</td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdnum="1049;0;#&nbsp;##0,00">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo number_format(round(($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        else
          echo number_format(round($zd_val['price']*$zd_val['count'],2),2,"."," ");
        echo '</td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 align="center" valign=middle sdnum="1049;0;#&nbsp;##0,00"></td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 align="center" valign=middle sdnum="1049;0;#&nbsp;##0,00">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo $mainc_taxtype['tax_rate']."%";
        else
          echo "Без НДС";
        echo '</td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=6 align="center" valign=middle sdval="0" sdnum="1049;0;#&nbsp;##0,00">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo number_format(round(($zd_val['price']*$zd_val['count'])-($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        echo '</td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdval="0" sdnum="1049;0;#&nbsp;##0,00">'.number_format($zd_val['price']*$zd_val['count'],2,"."," ").'</td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=4 align="center" valign=middle sdnum="1049;0;@">';

        echo '</td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdnum="1049;0;@">';

        echo '</td>
        <td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=6 align="center" valign=middle sdnum="1049;0;@">';

        echo '</td>';
      echo '</tr>';
      $i++;
      $zakaz_sum+=$zd_val['price']*$zd_val['count'];
      $zakaz_count_sum+=$zd_val['count'];
      $sum_without_nds+=round(($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
      $sum_nds+=round(($zd_val['price']*$zd_val['count'])-($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
    }
    ?>

	<tr>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=2 height="16" align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627" colspan=6 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 2px solid #232627; border-right: 1px solid #232627" colspan=35 align="left" valign=middle sdnum="1049;0;@"><b><font face="Times New Roman" size=1>Всего к оплате</font></b></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdval="0" sdnum="1049;0;#&nbsp;##0,00"><font face="Times New Roman" size=1>
		<?php echo number_format($sum_without_nds,2,"."," ");?></font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=8 align="center" valign=middle sdnum="1049;0;#&nbsp;##0,00"><font face="Times New Roman" size=1>Х</font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=6 align="center" valign=middle sdval="0" sdnum="1049;0;#&nbsp;##0,00"><font face="Times New Roman" size=1>
		<?php echo number_format($sum_nds,2,"."," ");?></font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 1px solid #232627; border-left: 1px solid #232627; border-right: 1px solid #232627" colspan=7 align="center" valign=middle sdval="0" sdnum="1049;0;#&nbsp;##0,00"><font face="Times New Roman" size=1>
		<?php echo number_format($zakaz_sum,2,"."," ");?></font></td>
		<td colspan=17 align="center" valign=middle sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td style="border-top: 1px solid #232627" colspan=8 height="6" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-top: 1px solid #232627; border-left: 2px solid #232627" colspan=80 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td colspan=8 height="30" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Документ составлен на </font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Руководитель организации <br>или иное уполномоченное лицо</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=6 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=15 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><?php if(!empty($mainc_data['ruk'])) echo $ruk; else echo ""; ?><br></font></td>
		<td colspan=16 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Главный бухгалтер <br>или иное уполномоченное лицо</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=6 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=15 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><?php if(!empty($mainc_data['buh'])) echo $mainc_data['buh']; else echo $ruk; ?><br></font></td>
		</tr>
	<tr>
		<td style="border-bottom: 1px solid #232627" colspan=3 height="15" align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=5 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1> листах</font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=6 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(подпись)</font></td>
		<td align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=15 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(ф.и.о.)</font></td>
		<td colspan=16 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=6 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(подпись)</font></td>
		<td align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=15 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(ф.и.о.)</font></td>
		</tr>
	<tr>
		<td style="border-right: 2px solid #232627" colspan=8 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Индивидуальный предприниматель</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=6 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=15 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=35 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td style="border-right: 2px solid #232627" colspan=8 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 2px solid #232627; border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 2px solid #232627" colspan=19 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>или иное уполномоченное лицо</font></td>
		<td style="border-bottom: 2px solid #232627" colspan=6 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(подпись)</font></td>
		<td style="border-bottom: 2px solid #232627" align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 2px solid #232627" colspan=15 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(ф.и.о.)</font></td>
		<td style="border-bottom: 2px solid #232627" colspan=3 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-top: 1px solid #232627; border-bottom: 2px solid #232627" colspan=35 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(реквизиты свидетельства о государственной регистрации индивидуального предпринимателя)</font></td>
		</tr>
	<tr>
		<td colspan=88 height="6" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td colspan=22 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Основание передачи (сдачи) / получения (приемки)</font></td>
		<td align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=60 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[8]</font></td>
		</tr>
	<tr>
		<td colspan=22 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-top: 1px solid #232627" colspan=60 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(договор; доверенность и др.)</font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td colspan=16 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Данные о транспортировке и грузе</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=69 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[9]</font></td>
		</tr>
	<tr>
		<td colspan=16 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-top: 1px solid #232627" colspan=69 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(транспортная накладная, поручение экспедитору, экспедиторская / складская расписка и др. / масса нетто/ брутто груза, если не приведены ссылки на транспортные документы, содержащие эти сведения)</font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td style="border-right: 2px solid #232627" colspan=45 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Товар (груз) передал / услуги, результаты работ, права сдал</font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=42 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Товар (груз) получил / услуги, результаты работ, права принял </font></td>
		</tr>
	<tr>
		<td style="border-bottom: 1px solid #232627" colspan=13 height="15" align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=13 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=14 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[10]</font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=13 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=10 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=14 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[15]</font></td>
		</tr>
	<tr>
		<td colspan=13 height="15" align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(должность)</font></td>
		<td align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=13 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(подпись)</font></td>
		<td align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=14 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(ф.и.о.)</font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=13 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(должность)</font></td>
		<td align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=10 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(подпись)</font></td>
		<td align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=14 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(ф.и.о.)</font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td colspan=15 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Дата отгрузки, передачи (сдачи)</font></td>
		<td align="right" sdnum="1049;0;@"><font face="Times New Roman" size=1>&quot;</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=2 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>&quot;</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=9 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=2 align="right" sdnum="1049;0;@"><font face="Times New Roman" size=1>20</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=2 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=10 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>г.</font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[11]</font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=13 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Дата получения (приемки)</font></td>
		<td align="right" sdnum="1049;0;@"><font face="Times New Roman" size=1>&quot;</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=2 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>&quot;</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=6 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=2 align="right" sdnum="1049;0;@"><font face="Times New Roman" size=1>20</font></td>
		<td style="border-bottom: 1px solid #232627" colspan=2 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=12 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>г.</font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[16]</font></td>
		</tr>
	<tr>
		<td colspan=42 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Иные сведения об отгрузке, передаче</font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=39 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Иные сведения о получении, приемке</font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td style="border-bottom: 1px solid #232627" colspan=42 height="15" align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[12]</font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=39 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[17]</font></td>
		</tr>
	<tr>
		<td style="border-top: 1px solid #232627" colspan=42 height="15" align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(ссылки на неотъемлемые приложения, сопутствующие документы, иные документы и т.п.)</font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-top: 1px solid #232627" colspan=39 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(информация о наличии/отсутствии претензии; ссылки на неотъемлемые приложения, и другие  документы и т.п.)</font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td colspan=42 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Ответственный за правильность оформления факта хозяйственной жизни</font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=39 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Ответственный за правильность оформления факта хозяйственной жизни</font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td style="border-bottom: 1px solid #232627" colspan=13 height="15" align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=13 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=14 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[13]</font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=13 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=10 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=14 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[18]</font></td>
		</tr>
	<tr>
		<td colspan=13 height="15" align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(должность)</font></td>
		<td align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=13 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(подпись)</font></td>
		<td align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=14 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(ф.и.о.)</font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=13 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(должность)</font></td>
		<td align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=10 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(подпись)</font></td>
		<td align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=14 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(ф.и.о.)</font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td colspan=42 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Наименование экономического субъекта – составителя документа (в т.ч. комиссионера / агента)</font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=39 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1>Наименование экономического субъекта - составителя документа</font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td style="border-bottom: 1px solid #232627" colspan=42 height="15" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[14]</font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-bottom: 1px solid #232627" colspan=39 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>[19]</font></td>
		</tr>
	<tr>
		<td style="border-top: 1px solid #232627" colspan=42 height="15" align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(может не заполняться при проставлении печати в М.П., может быть указан ИНН / КПП)</font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-top: 1px solid #232627" colspan=39 align="center" valign=top sdnum="1049;0;@"><font face="Times New Roman" size=1>(может не заполняться при проставлении печати в М.П., может быть указан ИНН / КПП)</font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
	<tr>
		<td colspan=13 height="15" align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>М.П.</font></td>
		<td colspan=29 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-right: 2px solid #232627" colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td style="border-left: 2px solid #232627" align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=13 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1>М.П.</font></td>
		<td colspan=26 align="left" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		<td colspan=3 align="center" sdnum="1049;0;@"><font face="Times New Roman" size=1><br></font></td>
		</tr>
</table>
<!-- ************************************************************************** -->
</body>

</html>
