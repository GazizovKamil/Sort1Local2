<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\DetailMark;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class DetailMarks extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        private static function is_your($detail_mark_id){
            $db = DB::getInstance();
            $DetailMark_comp_id=$db->getOne("select main_company from document where 
                id = (select document_id from document_details where id =(select in_document_detail_id from detail_mark where id=?i))",$detail_mark_id);
            if(!empty($DetailMark_comp_id) && $DetailMark_comp_id==$_SESSION['main_company']){
                return 1;
            }
            else return 0;
        }

        public static function save_detail_mark($request) {
            $db = DB::getInstance();
            if (isset($request->detail_mark_id)) $detail_mark_id=(int)$request->detail_mark_id;
            if (isset($request->document_id)) $document_id=(int)$request->document_id;
            if (isset($request->document_details_id) && (int)$request->document_details_id>0) $document_details_id=(int)$request->document_details_id;
            else return self::_error_arr("Не указан номер детали в документе");
      	    if (isset($detail_mark_id) && $detail_mark_id>0) {
                if(self::is_your($detail_mark_id)){
          		    $detail_mark=new DetailMark($detail_mark_id);
                }
                else { 
                    return self::_error_arr("Вы не можете редактировать чужие данные");
                }
          	}
          	else {
          	   $detail_mark=new DetailMark();
          	}
            //$DetailMark_num=$request->detail_mark;
      	    $detail_mark->mark=$request->detail_mark;
            $detail_mark->detail_id=$db->getOne("select detail_id from document_details where id=?i",$document_details_id);
            $document_sklad_id=$db->getOne("select sklad_id from document where id=(select document_id from document_details where id=?i)",$document_details_id);
            $detail_mark->sklad_id=$document_sklad_id;
            switch($request->mark_znak){
                case "+": $detail_mark->in_document_detail_id=$document_details_id;break;
                case "-": $detail_mark->out_document_detail_id=$document_details_id;break;
            }
      	    $err=$detail_mark->save();
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
                case 1:  
                    $status="ok"; $msg=""; 
                    break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }


	public static function get_document_detail_marks($request) {
	    $db = DB::getInstance();
        $in_doc_det=$db->getAll("select * from detail_mark where in_document_detail_id=?i and main_company_id=?i",$request->document_details_id,$_SESSION['main_company']);
        $out_doc_det=$db->getAll("select * from detail_mark where out_document_detail_id=?i and main_company_id=?i",$request->document_details_id,$_SESSION['main_company']);
	    $res=array_merge($in_doc_det,$out_doc_det);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['DetailMarks']=$res;
    		$ret['msg']="";
    	    }
    	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['DetailMarks']=array();
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_detail_mark($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (empty($request->detail_mark_id)) {
            return self::_error_arr("Не указан номер Маркировки");
        }
        if (empty($request->document_details_id)) {
            return self::_error_arr("Не указан номер товара в документе");
        }
        if(self::is_your($request->detail_mark_id)){
            switch($request->mark_znak){
                case "+": $sql="delete from detail_mark where id=?i and in_document_detail_id=?i and main_company_id=?i"; break;
                case "-": $sql="delete from detail_mark where id=?i and out_document_detail_id=?i and main_company_id=?i"; break;
            }
            if(empty($sql)) return self::_error_arr("не указан знак документа");
            $db->query($sql,$request->detail_mark_id,$request->document_details_id,$_SESSION['main_company']);
            if($db->affectedRows()>0){
                $ret['status']="ok";
                $ret['msg']="";
            }
            else {
                $ret['status']="err";
                $ret['err']="Ошибка при удалении маркировки из детали документа";
            }
        }
        else { 
            return self::_error_arr("Вы не можете редактировать чужие данные");
        }
	    
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_detail_mark($request) {
	    $fields="";
        $db = DB::getInstance();
        if (empty($request->detail_mark_id)) {
            return self::_error_arr("Не указан номер ");
        }
        if(self::is_your($request->detail_mark_id)){
            $ret['DetailMark']=$db->getRow("select * from detail_mark where id=?i",$request->detail_mark_id);
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
