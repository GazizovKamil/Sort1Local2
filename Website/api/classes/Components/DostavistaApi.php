<?php
namespace Sort1API\Components;
use Sort1API\Components\DostavistaZakaz;

class DostavistaApi
{
   public static function shipping_cost_calculation($request){
        $db = DB::getInstance();
        $res = $db->getRow("SELECT l.logistics_config  From logistics_company_configs l where l.id = ?i", $request->logistic_config_id);
        $api_key = json_decode($res['logistics_config'],true)['api_key'];
        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_URL, 'https://robot.dostavista.ru/api/business/1.3/calculate-order'); 
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST'); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-DV-Auth-Token: '.$api_key]); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 

        $user = $db->getRow("SELECT l.mphone, l.name  From users l where l.id = ?i", $_SESSION['user_id']);

        $details = [];
        for ($i=0; $i < count((array)$request->details); $i++) { 
            $details[$i]->ware_code = $request->details[$i]['article'];
            $details[$i]->description = $request->details[$i]['name'];
            $details[$i]->items_count = $request->details[$i]['count'];
            $details[$i]->item_payment_amount = $request->details[$i]['sale_price'];
        }
        
        $data = [ 
            'matter' => $request->comment, 
            'vehicle_type_id' => $request->delivery_type,
            'total_weight_kg' => $request->total_weight_kg,
            'is_client_notification_enabled' => $request->is_client_notification_enabled,
            'is_contact_person_notification_enabled' => $request->is_contact_person_notification_enabled,
            'loaders_count' => $request->loaders_count,
            'points' => [ 
                [ 
                    'address' => $request->addressSklad, 
                    'contact_person' => (object)[
                        'phone' => $user['mphone'],
                        'name' => $user['name'],
                    ],
                    'latitude' => $request->coordinatesSklad[0],
                    'longitude' => $request->coordinatesSklad[1],
                    'packages' => $details 
                ], 
                [ 
                    'address' => $request->addressDelivery, 
                    'contact_person' => (object)[
                        'phone' => $request->numberphoneDelivery,
                        'name' => $request->nameDelivery,
                    ],
                    'latitude' => $request->coordinatesDelivery[0],
                    'longitude' => $request->coordinatesDelivery[1],
                    'packages' => $details 
                ], 
            ], 
        ]; 
        
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json); 
        
        $result = curl_exec($curl); 
        if ($result === false) { 
            throw new Exception(curl_error($curl), curl_errno($curl)); 
        } 
        return $result;
   }

    public static function send_order($request){
        $db = DB::getInstance();
        $dostavista_zakaz = $db->getRow("SELECT * FROM dostavista_zakazs dz  where dz.zakaz_id_in_sort1 = (Select lo.zakaz_id From logistic_orders lo where lo.id = ?i)", $request->logistic_order_id);
        $res = $db->getRow("SELECT l.logistics_config  From logistics_company_configs l where l.id = ?i", $dostavista_zakaz['logistic_config_id']);
        $api_key = json_decode($res['logistics_config'],true)['api_key'];
        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_URL, 'https://robot.dostavista.ru/api/business/1.3/create-order'); 
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST'); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-DV-Auth-Token: '.$api_key]); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 

        $user = $db->getRow("SELECT l.mphone, l.name  From users l where l.id = ?i", $dostavista_zakaz['user_id']);

        $details_zakaz = $db->getAll('SELECT * FROM zakaz_details zd where zd.id IN (Select lod.zakaz_detail_id From logistic_order_details lod where lod.logistic_order_id = ?i)', $request->logistic_order_id);
        
        $details = [];
        for ($i=0; $i < count((array)$details_zakaz); $i++) { 
            $details[$i]->ware_code = $details_zakaz[$i]['article'];
            $details[$i]->description = $details_zakaz[$i]['name'];
            $details[$i]->items_count = $details_zakaz[$i]['count'];
            $details[$i]->item_payment_amount = $details_zakaz[$i]['price'];
        }
        
        $data = [ 
            'matter' => $dostavista_zakaz['comment'], 
            'vehicle_type_id' => $dostavista_zakaz['delivery_type'],
            'total_weight_kg' => $dostavista_zakaz['total_weight_kg'],
            'is_client_notification_enabled' => $dostavista_zakaz['is_client_notification_enabled'],
            'is_contact_person_notification_enabled' => $dostavista_zakaz['is_contact_person_notification_enabled'],
            'loaders_count' => $dostavista_zakaz['loaders_count'],
            'points' => [ 
                [ 
                    'address' => $dostavista_zakaz['delivery_from_address'], 
                    'contact_person' => (object)[
                        'phone' => $user['mphone'],
                        'name' => $user['name'],
                    ],
                    'latitude' => $dostavista_zakaz['delivery_from_latitude'],
                    'longitude' => $dostavista_zakaz['delivery_from_longitude'],
                    'packages' => $details 
                ], 
                [ 
                    'address' => $dostavista_zakaz['delivery_to_address'], 
                    'contact_person' => (object)[
                        'phone' => $dostavista_zakaz['numberphoneDelivery'],
                        'name' => $dostavista_zakaz['nameDelivery'],
                    ],
                    'latitude' => $dostavista_zakaz['delivery_to_latitude'],
                    'longitude' => $dostavista_zakaz['delivery_to_longitude'],
                    'packages' => $details 
                ], 
            ], 
        ]; 

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json); 
        
        $result = curl_exec($curl); 
        // print_r(json_decode($result,true)['order']['order_id']);
        if(json_decode($result,true)['is_successful']){
            $dostavista_zakazs = new DostavistaZakaz($dostavista_zakaz['id']);
            $dostavista_zakazs->zakaz_id_in_dostavista = json_decode($result,true)['order']['order_id'];
            $dostavista_zakazs->save();

            $logistic_order = new LogisticOrder($request->logistic_order_id);
            $logistic_order->status = 20;
            $logistic_order_saved=$logistic_order->save();
            if($logistic_order_saved['status']>=1){
                return true;
            }else{
                return $logistic_order_saved['msg'];
            }
        }//посмотреть как вытащить is_successful, после чего если true менять статус logistic_orders и их деталей на 20
        return false;
    }

    public static function refresh_status($request){
        $db = DB::getInstance();
        $logistic_orders = $db->getAll('SELECT *  From logistic_orders lo where lo.status >= 20 and lo.status < 40 and lo.logistic_order_type = 4');
        $curl = curl_init(); 

        for ($i=0; $i < count((array)$logistic_orders); $i++) { 
            $logistic_order = new LogisticOrder($logistic_orders[$i]['id']);


            $api_key = json_decode($db->getOne('SELECT lcc.logistics_config FROM logistics_company_configs lcc where lcc.id = (SELECT dz.logistic_config_id FROM dostavista_zakazs dz where dz.zakaz_id_in_sort1 = ?i)', $logistic_orders[$i]['zakaz_id']),true)['api_key'];
            $dostavista_id = $db->getOne('SELECT dz.zakaz_id_in_dostavista FROM dostavista_zakazs dz where dz.zakaz_id_in_sort1 = ?i', $logistic_orders[$i]['zakaz_id']);
            curl_setopt($curl, CURLOPT_URL, 'https://robot.dostavista.ru/api/business/1.3/deliveries?search_text='.$dostavista_id);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-DV-Auth-Token: '.$api_key]); 
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
            $result = json_decode(curl_exec($curl),true);
            if ($result['deliveries'][0]['status'] == 'planned'){
                $logistic_order->status = 25;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 25"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'courier_assigned'){
                $logistic_order->status = 26;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 26"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'courier_departed'){
                $logistic_order->status = 27;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 27"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'courier_at_pickup'){
                $logistic_order->status = 28;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 28"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'parcel_picked_up'){
                $logistic_order->status = 29;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 29"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'courier_arrived'){
                $logistic_order->status = 30;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 30"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'active'){
                $logistic_order->status = 31;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 31"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'finished'){
                $logistic_order->status = 40;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 40"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'canceled'){
                $logistic_order->status = 32;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 32"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'delayed'){
                $logistic_order->status = 33;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 33"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'failed'){
                $logistic_order->status = 34;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 34"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'reattempt_planned'){
                $logistic_order->status = 35;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 35"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'reattempt_courier_assigned'){
                $logistic_order->status = 36;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 36"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'reattempt_courier_departed'){
                $logistic_order->status = 37;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 37"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'reattempt_courier_picked_up'){
                $logistic_order->status = 38;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 37"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'reattempt_finished'){
                $logistic_order->status = 40;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 40"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'return_planned'){
                $logistic_order->status = 41;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 41"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'return_courier_assigned'){
                $logistic_order->status = 42;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 42"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'return_courier_departed'){
                $logistic_order->status = 43;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 43"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'return_courier_picked_up'){
                $logistic_order->status = 44;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 44"."\n",FILE_APPEND);
            }else if ($result['deliveries'][0]['status'] == 'return_finished'){
                $logistic_order->status = 50;
                file_put_contents("/var/log/sort1/arseniy.log","del_change 50"."\n",FILE_APPEND);
            }
            $logistic_order->save();
        }
        return true;
    }

    public static function check_test_config($request){
        $db = DB::getInstance();
        $res = $db->getRow("SELECT l.logistics_config  From logistics_company_configs l where l.id = ?i", $request->logistic_index);
        $api_key = json_decode($res['logistics_config'],true)['api_key'];
        

        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_URL, 'https://robot.dostavista.ru/api/business/1.3'); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-DV-Auth-Token: '.$api_key]); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
        
        $result = curl_exec($curl); 
        
        
        if (json_decode($result,true)['is_successful']==1) return true;
        else return false;
    }
}
?>