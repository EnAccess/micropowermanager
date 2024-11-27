<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\ConnectionType;
use App\Models\SubConnectionType;
use Inensus\SteamaMeter\Models\SteamaUserType;

class SteamaUserTypeService {
    private $connectionType;
    private $userType;
    private $subConnectionType;

    public function __construct(
        ConnectionType $connectionTypeModel,
        SteamaUserType $userTypeModel,
        SubConnectionType $subConnectionType,
    ) {
        $this->connectionType = $connectionTypeModel;
        $this->userType = $userTypeModel;
        $this->subConnectionType = $subConnectionType;
    }

    /**
     * This function uses one time on installation of the package.
     */
    public function createUserTypes($tariff) {
        $connectionTypes = [
            'NA' => 'Not Specified',
            'RES' => 'Residential',
            'BUS' => 'Business',
            'INS' => 'Institution',
        ];
        foreach ($connectionTypes as $key => $value) {
            $connectionType = $this->connectionType->newQuery()->where('name', $value)->first();

            if (!$connectionType) {
                $connectionType = $this->connectionType->newQuery()->create([
                    'name' => $value,
                ]);
            }

            $userType = $this->userType->newQuery()->where('name', $value)->first();
            if (!$userType) {
                $this->userType->newQuery()->create([
                    'mpm_connection_type_id' => $connectionType->id,
                    'name' => $value,
                    'syntax' => $key,
                ]);
            }

            $subConnectionType = $this->subConnectionType->newQuery()->where('name', $value)->first();
            if (!$subConnectionType) {
                $this->subConnectionType->newQuery()->create([
                    'name' => $value,
                    'connection_type_id' => $connectionType->id,
                    'tariff_id' => $tariff->id,
                ]);
            }
        }
    }
}
