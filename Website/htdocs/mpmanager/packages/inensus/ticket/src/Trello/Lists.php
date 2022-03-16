<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 20.08.18
 * Time: 09:02
 */

namespace Inensus\Ticket\Trello;


use Exception;
use Inensus\Ticket\Models\Board;
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
     * @param Board $board
     * @return mixed
     * @throws Exception
     */
    public function createList($name, Board $board)
    {
        $board->board_id;
        $request = $this->api->request('lists', null, $this->api::POST, [
            'name' => $name,
            'idBoard' => $board->board_id,
        ]);
        if ($request->getStatusCode() !== 200) {
            throw new Exception('38fmdskjckew9e');
        }
        return json_decode($request->getBody());

    }

    public function getByName(string $listName)
    {

    }
}
