<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 20.08.18
 * Time: 09:02
 */

namespace Inensus\Ticket\Trello;


use App\Exceptions\TrelloAPIException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Inensus\Ticket\Models\TicketBoard;
use function json_decode;

class Lists
{
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param $name
     * @param TicketBoard $board
     * @return mixed
     * @throws Exception
     */
    public function createList($name, TicketBoard $board)
    {
        $board->board_id;
        try {
            $request = $this->api->request('lists', null, $this->api::POST, [
                'name' => $name,
                'idBoard' => $board->board_id,
            ]);

            return json_decode($request->getBody());
        }catch (GuzzleException $e){
            throw new TrelloAPIException($e->getMessage());
        }

    }

    public function getByName(string $listName)
    {

    }
}
