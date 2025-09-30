<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentPerformanceMetricsResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'metrics' => $this->resource['metrics'],
            'top_agents' => $this->resource['top_agents'],
            'period' => $this->resource['period'],
        ];
    }

    /**
     * Customize the response for the resource.
     *
     * @param Request                       $request
     * @param JsonResponse $response
     *
     * @return void
     */
    public function withResponse($request, $response): void {
        $data = $response->getData(true);

        $responseData = [
            'data' => [
                'metrics' => $data['data']['metrics'] ?? null,
                'top_agents' => $data['data']['top_agents'] ?? [],
                'period' => $this->resource['period'],
            ],
            'status' => 'success',
        ];

        $response->setData($responseData);
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function with($request): array {
        return [
            'status' => 'success',
        ];
    }
}
