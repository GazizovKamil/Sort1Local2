<?php
namespace Sort1API\Components;

class AvitoApi
{
    static function get_avito_token($marketplaces_configs_id) {
        $db = DB::getInstance();
        $current_time = time();
        
        // Получаем client_id, client_secret и user_id из marketplaces_configs
        $config_data = $db->getRow("SELECT marketplace_config FROM marketplaces_configs WHERE id = ?i", $marketplaces_configs_id);
        if (!$config_data) {
            return null;
        }
        
        $marketplace_config = json_decode($config_data['marketplace_config'], true);
        $user_id = $marketplace_config['user_id'] ?? null;
        $client_id = $marketplace_config['Client_id'] ?? null;
        $client_secret = $marketplace_config['Client_secret'] ?? null;
    
        if (!$client_id || !$client_secret || !$user_id) {
            return null;
        }
        
        // Проверяем, есть ли в базе актуальный токен
        $token_data = $db->getRow("SELECT access_token, refresh_token, created_at FROM avito_tokens WHERE marketplace_config_id = ?i ORDER BY created_at DESC LIMIT 1", $marketplaces_configs_id);
        
        if ($token_data) {
            $token_age = $current_time - strtotime($token_data['created_at']);
            if ($token_age < 86400) { // 24 часа
                return [
                    'access_token' => $token_data['access_token'],
                    'user_id' => $user_id
                ];
            }
            
            // Если токен истек, пытаемся обновить его
            if (!empty($token_data['refresh_token'])) {
                $url = 'https://api.avito.ru/token/';
                $data = http_build_query([
                    'grant_type' => 'refresh_token',
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'refresh_token' => $token_data['refresh_token']
                ]);
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                
                $result = curl_exec($ch);
                curl_close($ch);
    
                if ($result !== FALSE) {
                    $response = json_decode($result, true);
                    if (isset($response['access_token'])) {
                        // Обновляем токен в базе
                        $db->query(
                            "INSERT INTO avito_tokens (access_token, refresh_token, created_at, user_id, company_id, marketplace_config_id) VALUES (?s, ?s, NOW(), ?i, ?i, ?i)",
                            $response['access_token'],
                            $response['refresh_token'] ?? null,
                            $_SESSION['user_id'],
                            (int)$_SESSION['main_company'],
                            $marketplaces_configs_id
                        );
                        return [
                            'access_token' => $response['access_token'],
                            'user_id' => $user_id
                        ];
                    }
                }
            }
        }
        
        // Если нет токена или обновление не удалось, запрашиваем новый
        $url = 'https://api.avito.ru/token/';
        $data = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $result = curl_exec($ch);
        curl_close($ch);
    
        if ($result === FALSE) {
            return null;
        }
        
        $response = json_decode($result, true);
        
        if (isset($response['access_token'])) {
            // Сохраняем новый токен в базу
            $db->query(
                "INSERT INTO avito_tokens (access_token, refresh_token, created_at, user_id, company_id, marketplace_config_id) VALUES (?s, ?s, NOW(), ?i, ?i, ?i)",
                $response['access_token'],
                $response['refresh_token'] ?? null,
                $_SESSION['user_id'],
                (int)$_SESSION['main_company'],
                $marketplaces_configs_id
            );
        }
        
        return [
            'access_token' => $response['access_token'] ?? null,
            'user_id' => $user_id
        ];
    }    
    
    static function get_seller_orders($request) {
        $db = DB::getInstance();
        $page = 1;
        $limit = 20; 
        $marketplaces_configs_id = (int)$request->marketplaces_configs_id;
    
        $date_from = !empty($request->date_from) ? strtotime($request->date_from) : strtotime('-7 days');
        
        // Получаем токен
        $avito_data = self::get_avito_token($marketplaces_configs_id);
        if (!$avito_data) {
            return ["status" => "err", "err" => "Не удалось получить токен Avito"];
        }
        $access_token = $avito_data['access_token'];
        $all_orders = [];
        
        // $log_file = '/var/log/sort1/avito.log';
        
        // // Стартовое время запроса
        // $start_time = microtime(true);
        
        do {
            $page_start_time = microtime(true); // Время для каждой итерации
            
            // Формируем URL запроса
            $url = "https://api.avito.ru/order-management/1/orders";
            
            // Формируем параметры запроса
            $query_params = [
                "page" => (int)$page,
                "limit" => min(max((int)$limit, 1), 20), // Ограничение от 1 до 20
                "dateFrom" => $date_from
            ];
            
            $url .= '?' . http_build_query($query_params);
    
            // Логируем отправляемый запрос
            // file_put_contents($log_file, "Request URL: $url\n", FILE_APPEND);
            // file_put_contents($log_file, "Request Parameters: " . json_encode($query_params) . "\n", FILE_APPEND);
    
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $access_token",
                "Content-Type: application/json"
            ]);
            
            $result = curl_exec($ch);
            curl_close($ch);
            
            // Логирование времени каждого запроса
            $page_end_time = microtime(true);
            // $execution_time = $page_end_time - $page_start_time;
            // file_put_contents($log_file, "Page $page request time: " . $execution_time . " seconds\n", FILE_APPEND);
    
