<?php
namespace Sort1API\Components\Models;
require_once '../vendor/autoload.php';
use Sort1API\Components\DB;
use CdekSDK;
use CdekSDK\Requests;

class CdekApi extends Model
{
    private static function get_auth_token($client_id, $client_secret){
        $array = array();
        $array['grant_type']    = 'client_credentials';
        $array['client_id']     = $client_id; 
        $array['client_secret'] = $client_secret; 
        
        $ch = curl_init('https://api.cdek.ru/v2/oauth/token?parameters');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&')); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $html = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($html, true);
        
        return $res['access_token'];
    }

    public static function check_test_config($request){
        $db = DB::getInstance();
        $res = $db->getRow("SELECT l.logistics_config  From logistics_company_configs l where l.id = ?i", $request->logistic_index);
        $client_id = json_decode($res['logistics_config'],true)['client_id'];
        $client_secret = json_decode($res['logistics_config'],true)['client_secret'];
        
        $access_token = self::get_auth_token($client_id, $client_secret);

        if(empty($access_token)){
            return false;
        }
        else{
            return true;
        }
    }

    public static function shipping_cost_calculation($request){
        $db = DB::getInstance();
        $res = $db->getRow("SELECT l.logistics_config From logistics_company_configs l where l.id = ?i", 25);
        $client_id = json_decode($res['logistics_config'],true)['client_id'];
        $client_secret = json_decode($res['logistics_config'],true)['client_secret'];
    
        $access_token = self::get_auth_token($client_id, $client_secret);
    
        $array = array();
        $array['type'] = 1;	
        $array['tariff_code'] = 751;	
                
        $array['from_location'] = array(
            'postal_code' => $request->addressSklad
        );	
            
        $array['to_location'] = array(
            'postal_code' => $request->addressDelivery
        );
         
        $sum = 0;
        foreach($request->details as $detail) {
            $weight = floatval($detail['weight']);
            $article = $detail['article'];
            $array['packages'][] = array(
                'weight' => $weight,
                'length' => 10,
                'width' => 10,
                'height' => 10,
            );
            $sum += $weight;
        }
                                
        $array = json_encode($array, JSON_UNESCAPED_UNICODE);
        $ch = curl_init('https://api.cdek.ru/v2/calculator/tariff');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $array); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $access_token));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $html = curl_exec($ch);
        curl_close($ch);
            
        $res = json_decode($html, true);	

        // print_r($res);      

        if (!$res['errors']){
            $ret['status'] = "ok";
            $ret['err'] = "";
            $ret['msg'] = "";
            $ret['total_sum'] = $res['total_sum'];
        } else {
            $ret['status'] = "err";
            $ret['msg'] = $res['errors'][0]['message'];
        }
        return $ret;
    }
    

    public static function get_zakaz_info($request){
        $db = DB::getInstance();
        $res = $db->getRow("SELECT l.logistics_config  From logistics_company_configs l where l.id = ?i", $request->logistic_index);
        $client_id = json_decode($res['logistics_config'],true)['client_id'];
        $client_secret = json_decode($res['logistics_config'],true)['client_secret'];

        $access_token = self::get_auth_token($client_id, $client_secret);

        $cdek_id = '1234';
		
        $ch = curl_init('https://api.cdek.ru/v2/orders/' . $cdek_id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json', 
            'Authorization: Bearer ' . $access_token
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $html = curl_exec($ch);
        curl_close($ch);
                    
        $res = json_decode($html, true);	
        //var_dump($res);
                    
        echo '<p>Номер отправления: ' . $res['entity']['cdek_number'] . '</p>';
        echo '<p>Статус: ' . $res['requests'][0]['state'] . '</p>';
        echo '<p>' . $res['requests'][0]['errors'][0]['message'] . '</p>';
    }

    public static function create_new_order_expense_client($request){
        $db = DB::getInstance();
        $res = $db->getRow("SELECT l.logistics_config  From logistics_company_configs l where l.id = ?i", $request->logistic_index);
        $client_id = json_decode($res['logistics_config'],true)['client_id'];
        $client_secret = json_decode($res['logistics_config'],true)['client_secret'];

        $access_token = self::get_auth_token($client_id, $client_secret);

        $prods = array(
            array(
                'name'   => 'Кофе в зернах Bushido Red Katana',
                'sku'    => '1234',
                'price'  => '379', // Стоимость
                'count'  => '1',   // кол-во
                'weight' => '227', // Вес, гр
                'length' => '10',  // Длина, см
                'width'  => '10',  // Ширина, см
                'height' => '10',  // Высота, см
            ),
        );
        
        // Стоимость доставки
        $array = array();
        $array['type'] = 1;	
        $array['tariff_code'] = '136';		
                
        $array['from_location'] = array(
            'address' => 'ПОЛНЫЙ_АДРЕС_ОТПРАВИТЕЛЯ'
        );	
            
        $array['to_location'] = array(
            'address' => 'ПОЛНЫЙ_АДРЕС_ПОЛУЧАТЕЛЯ'
        );
        
        $sum = 0;
        foreach($prods as $i => $row) {
            $sum += $row['count'] * $row['price'];
            
            $array['packages'][] = array(
                'weight' => $row['weight'] * $row['count'],
                'length' => $row['length'],
                'width'  => $row['width'],
                'height' => $row['height'],
            );					
        }
                                
        $array['services'] = array(
            'code' => 'INSURANCE',
            'parameter' => $sum
        );
                                
        $array = json_encode($array, JSON_UNESCAPED_UNICODE);
        $ch = curl_init('https://api.cdek.ru/v2/calculator/tariff');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $array); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $access_token));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $html = curl_exec($ch);
        curl_close($ch);
            
        $res = json_decode($html, true);	
        $delivery_sum = $res['total_sum'];
        
        if (!empty($delivery_sum)) {
            // Регистрация заявки
            $array = array();
            $array['type'] = 1;
            $array['tariff_code'] = '137';
            $array['number'] = 'НОМЕР_ЗАКАЗА_НА_ВАШЕМ_САЙТЕ';	
            
            $array['delivery_recipient_cost'] = array(
                'value' => $delivery_sum
            );
        
            $array['shipment_point'] = 'КОД_ПВЗ_ОТГРУЗКИ';	
            $array['to_location'] = array(
                'address' => 'ПОЛНЫЙ_АДРЕС_ПОЛУЧАТЕЛЯ'
            );
                    
            $array['recipient'] = array(
                'name' => 'ИМЯ_ПОЛУЧАТЕЛЯ',
                'phones' => array(
                    'number' => 'ТЕЛЕФОН_ПОЛУЧАТЕЛЯ',
                ),
                'address' => 'АДРЕС_ПОЛУЧАТЕЛЯ',
                'email'   => 'EMAIL_ПОЛУЧАТЕЛЯ',
            );	
            
            foreach($prods as $i => $row) {
                $array['packages'][] = array(
                    'number' => ++$i,
                    'weight' => $row['weight'] * $row['count'],
                    'length' => $row['length'],
                    'width'  => $row['width'],
                    'height' => $row['height'],
                    'items'  => array(
                        array(
                            'name'     => $row['name'],
                            'ware_key' => $row['sku'],
                            'payment'  => array(
                                'value' => $row['price'],
                            ),
                            'cost'     => $row['price'], // Стоимось товарая для страховки
                            'weight'   => $row['weight'],
                            'amount'   => $row['count'],
                        )
                    )
                );					
            }
                
            $array = json_encode($array, JSON_UNESCAPED_UNICODE);
            $ch = curl_init('https://api.cdek.ru/v2/orders');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $array); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $access_token));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $html = curl_exec($ch);
            curl_close($ch);
                                
            $res = json_decode($html, true);	
            var_dump($res);
        }
    }

    public static function create_new_order($request){
        $db = DB::getInstance();
        $res = $db->getRow("SELECT l.logistics_config  From logistics_company_configs l where l.id = ?i", $request->logistic_index);
        $client_id = json_decode($res['logistics_config'],true)['client_id'];
        $client_secret = json_decode($res['logistics_config'],true)['client_secret'];

        $access_token = self::get_auth_token($client_id, $client_secret);
        
        $prods = array(
            array(
                'name'   => 'Кофе в зернах Bushido Red Katana',
                'sku'    => '1234',
                'price'  => '379', // Стоимость
                'count'  => '1',   // кол-во
                'weight' => '227', // Вес, гр
                'length' => '10',  // Длина, см
                'width'  => '10',  // Ширина, см
                'height' => '10',  // Высота, см
            ),
        );
         
        // Регистрация заявки
        $array = array();
        $array['type'] = 1;	// Договор "интернет-магазин"
        $array['tariff_code'] = '137';
        $array['number'] = 'НОМЕР_ЗАКАЗА_НА_ВАШЕМ_САЙТЕ';	
        $array['shipment_point'] = 'КОД_ПВЗ_ОТГРУЗКИ';	
        $array['to_location'] = array(
            'address' => 'ПОЛНЫЙ_АДРЕС_ПОЛУЧАТЕЛЯ'
        );
                        
        $array['recipient'] = array(
            'name' => 'ИМЯ_ПОЛУЧАТЕЛЯ',
            'phones' => array(
                'number' => 'ТЕЛЕФОН_ПОЛУЧАТЕЛЯ',
            ),
            'address' => 'АДРЕС_ПОЛУЧАТЕЛЯ',
            'email'   => 'EMAIL_ПОЛУЧАТЕЛЯ',
        );	
         
        foreach($prods as $i => $row) {
            $array['packages'][] = array(
                'number' => ++$i,
                'weight' => $row['weight'] * $row['count'],
                'length' => $row['length'],
                'width'  => $row['width'],
                'height' => $row['height'],
                'items'  => array(
                    array(
                        'name'     => $row['name'],
                        'ware_key' => $row['sku'],
                        'payment'  => array(
                            'value' => $row['price'],
                        ),
                        'cost'     => $row['price'],
                        'weight'   => $row['weight'],
                        'amount'   => $row['count'],
                    )
                )
            );					
        }
            
        $array = json_encode($array, JSON_UNESCAPED_UNICODE);
        $ch = curl_init('https://api.cdek.ru/v2/orders');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $array); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json', 
            'Authorization: Bearer ' . $access_token
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $html = curl_exec($ch);
        curl_close($ch);
                            
        $res = json_decode($html, true);	
        var_dump($res);
    }
}
?>