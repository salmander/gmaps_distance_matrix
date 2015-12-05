<?php

namespace App;

class GMaps {

    private $url;
    private $guzzle;
    private $type;
    private $unit;
    private $available_types;
    private $available_units;
    private $origins;
    private $destinations;
    private $mode;

    public function __construct($guzzle)
    {
        $this->url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=&destinations=&mode=driving&units=imperial';
        $this->guzzle = $guzzle;
        $this->available_types = [
            'json',
            'xml',
        ];
        $this->available_units = [
            'imperial',
            'metric',
        ];
        $mode = 'driving';
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

    public function setOrigins($origins)
    {
        $this->origins = $origins;
    }

    public function setDestinations($destinations)
    {
        $this->destinations = $destinations;
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
        if ($this->url == null) {
            $this->url = 'https://maps.googleapis.com/maps/api/distancematrix/' .
                $this->getType();
        }

        return $this->url;
    }

    public function getOrigins()
    {
        if ($this->origins == null) {
            return trigger_error('Set origins by calling setOrigins().', E_USER_ERROR);
        }

        return $this->origins;
    }

    public function getDestinations()
    {
        if ($this->destinations == null) {
            return trigger_error('Set origins by calling setDestinations().', E_USER_ERROR);
        }
        
        return $this->destinations;
    }

    public function getResponse($origins = null, $destinations = null)
    {
        /**
        $client->request('GET', 'http://httpbin.org', [
            'query' => ['foo' => 'bar']
        ]);
        */

        if ($origins == null) {
            $origins = $this->getOrigins();
        }

        if ($destinations == null) {
            $destinations = $this->getDestinations();
        }

        $response = $this->guzzle->request('GET', $this->getURL(), [
            'query' => [
                'origins' => $origins,
                'destinations' => $destinations,
            ]
        ]);

        // Cast body as string
        return (string)$response->getBody();
    }


}
