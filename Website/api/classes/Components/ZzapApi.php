<?php
namespace Sort1API\Components;

class ZzapApi
{

    function add_product(){
                
    }

    static function get_seller_orders($request){
        $db = DB::getInstance();

        $marketplaces_configs_id = (int)$request->marketplaces_configs_id;

        $date_from = !empty($request->date_from) 
        ? date('Y-m-d\T00:00:00.000\Z', strtotime($request->date_from)) 
        : date('Y-m-d\T00:00:00.000\Z', strtotime('-15 days'));
    
        $date_to = !empty($request->date_to) 
        ? date('Y-m-d\T00:00:00.000\Z', strtotime('+1 day', strtotime($request->date_to)))
        : date('Y-m-d\T00:00:00.000\Z', strtotime('+1 day'));
        
        $data = $db->getRow('Select marketplace_config,marketplace_id From marketplaces_configs Where company_id = ?i and id = ?i', (int)$_SESSION['main_company'], $marketplaces_configs_id);
        $marketplace_config = json_decode($data['marketplace_config'],true);

        if(!empty($marketplace_config)){
            $login = $marketplace_config['login'];
            $password = $marketplace_config['password'];
            $api_key = $marketplace_config['api_key'];
        }
        else{ return array("status"=>"err","err"=>"Нет доступа к API"); }

        $stat = $db->getRow("SELECT GROUP_CONCAT(k.market_status_id SEPARATOR '; ') as status from market_zakaz_statuses k Where marketplace_id = 1");
        $codes_track = $stat['status'];
        $row_count=1000;

        $url = 'https://api.zzap.pro/webservice/datasharing.asmx/GetSellerOrdersV3';

        $ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"login=$login&password=$password&api_key=$api_key&row_count=$row_count&call_data=&codes_track=$codes_track&date_from=$date_from&date_to=$date_to");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$result = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($result);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        $array = json_decode($array[0]);

        return (array)$array;
    }

    static function set_order_status($request){
        $db = DB::getInstance();

        $marketplace_config_id = $request->marketplace_config_id;
        $data = $db->getRow('Select marketplace_config From marketplaces_configs Where company_id = ?i and id = ?i', (int)$_SESSION['main_company'], (int)$marketplace_config_id);
        $marketplace_config = json_decode($data['marketplace_config'],true);

        $code_order = $request->zakaz_id;
        $code_track = $db->getOne("Select market_status_id from market_zakaz_statuses where sort1_status = ?i and marketplace_id = 1", $request->status);
        $response = $request->comment;

        if(empty($code_track)){
            return array("status"=>"err","err"=>"Нет статуса");
        }

        if(!empty($marketplace_config)){
            $login = $marketplace_config['login'];
            $password = $marketplace_config['password'];
            $api_key = $marketplace_config['api_key'];
        }
        else
        { 
            return array("status"=>"err","err"=>"Нет доступа к API"); 
        }

        $url = 'https://api.zzap.pro/webservice/datasharing.asmx/SetOrderStatusV3';

        $ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"login=$login&password=$password&api_key=$api_key&code_order=$code_order&code_track=$code_track&response=$response&call_data=$call_data");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$result = curl_exec($ch);
        file_put_contents("/var/log/sort1/zzap_status.log","zzap url: ".$url."?login=$login&password=$password&api_key=$api_key&code_order=$code_order&code_track=$code_track&response=$response&call_data=$call_data"."\n zzap_return:".print_r($result,true)."\n",FILE_APPEND);
		curl_close($ch);

        $xml = simplexml_load_string($result);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        $array = json_decode($array[0]);
        
        return (array)$array;
    }

    static function get_order_status($request){
        $db = DB::getInstance();
         
        $marketplace_config_id = $request;
        $data = $db->getRow('Select marketplace_config From marketplaces_configs Where company_id = ?i and id = ?i', (int)$_SESSION['main_company'], (int)$marketplace_config_id);
        $marketplace_config = json_decode($data['marketplace_config'],true);

        if(!empty($marketplace_config)){
            $login = $marketplace_config['login'];
            $password = $marketplace_config['password'];
            $api_key = $marketplace_config['api_key'];
        }
        else{ return array("status"=>"err","err"=>"Нет доступа к API"); }

        $url = 'https://api.zzap.pro/webservice/datasharing.asmx/GetCodesTrack';

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"login=$login&password=$password&api_key=$api_key");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$result = curl_exec($ch);
		curl_close($ch);

        $xml = simplexml_load_string($result);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        $array = json_decode($array[0]);

        return (array)$array;
    }
}
?>