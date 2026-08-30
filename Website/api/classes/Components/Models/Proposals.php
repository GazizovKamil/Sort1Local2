<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Proposal;
use Sort1API\Components\Notify;
use Sort1API\Components\UploadHandler;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class Proposals extends Model {

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

    public static function save_proposal($request) {
        $db = DB::getInstance();
        if(!isset($request->proposal_id) && (int)$request->proposal_id<1){
            $proposal=new Proposal();
        }
        else 
            $proposal=new Proposal((int)$request->proposal_id);
        
        if(!empty($request->theme)) $proposal->theme=htmlentities($request->theme,ENT_QUOTES);
        if(!empty($request->descr)) $proposal->descr=htmlentities($request->descr,ENT_QUOTES);
        else {
            return self::_error_arr("Пустое сообщение, заполните поле с описанием проблемы");
        }
        //if(!empty($request->status)) $proposal->status=$request->status;
        preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
        $proposal->site=$origin[1];
        $save=$proposal->Save();
        switch($save){
            case 0: return array("status"=>"err","err"=>"Не удалось сохранить данные"); break;
            case 1:
                if(is_array($request->proposal_loaded_files)) $db->query("update proposal_files set proposal_id=?i where id in (?b)",$proposal->id,$request->proposal_loaded_files); 
                //else $db->query("update proposal_files set proposal_id=?i where id in (?a)",$proposal->id,$request->proposal_loaded_files);
                $user_data=$db->getRow("select * from users where id=?i",$_SESSION['user_id']);
                $send_text="Поступило новое предложение от пользователя:".$user_data['name']." ".$user_data['middlename']." ".$user_data['lastname']."\n<br>";
                $send_text.="Email: ".$user_data['email']."<br>\n";
                $send_text.="Тел: ".$user_data['mphone']."<br>\n";
                $send_text.="Кратко: ".$proposal->theme."<br>\n";
                $send_text.="Полно: ".$proposal->descr."<br>\n";
                Notify::mail("Новое предложение : ".$proposal->theme,$send_text,"info@sort1.ru");
                return array("status"=>"ok","msg"=>""); break;
        }
    }
    
    public static function get_proposals($request){
        $db = DB::getInstance();
        if($_SESSION['user_id']==66){
            $sql="select * from proposals where deleted=0 order by create_date desc ";
            $res=$db->getAll($sql);
            $users=$db->getInd("id","select id,name,middlename,lastname from users where id in (select user_id from proposals where deleted=0)");
        }
        else {
            $sql="select * from proposals where user_id=?i and deleted=0";
            $res=$db->getAll($sql,$_SESSION['user_id']);
            $users=$db->getInd("id","select id,name,middlename,lastname from users where id=?i order by create_date desc",$_SESSION['user_id']);
        }
        
        if($res){
            return array(
                "status"=>"ok",
                "msg"=>"",
                "proposals"=>$res,
                "users"=>$users
            );
        }
        else 
            return array("status"=>"ok","msg"=>"","proposals"=>array());
    }

    public static function get_proposal($request){
        if(!isset($request->proposal_id) || (int)$request->proposal_id<1){
            return array("status"=>"err","err"=>"Не указан номер заявки");
        }
        $db = DB::getInstance();
        if($_SESSION['user_id']==66){
            $sql="select * from proposals where id=?i";
            $res=$db->getRow($sql,(int)$request->proposal_id);
        }
        else {
            $sql="select * from proposals where user_id=?i and id=?i";
            $res=$db->getRow($sql,$_SESSION['user_id'],(int)$request->proposal_id);
        }
        if($res){
            $comments=$db->getAll("select bc.*,u.name,u.middlename,u.lastname from proposal_comments bc left join users u on(u.id=bc.user_id) where proposal_id=?i",(int)$request->proposal_id);
            $files=$db->getAll("select * from proposal_files where proposal_id=?i",(int)$request->proposal_id);
            return array("status"=>"ok","msg"=>"","proposal"=>$res,"comments"=>$comments,"files"=>$files);
        }
        else 
            return self::_error_arr("Нет данных");
    }

    public static function save_proposal_comment($request) {
        $db = DB::getInstance();
        if(!isset($request->proposal_id) && (int)$request->proposal_id<1){
            return self::_error_arr("Нет данных");
        }
        else 
            $proposal=new proposal((int)$request->proposal_id);
        if(!empty(trim($request->comment))) $comment=htmlentities(trim($request->comment),ENT_QUOTES);
        else return self::_error_arr("Не могу сохранить пустой комментарий");
        //if(!empty($request->descr)) $proposal->descr=htmlentities($request->descr,ENT_QUOTES);
        //if(!empty($request->status)) $proposal->status=$request->status;
        $save=$db->query("insert into proposal_comments (proposal_id,user_id,comment,create_date) values(?i,?i,?s,?s)",(int)$request->proposal_id,$_SESSION['user_id'],trim($request->comment),date("Y-m-d H:i:s"));
        if($save) {
            $user_data=$db->getRow("select * from users where id=?i",$_SESSION['user_id']);
            $send_text="Поступило новое сообщение на предложение от пользователя:".$user_data['name']." ".$user_data['middlename']." ".$user_data['lastname']."\n<br>";
            $send_text.="Email: ".$user_data['email']."<br>\n";
            $send_text.="Тел: ".$user_data['mphone']."<br>\n";
            $send_text.="Кратко: ".$proposal->theme."<br>\n";
            $send_text.="Полно: ".$proposal->descr."<br>\n";
            Notify::mail("Новое сообщение на предложение : ".$proposal->theme,$send_text,"info@sort1.ru");
            return array("status"=>"ok","msg"=>"");
        }
        else return array("status"=>"err","err"=>"Не удалось сохранить данные");
    }

    public static function upload_proposal_file(){
        $db=DB::getInstance();
        $upload_handler = new UploadHandler(array("upload_dir"=>dirname($_SERVER['SCRIPT_FILENAME']).'/support_files/'));
        $ret_files=array();
        $i=0;
        //echo "upload_handler: ".print_r($upload_handler,true)."\n";
	    foreach($upload_handler->response['files'] as $file_key=>$file_val){
                $proposal_id=(int)$file_val->proposal_id;
                $proposal_comment_id=(int)$file_val->proposal_comment_id;
				//switch($file_val->base_type){
				//    case "document": $base_type=3; break;
				//    case "sklad":$base_type=1; break;
				//    case "price_list":$base_type=2; break;
				//}
				$file_name=$file_val->realname;
                $local_filename=$file_val->name;
                $res=$db->query("insert into proposal_files (proposal_id,file_name,local_filename,proposal_comment_id,create_date) values (?i,?s,?s,?i,?s)",
                                $proposal_id,$file_name,$local_filename,$proposal_comment_id,date("Y-m-d H:i:s"));
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