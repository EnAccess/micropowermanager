<?php

namespace App\Rules;

use GeoJson\Exception\Exception as GeoJsonException;
use GeoJson\Feature\Feature;
use GeoJson\GeoJson;
use GeoJson\Geometry\Point;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a value is a GeoJSON Point Feature (RFC 7946) with in-range coordinates.
 * Parsing is delegated to the jmikola/geojson library; only the coordinate range check
 * (which the library does not perform) is done here.
 */
class GeoJsonPoint implements ValidationRule {
    public function validate(string $attribute, mixed $value, \Closure $fail): void {
        try {
            $geoJson = GeoJson::jsonUnserialize($value);
        } catch (GeoJsonException) {
            $fail("The $attribute field must be valid GeoJSON.");

            return;
        }

        if (!$geoJson instanceof Feature || !$geoJson->getGeometry() instanceof Point) {
            $fail("The $attribute field must be a GeoJSON Feature with a Point geometry.");

            return;
        }

        [$longitude, $latitude] = $geoJson->getGeometry()->getCoordinates();

        if ($longitude < -180 || $longitude > 180 || $latitude < -90 || $latitude > 90) {
            $fail("The $attribute field coordinates are out of range.");
        }
    }
}
