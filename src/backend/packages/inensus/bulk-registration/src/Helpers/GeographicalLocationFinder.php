<?php

namespace Inensus\BulkRegistration\Helpers;

use GuzzleHttp\Client;
use Spatie\Geocoder\Geocoder;

class GeographicalLocationFinder {
    public function getCoordinatesGivenAddress(string $address_string) {
        $client = new Client();
        $geocoder = new Geocoder($client);
        $geocoder->setApiKey(config('bulk-registration.geocoder.key'));
        $geocoder->setCountry(config('bulk-registration.geocoder.country', 'US'));

        return $geocoder->getCoordinatesForAddress($address_string);
    }
}
