<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Dogovor;
use Sort1API\Components\Zakaz;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Dogovors extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_dogovor($request) {
            $db = DB::getInstance();
      	    if (isset($request->dogovor_id)) $dogovor_id=(int)$request->dogovor_id;
      	    if (isset($dogovor_id) && $dogovor_id>0) {
          		$dogovor=new Dogovor($dogovor_id);
          		//$dogovor->update_date=date("Y-m-d H:i:s");
          		//echo print_r($dogovor,true);
          	}
          	else {
          	   $dogovor=new Dogovor();
          	}
          	if (isset($request->company_id) && (int)$request->company_id>0) {
            	$companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
            	if ($companys && in_array($request->company_id,$companys))
            	    $dogovor->company_id=(int)$request->company_id;
            	else {
            	    return self::_error_arr("Нельзя добавить документ к чужой компании");
          	  }
      	    }
      	    else {
      		      return self::_error_arr("Не указана компания");
      	    }
      	    if (isset($request->num)) $dogovor->num=$request->num;
      	    if (isset($request->start_date)) $dogovor->start_date=$request->start_date;
			if($dogovor->start_date=='') $dogovor->start_date='0000-00-00';
      	    if (isset($request->stop_date)) $dogovor->stop_date=$request->stop_date;
			if($dogovor->stop_date=='') $dogovor->stop_date='0000-00-00';
      	    if (!empty($request->price_type_id) || $request->price_type_id==0 ) $dogovor->price_type=(int)$request->price_type_id;
			if (isset($request->is_cashback) && $request->is_cashback=="on") $dogovor->is_cashback_by_default=1;
			else $dogovor->is_cashback_by_default=0;
      	    if (isset($request->payment_type)) $dogovor->payment_type=(int)$request->payment_type;
      	    if (isset($request->dogovor_type)) $dogovor->dogovor_type=(int)$request->dogovor_type;
      	    if (!empty($request->credit_limit) || $request->credit_limit==0) $dogovor->credit_limit=(float)$request->credit_limit;
			if (!empty($request->card_number) || $request->card_number==0) $dogovor->card_number=(int)$request->card_number;
      	    if (!empty($request->credit_limit_time) || $request->credit_limit_time==0 ) $dogovor->credit_limit_time=(int)$request->credit_limit_time;
      	    $dogovor->main_company=$_SESSION['main_company'];
      	    $dogovor->user_id=$_SESSION['user_id'];
      	    if (isset($request->descr)) $dogovor->descr=$request->descr;
      	    $err=$dogovor->save();
			if($dogovor->id>0 && isset($request->zakaz_id) && (int)$request->zakaz_id>0){
				$zakaz=new Zakaz((int)$request->zakaz_id);
				$zakaz->dogovor_id=$dogovor->id;
				$zakaz->Save();
			}
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1:  $status="ok"; $msg=""; break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }


	public static function get_dogovors($request) {
	    $db = DB::getInstance();
		$sql="select d.id,d.num,d.start_date,d.stop_date,d.company_id,d.payment_type,d.dogovor_type,cbt.descr as dogovor_type_name,
			d.price_type,c.name as company_name,d.create_date,d.update_date,p.proc,p.descr,d.credit_limit,d.card_number,d.is_cashback_by_default
		    from dogovor d
		    left join company c on (d.company_id=c.id)
		    left join dict_price_type p on (p.id=d.price_type)
		    left join company_business_types cbt on (cbt.id=d.dogovor_type)
		    where d.main_company=?i and d.deleted=0";
	    $res=$db->getAll($sql,$_SESSION['company_id']);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['dogovors']=$res;
    		$ret['msg']="";
    	    }
    	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['dogovors']=array();
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_dogovor_types(){
	    $db = DB::getInstance();
	    $sql="select * from dogovor_types";
	    if ($res=$db->getAll($sql)){
    		foreach($res as $key => $val){
    		    $types[$val['id']]=$val['descr'];
    		}
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['dogovor_types']=$res;
    	    }
    	    else {
    		$ret['status']="err";
    		$ret['err']="Невозможно получить данные";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_dogovor($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']>2) {
		    //  return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->dogovor_id)) {$dogovor_id=(int)$request->dogovor_id;}
	    if (isset($dogovor_id) && $dogovor_id>0){
    		$res2=$db->query("update dogovor set deleted=1 where id=?i and company_id in (select company_id from user_companys where main_company_id=?i)",$dogovor_id,$_SESSION['main_company']);
    		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
    		    $ret['status']="ok";
    		    $ret['msg']="dogovor успешно удален";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось удалить документ";
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_dogovor($request) {
	    $fields="";
	    $db = DB::getInstance();
	    //if ((int)$_SESSION['roles']!=1) {
	//	return self::_error_arr("У Вас нет прав для удаления");
	//    }
	    if (isset($request->dogovor_id)) {$dogovor_id=(int)$request->dogovor_id;}
	    if (isset($dogovor_id) && $dogovor_id>0){
    		$res2=$db->getRow("select * from dogovor where id=?i and company_id in (select company_id from user_companys where main_company_id=?i)",$dogovor_id,$_SESSION['main_company']);
    		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
    		    $ret['status']="ok";
    		    $ret['msg']="";
    		    $ret['dogovor']=$res2;
    		    $ret['dogovor']['price_type_name']=$db->getOne("select descr from dict_price_type where id=?i",$res2['price_type']);
    		    $ret['company_name']=str_replace(array("\"","'"),"",$db->getOne("select name from company where id=?i",$res2['company_id']));
    		    $ret['payment_type']="";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось получить документ";
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_company_dogovors($request) {
	    $fields="";
	    $db = DB::getInstance();
	    //if ((int)$_SESSION['roles']!=1) {
	//	return self::_error_arr("У Вас нет прав для удаления");
	//    }
	    if (isset($request->company_id)) {$company_id=(int)$request->company_id;}
	    if (isset($company_id) && $company_id>0){
    		//$res2=$db->getAll("select * from dogovor where company_id=?i and company_id in (select company_id from user_companys where user_id=?i)",$company_id,$_SESSION['user_id']);
			$sql="select d.id,d.num,
				DATE_FORMAT(d.start_date,'%d.%m.%Y') as start_date,DATE_FORMAT(d.stop_date,'%d.%m.%Y') as stop_date,d.company_id,d.payment_type,d.dogovor_type,cbt.descr as dogovor_type_name,
				d.price_type,c.name as company_name,d.create_date,d.update_date,p.proc,p.descr as price_type_descr,d.credit_limit,
				d.credit_limit_time,d.descr,d.card_number,d.is_cashback_by_default
    		    from dogovor d
    		    left join company c on (d.company_id=c.id)
    		    left join dict_price_type p on (p.id=d.price_type)
    		    left join company_business_types cbt on (cbt.id=d.dogovor_type)
    		    where d.company_id=?i and d.company_id in (select company_id from user_companys where main_company_id=?i) and d.main_company=?i and d.deleted=0";
    		$res2=$db->getAll($sql,$company_id,$_SESSION['main_company'],$_SESSION['main_company']);
    		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
    		    $ret['status']="ok";
    		    $ret['msg']="";
    		    $ret['dogovors']=$res2;
    		}
    		else {
    		    $ret['status']="ok";
    		    $ret['msg']="";
    		    $ret['dogovors']=array();
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных, необходимо сначала завести компанию";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

}



?>
