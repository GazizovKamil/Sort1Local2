<?php
if(empty($_GET['hash'])){
    die("Не указан заказ");
}
include "include/db_safe.inc.php";
$db=new SafeMySQL();

function set_zakaz_status($price_zakaz_id){
    global $db;
    $zakazes=$db->getCol("select distinct(zakaz_id) from zakaz_details where id in (select zakaz_details_id from price_zakaz_details where price_zakaz_id=?i)",$price_zakaz_id);
    foreach($zakazes as $zakaz_id){
        $zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz_id);
        $set_zakaz_status=$db->query("update zakaz set status=?i where id=?i",$zakaz_status,$zakaz_id);
    }
}

$price_zakaz=$db->getRow("select * from price_zakaz where hash=?s",$_GET['hash']);
if(!$price_zakaz){
    die("Заказ не найден");
}
$company_data=$db->getRow("select * from company where id=?i",$price_zakaz['from_company_id']);
if(isset($_GET['confirm_zakaz']) && (int)$_GET['confirm_zakaz']==1){
    if((int)$price_zakaz['status']<2){
        $res1=$db->query("update price_zakaz set status=20 where id=?i",$price_zakaz['id']);
        if($res1) {
            $res1=$db->query("update price_zakaz_details set status=20 where price_zakaz_id=?i",$price_zakaz['id']);
            $res2=$db->query("update zakaz_details set status=20 where id in (select zakaz_details_id from price_zakaz_details where price_zakaz_id=?i)",$price_zakaz['id']);
            set_zakaz_status($price_zakaz['id']);
        }
        $price_zakaz['status']=2;
    }
    else {
        echo '<font style="color:red;">Можно подтвердить только новый заказ</font>';
    }
}
if(isset($_GET['reject_zakaz']) && (int)$_GET['reject_zakaz']==1){
    if((int)$price_zakaz['status']<30){
        $res1=$db->query("update price_zakaz set status=101 where id=?i",$price_zakaz['id']);
        if($res1) {
            $res1=$db->query("update price_zakaz_details set status=101 where price_zakaz_id=?i",$price_zakaz['id']);
            $res2=$db->query("update zakaz_details set status=101 where id in (select zakaz_details_id from price_zakaz_details where price_zakaz_id=?i)",$price_zakaz['id']);
            set_zakaz_status($price_zakaz['id']);
        }
        $price_zakaz['status']=101;
    }
    else {
        echo '<font style="color:red;">Нельзя отменить выданный или отмененный заказ</font>';
    }
}
if(isset($_GET['issue_zakaz']) && (int)$_GET['issue_zakaz']==1){
    if((int)$price_zakaz['status']<30){
        $res1=$db->query("update price_zakaz set status=30 where id=?i",$price_zakaz['id']);
        if($res1) {
            $res1=$db->query("update price_zakaz_details set status=35 where price_zakaz_id=?i",$price_zakaz['id']);
            $res2=$db->query("update zakaz_details set status=35 where id in (select zakaz_details_id from price_zakaz_details where price_zakaz_id=?i)",$price_zakaz['id']);
            set_zakaz_status($price_zakaz['id']);
        }
        $price_zakaz['status']=35;
    }
    else {
        echo '<font style="color:red;">Нельзя выдать выданный или отмененный заказ</font>';
    }
}

$zd_statuses=$db->getInd("id","select id,descr from zakaz_detail_statuses");

$html='<html><head>
<script>
    function confirm_zakaz(hash){
        document.body.innerHTML = "Обновляю ....";
        window.location.href="/show_price_zakaz.php?hash='.$_GET['hash'].'&confirm_zakaz=1";
    }
    function reject_zakaz(hash){
        document.body.innerHTML = "Обновляю ....";
        window.location.href="/show_price_zakaz.php?hash='.$_GET['hash'].'&reject_zakaz=1";
    }
    function issue_zakaz(hash){
        document.body.innerHTML = "Обновляю ....";
        window.location.href="/show_price_zakaz.php?hash='.$_GET['hash'].'&issue_zakaz=1";
    }
</script>
<style>table {
    border-collapse: collapse;
    //width: 100%;
    }
    table td, th { 
    text-align: center;
    padding-left: 5px;
    padding-right: 5px;
    }

    .red {
        background: red;
        color:white;
    }
    .green {
        background: green;
        color:white;
    }
    .blue {
        background: blue;
        color:white;
    }
</style>
</head><body>';
$html.='<h3>Заказ № '.$price_zakaz['id'].' от '.$price_zakaz['create_date'].'</h3>';
$html.='<table border="0"><tbody><tr>
    <td>Заказчик: </td><td>'.$company_data['name'].'</td>
    </tr></tbody></table>';
$html.='<hr> <h4>Детали в заказе:</h4>';
$html.='<table border="1">
<thead><tr><th>№</th><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Цена</th><th>кол-во</th><th>Сумма</th><th>Статус</th></tr></thead>
<tbody>';
$zakaz_details=$db->getAll("select * from price_zakaz_details where price_zakaz_id=?i",$price_zakaz['id']);
foreach($zakaz_details as $i=>$det){
    $html.='<tr><td>'.($i+1).'</td><td>'.$det['article'].'</td><td>'.$det['brand'].'</td><td>'.$det['name'].'</td><td>'.$det['cost'].'</td><td>'.$det['count'].'</td><td>'.($det['count']*$det['cost']).'</td><td>'.$zd_statuses[$det['status']]['descr'].'</td></tr>';
}
$html.='</tbody></table><hr>';
if((int)$price_zakaz['status']<2) $html.='<button class="green" onclick="confirm_zakaz(\''.$_GET['hash'].'\')">Подтвердить заказ</button> ';
if((int)$price_zakaz['status']<30) $html.='<button class="red" onclick="reject_zakaz(\''.$_GET['hash'].'\')">Отказать</button> ';
if((int)$price_zakaz['status']<30) $html.='<button class="blue" onclick="issue_zakaz(\''.$_GET['hash'].'\')">Выдать</button>';
$html.='</body></html>';
echo $html;
?>