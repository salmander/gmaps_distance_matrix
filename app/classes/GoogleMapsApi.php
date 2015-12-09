<?php

namespace App;

interface GoogleMapsApi {

    public function getApiType();

    public function constructQuery($query);

    public function makeRequest();


}
