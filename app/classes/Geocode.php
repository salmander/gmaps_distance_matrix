<?php

namespace App;

class Geocode extends GoogleMaps implements GoogleMapsApi {

    private $address;

    public function setAddress($address = null)
    {
        if ($address == null) {
            return trigger_error('Address cannot be null', E_USER_ERROR);
        }

        $this->address = $address;
    }

    public function getApiType()
    {
        return 'geocode';
    }

    public function getAddress()
    {
        if ($this->address == null) {
            $this->setAddress();
        }

        return $this->address;
    }


    public function makeRequest($address = null)
    {
        // Set the address
        $this->setAddress($address);

        // Construct query parameters for this call
        $this->constructQuery(['address' => $this->getAddress()]);

        // Make API request
        if ($response = $this->request()) {
            $this->log->msg('Adding response to the `responses` array.');
            $this->responses[] = $response;
        }

        return $this->responses;

    }

    public function getAddressInfo()
    {
        $body = $this->getResponseBodyAsArray();

        if (count($body) > 0) {
            $address = [
                'formatted_address' => $body[0]['results'][0]['formatted_address'],
                'latitude' => $body[0]['results'][0]['geometry']['location']['lat'],
                'longitude' => $body[0]['results'][0]['geometry']['location']['lng'],
                'location_type' => $body[0]['results'][0]['geometry']['location_type'],
            ];

            $this->log->msg('Formatted address: ' . print_r($address, 1));

            return $address;
        }

        return false;
    }

}
