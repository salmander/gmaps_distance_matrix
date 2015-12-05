<?php

namespace DB;

class Customer extends \Illuminate\Database\Eloquent\Model {

    /**
    * Remove spaces from postcode
    */
    public function getPostcodeAttribute($value)
    {
        return str_replace(' ', '', $value);
    }

    /**
    CREATE TABLE `customers` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `title` varchar(11) NOT NULL DEFAULT '',
      `first_name` varchar(65) NOT NULL DEFAULT '',
      `last_name` varchar(65) NOT NULL DEFAULT '',
      `address1` varchar(125) NOT NULL DEFAULT '',
      `address2` varchar(125) DEFAULT NULL,
      `address3` varchar(125) DEFAULT NULL,
      `address4` varchar(125) DEFAULT '',
      `postcode` varchar(11) NOT NULL DEFAULT '',
      `type` varchar(25) NOT NULL DEFAULT '',
      `email` varchar(125) NOT NULL DEFAULT '',
      `mobile` varchar(25) NOT NULL DEFAULT '',
      `allow_post` tinyint(1) unsigned NOT NULL,
      `allow_text` tinyint(1) unsigned NOT NULL,
      `allow_email` tinyint(1) unsigned NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
    */
}
