<?php

namespace DB;

class Depot extends \Illuminate\Database\Eloquent\Model {

    /**
     CREATE TABLE `depots` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `depot` varchar(45) DEFAULT NULL,
      `address1` varchar(125) DEFAULT NULL,
      `address2` varchar(125) DEFAULT NULL,
      `address3` varchar(125) DEFAULT NULL,
      `address4` varchar(125) DEFAULT NULL,
      `postcode` varchar(125) DEFAULT NULL,
      `telephone` varchar(125) DEFAULT NULL,
      `longitude` varchar(45) DEFAULT NULL,
      `latitude` varchar(45) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=latin1;
    */

    public static function getDepotPostcodes($delimiter = '|')
    {
        $postcodes = '';

        foreach (self::limit(90)->get(['postcode'])->toArray() as $p) {
            $postcodes .= $p['postcode'] . $delimiter;
        }

        return $postcodes;
    }
}
