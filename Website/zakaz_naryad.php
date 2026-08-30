<?php
 session_start();
 if(!isset($_SESSION['user_id'])) {
   die("Необходимо авторизоваться");
 }
 include "include/db_safe.inc.php";
 $db=new SafeMySQL();
 if(isset($_GET['zakaz_id'])){
   $zakaz_id=$_GET['zakaz_id'];
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>201)",$zakaz_id);
   $zakaz_jobs=$db->getAll("select zj.*,sj.name from zakaz_jobs zj
    left join service_jobs sj on (sj.id=zj.job_id)
    where zj.zakaz_id=?i and (zj.status<100 or zj.status>201)",$zakaz_id);
   $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
   $company_car=$db->getRow("select * from company_cars where id=?i",$zakaz_data['car_id']);
   $user_data=$db->getRow("SELECT name,middlename,lastname FROM users WHERE id=?i",$zakaz_data['user_id']);
   $service_notes_car_id=$db->getOne("select car_id from service_notes where main_company_id=?i and zakaz_id=?i",$_SESSION['main_company'],(int)$_GET['zakaz_id']);
   if($service_notes_car_id<=0){
        $company_cars=$db->getAll("select * from company_cars where company_id=?i and main_company_id=?i",$zakaz_data['company_id'],$_SESSION['main_company']);
        /*if(count($company_cars)==1){
                $service_notes_car_id=$company_cars[0]['id'];
        }
        if(count($company_cars)>1){
                $
        }*/
   }
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
   $zakaz_data['id']=$zakaz_id;
  //}
  if((int)$zakaz_jobs[0]['company_car_id']>0)
        $company_car=$db->getRow("select * from company_cars where id=?i",$zakaz_jobs[0]['company_car_id']);
        else {
        $company_car=array(
                "auto_maker_name" =>"",
                "auto_model" =>"",
                "engine_num" =>"",
                "vin" =>"",
                "auto_dov_num" =>"",
                "kuzov_num" =>"",
                "made_year" =>"",
                "probeg" =>"",

        );
        }
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
        <title>Заказ-наряд</title>
        <style type="text/css">
            @page { size: 21cm 29.7cm; margin-right: 1.5cm; margin-top: 2cm; margin-bottom: 2cm }
            p { color: #000000; line-height: 115%; orphans: 2; widows: 2; margin-bottom: 0cm; direction: ltr; background: transparent }
            p.western { font-family: "Times New Roman", serif; font-size: 12pt; so-language: ru-RU }
                        p.western2 { font-family: "Verdana", serif; font-size: 12pt; so-language: ru-RU }
            p.cjk { font-family: "Times New Roman", serif; font-size: 12pt }
            p.ctl { font-family: "Times New Roman", serif; font-size: 12pt; so-language: ar-SA }
        </style>
    </head>
    <body>
    <table style="width:21cm" cellpadding="4" cellspacing="0">
        <tbody id="schet_header">

                                <colgroup>
                                        <col style="width:9cm"/>
                                        <col style="width:2cm"/>
                                        <col style="width:2cm"/>
                                        <col style="width:1cm"/>
                                        <col style="width:1cm"/>
                                        <col style="width:3cm"/>
                                        <col style="width:3cm"/>
                                </colgroup>

                                <tr valign="top">
                                        <td colspan="4" style="font-weight: bold ;border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western2" align="justify" style="orphans: 0; widows: 0">
                                                Заказ-наряд:</p>
                                        </td>
                                        <td colspan="3" style="font-weight:bold;border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western2" align="justify" style="orphans: 0; widows: 0">
                                        <?php echo $mainc_data['short_name']; ?></p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #928f8f; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                            Заказчик:</p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #928f8f; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Телефон</p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                </p>

                                        </td>
                                </tr>
                                <?php  ?>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        <?php echo $client_data['name']; ?></p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        <?php echo $client_data['mphone']; ?></p>
                                        </td>
                                        <td colspan="3" rowspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                        <?php echo $mainc_data['name']; ?></p>

                                        </td>
                                </tr>
                                <?php  ?>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #928f8f; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Автомобиль</p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #928f8f; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Номер кузова</p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        <?php echo $company_car['auto_maker_name'].' '.$company_car['auto_model'] ?></p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        <?php echo $company_car['kuzov_num'] ?></p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #928f8f; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Гос номер</p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #928f8f; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Номер двигателя</p>
                                        </td>
                                        <td colspan="3" rowspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        <?php echo $company_car['auto_gov_num'] ?></p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        <?php echo $company_car['engine_num'] ?></p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #928f8f; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Год выпуска</p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #928f8f; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Пробег</p>
                                        </td>
                                        <td colspan="3" rowspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        <?php echo $company_car['made_year'] ?></p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        <?php echo $company_car['probeg'] ?>  </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="4" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="2" rowspan="3" style="font-weight: bold ; border-top: none; border-bottom: 1px solid  #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                1. ВЫПОЛНЕННЫЕ РАБОТЫ И УСЛУГИ</p>
                                        </td>
                                        <td rowspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Дата</p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                оформления</p>
                                        </td>
                                        <?php
                                 ?>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center"; style="orphans: 0; widows: 0">
                                        <?php echo date("d.m.Y",strtotime($zakaz_data['create_date'])); ?></p>
                                </td>
                                <?php  ?>
                                </tr>
                                <tr valign="top">
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                исполнения</p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        </p>
                                </td>
                                </tr>

                                <tr valign="top">
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                факт.исполнения</p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        </p>
                                </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        </p>
                                        </td>
                                        <td style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Наименование работ, услуг</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Кол-во</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Цена</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Сумма</p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Фамилия, И., О. Исполнителя</p>
                                        </td>
                                </tr>
                                <?php
                                $zakaz_sum=0;
                                foreach($zakaz_jobs as $zj_key=>$zj_val){ ?>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        <?php echo $zj_val['name']; ?></p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                        <?php echo $zj_val['count']; ?></p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                        <?php echo number_format($zj_val['price'],2,"."," "); ?>р.</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                        <?php echo number_format($zj_val['count']*$zj_val['price'],2,"."," "); ?>р.</p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        <?php
                                        $zakaz_employees=$db->getAll("SELECT lastname,`name`,surname FROM service_employees WHERE id IN (SELECT employee_id FROM zakaz_job_employees WHERE zakaz_job_id=?i)",$zj_val['id']);
                                        foreach($zakaz_employees as $ze_val){
                                            echo $ze_val['lastname'].' '.$ze_val['name'].' '.$ze_val['surname'].'<br>';
                                        }
                                        $zakaz_sum+=$zj_val['count']*$zj_val['price'];
                                        ?></p>
                                        </td>
                                </tr>
                                <?php } ?>
                                <tr valign="top">
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #ffffff; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                                Итого:</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 1px solid #000000; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                         <?php echo number_format($zakaz_sum,2,"."," "); ?>р. </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="4" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                      <td colspan="6" style="font-weight: bold; border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        2. ЗАПАСНЫЕ ЧАСТИ И МАТЕРИАЛЫ, ОПЛАЧИВАЕМЫЕ ЗАКАЗЧИКОМ</p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="3" style="border: 1px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Материальные ценности</p>
                                        </td>
                                        <td rowspan="2" style="border: 1px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Единица измерения</p>
                                        </td>
                                        <td rowspan="2" style="border: 1px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Кол-во</p>
                                        </td>
                                        <td rowspan="2" style="border: 1px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Цена</p>
                                        </td>
                                        <td rowspan="2" style="border: 1px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Сумма</p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Наименование</p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                ОЕМ</p>
                                        </td>
                                </tr>
                                <?php $sum_details=0;
                                foreach($zakaz_details as $zj_key=>$zj_val){ ?>
                                <tr valign="top">
                                        <td style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                <?php echo "<input type='text' onkeyup='this.style.width = ((this.value.length + 4) * 8) + \"px\";' value='".str_replace("'",'"',$zj_val['name'])."' style='border:none;'>" ?></p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western"align="right"  style="orphans: 0; widows: 0">
                                        <?php echo "<input type='text' onkeyup='this.style.width = ((this.value.length + 4) * 8) + \"px\";' value='".str_replace("'",'"',$zj_val['article'])."' style='border:none;'>" ?></p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                                шт</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                        <?php echo $zj_val['count']; ?></p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                        <?php echo number_format($zj_val['price'],2,"."," "); ?>р.</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                        <?php echo number_format($zj_val['count']*$zj_val['price'],2,"."," ");
                                        $sum_details+=$zj_val['count']*$zj_val['price']; ?>р.</p>
                                        </td>
                                </tr>
                                <?php } ?>
                                <tr valign="top">
                                        <td colspan="6" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #ffffff; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                                Итого:</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                         <?php echo number_format($sum_details,2,"."," "); ?>р. </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="5" style="font-weight: bold; border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                3. ОТМЕТКА О ПРИНЯТЫХ ОТ ЗАКАЗЧИКА ЗАПАСНЫХ ЧАСТЯХ И МАТЕРИАЛАХ</p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Наименование</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Кол-во</p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Комментарий</p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                                Принял в производство</p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #ffffff; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                                Итого:</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #000000; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="4" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        С объемом работ и первоначальной стоимостью заказа согласен,</p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                        <?php echo number_format($zakaz_sum+$sum_details,2,"."," "); ?> руб 00 коп</p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="4" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                         с Правилами пользования услугами предприятия ознакомлен.</p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="right" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="4" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="font-size: 8pt; orphans: 0; widows: 0">
                                                (подпись)</p>
                                </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Заказ принял</p>
                                        </td>
                                        <td  colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                                        <?php
                                         echo $user_data['lastname'].' '.$user_data['name'].' '.$user_data['middlename'].'<br>'; ?></p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td  colspan="2" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="font-size: 8pt; orphans: 0; widows: 0">
                                                (подпись)</p>
                                        </td>
                                        <td  style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #ffffff; border-right: 1px solid #ffffff; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="font-size: 8pt; orphans: 0; widows: 0">
                                                (Ф.И.О., должность)</p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Получено при оформлении заказа</p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="5" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="font-size: 8pt; orphans: 0; widows: 0">
                                                (сумма прописью)</p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        </p>
                                </td>
                                </tr>
                <tr valign="top">
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        </p>
                                </td>
                                <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                </p>
                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="font-size: 8pt;  orphans: 0; widows: 0">
                                                (подпись кассира, штамп)</p>
                                        </td>
                                        <td style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        </p>
                                </td>
                                <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="font-size: 8pt; orphans: 0; widows: 0">
                                        (Ф.И.О., должность)</p>
                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="7" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                Получено в окончательный расчет</p>
                                        </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="5" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="font-size: 8pt; orphans: 0; widows: 0">
                                                (сумма прописью)</p>
                                        </td>
                                        <td colspan="2" style="border-top: none; border-bottom: 1px solid #ffffff; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        </p>
                                </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                                </p>
                                        </td>
                                        <td style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        </p>
                                </td>
                                <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                </p>
                            </td>
                                </tr>
                                <tr valign="top">
                                        <td colspan="3" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="font-size: 8pt;  orphans: 0; widows: 0">
                                                (подпись кассира, штамп)</p>
                                        </td>
                                        <td style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        </p>
                                </td>
                                <td colspan="3" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="font-size: 8pt; orphans: 0; widows: 0">
                                        (Ф.И.О., должность)</p>
                            </td>
                       </tr>
                           <tr valign="top">
                                <td colspan="7" style="background:rgb(199, 195, 195); border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                
                                <?php
                                $zakaz_garant=$db->getOne("select zakaz_garant from zakaz_garant where deleted=0 and is_default=1 and main_company_id=?i",$_SESSION['main_company']);
                                if($zakaz_garant){
                                        echo $zakaz_garant;
                                }
                                else {
                                ?>
                                <b>Гарантийные обязательства </b><br/>при соблюдении правил эксплуатации <?php echo $company_car['auto_maker_name'] ?> и рекомендаций <?php echo $mainc_data['name']; ?>:<br/>
                                а) на агрегаты, подвергавшиеся разборке, сборке и замене деталей - 6 месяцев, но не более 10 тысяч км пробега;<br/>
                                б) на агрегаты, механизмы, узлы, детали, системы подвергавшиеся регулировке и техническому обслуживанию - 1 месяц, но не более 1000 км пробега;<br/>
                                в) на детали, агрегаты, механизмы предоставленные заказчиком, либо бывшие в употреблении, а также на расходные материалы гарантийные обязательства не распространяются.
                                <?php
                                }
                                ?>
                        </td></tr>
                          <tr valign="top">
                                <td colspan="7" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                        <br/>
                            С объемом и стоимостью выполненных работ согласен, комплектность и внешний вид автомобиля проверил,автомобиль из ремонта получил.
                                </p>
                          </td></tr>
                          <tr valign="top">
                                <td colspan="4" style="border-top: none; border-bottom: 1px solid #FFFFFF;; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                        </p>
                                </td>
                          <td colspan="3" style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                            </p>
                           </td></tr>
                          <tr valign="top">
                                <td colspan="4" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" style="orphans: 0; widows: 0">
                                </p>
                           </td>
                                <td colspan="3" style="border-top: none; border-bottom: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #FFFFFF; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western" align="center" style="font-size: 8pt; orphans: 0; widows: 0">
                                        (подпись заказчика)</p>
                                </td></tr>

                </tbody>
        </table>
</body>
</html>
