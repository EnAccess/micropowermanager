<?php

return [
    /*
     * Result codes Calin's token API answers with when the login was accepted but the meter is unknown.
     * The token API has no endpoint that only checks a login, so "Check connection" vends to a meter
     * that cannot exist and treats exactly these codes as a working login.
     * Any other code is reported as a failure, so an unrecognised answer never looks like success.
     * Comma-separated, e.g. CALIN_METER_UNKNOWN_METER_RESULT_CODES=7,8
     */
    'unknown_meter_result_codes' => array_map(
        intval(...),
        array_filter(explode(',', (string) env('CALIN_METER_UNKNOWN_METER_RESULT_CODES', ''))),
    ),
];
