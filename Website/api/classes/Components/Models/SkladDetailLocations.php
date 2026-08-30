<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\SkladDetailLocation;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class SkladDetailLocations extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_sklad_detail_location($request) {
      	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
      		    //return self::_error_arr("У Вас нет прав для данного действия");
      	    }
            $db = DB::getInstance();
      	    if (isset($request->sklad_id)) $sklad_id=(int)$request->sklad_id;
      		if (isset($request->detail_id) && $request->detail_id!=0) $detail_id=(int)$request->detail_id;
      		if (isset($request->topology_id)) $topology_id=(int)$request->topology_id;
			if (isset($request->location_id)) $location_id=(int)$request->location_id;
			if (isset($request->document_details_id)) $document_details_id=(int)$request->document_details_id;
      		$topology=$db->getAll("select * from sklad_topology_levels where topology_id=?i order by level",$topology_id);
      		$location="";
      		foreach($topology as $top_key=>$top_val){
      			//echo "request->ряд:".$request->ряд."\n";
      			$name=$top_val['name'];
      			//echo $top_val['name'].":".$request->$name."\n";
      			if($top_key<(count((array)$topology)-1))
      				$location.=$request->$name.$top_val['delimiter'];
      			else
      				$location.=$request->$name;
      		}
            if(isset($location_id) && $location_id>0){
              $sklad_det_loc=new SkladDetailLocation(0,0,0,$location_id);
            }
            else {
              if (isset($sklad_id) && $sklad_id>0 && isset($detail_id) && !empty($location)) {
                $sklad_det_loc=new SkladDetailLocation($sklad_id,$detail_id,$location);
              }
              else
                 return self::_error_arr("Не указана деталь или склад или местоположение");
            }
            if (isset($request->sklad_id) && (int)$request->sklad_id>0) {
              $sklads=$db->getCol("select id from sklad where company_id in (select company_id from user_companys where user_id=?i and main_company_id=0)",$_SESSION['user_id']);
              if ($sklads && in_array($request->sklad_id,$sklads))
                  $sklad_det_loc->sklad_id=(int)$request->sklad_id;
              else {
                  return self::_error_arr("Нельзя указывать чужой склад");
              }
            }
            else
              return self::_error_arr("Не выбран склад");
            if (isset($request->count) && (int)$request->count>=0) {
      				switch($request->subaction){
      					case "edit": $sklad_det_loc->count=(float)$request->count; break;
      					case "add": $sklad_det_loc->count+=(float)$request->count; break;
      				}
      		}
			else {
				return self::_error_arr("Не указано количество");
			}
            if (!empty($location) && isset($location_id) && $location_id>0) {
      				$sklad_det_loc->location=$location;
			}
			if (!empty($document_details_id) && (int)$document_details_id>0) {
				$sklad_det_loc->document_details_id=(int)$document_details_id;
			}
			$err=$sklad_det_loc->save(); 
			$check_sql="select sd.count as sd_count,sum(sdl.count) as sdl_count from sklad_details sd left join sklad_detail_locations sdl on (sdl.sklad_id=sd.sklad_id and sdl.detail_id=sd.detail_id) where sd.sklad_id=?i and sd.detail_id=?i";
			$check_res=$db->getRow($check_sql,$sklad_id,$detail_id);
			if($check_res['sd_count']<$check_res['sdl_count']) {
				return self::_error_arr("Кол-во располагаемых деталей первышает кол-во на складе");
			}
			//if($check_res['sdl_count']<=0) {
			//	return self::_error_arr("Кол-во располагаемых деталей равно 0");
			//}
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: if (isset($request->sklad_id) && (int)$request->sklad_id>0){
                          		$status="ok"; $msg="";
                      		}
                      		else {
                          	    $status="ok"; $msg="";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"err"=>"", "check_res"=>$check_res);
        }


	public static function get_sklad_detail_location($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		//return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql="select * from sklad_detail_locations where sklad_id=?i and detail_id=?i and location=?s";
	    $res=$db->getRow($sql,(int)$request->sklad_id,(int)$request->detail_id,$request->location);
	    if ($res){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['sklad_id']=(int)$request->sklad_id;
    		$ret['sklad_topology_id']=$db->getOne("select topology_id from sklad where id=?i",(int)$request->sklad_id);
    		$ret['sklad_detail_location']=$res;
        $ret['topology_levels']=$db->getAll("select * from sklad_topology_levels where topology_id=?i",(int)$ret['sklad_topology_id']);
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_sklad_detail_locations($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
			//return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    //$sql="select * from sklad_details where sklad_id=?i";
	    //$res=$db->getAll($sql,(int)$request->sklad_id);

	    $sql="select * from sklad_detail_locations where sklad_id=?i and detail_id=?i and `count`>0 ";
	    $sql.=" order by create_date";
	    $res=$db->getAll($sql,(int)$request->sklad_id,(int)$request->detail_id);
	    if (is_array($res) && count((array)$res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['sklad_id']=(int)$request->sklad_id;
		$ret['sklad_detail_locations']=$res;
		$ret['msg']="";
	    }
	    else {
		$ret['status']="ok";
		$ret['msg']="";
		$ret['err']="";
		$ret['sklad_id']=(int)$request->sklad_id;
		$ret['sklad_details']=[];
		$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
		$ret['sklad_pages']=1;
		$ret['details_count']=0;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_sklad_detail_location($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		      return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->sklad_id)) {$sklad_id=(int)$request->sklad_id;}
    	if (isset($request->detail_id)) {$detail_id=(int)$request->detail_id;}
    	if (isset($request->location)) {$location=$request->location;}
    	if (isset($sklad_id) && $sklad_id>0 && isset($detail_id) && $detail_id!=0){
      		$res2=$db->query("delete from sklad_detail_locations where detail_id=?i and sklad_id=?i and location=?s",$detail_id,$sklad_id,$location);
      		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
      		if ($res2){
      		    $ret['status']="ok";
      		    $ret['msg']="Размещение успешно удалено";
      		}
      		else {
      		    $ret['status']="err";
      		    $ret['err']="не удалось удалить размещение";
      		}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

}
?>
