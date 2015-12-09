<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/eloquent.php';

use App\Log;

/**
* https://maps.googleapis.com/maps/api/distancematrix/json?c=LS157SS&destinations=LS9 7NN|WF1 4PY|HG3 1RZ|BD4 7DG&mode=driving&units=imperial
*/

$geocode = new App\Geocode(new GuzzleHttp\Client, new Log);

$responses = $geocode->makeRequest('LS15 7SS');

print_r($geocode->getAddressInfo());

exit;


// Get all the depot postcodes in pipe delimitted string format
$depot_postcodes = DB\Depot::getDepotPostcodes();

// Get all the customers
$customers = DB\Customer::where('postcode', '!=', '')
    ->whereRaw('(depot IS NULL OR depot = 0)')
    ->get();

if ($customers) {
    // Instantiate new GMaps object
    $gmaps = new App\GMaps(new GuzzleHttp\Client, new Log);

    // The destinations are pipe delimitted depots
    $gmaps->setDestinations($depot_postcodes);

    // Iterate over each customer
    foreach ($customers as $customer) {
        Log::msg('Processing customer: "'. $customer->title . ' ' .$customer->first_name . ' ' . $customer->last_name.'"');

        // Call the GMaps API
        $response = $gmaps->getResponse($customer->postcode);

        // Get nearest depot to the customer
        $nearest_depot = $gmaps->getNearestDestination();
        if ($nearest_depot) {
            Log::msg('Getting the depot model from the database where postcode = "' . $nearest_depot['destination_postcode'] . '"');

            // Get the depot id from the depot postcode
            $depot = DB\Depot::where('postcode', '=', $nearest_depot['destination_postcode'])->first();

            if ($depot) {
                $customer->depot = $depot->id;
                $customer->depot_distance = $nearest_depot['distance_meters'];
                $customer->save();
            }
        } else {
            Log::msg('No depot found for postcode "' . $nearest_depot['destination_postcode'] . '"');
        }

        Log::msg('Continue to the next customer ...', true, 1);

        break;
    }
}
