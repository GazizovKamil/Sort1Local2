<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\SkladDetail;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class SkladTopologys extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_sklad_topology($request) {
      	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
      		    //return self::_error_arr("У Вас нет прав для данного действия");
      	    }
            $db = DB::getInstance();
            if(empty($request->name))
              return self::_error_arr("Укажите наименование топологии");
            else {
              if($request->topology_id==0){
                if($db->query("insert into sklad_topology set name=?s,main_company_id=?i",$request->name,$_SESSION['main_company'])){
                  $topology_id=$db->insertId();
                }
                else {
                  return self::_error_arr("Топология с таким наименованием уже существует");
                }
              }
              elseif ((int)$request->topology_id>0) {
                $res_upd=$db->query("update sklad_topology set name=?s where id=?i",$request->name,$request->topology_id);

              }
            }

      			if (!isset($topology_id) && isset($request->topology_id))
              $topology_id=(int)$request->topology_id;
            if((int)$topology_id>0){
              $db->query("delete from sklad_topology_levels where topology_id=?i",$topology_id);
              //echo "topology_id: $topology_id\n";
        			foreach($request->levels as $l_key => $l_val){
                //echo "key: $l_key l_val:".print_r($l_val,true)."\n";
        				if(!empty($l_val['name'])) {
                  if(!empty($l_val['type']))
                    $type=$l_val['type'];
                  else
					$type=1;
				if(!empty($l_val['len']))
					$t_length=$l_val['len'];
				else
					$t_length=0;
				if(!empty($l_val['first']) || $l_val['first']=="0")
					$t_first=$l_val['first'];
				else {
					switch($type){
						case "1";$t_first=1; break;
						case "2";$t_first="A"; break;
						case "3";$t_first="А"; break;
					}
				}
                  if(!empty($l_val['delimiter']))
                      $delimiter=$l_val['delimiter'];
                  else
                      $delimiter="-";
        					$sql="insert into sklad_topology_levels set name=?s,type=?i,len=?i,first=?s,delimiter=?s,topology_id=?i,level=?i";
                  $db->query($sql,$l_val['name'],(int)$type,(int)$t_length,$t_first,$delimiter,$topology_id,$l_key);
                  //echo " db->query($sql,".$l_val['name'].",$type,0,$delimiter,$topology_id,$l_key)";
        				}
        			}
            }
            $err=1;
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: if (isset($request->topology_id) && (int)$request->topology_id>0){
                          		$status="ok"; $msg="Данные успешно изменены";
                      		}
                      		else {
                          	    $status="ok"; $msg="Новая топология успешно добавлена, не забудьте привязать топологию к складу";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"err"=>"");
        }


	public static function get_sklad_topology($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		    //return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql="select * from sklad_topology where id=?i and main_company_id=?i";
	    $res=$db->getRow($sql,(int)$request->topology_id,$_SESSION['main_company']);
	    if ($res){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['topology_id']=(int)$request->topology_id;
    		//$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
    		$ret['topology_name']=$res['name'];
        $ret['topology_levels']=$db->getAll("select * from sklad_topology_levels where topology_id=?i",(int)$request->topology_id);
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

  public static function get_sklad_topologys($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		    //return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql="select * from sklad_topology where main_company_id=?i";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if ($res){
    		$ret['status']="ok";
    		$ret['err']="";
    		//$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
			foreach($res as $st_key=>$st_val){
				$ret['topologys'][$st_key]=$st_val;
				$ret['topologys'][$st_key]['topology_levels']=$db->getAll("select * from sklad_topology_levels where topology_id=?i",(int)$st_val['id']);
			}
    		$ret['msg']="";
		}
		else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['msg']="";
			$ret['topologys']=array();
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}


}
?>
