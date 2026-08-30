<?php

function print_header(){?>
<tbody class="table4">
          <tr>
            <td rowspan="2" colspan="1">№ п/п</td>
            <td id="line2" rowspan="2" colspan="1">Код товара/ работ, услуг</td>
            <td rowspan="2">№ п/п</td>
            <td rowspan="2" colspan="2">Наименование товара (описание выполненных работ, оказанных услуг), имущественного права</td>
            <td rowspan="2">Показатели в связи с изменением стоимости  отгруженных товаров (выполненных работ, оказанных услуг), переданных имущественных прав</td>
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
            <td colspan="1">A</td>
            <td id="line2" colspan="1">Б</td>
            <td>1</td>
            <td colspan="2">1а</td>
            <td>1б</td>
            <td>1в</td>
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
          <tr><td rowspan="3" colspan="2" style="width: 68px;" id="line"><p>Универсальный</p> <p>корректировочный</p><p>документ</p></td>
          <td colspan="8"></td>
            <td colspan="8" rowspan="1" class="rightClass" style="font-size: 10px;"><p>Приложение № 2 к постановлению Правительства Российской Федерации от 26 декабря 2011 г. № 1137</p></td></tr>
          <tr><td colspan="16" style="padding-left: 8px;" id="num_date" onclick="change_num_date();">Корректировочный счет-фактура № <span style="text-decoration: underline;">								
         <?php
            if(!empty($zakaz_data['number'])){
              echo $zakaz_data['number'];
            }
            else {
              if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) echo  $zakaz_id;
              else echo "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
            } 
            ?></span> от <span style="text-decoration: underline;"><?php 
            if(isset($zakaz_data['document_date']) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!=""){
              echo date("d.m.Y",strtotime($zakaz_data['document_date']));
            }
            else {
              if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!="" ) 
                  echo date("d.m.Y",strtotime($zakaz_data['document_date']));
              else
                  echo date("d.m.Y",strtotime($zakaz_data['create_date']));
            }

            $source_document=$db->getRow("SELECT * FROM document WHERE zakaz_id=?i AND type_id=2 and deleted=0",$zakaz_data['zakaz_id']);
            ?></span> [1], исправление корректировочного счета-фактуры № ____________ от ____________ [1а]</td></tr>
          <tr><td colspan="16" style="width: 440px; padding-left: 8px;">к счету-фактуре (счетам-фактурам) № <?php echo "0000-".str_pad($source_document['id'],6,"0",STR_PAD_LEFT);?> от <?php echo preg_replace("/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/","$3.$2.$1",($source_document['document_date']!="0000-00-00"?$source_document['document_date']:$source_document['create_date']));?> , с учетом исправления № _____________ от ____________[1б]</td></tr>
          <tr></tr>
          <tr><td colspan="2" id="line"></td>
            <td style="padding-left: 8px; width: 180px" colspan="7"><b>Продавец</b></td>
            <td class="border" style="width: 410px;" colspan="9"><?php echo $mainc_data['name'];?></td>
            <td style="width: 20px; padding-left: 0px;">[2]</td>
            
          </tr>
          <tr><td rowspan="2">Статус:</td>
            <td rowspan="2" id="line"><div id="rectangle" onclick="change_status();" style="text-align:center;">
            <?php
            if($mainc_taxtype['is_nds']==1) echo "1";
            else echo "1";
            ?>
            </div></td>
            <td style="padding-left: 8px; width: 180px" colspan="7">Адрес</td>
            <td class="border" style="width: 410px;" colspan="9"><?php echo $mainc_data['address'];?></td>
            <td style="width: 20px; padding-left: 0px;">[2а]</td>
          </tr>
          <tr>
            <td style="padding-left: 8px; width: 180px" colspan="7">ИНН/КПП продавца</td>
              <td class="border" style="width: 410px;" colspan="9"><?php echo $mainc_data['inn']." / ".$mainc_data['kpp'];?></td>
              <td style="width: 20px; padding-left: 0px;">[2б]</td>
          </tr> 
          
          <tr>
            <td rowspan="6" colspan="2" style="width: 68px; font-size: 10px;" id="line">1–корректировочный счет-фактура и соглашение (уведомление)<br>2 – соглашение (уведомление)</td>
            <td style="padding-left: 8px; width: 280px" colspan="7"><b>Покупатель</b></td>
            <td class="border" style="width: 410px;" colspan="9"><?php 
            
            if(isset($client_main_org_data)) echo $client_main_org_data['name'];
            else echo $client_data['name'];
            
            ?></td>
            <td style="width: 20px; padding-left: 0px;">[3]</td>
          </tr>
          <tr>
          <td style="padding-left: 8px; width: 280px" colspan="7">Адрес</td>
            <td class="border" style="width: 410px;" colspan="9"><?php 
            if(isset($client_main_org_data)) echo $client_main_org_data['address'];
            else echo $client_data['address'];?></td>
            <td style="width: 20px; padding-left: 0px;">[3а]</td>

        </tr>
            <tr>
            <td style="padding-left: 8px; width: 280px" colspan="7">ИНН/КПП покупателя</td>
              <td class="border" style="width: 410px;" colspan="9"><?php 
              if(isset($client_main_org_data)  && $client_data['buyer_in_upd']=="0") echo $client_main_org_data['inn']." / ".$client_main_org_data['kpp'];
              else echo $client_data['inn']." / ".$client_data['kpp'];?>
              <td style="width: 20px; padding-left: 0px;">[3б]</td>
            </tr>

          <tr>
            <td style="padding-left: 8px; width: 280px" colspan="7">Валюта: наименование, код</td>
            <td  class="border" style="width: 410px;" colspan="9">Российский рубль, 643 </td>
            <td style="width: 20px; padding-left: 0px;">[4]</td>
          </tr>
          <tr>
            <td style="padding-left: 8px; width: 280px" colspan="7">Идентификатор государственного контракта, договора (соглашения) (при наличии):</td>
            <td class="border" style="width: 410px;" colspan="9"></td>
            <td style="width: 20px; padding-left: 0px;">[5]</td>
        </tr>
        <tr><td colspan="17"> &nbsp</td></tr>
        </tbody>
        <?php print_header();?>
        <tbody class="table4">
          <?php
        $i=1; $zakaz_sum=0;$zakaz_count_sum=0;$sum_without_nds=0;$sum_nds=0;$pages=1;
        $sum_nonds_v=0;$nds_sum_v=0;$sum_nds_v=0;
        $sum_nonds_g=0;$nds_sum_g=0;$sum_nds_g=0;
        foreach($zakaz_details as $zd_key=>$zd_val){
            //echo print_r($zd_val,true)."<br>";
            ?>
          <tr>
            <td rowspan="5"><?php echo $i ?></td>
            <td id="line2" colspan="1"  rowspan="5"><?php echo $zd_val['article'];?></td>
            <td rowspan="5">--</td>
            <td id="detail_name_<?php echo $i;?>" onclick="change_detail_name(<?php echo $i;?>);" colspan="2"  rowspan="5"><?php echo $zd_val['name'].' ('.$zd_val['brand'].' '.$zd_val['article'].')';?></td>
            
          </tr>
          <tr>
            <?php
            if((int)$zd_val['document_detail_id']>0){
                $dd_data=$db->getRow("SELECT * FROM document_details WHERE document_id=?i",$zd_val['document_detail_id']);
            }
            elseif((int)$zd_val['zakaz_detail_id']>0){
                $dd_data=$db->getRow("SELECT * FROM document_details WHERE document_id IN (SELECT id FROM document WHERE zakaz_id=?i AND type_id=2) AND detail_id=?i",$zakaz_data['zakaz_id'],$zd_val['detail_id']);
            }
            $A_data=array();
            $A_data['count']=$dd_data['count'];
            if((int)$mainc_taxtype['is_nds']==1){
                $A_data['price']=round(($dd_data['price']/(1+$mainc_taxtype['tax_rate']/100)),2);
            }
            else
                $A_data['price']=$dd_data['price'];
            $A_data['sum_nonds']=round($A_data['price']*$A_data['count'],2);
            $A_data['sum_nds']=round($zd_val['price']*$A_data['count'],2);
            $A_data['nds']=round(($dd_data['price']*$dd_data['count'])-($dd_data['price']*$dd_data['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
            ?>
            <td>А (до изменения)</td><td>--</td><td>796</td><td>шт.</td><td><?php echo $A_data['count']?></td>
            <td><?php
                echo number_format($A_data['price'],2,"."," ");
            ?></td><td>
            <?php
                echo number_format($A_data['sum_nonds'],2,"."," ");
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
                echo number_format($A_data['nds'],2,"."," ");
            ?>
            </td><td>
                <?php
                echo number_format($A_data['sum_nds'],2,"."," ");
                ?>
            </td><td></td><td></td><td colspan="2"></td>
          </tr>
          <tr>
            <?php
              $B_data['count']=($A_data['count']-$zd_val['count']);
              if((int)$mainc_taxtype['is_nds']==1){
                $B_data['price']=round(($zd_val['price']/(1+$mainc_taxtype['tax_rate']/100)),2);
              }
              else
                $B_data['price']=$zd_val['price'];
              $B_data['sum_nonds']=round($B_data['price']*$B_data['count'],2);
              $B_data['sum_nds']=round($zd_val['price']*$B_data['count'],2);
              $B_data['nds']=round(($zd_val['price']*$B_data['count'])-($zd_val['price']*$B_data['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
            ?>
            <td>Б (после изменения)</td><td>--</td><td>796</td><td>шт.</td><td><?php echo $B_data['count']?></td>
            <td><?php
                echo number_format($B_data['price'],2,"."," ");
            ?></td><td>
            <?php
                echo number_format($B_data['sum_nonds'],2,"."," ");
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
                echo number_format($B_data['nds'],2,"."," ");
            ?>
            </td><td>
                <?php
                echo number_format($B_data['sum_nds'],2,"."," ");
                ?>
            </td><td></td><td></td><td colspan="2"></td>
          </tr>
          <tr>
            <td>В (увеличение)</td><td>--</td><td>X</td><td>X</td><td>X</td>
            <td>X</td><td>
            <?php
            if($B_data['sum_nonds']>$A_data['sum_nonds']){
                echo number_format($B_data['sum_nonds']-$A_data['sum_nonds'],2,"."," ");
                $sum_nonds_v+=$B_data['sum_nonds']-$A_data['sum_nonds'];
            }
            else
                echo number_format(0,2,"."," ");
            ?>
            </td><td>X</td><td>X
            </td><td>
            <?php
                if($B_data['nds']>$A_data['nds']){
                  echo number_format($B_data['nds']-$A_data['nds'],2,"."," ");
                  $nds_sum_v+=($B_data['nds']-$A_data['nds']);
                }
                else
                  echo number_format(0,2,"."," ");
            ?>
            </td><td>
                <?php
                if($B_data['sum_nds']>$A_data['sum_nds']){
                  echo number_format($B_data['sum_nds']-$A_data['sum_nds'],2,"."," ");
                  $sum_nds_v+=($B_data['sum_nds']-$A_data['sum_nds']);
                }
                else
                  echo number_format(0,2,"."," ");
                ?>
            </td><td></td><td></td><td colspan="2"></td>
          </tr>
          <tr>
            <td>Г (уменьшение)</td><td>--</td><td>X</td><td>X</td><td>X</td>
            <td>X</td><td>
            <?php
            if($A_data['sum_nonds']>$B_data['sum_nonds']){
              echo number_format($A_data['sum_nonds']-$B_data['sum_nonds'],2,"."," ");
              $sum_nonds_g+=($A_data['sum_nonds']-$B_data['sum_nonds']);
            }
            else
              echo number_format(0,2,"."," ");
            ?>
            </td><td>X</td><td>X
            </td><td>
            <?php
                if($A_data['nds']>$B_data['nds']){
                echo number_format($A_data['nds']-$B_data['nds'],2,"."," ");
                $nds_sum_g+=$A_data['nds']-$B_data['nds'];
                }
              else
                echo number_format(0,2,"."," ");
            ?>
            </td><td>
                <?php
                if($A_data['sum_nds']>$B_data['sum_nds']){
                echo number_format($A_data['sum_nds']-$B_data['sum_nds'],2,"."," ");
                $sum_nds_g+=($A_data['sum_nds']-$B_data['sum_nds']);
              }
              else
                echo number_format(0,2,"."," ");
                ?>
            </td><td></td><td></td><td colspan="2"></td>
          </tr>
          <?php
          if($i%2==0 && $i<count($zakaz_details)) {
            $pages++;
            echo '</tbody><tbody class="page-break"><tr class="page-break"><td colspan="19"></td></tr>';
            echo '<tbody><tr><td colspan="19"><p>Универсальный корректировочный документ № ';
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
          if($i%2==0){
            $pages++;
            echo '</tbody><tbody class="page-break"><tr class="page-break"><td colspan="19"></td></tr>';
            echo '<tr><td colspan="19"><p>Универсальный корректировочный документ № ';
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
            <td id="line2" colspan="2"></td><td colspan="8" style="text-align: right; padding-left: 8px;"><b>Всего увеличение (сумма строк В)</b></td><td></td><td nowrap>
              <?php  echo number_format($sum_nonds_v,2,"."," "); ?></td>
            <td colspan="1">X</td><td colspan="1">X</td><td nowrap><?php echo $nds_sum_v?></td>
            <td nowrap><?php echo number_format($sum_nds_v,2,"."," ");?></td>
            <td></td>
            <td></td>
            <td colspan="2"></td>
          </tr>
          <tr>
            <td id="line2" colspan="2"></td><td colspan="8" style="text-align: right; padding-left: 8px;"><b>Всего уменьшение (сумма строк Г)</b></td><td></td><td nowrap>
              <?php  echo number_format($sum_nonds_g,2,"."," ");?></td>
            <td colspan="1">X</td><td colspan="1">X</td><td nowrap><?php echo $nds_sum_g;?></td>
            <td nowrap><?php echo number_format($sum_nds_g,2,"."," ");?></td>
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
            <?php if(!empty($mainc_data['ipreg_num']) && $mainc_data['type']==1) echo "Свидетельство ".$mainc_data['ipreg_num']; else echo ""; ?>
            <?php if(!empty($mainc_data['ipreg_date']) && $mainc_data['type']==1) echo " от ".preg_replace("/(\d+)-(\d+)-(\d+)/","$3.$2.$1",$mainc_data['ipreg_date']); else echo ""; ?>
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
            <td colspan="4" style="border: 0px;">К передаточным (отгрузочным) документам</td>
            <td class="border" colspan="14" style="text-align:left;border: 0px;border-bottom: 1px solid black;">
             Универсальный передаточный документ №<?php echo $source_document['id'];?> от <?php echo preg_replace("/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/","$3.$2.$1",($source_document['document_date']!="0000-00-00"?$source_document['document_date']:$source_document['create_date']));?>
            </td>
            <td style="border: 0px;">[5]</td>
          </tr>
          <tr>
            <td colspan="4" style="border: 0px;"></td>
            <td colspan="14" style="border: 0px; text-align: center;">(реквизиты передаточных(отгрузочных) документов, которыми были переданы товары, услуги, результаты работ, права)</td>
            <td style="border: 0px;"></td>
          </tr>
          <tr>
            <td style="border: 0px;" colspan="4">Основание изменения стоимости</td>
            <td class="border" style="text-align:right;border: 0px;border-bottom: 1px solid black;" colspan="14"></td>
            <td style="border: 0px;">[6]</td>
          </tr>
          <tr>
            <td colspan="5" style="border: 0px;"></td>
            <td colspan="13" id="mini" style="border: 0px;">(реквизиты договора, соглашения, уведомления и др.)</td>
            <td style="border: 0px;"></td>
          </tr>
          <tr>
            <td style="border: 0px;" colspan="4">Иные сведения</td>
            <td class="border" style="text-align:right;border: 0px;border-bottom: 1px solid black;" colspan="14"></td>
            <td style="border: 0px;">[7]</td>
          </tr>
          <tr>
            <td colspan="5" style="border: 0px;"></td>
            <td colspan="13" id="mini" style="border: 0px;">(ссылки на неотъемлемые приложения, сопутствующие документы, иные документы и т.п.)</td>
            <td style="border: 0px;"></td>
          </tr>
        </tbody>
        <tbody>
          <tr>
            <td colspan="10" id="line" style="border: 0px;border-right: 2px solid black;">Предлагаю изменить стоимость</td>
            <td colspan="9" style=" padding-left: 10px;border: 0px;">С изменением стоимости согласен</td>
          </tr>
          <tr>
            <td class="border" style="" colspan="2"></td>
            <td id="space"></td>
            <td class="border" style=""></td>
            <td id="space"></td>
            <td class="border" style="" colspan="4"><?php if(!empty($mainc_data['ruk']) && $mainc_data['type']==1) echo $ruk; else echo ""; ?></td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;">[8]</td>
            <td id="space"></td>
            <td class="border" style=""></td>
            <td id="space"></td>
            <td class="border" style=""></td>
            <td id="space"></td>
            <td class="border" style="" colspan="3"></td>
            <td class="centerClass" style=" padding-left: 10px;">[12]</td>
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
            <td colspan="9" style="">Уведомляю об изменении стоимости</td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;"></td>
            <td colspan="3" style=" padding-left: 10px;">Дата</td>
            <td colspan="5">« ____ » _____________________________ 20 ____ г.</td>
            <td class="centerClass" style=" padding-left: 10px;">[13]</td>
          </tr>
          <tr>
            <td class="border" style="" colspan="2"></td>
            <td id="space"></td>
            <td class="border" style=""></td>
            <td id="space"></td>
            <td class="border" style="" colspan="4"><?php if(!empty($mainc_data['ruk']) && $mainc_data['type']==1) echo $ruk; else echo ""; ?></td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;">[9]</td>
            <td id="space"></td>
            <td class="" style=""></td>
            <td id="space"></td>
            <td class="" style=""></td>
            <td id="space"></td>
            <td class="" style="" colspan="3"></td>
            <td class="centerClass" style=" padding-left: 10px;"></td>
          </tr>
          <tr>
            <td id="mini" style="" colspan="2">(должность)</td>
            <td id="space"></td>
            <td id="mini" style="">(подпись)</td>
            <td id="space"></td>
            <td id="mini" style="" colspan="4">(ф.и.о.)</td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;"></td>
            <td id="space"></td>
            <td id="mini" style=""></td>
            <td id="space"></td>
            <td id="mini" style=""></td>
            <td id="space"></td>
            <td id="mini" style=""  colspan="3"></td>
            <td class="centerClass" style=" padding-left: 10px;"></td>
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
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;">[10]</td>
            <td id="space"></td>
            <td class="border" style="" ></td>
            <td id="space"></td>
            <td class="border" style=""></td>
            <td id="space"></td>
            <td class="border" style="" colspan="3"></td>
            <td class="centerClass" style=" padding-left: 10px;">[14]</td>
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
            <td class="border" colspan="9"><?php echo $mainc_data['name'].", ИНН ".$mainc_data['inn'];?></td>
            <td id="line" class="centerClass" style=" padding-left: 10px; padding-right: 10px;">[11]</td>
            <td id="space"></td>
            <td class="border" colspan="7" style="padding-left: 10px;"><?php echo $client_data['name'].", ИНН ".$client_data['inn']." КПП ".$client_data['kpp'];?></td>
            <td class="centerClass" style=" padding-left: 10px;">[15]</td>
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