            if ($result === false) {
                // file_put_contents($log_file, "Request Error: " . curl_error($ch) . "\n", FILE_APPEND);
                return ["status" => "err", "err" => "Ошибка запроса к Avito API"];
            }
            
            $response = json_decode($result, true);
    
            // Логируем ответ от сервера
            // file_put_contents($log_file, "Response: " . json_encode($response) . "\n", FILE_APPEND);
            
            if (!isset($response["orders"])) {
                return ["status" => "err", "err" => "Некорректный ответ от Avito API", "response" => $response];
            }
    
            $all_orders = array_merge($all_orders, $response["orders"]);
            
            $has_more = isset($response["hasMore"]) ? $response["hasMore"] : false;
            $page++;
            
        } while ($has_more);
        
        // Общее время выполнения запроса
        // $end_time = microtime(true);
        // $total_execution_time = $end_time - $start_time;
        // file_put_contents($log_file, "Total execution time: " . $total_execution_time . " seconds\n", FILE_APPEND);
    
        return ["status" => "ok", "orders" => $all_orders];
    }

    static function set_order_status($request){
        $db = DB::getInstance();
    
        $marketplace_config_id = $request->marketplace_config_id;
    
        $order_id = $request->zakaz_id;
        $status = (int)$request->status; // Должно быть одно из: "confirm", "reject", "perform", "receive"
        $transition = null;

        if($status == 2){
            $transition = "confirm";
        }
        elseif($status == 102){
            $transition = "reject";
        }
        elseif($status == 70){
            $transition = "perform";
        }
        elseif($status == 75){
            $transition = "receive";
        }

        if (!in_array($transition, ["confirm", "reject", "perform", "receive"])) {
            return ["status" => "err", "err" => "Некорректный статус заказа"];
        }

        $avito_data = self::get_avito_token($marketplace_config_id);
        if (!$avito_data) {
            return ["status" => "err", "err" => "Не удалось получить токен Avito"];
        }
        $access_token = $avito_data['access_token'];
    
        $url = 'https://api.avito.ru/order-management/1/order/applyTransition';
        
        $headers = [
            "Authorization: Bearer $access_token",
            "Content-Type: application/json"
        ];
        
        $payload = json_encode([
            "orderId" => $order_id,
            "transition" => $transition
        ]);
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $result = curl_exec($ch);
        file_put_contents("/var/log/sort1/avito_status.log", "Avito API Request: " . print_r($payload, true) . "\nResponse: " . print_r($result, true) . "\n", FILE_APPEND);
        curl_close($ch);
        
        return json_decode($result, true);
    }    
    
    static function get_chat_messages($chat_id, $marketplace_id, $user_id, $access_token) {
        $db = DB::getInstance();
        $limit = 100;  
        $offset = 0;  
        $db->query("SET NAMES 'utf8mb4'");

        $url = "https://api.avito.ru/messenger/v3/accounts/{$user_id}/chats/{$chat_id}/messages/?limit={$limit}&offset={$offset}";
        $headers = [
            "Authorization: Bearer {$access_token}",
            "Content-Type: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return ["status" => "err", "err" => "Ошибка запроса к Avito API"];
        }

        $data = json_decode($response, true);
        
        if (isset($data['messages'])) {
            foreach ($data['messages'] as $message) {

                if ($message['type'] !== 'text') {
                    continue; // Пропускаем сообщение, если не тип "text"
                }

                $message_id = $message['id'];
                $author_id = $message['author_id'];
                $created_at = date('Y-m-d H:i:s', $message['created']);
                $content =  $message['content']['text'];
                $direction = $message['direction'];
                $is_read = $message['isRead'] ? 1 : 0;
                // $content = preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $content);
                $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                // Проверяем, есть ли уже сообщение в БД
                $existing = $db->getOne("SELECT id FROM marketplace_messages WHERE message_id = ?s AND marketplace_id = ?i", $message_id, $marketplace_id);
                
                $log_file = '/var/log/sort1/avito.log';

                if (!$existing) {
                    try {
                        $db->query("INSERT INTO marketplace_messages (message_id, chat_id, author_id, created_at, content, direction, is_read, marketplace_id)
                            VALUES (?s, ?s, ?i, ?s, ?s, ?s, ?i, ?i)", 
                            $message_id, $chat_id, $author_id, $created_at, $content, $direction, $is_read, $marketplace_id);
                    } catch (Exception $e) {
                        file_put_contents($log_file, 'Error inserting message: ' . $e->getMessage(). $data  . PHP_EOL, FILE_APPEND);
                    }
                }
            }
        }
    
        return ["status" => "ok"];
    }

    static function mark_chat_as_read($chat_id, $user_id, $access_token) {
        // URL для отправки запроса на пометку чата как прочитанного
        $url = "https://api.avito.ru/messenger/v1/accounts/{$user_id}/chats/{$chat_id}/read";
    
        // Заголовки запроса
        $headers = [
            "Authorization: Bearer {$access_token}",
            "Content-Type: application/json"
        ];
    
        // Инициализация cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); // POST метод
        $response = curl_exec($ch);
        curl_close($ch);
    
        // Обрабатываем ответ
        $data = json_decode($response, true);
    
        // Проверяем, что ответ успешный
        if (isset($data['ok']) && $data['ok'] === true) {
            return ["status" => "ok", "message" => "Чат помечен как прочитанный"];
        } else {
            return ["status" => "err", "err" => "Не удалось пометить чат как прочитанный"];
        }
    }

    static function get_all_chat_messages($chat_id, $marketplace_id, $user_id, $access_token) {
        $db = DB::getInstance();
    
        self::get_chat_messages($chat_id, $marketplace_id,$user_id,$access_token);
        // Получаем все сообщения по chat_id и marketplace_id
        $messages = $db->getAll("SELECT message_id, chat_id, author_id, created_at, content, direction, is_read 
                                 FROM marketplace_messages 
                                 WHERE chat_id = ?s AND marketplace_id = ?i ORDER BY created_at ASC", $chat_id, $marketplace_id);
    
        $username = $db->getOne("
            SELECT mc.name 
            FROM market_zakaz m
            JOIN marketplace_company mc ON m.company_id = mc.id
            WHERE m.chat_id = ?s
        ", $chat_id);
        // $mark_read_result = self::mark_chat_as_read($chat_id, $user_id, $access_token);
        $log_file = '/var/log/sort1/avito.log';
        // file_put_contents($log_file, 'Error inserting message: ' . $chat_id . " ".  $marketplace_id . PHP_EOL, FILE_APPEND);

        // if ($messages) {
            return [
                "status" => "ok",
                "user_name" => $username,
                "messages" => $messages
            ];
        // } else {
        //     return [
        //         "status" => "err",
        //         "err" => "Нет сообщений"
        //     ];
        // }
    }

    static function send_chat_message($chat_id, $user_id, $access_token, $message_text) {
        // URL для отправки запроса на отправку сообщения
        $url = "https://api.avito.ru/messenger/v1/accounts/{$user_id}/chats/{$chat_id}/messages";
    
        // Тело запроса, которое включает текст сообщения
        $data = [
            "message" => [
                "text" => $message_text
            ],
            "type" => "text"
        ];
    
        // Заголовки запроса
        $headers = [
            "Authorization: Bearer {$access_token}",
            "Content-Type: application/json"
        ];
    
        // Инициализация cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  // Передаем данные в формате JSON
        $response = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
    
        // Обрабатываем ответ
        $data = json_decode($response, true);
        // $log_file = '/var/log/sort1/avito.log';
        // file_put_contents($log_file, 'Error inserting message: ' . $chat_id . " ".  print_r($data) . PHP_EOL, FILE_APPEND);
        if ($http_code == 200) {
            return ["status" => "ok", "message" => "Сообщение успешно отправлено"];
        } else {
            return ["status" => "err", "err" => "Не удалось отправить сообщение"];
        }
    }

    public static function get_or_create_client($chatId, $marketplaces_configs_id) {
        $db = DB::getInstance();
        $avito_data = AvitoApi::get_avito_token($marketplaces_configs_id);
    
        if ($avito_data) {
            $access_token = $avito_data['access_token'];
            $user_id = $avito_data['user_id'];
        } else {
            return ['error' => 'Ошибка получения данных'];
        }
    
        $url = "https://api.avito.ru/messenger/v2/accounts/{$user_id}/chats/{$chatId}";
        $headers = [
            "Authorization: Bearer {$access_token}",
            "Content-Type: application/json"
        ];
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
    
        if ($response) {
            if (!empty($data['users'][0]['name'])) {
                $client_name = $data['users'][0]['name'];
                $client_id = $data['users'][0]['id'];
            } else {
                $client_id = 471; 
            }
        } else {
            $client_id = 471;
            $log_file = '/var/log/sort1/avito.log';
            $log_data = [
                'timestamp' => date('Y-m-d H:i:s'),
                'url' => $url,
                'response' => $response
            ];
            file_put_contents($log_file, json_encode($log_data) . PHP_EOL, FILE_APPEND);
        }
    
        $client = new MarketplaceCompany($client_id);
    
        if ($client->id > 0) {
            $check_bind = $db->getRow("SELECT * FROM marketplace_user_companys WHERE main_company_id=?i AND company_id=?i", $_SESSION['main_company'], $client->id);
            if (!$check_bind) {
                // Если связи нет, создаем
                $db->query("INSERT IGNORE INTO marketplace_user_companys SET user_id=?i, main_company_id=?i, company_id=?i, btype=1, marketplace_id=2, deleted=0", $_SESSION['user_id'], $_SESSION['main_company'], $client->id);
            }
        } else {
            $client->name = $client_name;
            $client->email = $client_id; 
            $client->Save();
            
            $db->query("INSERT IGNORE INTO marketplace_user_companys SET user_id=?i, main_company_id=?i, company_id=?i, btype=1, marketplace_id=2, deleted=0", $_SESSION['user_id'], $_SESSION['main_company'], $client->id);
        }
    
        return $client;
    }
}
?>