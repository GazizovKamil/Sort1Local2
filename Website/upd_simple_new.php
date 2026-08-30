<?php

function print_header(){?>
<tbody class="table4">
          <tr>
            <td id="line2" rowspan="2" colspan="2">Код товара/ работ, услуг</td>
            <td rowspan="2">№ п/п</td>
            <td rowspan="2" colspan="2">Наименование товара (описание выполненных работ, оказанных услуг), имущественного права</td>
            <td rowspan="2">Код вида товара</td>
            <td colspan="2">Единица измерения</td>
            <td rowspan="2" style="width:40px;">Количе-ство (объем)</td>
            <td rowspan="2" style="width:70px;">Цена (тариф) за единицу измерения</td>
            <td rowspan="2" style="width:70px;">Стоимость товаров (работ, услуг), имущественных прав без налога - всего</td>
            <td rowspan="2">В том числе сумма акциза</td>
            <td rowspan="2">Нало- говая ставка</td>
            <td rowspan="2">Сумма налога, предъявля- емая покупателю</td>
            <td rowspan="2" style="width:100px;">Стоимость товаров (работ, услуг), имущественных прав с налогом - всего</td>
            <td colspan="2">Страна происхождения товара</td>
            <td rowspan="2" colspan="2" style="width:100px;">Регистрационный номер декларации на товары или регистрационный номер партии товара, подлежащего прослеживаемости</td>
          </tr>
          <tr>
            <td style="width: 17px;">код</td>
            <td style="width:50px;">условное обозна-чение (нацио-нальное)</td>
            <td>Циф-ровой код</td>
            <td>Краткое наиме-нование</td>
          </tr>
          <tr>
            <td id="line2" colspan="2">A</td>
            <td>1</td>
            <td colspan="2">1а</td>
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
            <td colspan="2">11</td>
          </tr></tbody>
<?php
}

 session_start();
 if(!isset($_SESSION['user_id'])) {
   die("Необходимо авторизоваться");
 }
 include "include/db_safe.inc.php";
 $db=new SafeMySQL();
 if(isset($_GET['zakaz_id'])){
   $zakaz_id=$_GET['zakaz_id'];
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100)",$zakaz_id);
   $zakaz_jobs=$db->getAll("select zj.*,sj.name from zakaz_jobs zj 
    left join service_jobs sj on (sj.id=zj.job_id)
    where zj.zakaz_id=?i and (zj.status<100)",$zakaz_id);
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
	 $zakaz_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$_GET['document_id']);
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
 if($client_data['main_org_id']>0) $client_main_org_data=$db->getRow("select * from company where id=?i",$client_data['main_org_id']);
 $mainc_data=$db->getRow("select * from company where id=?i",$zakaz_data['main_company_id']);
 $poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['main_company_id'],$zakaz_data['main_company_id']);
 $pokupatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['company_id'],$zakaz_data['company_id']);
 $mainc_taxtype=$db->getRow("select * from tax_type where id=?i",$mainc_data['tax_type']);
 $sklad_data=$db->getRow("select * from sklad where id=(select delivery_type_id from zakaz where id=?i)",$zakaz_id);
 if(isset($_GET['zakaz_id']) && $mainc_taxtype['is_nds']==1 ){
   die("При работе с НДС, распечатка документов возможна только из вкладки документы");
 }
 $ruk_arr=explode(" ",$mainc_data['ruk']);
 //echo print_r($ruk_arr,true)."<br>";
 $ruk_name=mb_substr($ruk_arr[1],0,1);
 //echo print_r($ruk_name,true)."<br>";
 if(isset($ruk_arr[2])) $ruk_otch=mb_substr($ruk_arr[2],0,1);
 $ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";
 //echo "main_company_id=".$zakaz_data['main_company_id']."<br>";
 //echo "poluchatel_rs_data: ".print_r($poluchatel_rs_data,true)."<br>";
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>УПД от <?php
    if(isset($zakaz_data['document_date']) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!=""){
      echo date("d.m.Y",strtotime($zakaz_data['document_date']));
    }
    else {
      if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!="" ) 
          echo date("d.m.Y",strtotime($zakaz_data['document_date']));
      else
          echo date("d.m.Y",strtotime($zakaz_data['create_date']));
    }?>
    №
    <?php
    if(!empty($zakaz_data['number'])){
      echo $zakaz_data['number'];
    }
    else {
      if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) echo  $zakaz_id;
      else echo "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
    } 
    ?> 
    </title>
    <style>
      body { /*background-color: #DCC7C7; */ } /* общий фон */
      @media print {
        table { display: block; page-break-after:auto }
        tr    { page-break-inside:avoid; page-break-after:auto }
        td    { page-break-inside:avoid; page-break-after:auto }
        thead { display:table-header-group }
        tfoot { display:table-footer-group }
        .page-break  { display: block; page-break-before: always; }
        }
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
        /*border: 1px solid #000000;*/
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
        /*width: 73px;*/
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
      var detail_names=[];
      function change_detail_name(i){
        if(typeof($("#detail_name_input_"+i)).val()!="undefined") return;
        if($("#detail_name_"+i).text()!='') detail_names[i]=$("#detail_name_"+i).text();
        else {
          if($("#detail_name_input_"+i).val()!=''){
            detail_names[i]=$("#detail_name_input_"+i).val();
          }
        }
        var input='<input type="text" id="detail_name_input_'+i+'" onchange="set_detail_name('+i+');" style="width:390px; height: 20px;" onkeyup="if(event.keyCode===13) {set_detail_name('+i+');}">';        
        $("#detail_name_"+i).html(input);
        $("#detail_name_input_"+i).val(detail_names[i]);
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
        <table class="table6" style="font-size: 10px;"><tbody>
          <tr><td rowspan="3" colspan="2" style="width: 68px;" id="line"><p>Универсальный</p> <p>передаточный</p><p>документ</p></td>
            <td colspan="9"style="padding-left: 8px;" id="num_date" onclick="change_num_date();">Счет-фактура № <?php
            if(!empty($zakaz_data['number'])){
              echo $zakaz_data['number'];
            }
            else {
              if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) echo  $zakaz_id;
              else echo "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
            } 
            ?> от <?php 
            if(isset($zakaz_data['document_date']) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!=""){
              echo date("d.m.Y",strtotime($zakaz_data['document_date']));
            }
            else {
              if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!="" ) 
                  echo date("d.m.Y",strtotime($zakaz_data['document_date']));
              else
                  echo date("d.m.Y",strtotime($zakaz_data['create_date']));
            }
            ?> [1]</td>
            <td colspan="8" rowspan="2" class="rightClass" style="font-size: 10px;"><p>Приложение № 1</p><p>к постановлению Правительства РФ от 26.12.2011 г. № 1137 
              <br>(в ред. постановления Правительства РФ от 16.08.2024 № 1096)</p></td></tr>
          <tr><td colspan="9" style="width: 440px; padding-left: 8px;">Исправление № ____________ от ____________ (1а)</td></tr>
          <tr></tr>
          <tr><td colspan="2" id="line"></td>
            <td style="padding-left: 8px; width: 180px" colspan="2"><b>Продавец</b></td>
            <td class="border" style="width: 410px;" colspan="6"><?php echo $mainc_data['name'];?></td>
            <td style="width: 20px; padding-left: 0px;">[2]</td>
            <td style="padding-left: 8px; width: 280px" colspan="3"><b>Покупатель</b></td>
            <td class="border" style="width: 410px;" colspan="4"><?php 
            
            if(isset($client_main_org_data)) echo $client_main_org_data['name'];
            else echo $client_data['name'];
            
            ?></td>
            <td style="width: 20px; padding-left: 0px;">[6]</td>
          </tr>
          <tr><td rowspan="3">Статус:</td>
            <td rowspan="3" id="line"><div id="rectangle" onclick="change_status();" style="text-align:center;">
            <?php
            if($mainc_taxtype['is_nds']==1) echo "1";
            else echo "1";
            ?>
            </div></td>
            <td style="padding-left: 8px; width: 180px" colspan="2">Адрес</td>
            <td class="border" style="width: 410px;" colspan="6"><?php echo $mainc_data['address'];?></td>
            <td style="width: 20px; padding-left: 0px;">[2а]</td>
            <td style="padding-left: 8px; width: 280px" colspan="3">Адрес</td>
            <td class="border" style="width: 410px;" colspan="4"><?php 
            if(isset($client_main_org_data)) echo $client_main_org_data['address'];
            else echo $client_data['address'];?></td>
            <td style="width: 20px; padding-left: 0px;">[6а]</td>
          </tr>
          <tr>
            <td style="padding-left: 8px; width: 180px" colspan="2">ИНН/КПП продавца</td>
              <td class="border" style="width: 410px;" colspan="6"><?php echo $mainc_data['inn']." / ".$mainc_data['kpp'];?></td>
              <td style="width: 20px; padding-left: 0px;">[2б]</td>
            <td style="padding-left: 8px; width: 280px" colspan="3">ИНН/КПП покупателя</td>
              <td class="border" style="width: 410px;" colspan="4"><?php 
              if(isset($client_main_org_data)  && $client_data['buyer_in_upd']=="0") echo $client_main_org_data['inn']." / ".$client_main_org_data['kpp'];
              else echo $client_data['inn']." / ".$client_data['kpp'];?>
              <td style="width: 20px; padding-left: 0px;">[6б]</td>
          </tr> 
          <tr>
            <td style="padding-left: 8px; width: 180px" colspan="2">Грузоотправитель и его адрес</td>
            <td class="border" style="width: 410px;" colspan="6"> <?php if(isset($sklad_data['address'])){echo $mainc_data['name']." ".$sklad_data['address']; } else echo "он же";?></td>
            <td style="width: 20px; padding-left: 0px;">[3]</td>
            <td style="padding-left: 8px; width: 280px" colspan="3">Валюта: наименование, код</td>
            <td  class="border" style="width: 410px;" colspan="4">Российский рубль, 643 </td>
            <td style="width: 20px; padding-left: 0px;">[7]</td>
          </tr>
          <tr>
            <td rowspan="3" colspan="2" style="width: 68px; font-size: 10px;" id="line">1 - счет-фактура и передаточный документ (акт)<br>2 - передаточный<br>документ (акт)</td>
            <td style="padding-left: 8px; width: 180px" colspan="2">Грузополучатель и его адрес</td>
            <td class="border" style="width: 410px;" id="gruz_getter" onclick="change_gruz_getter();" colspan="6"><?php echo $client_data['name'];?>, <?php echo $client_data['address'];?></td>
            <td style="width: 20px; padding-left: 0px;">[4]</td>
            <td style="padding-left: 8px; width: 280px" colspan="3">Идентификатор государственного контракта, договора (соглашения) (при наличии):</td>
            <td class="border" style="width: 410px;" colspan="4"></td>
            <td style="width: 20px; padding-left: 0px;">[8]</td>
          </tr>
          <tr>
            <td style="padding-left: 8px; width: 180px" colspan="2">К платежно-расчетному документу</td>
            <td style="width: 70px;" class="border" colspan="3">№ <span id="plat_doc_num"  style="width: 40px; height:10px;  display: inline-block; text-align:center;" onclick="change_plat_doc_num();"></span> </td>
            <td style="width: 140px;"  class="border" colspan="3">от <span id="plat_doc_date" style="width: 70px; height:10px;  display: inline-block; text-align:center;" onclick="change_plat_doc_date();"></span></td>
            <td style="width: 20px; padding-left: 0px;">[5]</td>
            <td style="padding-left: 8px; width: 280px" colspan="3"></td>
            <td style="width: 410px;" colspan="4"></td>
            <td style="width: 20px; padding-left: 0px;"></td>
          </tr>
          <tr>
            <td style="padding-left: 8px; width: 180px" colspan="2">Документ об отгрузке</td>
            <td  colspan="6" class="border"><span id="otgruz_doc_num"  style=" height:10px;  display: inline-block;" onclick="change_otgruz_doc_num();">п/п 1-<?php echo (count($zakaz_details))?>№ <?php 
            if(!empty($zakaz_data['number'])){
              echo $zakaz_data['number'];
            }
            else {
              if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) echo  $zakaz_id;
              else echo "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
            }
            ?> от <?php 
            if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00"){
              echo date("d.m.Y",strtotime($zakaz_data['document_date']));
            }
            else {
              if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" ) 
                  echo date("d.m.Y",strtotime($zakaz_data['document_date']));
              else
                  echo date("d.m.Y",strtotime($zakaz_data['create_date']));
            }
            ?> г.</span></td>
            <td style="width: 20px; padding-left: 0px;">[5а]</td>
            <td style="padding-left: 8px; width: 280px" colspan="3"></td>
            <td style="width: 410px;" colspan="4"></td>
            <td style="width: 20px; padding-left: 0px;"></td>
          </tr>
          <tr><td colspan="2" style="width: 85px;" id="line"></td><td colspan="17" style="padding-left: 8px; height: 10px;"></td></tr>
        </tbody>
        <?php print_header();?>
        <tbody class="table4">
          <?php
        $i=1; $zakaz_sum=0;$zakaz_count_sum=0;$sum_without_nds=0;$sum_nds=0;$pages=1;
        foreach($zakaz_details as $zd_key=>$zd_val){?>
          <tr>
            <td id="line2" colspan="2"><?php echo $zd_val['article'];?></td><td><?php echo $i ?></td><td id="detail_name_<?php echo $i;?>" onclick="change_detail_name(<?php echo $i;?>);" colspan="2"><?php echo $zd_val['name'].' ('.$zd_val['brand'].' '.$zd_val['article'].')';?></td><td></td><td>796</td><td>шт.</td><td><?php echo $zd_val['count']?></td>
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
            </td><td></td><td></td><td colspan="2"></td>
          </tr>
          <?php
          if($i%10==0 && $i<count($zakaz_details)) {
            $pages++;
            echo '</tbody><tbody class="page-break"><tr class="page-break"><td colspan="19"></td></tr>';
            echo '<tbody><tr><td colspan="19"><p>Универсальный передаточный документ № ';
            if(!empty($zakaz_data['number'])){
              echo $zakaz_data['number'];
            }
            else {
              if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) echo  $zakaz_id;
              else echo "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
            } 
            echo " от "; 
            if($zakaz_data['document_date']!="0000-00-00"){
              echo date("d.m.Y",strtotime($zakaz_data['document_date']));
            }
            else {
              if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" ) 
                  echo date("d.m.Y",strtotime($zakaz_data['document_date']));
              else
                  echo date("d.m.Y",strtotime($zakaz_data['create_date']));
            }
            echo 'г.<span style="float:right;">Лист '.$pages.'</span></p>';
            //echo '<table class="table6" style="font-size: 10px;">';
            echo '</td></tr></tbody>';
            print_header();
            echo '<tbody class="table4">';
          }
          $i++;
          $zakaz_sum+=$zd_val['price']*$zd_val['count'];
          $zakaz_count_sum+=$zd_val['count'];
          $sum_without_nds+=round(($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
          $sum_nds+=round(($zd_val['price']*$zd_val['count'])-($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
          
        }
        foreach($zakaz_jobs as $zj_key=>$zj_val){?>
          <tr>
            <td id="line2" colspan="2"></td><td><?php echo $i ?></td>
            <td id="detail_name_<?php echo $i;?>" onclick="change_detail_name(<?php echo $i;?>);"><?php echo $zj_val['name'].' ';?></td>
            <td></td>
            <td></td>
            <td></td>
            <td><?php echo $zj_val['count']?></td>
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
            </td><td></td><td></td><td colspan="2"></td>
          </tr>
          <?php
          if($i%10==0){
            $pages++;
            echo '</tbody><tbody class="page-break"><tr class="page-break"><td colspan="19"></td></tr>';
            echo '<tr><td colspan="19"><p>Универсальный передаточный документ № ';
            if(!empty($zakaz_data['number'])){
              echo $zakaz_data['number'];
            }
            else {
              if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) echo  $zakaz_id;
              else echo "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
            } 
            echo " от "; 
            if($zakaz_data['document_date']!="0000-00-00"){
              echo date("d.m.Y",strtotime($zakaz_data['document_date']));
            }
            else {
              if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" ) 
                  echo date("d.m.Y",strtotime($zakaz_data['document_date']));
              else
                  echo date("d.m.Y",strtotime($zakaz_data['create_date']));
            }
            echo 'г.<span style="float:right;">Лист '.$pages.'</span></p>';
            echo '</td></tr>';
            //echo '<table class="table6" style="font-size: 10px;">';
            print_header();
            echo '<tbody class="table4">';
          }
          $i++;
          $zakaz_sum+=$zj_val['price']*$zj_val['count'];
          $zakaz_count_sum+=$zj_val['count'];
          $sum_without_nds+=round(($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
          $sum_nds+=round(($zj_val['price']*$zj_val['count'])-($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
          //echo $sum_nds."<br>";
        }
        //$sum_without_nds=round($zakaz_sum/(1+$mainc_taxtype['tax_rate']/100),2);
        //$sum_nds=round(($zakaz_sum-$sum_without_nds),2);
        ?>
          
          <tr>
            <td id="line2" colspan="2"></td><td colspan="8" style="text-align: left; padding-left: 8px;"><b>Всего к оплате</b></td><td nowrap>
              <?php  if((int)$mainc_taxtype['is_nds']==1){ echo number_format($sum_without_nds,2,"."," "); } else {echo number_format($zakaz_sum,2,"."," ");}?></td>
            <td colspan="2">X</td><td nowrap><?php if((int)$mainc_taxtype['is_nds']==1){echo number_format($sum_nds,2,"."," ");} else echo "X";?></td>
            <td nowrap><?php echo number_format($zakaz_sum,2,"."," ");?></td>
            <td></td>
            <td></td>
            <td colspan="2"></td>
          </tr>
        </tbody>
        <tbody>
          <tr>
            <td rowspan="5" style="padding:0px;border: 0px; border-right: 2px solid black;" id="line" colspan="2">Документ<br>составлен на<br><?php if($pages>1) echo $pages." листах"; else echo "1 листе";?></td>
            <td style=" padding-left: 8px; text-align: left; border: 0px;" colspan="3">Руководитель организации</td>
            <td style=" padding-left: 8px; border: 0px;" colspan="6"></td>
            <td style=" padding-left: 18px; text-align: left; border: 0px;" colspan="8">Главный бухгалтер</td>
          </tr>
          <tr>
            <td style="padding-left: 8px; text-align: left; border: 0px;" colspan="3">или иное уполномоченное лицо</td>
            <td class="border" style="border: 0px;border-bottom: 1px solid black;" colspan="3"></td>
            <td id="space" style="border: 0px;"></td>
            <td class="border" style=" border: 0px; border-bottom: 1px solid black;" colspan="2"><?php if(!empty($mainc_data['ruk']) && $mainc_data['type']==2) echo $ruk; else echo ""; ?></td>
            <td colspan="3" style=" padding-left: 18px; text-align: left; border: 0px;">или иное уполномоченное лицо</td>
            <td class="border" style="border: 0px;border-bottom: 1px solid #000000;" colspan="2"></td>
            <td id="space" style="border: 0px;"></td>
            <td class="border" style=" border: 0px;border-bottom: 1px solid #000000;" colspan="2"><?php if($mainc_data['type']==2){ if(!empty($mainc_data['buh'])) echo $mainc_data['buh']; else echo $ruk;} ?></td>
          </tr>
          <tr>
            <td style="padding-left: 8px; border: 0px;" colspan="3"></td>
            <td id="mini" style=" border: 0px;" colspan="3">(подпись)</td>
            <td id="space" style=" border: 0px;"></td>
            <td id="mini" style=" border: 0px;" colspan="2">(ф.и.о.)</td>
            <td colspan="3" style=" padding-left: 8px; border: 0px;"></td>
            <td id="mini" style=" border: 0px;" colspan="2">(подпись)</td>
            <td id="mini" style="border: 0px;" colspan="3">(ф.и.о.)</td>
          </tr>
          <tr>
            <td style="padding-left: 8px; text-align:left; border: 0px;" colspan="3">Индивидуальный предприниматель</td>
            <td class="border" style="border: 0px;border-bottom: 1px solid black;" colspan="3"></td>
            <td id="space" style=" border: 0px;"></td>
            <td class="border" style="border: 0px;border-bottom: 1px solid black;" colspan="2"><?php if(!empty($mainc_data['ruk']) && $mainc_data['type']==1) echo $ruk; else echo ""; ?></td>
            <td id="space" style=" border: 0px;"></td>
            <td colspan="7" class="border" style=" padding-left: 8px; border: 0px;border-bottom: 1px solid black;">
            <?php if(!empty($mainc_data['ipreg_num']) && $mainc_data['type']==1) echo $mainc_data['ipreg_num']; else echo ""; ?>
            <?php if(!empty($mainc_data['ipreg_date']) && $mainc_data['type']==1) echo " от ".$mainc_data['ipreg_date']; else echo ""; ?>
            </td>
          </tr>
          <tr>
            <td style="padding-left: 8px; border: 0px;" colspan="3"></td>
            <td id="mini" style="border: 0px;" colspan="3">(подпись)</td>
            <td id="space" style=" border: 0px;"></td>
            <td id="mini" style=" border: 0px;" colspan="2">(ф.и.о.)</td>
            <td id="space" style=" border: 0px;"></td>
            <td colspan="7" id="mini" style="padding-left: 8px; border: 0px;"> (реквизиты свидетельства о государственной регистрации индивидуального предпринимателя)</td>
          </tr>
          <tr><td style="border: 0px; border-right: 2px solid black;" id="line" colspan="2"></td><td colspan="17" style="height: 2px;  border: 0px;border-bottom: 2px solid #000000;"></td></tr>
        </tbody>
        <tbody>
          <tr>
            <td colspan="4" style="border: 0px;">Основание передачи (сдачи)/получения (приемки)</td>
            <td class="border" colspan="14" style="text-align:right;border: 0px;border-bottom: 1px solid black;"></td>
            <td style="border: 0px;">[9]</td>
          </tr>
          <tr>
            <td colspan="4" style="border: 0px;"></td>
            <td colspan="14" style="border: 0px; text-align: center;">(договор; доверенность и др.)</td>
            <td style="border: 0px;"></td>
          </tr>
          <tr>
            <td style="border: 0px;" colspan="4">Данные о транспортировке и грузе</td>
            <td class="border" style="text-align:right;border: 0px;border-bottom: 1px solid black;" colspan="14"></td>
            <td style="border: 0px;">[10]</td>
          </tr>
          <tr>
            <td colspan="5" style="border: 0px;"></td>
            <td colspan="13" id="mini" style="border: 0px;">(транспортная накладная, поручение экспедитору, экспедиторская/складская расписка и др./масса нетто/брутто груза, если не приведены ссылки на транспортные документы, содержащие эти сведения)</td>
            <td style="border: 0px;"></td>
          </tr>
        </tbody>
        <tbody>
          <tr>
            <td colspan="10" id="line" style="border: 0px;border-right: 2px solid black;">Товар (груз) передал/услуги, результаты работ, права сдал</td>
            <td colspan="9" style=" padding-left: 10px;border: 0px;">Товар (груз) получил/услуги, результаты работ, права принял</td>
          </tr>
          <tr>
            <td class="border" style="" colspan="2"></td>
            <td id="space"></td>
            <td class="border" style=""></td>
            <td id="space"></td>
            <td class="border" style="" colspan="4"><?php if(!empty($mainc_data['ruk']) && $mainc_data['type']==1) echo $ruk; else echo ""; ?></td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;">[11]</td>
            <td id="space"></td>
            <td class="border" style=""></td>
            <td id="space"></td>
            <td class="border" style=""></td>
            <td id="space"></td>
            <td class="border" style="" colspan="3"></td>
            <td class="centerClass" style=" padding-left: 10px;">[16]</td>
          </tr>
          <tr>
            <td id="mini" style="" colspan="2">(должность)</td>
            <td id="space"></td>
            <td id="mini" style="">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="" colspan="4">(ф.и.о.)</td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;"></td>
            <td id="space"></td>
            <td id="mini" style="">(должность)</td>
            <td id="space"></td>
            <td id="mini" style="">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style=""  colspan="3">(ф.и.о.)</td>
            <td class="centerClass" style=" padding-left: 10px;"></td>
          </tr>
          <tr>
            <td colspan="3" style="">Дата отгрузки, передачи (сдачи)</td>
            <td colspan="6">« ____ » _____________________________ 20 ____ г.</td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;">[12]</td>
            <td colspan="3" style=" padding-left: 10px;">Дата получения (приемки)</td>
            <td colspan="5">« ____ » _____________________________ 20 ____ г.</td>
            <td class="centerClass" style=" padding-left: 10px;">[17]</td>
          </tr>
          <tr>
            <td id="line" colspan="10">Иные сведения об отгрузке,  передаче</td>
            <td colspan="9" style="padding-left: 10px;">Иные сведения о получении, приемке</td>
          </tr>
          <tr>
            <td class="border" colspan="9"></td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;">[13]</td>
            <td id="space"></td>
            <td class="border" colspan="7" style="padding-left: 10px;"></td>
            <td class="centerClass" style=" padding-left: 10px;">[18]</td>
          </tr>
          <tr>
            <td id="mini" colspan="9">(ссылки на неотъемлемые приложения, сопутствующие документы, иные документы и т.п.)</td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;"></td>
            <td id="space"></td>
            <td id="mini" colspan="7" style="padding-left: 10px;">(ссылки на неотъемлемые приложения, сопутствующие документы, иные документы и т.п.)</td>
            <td class="centerClass" style="padding-left: 10px;"></td>
          </tr>
          <tr>
            <td id="line" colspan="10">Ответственный за правильность оформления факта хозяйственной жизни</td>
            <td colspan="9" style="padding-left: 10px;">Ответственный за правильность оформления факта хозяйственной жизни</td>
          </tr>
          <tr>
            <td class="border" style="" colspan="2"></td>
            <td id="space"></td>
            <td class="border" style=""></td>
            <td id="space"></td>
            <td class="border" style="" colspan="4"><?php if(!empty($mainc_data['ruk']) && $mainc_data['type']==1) echo $ruk; else echo ""; ?></td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;">[14]</td>
            <td id="space"></td>
            <td class="border" style="" ></td>
            <td id="space"></td>
            <td class="border" style=""></td>
            <td id="space"></td>
            <td class="border" style="" colspan="3"></td>
            <td class="centerClass" style=" padding-left: 10px;">[19]</td>
          </tr>
          <tr>
            <td id="mini" style="" colspan="2">(должность)</td>
            <td id="space"></td>
            <td id="mini" style="">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="" colspan="4">(ф.и.о.)</td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;"></td>
            <td id="space"></td>
            <td id="mini" style="">(должность)</td>
            <td id="space"></td>
            <td id="mini" style="">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="" colspan="3">(ф.и.о.)</td>
            <td class="centerClass" style=" padding-left: 10px;"></td>
          </tr>
          <tr>
            <td id="line" colspan="10">Наименование экономического субъекта - составителя документа (в т.ч. комиссионера/агента)</td>
            <td colspan="9" style="padding-left: 10px;">Наименование экономического субъекта - составителя документа</td>
          </tr>
          <tr>
            <td class="border" colspan="9"></td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;">[15]</td>
            <td id="space"></td>
            <td class="border" colspan="7" style="padding-left: 10px;"></td>
            <td class="centerClass" style=" padding-left: 10px;">[20]</td>
          </tr>
          <tr>
            <td id="mini" colspan="9">(может не заполняться при проставлении печати в М.П., может быть указан ИНН/КПП)</td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;"></td>
            <td id="space"></td>
            <td id="mini" colspan="7" style="padding-left: 10px;">(может не заполняться при проставлении печати в М.П., может быть указан ИНН/КПП)</td>
            <td class="centerClass" style="padding-left: 10px;"></td>
          </tr>
          <tr>
            <td id="line" colspan="10" style="padding-left: 100px;"><b>М.П.</b></td>
            <td id="space"></td>
            <td colspan="8" style="padding-left: 100px;"><b>М.П.</b></td>
          </tr>
        </tbody></table>
    </div></div>
  </body>
</html>
