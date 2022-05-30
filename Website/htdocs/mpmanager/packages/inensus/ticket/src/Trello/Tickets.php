<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 23.08.18
 * Time: 17:10
 */

namespace Inensus\Ticket\Trello;


use App\Exceptions\TrelloAPIException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use function json_decode;

class Tickets
{
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function closeTicket(string $ticketId)
    {
        try {
            $request = $this->api->request('cards', $ticketId, $this->api::PUT, ['closed' => 'true']);

            return json_decode($request->getBody());
        }catch (GuzzleException $e){
            throw new TrelloAPIException($e->getMessage());
        }

    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function createTicket(array $params = [])
    {
        if (!array_key_exists('idList', $params) || !array_key_exists('name', $params)) {
            throw new Exception('7rjhfgjvkwerlhtuio4hgjkednfs');
        }

        try {
            $request = $this->api->request('cards', null, $this->api::POST, $params);

            return json_decode($request->getBody());
        } catch (GuzzleException $e) {

            throw new TrelloAPIException($e->getMessage());
        }

    }

    public function get($ticketId)
    {
        try {
            $request = $this->api->request('cards', $ticketId, $this->api::GET, ['fields' => 'all']);

            return json_decode($request->getBody());
        } catch (GuzzleException $e) {

            throw new TrelloAPIException($e->getMessage());
        }
    }

    public function actions($ticketId)
    {
        try {
            $request = $this->api->request('cards', $ticketId . '/actions', $this->api::GET, []);

            return json_decode($request->getBody());
        } catch (GuzzleException $e) {
            throw new TrelloAPIException($e->getMessage());
        }

    }

}
