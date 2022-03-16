<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 20.08.18
 * Time: 10:07
 */
namespace Inensus\Ticket\Trello;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\ResponseInterface;

class Api
{
    public const GET = 0;
    public const POST = 1;
    public const DELETE = 2;
    public const PUT = 3;
    const APITOKEN = '9818ff631bd5a6cffa9d224675e1eb77de7df01f3ccaa92776d7d663a25c3c8f';
    const APIKEY = '0d9d80dbc26ccf55b5c605d910d9f27c';
    const APIURL = 'https://api.trello.com/1';
    private $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function request(
        string $resource,
        string $action = null,
        int $type = self::GET,
        array $params = [],
        array $options = []
    ): ResponseInterface {
        //building the url
        $url = sprintf('%s/%s/%s', self::APIURL, $resource, $action ?? '');
        $params['key'] = self::APIKEY;
        $params['token'] = self::APITOKEN;
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
}

