<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\GTD;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class GTDs extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        private static function is_your($gtd_id){
            $db = DB::getInstance();
            $gtd_comp_id=$db->getOne("select main_company from document where 
                id = (select document_id from document_details where id =(select document_details_id from gtd_to_doc_det where gtd_id=?i))",$gtd_id);
            if(!empty($gtd_comp_id) && $gtd_comp_id==$_SESSION['main_company']){
                return 1;
            }
            else return 0;
        }

        public static function save_gtd($request) {
            $db = DB::getInstance();
            if (isset($request->gtd_id)) $gtd_id=(int)$request->gtd_id;
            if (isset($request->document_id)) $document_id=(int)$request->document_id;
            if (isset($request->document_details_id) && (int)$request->document_details_id>0) $document_details_id=(int)$request->document_details_id;
            else return self::_error_arr("Не указан номер детали в документе");
      	    if (isset($gtd_id) && $gtd_id>0) {
                if(self::is_your($gtd_id)){
          		    $gtd=new GTD($gtd_id);
                }
                else { 
                    return self::_error_arr("Вы не можете редактировать чужие данные");
                }
          	}
          	else {
          	   $gtd=new GTD();
          	}
            $gtd_num=explode("/",$request->gtd_num);
            if(count((array)$gtd_num)!=4){
                return self::_error_arr("Неправильный формат ГТД");
            }  
      	    $gtd->custom_num=$gtd_num[0];
      	    $gtd->doc_date=$gtd_num[1];
      	    $gtd->num=$gtd_num[2];
      	    $gtd->pos_num=$gtd_num[3];
      	    if (isset($request->country_code)) $gtd->country_code=$request->country_code;
            if (isset($request->country_name)) $gtd->country_name=$request->country_name;
            if(empty($request->country_name))  $gtd->country_name=$db->getOne("select name from oksm_country where code=?s",$request->country_code);
      	    $err=$gtd->save();
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
                case 1:  
                    $res=$db->query("insert ignore into gtd_to_doc_det (gtd_id,document_details_id,create_date) values(?i,?i,?s)",$gtd->id,$document_details_id,date("Y-m-d H:i:s"));
                    $status="ok"; $msg=""; 

                    break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }


	public static function get_document_det_gtds($request) {
	    $db = DB::getInstance();
		$sql="select * from gtd where id in (select gtd_id from gtd_to_doc_det where document_details_id=?i)";
	    $res=$db->getAll($sql,$request->document_details_id);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['gtds']=$res;
    		$ret['msg']="";
    	    }
    	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['gtds']=array();
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_document_det_gtd($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (empty($request->gtd_id)) {
            return self::_error_arr("Не указан номер ГТД");
        }
        if (empty($request->document_details_id)) {
            return self::_error_arr("Не указан номер товара в документе");
        }
        if(self::is_your($request->gtd_id)){
            $sql="delete from gtd_to_doc_det where gtd_id=?i and document_details_id=?i";
            $db->query($sql,$request->gtd_id,$request->document_details_id);
            if($db->affectedRows()>0){
                $ret['status']="ok";
                $ret['msg']="";
            }
            else {
                $ret['status']="err";
                $ret['err']="Ошибка при удалении гтд из детали документа";
            }
        }
        else { 
            return self::_error_arr("Вы не можете редактировать чужие данные");
        }
	    
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_document_det_gtd($request) {
	    $fields="";
        $db = DB::getInstance();
        if (empty($request->gtd_id)) {
            return self::_error_arr("Не указан номер ГТД");
        }
        if(self::is_your($request->gtd_id)){
            $ret['gtd']=$db->getRow("select * from gtd where id=?i",$request->gtd_id);
            $ret['status']="ok";
            $ret['msg']="";
        }
        else {
            return self::_error_arr("Не ваш документ");
        }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}


}



?>
