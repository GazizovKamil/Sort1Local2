
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Магазин Запчастей</title>

    <!-- Bootstrap -->
    <link href="/vendor/bootstrap/css/bootstrap.css?_=<?php echo filemtime('vendor/bootstrap/css/bootstrap.css');?>" rel="stylesheet">
    <link href="/vendor/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet">
    <link href="/css/top.css?ver=1.1" rel="stylesheet">
    <link rel="stylesheet" href="/calendar/calendar.css">
    <link rel="stylesheet" href="/price/css/jquery.fileupload.css">
    <link rel="stylesheet" href="/css/jquery-ui.css">

    <script src="/js/jquery-3.6.0.js"></script>
    <script src="/js/jquery.blockUI.js"></script>
    <script src="/js/jquery.serializejson.js"></script>
    <script src="/js/moment.min.js"></script>
    <!--script src="https://unpkg.com/imask"></script-->
    <script src="https://api-maps.yandex.ru/2.1/?apikey=297f0577-9695-4bdf-a0af-75f2d9a9c42c&lang=ru_RU" type="text/javascript"></script>
    <script src="/js/lib.js?_=<?php echo filemtime('js/lib.js');?>"></script>
    <script src="/js/cdek-it-widget3.js" type="text/javascript"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/@unocss/runtime" type="text/javascript"></script> -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/@unocss/reset/tailwind.min.css" rel="stylesheet"> -->
    <?php
    if(session_id()==="") session_start();
    if(isset($_SESSION['user_id'])){
      if($_SESSION['main_company']==1523){
      ?>
      <script src="/js/1_chim.js?_=<?php echo time();?>"></script>
      <?php
      }
      else {
      ?>
      <script src="/js/1.js?_=<?php echo time();?>"></script>
      <?php
      }
      if($_SESSION['main_company']==1523){
      ?>
      <script src="/js/1_group_chim.js?_=<?php echo time();?>"></script>
      <?php
      }
      else {
      ?>
      <script src="/js/1_group.js?_=<?php echo time();?>"></script>
      <?php
      }
      ?>
      <script src="/js/2.js?_=<?php echo filemtime('js/2.js');?>"></script>
      <script src="/js/3.js?_=<?php echo filemtime('js/3.js');?>"></script>
      <script src="/js/3_zakaz.js?_=<?php echo filemtime('js/3_zakaz.js');?>"></script>
      <script src="/js/3_market_zakaz.js?_=<?php echo filemtime('js/3_market_zakaz.js');?>"></script>
      <script src="/js/4.js?_=<?php echo filemtime('js/4.js');?>"></script>
      <script src="/js/4_delivery.js?_=<?php echo filemtime('js/4_delivery.js');?>"></script>
      <script src="/js/6.js?_=<?php echo filemtime('js/6.js');?>"></script>
      <script src="/js/6_sort1.js?_=<?php echo filemtime('js/6_sort1.js');?>"></script>
      <script src="/js/7.js?_=<?php echo filemtime('js/7.js');?>"></script>
      <script src="/js/10_dogovor.js?_=<?php echo filemtime('js/10_dogovor.js');?>"></script>
      <script src="/js/8.js?_=<?php echo filemtime('js/8.js');?>"></script>
      <script src="/js/9.js?_=<?php echo filemtime('js/9.js');?>"></script>
      <script src="/js/9_tax.js?_=<?php echo filemtime('js/9_tax.js');?>"></script>
      <script src="/js/9_email.js?_=<?php echo filemtime('js/9_email.js');?>"></script>
      <script src="/js/9_acquiring.js?_=<?php echo filemtime('js/9_acquiring.js');?>"></script>
      <script src="/js/9_marketplace.js?_=<?php echo filemtime('js/9_marketplace.js');?>"></script>
      <script src="/js/9_logistic.js?_=<?php echo filemtime('js/9_logistic.js');?>"></script>
      <script src="/js/9_crossdata.js?_=<?php echo filemtime('js/9_logistic.js');?>"></script>
      <script src="/js/10_document.js?_=<?php echo filemtime('js/10_document.js');?>"></script>
      <script src="/js/11.js?_=<?php echo filemtime('js/11.js');?>"></script>
      <script src="/js/12.js?_=<?php echo filemtime('js/12.js');?>"></script>
      <script src="/js/12_proposal.js?_=<?php echo filemtime('js/12_proposal.js');?>"></script>
      <script src="/js/basket.js?_=<?php echo filemtime('js/basket.js');?>"></script>
      <script src="/js/sites.js?_=<?php echo filemtime('js/sites.js');?>"></script>
      <script src="/js/bootstrap.file-input.min.js?_=<?php echo time();?>"></script>
      <script src="/js/shim.min.js"></script>
      <script src="/js/kkmserver.js"></script>
      <script src="/js/xlsx.full.min.js?_=<?php echo time();?>"></script>
      <script src="/js/excel_reader_api.js?_=<?php echo time();?>"></script>
      <script src="/js/excel_reader_cross.js?_=<?php echo time();?>"></script>
      <script src="/js/excel_reader_jobs.js?_=<?php echo time();?>"></script>
      <script src="/js/excel_reader_clients.js?_=<?php echo time();?>"></script>
      <script src="/js/iconv.js"></script>
      <script type="text/javascript" src="/js/jquery.maskedinput.min.js"></script>
      <script src="/js/apexcharts/apexcharts.js"></script>
      <script>var KkmServerAddIn = {};</script>
    <?php
    }
    ?>
    <script src="/js/jquery-ui.js"></script>

<?php
//    <script src="/vendor/bootstrap/js/modal.js"></script>
?>
    <script src="/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="/vendor/bootstrap-select/js/bootstrap-select.min.js"></script>
    <script src="/js/bootbox.min.js"></script>
    <script src="/price/js/jquery.fileupload.js"></script>
    <script> bootbox.setLocale("ru");</script>
    <link href="/js/summernote/summernote-lite.css" rel="stylesheet">
    <script src="/js/summernote/summernote-lite.js"></script>
  </head>
<body>
