<?php

namespace App;

class GoogleMaps {
    protected $url;
    protected $guzzle;
    protected $type;
    protected $unit;
    protected $available_types;
    protected $available_units;
    protected $responses;
    protected $string_delimiter;
    protected $log;
    protected $api_key;
    protected $request_query_parameters;

    public function __construct($guzzle, Log $log)
    {
        $this->url = 'https://maps.googleapis.com/maps/api/';
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

    public function setType($type = 'json')
    {
        if (!in_array($type, $this->available_types)) {
            return trigger_error('Unsupported return type. Choose "xml" or "json".', E_USER_ERROR);
        }

        $this->type = $type;
    }

    public function setUnit($unit = 'imperial')
    {
        if (!in_array($unit, $this->available_units)) {
            return trigger_error('Unsupported unit type. Choose "imperial" (miles) or "metric" (km).', E_USER_ERROR);
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
        // url/<distancematrix|geocoding>/<json|xml>/
        return rtrim($this->url, '/') . '/' .
            $this->getApiType() . '/' .
            $this->getType();
    }

    public function getRequestQueryParameters()
    {
        if ($this->request_query_parameters == null) {
            // Throw error
            return trigger_error('Unknown Request Query Parameters');
        }

        return $this->request_query_parameters;
    }

    protected function request()
    {
        $query_paramerters = $this->getRequestQueryParameters();

        $this->log->msg('Query parameters for the request are: ' . print_r($query_paramerters, 1));

        $response = $this->guzzle->request('GET', $this->getURL(), [
            'query' => $query_paramerters,
        ]);

        $this->log->msg('Response received from the server.');

        // Check if a valid response is received from the server
        if (!$this->validateResponse($response)) {
            return false;
        }

        return $response;
    }

    public function validateResponse($response)
    {
        $this->log->msg('Validating response...');
        if ($response_obj = json_decode((string)$response->getBody(), true)) {
            if (isset($response_obj['status']) && $response_obj['status'] == 'OK') {
                $this->log->msg('Valid response.');
                return true;

            }
        }

        $this->log->msg('Invalid response: ' . print_r((string)$response->getBody(), 1));

        return false;
    }

    public function getResponseBody()
    {
        // Check if we have valid responses
        $this->validateResponses();

        $body = [];

        foreach ($this->responses as $response) {
            $body[] = (string)$response->getBody();
        }

        return $body;
    }

    public function validateResponses()
    {
        if ($this->responses > 0) {
            return true;
        }

        $this->log->msg('Nothing in responses array.');

        throw_error('No valid responses', E_USER_ERROR);

        return false;
    }

    public function getResponseBodyAsArray()
    {
        // Check if we have valid responses
        $this->validateResponses();

        $body = [];

        foreach ($this->responses as $response) {
            $body[] = json_decode((string)$response->getBody(), true);
        }

        return $body;
    }
}
