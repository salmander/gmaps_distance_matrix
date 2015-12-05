<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/eloquent.php';

/**
* https://maps.googleapis.com/maps/api/distancematrix/json?c=LS157SS&destinations=LS9 7NN|WF1 4PY|HG3 1RZ|BD4 7DG&mode=driving&units=imperial
*/

// Get all the depot postcodes in pipe delimitted string format
$depots_postcodes = DB\Depot::getDepotPostcodes();

//echo count(explode('|', $depots_postcodes)); exit;

// Get all the customers
$customers = DB\Customer::where('postcode', '!=', '')->get();

if ($customers) {
    // Instantiate new GMaps object
    $gmaps = new App\GMaps(new GuzzleHttp\Client);

    // The destinations are pipe delimitted depots
    $gmaps->setDestinations($depots_postcodes);

    // Iterate over each customer
    foreach ($customers as $customer) {
        // Call the GMaps API
        $response = $gmaps->getResponse($customer->postcode);

        print_r($response);

        break;
    }
}
