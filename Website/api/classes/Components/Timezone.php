<?php

namespace Sort1API\Components;

use \DateTime;
use \DateTimeZone;
use \IntlTimeZone;
use \Locale;

class Timezone {

    /**
     * Gets timezone abbreviation from the timezone identifier.
     *
     * @param string $identifier Timezone identifier (e.g. Europe/Moscow)
     *
     * @return string Abbreviation (e.g. MSK)
     */
    public static function getTimeZoneAbbreviation($identifier)
    {
        $dt = new DateTime();
        $dtz = new DateTimeZone($identifier);

        return $dt->setTimezone($dtz)->format('T');
    }

    /**
     * Sorts list of timezones by country first and by id then.
     *
     * @param array $list List of timezones
     */
    private static function sortList(array &$list)
    {
        usort($list, function ($a, $b) {
            if ($a['identifier'] > $b['identifier']) {
                return 1;
            }
            if ($a['identifier'] < $b['identifier']) {
                return -1;
            }
            if ($a['country'] > $b['country']) {
                return 1;
            }
            if ($a['country'] < $b['country']) {
                return -1;
            }
            if ($a['id'] > $b['id']) {
                return 1;
            }
            if ($a['id'] < $b['id']) {
                return -1;
            }
            return 0;
        });
    }

    /**
     * Gets localized list of timezones.
     *
     * @param string $locale Locate to get list for (e.g. en_US)
     *
     * @return array List of timezones
     */
    public static function getList($locale = 'en_US')
    {
        $tzs = array();

        $identifiers = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, "RU" );;
        //$tzs[]=$identifiers;
        foreach ($identifiers as $identifier) { 
            // create date time zone from identifier
            $dtz = new \DateTimeZone($identifier);

            // create timezone from identifier
            $tz = \IntlTimeZone::createTimeZone($identifier);
            //echo "tz=".print_r($tz,true)."\n";
            // get two-letter country code
            $countryCode = $dtz->getLocation()['country_code'];

            // get country name from country code
            $country = \Locale::getDisplayName('_' . $countryCode, $locale);

            // replace [] with ()
            $country = str_replace(['[', ']'], ['(', ')'], $country);

            // time offset
            $offset = $dtz->getOffset(new DateTime());
            $sign = ($offset < 0) ? '-' : '+';
            $row = [
                'identifier' => $identifier,
                'id' => $tz->getDisplayName(false, 3, $locale),
                'country' => $country,
                'code' => self::getTimeZoneAbbreviation($identifier),
                'offset' => $sign . date('H:i', $offset)
            ];
            //echo "row=".print_r($row,true)."\n";
            // if IntlTimeZone is unaware of timezone ID, use identifier as name
            if ($tz->getID() == 'Etc/Unknown') {
                $identifier = explode('/', $identifier);
                $row['id'] = array_pop($identifier);
            }

            $tzs[count($tzs)] = $row;
            //echo "tzs=".print_r($tzs,true)."\n";
        }

        //self::sortList($tzs);
        //echo "tzs=".print_r($tzs,true)."\n";
        return $tzs;
    }
}

