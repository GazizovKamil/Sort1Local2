<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Bug;
use Sort1API\Components\Notify;
use Sort1API\Components\UploadHandler;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class Bugs extends Model {

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

    public static function save_bug($request) {
        $db = DB::getInstance();
        if(!isset($request->bug_id) && (int)$request->bug_id<1){
            $bug=new Bug();
        }
        else 
            $bug=new Bug((int)$request->bug_id);
        
        if(!empty($request->theme)) $bug->theme=htmlentities($request->theme,ENT_QUOTES);
        if($_SESSION['user_id']==66){
            if(!empty($request->faq) && $request->faq=="on") $bug->faq=1;
            else $bug->faq=0;
        }
        if(!empty($request->status) && $request->status>0) $bug->status=$request->status;
        if(!empty($request->descr)) $bug->descr=htmlentities($request->descr,ENT_QUOTES);
        else {
            return self::_error_arr("Пустое сообщение, заполните поле с описанием проблемы");
        }
        //if(!empty($request->status)) $bug->status=$request->status;
        preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
        //if($origin[1]=="sort1.pro") return self::register_pro_user($request);
        $bug->site=$origin[1];
        $save=$bug->Save();
        switch($save){
            case 0: return array("status"=>"err","err"=>"Не удалось сохранить данные"); break;
            case 1:
                if(is_array($request->bug_loaded_files)) $db->query("update bug_files set bug_id=?i where id in (?a)",$bug->id,$request->bug_loaded_files); 
                //else $db->query("update bug_files set bug_id=?i where id in (?a)",$bug->id,$request->bug_loaded_files);
                $user_data=$db->getRow("select * from users where id=?i",$_SESSION['user_id']);
                $send_text="Поступила новая заявка от пользователя:".$user_data['name']." ".$user_data['middlename']." ".$user_data['lastname']."\n";
                $send_text.="Email: ".$user_data['email']."\n";
                $send_text.="Тел: ".$user_data['mphone']."\n";
                $send_text.="Тема: ".$bug->theme."\n";
                $send_text.="Заявка: ".$bug->descr."\n";
                Notify::mail("Новая заявка : ".$bug->theme,$send_text,"info@sort1.ru");
                return array("status"=>"ok","msg"=>""); break;
        }
    }
    
    public static function get_bugs($request){
        $db = DB::getInstance();
        if($_SESSION['user_id']==66){
            // $sql="select * from bugs where deleted=0 order by create_date desc ";
            $sql = "SELECT id, user_id, theme, descr, company_id, status, img_file, create_date, update_date, deleted, site, admin_read as 'read' FROM shop.bugs where deleted=0 order by create_date desc ";
            $res=$db->getAll($sql);
            $users=$db->getInd("id","select id,name,middlename,lastname,email,phone,mphone from users where id in (select user_id from bugs where deleted=0)");
            // print_r($res);
        }
        else {
            // $sql="select * from bugs where user_id=?i and deleted=0 order by create_date desc";
            $sql="select id, user_id, theme, descr, company_id, status, img_file, create_date, update_date, deleted, site, user_read as 'read' from shop.bugs where user_id=?i and deleted=0 order by create_date desc";
            $res=$db->getAll($sql,$_SESSION['user_id']);
            $users=$db->getInd("id","select id,name,middlename,lastname from users where id=?i order by create_date desc",$_SESSION['user_id']);
        }
        
        if($res){
            return array(
                "status"=>"ok",
                "msg"=>"",
                "bugs"=>$res,
                "users"=>$users
            );
        }
        else 
            return array("status"=>"ok","msg"=>"","bugs"=>array());
    }

    public static function get_faqs($request){
        $db = DB::getInstance();
        // $sql="select * from bugs where deleted=0 order by create_date desc ";
        $sql = "SELECT id, user_id, theme, descr, company_id, status, img_file, create_date, update_date, deleted, site, admin_read as 'read' FROM shop.bugs where deleted=0 and faq=1 order by create_date desc ";
        $res=$db->getAll($sql);
        // print_r($res);
        
        if($res){
            return array(
                "status"=>"ok",
                "msg"=>"",
                "faqs"=>$res,
            );
        }
        else 
            return array("status"=>"ok","msg"=>"","bugs"=>array());
    }

    public static function get_bug($request){
        if(!isset($request->bug_id) || (int)$request->bug_id<1){
            return array("status"=>"err","err"=>"Не указан номер заявки");
        }
        $db = DB::getInstance();
        if($_SESSION['user_id']==66){
            $sql="select * from bugs where id=?i";
            $bug = new Bug((int)$request->bug_id);
            $bug->admin_read = 1;
            $save = $bug->Save();
            $res=$db->getRow($sql,(int)$request->bug_id);
        } 
        else {
            $sql="select * from bugs where user_id=?i and id=?i";
            $bug = new Bug((int)$request->bug_id);
            $bug->user_read = 1;
            $save = $bug->Save();
            $res=$db->getRow($sql,$_SESSION['user_id'],(int)$request->bug_id);
        }
        if($res){
            $comments=$db->getAll("select bc.*,u.name,u.middlename,u.lastname from bug_comments bc left join users u on(u.id=bc.user_id) where bug_id=?i",(int)$request->bug_id);
            $files=$db->getAll("select * from bug_files where bug_id=?i",(int)$request->bug_id);
            return array("status"=>"ok","msg"=>"","bug"=>$res,"comments"=>$comments,"files"=>$files);
        }
        else 
            return self::_error_arr("Нет данных");
    }

    public static function get_faq($request){
        if(!isset($request->faq_id) || (int)$request->faq_id<1){
            return array("status"=>"err","err"=>"Не указан номер заявки");
        }
        $db = DB::getInstance();
            $sql="select * from bugs where id=?i";
            $res=$db->getRow($sql,(int)$request->faq_id);
        if($res){
            $comments=$db->getAll("select bc.*,u.name,u.middlename,u.lastname from bug_comments bc left join users u on(u.id=bc.user_id) where bug_id=?i",(int)$request->faq_id);
            $files=$db->getAll("select * from bug_files where bug_id=?i",(int)$request->faq_id);
            return array("status"=>"ok","msg"=>"","faq"=>$res,"comments"=>$comments,"files"=>$files);
        }
        else 
            return self::_error_arr("Нет данных");
    }

    public static function delete_bug($request){
        if(!isset($request->bug_id) || (int)$request->bug_id<1){
            return array("status"=>"err","err"=>"Не указан номер заявки");
        }
        $db = DB::getInstance();
        $db->query("update bugs set deleted=1 where id=?i",(int)$request->bug_id);
        if($db->affectedRows()>0){
            return array("status"=>"ok","msg"=>"");
        }
        else 
            return self::_error_arr("Не удалось удалить заявку");
    }

    public static function save_bug_comment($request) {
        $db = DB::getInstance();
        if(!isset($request->bug_id) && (int)$request->bug_id<1){
            return self::_error_arr("Нет данных");
        }
        else 
            $bug=new Bug((int)$request->bug_id);
        if(!empty(trim($request->comment))) $comment=htmlentities(trim($request->comment),ENT_QUOTES);
        else return self::_error_arr("Не могу сохранить пустой комментарий");
        //if(!empty($request->descr)) $bug->descr=htmlentities($request->descr,ENT_QUOTES);
        //if(!empty($request->status)) $bug->status=$request->status;
        if ($_SESSION['user_id']==66){
            $bug->admin_read = 1;
            $bug->user_read = 0;
            $saveBug = $bug->Save();
        }
        else{
            $bug->admin_read = 0;
            $bug->user_read = 1;
            $saveBug = $bug->Save();
        }
        $save=$db->query("insert into bug_comments (bug_id,user_id,comment,create_date) values(?i,?i,?s,?s)",(int)$request->bug_id,$_SESSION['user_id'],trim($request->comment),date("Y-m-d H:i:s"));
        if($save) {
            $user_data=$db->getRow("select * from users where id=?i",$_SESSION['user_id']);
            $send_text="Поступило новое сообщение на заявку от пользователя:".$user_data['name']." ".$user_data['middlename']." ".$user_data['lastname']."\n";
            $send_text.="Email: ".$user_data['email']."\n";
            $send_text.="Тел: ".$user_data['mphone']."\n";
            $send_text.="Тема: ".$bug->theme."\n";
            $send_text.="Заявка: ".$bug->descr."\n";
            Notify::mail("Новое сообщение в заявке : ".$bug->theme,$send_text,"info@sort1.ru");
            return array("status"=>"ok","msg"=>"");
        }
        else return array("status"=>"err","err"=>"Не удалось сохранить данные");
    }

    public static function upload_bug_file(){
        $db=DB::getInstance();
        $upload_handler = new UploadHandler(array("upload_dir"=>dirname($_SERVER['SCRIPT_FILENAME']).'/support_files/'));
        $ret_files=array();
        $i=0;
        //echo "upload_handler: ".print_r($upload_handler,true)."\n";
	    foreach($upload_handler->response['files'] as $file_key=>$file_val){
                $bug_id=(int)$file_val->bug_id;
                $bug_comment_id=(int)$file_val->bug_comment_id;
				//switch($file_val->base_type){
				//    case "document": $base_type=3; break;
				//    case "sklad":$base_type=1; break;
				//    case "price_list":$base_type=2; break;
				//}
				$file_name=$file_val->realname;
                $local_filename=$file_val->name;
                $res=$db->query("insert into bug_files (bug_id,file_name,local_filename,bug_comment_id,create_date) values (?i,?s,?s,?i,?s)",
                                $bug_id,$file_name,$local_filename,$bug_comment_id,date("Y-m-d H:i:s"));
                $ret_files[$i]['id']=$db->insertId();
                $ret_files[$i]['name']=$file_name;
                //$ret_files[$i]['localname']=$local_filename;
				//$excel_to_base = new ExcelToBase($base_id,$base_type,$file_name,$local_file_name);
                //return $excel_to_base->GetFirstDetails();
                $i++;
        }
        return array("status"=>"ok","msg"=>"","loaded_files"=>$ret_files);
	}

}
?>