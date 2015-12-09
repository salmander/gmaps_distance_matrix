<?php

namespace App;

class DistanceMatrix {

    private $origins;
    private $destinations;
    private $mode;
    private $destinations_limit;

    public function __construct($guzzle, Log $log)
    {
        parent::__construct($guzzle, $log);

        $this->mode = 'driving';
        $this->destinations_limit = 80;
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
            Log::msg('Constructing query parameters for making GMaps API call');

            $query = [
                'units' => $this->getUnit(),
                'origins' => $origins,
                'destinations' => $d,
            ];

            // Check if api key is set
            if ($this->getApiKey()) {
                // Add api key to the query strings
                $query['key'] = $this->getApiKey();
            }

            Log::msg('Query strings for this request: ' . print_r($query, 1));
            $response = $this->guzzle->request('GET', $this->getURL(), [
                'query' => $query,
            ]);

            Log::msg('Response received from the server.');

            // Check if a valid response is received from the server
            if (!$this->validateResponse($response)) {
                Log::msg('Invalid response... skipping to next.');

                // Between each request we need to wait for 1/2 a second
                sleep(0.5);
                continue;
            }

            Log::msg('Adding response to the `responses` array.');
            $this->responses[] = $response;

            // Between each request we need to wait for 1/2 a second
            sleep(0.5);
        }

        return $this->getResponseBody();
    }

    public function validateResponse($response)
    {
        if ($response_obj = json_decode((string)$response->getBody(), true)) {
            if (isset($response_obj['status']) && $response_obj['status'] == 'OK') {
                return true;
            }
        }

        return false;
    }

    public function getResponseBody()
    {
        $body = [];

        if (count($this->responses) > 0) {
            foreach ($this->responses as $response) {
                $body[] = (string)$response->getBody();
            }
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
        if (count($this->responses) < 1) {
            Log::msg('No successful response has been received from the server yet.');
            return [];
        }

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

        return $data;
    }

    public function getNearestDestination()
    {
        if ($data = $this->getResultsSortedByDistance()) {
            return $data[0];
        }
    }


}
