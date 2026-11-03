<?php

namespace App\Plugins\StsMeterVend\Http\Controllers;

use App\Events\NewLogEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Meter\Meter;
use App\Plugins\StsMeterVend\Http\Clients\StsApiClient;
use App\Plugins\StsMeterVend\Http\Requests\ImportStsKeysRequest;
use App\Plugins\StsMeterVend\Http\Resources\StsManagedMeterResource;
use App\Plugins\StsMeterVend\Services\StsMeterKeyService;
use Illuminate\Http\Request;

class StsMeterVendController extends Controller {
    public function __construct(
        private StsApiClient $client,
        private StsMeterKeyService $keyService,
    ) {}

    public function status(): ApiResource {
        return ApiResource::make($this->client->serviceInfo());
    }

    public function importKeys(ImportStsKeysRequest $request): ApiResource {
        $result = $this->keyService->importCsv($request->file('csv'));

        event(new NewLogEvent([
            'user_id' => auth('api')->user()->id,
            'affected' => auth('api')->user(),
            'action' => "Imported {$result['imported']} STS decoder key(s), skipped ".count($result['skipped']).'.',
        ]));

        return ApiResource::make($result);
    }

    public function meters(Request $request): ApiResource {
        return ApiResource::make(
            $this->keyService->paginateManagedMeters($request->integer('per_page', 15))
                ->through(fn (Meter $meter): StsManagedMeterResource => StsManagedMeterResource::make($meter)),
        );
    }
}
