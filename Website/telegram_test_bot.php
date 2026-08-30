<?php
namespace Sort1API;
use Sort1API\Components\DB;
use Sort1API\Components\Config;
use Sort1API\Components\Models\Sklads;
use Sort1API\Components\Models\Search;

require_once "api/classes/App.php";
App::$OUTPUT=0;
App::run(); 

$db = DB::getInstance();
$site_dir = dirname(dirname(__FILE__)).'/'; // корень сайта
$bot_token = '7242681591:AAGiApkL17ksKx81mW6txk9qvGE43CWPkp8'; // токен вашего бота
$data = file_get_contents('php://input'); // весь ввод перенаправляем в $data
$data = json_decode($data, true); // декодируем json-закодированные-текстовые данные в PHP-массив

// Для отладки, добавим запись полученных декодированных данных в файл message.txt, 
// который можно смотреть и понимать, что происходит при запросе к боту
// Позже, когда все будет работать закомментируйте эту строку:
file_put_contents('/var/log/sort1/telegram_message.txt', print_r($data, true),FILE_APPEND);

// Основной код: получаем сообщение, что юзер отправил боту и 
// заполняем переменные для дальнейшего использования
if(!empty($data['message']['contact'])){
    file_put_contents('/var/log/sort1/telegram_message.txt', "\n".print_r($data['message']['contact'], true),FILE_APPEND);
    $chat_id = $data['message']['from']['id'];
    $replyMarkup = array(
            'keyboard' => array(
                array(array('text'=>"",'request_contact'=>false))
            )
        );
        $encodedMarkup = json_encode($replyMarkup);
    message_to_telegram($bot_token, $chat_id, "Большое спасибо",$encodedMarkup);
}
if (!empty($data['message']['text'])) {
    $chat_id = $data['message']['from']['id'];
    $user_name = $data['message']['from']['username'];
    $first_name = $data['message']['from']['first_name'];
    $last_name = $data['message']['from']['last_name'];
    $text = trim($data['message']['text']);
    $text_array = explode(" ", $text);
    if($text=='/start'){
        $replyMarkup = array(
            'keyboard' => array(
                array(array('text'=>"Привязать к номеру",'request_contact'=>true))
            )
        );
        $encodedMarkup = json_encode($replyMarkup);
        message_to_telegram($bot_token, $chat_id, "Поделитесь пожалуйста телефонным номером", $encodedMarkup);
    }
    
    if ($text == '/help') {
        $text_return = "Привет, $first_name $last_name, вот команды, что я понимаю: 
/help - список команд
/about - о нас
";
        message_to_telegram($bot_token, $chat_id, $text_return);
    }
    elseif ($text == '/about') {
        $text_return = "verysimple_bot:
Я пример самого простого бота для телеграм, написанного на простом PHP.
Мой код можно скачивать, дополнять, исправлять. Код доступен в этой статье:
https://www.novelsite.ru/kak-sozdat-prostogo-bota-dlya-telegram-na-php.html
";
        message_to_telegram($bot_token, $chat_id, $text_return);
    }

}

// функция отправки сообщени в от бота в диалог с юзером
function message_to_telegram($bot_token, $chat_id, $text, $reply_markup = '')
{
    $ch = curl_init();
    $ch_post = [
        CURLOPT_URL => 'https://api.telegram.org/bot' . $bot_token . '/sendMessage',
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chat_id,
            'parse_mode' => 'HTML',
            'text' => $text,
            'reply_markup' => $reply_markup,
        ]
    ];

    curl_setopt_array($ch, $ch_post);
    curl_exec($ch);
}
?>