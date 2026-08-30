<?php
namespace Sort1API\Components;

class OzonApi
{
    function add_product($request)
    {
        $client_id = '900467';
        $token = 'f77c74fe-623c-4683-bf55-57a3172d8d75';
        if(!empty($request->sklad_id)) $sklad_id = $request->sklad_id;
        else return 0;

        $db = DB::getInstance();
        $sql = "select detail_id, article, brand_id, brand, name, price, count from sklad_details where sklad_id = 4 and count - reserved_count > 0";
        $prods = $db->getAll($sql);

        if (!empty($prods)) {
            $items = array();
            foreach ($prods as $prod) {
                // Получаем характеристики категории
                $data = array(
                    'attribute_type' => 'ALL',
                    'category_id'    => array($prod['ozon_category']),
                    'language'       => 'RU'
                );
            
                $ch = curl_init("https://api-seller.ozon.ru/v3/category/attribute");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Api-Key:' . $token,
                    'Client-Id:' . $client_id, 
                    'Content-Type:application/json'
                ));
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE)); 
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HEADER, false);
                $res = curl_exec($ch);
                curl_close($ch);
                
                // Заполнение характеристик
                $attributes = array();
                $res = json_decode($res, true);
                foreach ($res['result'][0]['attributes'] as $attr) {
                    // Бренд
                    if ($prodres['name'] == 'Бренд') {
                        $attributes[] = array(
                            'complex_id' => 0,
                            'id' => $attr['id'],
                            'values' => array(
                                array(
                                    'value' => $prod['name']
                                )
                            )
                        );
                    }
                    
                    // Описание товара
                    if ($prodres['name'] == 'Аннотация') {
                        $attributes[] = array(
                            'id' => $attr['id'],
                            'values' => array(
                                array(
                                    'dictionary_value_id' => 0,
                                    'value' => strip_tags($prod['text'])// описание
                                )
                            )
                        );
                    }
        
                    if ($prodres['name'] == 'Артикул') {
                        $attributes[] = array(
                            'id' => $attr['id'],
                            'values' => array(
                                array(
                                    'dictionary_value_id' => 0,
                                    'value' => $prod['sku'] //color
                                )
                            )
                        );
                    }
                }
        
                $item = array(
                    'attributes'           => $attributes,
                    'barcode'              => $prod['barcode'],
                    'category_id'          => $prod['ozon_category'],
                    //'color_image'        => '',
                    //'complex_attributes' => array(),
                    'currency_code'        => 'RUB',
                    'depth'                => $prod['depth'],
                    'dimension_unit'       => 'mm',
                    'height'               => $prod['height'],
                    'images'               => array(
                        'https://example.com/img/' . $prod['img_1'],
                        'https://example.com/img/' . $prod['img_2'],
                    ),
                    //'images360'          => array(),
                    'name'                 => $prod['name'],
                    'offer_id'             => $prod['id'],
                    //'pdf_list'           => array(),
                    //'premium_pric'       => '900',
                    'price'                => $prod['price'],
                    'old_price'            => $prod['price_old'],
                    //'primary_image'      => '',
                    'vat'                  => '0',
                    'weight'               => $prod['weight'],
                    'weight_unit'          => 'g',
                    'width'                => $prod['width']
                );
        
                $items[] = $item;
            }
        
            $data = array(
                'items' => $items
            );
            
            $ch = curl_init('https://api-seller.ozon.ru/v2/product/import');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Api-Key:' . $token,
                'Client-Id:' . $client_id, 
                'Content-Type:application/json'
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE)); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res = curl_exec($ch);
            curl_close($ch);
            
            $res = json_decode($res, true);
            print_r($res);
        }
    }

    function update_price_products($request)
    {
        $client_id = '900467';
        $token = 'f77c74fe-623c-4683-bf55-57a3172d8d75';

        if(!empty($request->sklad_id)) $sklad_id = $request->sklad_id;
        else return 0;

        $db = DB::getInstance();
        $sql = "select detail_id, article, brand_id, brand, name, price, count from sklad_details where sklad_id = 4 and count - reserved_count > 0";
        $prods = $db->getAll($sql);

        if (!empty($prods)) {
            $items = array();
            foreach ($prods as $prod) {
                $items[] =  array(
                    'auto_action_enabled' => 'UNKNOWN',
                    'currency_code' => 'RUB',
                    'min_price' => '0',
                    'offer_id' => $prod['id'],
                    'old_price' => 0,
                    'price' => strval($prod['price']),
                );
            }
        
            $data = array(
                'prices' => $items
            );
            
            $ch = curl_init('https://api-seller.ozon.ru/v1/product/import/prices');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Api-Key:' . $token,
                'Client-Id:' . $client_id, 
                'Content-Type:application/json'
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE)); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res = curl_exec($ch);
            curl_close($ch);
            
            $res = json_decode($res, true);
            print_r($res);	
        }
    }

    function update_remainder_products($request)
    {
        $client_id = '900467';
        $token = 'f77c74fe-623c-4683-bf55-57a3172d8d75';

        if(!empty($request->sklad_id)) $sklad_id = $request->sklad_id;
        else return 0;

        $db = DB::getInstance();
        $sql = "select detail_id, article, brand_id, brand, name, price, count from sklad_details where sklad_id = 4 and count - reserved_count > 0";
        $prods = $db->getAll($sql);

        if (!empty($prods)) {
            $items = array();
            foreach ($prods as $prod) {
                $items[] =  array(
                    'offer_id' => $prod['id'],
                    'stock'    => $prod['count'],
                );
            }
        
            $data = array(
                'stocks' => $items
            );
            
            $ch = curl_init('https://api-seller.ozon.ru/v1/product/import/stocks');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Api-Key:' . $token,
                'Client-Id:' . $client_id, 
                'Content-Type:application/json'
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE)); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res = curl_exec($ch);
            curl_close($ch);
            
            $res = json_decode($res, true);
            print_r($res);	
        }
    }
}

?>