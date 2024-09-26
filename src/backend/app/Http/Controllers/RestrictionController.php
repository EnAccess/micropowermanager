<?php

namespace App\Http\Controllers;

use App\Exceptions\PurchaseNotProcessable;
use App\Models\Restriction;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class RestrictionController extends Controller
{
    /**
     * @var Restriction
     */
    private $restriction;
    /**
     * @var Client
     */
    private $httpClient;

    public function __construct(Restriction $restriction, Client $httpClient)
    {
        $this->restriction = $restriction;
        $this->httpClient = $httpClient;
    }

    /**
     * @return Response
     */
    public function store(Request $request, Response $response): Response
    {
        $productId = $request->input('product_id');
        $token = $request->input('token');
        $type = $request->input('type');
        $url = 'https://stripe.micropowermanager.com/api/mpm/checkPurchasing';
        try {
            $validation = $this->httpClient->request(
                'POST',
                $url,
                [
                    'json' => [
                        'product_id' => $productId,
                        'type' => $type,
                        'token' => $token,
                    ],
                    'headers' => [
                        'mpm-secret' => '22]Qq&e5[2FYu\'t{',
                    ],
                ]
            );

            if ($validation->getStatusCode() !== 200) {
                return $response->setContent('validation failed')->setStatusCode(409);
            }
        } catch (RequestException $e) {
            if (
                $e->getResponse()->getStatusCode() === 400
                && (string) $e->getResponse()->getBody() === 'Invalid code.'
            ) {
                $response->setContent('Invalid token');
            }

            return $response->setStatusCode(409);
        } catch (GuzzleException $e) {
            Log::critical(
                'Token validation failed ',
                ['purchase_token' => $token, 'id' => '896789ghjk79gjklig6778tf']
            );

            return $response->setStatusCode(409);
        }

        if ($type === 'mini-grid') {
            $target = 'enable-data-stream';
            $toAdd = 1;
        } elseif ($type === 'maintenance') {
            $target = 'maintenance-user';
            $toAdd = 5;
        } else {
            Log::critical(
                'Unknown type of purchase ',
                ['purchase_token' => $token, 'id' => '43edui4ed09rdkceqw0s289']
            );

            return $response->setStatusCode(409);
        }

        try {
            $this->updateRestriction($target, $toAdd);

            return $response->setStatusCode(201);
        } catch (PurchaseNotProcessable $e) {
            Log::critical(
                'Purchase is not processable',
                ['purchase_token' => $token, 'id' => '48fkj24ofdhjkl4fhlsqjkl']
            );

            return $response->setStatusCode(409);
        }
    }

    /**
     * @param     $target
     * @param int $toAdd
     *
     * @return void
     *
     * @throws PurchaseNotProcessable
     */
    private function updateRestriction(string $target, int $toAdd = 1): void
    {
        try {
            $restriction = $this->restriction->where('target', $target)->firstOrFail();
            $restriction->limit += $toAdd;
            $restriction->update();
        } catch (ModelNotFoundException $exception) {
            throw new PurchaseNotProcessable('not found a restriction ');
        }
    }
}
