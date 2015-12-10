<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/eloquent.php';

use App\Log;
use App\Helper;

/**
* https://maps.googleapis.com/maps/api/distancematrix/json?c=LS157SS&destinations=LS9 7NN|WF1 4PY|HG3 1RZ|BD4 7DG&mode=driving&units=imperial
*/
Log::msg('Script Started');
// Get all the customers
$customers = DB\Customer::where('postcode', '!=', '')
    ->whereRaw('(depot IS NULL OR depot = 0)')
    ->where('no_results', 0)
    ->limit(1000)
    ->get();

if ($customers) {
    Log::msg('Total number of customers to process: ' . count($customers));
    // Iterate over each customer
    foreach ($customers as $customer) {
        Log::msg('Processing customer: "'. $customer->title . ' ' .$customer->first_name . ' ' . $customer->last_name.'"');

        // Get the latitude/longitude from Google Maps Geocode API for this customer
        $geocode = new App\Geocode(new GuzzleHttp\Client, new Log);

        // Make API request
        Log::msg('Making API request for postcode: "'.$customer->postcode.'"');
        $geocode->makeRequest($customer->postcode);

        // Get the address from the call
        $customer_address = $geocode->getAddressInfo();

        // Proceed only if the customer_address is true
        if ($customer_address) {
            // Update customer info in the database
            Log::msg('Updating customer latitude/longitude now.');
            $customer->latitude = $customer_address['latitude'];
            $customer->longitude = $customer_address['longitude'];
            $customer->save();

            // Get the customer distance to all depots
            $distances = [];
            $sort_distance = [];
            foreach (DB\Depot::all() as $depot) {
                $d = Helper::getDistanceBetweenCordinates(
                    $customer_address['latitude'],
                    $customer_address['longitude'],
                    $depot->latitude,
                    $depot->longitude
                );

                $distances[$depot->id] = [
                    'depot_id' => $depot->id,
                    'depot' => $depot->depot,
                    'depot_postcode' => $depot->postcode,
                    'distance_meters' => $d,
                ];

                // Create sort array for array_multisort
                $sort_distance[$depot->id] = $d;
            }

            // Sort the distances by shortest distance first
            array_multisort($sort_distance, SORT_ASC, $distances);

            // Update customer.depot and depot_distance
            if ($distances && isset($distances[0])) {
                Log::msg('Closest three depots to the customer are: ' . print_r(array_slice($distances, 0, 3), 1));

                Log::msg('Updating customer depot to "' . $distances[0]['depot_id'] . '"');
                $customer->depot = $distances[0]['depot_id'];
                $customer->depot_distance = $distances[0]['distance_meters'];
                $customer->save();
            } else {
                Log::msg('Invalid $distances array: ' . print_r($distances, 1));
            }
        } else {
            Log::msg('No latitude/longitude information found for postcode: ' . $customer->postcode);
            $customer->no_results = 1;
            $customer->save();
        }

        Log::msg('Continue to the next customer ...', true, 1);
        sleep(0.5);
    }
}

Log::msg('Script Ended', true, 1);
