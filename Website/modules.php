<?php
/*
$permitions=array(
  "1" => "(1,2,3,4,5,6,7,9,10,12,11)",
  "2" => "(1,2,3,4,5,6,7,9,10,12,11)",
  "3" => "(1,2,3,5,9,12)",
  "5" => "(1,2,3,6,7,9,12)",
  "6" => "(1,2,7,9,12)",
  "7" => "(7,12)",
  "10" => "(1,3,4,10,12)"
);
*/
//echo $permitions[$_SESSION['roles']];
$sql="select id,name,name_rus,modules_rights from roles_of_company where id=?i and main_company_id=?i";
        $res1=$db->getRow($sql,$_SESSION['roles'],$_SESSION['main_company']);
        $res2=$db->getRow($sql,$_SESSION['roles'],0);
        if(!$res1 || empty($res1['modules_rights'])) {
            
            $res=$res2;
            
        }
        else $res=$res1;
        if($res1) $res1['modules_rights']=json_decode(preg_replace("/[\t\n]/","",$res1['modules_rights']),true);
        if($res2) $res2['modules_rights']=json_decode(preg_replace("/[\t\n]/","",$res2['modules_rights']),true);
        if(empty($res1['modules_rights'])) $res1['modules_rights']=array();
        if(empty($res2['modules_rights'])) $res2['modules_rights']=array();
        $ret=array();
	    if($res){
            //$res['modules_rights_orig']=preg_replace("/[\t\n]/","",$res['modules_rights']);
            
            $modules_rights=json_decode(preg_replace("/[\t\n]/","",$res['modules_rights']),true);
            //$res['modules_rights']=array_replace_recursive($res2['modules_rights'],$res1['modules_rights']);
            
            //$ret['intersect']=self::intersectRecursive($res2['modules_rights'],$res1['modules_rights']);
            //if(count($ret['intersect'])>0) $res['modules_rights']=$ret['intersect'];
        }
foreach($modules_rights['modules'] as $mkey=>$mval){
    if($mval['show']==1) $permitions[$_SESSION['roles']][]=$mval['id'];
}
//echo print_r($res['modules_rights'],true);
$data=$db->getAll("select * from modules where module_id in (".implode(",",$permitions[$_SESSION['roles']]).") or module_id=13");
//echo "<pre>";
//echo implode(",",$permitions[$_SESSION['roles']]);
//echo print_r($data,true);
//echo "<div style=\"min-height: 28px; \"></div>";
echo "<div style=\"position: fixed; overflow: auto; min-height: 100vh; width: 85px; margin-top: -1px; background-color: #eeeeee\">";
echo "<div class=\"btn-group-vertical\" role=\"group\" aria-label=\"...\" style='width:100%'>";
foreach( $data as $key=>$val){
    if(!empty($val['icon'])){
    	if(isset($action) && $val['module_id']==(int)$action)
            echo "<button type=\"button\" class=\"btn btn-default active\" id=\"mod_link_".$val['module_id']."\" onclick=\"load_module(".$val['module_id'].")\" style=\"font-size:12px; padding: 4px 5px;\" title=\"".$val['descr']."\">";
    	else
            echo "<button type=\"button\" class=\"btn btn-default\" id=\"mod_link_".$val['module_id']."\" onclick=\"load_module(".$val['module_id'].")\" style=\"font-size:12px; padding: 4px 5px;\" title=\"".$val['descr']."\">";
    	echo "<img src=\"/new_images/".$val['icon']."\" style=\"width: 20px;\"><br/><span style=\"font-size:12px;\">".$val['name']."</span>";
    	echo "</button>";
        }
        else {
    	if($val['module_id']==(int)$action)
        echo "<button type=\"button\" class=\"btn btn-default active\" id=\"mod_link_".$val['module_id']."\" onclick=\"load_module(".$val['module_id'].")\" style=\"font-size:12px; padding: 4px 5px;\" title=\"".$val['descr']."\">";
    	else
        echo "<button type=\"button\" class=\"btn btn-default\" id=\"mod_link_".$val['module_id']."\" onclick=\"load_module(".$val['module_id'].")\" style=\"font-size:12px;padding: 4px 5px;\" title=\"".$val['descr']."\">";
    	echo "<span style=\"font-size:1.5em;\" class=\"glyphicon ".$val['icon_class']."\"></span><br/><span style=\"font-size:12px;padding: 4px 5px;\">".$val['name']."</span>";
    	echo "</button>";
    }
}
echo '<a href="https://t.me/SORT1_PRO" target="_blank" class="btn btn-primary" style="font-size:12px; display: flex; flex-direction: column; align-items: center; text-align: center;" title="Telegram-канал с новостями Сорт1">
<img src="/new_images/telegram.svg" style="width: 25px;padding-bottom: 5px;">
<span style="font-size:10px;">Канал Sort1</span>
</a>';
echo "</div>";
echo "</div>";
?>
