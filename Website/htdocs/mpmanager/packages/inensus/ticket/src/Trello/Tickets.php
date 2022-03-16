<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 23.08.18
 * Time: 17:10
 */

namespace Inensus\Ticket\Trello;


use Exception;
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
        $request = $this->api->request('cards', $ticketId, $this->api::PUT, ['closed' => 'true']);
        if ($request->getStatusCode() !== 200) {
            throw new Exception('34ufkj390fskljflk4..eoö');
        }
        return json_decode($request->getBody());
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

        $request = $this->api->request('cards', null, $this->api::POST, $params);
        if ($request->getStatusCode() !== 200) {
            throw new Exception('574895hdjfgkgh4398ge.#4u8');
        }
        return json_decode($request->getBody());
    }

    public function get($ticketId)
    {
        try {
            $request = $this->api->request('cards', $ticketId, $this->api::GET, ['fields' => 'all']);
        } catch (Exception $h) {
            return;
        }
        if ($request->getStatusCode() !== 200) {
            throw new Exception('37fj3f893ofjkgl44tjeridf8');
        }
        return json_decode($request->getBody());
    }

    public function actions($ticketId)
    {
        try {
            $request = $this->api->request('cards', $ticketId . '/actions', $this->api::GET, []);
        } catch (Exception $h) {
            return;
        }
        if ($request->getStatusCode() !== 200) {
            throw new Exception('435zifhdjkl7tpej');
        }
        return json_decode($request->getBody());
    }


}
