<?php
    class SberPayQr
    {
        private $member_id, $id_qr, $tid, $client_id, $client_secret, $pkcs12_filename, $pkcs12_password,$token;

        function __construct($member_id, $id_qr, $tid, $client_id, $client_secret, $pkcs12_filename, $pkcs12_password)
        {
            $this->member_id = $member_id;
            $this->id_qr = $id_qr;
            $this->tid = $tid;
            $this->client_id = $client_id;
            $this->client_secret = $client_secret;
            $this->pkcs12_filename = $pkcs12_filename;
            $this->pkcs12_password = $pkcs12_password;
        }

        function get_token($url)
        {
            $url = 'https://api.sberbank.ru:8443/prod/tokens/v2/oauth';
            $auth = base64_encode($this->client_id.':'.$this->client_secret);

            $headers = [
                'Accept: application/json',
                'Authorization: Basic '.$auth,
                'Content-Type: application/x-www-form-urlencoded',
                'rquid: '.md5(date("Y-m-d H:i:s").$this->member_id),
                'x-ibm-client-id: '.$this->client_id
            ];

            $post = [
                'grant_type' => 'client_credentials',
                'scope' => $url
            ];

            $ch = curl_init($url, http_build_query($post));

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true);

            $res = curl_exec($ch);
            $info = curl_getinfo($ch);

            $ret=array(
                'header' => substr($res,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
                'body' => substr($res,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
                'http_code' => $info['http_code'],
                );

            // print_r($info);
            // print_r($ret);
            $a = 'вытягивание токена';

            $result = json_decode($res);
            //echo($result);
            curl_close($ch);
            $this->token = $result->token;
        }

        function create($order_id, $positions)
        {
            $url = 'https://api.sberbank.ru:8443/prod/qr/order/v3/creation';
            $rq_uid = md5(date("Y-m-d H:i:s").$this->member_id);
            $dt = DateTime::createFromFormat('U.u', microtime(true))->format("H:i:s.u");
            $this->get_token('https://api.sberbank.ru/qr/order.create');
            $acces_token = $this->token;

            $headers = [
                'Accept: application/json',
                'Authorization: Bearer'.$this->token,
                'RqUID'.$rq_uid,
                'x-ibm-client-id'.$this->client_id
            ];

            $post = [
                'rq_uid: '.$rq_uid,
                'rq_tm: '.$dt,
                'member_id: '.$this->member_id,
                'order_number:'.$order_id,
                'order_create_date: '.$dt,
                'order_params_type: '.[
                        'position_name: .$name',
                        'position_count: count',
                        'osition_sum: '.$positions,
                        'position_description: .$name'
                ],
                'id_qr: '.$this->id_qr,
                'order_sum: '.$positions,
                'currency: 643',
                'description: .$name'
            ];

            $ch = curl_init($url, http_build_query($post));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($res);
            return $result;
        }

        function get_status($order_id, $partner_order_number)
        {
            $url = 'https://api.sberbank.ru:8443/prod/qr/order/v3/status';
            $rq_uid = md5(date("Y-m-d H:i:s").$this->member_id);
            $dt = DateTime::createFromFormat('U.u', microtime(true))->format("H:i:s.u");
            $this->get_token('https://api.sberbank.ru/qr/order.create');
            $acces_token = $this->token;

            $headers = [
                'Accept: application/json',
                'Authorization: Bearer'.$this->token,
                'RqUID'.$rq_uid,
                'x-ibm-client-id'.$this->client_id
            ];

            $post = [
                'rq_uid: '.$rq_uid,
                'rq_tm: '.$dt,
                'order_id: '.$order_id,
                'tid: '.$this->tid,
                'partner_order_number: '.$partner_order_number
            ];

            $ch = curl_init($url, http_build_query($post));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($res);
            return $result;
        }

        function cancel($order_id, $operation_id, $cancel_operation_sum)
        {
            $url = 'https://api.sberbank.ru:8443/prod/qr/order/v3/cancel';
            $rq_uid = md5(date("Y-m-d H:i:s").$this->member_id);
            $dt = DateTime::createFromFormat('U.u', microtime(true))->format("H:i:s.u");
            $this->get_token('https://api.sberbank.ru/qr/order.create');
            $acces_token = $this->token;

            $headers = [
                'Accept: application/json',
                'Authorization: Bearer'.$this->token,
                'RqUID'.$rq_uid,
                'x-ibm-client-id'.$this->client_id
            ];

            $post = [
                'rq_uid: '.$rq_uid,
                'rq_tm: '.$dt,
                'operation_id: '.$operation_id,
                'order_id: '.$order_id,
                'id_qr: '.$this->id_qr,
                'cancel_operation_sum: '.$cancel_operation_sum,
                'operation_currency: 643',
                'tid: '.$this->tid,
                'operation_type: REVERSE'
            ];

            $ch = curl_init($url, http_build_query($post));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($res);
            return $result;
        }
    }
?>