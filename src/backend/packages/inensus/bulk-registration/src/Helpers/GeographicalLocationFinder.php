<?php

namespace Inensus\BulkRegistration\Helpers;

use Spatie\Geocoder\Geocoder;

class GeographicalLocationFinder {
    public function getCoordinatesGivenAddress($address) {
        $client = new \GuzzleHttp\Client();
        $geocoder = new Geocoder($client);
        $geocoder->setApiKey(config('bulk-registration.geocoder.key'));
        $geocoder->setCountry(config('bulk-registration.geocoder.country', 'US'));

        return $geocoder->getCoordinatesForAddress($address);
    }
}
