<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;

class MyExampleModel extends Model {
	

    /**
     * Выборка данных для клиента относительно Компании
     * @return mixed
     * !!!!!!!!!!!!!!!!Внести параметры в поиск!!!!!!!!!!!!!!!!!
     */
    public static function getAllEventsForClients($values = false) {
        $db = DB::getInstance();
        $params = array(
            'events_main_company_id' => 1,
            'events_company_id' => 1,
            'events_client_id' => 1,
        );
        $data = $db->getAll('SELECT * FROM events WHERE events_main_company_id = 1 AND events_company_id = 1 AND events_client_id = 1');
        return $data;
    }

    //Список Всех задач оносящиеся к Исполнителю (ЗАДАЧИ)
    public function getAllEventsForClient ($events_client_id = false){
        $db = DB::getInstance();
        $params = [
            'events_company_id' => 1, //берем из сессии
            'events_contractor' => 1, //берем исполнителя
        ];
        //debug($params);
        $result = $db->row('SELECT
                              *
                            FROM 
                              events
                            WHERE 
                              events_company_id = :events_company_id 
                              AND events_contractor = :events_contractor', $params);
        //debug($result);
        return $result;
    }

    //Список Всех активных задач оносящиеся к Исполнителю (ЗАДАЧИ)
    public function getActiveEventsForClient ($events_client_id = false){
        $db = DB::getInstance();
        $params = [
            'events_company_id' => 1, //берем из сессии
            'events_contractor' => 1, //берется из исполниетля
            'events_status' => 1,
        ];
        //debug($params);
        $result = $db->row('SELECT
                                *
                            FROM 
                                events
                            WHERE 
                                events_company_id = :events_company_id 
                                AND events_contractor = :events_contractor 
                                AND events_status = :events_status', $params);
        //debug($result);
        return $result;
    }

    //Список Задач, Звонков, Email оносящиеся к Исполнителю на сегодня
    public static function getEventsTodayForClient ($events_client_id = false){
        $db = DB::getInstance();
        $params = [
            'events_company_id' => 1, //берем из сессии
            'events_contractor' => 1, //берем из исполнителя
            'date_start' =>  date('Y-m-d'),
            'events_status' => 1,
        ];
        //debug($params);
        $result = $db->getAll('SELECT
                                * 
                                FROM 
                                    events
                                WHERE 
                                    events_company_id = ?i 
                                    AND events_contractor = ?i
                                    AND events_status = ?i
                                    AND (events_date_start BETWEEN ?s AND ?s)',
                                $params['events_company_id'],
                                $params['events_contractor'],
                                $params['events_status'],
                                $params['date_start'],
                                $params['date_start']);
        //debug($result);
        return $result;
    }

    //Список Задач, Звонков, Email оносящиеся к Исполнителю на завтра
    public static function getEventsTomorrowForClient ($events_client_id = false){
        $db = DB::getInstance();
        $date = new \DateTime();
        $date->modify('+1 day');
        $params = [
            'events_company_id' => 1, //Заменить на исполнителя
            'events_contractor' => 1, //Заменить на параметр сессии
            'date_start' =>  $date->format('Y-m-d'),
            'events_status' => 1,
        ];
        //debug($params);
        $result = $db->getAll('SELECT
                                            * 
                                        FROM 
                                            events
                                        WHERE 
                                            events_company_id = ?i 
                                            AND events_contractor = ?i 
                                            AND events_status = ?i 
                                            AND (events_date_start BETWEEN ?s AND ?s)',
                                            $params['events_company_id'],
                                            $params['events_contractor'],
                                            $params['events_status'],
                                            $params['date_start'],
                                            $params['date_start']);
        //debug($result);
        return $result;
    }

    //Список Задач, Звонков, Email оносящиеся к Исполнителю на следующей неджели
    public static function getEventsNextWeekForClient ($events_client_id = false){
        $db = DB::getInstance();
        $date = new \DateTime('next monday');
        $dateFinish = new \DateTime('next monday');
        $dateFinish->modify('+6 day');
        $params = [
            'events_company_id' => 1, //Заменить на параметр сессии
            'events_contractor' => 1, //заменит на исполнителя
            'date_start' =>  $date->format('Y-m-d'),
            'date_finish' => $dateFinish->format('Y-m-d'),
            'events_status' => 1,
        ];
        //debug($params);
        $result = $db->getAll('SELECT
                                    * 
                                FROM 
                                    events
                                WHERE 
                                    events_company_id = ?i 
                                    AND events_contractor = ?i 
                                    AND events_status = ?i 
                                    AND (events_date_start BETWEEN ?s AND ?s)',
                                    $params['events_company_id'],
                                    $params['events_contractor'],
                                    $params['events_status'],
                                    $params['date_start'],
                                    $params['date_finish']);
        //debug($result);
        return $result;
    }

    //Просроченные Задачи, Звонков, Email оносящиеся к Исполнителю
    public static function getEventsOverdueForClient ($events_client_id = false){
        $db = DB::getInstance();
        $date = new \DateTime();
        $date->modify('-1 day');
        $params = [
            'events_company_id' => 1, //Заменить на параметр сессии
            'events_contractor' => 1, //Заменить на исполнителя
            'date_start' =>  '2000-01-01',
            'date_finish' => $date->format('Y-m-d'),
            'events_status' => 1,
        ];
        //debug($params);
        $result = $db->getAll('SELECT
                                * 
                            FROM 
                                events
                            WHERE 
                                events_company_id = ?i 
                                AND events_contractor = ?i 
                                AND events_status = ?i 
                                AND (events_date_start BETWEEN ?s AND ?s)',
                            $params['events_company_id'],
                            $params['events_contractor'],
                            $params['events_status'],
                            $params['date_start'],
                            $params['date_finish']);
        //debug($result);
        return $result;
    }

    //Количество активных Задач, Звонков, Email оносящиеся к Исполнителю
    public function getCountActiveEventsForClients ($events_client_id = false){
        $db = DB::getInstance();
        $params = [
            'events_company_id' => 1, //заменить из сессиии
            'events_contractor' => 1, //заменить на исполнителя
        ];
        //debug($params);
        $result = $db->row('SELECT
                                COUNT(events_id) AS count_events
                            FROM 
                                events
                            WHERE 
                                events_company_id = :events_company_id 
                                AND events_contractor = :events_contractor 
                                AND events_status = 1', $params);
        //debug($result);
        return $result[0]['count_events'];
    }

    /**
     * Задача выполнена
     * @param $events_id - первичный ключ задачи
     */
    public function completeEvents ($events_id) {
        $db = DB::getInstance();
        $params = [
            'events_id' => $events_id,
            'events_company_id' => 1, //Заменить на параметр сессии
            'events_status' => 2,
        ];
        $db->query('UPDATE events SET events_status = :events_status WHERE events_company_id = :events_company_id AND events_id =:events_id', $params);
    }

    /**
     * Задача удалена
     * @param $events_id - первичный ключ задачи
     */
    public static function deleteEvents ($events_id) {
        $db = DB::getInstance();
        $params = [
            'events_id' => $events_id,
            'events_company_id' => 1, //Заменить на параметр сессии
            'events_status' => 0,
        ];
        $db->query('UPDATE events SET events_status = :events_status WHERE events_company_id = :events_company_id AND events_id = :events_id', $params);
    }

    /**
     * Редактирования события
     * @param $params
     */
    public static function editEvent($params) {
        //echo $params;
        $ret = [
            "status" => false,
            "err" => "Ошибка запроса",
            "time" => date("d.m.Y H:i:s"),
            "action" => "",
        ];
//!!!
        $event_id = (int)$params['events_id'];
        $events_company_id = 1;
//!!!
        if ($event_id > 0 AND $events_company_id > 0) {
            $allowed=array('events_client_id',
                'events_type',
                'events_title',
                'events_phone',
                'events_email',
                'events_description',
                'events_date_start',
                'events_time_start');
            $db = DB::getInstance();
            $tmpArray = $db->filterArray($params,$allowed);
            //debug($tmpArray);
            //$ret['d'][]=$tmpArray;
            if ($tmpArray) {
                //$ret['d'][]='123';
                $db->query('UPDATE 
                            events  
                        SET ?u
                        WHERE events_company_id = ?i AND events_id = ?i', $tmpArray, $events_company_id, $event_id);

                //$ret['d'][]=$db->affectedRows();
                if ($db->affectedRows() > 0) {
                    $ret['status'] = true;
                    $ret['err'] = "Обноваление задачи прошло успешно!";
                    $ret['action'] = "edit";
                }
            }
        }

        return $ret;
    }

    /**
     * Добовленимя события
     * @param $values
     * @return array
     */
    public static function addEvent($values) {
        $ret = [
            "status" => false,
            "err" => "Ошибка запроса",
            "time" => date("d.m.Y H:i:s"),
            "action" => "",
            "index" => "",
        ];
        //
        $events_company_id = 1;
        $events_main_company_id = 1;

        if ($events_main_company_id > 0 AND $events_company_id > 0) {
            $db = DB::getInstance();
            $params = array(
                'events_id' => NULL,
                'events_main_company_id' => $events_main_company_id, //заменить из сессии
                'events_company_id' => $events_company_id, //заменить из сессии
                'events_client_id' => $values['events_client_id'],
                'events_type' => $values['events_type'],
                'events_title' => $values['events_title'],
                'events_phone' => $values['events_phone'],
                'events_email' => $values['events_email'],
                'events_description' => $values['events_description'],
                'events_result' => NULL,
                'events_worker_id' => 1, //заменить из сессии
                'events_contractor' => $values['events_contractor'],
                'events_date_start' => $values['events_date_start'],
                'events_time_start' => $values['events_time_start'],
                'events_date_finish' => NULL,
                'events_time_finish' => NULL,
                'events_status' => 1,
            );
            $db->query('INSERT INTO `events` set ?u', $params);
            if ($db->affectedRows() > 0) {
                $ret['status'] = true;
                $ret['err'] = "Задача успешно добавлена!";
                $ret['action'] = "add";
                $ret['index'] = $db->insertId();
            }
        }
        return $ret;
    }

    /**
     * Выборка данных для клиента относительно Компании
     * @return mixed
     */
    public static function getEventsForClients($values = false) {
        $db = DB::getInstance();
        $params = array(
            'events_main_company_id' => 1,
            'events_company_id' => 1,
            'events_worker_id' => 1,
            'events_contractor' => 1,
        );
        $data = $db->getAll('SELECT 
                                  *,
                                  IF (events_date_start < CURDATE(), "yesterday", 
                                      IF(CURDATE() = events_date_start, "today", 
                                        IF( (CURDATE() + INTERVAL 1 DAY) = events_date_start, "tomorrow",
                                              IF((CURDATE() + INTERVAL 2 DAY) = events_date_start, "week", "other")))) as flag
                              FROM 
                                  events 
                              WHERE events_main_company_id = ?i AND events_company_id = ?i OR events_worker_id = ?i OR events_contractor = ?i',
                                $params['events_main_company_id'],
                                $params['events_company_id'],
                                $params['events_worker_id'],
                                $params['events_contractor']);
        return $data;
    }

}


?>