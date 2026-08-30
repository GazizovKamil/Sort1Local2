<?php
$except = [];

$timezonesData = [];
$timezonesIdentifiers = \DateTimeZone::listIdentifiers();

foreach ($timezonesIdentifiers as $timezone) {
    try {
        $formatter = new \IntlDateFormatter(
            "ru_RU",
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            $timezone,
            \IntlDateFormatter::GREGORIAN,
            'ZZZZ'
        );

        if ($formatter) {
            $t10n = $formatter->format(0);
        } else {
            $except[] = 'exception 1 '.$e->getMessage(); // continue;
        }
    } catch (\Throwable $e) {
        $except[] = 'exception 2 '.$e->getMessage(); // continue;
    } catch (\Exception $e) {
        $except[] = 'exception 3 '.$e->getMessage(); // continue;
    }
    preg_match("/GMT\+(\d+):(\d+)/",$t10n,$preg);
    if((int)$preg[1]>2 && (int)$preg[1]<11 && (preg_match("/Asia/",$timezone) || preg_match("/Europe/",$timezone)))
        $timezonesData[$timezone] = $t10n;
}
var_dump($except);

print_r($timezonesData);

/* robust list of timezones */
function get_list_of_timezones($locale) {

    date_default_timezone_set('UTC');

    $identifiers = DateTimeZone::listIdentifiers();
    foreach($identifiers as $i) {
        // create date time zone from identifier
        $dtz = new DateTimeZone($i);
        // create timezone from identifier
        $tz = IntlTimeZone::createTimeZone($i);
        // if IntlTimeZone is unaware of timezone ID, use identifier as name, else use localized name
        if ($tz->getID() === 'Etc/Unknown' or $i === 'UTC') $name = $i;
        else $name =  $tz->getDisplayName(false, 3, $locale);
        // time offset
        $offset = $dtz->getOffset(new DateTime());
        $sign   = ($offset < 0) ? '-' : '+';

        $tzs[] = [
            'code'   => $i,
            'name'   => '(UTC' . $sign . date('H:i', abs($offset)) . ') ' . $name,
            'offset' => $offset,
        ];
    }

//    \yii\helpers\ArrayHelper::multisort($tzs, ['offset', 'name']);

    // sort by offset
//    usort($tzs, function($a, $b){
//        if ($a['offset'] > $b['offset']) {
//            return 1;
//        }
//        elseif ($a['offset'] < $b['offset']) {
//            return -1;
//        }
//        elseif ($a['name'] > $b['name']) {
//            return 1;
//        }
//        elseif ($a['name'] < $b['name']) {
//            return -1;
//        }
//        return 0;
//    });

    return array_column($tzs, 'name', 'code');
}

print_r(get_list_of_timezones("ru_RU"));
?>