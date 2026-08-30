<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;


class Catalogs extends Model{
    public static function get_catalogs($request){
        $db = DB::getInstance();
        $catalogs = $db->getAll("SELECT sc.id, sc.name_catalog FROM site_catalogs sc");
        $ret['status']="ok";
        $ret['err']="";
        $ret['catalogs']=$catalogs;
        $ret['msg']="";
        return $ret;
    }

    public static function get_config_catalog($request){
        $db = DB::getInstance();
        $config = $db->getRow("SELECT sc.catalog_config  FROM site_catalogs sc WHERE sc.id = ?i",$request->id);
        $ret['status']="ok";
        $ret['err']="";
        $ret['config']=json_decode($config['catalog_config'],true);
        $ret['msg']="";
        return $ret;
    }
}




?>