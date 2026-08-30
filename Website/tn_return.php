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
	$doc_details=$db->getAll("select * from doc_detail_to_zakaz_detail where document_id=?i",$_GET['document_id']);
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
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Название документа</title>
    <style>
      body { /*background-color: #DCC7C7; */} /* общий фон */

      .A4 {
        width: 1100px;   /* ширина */
        height: 750px; /* высота */
        padding: 40px 40px 30px 50px; /* внутренние отступы - верх, право, низ, лево */
        /*margin: 50px auto;*/  /* выравнивание по центру */
        /*box-shadow: 10px 10px 20px black;*/ /* небольшая тень для объемности */
        background-color: white;  /* цвет фона в блоке */
        font-family:  "Times New Roman"; /* нужный шрифт */
      }
      .container {
        width: 100%;
        height: 100%;
        font-size: 14px;
      }
      .container table {
        width: 100%;
      }
      .table2, .table3, .table6 {
          border-collapse: collapse;
      }
      .table4 {
        border-collapse: collapse;
        border: 1px solid #000000;
      }
      .table4 td{
        border: 1px solid #000000;
        text-align: center;
        vertical-align: middle;
      }
          .centerClass {
        text-align: center;
      }
      .leftClass {
        text-align: left;
        padding-left: 5px;
      }
      .rightClass {
        text-align: right;
      }
      #tabl {
        border: 1px solid #000000;
        text-align: center;
        vertical-align: middle;
        width: 50px;
      }
      #mini {
        font-size: 9px;
        text-align: center;
        vertical-align:top;
      }
      #space {
        width: 20px;
      }
      .border {
        border-bottom: 1px solid #000000;
      }
    </style>
  </head>
  <body>
    <div class = "A4">
      <div class = "container">
        <table class="table6" style="font-size: 8px; width: 60%;"><tbody>
          <tr><td colspan="3"></td><td id="tabl">Код</td></tr>
          <tr><td colspan="3" class ="table2td">Форма по ОКУД</td><td id="tabl">0330212</td></tr>
          <tr><td colspan="2" class="centerClass">ООО "Морской ветер", г. Москва, ул. Социалистическая, д. 11, , тел. 8 (495) 100-1234, ИНН 7700000001,</td><td style="width: 40px;"></td><td rowspan="7" id="tabl">66480000</td></tr>
          <tr><td colspan="2" class="rightClass" style="font-size: 9px;"><b>- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</b></td><td style="width: 40px;"></td></tr>
          <tr><td colspan="2" class="centerClass">КПП 770000001, р/с 40700000000000000000, ЗАО "Банк", г. Москва, БИК 044000000</td><td style="width: 40px;"></td></tr>
          <tr><td colspan="2" class="rightClass" style="font-size: 9px;">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</td><td class="centerClass" style="width: 30px;">по ОКПО</td></tr>
          <tr><td colspan="2" class="centerClass">к/с 30100000000000000000</td><td></td></tr>
          <tr><td colspan="2" class="rightClass" style="font-size: 9px;">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</td><td style="width: 40px;"></td></tr>
          <tr><td colspan="2"></td></tr>

          <tr><td rowspan="6" style="width: 60px;">Грузополучатель</td><td colspan="2" class="leftClass">ООО "Поставщик", г. Москва, ул. Ленина, д. 24, тел. 8 (495) 100-5678, факс 8 (495) 100-5678,</td><td id="tabl" rowspan="6">66480001</td></tr>
          <tr><td colspan="2" class="leftClass">ИНН 770000002, КПП 770000002, р/с 40700000000000000001, ЗАО "Банк", г. Москва,</td></tr>
          <tr><td colspan="2" class="leftClass">БИК 044000001, к/с 30100000000000000001</td></tr>
          <tr><td class="centerClass" style="font-size: 9px;">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</td><td class="centerClass" style="width: 40px;">по ОКПО</td></tr>
          <tr><td colspan="2" class="centerClass">организация, адрес, телефон, факс, банковские реквизиты</td></tr>
          <tr><td colspan="2" style="height: 8px;"></td></tr>

          <tr><td rowspan="6" style="width: 60px;">Поставщик</td><td colspan="2" class="leftClass">ООО "Морской ветер", г. Москва, ул. Социалистическая, д. 11, тел. 8 (495) 100-1234,</td><td id="tabl" rowspan="6">66480000</td></tr>
          <tr><td colspan="2" class="leftClass">факс 8 (495) 100-1234, ИНН 7700000001, КПП 770000001, р/с 40700000000000000000,</td></tr>
          <tr><td colspan="2" class="leftClass">ЗАО "Банк", г. Москва, БИК 044000000, к/с 30100000000000000000</td></tr>
          <tr><td class="centerClass" style="font-size: 9px;">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</td><td class="centerClass" style="width: 40px;">по ОКПО</td></tr>
          <tr><td colspan="2" class="centerClass">организация, адрес, телефон, факс, банковские реквизиты</td></tr>
          <tr><td colspan="2" style="height: 8px;"></td></tr>
          <tr><td rowspan="4" style="width: 60px;">Плательщик</td><td colspan="2" class="leftClass">ООО "Поставщик", г. Москва, ул. Ленина, д. 24, тел. 8 (495) 100-5678, факс 8 (495) 100-5678,</td><td id="tabl" rowspan="3">66480001</td></tr>
          <tr><td colspan="2" class="leftClass">ИНН 770000002, КПП 770000002, р/с 40700000000000000001, ЗАО "Банк", г. Москва,</td></tr>
          <tr><td class="leftClass" style="border-bottom: 1px dashed #000000;">БИК 044000001, к/с 30100000000000000001</td><td class="centerClass" style="width: 40px;">по ОКПО</td></tr>
          <tr><td class="centerClass" style="height: 14px;">организация, адрес, телефон, факс, банковские реквизиты</td><td id="tabl" style="width: 40px;">номер</td><td id="tabl">1</td></tr>
          <tr><td rowspan="3" style="width: 60px;">Основание</td><td class="leftClass" style="height: 14px; border-bottom: 1px dashed #000000;">Возврат товара по договору №1 от 07.04.2020</td><td id="tabl" style="width: 40px;">дата</td><td id="tabl">07.04.2020</td></tr>
          <tr><td class="centerClass" style="height: 14px;">договор, заказ-наряд</td><td id="tabl" style="width: 40px;">номер</td><td id="tabl">124</td></tr>
          <tr><td class="rightClass">Транспортная накладная</td><td id="tabl" style="width: 40px;">дата</td><td id="tabl">12.04.2020</td></tr>
          <tr><td colspan="3" class="rightClass">Вид операции</td><td id="tabl"></td></tr>
        </tbody></table>
        <table class="table6" style="font-size: 8px; width: 60%; margin-top: 30px;"><tbody>
            <tr><td colspan="2"></td><td id="tabl">Номер документа</td><td id="tabl">Дата составления</td><td style="width: 220px;"></td></tr>
            <tr><td></td><td class="rightClass" style="padding-right: 10px;">ТОВАРНАЯ НАКЛАДНАЯ</td><td  id="tabl">125</td><td id="tabl">12.04.2020</td><td style="width: 220px;"></td></tr>
            <tr><td colspan="5"  class="centerClass" style="padding-right: 290px;">ВОЗВРАТ</td></tr>

        </tbody></table>
    </div></div>


    <div class = "A4" ><div class = "container" style="font-size: 13px;">
    <!--  <p style="margin: 0; text-align: right; padding-bottom: 10px;">2-я страница формы № ИНВ-19</p> -->
      <table class = "table4"><tbody>
        <tr><td rowspan="2">Номер по по- рядку</td><td colspan="2">Товар</td>
        <td colspan="2">Единица                измерения</td><td colspan="1" rowspan="2">Вид упаковки</td><td colspan="2">Количество</td><td rowspan="2">Масса брутто</td><td rowspan="2">Количество (масса нетто)</td><td rowspan="2">Цена, руб. коп.</td><td rowspan="2">Сумма без учета НДС, руб. коп.</td>
        <td colspan="2">НДС</td><td rowspan="2">Сумма с учетом НДС, руб. коп.</td></tr>
        <tr><td style="width: 90px;">наименование, характеристики, сорт, артикул товара</td><td>код</td><td style="width: 60px;">наимено-вание</td><td>код по
  ОКЕИ</td><td>в одном месте</td><td>мест, штук</td><td style="width: 90px;">ставка, %</td><td>сумма, руб. коп.</td></tr>
        <tr><td>1</td><td>2</td><td >3</td><td >4</td><td>5</td><td >6</td><td >7</td><td >8</td><td >9</td><td >10</td><td >11</td><td>12</td><td>13</td><td>14</td><td>15</td></tr>
        <tr><td>1</td><td>Ткань сорочечная гладкокр. 65% п-э 35%; х/б шир. 148, РФ</td><td>-</td><td>пог. м</td><td>-</td><td >-</td><td>-</td><td>-</td><td>-</td><td>3,4</td>
          <td>2000</td><td>6800</td><td>-</td><td>-</td><td>6800</td></tr>
        <tr><td>2</td><td>Нитки 35 ЛЛ 2500 м цвет СК-194, РФ</td><td >-</td><td >боб.</td><td>-</td><td >-</td><td>-</td><td>-</td><td>-</td><td>5</td><td >300</td><td >1500</td><td>-</td><td>-</td><td>1500</td></tr>
        <tr><td style="text-align: left; padding-left: 10px;" colspan="7">Итого</td><td>-</td><td>-</td><td>X</td><td>X</td><td>8300</td><td>X</td><td>-</td><td>8300</td></tr>
        <tr><td style="text-align: left; padding-left: 10px;" colspan="7">Всего по накладной</td><td>-</td><td>-</td><td>X</td><td>X</td><td>8300</td><td>X</td><td>-</td><td>8300</td></tr>
      </tbody></table>
    <table class="table6" style="width: 500px; margin-top: 20px;"><tbody>
      <tr><td>Отпуск груза разрешил</td><td id="space"></td><td class="border" style="text-align: center; border-right: 1px solid #000000;">кладовщик</td><td id="space"></td><td colspan="3"></td></tr>
      <tr><td></td><td id="space"></td><td id="mini" style="border-right: 1px solid #000000;">(должность)</td><td id="space"></td><td colspan="3"></td></tr>
      <tr><td class="border" style="text-align: center;">Сухов</td><td id="space"></td><td class="border" style="text-align: center; border-right: 1px solid #000000;">Сухов Н.Н.</td><td id="space"></td><td>Груз принял</td><td id="space"></td>
        <td class="border" style="text-align: center;">директор</td></tr>
      <tr><td id="mini">(подпись)</td><td id="space"></td><td id="mini" style="border-right: 1px solid #000000;">(расшифровка подписи)</td><td id="space"></td><td colspan="2"></td><td id="mini">(должность)</td></tr>
      <tr><td colspan="3" style="border-right: 1px solid #000000;">Главный (старший) бухгалтер</td><td id="space"></td><td class="border" style="text-align: center;">Петров</td><td id="space"></td><td class="border" style="text-align: center;">Петров П.П.</td></tr>
      <tr><td class="border" style="text-align: center;">Климова</td><td id="space"></td><td class="border" style="text-align: center; border-right: 1px solid #000000;">Климова И.Л.</td><td id="space"></td><td id="mini">(подпись)</td><td id="space"></td><td id="mini">(расшифровка подписи)</td></tr>
      <tr><td id="mini">(подпись)</td><td id="space"></td><td id="mini" style="border-right: 1px solid #000000;">(расшифровка подписи)</td><td id="space"></td><td colspan="3"></td></tr>
      <tr><td>Отпуск груза произвел</td><td id="space"></td><td class="border" style="text-align: center; border-right: 1px solid #000000;">специалист</td><td id="space"></td><td colspan="3">Груз получил грузополучатель</td></tr>
      <tr><td></td><td id="space"></td><td id="mini"  style="border-right: 1px solid #000000;">(должность)</td><td id="space"></td><td colspan="3"></td></tr>
      <tr><td class="border" style="text-align: center;">Сухов</td><td id="space"></td><td class="border" style="text-align: center; border-right: 1px solid #000000;">Сухов Н.Н.</td><td id="space"></td><td colspan="3"></td></tr>
      <tr><td id="mini">(подпись)</td><td id="space"></td><td id="mini" style="border-right: 1px solid #000000;">(расшифровка подписи)</td><td id="space"></td><td colspan="3"></td></tr>
      <tr><td colspan="3" style="border-right: 1px solid #000000;">М.П.  "12" ________ 20___ года</td><td id="space"></td><td colspan="3">М.П.  "12" ________ 20___ года</td></tr>
    </tbody></table>
  </div></div>
  </body>
</html>
