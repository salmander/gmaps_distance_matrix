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
    private $responses;
    private $destinations_limit;
    private $string_delimiter;
    private $log;

    public function __construct($guzzle, Log $log)
    {
        $this->url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=&destinations=&mode=driving&units=imperial';
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

        $this->mode = 'driving';
        $this->destinations_limit = 100;
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

    public function getDestinations($limit = false, $delimiter = '|')
    {
        if ($this->destinations == null) {
            return trigger_error('Set origins by calling setDestinations().', E_USER_ERROR);
        }

        if (!$limit) {
            return $this->destinations;
        }

        return $this->makeChunks($this->destinations, $delimiter, $limit);
    }

    private function makeChunks($string, $delimiter = '|', $limit = 100)
    {
        // Make an array from string
        $chunks = array_chunk(explode($delimiter, $string), $limit);

        $output = array_map(function($a) use ($delimiter) {
            return implode($delimiter, $a);
        }, $chunks);

        return $output;
    }

    public function getResponse($origins = null, $destinations = null)
    {
        /**
        * $client->request('GET', 'http://httpbin.org', [
        *     'query' => ['foo' => 'bar']
        * ]);
        */

        if ($origins != null) {
            $this->setOrigins($origins);
        }

        if ($destinations != null) {
            $this->setDestinations($destinations);
        }

        $destinations = $this->getDestinations($this->destinations_limit, $this->string_delimiter);

        foreach ($destinations as $d) {
            // Make API call
            Log::msg('Making API call for postcodes: ' . $d . PHP_EOL);
            $response = $this->guzzle->request('GET', $this->getURL(), [
                'query' => [
                    'unit' => $this->getUnit(),
                    'origins' => $origins,
                    'destinations' => $d,
                ]
            ]);

            Log::msg('Adding response to the `responses` array.' . PHP_EOL);

            $this->responses[] = $response;
            sleep(1);
        }

        return $this->getResponseBody();
    }

    public function getResponseBody()
    {
        $body = [];

        //Log::msg( 'responses array: ' . print_r($this->responses, 1) . PHP_EOL;

        foreach ($this->responses as $response) {
            $body[] = (string)$response->getBody();
        }

        return $body;
    }

    public function getResultsAsArray()
    {
        /**
        * Output Array
        * (
        *     [origin_address] => Ash Vale, Aldershot, Surrey GU12 5EN, UK
        *     [destination_address] => Wakefield, Wakefield, West Yorkshire WF1 4PY, UK
        *     [distance_text] => 333 km
        *     [distance_meters] => 332835
        *     [duration_text] => 3 hours 47 mins
        *     [duration_seconds] => 13631
        *     [status] => OK
        * )
        */
        $responses = [];

        foreach ($this->responses as $response) {
            $responses[] = json_decode((string)$response->getBody(), true);
        }

        $addresses = [];
        foreach ($responses as $r) {
            foreach ($r['origin_addresses'] as $origin_key => $origin_add) {
                foreach ($r['destination_addresses'] as $destination_key => $destination_add) {
                    // Check if there is a correct address
                    if ($r['rows'][$origin_key]['elements'][$destination_key]['status'] == 'NOT_FOUND') {
                        continue;
                    }

                    $addresses[] = [
                        'origin_address' => $origin_add,
                        'origin_postcode' => $this->getPostcode($origin_add),
                        'destination_address' => $destination_add,
                        'destination_postcode' => $this->getPostcode($destination_add),
                        'distance_text' => $r['rows'][$origin_key]['elements'][$destination_key]['distance']['text'],
                        'distance_meters' => $r['rows'][$origin_key]['elements'][$destination_key]['distance']['value'],
                        'duration_text' => $r['rows'][$origin_key]['elements'][$destination_key]['duration']['text'],
                        'duration_seconds' => $r['rows'][$origin_key]['elements'][$destination_key]['duration']['value'],
                        'status' => $r['rows'][$origin_key]['elements'][$destination_key]['status'],
                    ];
                }
            }
        }

        return $addresses;
    }

    /**
    * Get the postcode from the address
    * E.g. Ash Vale, Aldershot, Surrey GU12 5EN, UK
    * postcode: GU12 5EN
    */
    private function getPostcode($str = '')
    {
        if (preg_match('/([a-zA-Z]{1,2}[\d]{1,2}\s[0-9a-zA-Z]{1,4}),\suk/i', $str, $matches)) {
            return $matches[1];
        }

        return false;
    }

    public function getResultsSortedByDistance()
    {
        $data = $this->getResultsAsArray();

        // Create an array of column 'distance_meters'
        $distance = [];
        foreach ($data as $key => $row) {
            $distance[$key] = $row['distance_meters'];
        }

        // Sort the array
        array_multisort($distance, SORT_ASC, $data);

        Log::msg('SORTED: ' . print_r($data, 1) . PHP_EOL);

        return $data;
    }

    public function getNearestDestination()
    {
        if ($data = $this->getResultsSortedByDistance()) {
            return $data[0];
        }
    }


}
