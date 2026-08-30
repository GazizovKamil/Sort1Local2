<?php
namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Request;
use Sort1API\Components\Config;
use Sort1API\Components\Functions;
use Sort1API\Components\FavoriteDetail;


class FavoriteDetails extends Model {
	
	public static function get_favorite_details($request) {

		$db = DB::getInstance();
        preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
        $sql="select id,company_id,shop_verify_phone,shop_sms_apikey from company_sites where site_name=?s";
        $site_data=$db->getRow($sql,str_replace("www.","",$origin[1]));
        $main_company_id=$site_data['company_id'];
        $f_details=array();
        if(isset($_SESSION['user_id']) && $_SESSION['user_id']>0){
            $f_details=$db->getCol("select detail_id from favorite_details where user_id=?i and main_company_id=?i and site_id=?i",$_SESSION['user_id'],$main_company_id,$site_data['id']);
        }
        else {
            $f_details=$db->getCol("select detail_id from favorite_details where session_id=?s and main_company_id=?i and site_id=?i",session_id(),$main_company_id,$site_data['id']);
        }
        return array("status"=>"ok","err"=>"","msg"=>"","favorite_details"=>$f_details);
    }

    public static function add_favorite_detail($request) {
        if(isset($request->detail_id) && (int)$request->detail_id!=0){
            $fav_detail=new FavoriteDetail((int)$request->detail_id);
            $fav_detail->detail_id=(int)$request->detail_id;
            $fav_detail->Save();
            return array("status"=>"ok","err"=>"","msg"=>"");
        }
        else {
            return array("status"=>"err","err"=>"Не указан код детали");
        }
    }

    public static function delete_favorite_detail($request) {
        if(isset($request->detail_id) && (int)$request->detail_id!=0){
            $fav_detail=new FavoriteDetail((int)$request->detail_id);
            $fav_detail->delete();
            return array("status"=>"ok","err"=>"","msg"=>"");
        }
        else {
            return array("status"=>"err","err"=>"Не указан код детали");
        }
    }
}