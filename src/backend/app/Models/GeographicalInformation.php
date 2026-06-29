<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\GeographicalInformationFactory;
use GeoJson\Feature\Feature;
use GeoJson\Geometry\Point;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Class GeographicalInformation.
 *
 * @property      int         $id
 * @property      int         $owner_id
 * @property      string      $owner_type
 * @property      object|null $geo_json
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Model       $owner
 */
class GeographicalInformation extends BaseModel {
    /** @use HasFactory<GeographicalInformationFactory> */
    use HasFactory;

    protected $table = 'geographical_informations';

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo {
        return $this->morphTo();
    }

    protected function casts(): array {
        return [
            'geo_json' => 'object',
        ];
    }

    /**
     * Build a GeoJSON Point Feature from a latitude/longitude pair, matching the Cluster model's
     * `geo_json` so the codebase has one GeoJSON convention. GeoJSON follows RFC 7946, which orders
     * coordinates as [longitude, latitude]; most humans use the ISO 6709 latitude/longitude order.
     * This is the single place that ordering is encoded.
     *
     * The empty properties array serializes to a `{}` object (per RFC 7946); the `geo_json` cast
     * `json_encode`s the Feature on save and reads it back as a `stdClass`.
     */
    public static function makePoint(float $latitude, float $longitude): Feature {
        return new Feature(new Point([$longitude, $latitude]), []);
    }

    /**
     * Build a GeoJSON Point Feature from an inbound "latitude,longitude" string (the format sent by
     * the UI forms and several third-party meter APIs). Returns null for blank/malformed input.
     */
    public static function pointFromString(?string $latitudeLongitude): ?Feature {
        if ($latitudeLongitude === null || trim($latitudeLongitude) === '') {
            return null;
        }

        [$latitude, $longitude] = array_pad(explode(',', $latitudeLongitude, 2), 2, null);

        if (!is_numeric(trim((string) $latitude)) || !is_numeric(trim((string) $longitude))) {
            return null;
        }

        return self::makePoint((float) $latitude, (float) $longitude);
    }

    /**
     * Read the [latitude, longitude] of this point, or [null, null] when no valid geometry is set.
     * Reverses the GeoJSON [longitude, latitude] ordering for callers that think in lat/lon.
     *
     * @return array{0: float|null, 1: float|null}
     */
    public function latitudeLongitude(): array {
        $coordinates = $this->geo_json->geometry->coordinates ?? null;

        if (!is_array($coordinates) || count($coordinates) < 2) {
            return [null, null];
        }

        return [(float) $coordinates[1], (float) $coordinates[0]];
    }
}
