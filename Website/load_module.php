<?php
session_start();
include "include/db.inc.php";
include "include/db_safe.inc.php";
$db = new SafeMySQL(['mysqli' => $mysqli]);
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
header("Content-type: application/json");
if (isset($_GET['id']) && (int)$_GET['id']>0){
    $module_id=(int)$_GET['id'];
    include "modules/".(int)$module_id.".php";
}
else {
    $ret_arr=array("content"=>"Error in request");
    echo json_encode($ret_arr);
}
$mysqli->close();
?>
