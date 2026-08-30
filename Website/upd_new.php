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
   $zakaz_jobs=$db->getAll("select zj.*,sj.name from zakaz_jobs zj 
    left join service_jobs sj on (sj.id=zj.job_id)
    where zj.zakaz_id=?i and (zj.status<100 or zj.status>199)",$zakaz_id);
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
   $zakaz_jobs=$db->getAll("select dj.*,sj.name from document_jobs dj
    left join service_jobs sj on (sj.id=dj.job_id)
    where dj.document_id=?i and dj.deleted=0",$_GET['document_id']);
	 $zakaz_data=$db->getRow("select * from document where id=?i",$_GET['document_id']);
	 $zakaz_data['main_company_id']=$zakaz_data['main_company'];
	 //$zakaz_data['id']=$zakaz_id;
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
 if(isset($_GET['zakaz_id']) && $mainc_taxtype['is_nds']==1 ){
   die("При работе с НДС, распечатка документов возможна только из вкладки документы");
 }
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
      body { /*background-color: #DCC7C7; */ } /* общий фон */

      .A4 {
        width: 900px;   /* ширина */
        height: 700px; /* высота */
        /*padding: 40px 30px 30px 50px;*/ /* внутренние отступы - верх, право, низ, лево */
        /*margin: 50px auto; */ /* выравнивание по центру */
        /*box-shadow: 10px 10px 20px black;*/ /* небольшая тень для объемности */
        background-color: white;  /* цвет фона в блоке */
        font-family:  "Times New Roman"; /* нужный шрифт */
      }
      .A4albom {
        width: 297mm;   /* ширина */
        height: 210mm; /* высота */
        /*padding: 40px 30px 30px 50px;*/ /* внутренние отступы - верх, право, низ, лево */
        /*margin: 50px auto; */ /* выравнивание по центру */
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
      .table6, .table7 {
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
        height: 11px;
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
      #mini {
        font-size: 9px;
        text-align: center;
        vertical-align:top;
      }
      #space {
        width: 10px;
      }
      .border {
        border-bottom: 1px solid #000000;
      }
      .table6 p {
        margin: 0;
      }
      #rectangle{
        width:30px;
        height:10px;
        border: 1px solid #000000;
      }
      #line {
        border-right: 2px solid #000000;
        padding-right: 8px;
      }
      #line2 {
        width: 54px;
        border-right: 2px solid #000000;
      }
      .table7 td {
        height: 12px;
      }
    </style>
 
  </head>
  <body>
    <script src="/js/jquery-3.3.1.js"></script>
    <script>
      function change_gruz_getter(){
        var gruz_getter=$("#gruz_getter").text();
        if(gruz_getter=="") 
          gruz_getter=$("#gruz_getter_input").val();
        var input='<input type="text" id="gruz_getter_input" onkeyup="if(event.keyCode===13) {set_gruz_getter();}" style="width:400px; height: 10px;">';
        $("#gruz_getter").html(input);
        if(gruz_getter!="") 
          $("#gruz_getter_input").val(gruz_getter);
        $("#gruz_getter_input").focus();
      }
      function set_gruz_getter(){
        $("#gruz_getter").html($("#gruz_getter_input").val());
      }
      function change_status(){
        var input='<input type="text" id="status_input" onchange="set_status_div();" style="width:30px; height: 10px;">';
        $("#rectangle").html(input);
        $("#status_input").focus();
      }
      function set_status_div(){
        $("#rectangle").html($("#status_input").val());
      }
      function change_plat_doc_num(){
        var input='<input type="text" id="plat_doc_num_input" onchange="set_plat_doc_num();" style="width:36px; height: 10px;">';
        var plat_doc_num=$("#plat_doc_num").text();
        $("#plat_doc_num").html(input);
        $("#plat_doc_num_input").val(plat_doc_num);
        $("#plat_doc_num_input").focus();
      }
      function set_plat_doc_num(){
        $("#plat_doc_num").html($("#plat_doc_num_input").val());
      }
      function change_num_date(){
        var input='<input type="text" id="num_date_input" onkeyup="if(event.keyCode===13) {set_num_date();}" style="width:256px; height: 10px;">';
        var num_date=$("#num_date").text();
        $("#num_date").html(input);
        $("#num_date_input").val(num_date);
        $("#num_date_input").focus();
      }
      function set_num_date(){
        $("#num_date").html($("#num_date_input").val());
      }
      function change_detail_name(i){
        var input='<input type="text" id="detail_name_input_'+i+'" onkeyup="if(event.keyCode===13) {set_detail_name('+i+');}" style="width:256px; height: 30px;">';
        var detail_name=$("#detail_name_"+i).text();
        $("#detail_name_"+i).html(input);
        $("#detail_name_input_"+i).val(detail_name);
        $("#detail_name_input_"+i).focus();
      }
      function set_detail_name(i){
        $("#detail_name_"+i).html($("#detail_name_input_"+i).val());
      }
      function change_plat_doc_date(){
        var input='<input type="date" id="plat_doc_date_input" onkeyup="if(event.keyCode===13) {set_plat_doc_date();}">';
        var plat_doc_date=$("#plat_doc_date").text();
        $("#plat_doc_date").html(input);
        //$("#plat_doc_date_input").val(plat_doc_date);
        $("#plat_doc_date_input").focus();
      }
      function change_otgruz_doc_num(){
        var input='<input type="text" id="otgruz_doc_num_input" onchange="set_otgruz_doc_num();" style="width:600px; height: 10px;">';
        var otgruz_doc_num=$("#otgruz_doc_num").text();
        $("#otgruz_doc_num").html(input);
        $("#otgruz_doc_num_input").val(otgruz_doc_num);
        $("#otgruz_doc_num_input").focus();
      }
      function set_otgruz_doc_num(){
        $("#otgruz_doc_num").html($("#otgruz_doc_num_input").val());
      }
      function change_otgruz_doc_date(){
        var input='<input type="date" id="otgruz_doc_date_input" onkeyup="if(event.keyCode===13) {set_otgruz_doc_date();}">';
        var otgruz_doc_date=$("#otgruz_doc_date").text();
        $("#otgruz_doc_date").html(input);
        //$("#plat_doc_date_input").val(plat_doc_date);
        $("#otgruz_doc_date_input").focus();
      }
      function set_otgruz_doc_date(){
        $("#otgruz_doc_date").html(getDate($("#otgruz_doc_date_input").val()));
      }
      var options = {
          day: 'numeric',
          month: 'numeric',
          year: 'numeric'
        }

      function getDate(str) {
        var date = new Date(str);
        return date.toLocaleString('ru', options)
      }
      function set_plat_doc_date(){
        $("#plat_doc_date").html(getDate($("#plat_doc_date_input").val()));
      }
    </script>
    <div class = "A4albom">
      <div class = "container">
        <table class="table6" style="font-size: 10px; " border="0"><tbody>
          <tr><td rowspan="3" colspan="2" style="width: 68px;" id="line"><p>Универсальный</p> <p>передаточный</p><p>документ</p></td>
            <td colspan="3"style="width: 440px; padding-left: 8px;" id="num_date" onclick="change_num_date();">Счет-фактура № <?php
            if(!empty($zakaz_data['number'])){
              echo $zakaz_data['number'];
            }
            else {
              if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) echo  $zakaz_id;
              else echo "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
            } 
            ?> от <?php 
            if($zakaz_data['document_date']!="0000-00-00"){
              echo date("d.m.Y",strtotime($zakaz_data['document_date']));
            }
            else {
              if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" ) 
                  echo date("d.m.Y",strtotime($zakaz_data['document_date']));
              else
                  echo date("d.m.Y",strtotime($zakaz_data['create_date']));
            }
            ?> (1)</td>
            <td colspan="4" rowspan="2" class="rightClass" style="font-size: 10px;"><p>Приложение № 1</p><p>к постановлению Правительства Российской Федерации</p><p>от 26 декабря 2011 г. № 1137 
              <br>(в ред. Постановления Правительства РФ от 02 апреля 2021 № 534)</p></td></tr>
          <tr><td colspan="3" style="width: 440px; padding-left: 8px;">Исправление № ____________ от ____________ (1а)</td></tr>
          <tr><td colspan="7" style="border-left: 2px solid #000000; padding-left: 8px; height: 10px;"></td></tr>
          <tr><td colspan="2" id="line"></td>
            <td style="padding-left: 8px; width: 180px"><b>Продавец</b></td>
            <td class="border" style="width: 410px;" colspan="2"><?php echo $mainc_data['name'];?></td>
            <td style="width: 20px; padding-left: 0px;">(2)</td>
            <td style="padding-left: 8px; width: 280px"><b>Покупатель</b></td>
            <td class="border" style="width: 410px;"><?php echo $client_data['name'];?></td>
            <td style="width: 20px; padding-left: 0px;">(6)</td>
          </tr>
          <tr><td rowspan="3">Статус:</td>
            <td rowspan="3" id="line"><div id="rectangle" onclick="change_status();" style="text-align:center;"></div></td>
            <td style="padding-left: 8px; width: 180px">Адрес</td>
            <td class="border" style="width: 410px;" colspan="2"><?php echo $mainc_data['address'];?></td>
            <td style="width: 20px; padding-left: 0px;">(2а)</td>
            <td style="padding-left: 8px; width: 280px">Адрес</td>
            <td class="border" style="width: 410px;"><?php echo $client_data['address'];?></td>
            <td style="width: 20px; padding-left: 0px;">(6а)</td>
          </tr>
          <tr>
            <td style="padding-left: 8px; width: 180px">ИНН/КПП продавца</td>
              <td class="border" style="width: 410px;" colspan="2"><?php echo $mainc_data['inn']." / ".$mainc_data['kpp'];?></td>
              <td style="width: 20px; padding-left: 0px;">(2б)</td>
            <td style="padding-left: 8px; width: 280px">ИНН/КПП покупателя</td>
              <td class="border" style="width: 410px;" ><?php echo $client_data['inn']." / ".$client_data['kpp'];?></td>
              <td style="width: 20px; padding-left: 0px;">(6б)</td>
          </tr> 
          <tr>
            <td style="padding-left: 8px; width: 180px">Грузоотправитель и его адрес</td>
            <td class="border" style="width: 410px;" colspan="2"></td>
            <td style="width: 20px; padding-left: 0px;">(3)</td>
            <td style="padding-left: 8px; width: 280px">Валюта: наименование, код</td>
            <td  class="border" style="width: 410px;"></td>
            <td style="width: 20px; padding-left: 0px;">(7)</td>
          </tr>
          <tr>
            <td rowspan="3" colspan="2" style="width: 68px; font-size: 10px;" id="line"><p>1 - счет-фактура и передаточный документ (акт)</p><p>2 - передаточный</p><p>документ (акт)</p></td>
            <td style="padding-left: 8px; width: 180px">Грузополучатель и его адрес</td>
            <td class="border" style="width: 410px;" id="gruz_getter" onclick="change_gruz_getter();" colspan="2"></td>
            <td style="width: 20px; padding-left: 0px;">(4)</td>
            <td style="padding-left: 8px; width: 280px">Идентификатор государственного контракта, договора (соглашения) (при наличии):</td>
            <td class="border" style="width: 410px;"></td>
            <td style="width: 20px; padding-left: 0px;">(8)</td>
          </tr>
          <tr>
            <td style="padding-left: 8px; width: 180px">К платежно-расчетному документу</td>
            <td style="width: 70px;" class="border">№ <span id="plat_doc_num"  style="width: 40px; height:10px;  display: inline-block; text-align:center;" onclick="change_plat_doc_num();"></span> </td>
            <td style="width: 340px;"  class="border">от <span id="plat_doc_date" style="width: 70px; height:10px;  display: inline-block; text-align:center;" onclick="change_plat_doc_date();"></span></td>
            <td style="width: 20px; padding-left: 0px;">(5)</td>
            <td style="padding-left: 8px; width: 280px"></td>
            <td style="width: 410px;" ></td>
            <td style="width: 20px; padding-left: 0px;"></td>
          </tr>
          <tr>
            <td style="padding-left: 8px; width: 180px">Документ об отгрузке</td>
            <td  colspan="2" class="border"><span id="otgruz_doc_num"  style=" height:10px;  display: inline-block;" onclick="change_otgruz_doc_num();">п/п 1-<?php echo (count($zakaz_details))?>№ <?php 
            if(!empty($zakaz_data['number'])){
              echo $zakaz_data['number'];
            }
            else {
              if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) echo  $zakaz_id;
              else echo "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
            }
            ?> от <?php 
            if($zakaz_data['document_date']!="0000-00-00"){
              echo date("d.m.Y",strtotime($zakaz_data['document_date']));
            }
            else {
              if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" ) 
                  echo date("d.m.Y",strtotime($zakaz_data['document_date']));
              else
                  echo date("d.m.Y",strtotime($zakaz_data['create_date']));
            }
            ?> г.</span></td>
            <td style="width: 20px; padding-left: 0px;">(5а)</td>
            <td style="padding-left: 8px; width: 280px"></td>
            <td style="width: 410px;"></td>
            <td style="width: 20px; padding-left: 0px;"></td>
          </tr>
          <tr><td colspan="2" style="width: 85px;" id="line"></td><td colspan="7" style="padding-left: 8px; height: 10px;"></td></tr>
        </tbody></table>
        <table class="table4" style="font-size: 10px;"><tbody>
          <tr>
            <td rowspan="2" style="width: 17px;">№ п/п</td>
            <td id="line2" rowspan="2">Код товара/ работ, услуг</td>
            <td rowspan="2">№ п/п</td>
            <td rowspan="2">Наименование товара (описание выполненных работ, оказанных услуг), имущественного права</td>
            <td rowspan="2">Код вида товара</td>
            <td colspan="2">Единица измерения</td>
            <td rowspan="2">Количе-ство (объем)</td>
            <td rowspan="2">Цена (тариф) за единицу измерения</td>
            <td rowspan="2">Стоимость товаров (работ, услуг), имущественных прав без налога - всего</td>
            <td rowspan="2">В том числе сумма акциза</td>
            <td rowspan="2">Нало- говая ставка</td>
            <td rowspan="2">Сумма налога, предъявля- емая покупателю</td>
            <td rowspan="2">Стоимость товаров (работ, услуг), имущественных прав с налогом - всего</td>
            <td colspan="2">Страна происхождения товара</td>
            <td rowspan="2">Регистрационный номер декларации на товары или регистрационный номер партии товара, подлежащего прослеживаемости</td>
            <td colspan="2">Количественная единица измерения товара, используемая в целях осуществления прослеживаемости</td>
            <td rowspan="2">Количество товара, подлежащего прослеживаемости, в количественной единице измерения товара, используемой в целях осуществления прослеживаемости</td>
          </tr>
          <tr>
            <td style="width: 17px;">код</td>
            <td>условное обозна-чение (нацио-нальное)</td>
            <td>Циф-ровой код</td>
            <td>Краткое наиме-нование</td>
            <td>код</td>
            <td>условное обозначение</td>
          </tr>
          <tr>
            <td>А</td>
            <td id="line2">Б</td>
            <td>1</td>
            <td>1а</td>
            <td>1б</td>
            <td>2</td>
            <td>2а</td>
            <td>3</td>
            <td>4</td>
            <td>5</td>
            <td>6</td>
            <td>7</td>
            <td>8</td>
            <td>9</td>
            <td>10</td>
            <td>10а</td>
            <td>11</td>
            <td>12</td>
            <td>12a</td>
            <td>13</td>
          </tr>
          <?php
        $i=1; $zakaz_sum=0;$zakaz_count_sum=0;$sum_without_nds=0;$sum_nds=0;
        foreach($zakaz_details as $zd_key=>$zd_val){?>
          <tr>
            <td><?php echo $i ?></td><td id="line2"></td><td><?php echo $i ?></td><td id="detail_name_<?php echo $i;?>" onclick="change_detail_name(<?php echo $i;?>);"><?php echo $zd_val['name'].' ('.$zd_val['brand'].' '.$zd_val['article'].')';?></td><td></td><td></td><td></td><td><?php echo $zd_val['count']?></td>
            <td><?php
            if((int)$mainc_taxtype['is_nds']==1){
                echo number_format(round(($zd_val['price'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
              }
              else
                echo number_format($zd_val['price'],2,"."," ");
            ?></td><td>
            <?php
            if((int)$mainc_taxtype['is_nds']==1)
                echo number_format(round(($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
            else
                echo number_format(round($zd_val['price']*$zd_val['count'],2),2,"."," ");
            ?>
            </td><td></td><td>
            <?php
            if((int)$mainc_taxtype['is_nds']==1)
                echo $mainc_taxtype['tax_rate']."%";
            else
                echo "Без НДС";
            ?>
            </td><td>
            <?php
                if((int)$mainc_taxtype['is_nds']==1)
                echo number_format(round(($zd_val['price']*$zd_val['count'])-($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
            ?>
            </td><td>
                <?php
                echo number_format($zd_val['price']*$zd_val['count'],2,"."," ");
                ?>
            </td><td></td><td></td><td></td><td></td><td></td><td></td>
          </tr>
          <?php
          $i++;
          $zakaz_sum+=$zd_val['price']*$zd_val['count'];
          $zakaz_count_sum+=$zd_val['count'];
          $sum_without_nds+=round(($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
          $sum_nds+=round(($zd_val['price']*$zd_val['count'])-($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
        }
        foreach($zakaz_jobs as $zj_key=>$zj_val){?>
          <tr>
            <td><?php echo $i ?></td><td id="line2"></td><td><?php echo $i ?></td><td id="detail_name_<?php echo $i;?>" onclick="change_detail_name(<?php echo $i;?>);"><?php echo $zj_val['name'].' ';?></td><td></td><td></td><td></td><td><?php echo $zj_val['count']?></td>
            <td><?php
            if((int)$mainc_taxtype['is_nds']==1){
                echo number_format(round(($zj_val['price'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
              }
              else
                echo number_format($zj_val['price'],2,"."," ");
            ?></td><td>
            <?php
            if((int)$mainc_taxtype['is_nds']==1)
                echo number_format(round(($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
            else
                echo number_format(round($zj_val['price']*$zj_val['count'],2),2,"."," ");
            ?>
            </td><td></td><td>
            <?php
            if((int)$mainc_taxtype['is_nds']==1)
                echo $mainc_taxtype['tax_rate']."%";
            else
                echo "Без НДС";
            ?>
            </td><td>
            <?php
                if((int)$mainc_taxtype['is_nds']==1)
                echo number_format(round(($zj_val['price']*$zj_val['count'])-($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
            ?>
            </td><td>
                <?php
                echo number_format($zj_val['price']*$zj_val['count'],2,"."," ");
                ?>
            </td><td></td><td></td><td></td><td></td><td></td><td></td>
          </tr>
          <?php
          $i++;
          $zakaz_sum+=$zj_val['price']*$zj_val['count'];
          $zakaz_count_sum+=$zj_val['count'];
          $sum_without_nds+=round(($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
          $sum_nds+=round(($zj_val['price']*$zj_val['count'])-($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
        }
        ?>
          
          <tr>
            <td></td><td id="line2"></td><td colspan="7" style="text-align: left; padding-left: 8px;"><b>Всего к оплате</b></td><td nowrap><?php echo number_format($sum_without_nds,2,"."," ");?></td>
            <td colspan="2">X</td><td nowrap><?php echo number_format($sum_nds,2,"."," ");?></td><td nowrap><?php echo number_format($zakaz_sum,2,"."," ");?></td><td></td><td></td><td></td>
          </tr>
        </tbody></table>
        <table class="table6" style="font-size: 10px;"><tbody>
          <tr>
            <td rowspan="5" style="width: 68px;" id="line"><p>Документ</p> <p>составлен на</p><p>______ листах</p></td>
            <td style="width: 350px; padding-left: 8px;" colspan="4">Руководитель организации</td>
            <td style="width: 350px; padding-left: 18px;" colspan="5">Главный бухгалтер</td>
          </tr>
          <tr>
            <td style="width: 155px; padding-left: 8px;">или иное уполномоченное лицо</td>
            <td class="border" style="width: 50px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 100px;"><?php if(!empty($mainc_data['ruk'])) echo $ruk; else echo ""; ?></td>
            <td colspan="2" style="width: 150px; padding-left: 18px;">или иное уполномоченное лицо</td>
            <td class="border" style="width: 70px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 120px;"><?php if(!empty($mainc_data['buh'])) echo $mainc_data['buh']; else echo $ruk; ?></td>
          </tr>
          <tr>
            <td style="width: 155px; padding-left: 8px;"></td>
            <td id="mini" style="width: 50px;">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="width: 100px;">(ф.и.о.)</td>
            <td colspan="2" style="width: 150px; padding-left: 8px;"></td>
            <td id="mini" style="width: 50px;">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="width: 100px;">(ф.и.о.)</td>
          </tr>
          <tr>
            <td style="width: 155px; padding-left: 8px;">Индивидуальный предприниматель</td>
            <td class="border" style="width: 50px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 100px;"></td>
            <td id="space"></td>
            <td class="border"></td>
            <td colspan="4"class="border" style="width: 300px; padding-left: 8px;"></td>
          </tr>
          <tr>
            <td style="width: 155px; padding-left: 8px;"></td>
            <td id="mini" style="width: 50px;">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="width: 100px;">(ф.и.о.)</td>
            <td id="space"></td>
            <td colspan="5" id="mini" style="width: 300px; padding-left: 8px;"> (реквизиты свидетельства о государственной регистрации индивидуального предпринимателя)</td>
          </tr>
          <tr><td style="width: 68px;" id="line"></td><td colspan="9" style="height: 2px; border-bottom: 2px solid #000000;"></td></tr>
        </tbody></table>
        <table class="table6" style="font-size: 10px; margin-top: 5px;"><tbody>
          <tr>
            <td colspan="2" style="width: 215px;">Основание передачи (сдачи)/получения (приемки)</td>
            <td class="border"></td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;">[8]</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td id="mini">(договор; доверенность и др.)</td>
            <td style="width: 20px; padding-left: 10px;"></td>
          </tr>
          <tr>
            <td style="width: 165px;">Данные о транспортировке и грузе</td>
            <td class="border" style="width: 50px;"></td>
            <td class="border"></td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;">[9]</td>
          </tr>
          <tr>
            <td></td>
            <td colspan="2" id="mini">(транспортная накладная, поручение экспедитору, экспедиторская/складская расписка и др./масса нетто/брутто груза, если не приведены ссылки на транспортные документы, содержащие эти сведения)</td>
            <td style="width: 20px; padding-left: 10px;"></td>
          </tr>
        </tbody></table>
        <table class="table7" style="font-size: 10px; margin-top: 5px;"><tbody>
          <tr>
            <td colspan="6" id="line" style="width: 450px;">Товар (груз) передал/услуги, результаты работ, права сдал</td>
            <td colspan="7" style="width: 450px; padding-left: 10px;">Товар (груз) получил/услуги, результаты работ, права принял</td>
          </tr>
          <tr>
            <td class="border" style="width: 120px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 80px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 150px;"></td>
            <td id="line" class="centerClass" style="width: 20px; padding-left: 10px; padding-right: 10px;">[10]</td>
            <td id="space"></td>
            <td class="border" style="width: 120px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 80px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 150px;"></td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;">[15]</td>
          </tr>
          <tr>
            <td id="mini" style="width: 120px;">(должность)</td>
            <td id="space"></td>
            <td id="mini" style="width: 80px;">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="width: 150px;">(ф.и.о.)</td>
            <td id="line" class="centerClass" style="width: 20px; padding-left: 10px; padding-right: 10px;"></td>
            <td id="space"></td>
            <td id="mini" style="width: 120px;">(должность)</td>
            <td id="space"></td>
            <td id="mini" style="width: 80px;">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="width: 150px;">(ф.и.о.)</td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;"></td>
          </tr>
          <tr>
            <td colspan="2" style="width: 120px;">Дата отгрузки, передачи (сдачи)</td>
            <td colspan="3">« ____ » _____________________________ 20 ____ г.</td>
            <td id="line" class="centerClass" style="width: 20px; padding-left: 10px; padding-right: 10px;">[11]</td>
            <td colspan="3" style="width: 100px; padding-left: 10px;">Дата получения (приемки)</td>
            <td colspan="3">« ____ » _____________________________ 20 ____ г.</td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;">[16]</td>
          </tr>
          <tr>
            <td id="line" colspan="6">Иные сведения об отгрузке,  передаче</td>
            <td colspan="7" style="padding-left: 10px;">Иные сведения о получении, приемке</td>
          </tr>
          <tr>
            <td class="border" colspan="5"></td>
            <td id="line" class="centerClass" style="width: 20px; padding-left: 10px; padding-right: 10px;">[12]</td>
            <td id="space"></td>
            <td class="border" colspan="5" style="padding-left: 10px;"></td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;">[17]</td>
          </tr>
          <tr>
            <td id="mini" colspan="5">(ссылки на неотъемлемые приложения, сопутствующие документы, иные документы и т.п.)</td>
            <td id="line" class="centerClass" style="width: 20px; padding-left: 10px; padding-right: 10px;"></td>
            <td id="space"></td>
            <td id="mini" colspan="5" style="padding-left: 10px;">(ссылки на неотъемлемые приложения, сопутствующие документы, иные документы и т.п.)</td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;"></td>
          </tr>
          <tr>
            <td id="line" colspan="6">Ответственный за правильность оформления факта хозяйственной жизни</td>
            <td colspan="7" style="padding-left: 10px;">Ответственный за правильность оформления факта хозяйственной жизни</td>
          </tr>
          <tr>
            <td class="border" style="width: 120px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 80px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 150px;"></td>
            <td id="line" class="centerClass" style="width: 20px; padding-left: 10px; padding-right: 10px;">[13]</td>
            <td id="space"></td>
            <td class="border" style="width: 120px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 80px;"></td>
            <td id="space"></td>
            <td class="border" style="width: 150px;"></td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;">[18]</td>
          </tr>
          <tr>
            <td id="mini" style="width: 120px;">(должность)</td>
            <td id="space"></td>
            <td id="mini" style="width: 80px;">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="width: 150px;">(ф.и.о.)</td>
            <td id="line" class="centerClass" style="width: 20px; padding-left: 10px; padding-right: 10px;"></td>
            <td id="space"></td>
            <td id="mini" style="width: 120px;">(должность)</td>
            <td id="space"></td>
            <td id="mini" style="width: 80px;">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="width: 150px;">(ф.и.о.)</td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;"></td>
          </tr>
          <tr>
            <td id="line" colspan="6">Наименование экономического субъекта - составителя документа (в т.ч. комиссионера/агента)</td>
            <td colspan="7" style="padding-left: 10px;">Наименование экономического субъекта - составителя документа</td>
          </tr>
          <tr>
            <td class="border" colspan="5"></td>
            <td id="line" class="centerClass" style="width: 20px; padding-left: 10px; padding-right: 10px;">[14]</td>
            <td id="space"></td>
            <td class="border" colspan="5" style="padding-left: 10px;"></td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;">[19]</td>
          </tr>
          <tr>
            <td id="mini" colspan="5">(может не заполняться при проставлении печати в М.П., может быть указан ИНН/КПП)</td>
            <td id="line" class="centerClass" style="width: 20px; padding-left: 10px; padding-right: 10px;"></td>
            <td id="space"></td>
            <td id="mini" colspan="5" style="padding-left: 10px;">(может не заполняться при проставлении печати в М.П., может быть указан ИНН/КПП)</td>
            <td class="centerClass" style="width: 20px; padding-left: 10px;"></td>
          </tr>
          <tr>
            <td id="line" colspan="6" style="padding-left: 100px;"><b>М.П.</b></td>
            <td id="space"></td>
            <td colspan="6" style="padding-left: 100px;"><b>М.П.</b></td>
          </tr>
        </tbody></table>
    </div></div>
  </body>
</html>
