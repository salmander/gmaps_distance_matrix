<?php

namespace App;

class GoogleMaps {
    private $url;
    private $guzzle;
    private $type;
    private $unit;
    private $available_types;
    private $available_units;
    private $responses;
    private $string_delimiter;
    private $log;
    private $api_key;


    public function __construct($guzzle, Log $log)
    {
        $this->url = 'https://maps.googleapis.com/maps/api/distancematrix/';
        $this->log = $log;
        $this->guzzle = $guzzle;
        $this->available_types = [
            'json',
            'xml',
        ];

        $this->available_units = [
            'imperial',
            'metric',
        ];

        $this->string_delimiter = '|';
    }

    public function setLog(App\Log $log)
    {
        $this->log = $log;
    }

    public function setType($type = 'JSON')
    {
        if (!in_array($type, $this->available_types)) {
            return trigger_error('Unsupported return type. Choose "XML" or "JSON".');
        }

        $this->type = $type;
    }

    public function setUnit($unit = 'imperial')
    {
        if (!in_array($unit, $this->available_units)) {
            return trigger_error('Unsupported unit type. Choose "imperial" (miles) or "metric" (km).');
        }

        $this->unit = $unit;
    }

    public function setApiKey($key = null)
    {
        if ($key != null) {
            $this->api_key = $key;
        } else if (defined('GMAPS_API_KEY')) {
            $this->api_key = GMAPS_API_KEY;
        }
    }

    public function getType()
    {
        if ($this->type == null) {
            $this->setType();
        }

        return $this->type;
    }

    public function getUnit()
    {
        if ($this->unit == null) {
            $this->setUnit();
        }

        return $this->unit;
    }

    public function getURL()
    {
        return $this->url . $this->getType() . '/';
    }
}
