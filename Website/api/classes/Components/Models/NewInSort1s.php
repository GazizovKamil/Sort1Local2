<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\NewInSort1;
use Sort1API\Components\Config;
use Sort1API\Components\Notify;
use Sort1API\Components\UploadHandler;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class NewInSort1s extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

		public static function save_new_in_sort1($request){
            $db = DB::getInstance();
			if(!empty($request->new_in_sort1_id))$new_in_sort1=New NewInSort1((int)$request->new_in_sort1_id);
            else $new_in_sort1=New NewInSort1();
            if(!empty($request->news_header)) $new_in_sort1->news_header=$request->news_header;
            if(!empty($request->news_text)) $new_in_sort1->news_text=$request->news_text;
            $res=$new_in_sort1->save();
            if($res){
                if(is_array($request->new_in_sort1_loaded_files)) $db->query("update new_in_sort1_files set new_in_sort1_id=?i where id in (?a)",$new_in_sort1->id,$request->new_in_sort1_loaded_files); 
                return array("status"=>"ok","msg"=>"");
            }
            else {
                return array("status"=>"err","err"=>"err","msg"=>"Не удалось сохранить новость");
            }
		}

	public static function get_new_in_sort1($request) {
	    $db = DB::getInstance();
	    $sql="select nis.*,nr.new_in_sort1_id as `read` from new_in_sort1 nis 
		left join news_read nr on (nr.new_in_sort1_id=nis.id and nr.user_id=?i) 
		where nis.id=?i and nis.deleted=0";
	    $res=$db->getRow($sql,$_SESSION['user_id'],(int)$request->new_in_sort1_id);
        if($res){
            $comments=$db->getAll("select bc.*,u.name,u.middlename,u.lastname from new_in_sort1_comments bc left join users u on(u.id=bc.user_id) where new_in_sort1_id=?i",(int)$request->new_in_sort1_id);
            $files=$db->getAll("select * from new_in_sort1_files where new_in_sort1_id=?i",(int)$request->new_in_sort1_id);
			$db->query("insert ignore into news_read values(?i,?i,?i,?s)",(int)$request->new_in_sort1_id,$_SESSION['user_id'],0,date("Y-m-d H:i:s"));
            return array("status"=>"ok","msg"=>"","new_in_sort1"=>$res,"comments"=>$comments,"files"=>$files);
        }
        else 
            return self::_error_arr("Нет данных");
	}

	public static function get_new_in_sort1s($request) {
	    $db = DB::getInstance();
	    $sql_count="select count(id) from new_in_sort1 where deleted=0";
	    if (!empty($request->search) && $request->search!="undefined") $sql_count.=" and (news_text like ?s or news_header like ?s)";
	    if (!empty($request->search) && $request->search!="undefined") $news_count=$db->getOne($sql_count,'%'.trim($request->search).'%','%'.trim($request->search).'%');
	    else $news_count=$db->getOne($sql_count);
	    $sql="select nis.*,nr.new_in_sort1_id as `read` from new_in_sort1 nis 
		left join news_read nr on (nr.new_in_sort1_id=nis.id and nr.user_id=".(int)$_SESSION['user_id'].") 
		where nis.deleted=0 ";
	    if (!empty($request->search) && $request->search!="undefined") $sql.=" and (nis.news_text like ?s or nis.news_header like ?s)";
	    $sql.=" order by nis.create_date desc";
	    if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
	    $pages=ceil($news_count/$page_size);
	    if(isset($request->page)) {
			$sql.=" limit ".$page_size*($request->page-1).",".$page_size;
	    }
	    else 
			$sql.=" limit 0,".$page_size;
		//echo $sql;
	    if (!empty($request->search) && $request->search!="undefined") {
			$res=$db->getAll($sql,'%'.trim($request->search).'%','%'.trim($request->search).'%');
			$ret['search']=$request->search;
	    }
	    else 
			$res=$db->getAll($sql);
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['new_in_sort1']=$res;
			$ret['new_in_sort1_pages']=$pages;
			$ret['new_in_sort1_count']=(int)$details_count;
			if (isset($request->page)) $ret['selected_page']=$request->page;
			$ret['msg']="";
	    }
	    else {
			$ret['status']="ok";
			$ret['msg']="";
			$ret['err']="";
			$ret['new_in_sort1']=[];
			$ret['new_in_sort1_pages']=1;
			$ret['details_count']=0;
	    }
        if($_SESSION['user_id']==66) $ret['edit']=1;
        else $ret['edit']=0;
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_new_in_sort1($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
			//return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->new_in_sort1_id)) {$new_in_sort1_id=(int)$request->new_in_sort1_id;}
		else {
			return self::_error_arr("Не указан номер");
		}
		$res2=$db->query("update new_in_sort1 set deleted=1 where id=?i",$new_in_sort1_id);
		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
		if ($res2){
		    $ret['status']="ok";
		    $ret['msg']="Новость успешно удалена";
		}
		else {
		    $ret['status']="err";
		    $ret['err']="не удалось удалить новость";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

    public static function save_new_in_sort1_comment($request) {
        $db = DB::getInstance();
        if(!isset($request->new_in_sort1_id) && (int)$request->new_in_sort1_id<1){
            return self::_error_arr("Нет данных");
        }
        else 
            $new_in_sort1=new NewInSort1((int)$request->new_in_sort1_id);
        if(!empty(trim($request->comment))) $comment=htmlentities(trim($request->comment),ENT_QUOTES);
        else return self::_error_arr("Не могу сохранить пустой комментарий");
        //if(!empty($request->descr)) $new_in_sort1->descr=htmlentities($request->descr,ENT_QUOTES);
        //if(!empty($request->status)) $new_in_sort1->status=$request->status;
        if ($_SESSION['user_id']==66){
            $new_in_sort1->admin_read = 1;
            $new_in_sort1->user_read = 0;
            $savenew_in_sort1 = $new_in_sort1->Save();
        }
        else{
            $new_in_sort1->admin_read = 0;
            $new_in_sort1->user_read = 1;
            $savenew_in_sort1 = $new_in_sort1->Save();
        }
        $save=$db->query("insert into new_in_sort1_comments (new_in_sort1_id,user_id,comment,create_date) values(?i,?i,?s,?s)",(int)$request->new_in_sort1_id,$_SESSION['user_id'],trim($request->comment),date("Y-m-d H:i:s"));
        if($save) {
            $user_data=$db->getRow("select * from users where id=?i",$_SESSION['user_id']);
            $send_text="Поступил новый клмментарий на новость, от пользователя:".$user_data['name']." ".$user_data['middlename']." ".$user_data['lastname']."\n";
            $send_text.="Email: ".$user_data['email']."\n";
            $send_text.="Тел: ".$user_data['mphone']."\n";
            $send_text.="Заголовок новости: ".$new_in_sort1->news_header."\n";
            $send_text.="Комментарий: ".$comment."\n";
            Notify::mail("Новое сообщение в заявке : ".$new_in_sort1->theme,$send_text,"info@sort1.ru");
            return array("status"=>"ok","msg"=>"");
        }
        else return array("status"=>"err","err"=>"Не удалось сохранить данные");
    }

    public static function upload_new_in_sort1_file(){
        $db=DB::getInstance();
        $upload_handler = new UploadHandler(array("upload_dir"=>dirname($_SERVER['SCRIPT_FILENAME']).'/support_files/'));
        $ret_files=array();
        $i=0;
        //echo "upload_handler: ".print_r($upload_handler,true)."\n";
	    foreach($upload_handler->response['files'] as $file_key=>$file_val){
                $new_in_sort1_id=(int)$file_val->new_in_sort1_id;
                $new_in_sort1_comment_id=(int)$file_val->new_in_sort1_comment_id;
				//switch($file_val->base_type){
				//    case "document": $base_type=3; break;
				//    case "sklad":$base_type=1; break;
				//    case "price_list":$base_type=2; break;
				//}
				$file_name=$file_val->realname;
                $local_filename=$file_val->name;
                $res=$db->query("insert into new_in_sort1_files (new_in_sort1_id,file_name,local_filename,new_in_sort1_comment_id,create_date) values (?i,?s,?s,?i,?s)",
                                $new_in_sort1_id,$file_name,$local_filename,$new_in_sort1_comment_id,date("Y-m-d H:i:s"));
                $ret_files[$i]['id']=$db->insertId();
                $ret_files[$i]['name']=$file_name;
                //$ret_files[$i]['localname']=$local_filename;
				//$excel_to_base = new ExcelToBase($base_id,$base_type,$file_name,$local_file_name);
                //return $excel_to_base->GetFirstDetails();
                $i++;
        }
        return array("status"=>"ok","msg"=>"","loaded_files"=>$ret_files);
	}

	public static function get_new_in_sort1s_unread_count($request) {
	    $db = DB::getInstance();
	    $sql_count="select count(unread_news.id) from (select nis.*,nr.new_in_sort1_id as `read` from new_in_sort1 nis 
		left join news_read nr on (nr.new_in_sort1_id=nis.id and nr.user_id=".(int)$_SESSION['user_id'].") 
		where nis.deleted=0) as unread_news where unread_news.read is null";
		$unread_count=$db->getOne($sql_count);
		$ret['status']="ok";
		$ret['err']="";
		$ret['unread_count']=$unread_count;
		$ret['msg']="";

		if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

}
?>