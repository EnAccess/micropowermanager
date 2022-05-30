<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 20.08.18
 * Time: 10:07
 */

namespace Inensus\Ticket\Trello;

use App\Services\TicketSettingsService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\ResponseInterface;

class Api
{
    public const GET = 0;
    public const POST = 1;
    public const DELETE = 2;
    public const PUT = 3;
    const TEST_API_TOKEN = 'ac3c02a90532592970c1b7f193a8c274c4ce28dff489cc237a0e8be95c37799f';
    const TEST_API_KEY = 'd913c290781ead9437b90b54ec5c4f08';
    const API_URL = 'https://api.trello.com/1';


    public function __construct(
        private Client $httpClient,
        private TicketSettingsService $ticketSettingsService
    ) {
    }

    public function request(
        string $resource,
        string $action = null,
        int $type = self::GET,
        array $params = [],
        array $options = []
    ): ResponseInterface {
        $environment = app()->environment();
        //building the url
        $url = sprintf('%s/%s/%s', self::API_URL, $resource, $action ?? '');
        $params['key'] = $this->isProduction() ? $this->ticketSettingsService->get()->api_key : self::TEST_API_KEY;
        $params['token'] =
            $this->isProduction() ? $this->ticketSettingsService->get()->api_token : self::TEST_API_TOKEN;
        switch ($type) {
            case self::GET:
                $url .= '?' . Query::build($params);
                $request = $this->httpClient->get($url, $options);
                break;
            case self::POST:
                //merge post data and options
                $options = array_merge(['form_params' => $params], $options);
                $request = $this->httpClient->post($url, $options);
                break;
            case self::PUT:
                $request = $this->httpClient->put($url, [
                    'form_params' => $params,
                ]);
                break;
            case self::DELETE:
                $request = $this->httpClient->delete($url, [
                    'form_params' => $params,
                ]);
                break;
        }
        return $request;
    }

    private function isProduction(): bool
    {
        $environment = app()->environment();
        if ($environment === 'testing' || $environment === 'development') {

            return false;
        }

        return true;
    }
}

