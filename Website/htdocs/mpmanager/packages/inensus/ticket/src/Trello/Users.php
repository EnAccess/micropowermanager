<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 06.09.18
 * Time: 11:14
 */

namespace Inensus\Ticket\Trello;


use Inensus\Ticket\Exceptions\ApiUserNotFound;

class Users
{


    private $api;


    public function __construct(Api $api)
    {
        $this->api = $api;
    }


    public function find($userTag)
    {
        $response = $this->api->request('members', $userTag, $this->api::GET, [], ['http_errors' => false]);


        // something went wrong
        if ($response->getStatusCode() !== 200) {
            throw new ApiUserNotFound($response->getStatusCode());
        }

        return json_decode((string)$response->getBody());

    }

}